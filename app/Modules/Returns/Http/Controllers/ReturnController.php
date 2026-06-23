<?php

namespace App\Modules\Returns\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Order\Models\Order;
use App\Modules\Returns\Enums\RefundDestination;
use App\Modules\Returns\Enums\ReturnReason;
use App\Modules\Returns\Enums\ReturnStatus;
use App\Modules\Returns\Http\Requests\StoreReturnRequest;
use App\Modules\Returns\Models\ReturnRequest;
use App\Modules\Returns\Services\ReturnEligibilityService;
use App\Modules\Returns\Services\ReturnRequestService;
use App\Modules\Returns\Services\ReturnShippingService;
use App\Modules\Returns\StateMachines\ReturnStateMachine;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class ReturnController extends Controller
{
    public function __construct(
        private readonly ReturnRequestService $returns,
        private readonly ReturnEligibilityService $eligibility,
        private readonly ReturnShippingService $shipping,
        private readonly ReturnStateMachine $machine,
    ) {}

    public function index(): View
    {
        $returns = ReturnRequest::where('user_id', auth()->id())
            ->with('items')
            ->latest()
            ->paginate(10);

        return view('account.returns.index', ['returns' => $returns]);
    }

    public function create(Order $order): View|RedirectResponse
    {
        abort_unless($order->user_id === auth()->id(), 403);

        $check = $this->eligibility->forOrder($order, auth()->user());

        if (! $check['eligible']) {
            return redirect()->route('account.orders')->with('error', $check['reason']);
        }

        return view('account.returns.create', [
            'order' => $order,
            'eligibleItems' => $check['items'],
            'blocked' => $check['blocked'],
            'windowExpiresAt' => $check['window_expires_at'],
            'reasons' => ReturnReason::cases(),
            'destinations' => RefundDestination::cases(),
        ]);
    }

    public function store(StoreReturnRequest $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === auth()->id(), 403);

        try {
            $return = $this->returns->create(
                order: $order,
                user: $request->user(),
                lines: $request->lines(),
                reasonCode: $request->validated('reason_code'),
                refundDestination: $request->validated('refund_destination'),
                customerNote: $request->validated('customer_note'),
                idempotencyKey: $request->header('Idempotency-Key') ?: $request->input('idempotency_key'),
            );

            foreach ((array) $request->file('photos', []) as $photo) {
                $return->addMedia($photo)->toMediaCollection(ReturnRequest::MEDIA_PHOTOS);
            }
        } catch (Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('account.returns.show', $return)
            ->with('status', "Return {$return->reference} submitted. We'll review it shortly.");
    }

    public function show(ReturnRequest $return): View
    {
        abort_unless($return->user_id === auth()->id(), 403);

        $return->load(['items.orderItem', 'events', 'order']);

        return view('account.returns.show', ['return' => $return]);
    }

    public function label(ReturnRequest $return): View
    {
        abort_unless($return->user_id === auth()->id(), 403);

        $shipment = $return->returnShipment;
        abort_if($shipment === null, 404);

        return view('account.returns.label', [
            'return' => $return->load('order'),
            'shipment' => $shipment,
            'dropoffAddress' => config('gobuy.returns.dropoff_address'),
        ]);
    }

    public function markShipped(ReturnRequest $return): RedirectResponse
    {
        abort_unless($return->user_id === auth()->id(), 403);

        if ($return->status !== ReturnStatus::AwaitingShipment) {
            return back()->with('error', 'This return is not awaiting shipment.');
        }

        try {
            if ($return->returnShipment) {
                $this->shipping->markShipped($return->returnShipment);
            }
            $this->machine->transitionTo($return, ReturnStatus::InTransit, auth()->user(), 'shipped_by_customer');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "Thanks! We'll process your return once it arrives.");
    }

    public function reply(Request $request, ReturnRequest $return): RedirectResponse
    {
        abort_unless($return->user_id === auth()->id(), 403);

        if ($return->status !== ReturnStatus::InfoRequested) {
            return back()->with('error', 'This return is not awaiting your reply.');
        }

        $data = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['image', 'max:5120'],
        ]);

        try {
            foreach ((array) $request->file('photos', []) as $photo) {
                $return->addMedia($photo)->toMediaCollection(ReturnRequest::MEDIA_PHOTOS);
            }

            $this->machine->record($return, 'customer_reply', auth()->user(), null, null, ['message' => $data['message']]);
            $this->machine->transitionTo($return, ReturnStatus::Requested, auth()->user(), 'customer_replied');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Thanks — your reply has been sent to our team.');
    }

    public function cancel(ReturnRequest $return): RedirectResponse
    {
        abort_unless($return->user_id === auth()->id(), 403);

        if (! $return->status->isOpen()) {
            return back()->with('error', 'This return can no longer be cancelled.');
        }

        try {
            $this->returns->cancel($return, auth()->user());
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Return cancelled.');
    }
}
