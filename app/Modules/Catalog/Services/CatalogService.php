<?php

namespace App\Modules\Catalog\Services;

use App\Admin\Models\Admin;
use App\Admin\Notifications\AdminAlertNotification;
use App\Admin\Notifications\LowStockNotification;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Exceptions\InsufficientStock;
use App\Modules\Inventory\Services\InventoryLedger;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Catalog write operations. Controllers call straight into here (simple flow).
 * Every product owns at least one variant (the default), which carries
 * pricing and stock. Other modules mutate stock via the variant methods.
 */
class CatalogService
{
    /**
     * Create a product and its default variant.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data): Product {
            $product = Product::create([
                ...$this->productAttributes($data),
                'slug' => $this->uniqueSlug($data['name']),
            ]);

            $product->variants()->create([
                'sku' => $data['sku'],
                'name' => 'Default',
                'is_default' => true,
                'retail_price' => Money::fromNaira($data['retail_price']),
                'sale_price' => $this->nairaOrNull($data['sale_price'] ?? null),
                'wholesale_price' => $this->nairaOrNull($data['wholesale_price'] ?? null),
                'stock' => $data['stock'] ?? 0,
            ]);

            $this->syncOptions($product, $data['options'] ?? []);
            $this->syncSpecifications($product, $data['specifications'] ?? []);
            $this->syncVariants($product, $data['variants'] ?? []);
            $this->syncQuantityDiscounts($product, $data['quantity_discounts'] ?? []);

            return $product;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            $attrs = $this->productAttributes($data);

            if ($data['name'] !== $product->name) {
                $attrs['slug'] = $this->uniqueSlug($data['name'], $product->id);
            }

            $product->update($attrs);

            $variant = $product->primaryVariant() ?? $product->variants()->make(['is_default' => true]);
            $variant->fill([
                'sku' => $data['sku'],
                'retail_price' => Money::fromNaira($data['retail_price']),
                'sale_price' => $this->nairaOrNull($data['sale_price'] ?? null),
                'wholesale_price' => $this->nairaOrNull($data['wholesale_price'] ?? null),
                'stock' => $data['stock'] ?? 0,
            ]);
            $variant->is_default = true;
            $variant->product_id = $product->id;
            $variant->save();

            $this->syncOptions($product, $data['options'] ?? []);
            $this->syncSpecifications($product, $data['specifications'] ?? []);
            $this->syncVariants($product, $data['variants'] ?? [], keepVariantId: $variant->id);
            $this->syncQuantityDiscounts($product, $data['quantity_discounts'] ?? []);

            return $product;
        });
    }

    /**
     * Build the persistable product attributes from validated form data.
     * Slug is added separately by the caller.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function productAttributes(array $data): array
    {
        return [
            'category_id' => $data['category_id'] ?? null,
            'brand_id' => $data['brand_id'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'condition' => $data['condition'] ?? 'new',
            'weight_g' => $data['weight_g'] ?? null,
            'length_mm' => $data['length_mm'] ?? null,
            'width_mm' => $data['width_mm'] ?? null,
            'height_mm' => $data['height_mm'] ?? null,
            // Dollars in the form → integer cents in the column.
            'cost_price_usd' => isset($data['cost_price_usd']) && $data['cost_price_usd'] !== ''
                ? (int) round(((float) $data['cost_price_usd']) * 100)
                : null,
            'status' => $data['status'] ?? 'draft',
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'is_vat_inclusive' => (bool) ($data['is_vat_inclusive'] ?? true),
            'is_tax_exempt' => (bool) ($data['is_tax_exempt'] ?? false),
            'vat_rate' => $data['vat_rate'] ?? config('gobuy.vat_rate'),
        ];
    }

    /**
     * Replace the product's option axes and their values from the submitted
     * rows. Each row is {name, values} where values is comma-separated.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncOptions(Product $product, array $rows): void
    {
        $product->options()->delete(); // cascade removes values + pivot links

        $sort = 0;
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $values = trim((string) ($row['values'] ?? ''));
            if ($name === '' || $values === '') {
                continue;
            }

            $option = $product->options()->create(['name' => $name, 'sort_order' => $sort++]);

            $valueSort = 0;
            foreach ($this->splitList($values) as $value) {
                $option->values()->create(['value' => $value, 'sort_order' => $valueSort++]);
            }
        }
    }

    /**
     * Replace the product's specifications with the submitted key/value rows.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncSpecifications(Product $product, array $rows): void
    {
        $product->specifications()->delete();

        $sort = 0;
        foreach ($rows as $row) {
            $label = trim((string) ($row['label'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));
            if ($label === '' || $value === '') {
                continue;
            }

            $product->specifications()->create([
                'label' => $label,
                'value' => $value,
                'sort_order' => $sort++,
            ]);
        }
    }

    /**
     * Create/update additional (non-default) variants and remove any that
     * were dropped from the form. Rows without a SKU are ignored.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncVariants(Product $product, array $rows, ?int $keepVariantId = null): void
    {
        $keptIds = $keepVariantId !== null ? [$keepVariantId] : [];

        foreach ($rows as $row) {
            $sku = trim((string) ($row['sku'] ?? ''));
            if ($sku === '') {
                continue;
            }

            $attributes = [
                'name' => $row['name'] ?? 'Variant',
                'sku' => $sku,
                'retail_price' => Money::fromNaira($row['retail_price'] ?? 0),
                'sale_price' => $this->nairaOrNull($row['sale_price'] ?? null),
                'wholesale_price' => $this->nairaOrNull($row['wholesale_price'] ?? null),
                'stock' => (int) ($row['stock'] ?? 0),
                'is_default' => false,
            ];

            $existingId = isset($row['id']) ? (int) $row['id'] : null;
            $variant = $existingId
                ? $product->variants()->whereKey($existingId)->first()
                : null;

            if ($variant) {
                $variant->update($attributes);
            } else {
                $variant = $product->variants()->create($attributes);
            }

            $this->linkVariantOptionValues($product, $variant, (string) ($row['options'] ?? ''));

            $keptIds[] = $variant->id;
        }

        $product->variants()
            ->where('is_default', false)
            ->whereNotIn('id', $keptIds)
            ->each(fn (ProductVariant $variant) => $variant->delete());
    }

    /**
     * Link a variant to the product's option values matching the given
     * comma-separated value list (e.g. "Red, Large"). Values are matched by
     * name against the product's defined option values.
     */
    private function linkVariantOptionValues(Product $product, ProductVariant $variant, string $values): void
    {
        $names = $this->splitList($values);

        if ($names === []) {
            $variant->optionValues()->sync([]);

            return;
        }

        $lookup = $product->options()
            ->with('values')
            ->get()
            ->flatMap(fn ($option) => $option->values)
            ->keyBy(fn ($value) => mb_strtolower($value->value));

        $ids = [];
        foreach ($names as $name) {
            $match = $lookup->get(mb_strtolower($name));
            if ($match) {
                $ids[] = $match->id;
            }
        }

        $variant->optionValues()->sync($ids);
    }

