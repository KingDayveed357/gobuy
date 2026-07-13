<?php

namespace App\Livewire\Admin\StockCounts;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Operations\StockCounts\Services\StockCountService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Write off damaged or lost stock at a location — one item at a time, with a
 * reason. Each write-off is a `damage` movement in the ledger.
 */
class WriteOffDamage extends Component
{
    public ?int $locationId = null;

    public ?int $variantId = null;

    public int $quantity = 1;

    public string $reason = '';

    public function mount(): void
    {
        $this->locationId = InventoryLocation::query()->where('is_default', true)->value('id');
    }

    /**
     * @return Collection<int, InventoryLocation>
     */
    #[Computed]
    public function locations()
    {
        return InventoryLocation::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get();
    }

    #[Computed]
    public function variant(): ?ProductVariant
    {
        return $this->variantId ? ProductVariant::with('product:id,name')->find($this->variantId) : null;
    }

    public function choose(int $variantId): void
    {
        $this->variantId = ProductVariant::whereKey($variantId)->value('id');
        unset($this->variant);
    }

    public function clearVariant(): void
    {
        $this->reset(['variantId', 'quantity', 'reason']);
        $this->quantity = 1;
    }

    public function submit(StockCountService $service): void
    {
        $this->validate([
            'locationId' => ['required'],
            'variantId' => ['required'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:160'],
        ], [], ['variantId' => 'item']);

        try {
            $service->writeOffDamage(
                ProductVariant::findOrFail($this->variantId),
                $this->quantity,
                InventoryLocation::findOrFail($this->locationId),
                $this->reason ?: null,
                auth('admin')->user(),
            );
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        $this->clearVariant();
        $this->dispatch('toast', type: 'success', message: 'Damage written off.');
    }

    public function render()
    {
        return view('livewire.admin.stock-counts.write-off-damage', [
            'recent' => InventoryMovement::query()
                ->where('type', MovementType::Damage->value)
                ->with(['location:id,name', 'variant.product:id,name'])
                ->latest()->limit(8)->get(),
        ]);
    }
}
