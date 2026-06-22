<?php

namespace App\Modules\Catalog\Database\Seeders;

use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Support\Money;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    /** @var list<string> Demo product images shipped with the Phoenix theme. */
    private array $images = [
        '1.png', '2.png', '3.png', '4.png', '5.png', '6.png', '7.png', '8.png',
        '10.png', '12.png', '16.png', '17.png', '18.png', '19.png', '20.png',
        '21.png', '23.png', '24.png', '25.png', '26.png', '27.png',
    ];

    public function run(): void
    {
        $brands = collect(['GoBuy Basics', 'SafeGuard Pro', 'PowerMax', 'UrbanLiving'])
            ->map(fn (string $name) => Brand::create([
                'name' => $name,
                'slug' => str($name)->slug(),
            ]));

        $categories = collect(['Groceries', 'Electronics', 'Home & Kitchen', 'Fashion'])
            ->map(fn (string $name) => Category::factory()->create(['name' => $name, 'slug' => str($name)->slug()]));

        // A sub-category to demonstrate hierarchy.
        $electronics = $categories[1];
        Category::factory()->create(['name' => 'Phones', 'slug' => 'phones', 'parent_id' => $electronics->id]);

        $categories->each(function (Category $category) use ($brands): void {
            Product::factory()->count(8)->for($category)->create()
                ->each(function (Product $p) use ($brands): void {
                    $p->update(['brand_id' => $brands->random()->id]);
                    $this->attachImage($p);
                });
        });

        Product::factory()->count(6)->featured()->for($categories->random())->create()
            ->each(fn (Product $p) => $this->attachImage($p));

        // One product on sale (discount UX).
        $sale = Product::factory()->featured()->for($electronics)->create(['name' => 'gobuy Wireless Earbuds Pro']);
        $sale->primaryVariant()->update([
            'retail_price' => Money::fromNaira(45000),
            'sale_price' => Money::fromNaira(33000),
            'stock' => 80,
        ]);
        $this->attachImage($sale);

        // One multi-variant product with tiered wholesale + specifications.
        $this->seedVariantProduct($electronics);
    }

    private function seedVariantProduct(Category $category): void
    {
        $product = Product::factory()->featured()->for($category)->create([
            'name' => 'gobuy Power Bank 20,000mAh',
            'weight_g' => 420,
        ]);
        $product->variants()->delete(); // replace the auto default with real variants

        // Relational option axis + values.
        $colour = $product->options()->create(['name' => 'Colour', 'sort_order' => 0]);
        $values = collect(['Black', 'White', 'Blue'])
            ->mapWithKeys(fn (string $name, int $i) => [
                $name => $colour->values()->create(['value' => $name, 'sort_order' => $i]),
            ]);

        foreach (['Black' => 'PB-BLK', 'White' => 'PB-WHT', 'Blue' => 'PB-BLU'] as $colourName => $sku) {
            $variant = $product->variants()->create([
                'sku' => $sku,
                'name' => $colourName,
                'retail_price' => Money::fromNaira(28000),
                'wholesale_price' => Money::fromNaira(23000),
                'stock' => fake()->numberBetween(20, 120),
                'is_default' => $colourName === 'Black',
            ]);
            $variant->optionValues()->attach($values[$colourName]->id);
        }

        // Relational specifications.
        $product->specifications()->createMany([
            ['label' => 'Capacity', 'value' => '20,000 mAh', 'sort_order' => 0],
            ['label' => 'Output', 'value' => '22.5W Fast Charge', 'sort_order' => 1],
            ['label' => 'Ports', 'value' => 'USB-C, 2x USB-A', 'sort_order' => 2],
        ]);

        // Tiered wholesale: cheaper per-unit at higher quantities.
        $product->quantityDiscounts()->createMany([
            ['min_qty' => 10, 'unit_price' => Money::fromNaira(22000)],
            ['min_qty' => 50, 'unit_price' => Money::fromNaira(20000)],
        ]);

        $this->attachImage($product);
    }

    private function attachImage(Product $product): void
    {
        $file = $this->images[array_rand($this->images)];
        $path = public_path("theme/img/products/{$file}");

        if (is_file($path)) {
            $product->addMedia($path)
                ->preservingOriginal()
                ->usingFileName(Str::uuid().'.png')
                ->toMediaCollection(Product::MEDIA_COLLECTION);
        }
    }
}
