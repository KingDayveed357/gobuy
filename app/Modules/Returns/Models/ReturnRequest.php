<?php

namespace App\Modules\Returns\Models;

use App\Admin\Models\Admin;
use App\Models\User;
use App\Modules\Order\Models\Order;
use App\Modules\Returns\Database\Factories\ReturnRequestFactory;
use App\Modules\Returns\Enums\RefundDestination;
use App\Modules\Returns\Enums\ReturnStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ReturnRequest extends Model implements HasMedia
{
    /** @use HasFactory<ReturnRequestFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use SoftDeletes;

    /** Customer-uploaded evidence photos (damage, wrong item, etc.). */
    public const MEDIA_PHOTOS = 'photos';

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_PHOTOS);
    }

    protected $fillable = [
        'reference',
        'order_id',
        'user_id',
        'status',
        'reason_code',
        'customer_note',
        'refund_destination',
        'refunded_total',
        'risk_score',
        'risk_flags',
        'auto_approved',
        'return_shipping_payer',
        'window_expires_at',
        'approved_by',
        'received_by',
        'settled_by',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReturnStatus::class,
            'refund_destination' => RefundDestination::class,
            'refunded_total' => 'integer',
            'risk_score' => 'integer',
            'risk_flags' => 'array',
            'auto_approved' => 'boolean',
            'window_expires_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ReturnEvent::class)->latest();
    }

    public function returnShipment(): HasOne
    {
        return $this->hasOne(ReturnShipment::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    protected static function newFactory(): ReturnRequestFactory
    {
        return ReturnRequestFactory::new();
    }
}
