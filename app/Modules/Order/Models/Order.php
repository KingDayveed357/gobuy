<?php

namespace App\Modules\Order\Models;

use App\Models\User;
use App\Modules\Logistics\Models\Shipment;
use App\Modules\Order\Database\Factories\OrderFactory;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Enums\PaymentMethod;
use App\Modules\Order\Enums\PaymentStatus;
use App\Modules\Payment\Models\BankTransferProof;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Models\Refund;
use App\Modules\Pricing\Models\Coupon;
use App\Modules\Returns\Models\ReturnRequest;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'customer_type',
        'customer_name',
        'customer_email',
        'customer_phone',
        'address_line',
        'city',
        'state',
        'status',
        'payment_status',
        'payment_method',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'delivery_fee',
        'total',
        'refunded_total',
        'store_credit_applied',
        'placed_at',
        'delivered_at',
        'coupon_id',
        'coupon_code',
    ];

    protected $attributes = [
        'status' => 'pending',
        'payment_status' => 'unpaid',
        'delivery_fee' => 0,
        'discount_amount' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'payment_method' => PaymentMethod::class,
            'subtotal' => Money::class,
            'discount_amount' => Money::class,
            'tax_amount' => Money::class,
            'delivery_fee' => Money::class,
            'total' => Money::class,
            'refunded_total' => Money::class,
            'store_credit_applied' => Money::class,
            'placed_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * What the gateway / POD / bank transfer must actually collect — the order
     * total minus any store credit tendered against it.
     */
    public function amountDue(): Money
    {
        $due = $this->total->kobo - ($this->store_credit_applied?->kobo ?? 0);

        return Money::fromKobo(max(0, $due));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class);
    }

    /**
     * The amount still refundable on this order (order total minus everything
     * already refunded/credited). The single guard against over-refunding,
     * shared by the legacy admin refund path and the Returns module.
     */
    public function refundableRemaining(): Money
    {
        $remaining = $this->total->kobo - ($this->refunded_total?->kobo ?? 0);

        return Money::fromKobo(max(0, $remaining));
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest();
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function transferProofs(): HasMany
    {
        return $this->hasMany(BankTransferProof::class)->latest();
    }

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Paid;
    }

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }
}
