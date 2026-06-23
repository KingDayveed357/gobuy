<?php

namespace App\Modules\Returns\Models;

use App\Admin\Models\Admin;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One immutable line in the store-credit ledger. `amount` is signed kobo:
 * positive credits, negative spends/expiries.
 */
class StoreCreditEntry extends Model
{
    public const UPDATED_AT = null;

    public const TYPE_REFUND_CREDIT = 'refund_credit';

    public const TYPE_SPEND = 'spend';

    public const TYPE_EXPIRY = 'expiry';

    public const TYPE_ADMIN_ADJUST = 'admin_adjust';

    protected $fillable = [
        'store_credit_id',
        'amount',
        'type',
        'source_type',
        'source_id',
        'reason',
        'expires_at',
        'idempotency_key',
        'admin_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => Money::class,
            'expires_at' => 'datetime',
        ];
    }

    public function storeCredit(): BelongsTo
    {
        return $this->belongsTo(StoreCredit::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
