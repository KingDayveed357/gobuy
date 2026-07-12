<?php

namespace App\Livewire\Admin\Dashboard;

use App\Admin\Services\DashboardService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * The dashboard body — KPI strip, recent orders and low-stock tables. Rendered
 * lazily from the dashboard shell (`<livewire:... lazy />`) so the page paints
 * instantly and the aggregate-heavy queries run in a single follow-up request
 * behind a skeleton {@see placeholder()}. Lazy is applied at the tag, not the
 * class, so the component stays reusable and directly testable.
 */
class Overview extends Component
{
    public function placeholder(): View
    {
        return view('livewire.admin.dashboard.placeholder');
    }

    public function render(DashboardService $dashboard): View
    {
        return view('livewire.admin.dashboard.overview', [
            'metrics' => $dashboard->metrics(),
            'recentOrders' => $dashboard->recentOrders(),
            'lowStock' => $dashboard->lowStockProducts(),
        ]);
    }
}
