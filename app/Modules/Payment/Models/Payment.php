<?php

namespace App\Modules\Payment\Models;

use App\Modules\Order\Models\Order;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'provider',
        'reference',
        'amount',
        'status',
        'paid_at',
        'payload',
    ];

    protected $attributes = [
        'provider' => 'paystack',
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'amount' => Money::class,
            'paid_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }
}
