<?php

namespace App\Modules\Customer\Models;

use App\Admin\Models\Admin;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class WholesaleProfile extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const MEDIA_DOCUMENTS = 'documents';

    public const TIERS = ['bronze', 'silver', 'gold'];

    protected $fillable = [
        'user_id',
        'business_name',
        'rc_number',
        'business_phone',
        'business_address',
        'intent',
        'industry',
        'status',
        'tier',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_DOCUMENTS);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    /**
     * @return MediaCollection<int, Media>
     */
    public function documents()
    {
        return $this->getMedia(self::MEDIA_DOCUMENTS);
    }
}
