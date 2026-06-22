<?php

namespace Tests\Feature\Admin;

use App\Admin\Notifications\LowStockNotification;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Services\CatalogService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class LowStockAlertTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    public function test_admins_are_alerted_when_a_sale_crosses_the_low_stock_threshold(): void
    {
        $admin = $this->actingAsAdmin('Super Admin');
        Notification::fake();

        $product = Product::factory()->create();
        $variant = $product->primaryVariant();
        $variant->update(['stock' => 6, 'low_stock_threshold' => 5]);

        app(CatalogService::class)->decrementStock($variant->fresh(), 2); // 6 -> 4, crosses 5

        Notification::assertSentTo($admin, LowStockNotification::class);
    }

    public function test_no_alert_when_stock_stays_above_threshold(): void
    {
        $this->actingAsAdmin('Super Admin');
        Notification::fake();

        $product = Product::factory()->create();
        $variant = $product->primaryVariant();
        $variant->update(['stock' => 50, 'low_stock_threshold' => 5]);

        app(CatalogService::class)->decrementStock($variant->fresh(), 2); // 50 -> 48

        Notification::assertNothingSent();
    }
}
