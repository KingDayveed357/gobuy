<?php

namespace App\Modules\Returns\Models;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Returns\Enums\ReturnItemDisposition;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnItem extends Model
{
    protected $fillable = [
        'return_request_id',
        'order_item_id',
        'product_variant_id',
        'quantity',
        'approved_quantity',
        'unit_price_snapshot',
        'reason_code',
        'condition_reported',
        'disposition',
        'resolution',
        'restocked',
        'inspection_note',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'approved_quantity' => 'integer',
            'unit_price_snapshot' => Money::class,
            'disposition' => ReturnItemDisposition::class,
            'resolution' => \App\Modules\Returns\Enums\ReturnItemResolution::class,
            'restocked' => 'boolean',
        ];
    }

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Units the agent accepted (falls back to the requested quantity until
     * inspection sets an explicit approved quantity).
     */
    public function effectiveQuantity(): int
    {
        return $this->approved_quantity ?? $this->quantity;
    }
}
