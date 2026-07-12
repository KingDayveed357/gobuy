<?php

namespace App\Modules\Inventory\Services;

use App\Admin\Models\Admin;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Services\BackInStockService;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Exceptions\InsufficientStock;
use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Inventory\Models\StockLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * The single writer of stock. Every change becomes an append-only
 * {@see InventoryMovement} against a location; the per-location {@see StockLevel}
 * and the Core `product_variants.stock` read-model are updated by the SAME signed
 * delta — never an absolute recomputation — so an out-of-band stock edit is never
 * clobbered, only surfaced by `inventory:reconcile`.
 *
 * Records silently against the seeded Default location even when the multi-
 * location module is off, so the audit trail accrues from day one.
 */
class InventoryLedger
{
    public function __construct(private readonly BackInStockService $backInStock) {}

    /** A sale removes stock; throws if it would oversell. */
    public function recordSale(ProductVariant $variant, int $quantity, ?Model $reference = null): InventoryMovement
    {
        return $this->record($variant, -abs($quantity), MovementType::Sale, ['reference' => $reference]);
    }

    /** A return/refund puts stock back. */
    public function recordReturn(ProductVariant $variant, int $quantity, ?Model $reference = null): InventoryMovement
    {
        return $this->record($variant, abs($quantity), MovementType::Return, ['reference' => $reference]);
    }

    /** A manual, audited adjustment (clamped at zero, like the old adjust path). */
    public function recordAdjustment(ProductVariant $variant, int $delta, ?string $reason = null, ?Admin $admin = null): InventoryMovement
    {
        return $this->record($variant, $delta, MovementType::Adjustment, ['clamp' => true, 'note' => $reason, 'admin' => $admin]);
    }

    /** Goods received against a purchase order — adds stock at a location. */
    public function recordReceipt(ProductVariant $variant, int $quantity, InventoryLocation $location, ?Model $reference = null, ?Admin $admin = null): InventoryMovement
    {
        return $this->record($variant, abs($quantity), MovementType::Receipt, ['location' => $location, 'reference' => $reference, 'admin' => $admin]);
    }

    /** Damaged / written-off stock — removes units at a location (clamped at zero). */
    public function recordDamage(ProductVariant $variant, int $quantity, InventoryLocation $location, ?string $reason = null, ?Admin $admin = null): InventoryMovement
    {
        return $this->record($variant, -abs($quantity), MovementType::Damage, ['location' => $location, 'clamp' => true, 'note' => $reason, 'admin' => $admin]);
    }

    /**
     * Reconcile a location's stock to a physically counted quantity, recording the
     * signed difference as a `count` movement. Returns null when already in sync.
     */
    public function recordCount(ProductVariant $variant, int $countedQuantity, InventoryLocation $location, ?Admin $admin = null, ?Model $reference = null): ?InventoryMovement
    {
        return DB::transaction(function () use ($variant, $countedQuantity, $location, $admin, $reference): ?InventoryMovement {
            $fresh = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
            $delta = max(0, $countedQuantity) - $this->levelFor($fresh, $location)->on_hand;

            if ($delta === 0) {
                return null;
            }

            return $this->record($fresh, $delta, MovementType::Count, ['location' => $location, 'admin' => $admin, 'reference' => $reference, 'note' => 'Stock count']);
        });
    }

    /**
     * Read a variant's on-hand at a location WITHOUT touching it — mirrors the
     * lazy-init assumption (the Default location holds the read-model, others start
     * empty) so callers can snapshot the expected quantity before a count.
     */
    public function onHandAt(ProductVariant $variant, InventoryLocation $location): int
    {
        $level = StockLevel::query()
            ->where('product_variant_id', $variant->id)
            ->where('inventory_location_id', $location->id)
            ->first();

        if ($level) {
            return (int) $level->on_hand;
        }

        return $location->is_default ? max(0, (int) $variant->stock) : 0;
    }

    /**
     * Move stock between two locations as one atomic pair of movements. The total
     * on-hand is unchanged, so it does NOT fire back-in-stock (notify:false).
     * Throws if the source location doesn't hold enough.
     *
     * @return array{out: InventoryMovement, in: InventoryMovement}
     */
    public function transfer(ProductVariant $variant, int $quantity, InventoryLocation $from, InventoryLocation $to, array $options = []): array
    {
        if ($from->is($to)) {
            throw new \InvalidArgumentException('A transfer needs two different locations.');
        }

        $quantity = abs($quantity);
        $shared = ['reference' => $options['reference'] ?? null, 'admin' => $options['admin'] ?? null, 'notify' => false];

        return DB::transaction(fn (): array => [
            'out' => $this->record($variant, -$quantity, MovementType::TransferOut, $shared + ['location' => $from]),
            'in' => $this->record($variant, $quantity, MovementType::TransferIn, $shared + ['location' => $to]),
        ]);
    }

