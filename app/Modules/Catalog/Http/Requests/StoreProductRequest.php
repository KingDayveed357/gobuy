<?php

namespace App\Modules\Catalog\Http\Requests;

use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', Rule::exists('categories', 'id')],
            'brand_id' => ['nullable', Rule::exists('brands', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'condition' => ['required', Rule::in(['new', 'used', 'refurbished'])],
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
            'is_featured' => ['boolean'],

            // Logistics + margin attributes.
            'weight_g' => ['nullable', 'integer', 'min:0'],
            'length_mm' => ['nullable', 'integer', 'min:0'],
            'width_mm' => ['nullable', 'integer', 'min:0'],
            'height_mm' => ['nullable', 'integer', 'min:0'],
            'cost_price_usd' => ['nullable', 'numeric', 'min:0'],

            // Specifications (relational key/value).
            'specifications' => ['nullable', 'array'],
            'specifications.*.label' => ['required_with:specifications.*.value', 'nullable', 'string', 'max:120'],
            'specifications.*.value' => ['required_with:specifications.*.label', 'nullable', 'string', 'max:255'],

            // Option axes (relational): name + comma-separated values.
            'options' => ['nullable', 'array'],
            'options.*.name' => ['required_with:options.*.values', 'nullable', 'string', 'max:60'],
            'options.*.values' => ['required_with:options.*.name', 'nullable', 'string', 'max:255'],

            // Default variant (always present).
            'sku' => ['required', 'string', 'max:64'],
            'retail_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lt:retail_price'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],

            // Tax.
            'is_vat_inclusive' => ['boolean'],
            'is_tax_exempt' => ['boolean'],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],

            // Gallery images.
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['image', 'max:5120'],
            'remove_media' => ['nullable', 'array'],
            'remove_media.*' => ['integer'],
            // Tokens for images uploaded asynchronously via the drag-and-drop uploader.
            'uploaded_tokens' => ['nullable', 'array', 'max:8'],
            'uploaded_tokens.*' => ['string', 'max:80'],

            // Additional variants.
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.name' => ['required_with:variants.*.sku', 'nullable', 'string', 'max:120'],
            'variants.*.sku' => ['required_with:variants.*.name', 'nullable', 'string', 'max:64'],
            'variants.*.retail_price' => ['required_with:variants.*.sku', 'nullable', 'numeric', 'min:0'],
            'variants.*.sale_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock' => ['required_with:variants.*.sku', 'nullable', 'integer', 'min:0'],
            'variants.*.options' => ['nullable', 'string', 'max:255'], // e.g. "Red, Large"

            // Wholesale quantity tiers.
            'quantity_discounts' => ['nullable', 'array'],
            'quantity_discounts.*.min_qty' => ['required_with:quantity_discounts.*.unit_price', 'nullable', 'integer', 'min:2'],
            'quantity_discounts.*.unit_price' => ['required_with:quantity_discounts.*.min_qty', 'nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateSkuUniqueness($validator);
        });
    }

    /**
     * SKUs must be unique across the default variant, all additional variants,
     * and every other product's variants in the database.
     */
    protected function validateSkuUniqueness(Validator $validator): void
    {
        $submitted = [];

        $primary = trim((string) $this->input('sku'));
        if ($primary !== '') {
            $submitted['sku'] = $primary;
        }

        foreach ((array) $this->input('variants', []) as $index => $variant) {
            $sku = trim((string) ($variant['sku'] ?? ''));
            if ($sku !== '') {
                $submitted["variants.$index.sku"] = $sku;
            }
        }

        // Duplicates within the submission.
        $seen = [];
        foreach ($submitted as $field => $sku) {
            $key = mb_strtolower($sku);
            if (isset($seen[$key])) {
                $validator->errors()->add($field, "The SKU \"$sku\" is used more than once on this product.");
            }
            $seen[$key] = true;
        }

        // Collisions with other products in the database.
        foreach ($submitted as $field => $sku) {
            if ($this->skuExists($sku)) {
                $validator->errors()->add($field, "The SKU \"$sku\" is already in use.");
            }
        }
    }

    protected function skuExists(string $sku): bool
    {
        return ProductVariant::query()
            ->whereRaw('LOWER(sku) = ?', [mb_strtolower($sku)])
            ->when($this->ignoredProductId(), fn ($q, $id) => $q->where('product_id', '!=', $id))
            ->exists();
    }

    protected function ignoredProductId(): ?int
    {
        return null;
    }
}
