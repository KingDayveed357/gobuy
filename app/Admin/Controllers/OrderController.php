<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\InvalidOrderTransition;
use App\Modules\Order\Services\OrderStatusService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function __construct(private readonly OrderStatusService $status) {}

    public function index(Request $request): View
    {
        $orders = $this->filtered($request)
            ->withCount('items')
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'statuses' => OrderStatus::cases(),
        ]);
    }

    /**
     * Stream the (filtered) orders as a CSV — chunked so a large catalog never
     * loads fully into memory.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = $this->filtered($request)->withCount('items');

        $columns = ['Order #', 'Date', 'Customer', 'Email', 'Phone', 'Status', 'Payment', 'Items', 'Subtotal', 'Discount', 'Delivery', 'Total'];

        return response()->streamDownload(function () use ($query, $columns): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);

            $query->chunk(200, function ($orders) use ($out): void {
                foreach ($orders as $order) {
                    fputcsv($out, [
                        $order->order_number,
                        $order->created_at?->toDateString(),
                        $order->customer_name,
                        $order->customer_email,
                        $order->customer_phone,
                        $order->status->value,
                        $order->payment_status->value,
                        $order->items_count,
                        $order->subtotal->toNaira(),
                        $order->discount_amount->toNaira(),
                        $order->delivery_fee->toNaira(),
                        $order->total->toNaira(),
                    ]);
                }
            });

            fclose($out);
        }, 'orders-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Base query shared by the listing and the CSV export (same filters).
     */
    private function filtered(Request $request): Builder
    {
        return Order::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), function ($q) use ($request): void {
                $term = $request->string('q')->toString();
                $q->where(fn ($sub) => $sub->where('order_number', 'like', "%{$term}%")
                    ->orWhere('customer_email', 'like', "%{$term}%")
                    ->orWhere('customer_name', 'like', "%{$term}%"));
            })
            ->latest();
    }

    public function show(Order $order): View
    {
        $order->load(['items.variant.product.media', 'statusHistories', 'payment', 'user']);

        return view('admin.orders.show', [
            'order' => $order,
            'allowedTransitions' => $order->status->allowedTransitions(),
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->status->transitionTo($order, OrderStatus::from($validated['status']), $validated['note'] ?? null);
        } catch (InvalidOrderTransition $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "Order moved to {$order->status->label()}.");
    }
}
