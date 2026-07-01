<?php

namespace Tests\Feature\Returns;

use App\Models\User;
use App\Modules\Cart\Models\CartItem;
use App\Modules\Catalog\Models\Product;
use App\Modules\Order\Models\Order;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\Returns\Exceptions\InsufficientStoreCredit;
use App\Modules\Returns\Services\StoreCreditService;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StoreCreditRedemptionTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function customer(): array
    {
        return [
            'customer_name' => 'Ada Obi', 'customer_email' => 'ada@example.com', 'customer_phone' => '08030000000',
            'delivery_method' => 'home_delivery', 'address_line' => '12 Marina', 'city' => 'Lagos', 'state' => 'Lagos',
        ];
    }

    private function grant(User $user, int $naira): void
    {
        app(StoreCreditService::class)->issue($user, Money::fromNaira($naira), null, 'seed-'.uniqid());
    }

    public function test_balance_reflects_issued_credit(): void
    {
        $user = User::factory()->create();
        $this->grant($user, 5000);

        $this->assertSame(500000, app(StoreCreditService::class)->balanceFor($user)->kobo);
    }

    public function test_spend_is_guarded_against_overspending(): void
    {
        $user = User::factory()->create();
        $this->grant($user, 1000);

        $this->expectException(InsufficientStoreCredit::class);
        app(StoreCreditService::class)->spend($user, Money::fromNaira(5000));
    }

    public function test_spend_is_idempotent_on_the_key(): void
    {
        $user = User::factory()->create();
        $this->grant($user, 5000);

        app(StoreCreditService::class)->spend($user, Money::fromNaira(2000), null, 'spend-1');
        app(StoreCreditService::class)->spend($user, Money::fromNaira(2000), null, 'spend-1');

        $this->assertSame(300000, app(StoreCreditService::class)->balanceFor($user)->kobo); // only spent once
    }

    public function test_credit_covering_the_whole_bill_places_a_paid_order_without_the_gateway(): void
    {
        Http::fake(); // gateway must NOT be called
        $user = User::factory()->create();
        $this->grant($user, 100000); // ₦100k — comfortably covers the order
        $product = Product::factory()->priced(5000)->stock(10)->create();

        $this->actingAs($user)->post(route('cart.store'), ['product_variant_id' => $product->primaryVariant()->id, 'quantity' => 1]);
        $this->actingAs($user)->post(route('checkout.store-credit'), ['apply' => 1]);

        $this->actingAs($user)->post(route('checkout.store'), $this->customer())
            ->assertRedirect();

        $order = Order::first();
        $this->assertSame('paid', $order->payment_status->value);
        $this->assertTrue($order->amountDue()->isZero());
        $this->assertTrue($order->store_credit_applied->isPositive());
        // Credit was spent exactly once, on acceptance.
        $this->assertSame($order->total->kobo, 10000000 - app(StoreCreditService::class)->balanceFor($user)->kobo);
        // Every payment method converges: the cart is cleared on a completed checkout.
        $this->assertSame(0, CartItem::count());
        Http::assertNothingSent();
    }

    public function test_partial_credit_charges_only_the_remainder_via_gateway(): void
    {
        Http::fake(['*/transaction/initialize' => Http::response([
            'status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/x'],
        ])]);
        $user = User::factory()->create();
        $this->grant($user, 2000); // ₦2,000 credit
        $product = Product::factory()->priced(5000)->stock(10)->create();

        $this->actingAs($user)->post(route('cart.store'), ['product_variant_id' => $product->primaryVariant()->id, 'quantity' => 1]);
        $this->actingAs($user)->post(route('checkout.store-credit'), ['apply' => 1]);

        $this->actingAs($user)->post(route('checkout.store'), $this->customer())
            ->assertRedirect('https://checkout.paystack.com/x');

        $order = Order::first();
        $this->assertSame(200000, $order->store_credit_applied->kobo);
        // Pending payment is for the remainder, not the full total.
        $this->assertSame($order->total->kobo - 200000, $order->payment->amount->kobo);
        // Credit is reserved on the order but only spent at acceptance.
        $this->assertSame(200000, app(StoreCreditService::class)->balanceFor($user)->kobo);

        // Simulate gateway success → credit is consumed.
        Http::fake(['*/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success']])]);
        app(PaymentService::class)->verifyAndComplete($order->payment->reference);

        $this->assertSame(0, app(StoreCreditService::class)->balanceFor($user->fresh())->kobo);
        $this->assertSame('paid', $order->fresh()->payment_status->value);
    }
}
