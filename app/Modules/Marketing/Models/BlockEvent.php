<?php

namespace App\Modules\Marketing\Models;

use App\Modules\Marketing\Services\BlockAnalytics;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single storefront interaction with a homepage section — an impression (the
 * block scrolled into view) or a click (a CTA/product inside it was clicked).
 * Aggregated by {@see BlockAnalytics} into CTR.
 */
class BlockEvent extends Model
{
    public const TYPE_IMPRESSION = 'impression';

    public const TYPE_CLICK = 'click';

    public const TYPES = [self::TYPE_IMPRESSION, self::TYPE_CLICK];

    /** Events are write-once; there is no updated_at column. */
    public const UPDATED_AT = null;

    protected $fillable = ['homepage_section_id', 'type', 'created_at'];

    public function section(): BelongsTo
    {
        return $this->belongsTo(HomepageSection::class, 'homepage_section_id');
    }
}
