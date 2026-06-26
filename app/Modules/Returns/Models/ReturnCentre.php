<?php

namespace App\Modules\Returns\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnCentre extends Model
{
    protected $fillable = [
        'name',
        'address',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
