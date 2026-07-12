<?php

namespace App\Modules\Operations\WalkIn\Services;

use App\Admin\Models\Admin;
use App\Models\User;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Order\Enums\PaymentMethod;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderService;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\Pricing\Services\PricingEngine;
use App\Modules\Pricing\Services\TaxCalculator;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Records an in-store / walk-in sale as a first-class Order on the `walk_in`
 * channel — then commits it through the SAME pipeline as a web order
 * ({@see PaymentService::completeOrder}: stock deduction via the inventory
 * ledger, status advance, events). No parallel POS order system: a walk-in sale
 * is just an order that came through a different door and is paid on the spot.
 */
class WalkInSaleService
{
    public function __construct(
        private readonly PricingEngine $pricing,
        private readonly TaxCalculator $tax,
        private readonly OrderService $orders,
        private readonly PaymentService $payments,
    ) {}

    /**
     * @param  list<array{variant_id: int, quantity: int}>  $lines
     * @param  array{name?: ?string, phone?: ?string}  $customer
     */
    public function record(array $lines, PaymentMethod $method, bool $wholesale = false, array $customer = [], ?Admin $admin = null): Order
    {
        if ($lines === []) {
            throw new \InvalidArgumentException('A sale needs at least one item.');
        }

        // A transient wholesale user makes the pricing engine apply wholesale +
        // quantity-tier pricing without persisting a customer.
        $pricingCustomer = $wholesale ? new User(['customer_type' => User::TYPE_WHOLESALE]) : null;

        return DB::transaction(function () use ($lines, $method, $wholesale, $customer, $pricingCustomer): Order {
            $subtotal = Money::zero();
            $taxAmount = Money::zero();
            $exclusiveTax = Money::zero();
            $prepared = [];

            foreach ($lines as $line) {
                $variant = ProductVariant::with('product')->findOrFail($line['variant_id']);
                $quantity = max(1, (int) $line['quantity']);
                $price = $this->pricing->priceForVariant($variant, $pricingCustomer, $quantity);
                $lineTotal = $price->unitPrice->times($quantity);

                $subtotal = $subtotal->plus($lineTotal);
                $vat = $this->tax->lineVat($variant->product, $lineTotal);
                $taxAmount = $taxAmount->plus($vat);
                if ($this->tax->isExclusive($variant->product)) {
                    $exclusiveTax = $exclusiveTax->plus($vat);
                }

                $prepared[] = ['variant' => $variant, 'quantity' => $quantity, 'unit' => $price->unitPrice, 'lineTotal' => $lineTotal];
            }

            $total = $subtotal->plus($exclusiveTax); // no delivery fee in-store

            $order = Order::create([
                'order_number' => $this->orders->generateOrderNumber(),
                'channel' => 'walk_in',
                'customer_type' => $wholesale ? User::TYPE_WHOLESALE : User::TYPE_RETAIL,
                'customer_name' => $customer['name'] ?? null,
                'customer_phone' => $customer['phone'] ?? null,
                'payment_method' => $method,
                'subtotal' => $subtotal,
                'discount_amount' => Money::zero(),
                'delivery_fee' => Money::zero(),
                'tax_amount' => $taxAmount,
                'total' => $total,
                'placed_at' => now(),
            ]);

            foreach ($prepared as $item) {
                $variant = $item['variant'];
                $label = $variant->is_default ? $variant->product->name : "{$variant->product->name} — {$variant->label()}";

                $order->items()->create([
                    'product_variant_id' => $variant->id,
                    'name' => $label,
                    'sku' => $variant->sku,
                    'unit_price' => $item['unit'],
                    'quantity' => $item['quantity'],
                    'line_total' => $item['lineTotal'],
                ]);
            }

            // The tender, recorded as a settled payment.
            Payment::create([
                'order_id' => $order->id,
                'provider' => $method->value,
                'reference' => 'WALKIN-'.strtoupper(Str::random(10)),
                'amount' => $total,
                'status' => 'success',
                'paid_at' => now(),
            ]);

            // Commit exactly like a web order: deducts stock through the ledger
            // (stamped with this order), advances status, marks paid, fires events.
            $order->load('items');
            $this->payments->completeOrder($order, paymentReceived: true);

            return $order->fresh(['items']);
        });
    }
}
