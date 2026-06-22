<?php

namespace App\Modules\Pricing\Services;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Pricing\Concerns\RecordsPriceHistory;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Applies a single price adjustment across many variants at once — a percentage
 * or flat change to a chosen price field, optionally scoped to a category (and
 * its descendants). Every change flows through the variant model so the
 * {@see RecordsPriceHistory} trait audits it.
 */
class BulkPricingService
{
    public const FIELDS = ['retail_price', 'sale_price', 'wholesale_price'];

    /**
     * Variants the adjustment would touch, with product + category eager-loaded.
     *
     * @param  array{category_id?: int|null}  $data
     * @return Collection<int, ProductVariant>
     */
    public function targetVariants(array $data): Collection
    {
        return ProductVariant::query()
            ->with('product:id,name,category_id')
            ->when(! empty($data['category_id']), function ($query) use ($data) {
                $ids = $this->categoryWithDescendants((int) $data['category_id']);
                $query->whereHas('product', fn ($q) => $q->whereIn('category_id', $ids));
            })
            ->get();
    }

    /**
     * Compute the new value for a variant's field without persisting.
     *
     * @param  array{field: string, direction: string, method: string, value: float|int|string}  $data
     */
    public function newValueFor(ProductVariant $variant, array $data): ?Money
    {
        $current = $variant->{$data['field']};
        if (! $current instanceof Money) {
            return null; // e.g. a null sale_price — nothing to adjust
        }

        $delta = $data['method'] === 'percentage'
            ? $current->percentage((float) $data['value'])
            : Money::fromNaira($data['value']);

        $new = $data['direction'] === 'increase' ? $current->plus($delta) : $current->minus($delta);

        return $new->kobo < 0 ? Money::zero() : $new;
    }

    /**
     * A non-persisting preview of the change.
     *
     * @param  array{field: string, direction: string, method: string, value: mixed, category_id?: int|null}  $data
     * @return array{rows: Collection<int, array<string, mixed>>, count: int}
     */
    public function preview(array $data): array
    {
        $rows = $this->targetVariants($data)
            ->map(function (ProductVariant $variant) use ($data) {
                $old = $variant->{$data['field']};
                $new = $this->newValueFor($variant, $data);

                return [
                    'variant' => $variant,
                    'product' => $variant->product?->name,
                    'label' => $variant->label(),
                    'old' => $old,
                    'new' => $new,
                    'changed' => $new !== null && $old instanceof Money && ! $new->equals($old),
                ];
            })
            ->filter(fn ($row) => $row['changed'])
            ->values();

        return ['rows' => $rows, 'count' => $rows->count()];
    }

    /**
     * Apply the adjustment and return the number of variants updated.
     *
     * @param  array{field: string, direction: string, method: string, value: mixed, category_id?: int|null, reason?: string|null}  $data
     */
    public function apply(array $data): int
    {
        $variants = $this->targetVariants($data);
        $updated = 0;

        DB::transaction(function () use ($variants, $data, &$updated): void {
            foreach ($variants as $variant) {
                $new = $this->newValueFor($variant, $data);
                $old = $variant->{$data['field']};

                if ($new === null || ! $old instanceof Money || $new->equals($old)) {
                    continue;
                }

                $variant->priceChangeReason = $data['reason'] ?? 'Bulk price adjustment';
                $variant->{$data['field']} = $new;
                $variant->save();
                $updated++;
            }
        });

        return $updated;
    }

    /**
     * @return array<int, int>
     */
    private function categoryWithDescendants(int $categoryId): array
    {
        $ids = [$categoryId];
        $frontier = [$categoryId];

        while ($frontier !== []) {
            $children = Category::whereIn('parent_id', $frontier)->pluck('id')->all();
            $frontier = array_diff($children, $ids);
            $ids = array_merge($ids, $frontier);
        }

        return $ids;
    }
}
