<?php

namespace App\Modules\Payment\Models;

use App\Admin\Models\Admin;
use App\Modules\Order\Models\Order;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    protected $fillable = [
        'order_id',
        'payment_id',
        'admin_id',
        'amount',
        'reason',
        'status',
        'provider_reference',
        'payload',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => Money::class,
            'payload' => 'array',
            'confirmed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
