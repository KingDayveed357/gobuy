<?php

namespace App\Modules\Pricing\Models;

use App\Admin\Models\Admin;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Immutable audit row capturing a single price change. Written by the
 * RecordsPriceHistory observer whenever a priced field changes.
 */
class PriceHistory extends Model
{
    protected $fillable = [
        'priceable_type', 'priceable_id', 'field',
        'old_value', 'new_value', 'admin_id', 'reason',
    ];

    protected function casts(): array
    {
        return [
            'old_value' => Money::class,
            'new_value' => Money::class,
        ];
    }

    public function priceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
