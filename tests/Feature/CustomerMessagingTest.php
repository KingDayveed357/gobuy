<?php

namespace Tests\Feature;

use App\Modules\Catalog\Models\Product;
use App\Modules\Logistics\Models\Shipment;
use App\Modules\Logistics\Services\ShipmentService;
use App\Modules\Notification\Notifications\OrderAcceptedMessage;
use App\Modules\Notification\Notifications\ShipmentStageMessage;
use App\Modules\Order\Models\Order;
use App\Modules\Payment\Services\PaymentService;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CustomerMessagingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_accepting_an_order_sends_the_customer_a_confirmation_message(): void
    {
        Mail::fake();
        Notification::fake();

        $product = Product::factory()->stock(10)->create();
        $order = Order::factory()->create(['customer_phone' => '08030000000']);
        $order->items()->create([
            'product_variant_id' => $product->primaryVariant()->id,
            'name' => 'Widget', 'sku' => 'W1',
            'unit_price' => Money::fromNaira(1000), 'quantity' => 1, 'line_total' => Money::fromNaira(1000),
        ]);

        app(PaymentService::class)->completeOrder($order->fresh());

        Notification::assertSentOnDemand(OrderAcceptedMessage::class);
    }

    public function test_each_shipment_stage_messages_the_customer(): void
    {
        Notification::fake();

        $order = Order::factory()->create(['customer_phone' => '08030000000']);
        $shipment = $order->shipment()->create(['method' => Shipment::METHOD_HOME, 'status' => 'pending']);

        $service = app(ShipmentService::class);
        $service->advance($shipment); // pending -> packed
        $service->advance($shipment->fresh()); // packed -> dispatched

        Notification::assertSentOnDemandTimes(ShipmentStageMessage::class, 2);
    }

    public function test_order_without_a_phone_is_not_messaged(): void
    {
        Mail::fake();
        Notification::fake();

        $product = Product::factory()->stock(10)->create();
        $order = Order::factory()->create(['customer_phone' => '']);
        $order->items()->create([
            'product_variant_id' => $product->primaryVariant()->id,
            'name' => 'Widget', 'sku' => 'W1',
            'unit_price' => Money::fromNaira(1000), 'quantity' => 1, 'line_total' => Money::fromNaira(1000),
        ]);

        app(PaymentService::class)->completeOrder($order->fresh());

        Notification::assertSentOnDemandTimes(OrderAcceptedMessage::class, 0);
    }
}
