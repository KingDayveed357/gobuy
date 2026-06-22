<?php

namespace App\Modules\Catalog\Database\Factories;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        $retail = fake()->numberBetween(1500, 250000);

        return [
            'product_id' => Product::factory(),
            'sku' => strtoupper(Str::random(8)),
            'name' => 'Default',
            'retail_price' => Money::fromNaira($retail),
            'sale_price' => null,
            'wholesale_price' => Money::fromNaira(round($retail * 0.8, 2)),
            'stock' => fake()->numberBetween(0, 200),
            'is_default' => true,
            'sort_order' => 0,
        ];
    }
}
