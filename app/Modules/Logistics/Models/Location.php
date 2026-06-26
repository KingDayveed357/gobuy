<?php

namespace App\Modules\Logistics\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'name', 'address', 'city', 'state', 'phone', 'opening_hours', 
        'is_active', 'is_pickup', 'is_return', 'is_default_return'
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_pickup' => 'boolean',
            'is_return' => 'boolean',
            'is_default_return' => 'boolean',
        ];
    }

    protected static function booted()
    {
        static::saving(function ($location) {
            // Ensure only one default return centre exists
            if ($location->is_default_return && $location->isDirty('is_default_return')) {
                static::where('id', '!=', $location->id)->update(['is_default_return' => false]);
            }
            
            // If it's not a return centre, it cannot be the default return centre
            if (! $location->is_return) {
                $location->is_default_return = false;
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePickup(Builder $query): Builder
    {
        return $query->where('is_pickup', true);
    }

    public function scopeReturnCentre(Builder $query): Builder
    {
        return $query->where('is_return', true);
    }

    public function scopeDefaultReturn(Builder $query): Builder
    {
        return $query->where('is_default_return', true);
    }

    public function formatted(): string
    {
        return collect([$this->address, $this->city, $this->state])->filter()->implode(', ');
    }
}
