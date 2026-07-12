<?php

namespace App\Modules\Catalog\Database\Seeders;

use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Inventory\Services\InventoryLedger;
use App\Modules\Operations\Packaging\Models\PackagingUnit;
use App\Support\Money;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds a realistic Nigerian supermarket / drinks-and-provisions catalogue —
 * beer, stout, malt, juice, water, soft & energy drinks, wine, spirits, biscuits,
 * noodles, snacks and dairy — with size variants, packaging (cartons/crates),
 * multi-location stock and generated placeholder imagery. Modelled on the kind of
 * inventory a Lagos neighbourhood store actually carries.
 */
class CatalogSeeder extends Seeder
{
    private InventoryLedger $ledger;

    /** @var array<string, InventoryLocation> keyed shop|warehouse|store_room */
    private array $locations = [];

    /** @var array<string, Brand> */
    private array $brands = [];

    public function run(): void
    {
        $this->ledger = app(InventoryLedger::class);
        $this->setUpLocations();

        $sort = 0;
        foreach ($this->catalogue() as $categoryName => $group) {
            $category = Category::firstOrCreate(
                ['slug' => Str::slug($categoryName)],
                ['name' => $categoryName, 'is_active' => true, 'sort_order' => $sort++],
            );

            foreach ($group['products'] as $definition) {
                $this->createProduct($category, $group, $definition);
            }
        }
    }

    /**
     * Turn the seeded single "Default" location into a proper three-location
     * footprint: the shop counter, a warehouse and a back store room.
     */
    private function setUpLocations(): void
    {
        $shop = InventoryLocation::query()->where('is_default', true)->first()
            ?? InventoryLocation::create(['name' => 'Main Shop', 'code' => 'main-shop', 'is_default' => true]);

        $shop->update(['name' => 'Main Shop', 'code' => 'main-shop', 'type' => 'shop', 'is_active' => true]);

        $this->locations = [
            'shop' => $shop,
            'warehouse' => InventoryLocation::firstOrCreate(
                ['code' => 'warehouse'],
                ['name' => 'Warehouse', 'type' => 'warehouse', 'is_active' => true],
            ),
            'store_room' => InventoryLocation::firstOrCreate(
                ['code' => 'store-room'],
                ['name' => 'Store Room', 'type' => 'storage', 'is_active' => true],
            ),
        ];
    }

