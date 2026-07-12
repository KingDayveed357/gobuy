<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Services\InventoryLedger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * On-hand quantity of one variant at one location — the per-location breakdown
 * that sums to the variant's Core `stock`. Maintained only by
 * {@see InventoryLedger}.
 */
class StockLevel extends Model
{
    protected $fillable = ['product_variant_id', 'inventory_location_id', 'on_hand'];

    protected function casts(): array
    {
        return ['on_hand' => 'integer'];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'inventory_location_id');
    }
}
