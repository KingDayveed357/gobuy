<?php

namespace App\Modules\Returns\Models;

use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A customer's store-credit wallet. `balance` is a cached integer-kobo sum of
 * the ledger entries; it is only ever recomputed from {@see StoreCreditEntry},
 * never edited ad hoc.
 */
class StoreCredit extends Model
{
    protected $fillable = ['user_id', 'balance'];

    protected function casts(): array
    {
        return [
            'balance' => Money::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(StoreCreditEntry::class);
    }
}
