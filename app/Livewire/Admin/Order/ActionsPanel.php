<?php

namespace App\Livewire\Admin\Order;

use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\InvalidOrderTransition;
use App\Modules\Order\Services\OrderStatusService;
use Livewire\Component;

class ActionsPanel extends Component
{
    public Order $order;
    public string $nextStatus = '';
    public string $note = '';

    public function updateStatus(OrderStatusService $statusService)
    {
        $this->validate([
            'nextStatus' => ['required', 'string'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $status = OrderStatus::from($this->nextStatus);
            $statusService->transitionTo($this->order, $status, $this->note);
            
            $this->order->refresh();
            
            // Dispatch event to refresh sibling components
            $this->dispatch('orderStatusUpdated');
            
            $this->nextStatus = '';
            $this->note = '';
            
            session()->flash('success', "Order moved to {$this->order->status->label()}.");
            
        } catch (InvalidOrderTransition $e) {
            $this->addError('nextStatus', $e->getMessage());
        } catch (\ValueError $e) {
            $this->addError('nextStatus', 'Invalid status selected.');
        }
    }

    public function render()
    {
        return view('livewire.admin.order.actions-panel', [
            'allowedTransitions' => $this->order->status->allowedTransitions(),
        ]);
    }
}
