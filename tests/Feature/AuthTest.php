<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Cart\Models\Cart;
use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_registration_page_renders(): void
    {
        $this->get(route('register'))->assertOk()->assertSee('Create your account');
    }

    public function test_a_user_can_register_and_is_logged_in(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'New Buyer',
            'email' => 'new@example.com',
            'phone' => '08030000000',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('account.dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'new@example.com',
            'role' => User::ROLE_CUSTOMER,
            'customer_type' => User::TYPE_RETAIL,
        ]);
    }

    public function test_a_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['email' => 'me@example.com', 'password' => Hash::make('secret123')]);

        $this->post(route('login'), ['email' => 'me@example.com', 'password' => 'secret123'])
            ->assertRedirect(route('account.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'me@example.com', 'password' => Hash::make('secret123')]);

        $this->post(route('login'), ['email' => 'me@example.com', 'password' => 'wrong'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_user_can_logout(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('logout'))->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_guest_cart_merges_into_user_on_login(): void
    {
        $product = Product::factory()->create(['stock' => 50]);
        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 2]);

        $user = User::factory()->create(['email' => 'merge@example.com', 'password' => Hash::make('secret123')]);

        $this->post(route('login'), ['email' => 'merge@example.com', 'password' => 'secret123']);

        $cart = Cart::firstWhere('user_id', $user->id);
        $this->assertNotNull($cart);
        $this->assertDatabaseHas('cart_items', ['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 2]);
    }
}