    /**
     * Apply a signed stock change at a location and log it. The heart of the ledger.
     *
     * @param  array{location?: InventoryLocation, reference?: ?Model, admin?: ?Admin, note?: ?string, clamp?: bool}  $options
     */
    public function record(ProductVariant $variant, int $delta, MovementType $type, array $options = []): InventoryMovement
    {
        return DB::transaction(function () use ($variant, $delta, $type, $options): InventoryMovement {
            // Locking the variant serialises every concurrent movement for it,
            // preserving the oversell guard exactly as the old decrement did.
            $fresh = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
            $location = $options['location'] ?? InventoryLocation::default();

            $level = $this->levelFor($fresh, $location);

            $before = $level->on_hand;
            $target = $before + $delta;

            if ($target < 0) {
                if (! ($options['clamp'] ?? false)) {
                    throw new InsufficientStock("Insufficient stock for variant {$fresh->sku}.");
                }
                $target = 0;
            }

            $applied = $target - $before;
            $level->update(['on_hand' => $target]);

            $movement = $this->log($fresh, $location, $type, $applied, $target, $options);

            // Maintain the read-model by the same delta (see class docblock).
            $variantBefore = (int) $fresh->stock;
            ProductVariant::query()->whereKey($fresh->id)->update(['stock' => max(0, $variantBefore + $applied)]);

            // The ledger writes stock via the query builder (bypassing the model
            // observer), so it owns the back-in-stock flush on a 0 → positive cross.
            // Transfers pass notify:false — the total didn't change, so it's not
            // genuinely "back in stock".
            if (($options['notify'] ?? true) && $variantBefore <= 0 && ($variantBefore + $applied) > 0) {
                $this->backInStock->flush($fresh->refresh());
            }

            return $movement;
        });
    }

    /** Total on-hand for a variant across every location (== the read-model when consistent). */
    public function totalOnHand(ProductVariant $variant): int
    {
        return (int) StockLevel::query()->where('product_variant_id', $variant->id)->sum('on_hand');
    }

    /**
     * Heal drift between the read-model and the ledger by moving the Default
     * level onto `variants.stock` (the source of truth for out-of-band edits like
     * the product form) and recording it — WITHOUT re-deltaing the read-model.
     * Returns the reconciliation movement, or null when already in sync.
     */
    public function reconcile(ProductVariant $variant): ?InventoryMovement
    {
        return DB::transaction(function () use ($variant): ?InventoryMovement {
            $fresh = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
            $location = InventoryLocation::default();
            $level = $this->levelFor($fresh, $location); // lazy-inits a missing level from stock

            $drift = (int) $fresh->stock - $this->totalOnHand($fresh);
            if ($drift === 0) {
                return null;
            }

            $newOnHand = max(0, $level->on_hand + $drift);
            $level->update(['on_hand' => $newOnHand]);

            return $this->log($fresh, $location, MovementType::Adjustment, $drift, $newOnHand, ['note' => 'Reconciliation']);
        });
    }

    /**
     * The variant's stock level at a location, lazily seeding it the first time
     * from the current read-model (default location adopts the variant's stock as
     * an opening balance; others start empty) so pre-existing and factory-made
     * variants need no explicit backfill.
     */
    private function levelFor(ProductVariant $variant, InventoryLocation $location): StockLevel
    {
        $level = StockLevel::query()
            ->where('product_variant_id', $variant->id)
            ->where('inventory_location_id', $location->id)
            ->lockForUpdate()
            ->first();

        if ($level) {
            return $level;
        }

        $opening = $location->is_default ? max(0, (int) $variant->stock) : 0;
        $level = StockLevel::create([
            'product_variant_id' => $variant->id,
            'inventory_location_id' => $location->id,
            'on_hand' => $opening,
        ]);

        if ($opening !== 0) {
            $this->log($variant, $location, MovementType::Opening, $opening, $opening, ['note' => 'Opening balance']);
        }

        return $level;
    }

    /**
     * @param  array{reference?: ?Model, admin?: ?Admin, note?: ?string}  $options
     */
    private function log(ProductVariant $variant, InventoryLocation $location, MovementType $type, int $quantity, int $quantityAfter, array $options): InventoryMovement
    {
        $reference = $options['reference'] ?? null;

        return InventoryMovement::create([
            'product_variant_id' => $variant->id,
            'inventory_location_id' => $location->id,
            'type' => $type->value,
            'quantity' => $quantity,
            'quantity_after' => $quantityAfter,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'admin_id' => ($options['admin'] ?? null)?->id,
            'note' => $options['note'] ?? null,
        ]);
    }
}
