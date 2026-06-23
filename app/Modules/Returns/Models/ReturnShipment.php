<?php

namespace App\Modules\Returns\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnShipment extends Model
{
    protected $fillable = [
        'return_request_id',
        'tracking_reference',
        'carrier',
        'payer',
        'label_path',
        'shipped_at',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'shipped_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    public function isMerchantPaid(): bool
    {
        return $this->payer === 'merchant';
    }
}
