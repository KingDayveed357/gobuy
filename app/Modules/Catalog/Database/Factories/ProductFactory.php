<?php

namespace App\Modules\Catalog\Database\Factories;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'description' => fake()->paragraphs(2, true),
            'status' => 'active',
            'is_featured' => fake()->boolean(20),
            'is_vat_inclusive' => true,
            'is_tax_exempt' => false,
            'vat_rate' => 7.5,
        ];
    }

    /**
     * Every product gets a default variant carrying price + stock.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Product $product): void {
            if ($product->variants()->exists()) {
                return;
            }

            $retail = fake()->numberBetween(1500, 250000);

            $product->variants()->create([
                'sku' => strtoupper(Str::random(8)),
                'name' => 'Default',
                'is_default' => true,
                'retail_price' => Money::fromNaira($retail),
                'wholesale_price' => Money::fromNaira(round($retail * 0.8, 2)),
                'sale_price' => null,
                'stock' => fake()->numberBetween(0, 200),
            ]);
        });
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft']);
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }

    /** Set the default variant's pricing (values given in Naira). */
    public function priced(float $retail, ?float $wholesale = null, ?float $sale = null): static
    {
        return $this->afterCreating(fn (Product $product) => $product->primaryVariant()?->update(array_filter([
            'retail_price' => Money::fromNaira($retail),
            'wholesale_price' => $wholesale !== null ? Money::fromNaira($wholesale) : null,
            'sale_price' => $sale !== null ? Money::fromNaira($sale) : null,
        ], fn ($v) => $v !== null)));
    }

    public function stock(int $quantity): static
    {
        return $this->afterCreating(fn (Product $product) => $product->primaryVariant()?->update(['stock' => $quantity]));
    }

    public function outOfStock(): static
    {
        return $this->stock(0);
    }

    public function sku(string $sku): static
    {
        return $this->afterCreating(fn (Product $product) => $product->primaryVariant()?->update(['sku' => $sku]));
    }
}