    private function brand(string $name): Brand
    {
        return $this->brands[$name] ??= Brand::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'is_active' => true],
        );
    }

    /**
     * @param  array<string, mixed>  $group
     * @param  array<string, mixed>  $def
     */
    private function createProduct(Category $category, array $group, array $def): void
    {
        $brand = $this->brand($def['brand'] ?? $group['brand']);
        $exempt = (bool) ($group['tax_exempt'] ?? false);
        $weight = (int) ($def['weight'] ?? $group['weight'] ?? 700);

        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => $def['name'],
            'slug' => Str::slug($def['name']).'-'.Str::lower(Str::random(4)),
            'description' => $this->description($def, $category, $brand),
            'status' => 'active',
            'condition' => 'new',
            'weight_g' => $weight,
            'length_mm' => (int) ($def['length'] ?? 80),
            'width_mm' => (int) ($def['width'] ?? 80),
            'height_mm' => (int) ($def['height'] ?? 250),
            'cost_price_usd' => max(1, (int) round(($def['price'] ?? 1000) * 0.65 / 1550)),
            'is_vat_inclusive' => true,
            'is_tax_exempt' => $exempt,
            'vat_rate' => $exempt ? 0 : 7.5,
            'is_returnable' => false,
            'return_window_days' => 0,
            'is_featured' => (bool) ($def['featured'] ?? false),
            'rating_avg' => fake()->randomFloat(1, 3.8, 5.0),
            'rating_count' => fake()->numberBetween(4, 220),
        ]);

        // Replace the auto-created default variant with our real ones.
        $product->variants()->delete();

        $variants = $def['variants'] ?? [[
            'label' => $def['unit'] ?? 'Each',
            'price' => $def['price'],
            'wholesale' => $def['wholesale'] ?? null,
            'sale' => $def['sale'] ?? null,
            'stock' => $def['stock'] ?? null,
            'reorder' => $def['reorder'] ?? null,
            'pack' => $def['pack'] ?? null,
            'default' => true,
        ]];

        $codePrefix = strtoupper(Str::substr(Str::slug($category->name), 0, 3));
        foreach (array_values($variants) as $i => $v) {
            $this->createVariant($product, $group, $codePrefix, $v, $i, count($variants) === 1 || ($v['default'] ?? false));
        }

        $this->attachPlaceholder($product, $group['color'] ?? '#4f46e5', $brand->name, $category->name);
    }

    /**
     * @param  array<string, mixed>  $group
     * @param  array<string, mixed>  $v
     */
    private function createVariant(Product $product, array $group, string $codePrefix, array $v, int $index, bool $isDefault): void
    {
        $retail = (int) $v['price'];
        $wholesale = (int) ($v['wholesale'] ?? round($retail * 0.9));
        $reorder = (int) ($v['reorder'] ?? $group['reorder'] ?? 24);

        $variant = $product->variants()->create([
            'sku' => $codePrefix.'-'.strtoupper(Str::slug(Str::substr($product->name, 0, 10))).'-'.strtoupper(Str::random(4)),
            'name' => $v['label'] ?? 'Each',
            'retail_price' => Money::fromNaira($retail),
            'wholesale_price' => Money::fromNaira($wholesale),
            'sale_price' => isset($v['sale']) ? Money::fromNaira((int) $v['sale']) : null,
            'low_stock_threshold' => $reorder,
            'is_default' => $isDefault,
            'sort_order' => $index,
        ]);

        // A realistic on-hand: usually healthy, occasionally at/under the reorder
        // line so the low-stock views have something to surface.
        $total = $v['stock'] ?? (fake()->boolean(18)
            ? fake()->numberBetween(0, $reorder)
            : fake()->numberBetween($reorder * 2, $reorder * 12));

        $this->distributeStock($variant, (int) $total);
        $this->attachPackaging($variant, $v['pack'] ?? null);
    }

    /**
     * Split a variant's stock across the shop, warehouse and store room, each move
     * recorded as an opening movement through the ledger so the per-location views
     * and reconciliation stay truthful.
     */
    private function distributeStock(ProductVariant $variant, int $total): void
    {
        if ($total <= 0) {
            return;
        }

        $shop = (int) round($total * fake()->randomFloat(2, 0.4, 0.6));
        $warehouse = (int) round(($total - $shop) * fake()->randomFloat(2, 0.55, 0.9));
        $storeRoom = $total - $shop - $warehouse;

        $splits = [
            'shop' => $shop,
            'warehouse' => $warehouse,
            'store_room' => max(0, $storeRoom),
        ];

        foreach ($splits as $key => $qty) {
            if ($qty > 0) {
                $this->ledger->record($variant, $qty, MovementType::Opening, [
                    'location' => $this->locations[$key],
                    'notify' => false,
                ]);
            }
        }
    }

    /**
     * @param  array{0: string, 1: int, 2: int}|null  $pack  [name, base units, price]
     */
    private function attachPackaging(ProductVariant $variant, ?array $pack): void
    {
        if ($pack === null) {
            return;
        }

        [$name, $multiplier, $price] = $pack;

        PackagingUnit::create([
            'product_variant_id' => $variant->id,
            'name' => $name,
            'multiplier' => $multiplier,
            'barcode' => '615'.str_pad((string) fake()->numberBetween(0, 9999999999), 10, '0', STR_PAD_LEFT),
            'sku' => $variant->sku.'-'.strtoupper(Str::substr($name, 0, 3)),
            'retail_price' => Money::fromNaira($price),
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $def
     */
    private function description(array $def, Category $category, Brand $brand): string
    {
        if (! empty($def['description'])) {
            return $def['description'];
        }

        return sprintf(
            '%s from %s. A staple on Nigerian shelves — %s stocked fresh and sold by the unit or by the %s. Store cool and dry, away from direct sunlight.',
            $def['name'],
            $brand->name,
            Str::lower($category->name),
            Str::lower($def['pack'][0] ?? 'carton'),
        );
    }

    /**
     * Generate a clean, consistently-sized (800×800) SVG placeholder carrying the
     * product name and brand over a category colour, and attach it to the gallery.
     */
    private function attachPlaceholder(Product $product, string $color, string $brand, string $category): void
    {
        $name = htmlspecialchars($product->name, ENT_QUOTES);
        $brandLabel = htmlspecialchars(Str::upper($brand), ENT_QUOTES);
        $categoryLabel = htmlspecialchars(Str::upper($category), ENT_QUOTES);
        $initials = htmlspecialchars(Str::upper(Str::substr($product->name, 0, 2)), ENT_QUOTES);

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 800" width="800" height="800">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$color}"/>
      <stop offset="100%" stop-color="#0f172a"/>
    </linearGradient>
  </defs>
  <rect width="800" height="800" fill="url(#g)"/>
  <circle cx="400" cy="300" r="150" fill="#ffffff" fill-opacity="0.12"/>
  <text x="400" y="330" font-family="Arial, sans-serif" font-size="120" font-weight="700" fill="#ffffff" text-anchor="middle">{$initials}</text>
  <text x="400" y="560" font-family="Arial, sans-serif" font-size="46" font-weight="700" fill="#ffffff" text-anchor="middle">{$name}</text>
  <text x="400" y="612" font-family="Arial, sans-serif" font-size="26" fill="#ffffff" fill-opacity="0.8" text-anchor="middle">{$brandLabel}</text>
  <text x="400" y="700" font-family="Arial, sans-serif" font-size="22" letter-spacing="4" fill="#ffffff" fill-opacity="0.6" text-anchor="middle">{$categoryLabel}</text>
</svg>
SVG;

        $product->addMediaFromString($svg)
            ->usingFileName(Str::slug($product->name).'-'.Str::lower(Str::random(4)).'.svg')
            ->toMediaCollection(Product::MEDIA_COLLECTION);
    }

    /**
     * The catalogue. Prices are in Naira and reflect typical 2024/25 street prices.
     * Each product may declare size `variants` (each optionally with a `pack`
     * packaging unit) or a single `unit`/`price`.
     *
     * @return array<string, array<string, mixed>>
     */
    private function catalogue(): array
    {
        return [
            'Beer' => [
                'brand' => 'Nigerian Breweries', 'color' => '#b45309', 'weight' => 650, 'reorder' => 24,
                'products' => [
                    ['name' => 'Star Lager', 'unit' => 'Bottle 60cl', 'price' => 1200, 'featured' => true, 'pack' => ['Crate of 12', 12, 13800]],
                    ['name' => 'Gulder Lager', 'unit' => 'Bottle 60cl', 'price' => 1300, 'pack' => ['Crate of 12', 12, 15000]],
                    ['name' => 'Life Continental Lager', 'unit' => 'Bottle 60cl', 'price' => 1000, 'pack' => ['Crate of 12', 12, 11400]],
                    ['name' => 'Goldberg Lager', 'unit' => 'Bottle 60cl', 'price' => 1050, 'pack' => ['Crate of 12', 12, 12000]],
                    ['name' => 'Trophy Lager', 'brand' => 'International Breweries', 'unit' => 'Bottle 60cl', 'price' => 1000, 'pack' => ['Crate of 12', 12, 11400]],
                    ['name' => 'Hero Lager', 'brand' => 'International Breweries', 'unit' => 'Bottle 60cl', 'price' => 1100, 'featured' => true, 'pack' => ['Crate of 12', 12, 12600]],
                    ['name' => 'Heineken Lager', 'unit' => 'Bottle 65cl', 'price' => 1600, 'sale' => 1450, 'pack' => ['Crate of 12', 12, 18600]],
                    ['name' => 'Budweiser', 'brand' => 'International Breweries', 'unit' => 'Bottle 60cl', 'price' => 1500, 'pack' => ['Crate of 12', 12, 17400]],
                    ['name' => 'Desperados Tequila Lager', 'unit' => 'Bottle 60cl', 'price' => 1700, 'pack' => ['Crate of 12', 12, 19800]],
                    ['name' => 'Tiger Lager', 'unit' => 'Bottle 60cl', 'price' => 1400, 'pack' => ['Crate of 12', 12, 16200]],
                    ['name' => 'Star Radler', 'unit' => 'Bottle 60cl', 'price' => 1100, 'pack' => ['Crate of 12', 12, 12600]],
                    ['name' => '33 Export Lager', 'unit' => 'Bottle 60cl', 'price' => 1050, 'pack' => ['Crate of 12', 12, 12000]],
                ],
            ],
            'Stout' => [
                'brand' => 'Guinness Nigeria', 'color' => '#292524', 'weight' => 660, 'reorder' => 24,
                'products' => [
                    ['name' => 'Guinness Foreign Extra Stout', 'featured' => true, 'variants' => [
                        ['label' => 'Small 20cl', 'price' => 600, 'reorder' => 24, 'pack' => ['Pack of 24', 24, 13000]],
                        ['label' => 'Bottle 60cl', 'price' => 1400, 'default' => true, 'reorder' => 24, 'pack' => ['Crate of 12', 12, 16200]],
                        ['label' => 'Can 33cl', 'price' => 1000, 'reorder' => 24, 'pack' => ['Pack of 24', 24, 22000]],
                    ]],
                    ['name' => 'Legend Extra Stout', 'brand' => 'Nigerian Breweries', 'variants' => [
                        ['label' => 'Small 20cl', 'price' => 550, 'reorder' => 24, 'pack' => ['Pack of 24', 24, 12000]],
                        ['label' => 'Bottle 60cl', 'price' => 1300, 'default' => true, 'reorder' => 24, 'pack' => ['Crate of 12', 12, 15000]],
                    ]],
                    ['name' => 'Satzenbrau Pilsner Stout', 'brand' => 'Guinness Nigeria', 'unit' => 'Bottle 60cl', 'price' => 1100, 'pack' => ['Crate of 12', 12, 12600]],
                ],
            ],
            'Malt Drinks' => [
                'brand' => 'Nigerian Breweries', 'color' => '#a16207', 'weight' => 400, 'reorder' => 24, 'tax_exempt' => true,
                'products' => [
                    ['name' => 'Maltina', 'featured' => true, 'variants' => [
                        ['label' => 'Bottle 33cl', 'price' => 450, 'default' => true, 'reorder' => 24, 'pack' => ['Crate of 24', 24, 9600]],
                        ['label' => 'Can 33cl', 'price' => 500, 'reorder' => 24, 'pack' => ['Pack of 24', 24, 11000]],
                        ['label' => 'PET 40cl', 'price' => 550, 'reorder' => 12],
                    ]],
                    ['name' => 'Amstel Malta', 'variants' => [
                        ['label' => 'Bottle 33cl', 'price' => 450, 'default' => true, 'reorder' => 24, 'pack' => ['Crate of 24', 24, 9600]],
                        ['label' => 'Can 33cl', 'price' => 500, 'reorder' => 24, 'pack' => ['Pack of 24', 24, 11000]],
                    ]],
                    ['name' => 'Malta Guinness', 'brand' => 'Guinness Nigeria', 'variants' => [
                        ['label' => 'Bottle 33cl', 'price' => 500, 'default' => true, 'reorder' => 24, 'pack' => ['Crate of 24', 24, 11000]],
                        ['label' => 'Can 33cl', 'price' => 550, 'reorder' => 24, 'pack' => ['Pack of 24', 24, 12500]],
                    ]],
                    ['name' => 'Hi-Malt', 'brand' => 'International Breweries', 'unit' => 'Bottle 33cl', 'price' => 400, 'pack' => ['Crate of 24', 24, 8600]],
                    ['name' => 'Grand Malt', 'brand' => 'International Breweries', 'unit' => 'Bottle 33cl', 'price' => 420, 'pack' => ['Crate of 24', 24, 9000]],
                ],
            ],
            'Juice' => [
                'brand' => 'Chi Limited', 'color' => '#ea580c', 'weight' => 1100, 'reorder' => 12, 'tax_exempt' => true,
                'products' => [
                    ['name' => 'Chivita 100% Juice', 'featured' => true, 'variants' => [
                        ['label' => 'Pack 1L', 'price' => 1800, 'default' => true, 'reorder' => 12, 'pack' => ['Carton of 12', 12, 20400]],
                        ['label' => 'Pack 315ml', 'price' => 500, 'reorder' => 24, 'pack' => ['Carton of 24', 24, 11000]],
                        ['label' => 'Pack 125ml', 'price' => 200, 'reorder' => 27, 'pack' => ['Carton of 27', 27, 5000]],
                    ]],
                    ['name' => 'Hollandia Evap & Juice', 'variants' => [
                        ['label' => 'Pack 1L', 'price' => 1700, 'default' => true, 'reorder' => 12, 'pack' => ['Carton of 12', 12, 19200]],
                        ['label' => 'Pack 500ml', 'price' => 900, 'reorder' => 12, 'pack' => ['Carton of 12', 12, 10200]],
                    ]],
                    ['name' => 'Five Alive', 'brand' => 'Coca-Cola', 'variants' => [
                        ['label' => 'Pack 1L', 'price' => 1600, 'default' => true, 'reorder' => 12, 'pack' => ['Carton of 12', 12, 18000]],
                        ['label' => 'PET 350ml', 'price' => 450, 'reorder' => 24, 'pack' => ['Carton of 24', 24, 10000]],
                    ]],
                    ['name' => 'Exotic Juice', 'brand' => 'Chi Limited', 'unit' => 'Pack 1L', 'price' => 1500, 'pack' => ['Carton of 12', 12, 17000]],
                    ['name' => 'Capri-Sun', 'brand' => 'Chi Limited', 'unit' => 'Pouch 200ml', 'price' => 300, 'reorder' => 40, 'pack' => ['Carton of 40', 40, 11000]],
                    ['name' => 'Happy Hour Juice', 'brand' => 'Rite Foods', 'unit' => 'Pack 1L', 'price' => 1200, 'pack' => ['Carton of 12', 12, 13500]],
                ],
            ],
            'Water' => [
                'brand' => 'Eva', 'color' => '#0284c7', 'weight' => 1500, 'reorder' => 12, 'tax_exempt' => true,
                'products' => [
                    ['name' => 'Eva Table Water', 'brand' => 'Eva', 'featured' => true, 'variants' => [
                        ['label' => 'Bottle 75cl', 'price' => 250, 'default' => true, 'reorder' => 12, 'pack' => ['Pack of 12', 12, 2600]],
                        ['label' => 'Bottle 1.5L', 'price' => 400, 'reorder' => 12, 'pack' => ['Pack of 9', 9, 3300]],
                        ['label' => 'Bottle 35cl', 'price' => 150, 'reorder' => 24, 'pack' => ['Pack of 24', 24, 3000]],
                    ]],
                    ['name' => 'CWAY Water', 'brand' => 'CWAY', 'variants' => [
                        ['label' => 'Bottle 50cl', 'price' => 150, 'default' => true, 'reorder' => 24, 'pack' => ['Pack of 24', 24, 3000]],
                        ['label' => 'Bottle 1.5L', 'price' => 400, 'reorder' => 12, 'pack' => ['Pack of 9', 9, 3300]],
                    ]],
                    ['name' => 'Aquafina Water', 'brand' => 'Seven-Up Bottling', 'variants' => [
                        ['label' => 'Bottle 50cl', 'price' => 200, 'default' => true, 'reorder' => 24, 'pack' => ['Pack of 12', 12, 2200]],
                        ['label' => 'Bottle 75cl', 'price' => 300, 'reorder' => 12, 'pack' => ['Pack of 12', 12, 3300]],
                    ]],
                    ['name' => 'La Sien Sachet Water', 'brand' => 'La Sien', 'unit' => 'Bag of 20 sachets', 'price' => 400, 'reorder' => 20, 'pack' => ['Dozen bags', 12, 4600]],
                    ['name' => 'Nestlé Pure Life', 'brand' => 'Nestlé', 'unit' => 'Bottle 50cl', 'price' => 200, 'reorder' => 24, 'pack' => ['Pack of 12', 12, 2200]],
                ],
            ],
            'Soft Drinks' => [
                'brand' => 'Coca-Cola', 'color' => '#dc2626', 'weight' => 500, 'reorder' => 24,
                'products' => [
                    ['name' => 'Coca-Cola', 'featured' => true, 'variants' => [
                        ['label' => 'Bottle 50cl PET', 'price' => 400, 'default' => true, 'reorder' => 24, 'pack' => ['Pack of 12', 12, 4400]],
                        ['label' => 'Bottle 35cl RGB', 'price' => 250, 'reorder' => 24, 'pack' => ['Crate of 24', 24, 5600]],
                        ['label' => 'Can 33cl', 'price' => 350, 'reorder' => 24, 'pack' => ['Pack of 24', 24, 7800]],
                    ]],
                    ['name' => 'Fanta Orange', 'variants' => [
                        ['label' => 'Bottle 50cl PET', 'price' => 400, 'default' => true, 'reorder' => 24, 'pack' => ['Pack of 12', 12, 4400]],
                        ['label' => 'Bottle 35cl RGB', 'price' => 250, 'reorder' => 24, 'pack' => ['Crate of 24', 24, 5600]],
                    ]],
                    ['name' => 'Sprite', 'variants' => [
                        ['label' => 'Bottle 50cl PET', 'price' => 400, 'default' => true, 'reorder' => 24, 'pack' => ['Pack of 12', 12, 4400]],
                        ['label' => 'Can 33cl', 'price' => 350, 'reorder' => 24, 'pack' => ['Pack of 24', 24, 7800]],
                    ]],
                    ['name' => 'Pepsi', 'brand' => 'Seven-Up Bottling', 'variants' => [
                        ['label' => 'Bottle 50cl PET', 'price' => 400, 'default' => true, 'reorder' => 24, 'pack' => ['Pack of 12', 12, 4400]],
                        ['label' => 'Bottle 35cl RGB', 'price' => 250, 'reorder' => 24, 'pack' => ['Crate of 24', 24, 5600]],
                    ]],
                    ['name' => 'Mirinda', 'brand' => 'Seven-Up Bottling', 'unit' => 'Bottle 50cl PET', 'price' => 400, 'pack' => ['Pack of 12', 12, 4400]],
                    ['name' => '7Up', 'brand' => 'Seven-Up Bottling', 'unit' => 'Bottle 50cl PET', 'price' => 400, 'pack' => ['Pack of 12', 12, 4400]],
                    ['name' => 'Schweppes Chapman', 'unit' => 'Can 33cl', 'price' => 450, 'reorder' => 24, 'pack' => ['Pack of 24', 24, 10000]],
                    ['name' => 'Bigi Cola', 'brand' => 'Rite Foods', 'unit' => 'Bottle 60cl PET', 'price' => 300, 'pack' => ['Pack of 12', 12, 3400]],
                    ['name' => 'Limca', 'brand' => 'Seven-Up Bottling', 'unit' => 'Bottle 50cl PET', 'price' => 380, 'pack' => ['Pack of 12', 12, 4200]],
                ],
            ],
            'Energy Drinks' => [
                'brand' => 'Rite Foods', 'color' => '#65a30d', 'weight' => 350, 'reorder' => 24,
                'products' => [
                    ['name' => 'Fearless Energy Drink', 'brand' => 'Rite Foods', 'featured' => true, 'unit' => 'Can 40cl', 'price' => 400, 'pack' => ['Pack of 24', 24, 8800]],
                    ['name' => 'Predator Energy Drink', 'brand' => 'Seven-Up Bottling', 'unit' => 'Can 40cl', 'price' => 300, 'pack' => ['Pack of 24', 24, 6600]],
                    ['name' => 'Monster Energy', 'brand' => 'Monster', 'unit' => 'Can 44cl', 'price' => 1200, 'pack' => ['Pack of 12', 12, 13800]],
                    ['name' => 'Red Bull', 'brand' => 'Red Bull', 'unit' => 'Can 25cl', 'price' => 1500, 'sale' => 1350, 'pack' => ['Pack of 24', 24, 33000]],
                ],
            ],
            'Wine' => [
                'brand' => 'Four Cousins', 'color' => '#9f1239', 'weight' => 1300, 'reorder' => 6, 'height' => 320,
                'products' => [
                    ['name' => 'Four Cousins Sweet Red', 'brand' => 'Four Cousins', 'featured' => true, 'unit' => 'Bottle 75cl', 'price' => 6000, 'pack' => ['Case of 6', 6, 34000]],
                    ['name' => 'Four Cousins Sweet White', 'brand' => 'Four Cousins', 'unit' => 'Bottle 75cl', 'price' => 6000, 'pack' => ['Case of 6', 6, 34000]],
                    ['name' => 'Four Cousins Rosé', 'brand' => 'Four Cousins', 'unit' => 'Bottle 75cl', 'price' => 6000, 'pack' => ['Case of 6', 6, 34000]],
                    ['name' => 'Carlo Rossi Red', 'brand' => 'Carlo Rossi', 'unit' => 'Bottle 75cl', 'price' => 8000, 'pack' => ['Case of 6', 6, 46000]],
                    ['name' => 'Baron Romero', 'brand' => 'Baron Romero', 'unit' => 'Bottle 75cl', 'price' => 4500, 'pack' => ['Case of 6', 6, 25000]],
                    ['name' => 'Drostdy-Hof Extra Light', 'brand' => 'Drostdy-Hof', 'unit' => 'Bottle 75cl', 'price' => 7000, 'pack' => ['Case of 6', 6, 40000]],
                    ['name' => 'JP Chenet Sparkling', 'brand' => 'JP Chenet', 'unit' => 'Bottle 75cl', 'price' => 9000, 'pack' => ['Case of 6', 6, 52000]],
                    ['name' => 'Casillero del Diablo Cabernet', 'brand' => 'Casillero del Diablo', 'unit' => 'Bottle 75cl', 'price' => 12000, 'pack' => ['Case of 6', 6, 70000]],
                ],
            ],
            'Spirits' => [
                'brand' => 'Diageo', 'color' => '#78350f', 'weight' => 1200, 'reorder' => 6, 'height' => 300,
                'products' => [
                    ['name' => 'Jameson Irish Whiskey', 'brand' => 'Jameson', 'featured' => true, 'unit' => 'Bottle 75cl', 'price' => 18000, 'pack' => ['Case of 12', 12, 205000]],
                    ['name' => 'Hennessy VS Cognac', 'brand' => 'Hennessy', 'featured' => true, 'unit' => 'Bottle 70cl', 'price' => 75000, 'pack' => ['Case of 6', 6, 435000]],
                    ['name' => 'Martell VS', 'brand' => 'Martell', 'unit' => 'Bottle 70cl', 'price' => 60000, 'pack' => ['Case of 6', 6, 348000]],
                    ['name' => 'Glenfiddich 12yr', 'brand' => 'Glenfiddich', 'unit' => 'Bottle 70cl', 'price' => 45000, 'pack' => ['Case of 6', 6, 260000]],
                    ['name' => 'Smirnoff Vodka', 'brand' => 'Smirnoff', 'unit' => 'Bottle 75cl', 'price' => 8000, 'pack' => ['Case of 12', 12, 92000]],
                    ['name' => 'Campari Bitters', 'brand' => 'Campari', 'unit' => 'Bottle 75cl', 'price' => 12000, 'pack' => ['Case of 12', 12, 138000]],
                    ['name' => "Gordon's London Dry Gin", 'brand' => "Gordon's", 'unit' => 'Bottle 75cl', 'price' => 9000, 'pack' => ['Case of 12', 12, 103000]],
                    ['name' => 'Chelsea Dry Gin', 'brand' => 'Chelsea', 'unit' => 'Bottle 75cl', 'price' => 4500, 'pack' => ['Case of 12', 12, 51000]],
                ],
            ],
            'Biscuits' => [
                'brand' => 'Beloxxi', 'color' => '#c2410c', 'weight' => 300, 'reorder' => 20, 'tax_exempt' => true,
                'products' => [
                    ['name' => 'Beloxxi Cream Crackers', 'brand' => 'Beloxxi', 'featured' => true, 'unit' => 'Pack', 'price' => 1200, 'pack' => ['Carton of 20', 20, 22000]],
                    ['name' => 'Parle-G Glucose Biscuits', 'brand' => 'Parle', 'unit' => 'Pack', 'price' => 300, 'reorder' => 40, 'pack' => ['Carton of 60', 60, 16000]],
                    ['name' => 'Cabin Biscuits', 'brand' => 'Yale Foods', 'unit' => 'Pack', 'price' => 250, 'reorder' => 40, 'pack' => ['Carton of 40', 40, 9000]],
                    ['name' => "McVitie's Digestive", 'brand' => "McVitie's", 'unit' => 'Pack', 'price' => 2000, 'reorder' => 12, 'pack' => ['Carton of 12', 12, 22000]],
                    ['name' => 'Yale Shortcake', 'brand' => 'Yale Foods', 'unit' => 'Pack', 'price' => 350, 'reorder' => 30, 'pack' => ['Carton of 30', 30, 9500]],
                    ['name' => 'Pure Butter Cookies', 'brand' => 'Yale Foods', 'unit' => 'Pack', 'price' => 500, 'reorder' => 24, 'pack' => ['Carton of 24', 24, 11000]],
                ],
            ],
            'Noodles' => [
                'brand' => 'Dufil Prima', 'color' => '#eab308', 'weight' => 100, 'reorder' => 40, 'tax_exempt' => true,
                'products' => [
                    ['name' => 'Indomie Chicken', 'brand' => 'Dufil Prima', 'featured' => true, 'variants' => [
                        ['label' => 'Pack 70g', 'price' => 200, 'default' => true, 'reorder' => 40, 'pack' => ['Carton of 40', 40, 7600]],
                        ['label' => 'Super Pack 120g', 'price' => 400, 'reorder' => 40, 'pack' => ['Carton of 40', 40, 15000]],
                    ]],
                    ['name' => 'Indomie Onion Chicken', 'brand' => 'Dufil Prima', 'unit' => 'Pack 70g', 'price' => 200, 'reorder' => 40, 'pack' => ['Carton of 40', 40, 7600]],
                    ['name' => 'Indomie Pepper Chicken', 'brand' => 'Dufil Prima', 'unit' => 'Pack 70g', 'price' => 220, 'reorder' => 40, 'pack' => ['Carton of 40', 40, 8200]],
                    ['name' => 'Golden Penny Noodles', 'brand' => 'Flour Mills', 'unit' => 'Pack 70g', 'price' => 180, 'reorder' => 40, 'pack' => ['Carton of 40', 40, 6800]],
                    ['name' => 'Mimee Noodles Chicken', 'brand' => 'Mimee', 'unit' => 'Pack 70g', 'price' => 170, 'reorder' => 40, 'pack' => ['Carton of 40', 40, 6400]],
                ],
            ],
            'Snacks' => [
                'brand' => 'UAC Foods', 'color' => '#d97706', 'weight' => 60, 'reorder' => 24,
                'products' => [
                    ['name' => 'Gala Sausage Roll', 'brand' => 'UAC Foods', 'featured' => true, 'unit' => 'Roll', 'price' => 200, 'reorder' => 30, 'pack' => ['Pack of 30', 30, 5400]],
                    ['name' => 'Beefie Beef Roll', 'brand' => 'UAC Foods', 'unit' => 'Roll', 'price' => 200, 'reorder' => 30, 'pack' => ['Pack of 30', 30, 5400]],
                    ['name' => 'Plantain Chips', 'brand' => 'Olu Olu', 'unit' => 'Pack 100g', 'price' => 500, 'reorder' => 20, 'pack' => ['Carton of 20', 20, 9000]],
                    ['name' => 'Popcorn', 'brand' => 'House of Treats', 'unit' => 'Pack 90g', 'price' => 300, 'reorder' => 20, 'pack' => ['Carton of 20', 20, 5400]],
                    ['name' => 'Chin Chin', 'brand' => 'House of Treats', 'unit' => 'Pack 150g', 'price' => 1000, 'reorder' => 15, 'pack' => ['Carton of 15', 15, 13500]],
                ],
            ],
            'Dairy' => [
                'brand' => 'FrieslandCampina', 'color' => '#0891b2', 'weight' => 400, 'reorder' => 12, 'tax_exempt' => true,
                'products' => [
                    ['name' => 'Peak Milk Powder', 'brand' => 'FrieslandCampina', 'featured' => true, 'variants' => [
                        ['label' => 'Tin 400g', 'price' => 2600, 'default' => true, 'reorder' => 12, 'pack' => ['Carton of 12', 12, 30000]],
                        ['label' => 'Refill 350g', 'price' => 2200, 'reorder' => 12, 'pack' => ['Carton of 12', 12, 25000]],
                        ['label' => 'Sachet', 'price' => 200, 'reorder' => 40, 'pack' => ['Carton of 120', 120, 22000]],
                    ]],
                    ['name' => 'Peak Evaporated Milk', 'brand' => 'FrieslandCampina', 'unit' => 'Tin 170g', 'price' => 500, 'reorder' => 24, 'pack' => ['Carton of 48', 48, 22000]],
                    ['name' => 'Three Crowns Milk', 'brand' => 'FrieslandCampina', 'unit' => 'Tin 380g', 'price' => 2400, 'pack' => ['Carton of 12', 12, 27500]],
                    ['name' => 'Cowbell Milk', 'brand' => 'Promasidor', 'unit' => 'Sachet 40g', 'price' => 200, 'reorder' => 40, 'pack' => ['Carton of 120', 120, 22000]],
                    ['name' => 'Dano Milk', 'brand' => 'Arla', 'unit' => 'Refill 350g', 'price' => 2300, 'pack' => ['Carton of 12', 12, 26000]],
                    ['name' => 'Hollandia Yoghurt', 'brand' => 'Chi Limited', 'unit' => 'Bottle 1L', 'price' => 1800, 'reorder' => 12, 'pack' => ['Carton of 12', 12, 20400]],
                ],
            ],
        ];
    }
}
