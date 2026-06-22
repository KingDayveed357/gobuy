<?php

namespace App\Modules\Payment\Models;

use App\Admin\Models\Admin;
use App\Modules\Order\Models\Order;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class BankTransferProof extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const MEDIA_RECEIPT = 'receipt';

    protected $fillable = [
        'order_id', 'amount', 'sender_name', 'bank_reference',
        'status', 'reviewed_by', 'reviewed_at', 'note',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    protected function casts(): array
    {
        return [
            'amount' => Money::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_RECEIPT)->singleFile();
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    public function receiptUrl(): ?string
    {
        $url = $this->getFirstMediaUrl(self::MEDIA_RECEIPT);

        return $url !== '' ? $url : null;
    }
}
