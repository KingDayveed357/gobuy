<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Customer\Models\Address;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CheckoutAddressTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_checkout_prefills_the_default_shipping_address(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->for($user)->defaultShipping()->create([
            'recipient_name' => 'Ada Obi',
            'line1' => '12 Marina Road',
            'city' => 'Lagos',
            'state' => 'Lagos',
        ]);

        $product = Product::factory()->stock(10)->create();
        $this->actingAs($user)->post(route('cart.store'), [
            'product_variant_id' => $product->primaryVariant()->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('Use a saved address')
            ->assertSee('Ada Obi')
            ->assertSee('12 Marina Road');
    }

    public function test_guest_checkout_has_no_saved_address_picker(): void
    {
        $product = Product::factory()->stock(10)->create();
        $this->post(route('cart.store'), [
            'product_variant_id' => $product->primaryVariant()->id,
            'quantity' => 1,
        ]);

        $this->get(route('checkout.show'))
            ->assertOk()
            ->assertDontSee('Use a saved address');
    }
}
