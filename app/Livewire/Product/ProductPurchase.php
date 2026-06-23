<?php

namespace App\Livewire\Product;

use App\Modules\Cart\Services\CartService;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * PDP add-to-cart. Deliberately tiny: the variant/quantity UI stays client-side
 * (Alpine + the page's existing zero-network variant map), and Livewire only
 * fires the single server action that mutates the cart through the shared
 * {@see CartService}. One component + one round-trip per add — VPS-friendly.
 */
class ProductPurchase extends Component
{
    #[Locked]
    public int $productId;

    #[Locked]
    public ?int $selectedId = null;

    #[Locked]
    public int $cartQty = 0;

    #[Locked]
    public int $stock = 0;

    public function mount(Product $product, ?int $selectedId = null, int $cartQty = 0, int $stock = 0): void
    {
        $this->productId = $product->id;
        $this->selectedId = $selectedId;
        $this->cartQty = $cartQty;
        $this->stock = $stock;
    }

    /**
     * Set the cart line for the chosen variant to an absolute quantity (matches
     * the PDP "Add / Update cart" semantics of the legacy set-quantity route).
     */
    public function add(int $variantId, int $quantity, CartService $cart): void
    {
        $variant = ProductVariant::with('product')->find($variantId);

        if (! $variant || $variant->product_id !== $this->productId || $variant->product?->status !== 'active') {
            $this->dispatch('toast', type: 'error', message: 'This item is no longer available.');

            return;
        }

        if ($variant->stock < 1) {
            $this->dispatch('toast', type: 'error', message: "{$variant->product->name} is out of stock.");

            return;
        }

        $quantity = max(1, min($quantity, $variant->stock));
        $existing = $cart->getOrCreate()->items()->firstWhere('product_variant_id', $variant->id);

        if ($existing) {
            $cart->updateQuantity($existing, $quantity);
        } else {
            $cart->add($variant, $quantity);
        }

        $this->dispatch('cart-updated');
        $this->dispatch('toast', type: 'success', message: "{$variant->product->name} added to cart.");
    }

    public function render()
    {
        return view('livewire.product.product-purchase');
    }
}
