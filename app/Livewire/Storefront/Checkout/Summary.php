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

    private const CREDIT_SESSION_KEY = 'checkout.apply_credit';

    public function mount(string $defaultDeliveryMethod = 'home_delivery', string $defaultState = '')
    {
        $this->deliveryMethod = $defaultDeliveryMethod;
        $this->deliveryState = $defaultState;
        $this->applyCredit = (bool) session(self::CREDIT_SESSION_KEY);
    }

    #[On('delivery-updated')]
    public function updateDelivery(array $data)
    {
        $this->deliveryMethod = $data['method'] ?? 'home_delivery';
        $this->deliveryState = $data['state'] ?? '';
        
        // Unset the memoized totals so they recalculate
        unset($this->totals);
    }

    public function toggleStoreCredit()
    {
        $this->applyCredit = !$this->applyCredit;
        
        // Sync with session
        session([self::CREDIT_SESSION_KEY => $this->applyCredit]);
        
        // Unset memoized totals
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
