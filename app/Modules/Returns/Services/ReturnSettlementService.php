<?php

namespace App\Modules\Returns\Services;

use App\Admin\Models\Admin;
use App\Modules\Catalog\Services\CatalogService;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Enums\PaymentStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderStatusService;
use App\Modules\Payment\Services\RefundService;
use App\Modules\Returns\Enums\RefundDestination;
use App\Modules\Returns\Enums\ReturnItemDisposition;
use App\Modules\Returns\Enums\ReturnStatus;
use App\Modules\Returns\Events\ReturnSettled;
use App\Modules\Returns\Exceptions\ReturnSettlementFailed;
use App\Modules\Returns\Models\ReturnRequest;
use App\Modules\Returns\StateMachines\ReturnStateMachine;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Settles a received return: restocks accepted items, returns the money
 * (store-credit-first, original-method when chosen and possible), and advances
 * the return to a terminal state. The whole operation is one transaction with a
 * row lock on the order so concurrent settlements can never over-refund.
 */
class ReturnSettlementService
{
    public function __construct(
        private readonly CatalogService $catalog,
        private readonly RefundService $refunds,
        private readonly StoreCreditService $credit,
        private readonly ReturnStateMachine $machine,
        private readonly OrderStatusService $orderStatus,
    ) {}

    /**
     * @return array{settled: bool, amount: Money, via: ?string}
     */
    public function settle(ReturnRequest $return, Admin $admin): array
    {
        if ($return->status->isSettled()) {
            return ['settled' => false, 'amount' => Money::zero(), 'via' => null]; // idempotent replay
        }

        if (! in_array($return->status, [ReturnStatus::Received, ReturnStatus::Inspecting], true)) {
            throw new RuntimeException('A return must be received before it can be settled.');
        }

        $result = DB::transaction(function () use ($return, $admin): array {
            /** @var Order $order */
            $order = Order::query()->lockForUpdate()->findOrFail($return->order_id);
            $return->load(['items.orderItem', 'items.variant']);

            // 1. Approved lines + gross goods value (what they paid, pre-coupon).
            $grossKobo = 0;
            $approved = [];
            foreach ($return->items as $item) {
                $disposition = $item->disposition ?? ReturnItemDisposition::Restock;
                if (! $disposition->isApproved()) {
                    continue;
                }
                $qty = $item->effectiveQuantity();
                if ($qty < 1) {
                    continue;
                }
                $grossKobo += $item->unit_price_snapshot->kobo * $qty;
                $approved[] = [$item, $disposition, $qty];
            }

            if ($approved === []) {
                $this->machine->transitionTo($return, ReturnStatus::Rejected, $admin, 'settled_no_approved_items');

                return ['settled' => false, 'amount' => Money::zero(), 'via' => null];
            }

            // 2. Net the order-level discount (coupon) proportionally, then cap
            //    at whatever the order still has left to refund.
            $netKobo = $this->applyDiscountProration($order, $grossKobo);
            $settleKobo = min($netKobo, $order->refundableRemaining()->kobo);
            $amount = Money::fromKobo(max(0, $settleKobo));

            // 3. Restock accepted units and tally them against the order line.
            foreach ($approved as [$item, $disposition, $qty]) {
                if ($disposition->shouldRestock() && $item->variant) {
                    $this->catalog->restock($item->variant, $qty);
                }

                $item->update([
                    'disposition' => $disposition,
                    'approved_quantity' => $qty,
                    'restocked' => $disposition->shouldRestock(),
                ]);

                $item->orderItem?->increment('returned_quantity', $qty);
            }

            // 4. Money settlement — store credit unless the customer chose their
            //    original method AND that method can actually be reversed.
            $via = null;
            if ($amount->isPositive()) {
                $useStoreCredit = $return->refund_destination === RefundDestination::StoreCredit
                    || $order->payment === null   // POD/never-paid-online → credit
                    || $return->user === null;    // no wallet owner → fall through below

                if ($useStoreCredit && $return->user !== null) {
                    $this->credit->issue(
                        $return->user, $amount, $return,
                        "return-credit:{$return->id}", "Refund for return {$return->reference}", $admin,
                    );
                    // Store credit also consumes the order's refundable balance.
                    Order::whereKey($order->id)->update(['refunded_total' => DB::raw('refunded_total + '.$amount->kobo)]);
                    $via = 'store_credit';
                } else {
                    // Original method (gateway) or a recorded manual reversal.
                    $refund = $this->refunds->refundForReturn($order, $admin, $amount, "Return {$return->reference}");

                    // A declined gateway refund aborts the whole settlement so it
                    // can be retried — nothing is left half-settled.
                    if ($refund->status === 'failed') {
                        throw new ReturnSettlementFailed("Gateway refund failed for return {$return->reference}.");
                    }

                    $via = 'original';
                }
            }

            // 5. Advance the return + record what we settled.
            $return->update(['refunded_total' => $amount->kobo, 'settled_by' => $admin->id]);
            $target = $via === 'store_credit' ? ReturnStatus::Credited : ReturnStatus::Refunded;
            $this->machine->transitionTo($return, $target, $admin, 'settled', [
                'amount_kobo' => $amount->kobo,
                'via' => $via,
            ]);
            $this->machine->transitionTo($return, ReturnStatus::Closed, $admin, 'closed');

            // 6. If the entire order is now refunded, reflect that on the order.
            $this->maybeMarkOrderFullyRefunded($order);

            return ['settled' => true, 'amount' => $amount, 'via' => $via];
        });

        if ($result['settled']) {
            ReturnSettled::dispatch($return, $result['amount']->kobo, $result['via']);
        }

        return $result;
    }

    /**
     * Scale a gross goods value down by the order's discount ratio so a partial
     * return only refunds the net amount the customer actually paid.
     */
    private function applyDiscountProration(Order $order, int $grossKobo): int
    {
        $subtotalKobo = $order->subtotal->kobo;
        $discountKobo = $order->discount_amount->kobo;

        if ($subtotalKobo <= 0 || $discountKobo <= 0) {
            return $grossKobo;
        }

        return (int) round($grossKobo * ($subtotalKobo - $discountKobo) / $subtotalKobo);
    }

    private function maybeMarkOrderFullyRefunded(Order $order): void
    {
        $order->refresh();

        if ($order->payment_status === PaymentStatus::Paid && $order->refundableRemaining()->isZero()) {
            $order->update(['payment_status' => PaymentStatus::Refunded]);

            try {
                $this->orderStatus->transitionTo($order, OrderStatus::Refunded, 'Fully refunded via returns');
            } catch (\Throwable) {
                // Order already in a terminal state — leave it as is.
            }
        }
    }
}
