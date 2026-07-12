<?php

namespace App\Modules\Inventory\Console;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Services\InventoryLedger;
use Illuminate\Console\Command;

/**
 * The drift guard for the inventory ledger. Reports any variant whose Core
 * `stock` read-model disagrees with the ledger (Σ stock levels) — the classic
 * failure mode being out-of-band stock edits (the product form) that bypass the
 * ledger. `--fix` records reconciliation movements so the ledger catches up.
 */
class ReconcileInventory extends Command
{
    protected $signature = 'inventory:reconcile {--fix : Record reconciliation movements to heal any drift}';

    protected $description = 'Report (and optionally heal) drift between product stock and the inventory ledger.';

    public function handle(InventoryLedger $ledger): int
    {
        $fix = (bool) $this->option('fix');
        $drifted = 0;

        ProductVariant::query()->orderBy('id')->chunk(500, function ($variants) use ($ledger, $fix, &$drifted): void {
            foreach ($variants as $variant) {
                $ledgerTotal = $ledger->totalOnHand($variant);
                $drift = (int) $variant->stock - $ledgerTotal;

                if ($drift === 0) {
                    continue;
                }

                $drifted++;
                $this->warn(sprintf('%s: stock=%d, ledger=%d, drift=%+d', $variant->sku, $variant->stock, $ledgerTotal, $drift));

                if ($fix) {
                    $ledger->reconcile($variant);
                }
            }
        });

        if ($drifted === 0) {
            $this->info('Inventory is in sync — no drift.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info($fix
            ? "Healed {$drifted} variant(s) — reconciliation movements recorded."
            : "{$drifted} variant(s) drifted. Re-run with --fix to heal.");

        return self::SUCCESS;
    }
}
