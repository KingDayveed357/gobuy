<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_toggle_adds_then_removes_a_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->stock(5)->create();

        $this->actingAs($user)->post(route('wishlist.toggle', $product))->assertRedirect();
        $this->assertDatabaseHas('wishlist_items', ['user_id' => $user->id, 'product_id' => $product->id]);

        $this->actingAs($user)->post(route('wishlist.toggle', $product))->assertRedirect();
        $this->assertDatabaseMissing('wishlist_items', ['user_id' => $user->id, 'product_id' => $product->id]);
    }

    public function test_toggle_returns_json_when_requested(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->stock(5)->create();

        $this->actingAs($user)->postJson(route('wishlist.toggle', $product))
            ->assertOk()
            ->assertJson(['wished' => true, 'count' => 1]);
    }

    public function test_moving_a_wishlist_item_to_cart_adds_it_and_clears_the_wish(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->stock(5)->create();
        $user->wishlistItems()->create(['product_id' => $product->id]);

        $this->actingAs($user)->post(route('wishlist.to-cart', $product))
            ->assertRedirect(route('cart.index'));

        $this->assertDatabaseMissing('wishlist_items', ['user_id' => $user->id, 'product_id' => $product->id]);
        $this->assertDatabaseHas('cart_items', ['product_variant_id' => $product->primaryVariant()->id]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $product = Product::factory()->create();

        $this->post(route('wishlist.toggle', $product))->assertRedirect(route('login'));
    }

    public function test_wishlist_page_renders_with_breadcrumb_and_paginates_beyond_nine(): void
    {
        $user = User::factory()->create();
        Product::factory()->count(11)->create()
            ->each(fn ($p) => $user->wishlistItems()->create(['product_id' => $p->id]));

        $this->actingAs($user)->get(route('wishlist.index'))
            ->assertOk()
            ->assertSee('Account')      // breadcrumb retained
            ->assertSee('(11)')         // total count in the heading
            ->assertSee('wire:click="gotoPage(2)"', false);      // paginated at 9 per page
    }

    public function test_merge_persists_guest_items_and_deduplicates(): void
    {
        $user = User::factory()->create();
        [$a, $b, $c] = Product::factory()->count(3)->create();
        $user->wishlistItems()->create(['product_id' => $a->id]); // already saved

        $this->actingAs($user)->postJson(route('wishlist.merge'), [
            'product_ids' => [$a->id, $b->id, $c->id, 999999], // dup + new + invalid
        ])->assertOk()->assertJson(['count' => 3]);

        $this->assertSame(3, $user->wishlistItems()->count());
        $this->assertDatabaseHas('wishlist_items', ['user_id' => $user->id, 'product_id' => $b->id]);
        $this->assertDatabaseMissing('wishlist_items', ['user_id' => $user->id, 'product_id' => 999999]);
    }
}
