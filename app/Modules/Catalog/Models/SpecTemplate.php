<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A category-scoped template suggesting specification labels for the admin.
 */
class SpecTemplate extends Model
{
    protected $fillable = ['category_id', 'name', 'labels'];

    protected function casts(): array
    {
        return ['labels' => 'array'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
