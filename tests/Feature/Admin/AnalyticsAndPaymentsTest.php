<?php

namespace Tests\Feature\Admin;

use App\Modules\Order\Models\Order;
use App\Modules\Payment\Models\Payment;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class AnalyticsAndPaymentsTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    public function test_admin_sees_analytics_with_data(): void
    {
        $this->actingAsAdmin('Admin');
        Order::factory()->paid()->create()->items()->create([
            'product_variant_id' => null, 'name' => 'Hot Product', 'sku' => 'HP1', 'unit_price' => 5000, 'quantity' => 3, 'line_total' => 15000,
        ]);

        $this->get(route('admin.analytics'))
            ->assertOk()
            ->assertSee('Revenue')
            ->assertSee('Hot Product');
    }

    public function test_top_products_uses_the_ranked_bi_component_not_the_old_table(): void
    {
        $this->actingAsAdmin('Admin');
        Order::factory()->paid()->create()->items()->create([
            'product_variant_id' => null, 'name' => 'Hot Product', 'sku' => 'HP1',
            'unit_price' => 5000, 'quantity' => 3, 'line_total' => 15000,
        ]);

        $this->get(route('admin.analytics'))
            ->assertOk()
            ->assertSee('Top products')
            ->assertSee('of paid revenue', false)   // the share-of-total headline
            ->assertSee('gb-rbl-bar', false)         // inline magnitude bar
            ->assertSee('% of total', false)         // per-row contribution
            ->assertDontSee('Product performance table'); // the removed redundant table
    }

    public function test_analytics_details_are_grouped_into_progressive_disclosure_tabs(): void
    {
        $this->actingAsAdmin('Admin');

        $this->get(route('admin.analytics'))
            ->assertOk()
            ->assertSee('id="analyticsTabs"', false)
            ->assertSee('id="tab-revenue"', false)
            ->assertSee('id="tab-products"', false)
            ->assertSee('id="tab-customers"', false)
            // The executive KPIs + business summary stay above the tabs.
            ->assertSee('Business summary');
    }

    public function test_payments_list_renders(): void
    {
        $this->actingAsAdmin('Admin');
        $order = Order::factory()->paid()->create();
        Payment::create(['order_id' => $order->id, 'reference' => 'GB-PAY-LIST', 'amount' => $order->total, 'status' => 'success', 'paid_at' => now()]);

        $this->get(route('admin.payments.index'))
            ->assertOk()
            ->assertSee('GB-PAY-LIST');
    }

    public function test_support_cannot_view_analytics(): void
    {
        $this->actingAsAdmin('Support'); // Support lacks view_analytics

        $this->get(route('admin.analytics'))->assertForbidden();
    }
}
