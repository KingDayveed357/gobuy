<?php

namespace App\Modules\Operations\Purchasing\Models;

use App\Admin\Models\Admin;
use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Operations\Purchasing\Enums\PurchaseOrderStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An order raised with a supplier to buy stock into a location. Its item lines
 * carry the ordered/received quantities and unit cost; receiving lands the goods
 * into stock through the inventory ledger and advances {@see PurchaseOrderStatus}.
 */
class PurchaseOrder extends Model
{
    protected $fillable = ['reference', 'supplier_id', 'inventory_location_id', 'created_by_id', 'status', 'note', 'ordered_at', 'received_at'];

    protected function casts(): array
    {
        return [
            'status' => PurchaseOrderStatus::class,
            'ordered_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
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
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /** Total value of the order (Σ unit cost × quantity ordered). */
    public function total(): Money
    {
        return $this->items->reduce(
            fn (Money $carry, PurchaseOrderItem $item): Money => $carry->plus($item->lineCost()),
            Money::zero(),
        );
    }

    public function totalOrdered(): int
    {
        return (int) $this->items->sum('quantity_ordered');
    }

    public function totalReceived(): int
    {
        return (int) $this->items->sum('quantity_received');
    }

    /** True once every line has been fully received. */
    public function isFullyReceived(): bool
    {
        return $this->items->every(fn (PurchaseOrderItem $item): bool => $item->outstanding() === 0);
    }
}
