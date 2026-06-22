<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A relational key/value specification for a product (ordered).
 */
class ProductSpecification extends Model
{
    protected $fillable = ['product_id', 'spec_template_id', 'label', 'value', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SpecTemplate::class, 'spec_template_id');
    }
}
