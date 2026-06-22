<?php

namespace App\Admin\Notifications;

use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly ProductVariant $variant) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'low_stock',
            'variant_id' => $this->variant->id,
            'sku' => $this->variant->sku,
            'product' => $this->variant->product?->name,
            'stock' => $this->variant->stock,
        ];
    }
}
