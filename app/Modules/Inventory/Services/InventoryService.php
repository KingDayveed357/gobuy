<?php

namespace App\Modules\Inventory\Services;

use App\Admin\Models\Admin;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Inventory\Models\StockReservation;
use Illuminate\Support\Facades\DB;

/**
 * Owns availability and soft reservations. Manual stock changes are delegated to
 * the {@see InventoryLedger} so they join the one audit trail. "Available" stock
 * is on-hand minus active (unexpired) holds, so concurrent shoppers cannot
 * oversell before payment.
 */
class InventoryService
{
    public function __construct(private readonly InventoryLedger $ledger) {}

    /** Total quantity actively reserved for a variant, optionally excluding one holder. */
    public function reservedQuantity(ProductVariant $variant, ?string $excludeHolder = null): int
    {
        return (int) StockReservation::query()
            ->where('product_variant_id', $variant->id)
            ->active()
            ->when($excludeHolder, fn ($q) => $q->where('holder_key', '!=', $excludeHolder))
            ->sum('quantity');
    }

    /** On-hand stock minus active holds (excluding the given holder's own hold). */
    public function availableStock(ProductVariant $variant, ?string $excludeHolder = null): int
    {
        return max(0, $variant->stock - $this->reservedQuantity($variant, $excludeHolder));
    }

    /**
     * Place/refresh a hold for $holderKey, capped at what is available to them.
     * Returns the quantity actually held (may be lower than requested).
     */
    public function reserve(ProductVariant $variant, int $quantity, string $holderKey): int
    {
        return DB::transaction(function () use ($variant, $quantity, $holderKey): int {
            $fresh = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
            $cap = $this->availableStock($fresh, excludeHolder: $holderKey);
            $held = max(0, min($quantity, $cap));

            if ($held === 0) {
                StockReservation::where('product_variant_id', $fresh->id)->where('holder_key', $holderKey)->delete();

                return 0;
            }

            StockReservation::updateOrCreate(
                ['product_variant_id' => $fresh->id, 'holder_key' => $holderKey],
                ['quantity' => $held, 'expires_at' => now()->addMinutes($this->ttlMinutes())],
            );

            return $held;
        });
    }

    /** Release every hold owned by a holder (e.g. on cart clear or order placement). */
    public function release(string $holderKey): void
    {
        StockReservation::where('holder_key', $holderKey)->delete();
    }

    /** Release a holder's hold on a single variant (e.g. on cart line removal). */
    public function releaseVariant(ProductVariant $variant, string $holderKey): void
    {
        StockReservation::where('product_variant_id', $variant->id)->where('holder_key', $holderKey)->delete();
    }

    /** Drop all expired holds. Returns the number released. */
    public function releaseExpired(): int
    {
        return StockReservation::where('expires_at', '<=', now())->delete();
    }

    /**
     * Apply a signed manual stock change and record it. Stock never goes below
     * zero; the logged delta reflects the actual change applied.
     */
    public function adjust(ProductVariant $variant, int $delta, ?string $reason = null, ?Admin $admin = null): InventoryMovement
    {
        return $this->ledger->recordAdjustment($variant, $delta, $reason, $admin);
    }

    /** Set on-hand stock to an absolute target, recording the difference. */
    public function setStock(ProductVariant $variant, int $target, ?string $reason = null, ?Admin $admin = null): InventoryMovement
    {
        return $this->adjust($variant, $target - $variant->stock, $reason, $admin);
    }

    private function ttlMinutes(): int
    {
        return (int) config('gobuy.reservation_ttl_minutes', 30);
    }
}
