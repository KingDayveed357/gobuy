<?php

namespace App\Modules\Order\Services;

use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Events\OrderStatusChanged;
use App\Modules\Order\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The single guarded entry point for changing an order's status.
 * Enforces the state machine and records an audit trail.
 */
class OrderStatusService
{
    public function transitionTo(Order $order, OrderStatus $target, ?string $note = null): void
    {
        $current = $order->status;

        if ($current === $target) {
            return; // idempotent — already there
        }

        if (! $current->canTransitionTo($target)) {
            throw InvalidOrderTransition::between($current, $target);
        }

        DB::transaction(function () use ($order, $target, $note): void {
            $attributes = ['status' => $target];

            // Stamp the authoritative delivery time once — it starts the return
            // window. Don't overwrite it on later edits/re-deliveries.
            if ($target === OrderStatus::Delivered && $order->delivered_at === null) {
                $attributes['delivered_at'] = now();
            }

            $order->update($attributes);
            $order->statusHistories()->create(['status' => $target, 'note' => $note]);
        });

        Log::info('Order status transitioned', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'from' => $current->value,
            'to' => $target->value,
        ]);

        // Single domain event every transition flows through — fulfilment sync,
        // notifications and future concerns subscribe instead of being inlined.
        OrderStatusChanged::dispatch($order, $current, $target);
    }

    public function recordInitial(Order $order): void
    {
        $order->statusHistories()->create([
            'status' => $order->status,
            'note' => 'Order placed',
        ]);
    }
}
