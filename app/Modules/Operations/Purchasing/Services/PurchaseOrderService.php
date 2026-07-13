<?php

namespace App\Modules\Operations\Purchasing\Services;

use App\Admin\Models\Admin;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Inventory\Services\InventoryLedger;
use App\Modules\Operations\Purchasing\Enums\PurchaseOrderStatus;
use App\Modules\Operations\Purchasing\Exceptions\PurchasingException;
use App\Modules\Operations\Purchasing\Models\PurchaseOrder;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

/**
 * Raises purchase orders and receives goods into stock. Receiving is the only
 * thing that touches inventory: each received line records a `receipt` movement
 * through the {@see InventoryLedger} at the PO's destination location, so bought
 * stock enters the same audited ledger as every other movement.
 */
class PurchaseOrderService
{
    public function __construct(private readonly InventoryLedger $ledger) {}

    /**
     * Raise a purchase order (as a draft, unless $place opens it straight away).
     *
     * @param  list<array{variant_id: int, quantity: int, unit_cost?: int}>  $lines  unit_cost in kobo
     */
    public function create(?InventoryLocation $location, array $lines, array $meta = []): PurchaseOrder
    {
        $location ??= InventoryLocation::default();

        $lines = array_values(array_filter($lines, fn ($line): bool => (int) ($line['quantity'] ?? 0) > 0));
        if ($lines === []) {
            throw new PurchasingException('Add at least one item to the purchase order.');
        }

        $place = (bool) ($meta['place'] ?? false);

        return DB::transaction(function () use ($location, $lines, $meta, $place): PurchaseOrder {
            /** @var Admin|null $admin */
            $admin = $meta['admin'] ?? null;

            $po = PurchaseOrder::create([
                'reference' => 'PENDING',
                'supplier_id' => $meta['supplier_id'] ?? null,
                'inventory_location_id' => $location->id,
                'created_by_id' => $admin?->id,
                'status' => $place ? PurchaseOrderStatus::Ordered : PurchaseOrderStatus::Draft,
                'note' => $meta['note'] ?? null,
                'ordered_at' => $place ? now() : null,
            ]);
            $po->update(['reference' => 'PO-'.str_pad((string) $po->id, 5, '0', STR_PAD_LEFT)]);

            foreach ($lines as $line) {
                $variant = ProductVariant::findOrFail($line['variant_id']);
                $po->items()->create([
                    'product_variant_id' => $variant->id,
                    'quantity_ordered' => (int) $line['quantity'],
                    'quantity_received' => 0,
                    'unit_cost' => Money::fromKobo((int) ($line['unit_cost'] ?? 0)),
                ]);
            }

            return $po->load('items');
        });
    }
    /**
     * Update a draft purchase order.
     *
     * @param  list<array{variant_id: int, quantity: int, unit_cost?: int}>  $lines  unit_cost in kobo
     */
    public function update(PurchaseOrder $po, ?InventoryLocation $location, array $lines, array $meta = []): PurchaseOrder
    {
        if ($po->status !== PurchaseOrderStatus::Draft) {
            throw new PurchasingException('Only a draft purchase order can be edited.');
        }

        $location ??= InventoryLocation::default();

        $lines = array_values(array_filter($lines, fn ($line): bool => (int) ($line['quantity'] ?? 0) > 0));
        if ($lines === []) {
            throw new PurchasingException('Add at least one item to the purchase order.');
        }

        $place = (bool) ($meta['place'] ?? false);

        return DB::transaction(function () use ($po, $location, $lines, $meta, $place): PurchaseOrder {
            $po->update([
                'supplier_id' => $meta['supplier_id'] ?? null,
                'inventory_location_id' => $location->id,
                'note' => $meta['note'] ?? null,
                'status' => $place ? PurchaseOrderStatus::Ordered : PurchaseOrderStatus::Draft,
                'ordered_at' => $place ? now() : null,
            ]);

            // Replace all items
            $po->items()->delete();

            foreach ($lines as $line) {
                $variant = ProductVariant::findOrFail($line['variant_id']);
                $po->items()->create([
                    'product_variant_id' => $variant->id,
                    'quantity_ordered' => (int) $line['quantity'],
                    'quantity_received' => 0,
                    'unit_cost' => Money::fromKobo((int) ($line['unit_cost'] ?? 0)),
                ]);
            }

            return $po->load('items');
        });
    }


    /** Place a draft order with the supplier (draft → ordered). */
    public function place(PurchaseOrder $po): PurchaseOrder
    {
        if ($po->status !== PurchaseOrderStatus::Draft) {
            throw new PurchasingException('Only a draft purchase order can be placed.');
        }

        $po->update(['status' => PurchaseOrderStatus::Ordered, 'ordered_at' => now()]);

        return $po;
    }

    /**
     * Receive goods against a placed order. Each entry adds stock at the PO's
     * location through the ledger and advances the line's received quantity; the
     * PO becomes Received once every line is complete, else PartiallyReceived.
     * Receiving more than a line's outstanding quantity is refused.
     *
     * @param  array<int, int>  $received  purchase_order_item id => quantity received now
     */
    public function receive(PurchaseOrder $po, array $received, ?Admin $admin = null): PurchaseOrder
    {
        if (! $po->status->canReceive()) {
            throw new PurchasingException('This purchase order cannot receive goods in its current state.');
        }

        $received = array_filter($received, fn ($qty): bool => (int) $qty > 0);
        if ($received === []) {
            throw new PurchasingException('Enter at least one quantity to receive.');
        }

        return DB::transaction(function () use ($po, $received, $admin): PurchaseOrder {
            $po->load('items');
            $location = $po->location;

            foreach ($received as $itemId => $qty) {
                $item = $po->items->firstWhere('id', (int) $itemId);
                if (! $item) {
                    continue;
                }

                $qty = (int) $qty;
                if ($qty > $item->outstanding()) {
                    throw new PurchasingException("Cannot receive more than the {$item->outstanding()} outstanding for {$item->variant->sku}.");
                }

                $this->ledger->recordReceipt($item->variant, $qty, $location, $po, $admin);
                $item->update(['quantity_received' => $item->quantity_received + $qty]);
            }

            $po->load('items');
            $fully = $po->isFullyReceived();
            $po->update([
                'status' => $fully ? PurchaseOrderStatus::Received : PurchaseOrderStatus::PartiallyReceived,
                'received_at' => $fully ? now() : $po->received_at,
            ]);

            return $po;
        });
    }

    /** Cancel a PO before it is (even partly) received. */
    public function cancel(PurchaseOrder $po): PurchaseOrder
    {
        if (in_array($po->status, [PurchaseOrderStatus::Received, PurchaseOrderStatus::PartiallyReceived], true)) {
            throw new PurchasingException('A purchase order that has received goods cannot be cancelled.');
        }

        $po->update(['status' => PurchaseOrderStatus::Cancelled]);

        return $po;
    }
}
