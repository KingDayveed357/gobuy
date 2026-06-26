<?php

namespace App\Modules\Order\Services;

use App\Models\User;
use App\Modules\Order\DTOs\CheckoutData;
use App\Modules\Order\Models\Order;
use App\Modules\Pricing\Models\Coupon;
use App\Modules\Pricing\Services\TaxCalculator;
use App\Modules\Pricing\ValueObjects\ResolvedPrice;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(private readonly TaxCalculator $tax) {}

    /**
     * Create an order (and its snapshotted items) from a priced cart summary.
     * The delivery fee is computed upstream (zone/weight aware) and passed in.
     *
     * A validated coupon (with its already-computed discount) may be passed in;
     * the discount is subtracted from the subtotal before fees and tax.
     *
     * @param  array{lines: array<int, array{item: mixed, price: ResolvedPrice, lineTotal: Money}>, subtotal: Money, count: int}  $summary
     */
    public function createFromCart(CheckoutData $data, array $summary, ?User $user, ?Money $deliveryFee = null, ?Coupon $coupon = null, ?Money $discount = null): Order
    {
        $deliveryFee ??= Money::fromNaira(config('gobuy.delivery_fee'));
        $subtotal = $summary['subtotal'];

        // A coupon never discounts more than the subtotal.
        $discount ??= Money::zero();
        if ($discount->lessThan($subtotal)) {
            $discounted = $subtotal->minus($discount);
        } else {
            $discount = $subtotal;
            $discounted = Money::zero();
        }

        // VAT: extracted (inclusive) for the record; added to total when exclusive.
        $taxAmount = Money::zero();
        $exclusiveTax = Money::zero();
        foreach ($summary['lines'] as $line) {
            $product = $line['item']->variant->product;
            $vat = $this->tax->lineVat($product, $line['lineTotal']);
            $taxAmount = $taxAmount->plus($vat);
            if ($this->tax->isExclusive($product)) {
                $exclusiveTax = $exclusiveTax->plus($vat);
            }
        }

        $total = $discounted->plus($deliveryFee)->plus($exclusiveTax);

        return DB::transaction(function () use ($data, $summary, $user, $subtotal, $deliveryFee, $taxAmount, $total, $coupon, $discount): Order {
            $order = Order::create([
                ...$data->toOrderAttributes(),
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $user?->id,
                'customer_type' => $user?->customer_type ?? User::TYPE_RETAIL,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'delivery_fee' => $deliveryFee,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'placed_at' => now(),
                'expires_at' => now()->addMinutes(60),
                'checkout_token' => $data->checkoutToken,
            ]);

            foreach ($summary['lines'] as $line) {
                $variant = $line['item']->variant;
                $product = $variant->product;
                $label = $variant->is_default ? $product->name : "{$product->name} — {$variant->label()}";

                $order->items()->create([
                    'product_variant_id' => $variant->id,
                    'name' => $label,
                    'sku' => $variant->sku,
                    'unit_price' => $line['price']->unitPrice,
                    'quantity' => $line['item']->quantity,
                    'line_total' => $line['lineTotal'],
                ]);
            }

            return $order;
        });
    }

    public function generateOrderNumber(): string
    {
        do {
            $number = 'GB-'.now()->format('ymd').'-'.strtoupper(Str::random(5));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
