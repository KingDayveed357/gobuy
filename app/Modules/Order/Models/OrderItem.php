<?php

namespace App\Modules\Order\Models;

use App\Modules\Catalog\Models\ProductVariant;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_variant_id',
        'name',
        'sku',
        'unit_price',
        'discount_amount',
        'quantity',
        'returned_quantity',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => Money::class,
            'discount_amount' => Money::class,
            'quantity' => 'integer',
            'returned_quantity' => 'integer',
            'line_total' => Money::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Units of this line still eligible to be returned.
     */
    public function returnableQuantity(): int
    {
        return max(0, $this->quantity - (int) $this->returned_quantity);
    }
}
