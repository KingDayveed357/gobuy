<?php

namespace App\Modules\Catalog\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A shopper's request to buy more of a product than the on-hand stock allows.
 * A sales/wholesale lead — captured for an admin to follow up, NOT a backorder
 * (no payment, reservation, or fulfilment machinery attached).
 */
class BulkQuantityRequest extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'user_id',
        'name',
        'email',
        'phone',
        'quantity',
        'note',
        'status',
    ];

    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
