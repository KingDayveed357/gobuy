<?php

namespace App\Modules\Operations\Transfers\Models;

use App\Admin\Models\Admin;
use App\Modules\Inventory\Models\InventoryLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A recorded movement of stock from one location to another. Its item lines drive
 * a transfer_out / transfer_in pair per variant through the inventory ledger.
 */
class StockTransfer extends Model
{
    protected $fillable = ['from_location_id', 'to_location_id', 'created_by_id', 'note'];

    public function from(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'from_location_id');
    }

    public function to(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'to_location_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function totalUnits(): int
    {
        return (int) $this->items->sum('quantity');
    }
}
