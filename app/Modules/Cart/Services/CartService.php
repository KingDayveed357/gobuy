<?php

namespace App\Modules\Cart\Services;

use App\Models\User;
use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Models\CartItem;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Pricing\Services\PriceResolver;
use App\Modules\Pricing\ValueObjects\ResolvedPrice;
use App\Support\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

/**
 * Owns the cart lifecycle for guests (session-token cart) and authenticated
 * users (user-bound cart). Cart lines reference a product VARIANT.
 */
class CartService
{
    private const SESSION_KEY = 'cart_token';

    /** Eager-load path for a fully-priceable cart. */
    private const WITH = 'items.variant.product.quantityDiscounts';

    public function __construct(
        private readonly PriceResolver $prices,
        private readonly InventoryService $inventory,
    ) {}

    private function holderKey(Cart $cart): string
    {
        return "cart:{$cart->id}";
    }

    public function find(): ?Cart
    {
        if (Auth::check()) {
            return Cart::with(self::WITH)->firstWhere('user_id', Auth::id());
        }

        $token = Session::get(self::SESSION_KEY);

        return $token ? Cart::with(self::WITH)->firstWhere('session_token', $token) : null;
    }

    public function getOrCreate(): Cart
    {
        if (Auth::check()) {
            return Cart::firstOrCreate(['user_id' => Auth::id()]);
        }

        $token = Session::get(self::SESSION_KEY);

        if (! $token) {
            $token = (string) Str::uuid();
            Session::put(self::SESSION_KEY, $token);
        }

        return Cart::firstOrCreate(['session_token' => $token]);
    }

    public function add(ProductVariant $variant, int $quantity = 1): CartItem
    {
        $cart = $this->getOrCreate();
        $item = $cart->items()->firstOrNew(['product_variant_id' => $variant->id]);

        $desired = ($item->quantity ?? 0) + max(1, $quantity);
        $held = $this->inventory->reserve($variant, $desired, $this->holderKey($cart));

        if ($held < 1) {
            // Nothing available to hold — leave the cart unchanged.
            if ($item->exists) {
                $this->remove($item);
            }

            return $item;
        }

        $item->quantity = $held;
        $item->save();

        return $item;
    }

    public function updateQuantity(CartItem $item, int $quantity): void
    {
        if ($quantity < 1 || ! $item->variant) {
            $this->remove($item);

            return;
        }

        $held = $this->inventory->reserve($item->variant, $quantity, "cart:{$item->cart_id}");

        if ($held < 1) {
            $this->remove($item);

            return;
        }

        $item->quantity = $held;
        $item->save();
    }

    public function remove(CartItem $item): void
    {
        if ($item->variant) {
            $this->inventory->releaseVariant($item->variant, "cart:{$item->cart_id}");
        }

        $item->delete();
    }

    public function clear(): void
    {
        $cart = $this->find();

        if (! $cart) {
            return;
        }

        $this->inventory->release($this->holderKey($cart));
        $cart->items()->delete();
    }

    public function count(): int
    {
        return (int) ($this->find()?->items->sum('quantity') ?? 0);
    }

    /**
     * Priced cart summary. Each line is priced through the PricingEngine so
     * wholesale/sale/tier logic applies per line based on quantity.
     *
     * @return array{lines: array<int, array{item: CartItem, price: ResolvedPrice, lineTotal: Money}>, subtotal: Money, count: int}
     */
    public function summary(): array
    {
        $cart = $this->find();
        $user = Auth::user();
        $lines = [];
        $subtotal = Money::zero();
        $weight = 0;

        foreach ($cart?->items ?? [] as $item) {
            if (! $item->variant) {
                continue;
            }

            $price = $this->prices->forVariant($item->variant, $user, $item->quantity);
            $lineTotal = $price->lineTotal($item->quantity);
            $subtotal = $subtotal->plus($lineTotal);
            $weight += (int) ($item->variant->product->weight_g ?? 0) * $item->quantity;

            $lines[] = ['item' => $item, 'price' => $price, 'lineTotal' => $lineTotal];
        }

        return [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'weight' => $weight,
            'count' => (int) ($cart?->items->sum('quantity') ?? 0),
        ];
    }

    /**
     * Merge a guest's session cart into the user's on login (quantities summed,
     * capped at stock).
     */
    public function mergeGuestCartIntoUser(User $user): void
    {
        $token = Session::get(self::SESSION_KEY);

        if (! $token) {
            return;
        }

        $guestCart = Cart::with('items.variant')->firstWhere('session_token', $token);

        if (! $guestCart || $guestCart->items->isEmpty()) {
            $guestCart?->delete();
            Session::forget(self::SESSION_KEY);

            return;
        }

        DB::transaction(function () use ($guestCart, $user): void {
            $userCart = Cart::firstOrCreate(['user_id' => $user->id]);

            // Drop the guest's holds first so they don't block the merged total.
            $this->inventory->release($this->holderKey($guestCart));

            foreach ($guestCart->items as $guestItem) {
                if (! $guestItem->variant) {
                    continue;
                }

                $userItem = $userCart->items()->firstOrNew(['product_variant_id' => $guestItem->product_variant_id]);
                $combined = ($userItem->quantity ?? 0) + $guestItem->quantity;
                $held = $this->inventory->reserve($guestItem->variant, $combined, $this->holderKey($userCart));

                if ($held < 1) {
                    continue;
                }

                $userItem->quantity = $held;
                $userItem->save();
            }

            $guestCart->delete();
        });

        Session::forget(self::SESSION_KEY);

        Log::info('Guest cart merged into user cart', ['user_id' => $user->id]);
    }
}
