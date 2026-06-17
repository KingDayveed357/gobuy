<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Admin\Services\AnalyticsService;
use Illuminate\Contracts\View\View;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public function __invoke(): View
    {
        return view('admin.analytics.index', [
            'totals' => $this->analytics->totals(),
            'revenueByDay' => $this->analytics->revenueByDay(),
            'ordersByStatus' => $this->analytics->ordersByStatus(),
            'topProducts' => $this->analytics->topProducts(),
        ]);
    }
}
