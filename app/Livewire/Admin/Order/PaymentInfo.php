<?php

namespace App\Livewire\Admin\Order;

use App\Modules\Order\Enums\PaymentStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Payment\Models\Payment;
use Livewire\Component;

class PaymentInfo extends Component
{
    public Order $order;

    protected $listeners = ['orderStatusUpdated' => '$refresh'];

    public function markCashCollected()
    {
        // This is a simplified version of the PaymentController@markPodCollected logic
        // Ideally we would inject a service here, but for now we mimic the basic logic to update state.
        if ($this->order->payment_method === \App\Modules\Order\Enums\PaymentMethod::PayOnDelivery && !$this->order->isPaid()) {
            
            // Create a payment record
            Payment::create([
                'order_id' => $this->order->id,
                'provider' => 'cash',
                'reference' => 'POD-' . time(),
                'amount' => $this->order->amountDue()->kobo,
                'status' => 'success',
                'paid_at' => now(),
            ]);

            // Update order status
            $this->order->update([
                'payment_status' => PaymentStatus::Paid,
            ]);

            $this->dispatch('orderStatusUpdated');
            
            session()->flash('payment_success', 'Cash collection recorded.');
        }
    }

    public function render()
    {
        return view('livewire.admin.order.payment-info');
    }
}
