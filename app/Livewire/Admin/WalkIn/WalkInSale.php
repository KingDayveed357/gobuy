<?php

namespace App\Livewire\Admin\WalkIn;

use App\Models\User;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Exceptions\InsufficientStock;
use App\Modules\Operations\WalkIn\Services\WalkInSaleService;
use App\Modules\Order\Enums\PaymentMethod;
use App\Modules\Pricing\Services\PricingEngine;
use App\Support\Money;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * The fast in-store sale screen: search products, build a basket, take a tender,
 * done — a few taps to replace the notebook. It only prepares input; the actual
 * sale is recorded by {@see WalkInSaleService} through the shared order pipeline.
 */
class WalkInSale extends Component
{
    public string $search = '';

    /** @var array<int, int> variant id => quantity */
    public array $lines = [];

    public string $paymentMethod = 'cash';

    public bool $wholesale = false;

    public string $customerName = '';

    public string $customerPhone = '';

    /** @var array{number: string, total: string, method: string}|null */
    public ?array $completed = null;

    public function addVariant(int $variantId): void
    {
        $this->completed = null;
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
     * Product/variant search — in-stock sellable variants matching name or SKU.
     *
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
            ->where('stock', '>', 0)
            ->whereHas('product', fn ($q) => $q->where('status', 'active'))
            ->where(fn ($q) => $q->where('sku', 'like', "%{$term}%")
                ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$term}%")))
            ->with('product:id,name')
            ->limit(8)->get();
    }

    /**
     * The basket resolved to display rows (variant, unit price, line total),
     * repriced whenever the wholesale toggle flips.
     *
     * @return array{rows: list<array<string, mixed>>, total: Money, count: int}
     */
    #[Computed]
    public function cart(): array
    {
        if ($this->lines === []) {
            return ['rows' => [], 'total' => Money::zero(), 'count' => 0];
        }

        $pricingCustomer = $this->wholesale ? new User(['customer_type' => User::TYPE_WHOLESALE]) : null;
        $variants = ProductVariant::with('product:id,name')->findMany(array_keys($this->lines))->keyBy('id');
        $engine = app(PricingEngine::class);

        $rows = [];
        $total = Money::zero();
        $count = 0;

        foreach ($this->lines as $variantId => $quantity) {
            $variant = $variants->get($variantId);
            if (! $variant) {
                continue;
            }
            $unit = $engine->priceForVariant($variant, $pricingCustomer, $quantity)->unitPrice;
            $lineTotal = $unit->times($quantity);
            $total = $total->plus($lineTotal);
            $count += $quantity;

            $rows[] = [
                'variant_id' => $variant->id,
                'name' => $variant->is_default ? $variant->product->name : "{$variant->product->name} — {$variant->label()}",
                'sku' => $variant->sku,
                'stock' => $variant->stock,
                'quantity' => $quantity,
                'unit' => $unit,
                'line_total' => $lineTotal,
            ];
        }

        return ['rows' => $rows, 'total' => $total, 'count' => $count];
    }

    public function complete(WalkInSaleService $sales): void
    {
        if ($this->lines === []) {
            $this->dispatch('toast', type: 'error', message: 'Add at least one item.');

            return;
        }

        $method = PaymentMethod::tryFrom($this->paymentMethod);
        if (! $method || ! in_array($method, PaymentMethod::inStore(), true)) {
            $this->dispatch('toast', type: 'error', message: 'Choose a valid payment method.');

            return;
        }

        $lines = [];
        foreach ($this->lines as $variantId => $quantity) {
            $lines[] = ['variant_id' => $variantId, 'quantity' => $quantity];
        }

        try {
            $order = $sales->record(
                $lines,
                $method,
                $this->wholesale,
                ['name' => $this->customerName ?: null, 'phone' => $this->customerPhone ?: null],
                auth('admin')->user(),
            );
        } catch (InsufficientStock $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        $this->completed = [
            'number' => $order->order_number,
            'total' => $order->total->format(),
            'method' => $method->label(),
        ];

        $this->reset(['lines', 'search', 'wholesale', 'customerName', 'customerPhone']);
        $this->paymentMethod = 'cash';
        $this->dispatch('toast', type: 'success', message: "Sale {$order->order_number} recorded.");
    }

    public function newSale(): void
    {
        $this->completed = null;
    }

    public function render()
    {
        return view('livewire.admin.walk-in.sale', [
            'methods' => PaymentMethod::inStore(),
        ]);
    }
}
