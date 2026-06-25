<?php

namespace App\Livewire\Admin\Order;

use App\Modules\Order\Models\Order;
use Livewire\Component;

class Timeline extends Component
{
    public Order $order;

    protected $listeners = ['orderStatusUpdated' => '$refresh'];

    public function render()
    {
        return view('livewire.admin.order.timeline', [
            'events' => $this->order->timelineEvents(),
        ]);
    }
}
