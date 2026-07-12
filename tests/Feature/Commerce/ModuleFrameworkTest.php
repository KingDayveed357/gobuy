<?php

namespace Tests\Feature\Commerce;

use App\Admin\Support\AdminNavigation;
use App\Modules\Order\Enums\SalesChannel;
use App\Modules\Order\Models\Order;
use App\Support\Commerce\CommerceModules;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class ModuleFrameworkTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    public function test_orders_default_to_the_web_channel(): void
    {
        $order = Order::factory()->create();

        $this->assertSame('web', $order->channel);
        $this->assertSame('Website', $order->channelLabel());
    }

    public function test_an_unknown_channel_still_gets_a_human_label(): void
    {
        // A module-introduced channel the Core has never heard of must not throw.
        $this->assertSame('Walk In', SalesChannel::labelFor('walk_in'));
    }

    public function test_a_module_route_is_404_until_its_module_is_enabled(): void
    {
        $this->app->instance(CommerceModules::class, $modules = new CommerceModules([
            'demo' => ['label' => 'Demo', 'depends' => [], 'shipped' => true],
        ]));

        Route::middleware(['web', 'module:demo'])->get('/__module_probe', fn () => 'reached');

        $this->get('/__module_probe')->assertNotFound();

        $modules->enable('demo');
        $this->get('/__module_probe')->assertOk()->assertSee('reached');
    }

    public function test_navigation_hides_entries_owned_by_a_disabled_module(): void
    {
        $this->app->instance(CommerceModules::class, new CommerceModules([
            'demo' => ['label' => 'Demo', 'depends' => [], 'shipped' => true],
        ]));

        config(['admin-navigation' => [
            ['type' => 'link', 'label' => 'Core Thing', 'route' => 'admin.dashboard', 'active' => ['admin.dashboard']],
            ['type' => 'link', 'label' => 'Ops Thing', 'route' => 'admin.dashboard', 'active' => ['admin.dashboard'], 'module' => 'demo'],
        ]]);

        $admin = $this->actingAsAdmin('Super Admin');
        $labels = collect(app(AdminNavigation::class)->resolve($admin))->pluck('label');

        $this->assertContains('Core Thing', $labels);
        $this->assertNotContains('Ops Thing', $labels, 'a disabled module must not appear in the nav');
    }
}
