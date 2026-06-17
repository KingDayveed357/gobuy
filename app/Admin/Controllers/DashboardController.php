<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Admin\Services\DashboardService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function __invoke(): View
    {
        return view('admin.dashboard.index', [
            'metrics' => $this->dashboard->metrics(),
            'recentOrders' => $this->dashboard->recentOrders(),
            'lowStock' => $this->dashboard->lowStockProducts(),
        ]);
    }
}
