<?php

namespace App\Modules\Returns\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Append-only audit record for a return request. Has no updated_at — events are
 * never modified.
 */
class ReturnEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'return_request_id',
        'actor_type',
        'actor_id',
        'from_status',
        'to_status',
        'action',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }
}
