<?php

namespace App\Modules\Operations\Dashboards\Http;

use App\Http\Controllers\Controller;
use App\Modules\Operations\Dashboards\Services\OperationsReport;
use Illuminate\Contracts\View\View;

/**
 * The operations dashboard — a read-only roll-up of inventory, sales by channel
 * and movers. Part of the ops.dashboards module.
 */
class OperationsDashboardController extends Controller
{
    public function index(OperationsReport $report): View
    {
        return view('admin.ops-dashboard.index', [
            'totals' => $report->inventoryTotals(),
            'byLocation' => $report->inventoryByLocation(),
            'salesByChannel' => $report->salesByChannel(),
            'topMovers' => $report->topMovers(),
            'lowStock' => $report->lowStock(),
        ]);
    }
}
