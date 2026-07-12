<?php

namespace App\Livewire\Admin\Purchasing;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Operations\Purchasing\Exceptions\PurchasingException;
use App\Modules\Operations\Purchasing\Models\Supplier;
use App\Modules\Operations\Purchasing\Services\PurchaseOrderService;
use App\Support\Money;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Raise a purchase order: pick a supplier and the location goods will land at,
 * add product lines with an ordered quantity and unit cost, then save it as a
 * draft or place it straight away. The actual write is done by the service.
 */
class PurchaseOrderBuilder extends Component
{
    public ?int $supplierId = null;

    public ?int $locationId = null;

    public string $note = '';

    public string $search = '';

    /** @var array<int, array{quantity: int, unit_cost: string}> variant id => line */
    public array $lines = [];

    public function mount(): void
    {
        $this->locationId = InventoryLocation::query()->where('is_default', true)->value('id');
    }

    /**
     * @return Collection<int, Supplier>
     */
    #[Computed]
    public function suppliers()
    {
        return Supplier::query()->where('is_active', true)->orderBy('name')->get();
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
        if (! isset($this->lines[$variantId])) {
            $this->lines[$variantId] = ['quantity' => 1, 'unit_cost' => ''];
        } else {
            $this->lines[$variantId]['quantity']++;
        }
        $this->search = '';
    }

    public function removeLine(int $variantId): void
    {
        unset($this->lines[$variantId]);
    }

    /**
     * Resolved lines for display, with each line's cost.
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

        $rows = [];
        foreach ($this->lines as $variantId => $line) {
            $variant = $variants->get($variantId);
            if (! $variant) {
                continue;
            }
            $quantity = max(1, (int) ($line['quantity'] ?? 1));
            $unitCost = Money::fromNaira($line['unit_cost'] !== '' ? $line['unit_cost'] : 0);
            $rows[] = [
                'variant_id' => $variant->id,
                'name' => $variant->is_default ? $variant->product->name : "{$variant->product->name} — {$variant->label()}",
                'sku' => $variant->sku,
                'quantity' => $quantity,
                'line_cost' => $unitCost->times($quantity),
            ];
        }

        return $rows;
    }

    #[Computed]
    public function total(): Money
    {
        return array_reduce($this->rows, fn (Money $carry, array $row): Money => $carry->plus($row['line_cost']), Money::zero());
    }

    public function save(bool $place = false): void
    {
        $this->validate(['locationId' => ['required']], [], ['locationId' => 'location']);

        $lines = [];
        foreach ($this->lines as $variantId => $line) {
            $lines[] = [
                'variant_id' => (int) $variantId,
                'quantity' => max(0, (int) ($line['quantity'] ?? 0)),
                'unit_cost' => Money::fromNaira($line['unit_cost'] !== '' ? $line['unit_cost'] : 0)->kobo,
            ];
        }

        try {
            $po = app(PurchaseOrderService::class)->create(
                InventoryLocation::findOrFail($this->locationId),
                $lines,
                [
                    'supplier_id' => $this->supplierId,
                    'note' => $this->note ?: null,
                    'admin' => auth('admin')->user(),
                    'place' => $place,
                ],
            );
        } catch (PurchasingException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        $this->redirectRoute('admin.purchase-orders.show', $po, navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.purchasing.purchase-order-builder');
    }
}
