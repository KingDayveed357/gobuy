<?php

namespace App\Modules\Operations\StockCounts\Services;

use App\Admin\Models\Admin;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Inventory\Services\InventoryLedger;
use App\Modules\Operations\StockCounts\Models\StockCount;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Records physical stock counts and damage write-offs. Both are just movements
 * through the {@see InventoryLedger}: a count reconciles a location to its
 * counted quantity (a `count` movement for the difference); a write-off removes
 * damaged units (a `damage` movement). The ledger stays the single writer.
 */
class StockCountService
{
    public function __construct(private readonly InventoryLedger $ledger) {}

    /**
     * Record a physical count at a location. Each line snapshots the expected
     * on-hand and posts the difference to the ledger to bring the books in line.
     *
     * @param  array<int, int>  $counted  product_variant_id => counted quantity
     */
    public function record(InventoryLocation $location, array $counted, ?string $note = null, ?Admin $admin = null): StockCount
    {
        $counted = array_filter($counted, fn ($qty): bool => $qty !== '' && $qty !== null);
        if ($counted === []) {
            throw new InvalidArgumentException('Count at least one item.');
        }

        return DB::transaction(function () use ($location, $counted, $note, $admin): StockCount {
            $count = StockCount::create([
                'inventory_location_id' => $location->id,
                'created_by_id' => $admin?->id,
                'note' => $note,
                'counted_at' => now(),
            ]);

            foreach ($counted as $variantId => $countedQty) {
                $variant = ProductVariant::findOrFail((int) $variantId);
                $countedQty = max(0, (int) $countedQty);
                $expected = $this->ledger->onHandAt($variant, $location);

                $count->items()->create([
                    'product_variant_id' => $variant->id,
                    'expected_qty' => $expected,
                    'counted_qty' => $countedQty,
                ]);

                // Reconcile the location to the counted figure (no-op if unchanged).
                $this->ledger->recordCount($variant, $countedQty, $location, $admin, $count);
            }

            return $count->load('items');
        });
    }

    /** Write off damaged / lost units at a location (a `damage` ledger movement). */
    public function writeOffDamage(ProductVariant $variant, int $quantity, InventoryLocation $location, ?string $reason = null, ?Admin $admin = null): InventoryMovement
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Enter how many units to write off.');
        }

        return $this->ledger->recordDamage($variant, $quantity, $location, $reason, $admin);
    }
}
