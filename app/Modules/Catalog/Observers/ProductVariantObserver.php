<?php

namespace App\Modules\Catalog\Observers;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Services\BackInStockService;

class ProductVariantObserver
{
    public function __construct(private readonly BackInStockService $backInStock) {}

    /**
     * Flush any "notify me" waiters the moment a variant crosses from sold-out
     * back into stock. Covers every path that saves the model (admin adjustment,
     * product edit); the refund restock path calls the service directly too.
     */
    public function updated(ProductVariant $variant): void
    {
        if ($variant->wasChanged('stock')
            && (int) $variant->getOriginal('stock') <= 0
            && $variant->stock > 0) {
            $this->backInStock->flush($variant);
        }
    }
}
