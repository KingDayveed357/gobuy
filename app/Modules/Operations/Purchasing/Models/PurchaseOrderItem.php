<?php

namespace App\Modules\Operations\Purchasing\Models;

use App\Modules\Catalog\Models\ProductVariant;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single product line on a purchase order: how many were ordered, how many have
 * been received so far, and the agreed unit cost.
 */
class PurchaseOrderItem extends Model
{
    protected $fillable = ['purchase_order_id', 'product_variant_id', 'quantity_ordered', 'quantity_received', 'unit_cost'];

    protected function casts(): array
    {
        return [
            'quantity_ordered' => 'integer',
            'quantity_received' => 'integer',
            'unit_cost' => Money::class,
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /** Units still to be delivered. */
    public function outstanding(): int
    {
        return max(0, $this->quantity_ordered - $this->quantity_received);
    }

    public function lineCost(): Money
    {
        return $this->unit_cost->times($this->quantity_ordered);
    }
}
