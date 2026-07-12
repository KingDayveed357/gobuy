<?php

namespace App\Modules\Operations\Transfers\Services;

use App\Admin\Models\Admin;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Inventory\Services\InventoryLedger;
use App\Modules\Operations\Transfers\Models\StockTransfer;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Records a stock transfer between two locations: a header + item lines, each
 * line moving stock through the ledger as a transfer_out / transfer_in pair. If
 * any line lacks stock at the source, the whole transfer rolls back.
 */
class TransferService
{
    public function __construct(private readonly InventoryLedger $ledger) {}

    /**
     * @param  list<array{variant_id: int, quantity: int}>  $lines
     */
    public function transfer(InventoryLocation $from, InventoryLocation $to, array $lines, ?string $note = null, ?Admin $admin = null): StockTransfer
    {
        if ($from->is($to)) {
            throw new InvalidArgumentException('Choose two different locations.');
        }

        $lines = array_values(array_filter($lines, fn ($line) => (int) ($line['quantity'] ?? 0) > 0));
        if ($lines === []) {
            throw new InvalidArgumentException('Add at least one item to transfer.');
        }

        return DB::transaction(function () use ($from, $to, $lines, $note, $admin): StockTransfer {
            $transfer = StockTransfer::create([
                'from_location_id' => $from->id,
                'to_location_id' => $to->id,
                'created_by_id' => $admin?->id,
                'note' => $note,
            ]);

            foreach ($lines as $line) {
                $variant = ProductVariant::findOrFail($line['variant_id']);
                $quantity = (int) $line['quantity'];

                $transfer->items()->create(['product_variant_id' => $variant->id, 'quantity' => $quantity]);
                $this->ledger->transfer($variant, $quantity, $from, $to, ['reference' => $transfer, 'admin' => $admin]);
            }

            return $transfer->load('items');
        });
    }
}
