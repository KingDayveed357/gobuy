<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Returns\Services\StoreCreditService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class StoreCreditAdminTest extends TestCase
{
    use InteractsWithAdmin;
    use LazilyRefreshDatabase;

    public function test_admin_can_issue_store_credit_to_a_customer(): void
    {
        $this->actingAsAdmin('Admin'); // has manage_refunds
        $customer = User::factory()->create(['email' => 'cust@example.com']);

        $this->post(route('admin.store-credits.issue'), [
            'email' => 'cust@example.com',
            'amount' => 2500,
            'reason' => 'Service recovery',
        ])->assertRedirect();

        $this->assertSame(250000, app(StoreCreditService::class)->balanceFor($customer)->kobo);
        $this->assertDatabaseHas('store_credit_entries', ['type' => 'refund_credit', 'amount' => 250000, 'reason' => 'Service recovery']);
    }

    public function test_a_role_without_manage_refunds_cannot_access_store_credit(): void
    {
        $this->actingAsAdmin('Support'); // manage_returns but NOT manage_refunds
        $this->get(route('admin.store-credits.index'))->assertForbidden();
    }
}
