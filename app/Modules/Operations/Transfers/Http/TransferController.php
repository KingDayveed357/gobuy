<?php

namespace App\Modules\Operations\Transfers\Http;

use App\Http\Controllers\Controller;
use App\Livewire\Admin\Transfers\TransferStock;

class TransferController extends Controller
{
    /**
     * Stock transfer workbench — the screen itself is the Livewire
     * {@see TransferStock} component.
     */
    public function index()
    {
        return view('admin.transfers.index');
    }
}
