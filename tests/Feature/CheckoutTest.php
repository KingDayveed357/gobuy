<?php

namespace Tests\Feature;

use App\Modules\Catalog\Models\Product;
use App\Modules\Order\Models\Order;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutTest extends TestCase
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

    public function test_checkout_redirects_to_cart_when_empty(): void
    {
        $this->get(route('checkout.show'))->assertRedirect(route('cart.index'));
    }

    public function test_checkout_page_renders_with_items(): void
    {
        $product = Product::factory()->stock(10)->create();
        $this->post(route('cart.store'), ['product_variant_id' => $product->primaryVariant()->id, 'quantity' => 2]);

        $this->get(route('checkout.show'))->assertOk()->assertSee('Check out');
    }

    public function test_placing_an_order_creates_order_with_snapshots_and_redirects_to_paystack(): void
    {
        Http::fake([
            '*/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://checkout.paystack.com/abc123'],
            ]),
        ]);

        $product = Product::factory()->priced(5000)->stock(10)->create(['name' => 'Boxed Widget']);
        $this->post(route('cart.store'), ['product_variant_id' => $product->primaryVariant()->id, 'quantity' => 2]);

        $response = $this->post(route('checkout.store'), $this->customer());

        $response->assertRedirect('https://checkout.paystack.com/abc123');

        // Money is stored as integer kobo (Naira × 100).
        $this->assertDatabaseHas('orders', [
            'customer_email' => 'ada@example.com',
            'subtotal' => 1000000,
            'payment_status' => 'unpaid',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('order_items', [
            'name' => 'Boxed Widget',
            'unit_price' => 500000,
            'quantity' => 2,
            'line_total' => 1000000,
        ]);
        $this->assertDatabaseHas('payments', ['status' => 'pending', 'amount' => Order::first()->total->kobo]);

        // Stock is NOT decremented until payment is confirmed.
        $this->assertSame(10, $product->primaryVariant()->fresh()->stock);
    }

    public function test_checkout_validates_input(): void
    {
        $product = Product::factory()->stock(10)->create();
        $this->post(route('cart.store'), ['product_variant_id' => $product->primaryVariant()->id, 'quantity' => 1]);

        $this->post(route('checkout.store'), [])
            ->assertSessionHasErrors(['customer_name', 'customer_email', 'customer_phone', 'delivery_method']);
    }

    public function test_order_item_snapshot_survives_product_change(): void
    {
        Http::fake(['*/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://paystack.test/x']])]);

        $product = Product::factory()->priced(5000)->stock(10)->create(['name' => 'Original Name']);
        $this->post(route('cart.store'), ['product_variant_id' => $product->primaryVariant()->id, 'quantity' => 1]);
        $this->post(route('checkout.store'), $this->customer());

        $product->update(['name' => 'Renamed']);
        $product->primaryVariant()->update(['retail_price' => Money::fromNaira(9999)]);

        $this->assertDatabaseHas('order_items', ['name' => 'Original Name', 'unit_price' => 500000]); // kobo
    }
}
