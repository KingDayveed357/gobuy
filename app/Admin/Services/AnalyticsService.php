<?php

namespace App\Admin\Services;

use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Enums\PaymentStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AnalyticsService
{
    /**
     * Paid revenue per day for the last N days (zero-filled).
     *
     * @return Collection<int, array{label: string, value: float}>
     */
    public function revenueByDay(int $days = 14): Collection
    {
        $rows = Order::query()
            ->where('payment_status', PaymentStatus::Paid->value)
            ->where('placed_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->get(['total', 'placed_at'])
            ->groupBy(fn (Order $o) => $o->placed_at->toDateString())
            ->map(fn (Collection $group) => $group->sum(fn (Order $o) => $o->total->kobo)); // kobo

        return collect(range($days - 1, 0))->map(function (int $offset) use ($rows) {
            $date = Carbon::today()->subDays($offset);

            return [
                'label' => $date->format('M j'),
                'value' => (float) ($rows[$date->toDateString()] ?? 0),
            ];
        });
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
    public function topProducts(int $limit = 5): Collection
    {
        return OrderItem::query()
            ->selectRaw('name, SUM(quantity) as quantity, SUM(line_total) as revenue')
            ->groupBy('name')
            ->orderByDesc('quantity')
            ->take($limit)
            ->get()
            ->map(fn (OrderItem $row) => [
                'name' => $row->name,
                'quantity' => (int) $row->quantity,
                'revenue' => (float) $row->revenue,
            ]);
    }

    /**
     * @return array<string, int|float>
     */
    public function totals(): array
    {
        $paid = Order::where('payment_status', PaymentStatus::Paid->value);
        $count = (clone $paid)->count();
        $revenue = (float) (clone $paid)->sum('total');

        return [
            'revenue' => $revenue,
            'paid_orders' => $count,
            'average_order_value' => $count > 0 ? round($revenue / $count, 2) : 0.0,
            'refunded' => (float) Order::where('payment_status', PaymentStatus::Refunded->value)->sum('total'),
        ];
    }
}
