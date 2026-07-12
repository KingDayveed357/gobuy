<?php

namespace App\Modules\Operations\StockCounts\Http;

use App\Http\Controllers\Controller;
use App\Livewire\Admin\StockCounts\RecordStockCount;
use App\Livewire\Admin\StockCounts\WriteOffDamage;
use Illuminate\Contracts\View\View;

/**
 * Stock counts & damage — the screens are the Livewire
 * {@see RecordStockCount} and
 * {@see WriteOffDamage} components.
 */
class StockCountController extends Controller
{
    public function index(): View
    {
        return view('admin.stock-counts.index');
    }
}
