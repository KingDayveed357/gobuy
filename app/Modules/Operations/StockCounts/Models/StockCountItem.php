<?php

namespace App\Modules\Operations\StockCounts\Models;

use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of a stock count: the expected (system) quantity captured at count
 * time and the counted (physical) quantity. Their difference was posted to the
 * ledger as a `count` movement.
 */
class StockCountItem extends Model
{
    protected $fillable = ['stock_count_id', 'product_variant_id', 'expected_qty', 'counted_qty'];

    protected function casts(): array
    {
        return [
            'expected_qty' => 'integer',
            'counted_qty' => 'integer',
        ];
    }

    public function stockCount(): BelongsTo
    {
        return $this->belongsTo(StockCount::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /** Counted minus expected: positive = found more, negative = shrinkage. */
    public function variance(): int
    {
        return $this->counted_qty - $this->expected_qty;
    }
}
