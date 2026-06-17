<?php

namespace App\Modules\Cart\Services;

use App\Models\User;
use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Models\CartItem;
use App\Modules\Catalog\Models\Product;
use App\Modules\Pricing\Services\PriceResolver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

/**
 * Owns the cart lifecycle for both guests (session-token cart) and
 * authenticated users (user-bound cart), and the merge between them.
 */
class CartService
{
    private const SESSION_KEY = 'cart_token';

    public function __construct(private readonly PriceResolver $priceResolver) {}

    /**
     * The current cart without creating one. Null when nothing exists yet.
     */
    public function find(): ?Cart
    {
        if (Auth::check()) {
            return Cart::with('items.product.images')->firstWhere('user_id', Auth::id());
        }

        $token = Session::get(self::SESSION_KEY);

        return $token ? Cart::with('items.product.images')->firstWhere('session_token', $token) : null;
    }

    /**
     * The current cart, creating an empty one if needed.
     */
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

    public function add(Product $product, int $quantity = 1): CartItem
    {
        $cart = $this->getOrCreate();

        $item = $cart->items()->firstOrNew(['product_id' => $product->id]);
        $item->quantity = $this->clampToStock($product, $item->quantity + $quantity);
        $item->save();

        return $item;
    }

    public function updateQuantity(CartItem $item, int $quantity): void
    {
        if ($quantity < 1) {
            $this->remove($item);

            return;
        }

        $item->quantity = $this->clampToStock($item->product, $quantity);
        $item->save();
    }

    public function remove(CartItem $item): void
    {
        $item->delete();
    }

    public function clear(): void
    {
        $this->find()?->items()->delete();
    }

    /**
     * Total quantity of items — used for the navbar badge.
     */
    public function count(): int
    {
        return (int) ($this->find()?->items->sum('quantity') ?? 0);
    }

    /**
     * Priced cart summary. Every line is priced through PriceResolver so
     * wholesale thresholds apply per-line based on quantity.
     *
     * @return array{lines: array<int, array{item: CartItem, price: \App\Modules\Pricing\ValueObjects\ResolvedPrice, lineTotal: float}>, subtotal: float, count: int}
     */
    public function summary(): array
    {
        $cart = $this->find();
        $user = Auth::user();
        $lines = [];
        $subtotal = 0.0;

        foreach ($cart?->items ?? [] as $item) {
            $price = $this->priceResolver->for($item->product, $user, $item->quantity);
            $lineTotal = $price->lineTotal($item->quantity);
            $subtotal += $lineTotal;

            $lines[] = ['item' => $item, 'price' => $price, 'lineTotal' => $lineTotal];
        }

        return [
            'lines' => $lines,
            'subtotal' => round($subtotal, 2),
            'count' => (int) ($cart?->items->sum('quantity') ?? 0),
        ];
    }

    /**
     * Merge a guest's session cart into the user's cart on login.
     * Duplicate products are combined (quantities summed, capped at stock).
     */
    public function mergeGuestCartIntoUser(User $user): void
    {
        $token = Session::get(self::SESSION_KEY);

        if (! $token) {
            return;
        }

        $guestCart = Cart::with('items.product')->firstWhere('session_token', $token);

        if (! $guestCart || $guestCart->items->isEmpty()) {
            $guestCart?->delete();
            Session::forget(self::SESSION_KEY);

            return;
        }

        DB::transaction(function () use ($guestCart, $user): void {
            $userCart = Cart::firstOrCreate(['user_id' => $user->id]);

            foreach ($guestCart->items as $guestItem) {
                $userItem = $userCart->items()->firstOrNew(['product_id' => $guestItem->product_id]);
                $combined = ($userItem->quantity ?? 0) + $guestItem->quantity;
                $userItem->quantity = $this->clampToStock($guestItem->product, $combined);
                $userItem->save();
            }

            $guestCart->delete();
        });

        Session::forget(self::SESSION_KEY);

        Log::info('Guest cart merged into user cart', ['user_id' => $user->id]);
    }

    private function clampToStock(Product $product, int $quantity): int
    {
        $max = max($product->stock, 1);

        return (int) min(max($quantity, 1), $max);
    }
}
