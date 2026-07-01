<?php

namespace App\Modules\Catalog\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A shopper's request to be emailed when a sold-out variant is back in stock.
 * Deleted once fulfilled, so a row always represents an outstanding waiter.
 */
class StockNotification extends Model
{
    protected $fillable = [
        'product_variant_id',
        'user_id',
        'email',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
