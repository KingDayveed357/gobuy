<?php

namespace App\Livewire\Admin\Purchasing;

use App\Modules\Operations\Purchasing\Exceptions\PurchasingException;
use App\Modules\Operations\Purchasing\Models\PurchaseOrder;
use App\Modules\Operations\Purchasing\Services\PurchaseOrderService;
use Livewire\Component;

/**
 * Receive goods against a placed purchase order. Pre-fills each outstanding line
 * with its full outstanding quantity; the buyer trims anything short-delivered
 * and confirms. Landing the stock is done by the service through the ledger.
 */
class ReceiveGoods extends Component
{
    public PurchaseOrder $order;

    /** @var array<int, int> purchase_order_item id => quantity to receive now */
    public array $receive = [];

    public function mount(PurchaseOrder $order): void
    {
        $this->order = $order;
        $this->fillOutstanding();
    }

    private function fillOutstanding(): void
    {
        $this->receive = $this->order->items
            ->mapWithKeys(fn ($item): array => [$item->id => $item->outstanding()])
            ->all();
    }

    public function submit(PurchaseOrderService $service): void
    {
        try {
            $service->receive($this->order, array_map('intval', $this->receive), auth('admin')->user());
        } catch (PurchasingException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        // Reload the whole PO page so the items table, status and actions all
        // reflect the receipt (the surrounding page is server-rendered).
        session()->flash('status', 'Goods received into stock.');
        $this->redirectRoute('admin.purchase-orders.show', $this->order, navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.purchasing.receive-goods');
    }
}
