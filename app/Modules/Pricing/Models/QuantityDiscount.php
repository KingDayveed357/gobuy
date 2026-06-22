<?php

namespace App\Modules\Pricing\Models;

use App\Modules\Catalog\Models\Product;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuantityDiscount extends Model
{
    protected $fillable = [
        'product_id',
        'min_qty',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'min_qty' => 'integer',
            'unit_price' => Money::class,
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
