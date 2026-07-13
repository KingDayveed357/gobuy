<?php

namespace App\Livewire\Admin\StockCounts;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Inventory\Services\InventoryLedger;
use App\Modules\Operations\StockCounts\Models\StockCount;
use App\Modules\Operations\StockCounts\Services\StockCountService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Record a physical stock count at a location: search items, enter what was
 * actually on the shelf next to the system figure, and post the differences to
 * the ledger in one go via {@see StockCountService}.
 */
class RecordStockCount extends Component
{
    public ?int $locationId = null;

    /** @var array<int, int|string> variant id => counted quantity */
    public array $counts = [];

    public string $note = '';

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

    public function addVariant(int $variantId): void
    {
        if (! ProductVariant::whereKey($variantId)->exists()) {
            return;
        }

        if (! array_key_exists($variantId, $this->counts)) {
            $this->counts[$variantId] = '';
        }
    }

    public function removeLine(int $variantId): void
    {
        unset($this->counts[$variantId]);
    }

    /**
     * Resolved lines with the expected on-hand at the chosen location.
     *
     * @return list<array<string, mixed>>
     */
    #[Computed]
    public function rows(): array
    {
        if ($this->counts === [] || ! $this->locationId) {
            return [];
        }

        $location = InventoryLocation::find($this->locationId);
        $ledger = app(InventoryLedger::class);
        $variants = ProductVariant::with('product:id,name')->findMany(array_keys($this->counts))->keyBy('id');

        $rows = [];
        foreach ($this->counts as $variantId => $counted) {
            $variant = $variants->get($variantId);
            if (! $variant || ! $location) {
                continue;
            }
            $expected = $ledger->onHandAt($variant, $location);
            $rows[] = [
                'variant_id' => $variant->id,
                'name' => $variant->is_default ? $variant->product->name : "{$variant->product->name} — {$variant->label()}",
                'sku' => $variant->sku,
                'expected' => $expected,
                'counted' => $counted,
                'variance' => ($counted === '' || $counted === null) ? null : ((int) $counted - $expected),
            ];
        }

        return $rows;
    }

    public function submit(StockCountService $service): void
    {
        $this->validate(['locationId' => ['required']], [], ['locationId' => 'location']);

        try {
            $service->record(
                InventoryLocation::findOrFail($this->locationId),
                $this->counts,
                $this->note ?: null,
                auth('admin')->user(),
            );
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        $this->reset(['counts', 'note']);
        unset($this->rows);
        $this->dispatch('toast', type: 'success', message: 'Stock count recorded.');
    }

    public function render()
    {
        return view('livewire.admin.stock-counts.record-stock-count', [
            'recent' => StockCount::query()->with(['location:id,name', 'createdBy:id,name', 'items'])
                ->latest()->limit(8)->get(),
        ]);
    }
}
