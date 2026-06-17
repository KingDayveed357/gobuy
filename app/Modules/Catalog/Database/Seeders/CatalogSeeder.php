<?php

namespace App\Modules\Catalog\Database\Seeders;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    /**
     * Demo product images shipped with the Phoenix theme.
     *
     * @var list<string>
     */
    private array $images = [
        '1.png', '2.png', '3.png', '4.png', '5.png', '6.png', '7.png', '8.png',
        '10.png', '12.png', '16.png', '17.png', '18.png', '19.png', '20.png',
        '21.png', '23.png', '24.png', '25.png', '26.png', '27.png',
    ];

    public function run(): void
    {
        $categories = collect(['Groceries', 'Electronics', 'Home & Kitchen', 'Fashion'])
            ->map(fn (string $name) => Category::factory()->create(['name' => $name, 'slug' => str($name)->slug()]));

        $categories->each(function (Category $category): void {
            Product::factory()
                ->count(8)
                ->for($category)
                ->create()
                ->each(fn (Product $product) => $this->attachImage($product));
        });

        Product::factory()
            ->count(6)
            ->featured()
            ->for($categories->random())
            ->create()
            ->each(fn (Product $product) => $this->attachImage($product));
    }

    private function attachImage(Product $product): void
    {
        $file = $this->images[array_rand($this->images)];

        $product->images()->create([
            'path' => "theme/img/products/{$file}",
            'alt' => $product->name,
            'is_primary' => true,
        ]);
    }
}
