<?php

namespace App\Modules\Operations\Purchasing\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A source of stock — the wholesaler, importer or distributor a purchase order is
 * raised against. Owned by the ops.purchasing module; Core knows nothing of it.
 */
class Supplier extends Model
{
    protected $fillable = ['name', 'contact_name', 'phone', 'email', 'address', 'notes', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
