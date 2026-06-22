<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class StoreSettingsAndExportTest extends TestCase
{
    use InteractsWithAdmin;
    use LazilyRefreshDatabase;

    public function test_admin_can_save_store_settings_and_they_persist(): void
    {
        $this->actingAsAdmin('Super Admin');

        $this->post(route('admin.settings.store.update'), [
            'store_name' => 'GoBuy PH',
            'store_email' => 'hello@gobuy.test',
            'instagram_url' => 'https://instagram.com/gobuy',
        ])->assertRedirect();

        $this->assertSame('GoBuy PH', Setting::get('store_name'));
        $this->assertSame('https://instagram.com/gobuy', Setting::get('instagram_url'));
    }

    public function test_saved_settings_surface_on_the_storefront(): void
    {
        Setting::putMany(['store_name' => 'GoBuy PH', 'instagram_url' => 'https://instagram.com/gobuy']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('GoBuy PH')
            ->assertSee('https://instagram.com/gobuy', false);
    }

    public function test_orders_export_streams_csv_with_filtered_rows(): void
    {
        $this->actingAsAdmin('Super Admin');
        $product = Product::factory()->priced(5000)->stock(5)->create();
        Order::factory()->create(['order_number' => 'GB-EXPORT-1', 'status' => OrderStatus::Delivered, 'customer_name' => 'Ada']);

        $response = $this->get(route('admin.orders.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Order #', $response->streamedContent());
        $this->assertStringContainsString('GB-EXPORT-1', $response->streamedContent());
    }

    public function test_customers_export_streams_csv(): void
    {
        $this->actingAsAdmin('Super Admin');
        User::factory()->create(['role' => User::ROLE_CUSTOMER, 'name' => 'Chidi Export', 'email' => 'chidi@example.com']);

        $response = $this->get(route('admin.customers.export'));

        $response->assertOk();
        $this->assertStringContainsString('chidi@example.com', $response->streamedContent());
    }
}
