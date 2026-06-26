<?php

namespace App\Admin\Services;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Enums\PaymentMethod;
use App\Modules\Order\Enums\PaymentStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AnalyticsService
{
    public function dashboard(int $days = 30): array
    {
        return [
            'period' => $days,
            'totals' => $this->totals($days),
            'comparison' => $this->periodComparison($days),
            'revenueByDay' => $this->revenueByDay($days),
            'revenueForecast' => $this->revenueForecast($days),
            'ordersByStatus' => $this->ordersByStatus(),
            'topProducts' => $this->topProducts(8, 'revenue'),
            'topProductsByUnits' => $this->topProducts(8, 'quantity'),
            'topCategories' => $this->topCategories(6),
            'customerMix' => $this->customerMix($days),
            'retentionTrend' => $this->retentionTrend($days),
            'orderRates' => $this->orderRates($days),
            'paymentMethods' => $this->paymentMethodBreakdown($days),
            'failedPaymentsByDay' => $this->failedPaymentsByDay($days),
            'paymentSuccessRate' => $this->paymentSuccessRate($days),
            'insights' => $this->insights($days),
            'topCustomers' => $this->topCustomers(5),
        ];
    }

    /**
     * @return array<string, int|float>
     */
    public function totals(int $days = 30): array
    {
        $paid = $this->paidOrdersQuery($days);
        $count = (clone $paid)->count();
        $revenue = (float) (clone $paid)->sum('total');

        return [
            'revenue' => $revenue,
            'paid_orders' => $count,
            'average_order_value' => $count > 0 ? round($revenue / $count, 2) : 0.0,
            'refunded' => (float) Order::query()
                ->where('payment_status', PaymentStatus::Refunded->value)
                ->where('placed_at', '>=', now()->subDays($days - 1)->startOfDay())
                ->sum('total'),
            'growth_rate' => $this->periodComparison($days)['revenue_growth'],
        ];
    }

    /**
     * @return array<string, float|int>
     */
    public function periodComparison(int $days = 30): array
    {
        $currentStart = now()->subDays($days - 1)->startOfDay();
        $previousStart = now()->subDays(($days * 2) - 1)->startOfDay();
        $previousEnd = $currentStart->copy()->subSecond();

        $currentRevenue = (float) Order::query()
            ->where('payment_status', PaymentStatus::Paid->value)
            ->where('placed_at', '>=', $currentStart)
            ->sum('total');

        $previousRevenue = (float) Order::query()
            ->where('payment_status', PaymentStatus::Paid->value)
            ->whereBetween('placed_at', [$previousStart, $previousEnd])
            ->sum('total');

        $currentOrders = Order::query()
            ->where('payment_status', PaymentStatus::Paid->value)
            ->where('placed_at', '>=', $currentStart)
            ->count();

        $previousOrders = Order::query()
            ->where('payment_status', PaymentStatus::Paid->value)
            ->whereBetween('placed_at', [$previousStart, $previousEnd])
            ->count();

        return [
            'current_revenue' => $currentRevenue,
            'previous_revenue' => $previousRevenue,
            'revenue_growth' => $this->growthPercent($previousRevenue, $currentRevenue),
            'current_orders' => $currentOrders,
            'previous_orders' => $previousOrders,
            'orders_growth' => $this->growthPercent($previousOrders, $currentOrders),
        ];
    }

    /**
     * @return Collection<int, array{label: string, value: float, previous: float}>
     */
    public function revenueByDay(int $days = 30): Collection
    {
        $current = $this->dailyRevenueMap($days, 0);
        $previous = $this->dailyRevenueMap($days, $days);

        return collect(range($days - 1, 0))->map(function (int $offset) use ($current, $previous, $days) {
            $date = Carbon::today()->subDays($offset);
            $prevDate = $date->copy()->subDays($days);

            return [
                'label' => $date->format('M j'),
                'value' => (float) ($current[$date->toDateString()] ?? 0),
                'previous' => (float) ($previous[$prevDate->toDateString()] ?? 0),
            ];
        });
    }

    /**
     * Simple linear forecast for the next 7 days based on recent daily average.
     *
     * @return Collection<int, array{label: string, value: float, forecast: bool}>
     */
    public function revenueForecast(int $days = 30): Collection
    {
        $history = $this->revenueByDay(min($days, 14));
        $avg = $history->avg('value') ?: 0;
        $points = $history->map(fn (array $row) => [
            'label' => $row['label'],
            'value' => $row['value'],
            'forecast' => false,
        ]);

        for ($i = 1; $i <= 7; $i++) {
            $date = Carbon::today()->addDays($i);
            $points->push([
                'label' => $date->format('M j'),
                'value' => round($avg, 2),
                'forecast' => true,
            ]);
        }

        return $points;
    }

    /**
     * @return array<string, int>
     */
    public function ordersByStatus(): array
    {
        $counts = Order::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return collect(OrderStatus::cases())
            ->mapWithKeys(fn (OrderStatus $s) => [$s->label() => (int) ($counts[$s->value] ?? 0)])
            ->all();
    }

    /**
     * @return Collection<int, array{name: string, quantity: int, revenue: float}>
     */
    public function topProducts(int $limit = 5, string $sort = 'revenue'): Collection
    {
        $query = OrderItem::query()
            ->selectRaw('name, SUM(quantity) as quantity, SUM(line_total) as revenue')
            ->groupBy('name');

        return $query
            ->orderByDesc($sort === 'quantity' ? 'quantity' : 'revenue')
            ->take($limit)
            ->get()
            ->map(fn (OrderItem $row) => [
                'name' => $row->name,
                'quantity' => (int) $row->quantity,
                'revenue' => (float) $row->revenue,
            ]);
    }

    /**
     * @return Collection<int, array{name: string, revenue: float, quantity: int}>
     */
    public function topCategories(int $limit = 6): Collection
    {
        return OrderItem::query()
            ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereNotNull('order_items.product_variant_id')
            ->selectRaw('categories.name as name, SUM(order_items.line_total) as revenue, SUM(order_items.quantity) as quantity')
            ->groupBy('categories.name')
            ->orderByDesc('revenue')
            ->take($limit)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'revenue' => (float) $row->revenue,
                'quantity' => (int) $row->quantity,
            ]);
    }

    /**
     * @return array<string, int|float>
     */
    public function customerMix(int $days = 30): array
    {
        $since = now()->subDays($days - 1)->startOfDay();
        $previousStart = now()->subDays(($days * 2) - 1)->startOfDay();
        $previousEnd = $since->copy()->subSecond();

        $recentPaidOrders = Order::query()
            ->where('payment_status', PaymentStatus::Paid->value)
            ->where('placed_at', '>=', $since)
            ->whereNotNull('user_id')
            ->get(['user_id', 'placed_at']);

        // Count new customers in the PRIOR period for growth comparison
        $priorPaidOrders = Order::query()
            ->where('payment_status', PaymentStatus::Paid->value)
            ->whereBetween('placed_at', [$previousStart, $previousEnd])
            ->whereNotNull('user_id')
            ->get(['user_id', 'placed_at']);

        $userIds = $recentPaidOrders->pluck('user_id')->unique();
        $returning = 0;
        $new = 0;

        foreach ($userIds as $userId) {
            $firstPaidAt = Order::query()
                ->where('user_id', $userId)
                ->where('payment_status', PaymentStatus::Paid->value)
                ->min('placed_at');

            if ($firstPaidAt && Carbon::parse($firstPaidAt)->lt($since)) {
                $returning++;
            } else {
                $new++;
            }
        }

        // Count unique new customers from prior period
        $priorUserIds = $priorPaidOrders->pluck('user_id')->unique();
        $priorNew = 0;
        foreach ($priorUserIds as $userId) {
            $firstPaidAt = Order::query()
                ->where('user_id', $userId)
                ->where('payment_status', PaymentStatus::Paid->value)
                ->min('placed_at');
            if ($firstPaidAt && Carbon::parse($firstPaidAt)->gte($previousStart)) {
                $priorNew++;
            }
        }

        $guestOrders = Order::query()
            ->where('payment_status', PaymentStatus::Paid->value)
            ->where('placed_at', '>=', $since)
            ->whereNull('user_id')
            ->count();

        $total = $new + $returning;
        $repeatPurchaseRate = $total > 0 ? round(($returning / $total) * 100, 1) : 0.0;

        // All-time total registered customers
        $allTimeCustomers = User::query()->count();

        $ltvData = $this->averageCustomerLtvWithCount();

        return [
            'new' => $new,
            'returning' => $returning,
            'guest_checkouts' => $guestOrders,
            'average_ltv' => $ltvData['average'],
            'ltv_customer_count' => $ltvData['count'],
            'repeat_purchase_rate' => $repeatPurchaseRate,
            'new_growth_rate' => $this->growthPercent((float) $priorNew, (float) $new),
            'all_time_customers' => $allTimeCustomers,
        ];
    }

    /**
     * Top customers by lifetime spend (name only, no PII like email/phone).
     *
     * @return Collection<int, array{name: string, lifetime_spend: float, total_orders: int, last_order_at: string}>
     */
    public function topCustomers(int $limit = 5): Collection
    {
        return Order::query()
            ->select('user_id')
            ->selectRaw('SUM(total) as lifetime_total')
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('MAX(placed_at) as last_order_at')
            ->where('payment_status', PaymentStatus::Paid->value)
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('lifetime_total')
            ->take($limit)
            ->get()
            ->map(function ($row) {
                $user = User::find($row->user_id);
                return [
                    'name' => $user?->name ?? 'Unknown',
                    'lifetime_spend' => (float) $row->lifetime_total,
                    'total_orders' => (int) $row->total_orders,
                    'last_order_at' => $row->last_order_at
                        ? Carbon::parse($row->last_order_at)->format('M j, Y')
                        : '—',
                ];
            });
    }

    /**
     * @return Collection<int, array{label: string, returning: int, new: int}>
     */
    public function retentionTrend(int $days = 30): Collection
    {
        return collect(range($days - 1, 0))->map(function (int $offset) {
            $date = Carbon::today()->subDays($offset);
            $start = $date->copy()->startOfDay();
            $end = $date->copy()->endOfDay();

            $orders = Order::query()
                ->where('payment_status', PaymentStatus::Paid->value)
                ->whereBetween('placed_at', [$start, $end])
                ->whereNotNull('user_id')
                ->pluck('user_id')
                ->unique();

            $returning = 0;
            $new = 0;

            foreach ($orders as $userId) {
                $prior = Order::query()
                    ->where('user_id', $userId)
                    ->where('payment_status', PaymentStatus::Paid->value)
                    ->where('placed_at', '<', $start)
                    ->exists();

                $prior ? $returning++ : $new++;
            }

            return [
                'label' => $date->format('M j'),
                'returning' => $returning,
                'new' => $new,
            ];
        });
    }

    /**
     * @return array<string, float|int>
     */
    public function orderRates(int $days = 30): array
    {
        $since = now()->subDays($days - 1)->startOfDay();
        $total = Order::query()->where('placed_at', '>=', $since)->count();
        $paid = Order::query()
            ->where('placed_at', '>=', $since)
            ->where('payment_status', PaymentStatus::Paid->value)
            ->count();
        $cancelled = Order::query()
            ->where('placed_at', '>=', $since)
            ->where('status', OrderStatus::Cancelled->value)
            ->count();
        $refunded = Order::query()
            ->where('placed_at', '>=', $since)
            ->where('payment_status', PaymentStatus::Refunded->value)
            ->count();

        return [
            'total' => $total,
            'paid' => $paid,
            'conversion_rate' => $total > 0 ? round(($paid / $total) * 100, 1) : 0.0,
            'cancellation_rate' => $total > 0 ? round(($cancelled / $total) * 100, 1) : 0.0,
            'refund_rate' => $paid > 0 ? round(($refunded / $paid) * 100, 1) : 0.0,
        ];
    }

    /**
     * @return Collection<int, array{method: string, count: int, revenue: float}>
     */
    public function paymentMethodBreakdown(int $days = 30): Collection
    {
        $since = now()->subDays($days - 1)->startOfDay();

        return Order::query()
            ->where('payment_status', PaymentStatus::Paid->value)
            ->where('placed_at', '>=', $since)
            ->whereNotNull('payment_method')
            ->selectRaw('payment_method, COUNT(*) as count, SUM(total) as revenue')
            ->groupBy('payment_method')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row) => [
                'method' => $row->payment_method instanceof PaymentMethod
                    ? $row->payment_method->label()
                    : ucfirst(str_replace('_', ' ', (string) $row->payment_method)),
                'count' => (int) $row->count,
                'revenue' => (float) $row->revenue,
            ]);
    }

    /**
     * @return Collection<int, array{label: string, value: int}>
     */
    public function failedPaymentsByDay(int $days = 30): Collection
    {
        $rows = Order::query()
            ->where('payment_status', PaymentStatus::Failed->value)
            ->where('placed_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->get(['placed_at'])
            ->groupBy(fn (Order $o) => $o->placed_at->toDateString())
            ->map(fn (Collection $group) => $group->count());

        return collect(range($days - 1, 0))->map(function (int $offset) use ($rows) {
            $date = Carbon::today()->subDays($offset);

            return [
                'label' => $date->format('M j'),
                'value' => (int) ($rows[$date->toDateString()] ?? 0),
            ];
        });
    }

    public function paymentSuccessRate(int $days = 30): float
    {
        $since = now()->subDays($days - 1)->startOfDay();
        $attempted = Order::query()
            ->where('placed_at', '>=', $since)
            ->whereIn('payment_status', [
                PaymentStatus::Paid->value,
                PaymentStatus::Failed->value,
                PaymentStatus::Unpaid->value,
            ])
            ->count();
        $paid = Order::query()
            ->where('placed_at', '>=', $since)
            ->where('payment_status', PaymentStatus::Paid->value)
            ->count();

        return $attempted > 0 ? round(($paid / $attempted) * 100, 1) : 0.0;
    }

    /**
     * Actionable executive insights derived from store data.
     *
     * @return list<array{type: string, title: string, message: string}>
     */
    public function insights(int $days = 30): array
    {
        $insights = [];
        $comparison = $this->periodComparison($days);

        if ($comparison['revenue_growth'] <= -15) {
            $insights[] = [
                'type' => 'danger',
                'title' => 'Revenue decline detected',
                'message' => sprintf(
                    'Revenue is down %.1f%% vs the previous %d-day period. Review traffic sources and top-performing products.',
                    abs($comparison['revenue_growth']),
                    $days
                ),
            ];
        } elseif ($comparison['revenue_growth'] >= 20) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Strong revenue growth',
                'message' => sprintf(
                    'Revenue grew %.1f%% compared to the previous %d-day period. Consider scaling inventory for best sellers.',
                    $comparison['revenue_growth'],
                    $days
                ),
            ];
        }

        $lowStockCount = ProductVariant::query()
            ->whereColumn('stock', '<=', 'low_stock_threshold')
            ->where('stock', '>', 0)
            ->count();

        if ($lowStockCount > 0) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Low stock risk',
                'message' => "{$lowStockCount} variant(s) are at or below their reorder threshold. Restock before demand spikes.",
            ];
        }

        $outOfStock = ProductVariant::query()->where('stock', '<=', 0)->count();
        if ($outOfStock > 0) {
            $insights[] = [
                'type' => 'danger',
                'title' => 'Out-of-stock variants',
                'message' => "{$outOfStock} variant(s) are out of stock and may be blocking conversions.",
            ];
        }

        $declining = $this->topProducts(3, 'revenue');
        if ($declining->isEmpty()) {
            $insights[] = [
                'type' => 'info',
                'title' => 'Build sales momentum',
                'message' => 'No paid order line items yet. Launch a promotion or feature hero products to kick-start sales.',
            ];
        }

        if ($this->paymentSuccessRate($days) < 85 && $this->orderRates($days)['total'] > 5) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Payment friction',
                'message' => 'Payment success rate is below 85%. Review failed payments and checkout experience.',
            ];
        }

        $mix = $this->customerMix($days);
        if (($mix['returning'] + $mix['new']) > 0 && $mix['returning'] > ($mix['new'] * 1.5)) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Healthy repeat purchase behavior',
                'message' => 'Returning customers outpace new buyers — loyalty and retention are working.',
            ];
        }

        return $insights;
    }

    private function paidOrdersQuery(int $days)
    {
        return Order::query()
            ->where('payment_status', PaymentStatus::Paid->value)
            ->where('placed_at', '>=', now()->subDays($days - 1)->startOfDay());
    }

    /**
     * @return array<string, float>
     */
    private function dailyRevenueMap(int $days, int $offsetDays): array
    {
        $start = now()->subDays(($days + $offsetDays) - 1)->startOfDay();
        $end = now()->subDays($offsetDays)->endOfDay();

        return Order::query()
            ->where('payment_status', PaymentStatus::Paid->value)
            ->whereBetween('placed_at', [$start, $end])
            ->get(['total', 'placed_at'])
            ->groupBy(fn (Order $o) => $o->placed_at->toDateString())
            ->map(fn (Collection $group) => (float) $group->sum(fn (Order $o) => $o->total->kobo))
            ->all();
    }

    private function growthPercent(float $previous, float $current): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function averageCustomerLtv(): float
    {
        return $this->averageCustomerLtvWithCount()['average'];
    }

    /**
     * @return array{average: float, count: int}
     */
    private function averageCustomerLtvWithCount(): array
    {
        $rows = Order::query()
            ->where('payment_status', PaymentStatus::Paid->value)
            ->whereNotNull('user_id')
            ->selectRaw('user_id, SUM(total) as lifetime_total')
            ->groupBy('user_id')
            ->pluck('lifetime_total');

        return [
            'average' => round((float) ($rows->avg() ?? 0), 2),
            'count' => $rows->count(),
        ];
    }
}
