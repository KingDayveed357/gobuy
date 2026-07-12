<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Dashboard\Overview;
use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin('Super Admin');
    }

    public function test_the_dashboard_shell_renders_a_skeleton_placeholder_before_hydration(): void
    {
        // A lazy component renders its placeholder on the initial (no-JS) request.
        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('gb-skel', false)
            ->assertSeeLivewire(Overview::class);
    }

    public function test_the_hydrated_overview_shows_kpis_and_the_recent_orders_table(): void
    {
        Livewire::test(Overview::class)
            ->assertSee('Revenue (paid)')
            ->assertSee('Recent orders')
            ->assertSee('Low stock');
    }

    public function test_the_overview_lists_a_low_stock_product(): void
    {
        Product::factory()->stock(2)->create(['name' => 'Almost Gone Gin']);

        Livewire::test(Overview::class)
            ->assertSee('Almost Gone Gin');
    }

    public function test_the_placeholder_is_pure_skeleton(): void
    {
        $html = view('livewire.admin.dashboard.placeholder')->render();

        $this->assertStringContainsString('gb-skel', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html);
    }
}
