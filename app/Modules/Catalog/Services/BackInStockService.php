<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Mail\BackInStockMail;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Models\StockNotification;
use Illuminate\Support\Facades\Mail;

/**
 * Captures "notify me when available" requests and flushes them the moment a
 * variant is replenished. Turns lost sold-out visits into recoverable demand.
 */
class BackInStockService
{
    /**
     * Register a waiter for a variant (idempotent per email + variant).
     */
    public function register(ProductVariant $variant, string $email, ?int $userId = null): StockNotification
    {
        return StockNotification::firstOrCreate(
            ['product_variant_id' => $variant->id, 'email' => mb_strtolower($email)],
            ['user_id' => $userId],
        );
    }

    /**
     * Notify everyone waiting on a variant that it is back in stock, then clear
     * the queue. No-op when the variant is still out of stock. Idempotent — a
     * second call finds no waiters.
     */
    public function flush(ProductVariant $variant): void
    {
        if ($variant->stock < 1) {
            return;
        }

        $variant->loadMissing('product');

        StockNotification::where('product_variant_id', $variant->id)
            ->get()
            ->each(function (StockNotification $waiter) use ($variant): void {
                Mail::to($waiter->email)->queue(new BackInStockMail($variant));
                $waiter->delete();
            });
    }
}
