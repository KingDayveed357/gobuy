<?php

namespace Tests\Feature;

use App\Livewire\Storefront\Checkout\Summary;
use App\Models\User;
use App\Modules\Cart\Models\CartItem;
use App\Modules\Catalog\Models\Product;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\CheckoutCalculator;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\Pricing\Models\Coupon;
use App\Modules\Pricing\Services\CouponService;
use App\Modules\Returns\Services\StoreCreditService;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression coverage for the payment-security audit fixes:
 *  - Bug 1 / C1: a cancelled gateway payment must NOT consume the coupon or wipe
 *    the shopper's checkout state.
 *  - H1: confirming the same order twice (browser callback racing the webhook)
 *    must apply side effects exactly once.
 *  - Bug 2 / H2: the store-credit toggle binds to the checkbox without inverting.
 */
class PaymentHardeningTest extends TestCase
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

    private function placeCouponedPaystackOrder(Coupon $coupon, Product $product): Order
    {
        Http::fake(['*/transaction/initialize' => Http::response([
            'status' => true,
            'data' => ['authorization_url' => 'https://paystack.test/redirect'],
        ])]);

        $this->post(route('cart.store'), ['product_variant_id' => $product->primaryVariant()->id, 'quantity' => 2]);
        $this->post(route('cart.coupon.apply'), ['code' => $coupon->code]);
        $this->post(route('checkout.store'), $this->customer())
            ->assertRedirect('https://paystack.test/redirect');

        return Order::firstOrFail();
    }

    public function test_cancelled_payment_preserves_coupon_and_cart_and_never_redeems(): void
    {
        $product = Product::factory()->priced(5000)->stock(10)->create();
        $coupon = Coupon::factory()->percentage(10)->create(['code' => 'SAVE10']);

        $order = $this->placeCouponedPaystackOrder($coupon, $product);

        // Shopper cancels on Paystack → the charge did not succeed.
        Http::fake(['*/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'failed']])]);
        $this->get(route('payment.callback', ['reference' => $order->payment->reference]));

        // The coupon is still applied, its usage limit untouched, and the cart and
        // stock are preserved so the shopper can retry.
        $this->assertNotNull(session(CouponService::SESSION_KEY));
        $this->assertDatabaseCount('coupon_usages', 0);
        $this->assertGreaterThan(0, CartItem::count());
        $this->assertSame(10, $product->primaryVariant()->fresh()->stock);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'failed', 'status' => 'pending']);
    }

    public function test_confirming_an_order_twice_applies_side_effects_exactly_once(): void
    {
        $product = Product::factory()->priced(5000)->stock(10)->create();
        $coupon = Coupon::factory()->percentage(10)->create(['code' => 'SAVE10']);

        $order = $this->placeCouponedPaystackOrder($coupon, $product);

        // Simulate the browser callback and the webhook both completing the order.
        $payments = app(PaymentService::class);
        $payments->completeOrder($order, paymentReceived: true);
        $payments->completeOrder($order->fresh(), paymentReceived: true);

        // Stock decremented once (10 → 8, not 6) and the coupon redeemed once.
        $this->assertSame(8, $product->primaryVariant()->fresh()->stock);
        $this->assertDatabaseCount('coupon_usages', 1);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid']);
    }

    public function test_store_credit_toggle_binds_without_inverting_and_applies_in_totals(): void
    {
        $user = User::factory()->create();
        app(StoreCreditService::class)->issue($user, Money::fromNaira(5000), null, 'grant-1', 'Test grant');
        $this->actingAs($user);

        $product = Product::factory()->priced(5000)->stock(5)->create();
        $this->post(route('cart.store'), ['product_variant_id' => $product->primaryVariant()->id, 'quantity' => 1]);

        Livewire::test(Summary::class)
            ->assertSet('applyCredit', false)
            ->set('applyCredit', true)      // ON
            ->assertSet('applyCredit', true)
            ->assertSee('Store credit')      // applied-discount row now rendered
            ->set('applyCredit', false)     // OFF
            ->assertSet('applyCredit', false);

        // Final state persisted to the session the order placement reads from.
        $this->assertFalse((bool) session(CheckoutCalculator::CREDIT_SESSION_KEY));
    }
}
