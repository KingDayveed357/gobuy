<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\ProductVariant;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Search-as-you-type backend for the admin Product Picker combobox.
 *
 * Returns rich, self-contained variant rows (thumbnail, brand, category, stock,
 * packaging, retail/wholesale price) so operational screens — packaging, the
 * walk-in till, the register — can locate a product in a keystroke without a
 * full page of Livewire round-trips. The response is intentionally small and
 * cache-friendly (see {@see \App\View\Components\...}) so the picker stays
 * responsive on poor connections.
 */
class ProductSearchController extends Controller
{
    private const MAX_RESULTS = 12;

    public function search(Request $request): JsonResponse
    {
        $term = trim($request->string('q')->toString());

        if (mb_strlen($term) < 2) {
            return response()->json(['data' => []]);
        }

        $inStockOnly = $request->boolean('in_stock');

        $variants = ProductVariant::query()
            ->when($inStockOnly, fn ($q) => $q->where('stock', '>', 0))
            ->where(function ($q) use ($term): void {
                $q->where('sku', 'like', "%{$term}%")
                    ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('product.brand', fn ($b) => $b->where('name', 'like', "%{$term}%"));
            })
            ->with([
                'product:id,name,category_id,brand_id',
                'product.media',
                'product.brand:id,name',
                'product.category:id,name',
            ])
            ->withCount('packagingUnits')
            ->orderByRaw('stock <= 0')          // in-stock first
            ->orderByRaw('sku = ? DESC', [$term]) // exact SKU hit floats up
            ->limit(self::MAX_RESULTS)
            ->get();

        return response()->json([
            'data' => $variants->map(fn (ProductVariant $variant) => $this->transform($variant))->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(ProductVariant $variant): array
    {
        $product = $variant->product;
        $wholesale = $variant->wholesale_price;

        return [
            'id' => $variant->id,
            'name' => $product?->name ?? 'Product',
            'variant' => $variant->is_default ? null : $variant->label(),
            'sku' => $variant->sku,
            'brand' => $product?->brand?->name,
            'category' => $product?->category?->name,
            'stock' => (int) $variant->stock,
            'low_stock' => $variant->isLowStock(),
            'packaging' => (int) ($variant->packaging_units_count ?? 0),
            'retail' => $variant->retail_price?->format() ?? Money::zero()->format(),
            'wholesale' => $wholesale instanceof Money && ! $wholesale->isZero() ? $wholesale->format() : null,
            'thumb' => $product?->thumbUrl() ?? asset('theme/img/placeholder.svg'),
        ];
    }
}
