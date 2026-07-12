<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A place stock physically sits — a shop, a store room, a warehouse, a supplier.
 * The Commerce Core ships one seeded "Default" location and never exposes the
 * concept; the multi-location module lets a retailer add and manage more.
 */
class InventoryLocation extends Model
{
    protected $fillable = ['name', 'code', 'type', 'is_default', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** The single location every Core installation has; where stock lives by default. */
    public static function default(): self
    {
        return static::query()->where('is_default', true)->firstOrFail();
    }
}
