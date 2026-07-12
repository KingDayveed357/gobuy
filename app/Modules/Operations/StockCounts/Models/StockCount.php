<?php

namespace App\Modules\Operations\StockCounts\Models;

use App\Admin\Models\Admin;
use App\Modules\Inventory\Models\InventoryLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A recorded physical stock count at a location. Its item lines each carry the
 * expected (system) quantity and the counted (physical) quantity; the difference
 * was posted to the ledger as a `count` movement when the count was recorded.
 */
class StockCount extends Model
{
    protected $fillable = ['inventory_location_id', 'created_by_id', 'note', 'counted_at'];

    protected function casts(): array
    {
        return ['counted_at' => 'datetime'];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'inventory_location_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockCountItem::class);
    }

    /** Net units gained or lost across the count (Σ counted − Σ expected). */
    public function netVariance(): int
    {
        return (int) ($this->items->sum('counted_qty') - $this->items->sum('expected_qty'));
    }
}
