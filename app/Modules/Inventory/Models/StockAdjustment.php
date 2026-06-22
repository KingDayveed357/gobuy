<?php

namespace App\Modules\Inventory\Models;

use App\Admin\Models\Admin;
use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustment extends Model
{
    protected $fillable = [
        'product_variant_id',
        'admin_id',
        'delta',
        'quantity_after',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'delta' => 'integer',
            'quantity_after' => 'integer',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
