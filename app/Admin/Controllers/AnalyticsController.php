<?php

namespace App\Admin\Controllers;

use App\Admin\Services\AnalyticsService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public function __invoke(Request $request): View
    {
        $days = in_array((int) $request->query('period'), [7, 14, 30, 90], true)
            ? (int) $request->query('period')
            : 30;

        $data = $this->analytics->dashboard($days);

        return view('admin.analytics.index', [
            'period' => $days,
            ...$data,
        ]);
    }
}
