<?php

namespace App\Modules\Returns\Services;

use App\Admin\Models\Admin;
use App\Models\User;
use App\Modules\Order\Models\Order;
use App\Modules\Returns\Enums\ReturnReason;
use App\Modules\Returns\Enums\ReturnStatus;
use App\Modules\Returns\Events\ReturnRequested;
use App\Modules\Returns\Models\ReturnRequest;
use App\Modules\Returns\StateMachines\ReturnStateMachine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Creates and progresses customer return requests. Guards against returning more
 * units than were bought (counting units already locked in other open returns)
 * and is idempotent on a client-supplied key.
 */
class ReturnRequestService
{
    public function __construct(
        private readonly ReturnEligibilityService $eligibility,
        private readonly ReturnStateMachine $machine,
        private readonly ReturnShippingService $shipping,
    ) {}

    /**
     * @param  array<int, array{order_item_id: int, quantity: int, reason_code?: string, condition_reported?: string}>  $lines
     */
    public function create(
        Order $order,
        ?User $user,
        array $lines,
        string $reasonCode,
        string $refundDestination,
        ?string $customerNote = null,
        ?string $idempotencyKey = null,
    ): ReturnRequest {
        if ($idempotencyKey !== null) {
            $existing = ReturnRequest::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing; // replay — never create twice
            }
        }

        $check = $this->eligibility->forOrder($order, $user);
        if (! $check['eligible']) {
            throw new RuntimeException($check['reason'] ?? 'This order cannot be returned.');
        }

        if ($lines === []) {
            throw new RuntimeException('Select at least one item to return.');
        }

        return DB::transaction(function () use ($order, $user, $lines, $reasonCode, $refundDestination, $customerNote, $idempotencyKey, $check): ReturnRequest {
            // Lock the order so concurrent requests can't both pass the quantity guard.
            Order::query()->lockForUpdate()->find($order->id);

            $eligibleItems = $order->items()->with('variant.product.category')->get()->keyBy('id');
            $reasons = [];

            $resolved = [];
            foreach ($lines as $line) {
                $orderItem = $eligibleItems->get($line['order_item_id']);
                if ($orderItem === null) {
                    throw new RuntimeException('One of the items is not part of this order.');
                }

                $blockReason = $this->eligibility->itemBlockReason($orderItem, $order);
                if ($blockReason !== null) {
                    throw new RuntimeException($blockReason);
                }

                $quantity = (int) $line['quantity'];
                $allowed = $orderItem->returnableQuantity() - $this->unitsLockedInOpenReturns($orderItem->id);
                if ($quantity < 1 || $quantity > $allowed) {
                    throw new RuntimeException("You can return at most {$allowed} of \"{$orderItem->name}\".");
                }

                $lineReason = $line['reason_code'] ?? $reasonCode;
                $reasons[] = $lineReason;
                $resolved[] = [$orderItem, $quantity, $lineReason, $line['condition_reported'] ?? null];
            }

            $return = ReturnRequest::create([
                'reference' => $this->generateReference(),
                'order_id' => $order->id,
                'user_id' => $user?->id,
                'status' => ReturnStatus::Requested,
                'reason_code' => $reasonCode,
                'customer_note' => $customerNote,
                'refund_destination' => $refundDestination,
                'return_shipping_payer' => $this->shippingPayer($reasons),
                'window_expires_at' => $check['window_expires_at'],
                'idempotency_key' => $idempotencyKey,
            ]);

            foreach ($resolved as [$orderItem, $quantity, $lineReason, $condition]) {
                $return->items()->create([
                    'order_item_id' => $orderItem->id,
                    'product_variant_id' => $orderItem->product_variant_id,
                    'quantity' => $quantity,
                    'unit_price_snapshot' => $orderItem->unit_price, // what they PAID
                    'reason_code' => $lineReason,
                    'condition_reported' => $condition,
                ]);
            }

            $this->machine->record($return, 'created', $user, null, ReturnStatus::Requested, [
                'item_count' => count($resolved),
            ]);

            // Risk scoring + auto-approval happen in the ScoreReturnRisk listener
            // (synchronously today; the event seam lets it move fully async later).
            ReturnRequested::dispatch($return);

            return $return->refresh();
        });
    }

    public function cancel(ReturnRequest $return, ?Model $actor = null): void
    {
        $this->machine->transitionTo($return, ReturnStatus::Cancelled, $actor, 'cancelled');
    }

    /**
     * Approve a return (by an admin, or the system on auto-approval), issue its
     * return label, and move it to awaiting-shipment. Shared by the admin
     * console and the auto-approval engine so both behave identically.
     */
    public function approve(ReturnRequest $return, ?Admin $admin = null, bool $auto = false): void
    {
        $return->update(['approved_by' => $admin?->id, 'auto_approved' => $auto]);

        $this->machine->transitionTo($return, ReturnStatus::Approved, $admin, $auto ? 'auto_approved' : 'approved');

        $shipment = $this->shipping->issueLabel($return);
        $this->machine->transitionTo($return, ReturnStatus::AwaitingShipment, $admin, 'label_issued', [
            'tracking_reference' => $shipment->tracking_reference,
            'payer' => $shipment->payer,
        ]);
    }

    /**
     * Units of an order line already committed to other still-open returns, so
     * a second request can't re-claim the same physical units.
     */
    private function unitsLockedInOpenReturns(int $orderItemId, ?int $ignoreReturnId = null): int
    {
        return (int) ReturnRequest::query()
            ->whereNotIn('status', [
                ReturnStatus::Rejected->value,
                ReturnStatus::Cancelled->value,
                ReturnStatus::Expired->value,
            ])
            ->when($ignoreReturnId, fn ($q) => $q->whereKeyNot($ignoreReturnId))
            ->whereHas('items', fn ($q) => $q->where('order_item_id', $orderItemId))
            ->with('items')
            ->get()
            ->flatMap->items
            ->where('order_item_id', $orderItemId)
            ->sum('quantity');
    }

    /**
     * @param  array<int, string>  $reasonCodes
     */
    private function shippingPayer(array $reasonCodes): string
    {
        foreach ($reasonCodes as $code) {
            $reason = ReturnReason::tryFrom($code);
            if ($reason?->isMerchantFault()) {
                return 'merchant';
            }
        }

        return 'customer';
    }

    private function generateReference(): string
    {
        do {
            $reference = 'RMA-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        } while (ReturnRequest::where('reference', $reference)->exists());

        return $reference;
    }
}
