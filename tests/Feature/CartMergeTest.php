<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Services\CartService;
use App\Modules\Catalog\Models\Product;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CartMergeTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_cart_merges_into_user_cart_on_login(): void
    {
        $token = 'guest-token-123';
        session(['cart_token' => $token]);

        $shared = Product::factory()->create(['stock' => 100]);
        $guestOnly = Product::factory()->create(['stock' => 100]);

        $guestCart = Cart::factory()->create(['session_token' => $token]);
        $guestCart->items()->create(['product_id' => $shared->id, 'quantity' => 2]);
        $guestCart->items()->create(['product_id' => $guestOnly->id, 'quantity' => 1]);

        $user = User::factory()->create();
        $userCart = Cart::factory()->forUser($user->id)->create();
        $userCart->items()->create(['product_id' => $shared->id, 'quantity' => 3]);

        app(CartService::class)->mergeGuestCartIntoUser($user);

        // Shared product: 3 (user) + 2 (guest) = 5
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $userCart->id,
            'product_id' => $shared->id,
            'quantity' => 5,
        ]);
        // Guest-only product moved into the user cart
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $userCart->id,
            'product_id' => $guestOnly->id,
            'quantity' => 1,
        ]);
        // Guest cart removed, session token cleared
        $this->assertDatabaseMissing('carts', ['id' => $guestCart->id]);
        $this->assertNull(session('cart_token'));
    }

    public function test_merged_quantity_is_capped_at_stock(): void
    {
        $token = 'guest-token-cap';
        session(['cart_token' => $token]);

        $product = Product::factory()->create(['stock' => 4]);

        $guestCart = Cart::factory()->create(['session_token' => $token]);
        $guestCart->items()->create(['product_id' => $product->id, 'quantity' => 3]);

        $user = User::factory()->create();
        $userCart = Cart::factory()->forUser($user->id)->create();
        $userCart->items()->create(['product_id' => $product->id, 'quantity' => 3]);

        app(CartService::class)->mergeGuestCartIntoUser($user);

        // 3 + 3 = 6, capped at stock 4
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $userCart->id,
            'product_id' => $product->id,
            'quantity' => 4,
        ]);
    }

    public function test_login_event_triggers_merge(): void
    {
        $token = 'guest-token-login';
        session(['cart_token' => $token]);

        $product = Product::factory()->create(['stock' => 100]);
        $guestCart = Cart::factory()->create(['session_token' => $token]);
        $guestCart->items()->create(['product_id' => $product->id, 'quantity' => 2]);

        $user = User::factory()->create();

        event(new Login('web', $user, false));

        $this->assertDatabaseHas('carts', ['user_id' => $user->id]);
        $this->assertDatabaseHas('cart_items', ['product_id' => $product->id, 'quantity' => 2]);
        $this->assertDatabaseMissing('carts', ['id' => $guestCart->id]);
    }
}
