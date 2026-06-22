<?php

namespace Tests\Feature;

use App\Modules\Cart\Services\CartService;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Order\Models\Order;
use App\Modules\Pricing\Models\Coupon;
use App\Modules\Pricing\Services\CouponService;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CouponCheckoutTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function customer(): array
    {
        return [
            'customer_name' => 'Ada Obi',
            'customer_email' => 'ada@example.com',
            'customer_phone' => '08030000000',
            'delivery_method' => 'home_delivery',
            'address_line' => '12 Marina Road',
            'city' => 'Lagos',
            'state' => 'Lagos',
        ];
    }

    private function cartWith(Product $product, int $qty = 1): void
    {
        $this->post(route('cart.store'), ['product_variant_id' => $product->primaryVariant()->id, 'quantity' => $qty]);
    }

    public function test_a_percentage_coupon_applies_a_discount_to_the_cart(): void
    {
        $product = Product::factory()->priced(5000)->stock(10)->create();
        $coupon = Coupon::factory()->percentage(10)->create(['code' => 'SAVE10']);
        $this->cartWith($product);

        $this->post(route('cart.coupon.apply'), ['code' => 'save10'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame('SAVE10', session(CouponService::SESSION_KEY));

        $this->get(route('cart.index'))->assertOk()->assertSee('SAVE10');
    }

    public function test_an_invalid_code_is_rejected_with_an_error(): void
    {
        $product = Product::factory()->priced(5000)->stock(10)->create();
        $this->cartWith($product);

        $this->post(route('cart.coupon.apply'), ['code' => 'NOPE'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNull(session(CouponService::SESSION_KEY));
    }

    public function test_a_coupon_below_minimum_cart_value_is_rejected(): void
    {
        $product = Product::factory()->priced(1000)->stock(10)->create();
        Coupon::factory()->fixed(500)->create(['code' => 'BIG', 'min_cart_value' => 5000]);
        $this->cartWith($product);

        $this->post(route('cart.coupon.apply'), ['code' => 'BIG'])
            ->assertSessionHas('error');
        $this->assertNull(session(CouponService::SESSION_KEY));
    }

    public function test_placing_an_order_records_the_discount_and_redeems_the_coupon(): void
    {
        Http::fake([
            '*/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://checkout.paystack.com/abc123'],
            ]),
        ]);

        $product = Product::factory()->priced(5000)->stock(10)->create();
        $coupon = Coupon::factory()->percentage(10)->create(['code' => 'SAVE10']);
        $this->cartWith($product, 2); // subtotal 10,000

        $this->post(route('cart.coupon.apply'), ['code' => 'SAVE10']);
        $this->post(route('checkout.store'), $this->customer())
            ->assertRedirect('https://checkout.paystack.com/abc123');

        // 10% of ₦10,000 = ₦1,000 discount → 100,000 kobo.
        $this->assertDatabaseHas('orders', [
            'subtotal' => 1000000,
            'discount_amount' => 100000,
            'coupon_id' => $coupon->id,
            'coupon_code' => 'SAVE10',
        ]);
        $this->assertDatabaseHas('coupon_usages', ['coupon_id' => $coupon->id, 'order_id' => Order::first()->id]);

        // Total = subtotal - discount + delivery; coupon cleared from session.
        $order = Order::first();
        $this->assertSame(900000, $order->total->minus($order->delivery_fee)->kobo);
        $this->assertNull(session(CouponService::SESSION_KEY));
    }

    public function test_a_fixed_coupon_only_discounts_matching_categories_when_scoped(): void
    {
        $eligible = Category::factory()->create();
        $other = Category::factory()->create();
        $cheap = Product::factory()->priced(2000)->stock(10)->create(['category_id' => $eligible->id]);
        $excluded = Product::factory()->priced(8000)->stock(10)->create(['category_id' => $other->id]);

        $coupon = Coupon::factory()->fixed(3000)->create(['code' => 'CAT3K']);
        $coupon->categories()->attach($eligible->id);

        $this->cartWith($cheap);     // ₦2,000 eligible
        $this->cartWith($excluded);  // ₦8,000 not eligible

        $service = app(CouponService::class);
        $summary = app(CartService::class)->summary();
        $result = $service->evaluate('CAT3K', $summary['subtotal'], null, collect($summary['lines']));

        // Fixed ₦3,000 capped at the ₦2,000 eligible subtotal.
        $this->assertTrue($result['ok']);
        $this->assertSame(Money::fromNaira(2000)->kobo, $result['discount']->kobo);
    }
}
