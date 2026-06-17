<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class CustomerListTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin('Super Admin');
    }

    public function test_admin_sees_customers(): void
    {
        User::factory()->create(['name' => 'Real Customer']);

        $this->get(route('admin.customers.index'))
            ->assertOk()
            ->assertSee('Real Customer');
    }

    public function test_customers_can_be_filtered_by_type(): void
    {
        User::factory()->create(['name' => 'Retail Rita']);
        User::factory()->wholesale()->create(['name' => 'Wholesale Wale']);

        $this->get(route('admin.customers.index', ['type' => 'wholesale']))
            ->assertOk()
            ->assertSee('Wholesale Wale')
            ->assertDontSee('Retail Rita');
    }
}
