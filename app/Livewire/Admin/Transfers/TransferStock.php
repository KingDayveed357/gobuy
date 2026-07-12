<?php

namespace App\Livewire\Admin\Transfers;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Exceptions\InsufficientStock;
use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Inventory\Models\StockLevel;
use App\Modules\Operations\Transfers\Models\StockTransfer;
use App\Modules\Operations\Transfers\Services\TransferService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Move stock from one location to another. Prepares the lines only — the actual
 * transfer (and its ledger movements) is recorded by {@see TransferService}.
 */
class TransferStock extends Component
{
    public ?int $fromId = null;

    public ?int $toId = null;

    public string $search = '';

    /** @var array<int, int> variant id => quantity */
    public array $lines = [];

    public string $note = '';

    public function mount(): void
    {
        $default = InventoryLocation::query()->where('is_default', true)->first();
        $this->fromId = $default?->id;
    }

    /**
     * @return Collection<int, InventoryLocation>
     */
    #[Computed]
    public function locations()
    {
        return InventoryLocation::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get();
    }

    /**
     * @return Collection<int, ProductVariant>
     */
    #[Computed]
    public function results()
    {
        $term = trim($this->search);
        if (mb_strlen($term) < 2) {
            return collect();
        }

        return ProductVariant::query()
            ->where(fn ($q) => $q->where('sku', 'like', "%{$term}%")
                ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$term}%")))
            ->with('product:id,name')
            ->limit(8)->get();
    }

    public function addVariant(int $variantId): void
    {
        $this->lines[$variantId] = ($this->lines[$variantId] ?? 0) + 1;
        $this->search = '';
    }

    public function setQuantity(int $variantId, int $quantity): void
    {
        if ($quantity <= 0) {
            unset($this->lines[$variantId]);

            return;
        }
        $this->lines[$variantId] = $quantity;
    }

    public function removeLine(int $variantId): void
    {
        unset($this->lines[$variantId]);
    }

    /**
     * Resolved lines with how much is available at the source location.
     *
     * @return list<array<string, mixed>>
     */
    #[Computed]
    public function rows(): array
    {
        if ($this->lines === []) {
            return [];
        }

        $variants = ProductVariant::with('product:id,name')->findMany(array_keys($this->lines))->keyBy('id');
        $available = $this->fromId
            ? StockLevel::query()->where('inventory_location_id', $this->fromId)
                ->whereIn('product_variant_id', array_keys($this->lines))
                ->pluck('on_hand', 'product_variant_id')
            : collect();

        $rows = [];
        foreach ($this->lines as $variantId => $quantity) {
            $variant = $variants->get($variantId);
            if (! $variant) {
                continue;
            }
            $rows[] = [
                'variant_id' => $variant->id,
                'name' => $variant->is_default ? $variant->product->name : "{$variant->product->name} — {$variant->label()}",
                'sku' => $variant->sku,
                'quantity' => $quantity,
                'available' => (int) ($available[$variantId] ?? 0),
            ];
        }

        return $rows;
    }

    public function submit(TransferService $transfers): void
    {
        $this->resetErrorBag();
        $this->validate([
            'fromId' => ['required', 'different:toId'],
            'toId' => ['required'],
        ], [], ['fromId' => 'source', 'toId' => 'destination']);

        if ($this->lines === []) {
            $this->dispatch('toast', type: 'error', message: 'Add at least one item.');

            return;
        }

        $lines = [];
        foreach ($this->lines as $variantId => $quantity) {
            $lines[] = ['variant_id' => $variantId, 'quantity' => $quantity];
        }

        try {
            $transfers->transfer(
                InventoryLocation::findOrFail($this->fromId),
                InventoryLocation::findOrFail($this->toId),
                $lines,
                $this->note ?: null,
                auth('admin')->user(),
            );
        } catch (InsufficientStock $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        $this->reset(['lines', 'search', 'note']);
        unset($this->rows);
        $this->dispatch('toast', type: 'success', message: 'Stock transferred.');
    }

    public function render()
    {
        return view('livewire.admin.transfers.transfer-stock', [
            'recent' => StockTransfer::query()->with(['from:id,name', 'to:id,name', 'createdBy:id,name', 'items'])
                ->latest()->limit(10)->get(),
        ]);
    }
}
