<?php

namespace App\Livewire\Admin\Packaging;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Operations\Packaging\Models\PackagingUnit;
use App\Support\Money;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Manage the packaging units of a chosen variant — add a carton of 12, a pack of
 * 24 — each with its own barcode and (optional) price. Stock is never touched;
 * these are only sellable lenses over the same base-unit inventory.
 */
class ManagePackaging extends Component
{
    public string $search = '';

    public ?int $variantId = null;

    public ?int $editingId = null;

    public string $name = '';

    public int $multiplier = 1;

    public string $barcode = '';

    public string $price = '';

    public function updatedSearch(): void
    {
        $this->variantId = null;
    }

    /**
     * @return Collection<int, ProductVariant>
     */
    #[Computed]
    public function results()
    {
        $term = trim($this->search);
        if ($this->variantId || mb_strlen($term) < 2) {
            return collect();
        }

        return ProductVariant::query()
            ->where(fn ($q) => $q->where('sku', 'like', "%{$term}%")
                ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$term}%")))
            ->with('product:id,name')
            ->limit(8)->get();
    }

    #[Computed]
    public function variant(): ?ProductVariant
    {
        return $this->variantId ? ProductVariant::with('product:id,name')->find($this->variantId) : null;
    }

    /**
     * @return Collection<int, PackagingUnit>
     */
    #[Computed]
    public function units()
    {
        if (! $this->variantId) {
            return collect();
        }

        return PackagingUnit::query()->where('product_variant_id', $this->variantId)->orderBy('multiplier')->get();
    }

    public function choose(int $variantId): void
    {
        $this->variantId = $variantId;
        $this->search = '';
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $unit = PackagingUnit::findOrFail($id);
        $this->editingId = $unit->id;
        $this->name = $unit->name;
        $this->multiplier = $unit->multiplier;
        $this->barcode = (string) $unit->barcode;
        $this->price = $unit->retail_price && ! $unit->retail_price->isZero() ? (string) $unit->retail_price->toNaira() : '';
    }

    public function save(): void
    {
        $this->validate([
            'variantId' => ['required'],
            'name' => ['required', 'string', 'max:40'],
            'multiplier' => ['required', 'integer', 'min:1'],
            'barcode' => ['nullable', 'string', 'max:64'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data = [
            'product_variant_id' => $this->variantId,
            'name' => $this->name,
            'multiplier' => $this->multiplier,
            'barcode' => $this->barcode ?: null,
            'retail_price' => $this->price !== '' ? Money::fromNaira($this->price) : null,
        ];

        if ($this->editingId) {
            PackagingUnit::findOrFail($this->editingId)->update($data);
        } else {
            PackagingUnit::create($data + ['is_active' => true]);
        }

        unset($this->units);
        $this->resetForm();
        $this->dispatch('toast', type: 'success', message: 'Packaging saved.');
    }

    public function delete(int $id): void
    {
        PackagingUnit::whereKey($id)->delete();
        unset($this->units);
        $this->dispatch('toast', type: 'success', message: 'Packaging removed.');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'multiplier', 'barcode', 'price']);
        $this->multiplier = 1;
    }

    public function render()
    {
        return view('livewire.admin.packaging.manage-packaging');
    }
}
