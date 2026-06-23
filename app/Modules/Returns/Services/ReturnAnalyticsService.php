<?php

namespace App\Modules\Returns\Services;

use App\Modules\Order\Models\Order;
use App\Modules\Returns\Enums\ReturnStatus;
use App\Modules\Returns\Models\ReturnRequest;
use App\Support\Money;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;

/**
 * Operational KPIs for the returns desk — computed on demand from live data
 * (cheap aggregates), so there is no separate metrics store to keep in sync.
 */
class ReturnAnalyticsService
{
    /**
     * @return array{
     *   total: int, open: int, window_days: int,
     *   return_rate: float, auto_approval_rate: float,
     *   refunded_value: Money, avg_approval_hours: ?float, failed_settlements: int
     * }
     */
    public function kpis(int $days = 30): array
    {
        $since = now()->subDays($days - 1)->startOfDay();

        $total = ReturnRequest::where('created_at', '>=', $since)->count();
        $open = ReturnRequest::whereIn('status', $this->openStatuses())->count();

        $deliveredOrders = Order::whereNotNull('delivered_at')->where('delivered_at', '>=', $since)->count();
        $approved = ReturnRequest::where('created_at', '>=', $since)
            ->whereNotNull('approved_by')->orWhere('auto_approved', true)->count();
        $autoApproved = ReturnRequest::where('created_at', '>=', $since)->where('auto_approved', true)->count();

        $refundedKobo = (int) ReturnRequest::where('created_at', '>=', $since)->sum('refunded_total');

        return [
            'total' => $total,
            'open' => $open,
            'window_days' => $days,
            'return_rate' => $deliveredOrders > 0 ? round($total / $deliveredOrders * 100, 1) : 0.0,
            'auto_approval_rate' => $approved > 0 ? round($autoApproved / $approved * 100, 1) : 0.0,
            'refunded_value' => Money::fromKobo($refundedKobo),
            'avg_approval_hours' => $this->averageApprovalHours($since),
            'failed_settlements' => $this->failedSettlementAlerts(),
        ];
    }

    /**
     * Mean hours between a return being requested and approved, over the window.
     */
    private function averageApprovalHours(Carbon $since): ?float
    {
        $samples = ReturnRequest::query()
            ->where('created_at', '>=', $since)
            ->whereHas('events', fn ($q) => $q->where('to_status', ReturnStatus::Approved->value))
            ->with(['events' => fn ($q) => $q->where('to_status', ReturnStatus::Approved->value)])
            ->get()
            ->map(function (ReturnRequest $r) {
                $approvedAt = $r->events->first()?->created_at;

                return $approvedAt ? $r->created_at->diffInMinutes($approvedAt) : null;
            })
            ->filter()
            ->values();

        return $samples->isEmpty() ? null : round($samples->avg() / 60, 1);
    }

    /**
     * Unread settlement-failure alerts across admins (de-duplicated by return).
     */
    private function failedSettlementAlerts(): int
    {
        return DatabaseNotification::query()
            ->whereNull('read_at')
            ->where('data->type', 'return_settlement_failed')
            ->get()
            ->pluck('data.return_id')
            ->unique()
            ->count();
    }

    /**
     * @return array<int, string>
     */
    private function openStatuses(): array
    {
        return collect(ReturnStatus::cases())
            ->filter(fn (ReturnStatus $s) => $s->isOpen())
            ->map(fn (ReturnStatus $s) => $s->value)
            ->all();
    }
}