    /**
     * Split a comma-separated list into trimmed, non-empty, de-duplicated values.
     *
     * @return list<string>
     */
    private function splitList(string $list): array
    {
        return collect(explode(',', $list))
            ->map(fn (string $item) => trim($item))
            ->filter(fn (string $item) => $item !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Replace the product's wholesale quantity tiers with the submitted rows.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncQuantityDiscounts(Product $product, array $rows): void
    {
        $product->quantityDiscounts()->delete();

        foreach ($rows as $row) {
            $minQty = (int) ($row['min_qty'] ?? 0);
            $unitPrice = $row['unit_price'] ?? null;

            if ($minQty < 2 || $unitPrice === null || $unitPrice === '') {
                continue;
            }

            $product->quantityDiscounts()->create([
                'min_qty' => $minQty,
                'unit_price' => Money::fromNaira($unitPrice),
            ]);
        }
    }

    /**
     * Convert a Naira form value to Money, treating blank/null as null.
     */
    private function nairaOrNull(int|float|string|null $value): ?Money
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Money::fromNaira($value);
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    /**
     * Atomically reduce variant stock on a confirmed sale. Records an inventory
     * movement (the audit trail) via the ledger, then fires low/out-of-stock
     * alerts. $reference (the order) is stamped onto the movement.
     *
     * @throws InsufficientStock when overselling.
     */
    public function decrementStock(ProductVariant $variant, int $quantity, ?Model $reference = null): void
    {
        $wasAboveThreshold = (int) $variant->fresh()->stock > $variant->low_stock_threshold;

        app(InventoryLedger::class)->recordSale($variant, $quantity, $reference);

        $fresh = $variant->fresh();
        $reachedZero = $fresh->stock <= 0;
        // Low-stock alert only when the sale pushes the variant to/under its
        // threshold. Sell-out supersedes it (louder, distinct alert below).
        $crossedLowStock = ! $reachedZero && $wasAboveThreshold && $fresh->isLowStock();

        if ($reachedZero) {
            $this->alertOutOfStock($fresh);
        } elseif ($crossedLowStock) {
            $this->alertLowStock($fresh);
        }
    }

    private function alertOutOfStock(ProductVariant $variant): void
    {
        $variant->loadMissing('product');
        $name = $variant->product?->name ?? $variant->sku;

        Notification::send(
            Admin::withAbility('manage_products'),
            new AdminAlertNotification(
                'Out of stock',
                "{$name} (SKU {$variant->sku}) has just sold out. Restock to keep it on sale.",
                'important',
                $variant->product ? route('admin.products.edit', $variant->product) : null,
                'fa-box-open',
            ),
        );
    }

    private function alertLowStock(ProductVariant $variant): void
    {
        $variant->loadMissing('product');

        $admins = Admin::where('is_active', true)->get()
            ->filter(fn (Admin $admin) => $admin->can('manage_products'));

        Notification::send($admins, new LowStockNotification($variant));
    }

    /**
     * Return stock to a variant (e.g. after a refund or accepted return). Records
     * a Return movement; the ledger updates stock and flushes back-in-stock
     * waiters on a 0 → positive cross.
     */
    public function restock(ProductVariant $variant, int $quantity, ?Model $reference = null): void
    {
        app(InventoryLedger::class)->recordReturn($variant, $quantity, $reference);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Product::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-".++$i;
        }

        return $slug;
    }
}
