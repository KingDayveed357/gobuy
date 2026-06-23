<?php

namespace App\Admin\Controllers;

use App\Admin\Models\Admin;
use App\Admin\Notifications\ReturnSettlementFailedNotification;
use App\Http\Controllers\Controller;
use App\Modules\Returns\Enums\ReturnItemDisposition;
use App\Modules\Returns\Enums\ReturnStatus;
use App\Modules\Returns\Exceptions\ReturnSettlementFailed;
use App\Modules\Returns\Models\ReturnRequest;
use App\Modules\Returns\Services\ReturnAnalyticsService;
use App\Modules\Returns\Services\ReturnRequestService;
use App\Modules\Returns\Services\ReturnSettlementService;
use App\Modules\Returns\Services\ReturnShippingService;
use App\Modules\Returns\StateMachines\ReturnStateMachine;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ReturnController extends Controller
{
    public function __construct(
        private readonly ReturnStateMachine $machine,
        private readonly ReturnSettlementService $settlement,
        private readonly ReturnShippingService $shipping,
        private readonly ReturnRequestService $returns,
    ) {}

    public function index(Request $request): View
    {
        $returns = ReturnRequest::query()
            ->with(['order:id,order_number', 'items'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), function ($q) use ($request): void {
                $term = $request->string('q')->toString();
                $q->where('reference', 'like', "%{$term}%")
                    ->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', "%{$term}%"));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.returns.index', [
            'returns' => $returns,
            'statuses' => ReturnStatus::cases(),
            'kpis' => app(ReturnAnalyticsService::class)->kpis(),
        ]);
    }

    public function show(ReturnRequest $return): View
    {
        $return->load(['items.orderItem', 'events.actor', 'order', 'user', 'media', 'returnShipment']);

        return view('admin.returns.show', ['return' => $return]);
    }

    /**
     * Stream the (filtered) return queue as a CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = ReturnRequest::query()
            ->with(['order:id,order_number', 'items'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest();

        $columns = ['Reference', 'Order', 'Status', 'Reason', 'Risk', 'Refund to', 'Items', 'Refunded', 'Created'];

        return response()->streamDownload(function () use ($query, $columns): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);

            $query->chunk(200, function ($returns) use ($out): void {
                foreach ($returns as $return) {
                    fputcsv($out, [
                        $return->reference,
                        $return->order?->order_number,
                        $return->status->value,
                        $return->reason_code,
                        $return->risk_score,
                        $return->refund_destination->value,
                        $return->items->sum('quantity'),
                        $return->refunded_total->toNaira(),
                        $return->created_at?->toDateString(),
                    ]);
                }
            });

            fclose($out);
        }, 'returns-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function approve(ReturnRequest $return): RedirectResponse
    {
        return $this->guarded(
            $return,
            fn () => $this->returns->approve($return, Auth::guard('admin')->user()),
            'Return approved and label issued.',
        );
    }

    public function deny(Request $request, ReturnRequest $return): RedirectResponse
    {
        $note = $request->validate(['note' => ['nullable', 'string', 'max:500']])['note'] ?? null;

        return $this->guarded($return, fn () => $this->machine->transitionTo(
            $return,
            ReturnStatus::Rejected,
            Auth::guard('admin')->user(),
            'denied',
            array_filter(['note' => $note]),
        ), 'Return denied.');
    }

    public function requestInfo(Request $request, ReturnRequest $return): RedirectResponse
    {
        $message = $request->validate(['message' => ['required', 'string', 'max:500']])['message'];

        return $this->guarded($return, fn () => $this->machine->transitionTo(
            $return,
            ReturnStatus::InfoRequested,
            Auth::guard('admin')->user(),
            'info_requested',
            ['message' => $message],
        ), 'Information requested from the customer.');
    }

    public function receive(ReturnRequest $return): RedirectResponse
    {
        return $this->guarded($return, function () use ($return): void {
            $return->update(['received_by' => Auth::guard('admin')->id()]);
            if ($return->returnShipment) {
                $this->shipping->markReceived($return->returnShipment);
            }
            $this->machine->transitionTo($return, ReturnStatus::Received, Auth::guard('admin')->user(), 'received');
        }, 'Return marked as received.');
    }

    /**
     * Record per-item inspection outcomes (disposition + approved quantity).
     */
    public function inspect(Request $request, ReturnRequest $return): RedirectResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.disposition' => ['required', 'in:restock,damaged,reject'],
            'items.*.approved_quantity' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($data['items'] as $itemId => $row) {
            $item = $return->items()->whereKey($itemId)->first();
            if ($item === null) {
                continue;
            }

            $item->update([
                'disposition' => ReturnItemDisposition::from($row['disposition']),
                'approved_quantity' => min((int) $row['approved_quantity'], $item->quantity),
            ]);
        }

        if ($return->status === ReturnStatus::Received) {
            $this->machine->transitionTo($return, ReturnStatus::Inspecting, Auth::guard('admin')->user(), 'inspected');
        } else {
            $this->machine->record($return, 'inspected', Auth::guard('admin')->user());
        }

        return back()->with('status', 'Inspection saved.');
    }

    /**
     * Settle the return — restock, refund/credit, close. Requires the
     * money-moving permission (separation of duties from review).
     */
    public function settle(ReturnRequest $return): RedirectResponse
    {
        abort_unless(Auth::guard('admin')->user()->can('manage_refunds'), 403);

        try {
            $result = $this->settlement->settle($return, Auth::guard('admin')->user());
        } catch (ReturnSettlementFailed $e) {
            // Alert the returns team — the gateway declined the refund.
            $admins = Admin::where('is_active', true)->get()
                ->filter(fn ($a) => $a->can('manage_returns'));
            Notification::send($admins, new ReturnSettlementFailedNotification($return));

            return back()->with('error', 'The payment provider declined the refund. The team has been alerted — please retry shortly.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $message = $result['settled']
            ? 'Return settled — '.money($result['amount']).' via '.str_replace('_', ' ', (string) $result['via']).'.'
            : 'Return closed with no refund due.';

        return redirect()->route('admin.returns.show', $return)->with('status', $message);
    }

    /**
     * Run an admin action, catching illegal-transition errors into a flash.
     */
    private function guarded(ReturnRequest $return, callable $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.returns.show', $return)->with('status', $success);
    }
}
