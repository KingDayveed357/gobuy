<?php

namespace Tests\Feature\Catalog;

use App\Modules\Catalog\Mail\BackInStockMail;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\StockNotification;
use App\Modules\Catalog\Services\BackInStockService;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BackInStockTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_shopper_can_register_for_a_back_in_stock_alert(): void
    {
        $variant = Product::factory()->stock(0)->create()->primaryVariant();

        $this->post(route('back-in-stock.store'), [
            'product_variant_id' => $variant->id,
            'email' => 'Buyer@Example.com',
        ])->assertRedirect();

        $this->assertDatabaseHas('stock_notifications', [
            'product_variant_id' => $variant->id,
            'email' => 'buyer@example.com', // stored lower-cased
        ]);
    }

    public function test_registering_twice_does_not_duplicate(): void
    {
        $variant = Product::factory()->stock(0)->create()->primaryVariant();
        $service = app(BackInStockService::class);

        $service->register($variant, 'buyer@example.com');
        $service->register($variant, 'buyer@example.com');

        $this->assertSame(1, StockNotification::count());
    }

    public function test_restocking_emails_waiters_and_clears_the_queue(): void
    {
        Mail::fake();

        $variant = Product::factory()->stock(0)->create()->primaryVariant();
        app(BackInStockService::class)->register($variant, 'buyer@example.com');

        // Replenish via the audited admin adjustment path (0 -> 5) — fires the observer.
        app(InventoryService::class)->adjust($variant, 5);

        Mail::assertQueued(BackInStockMail::class);
        $this->assertDatabaseMissing('stock_notifications', ['product_variant_id' => $variant->id]);
    }

    public function test_no_email_while_still_out_of_stock(): void
    {
        Mail::fake();

        $variant = Product::factory()->stock(0)->create()->primaryVariant();
        app(BackInStockService::class)->register($variant, 'buyer@example.com');

        app(BackInStockService::class)->flush($variant->refresh()); // still 0

        Mail::assertNothingQueued();
        $this->assertDatabaseHas('stock_notifications', ['product_variant_id' => $variant->id]);
    }
}
