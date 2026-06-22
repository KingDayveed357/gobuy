<?php

namespace Tests\Feature;

use App\Modules\Catalog\Models\Product;
use App\Modules\Logistics\Database\Seeders\LogisticsSeeder;
use App\Modules\Logistics\Enums\ShipmentStatus;
use App\Modules\Logistics\Models\PickupLocation;
use App\Modules\Order\Models\Order;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutLogisticsTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LogisticsSeeder::class);
        Http::fake(['*/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://paystack.test/x']])]);
    }

    private function addToCart(int $weightG = 1000): Product
    {
        $product = Product::factory()->priced(10000)->stock(20)->create(['weight_g' => $weightG]);
        $this->post(route('cart.store'), ['product_variant_id' => $product->primaryVariant()->id, 'quantity' => 2]);

        return $product;
    }

    public function test_home_delivery_creates_a_shipment_with_zone_and_weight_fee(): void
    {
        $this->addToCart(weightG: 1000); // 2 × 1kg = 2kg

        $this->post(route('checkout.store'), [
            'customer_name' => 'Ada Obi', 'customer_email' => 'ada@example.com', 'customer_phone' => '0803',
            'delivery_method' => 'home_delivery', 'payment_method' => 'paystack',
            'address_line' => '12 Marina', 'city' => 'Port Harcourt', 'state' => 'Rivers',
        ])->assertRedirect('https://paystack.test/x');

        $order = Order::latest('id')->first();
        $shipment = $order->shipment;
        $this->assertSame('home_delivery', $shipment->method);
        $this->assertSame('Port Harcourt & Rivers', $shipment->zone->name);
        // Rivers base ₦1,000 + 2kg × ₦200 = ₦1,400.
        $this->assertSame(Money::fromNaira(1400)->kobo, $order->delivery_fee->kobo);
        $this->assertSame(ShipmentStatus::Pending, $shipment->status);
    }

    public function test_pickup_is_free_and_snapshots_the_location_address(): void
    {
        $location = PickupLocation::first();
        $this->addToCart();

        $this->post(route('checkout.store'), [
            'customer_name' => 'Ada Obi', 'customer_email' => 'ada@example.com', 'customer_phone' => '0803',
            'delivery_method' => 'pickup', 'pickup_location_id' => $location->id, 'payment_method' => 'paystack',
        ])->assertRedirect('https://paystack.test/x');

        $order = Order::latest('id')->first();
        $this->assertTrue($order->delivery_fee->isZero());
        $this->assertTrue($order->shipment->isPickup());
        $this->assertSame($location->id, $order->shipment->pickup_location_id);
        $this->assertSame($location->address, $order->address_line); // snapshotted
    }

    public function test_delivery_quote_endpoint_returns_a_fee(): void
    {
        $this->addToCart(weightG: 1000);

        $this->postJson(route('checkout.delivery-quote'), ['delivery_method' => 'home_delivery', 'state' => 'Rivers'])
            ->assertOk()
            ->assertJson(['zone' => 'Port Harcourt & Rivers'])
            ->assertJsonPath('fee_kobo', Money::fromNaira(1400)->kobo);
    }
}
