<?php

namespace App\Livewire\Cart;

use App\Modules\Cart\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Live cart-count badge in the nav. Refreshes whenever any component dispatches
 * `cart-updated`, so adding to cart anywhere updates the badge with no reload.
 */
class CartCount extends Component
{
    public int $count = 0;

    public function mount(CartService $cart): void
    {
        $this->count = $cart->count();
    }

    #[On('cart-updated')]
    public function refresh(CartService $cart): void
    {
        $this->count = $cart->count();
    }

    public function render()
    {
        return view('livewire.cart.cart-count');
    }
}
