<?php

namespace App\Modules\Operations\Dashboards\Services;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Order\Enums\SalesChannel;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-only operational reporting over the inventory ledger and orders — the
 * ops.dashboards module. Pure aggregation: it writes nothing and depends only on
 * Core tables plus the ledger every operations module already feeds.
 */
class OperationsReport
{
    /**
     * On-hand units, distinct SKUs and stock value at each location.
     *
     * @return Collection<int, array{name: string, units: int, skus: int, value: Money}>
     */
    public function inventoryByLocation(): Collection
    {
        return DB::table('stock_levels as sl')
            ->join('product_variants as pv', 'pv.id', '=', 'sl.product_variant_id')
            ->join('inventory_locations as il', 'il.id', '=', 'sl.inventory_location_id')
            ->groupBy('il.id', 'il.name')
            ->orderByDesc('units')
            ->selectRaw('il.name as name, SUM(sl.on_hand) as units, SUM(CASE WHEN sl.on_hand > 0 THEN 1 ELSE 0 END) as skus, SUM(sl.on_hand * pv.retail_price) as value_kobo')
            ->get()
            ->map(fn ($row): array => [
                'name' => $row->name,
                'units' => (int) $row->units,
                'skus' => (int) $row->skus,
                'value' => Money::fromKobo((int) $row->value_kobo),
            ]);
    }

    /**
     * Platform-wide inventory totals.
     *
     * @return array{units: int, skus: int, value: Money}
     */
    public function inventoryTotals(): array
    {
        $row = DB::table('stock_levels as sl')
            ->join('product_variants as pv', 'pv.id', '=', 'sl.product_variant_id')
            ->selectRaw('SUM(sl.on_hand) as units, SUM(CASE WHEN sl.on_hand > 0 THEN 1 ELSE 0 END) as skus, SUM(sl.on_hand * pv.retail_price) as value_kobo')
            ->first();

        return [
            'units' => (int) ($row->units ?? 0),
            'skus' => (int) ($row->skus ?? 0),
            'value' => Money::fromKobo((int) ($row->value_kobo ?? 0)),
        ];
    }

    /**
     * Paid revenue and order count per sales channel over the last $days.
     *
     * @return Collection<int, array{channel: string, label: string, orders: int, revenue: Money}>
     */
    public function salesByChannel(int $days = 30): Collection
    {
        return DB::table('orders')
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('channel')
            ->orderByDesc('revenue_kobo')
            ->selectRaw('channel, COUNT(*) as orders, SUM(total) as revenue_kobo')
            ->get()
            ->map(fn ($row): array => [
                'channel' => (string) $row->channel,
                'label' => SalesChannel::labelFor($row->channel),
                'orders' => (int) $row->orders,
                'revenue' => Money::fromKobo((int) $row->revenue_kobo),
            ]);
    }

    /**
     * The best-selling variants (by units sold) over the last $days.
     *
     * @return Collection<int, array{product: string, sku: string, units: int}>
     */
    public function topMovers(int $days = 30, int $limit = 8): Collection
    {
        return DB::table('inventory_movements as m')
            ->join('product_variants as pv', 'pv.id', '=', 'm.product_variant_id')
            ->join('products as p', 'p.id', '=', 'pv.product_id')
            ->where('m.type', 'sale')
            ->where('m.created_at', '>=', now()->subDays($days))
            ->groupBy('pv.id', 'p.name', 'pv.sku')
            ->orderByDesc('units')
            ->limit($limit)
            ->selectRaw('p.name as product, pv.sku as sku, -SUM(m.quantity) as units')
            ->get()
            ->map(fn ($row): array => [
                'product' => $row->product,
                'sku' => $row->sku,
                'units' => (int) $row->units,
            ]);
    }

    /**
     * Variants at or below a low-stock threshold, most urgent first.
     *
     * @return Collection<int, ProductVariant>
     */
    public function lowStock(int $threshold = 5, int $limit = 10): Collection
    {
        return ProductVariant::query()
            ->with('product:id,name')
            ->where('stock', '<=', $threshold)
            ->orderBy('stock')
            ->limit($limit)
            ->get();
    }
}
