<?php

namespace App\Modules\Catalog\Http\Requests;

use App\Modules\Catalog\Models\Product;

class UpdateProductRequest extends StoreProductRequest
{
    protected function ignoredProductId(): ?int
    {
        /** @var Product $product */
        $product = $this->route('product');

        return $product?->id;
    }
}
