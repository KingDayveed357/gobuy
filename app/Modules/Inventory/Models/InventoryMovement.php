<?php

namespace App\Modules\Inventory\Models;

use App\Admin\Models\Admin;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Enums\MovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One append-only entry in the inventory audit trail: a signed stock change at a
 * location, why it happened, what it referenced (order, return, adjustment, PO,
 * transfer…) and who did it. The permanent record the whole platform reconciles
 * against. Write-once — there is no updated_at.
 */
class InventoryMovement extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'product_variant_id',
        'inventory_location_id',
        'type',
        'quantity',
        'quantity_after',
        'reference_type',
        'reference_id',
        'admin_id',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'type' => MovementType::class,
            'quantity' => 'integer',
            'quantity_after' => 'integer',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'inventory_location_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
