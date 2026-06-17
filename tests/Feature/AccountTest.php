<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Order\Models\Order;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_cannot_view_account(): void
    {
        $this->get(route('account.dashboard'))->assertRedirect(route('login'));
        $this->get(route('account.orders'))->assertRedirect(route('login'));
    }

    public function test_dashboard_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create(['name' => 'Jane Buyer']);

        $this->actingAs($user)->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee('Jane Buyer');
    }

    public function test_user_sees_only_their_own_orders(): void
    {
        $user = User::factory()->create();
        $mine = Order::factory()->create(['user_id' => $user->id]);
        $theirs = Order::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->actingAs($user)->get(route('account.orders'))
            ->assertOk()
            ->assertSee($mine->order_number)
            ->assertDontSee($theirs->order_number);
    }
}
