<?php

namespace App\Livewire\Admin\Order;

use App\Modules\Order\Models\Order;
use Livewire\Component;

class Header extends Component
{
    public Order $order;

    protected $listeners = ['orderStatusUpdated' => '$refresh'];

    public function render()
    {
        return view('livewire.admin.order.header');
    }
}
