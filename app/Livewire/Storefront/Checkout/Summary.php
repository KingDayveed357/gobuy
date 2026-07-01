<?php

namespace App\Livewire\Storefront\Checkout;

use App\Modules\Order\Services\CheckoutCalculator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Summary extends Component
{
    public bool $applyCredit = false;

    public string $deliveryMethod = 'home_delivery';

    public string $deliveryState = '';

    public function mount(string $defaultDeliveryMethod = 'home_delivery', string $defaultState = '')
    {
        $this->deliveryMethod = $defaultDeliveryMethod;
        $this->deliveryState = $defaultState;
        $this->applyCredit = (bool) session(CheckoutCalculator::CREDIT_SESSION_KEY);
    }

    #[On('delivery-updated')]
    public function updateDelivery(array $data)
    {
        $this->deliveryMethod = $data['method'] ?? 'home_delivery';
        $this->deliveryState = $data['state'] ?? '';

        // Unset the memoized totals so they recalculate
        unset($this->totals);
    }

    /**
     * Fired by `wire:model.live="applyCredit"` whenever the switch changes.
     * Binding the property directly to the checkbox (rather than a wire:click
     * that manually flips a boolean alongside the browser's own native toggle)
     * keeps the displayed state and the calculated state in lockstep — the
     * source of the previously-inverted behaviour.
     */
    public function updatedApplyCredit(): void
    {
        session([CheckoutCalculator::CREDIT_SESSION_KEY => $this->applyCredit]);

        unset($this->totals);
    }

    #[Computed]
    public function totals()
    {
        $calculator = app(CheckoutCalculator::class);

        return $calculator->calculate(
            Auth::user(),
            $this->deliveryMethod,
            $this->deliveryState,
            $this->applyCredit
        );
    }

    public function render()
    {
        return view('livewire.storefront.checkout.summary');
    }
}
