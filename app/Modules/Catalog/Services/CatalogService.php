<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Catalog write operations. Controllers call straight into here (simple flow).
 *
 * Also exposes stock mutation for other modules (Order) to call — controlled
 * cross-module communication via a public service method.
 */
class CatalogService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        $data['slug'] = $this->uniqueSlug($data['name']);

        return Product::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        if (isset($data['name']) && $data['name'] !== $product->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $product->id);
        }

        $product->update($data);

        return $product;
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    /**
     * Decrement stock atomically. Called by the Order module at checkout.
     *
     * @throws RuntimeException when stock is insufficient.
     */
    public function decrementStock(Product $product, int $quantity): void
    {
        DB::transaction(function () use ($product, $quantity): void {
            $fresh = Product::query()->lockForUpdate()->findOrFail($product->id);

            if ($fresh->stock < $quantity) {
                Log::warning('Stock decrement failed: insufficient stock', [
                    'product_id' => $fresh->id,
                    'requested' => $quantity,
                    'available' => $fresh->stock,
                ]);

                throw new RuntimeException("Insufficient stock for product {$fresh->id}.");
            }

            $fresh->decrement('stock', $quantity);
        });
    }

    /**
     * Return stock to inventory (e.g. after a refund).
     */
    public function restock(Product $product, int $quantity): void
    {
        DB::transaction(function () use ($product, $quantity): void {
            $fresh = Product::query()->lockForUpdate()->findOrFail($product->id);
            $fresh->increment('stock', $quantity);
        });
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
