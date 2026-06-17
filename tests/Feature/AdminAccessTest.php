<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.products.index'))->assertRedirect(route('admin.login'));
    }

    public function test_customer_web_session_does_not_grant_admin_access(): void
    {
        // A logged-in customer is still a guest on the separate admin guard.
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_super_admin_can_access_everything(): void
    {
        $this->actingAsAdmin('Super Admin');

        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.products.index'))->assertOk();
        $this->get(route('admin.orders.index'))->assertOk();
        $this->get(route('admin.customers.index'))->assertOk();
    }

    public function test_manager_can_manage_catalog_and_orders_but_not_customers(): void
    {
        $this->actingAsAdmin('Manager');

        $this->get(route('admin.products.index'))->assertOk();
        $this->get(route('admin.orders.index'))->assertOk();
        $this->get(route('admin.customers.index'))->assertForbidden();
    }

    public function test_support_can_handle_orders_and_customers_but_not_products(): void
    {
        $this->actingAsAdmin('Support');

        $this->get(route('admin.orders.index'))->assertOk();
        $this->get(route('admin.customers.index'))->assertOk();
        $this->get(route('admin.products.index'))->assertForbidden();
    }
}
