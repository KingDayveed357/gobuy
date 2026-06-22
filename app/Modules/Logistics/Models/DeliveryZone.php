<?php

namespace App\Modules\Logistics\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryZone extends Model
{
    protected $fillable = [
        'name', 'slug', 'base_fee', 'per_kg_fee', 'free_over_subtotal', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'base_fee' => Money::class,
            'per_kg_fee' => Money::class,
            'free_over_subtotal' => Money::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function states(): HasMany
    {
        return $this->hasMany(DeliveryZoneState::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
