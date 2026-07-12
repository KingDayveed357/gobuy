<?php

namespace App\Modules\Operations\Register\Models;

use App\Admin\Models\Admin;
use App\Modules\Order\Enums\PaymentMethod;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * One business day at the till: opened with a cash float, closed by counting the
 * drawer against what the day's walk-in sales expected. Expected totals are
 * computed live while open, then frozen as a snapshot at close so history is
 * immune to later order edits.
 */
class CashSession extends Model
{
    protected $fillable = [
        'opened_by_id', 'opening_float', 'opened_at',
        'closed_by_id', 'closed_at', 'note',
        'counted_cash', 'counted_pos', 'counted_transfer',
        'expected_cash', 'expected_pos', 'expected_transfer',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_float' => Money::class,
            'counted_cash' => Money::class,
            'counted_pos' => Money::class,
            'counted_transfer' => Money::class,
            'expected_cash' => Money::class,
            'expected_pos' => Money::class,
            'expected_transfer' => Money::class,
        ];
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'opened_by_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'closed_by_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('closed_at');
    }

    public function isOpen(): bool
    {
        return $this->closed_at === null;
    }

    /** The currently-open session, if any. */
    public static function current(): ?self
    {
        return static::query()->open()->latest('opened_at')->first();
    }

    /**
     * Walk-in sales taken during this session, totalled per tender (in kobo).
     *
     * @return array{cash: Money, pos: Money, transfer: Money, count: int}
     */
    public function windowSales(): array
    {
        $rows = DB::table('orders')
            ->where('channel', 'walk_in')
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$this->opened_at, $this->closed_at ?? now()])
            ->selectRaw('payment_method, COUNT(*) as cnt, SUM(total) as tot')
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method');

        $total = fn (PaymentMethod $method) => Money::fromKobo((int) ($rows[$method->value]->tot ?? 0));

        return [
            'cash' => $total(PaymentMethod::Cash),
            'pos' => $total(PaymentMethod::PosTerminal),
            'transfer' => $total(PaymentMethod::BankTransfer),
            'count' => (int) $rows->sum('cnt'),
        ];
    }

    /**
     * What each tender SHOULD total: the live window while open, else the frozen
     * snapshot. Cash also includes the opening float.
     *
     * @return array{cash: Money, pos: Money, transfer: Money}
     */
    public function expected(): array
    {
        if (! $this->isOpen()) {
            return [
                'cash' => $this->expected_cash ?? Money::zero(),
                'pos' => $this->expected_pos ?? Money::zero(),
                'transfer' => $this->expected_transfer ?? Money::zero(),
            ];
        }

        $sales = $this->windowSales();

        return [
            'cash' => $this->opening_float->plus($sales['cash']),
            'pos' => $sales['pos'],
            'transfer' => $sales['transfer'],
        ];
    }

    /**
     * Counted minus expected per tender (negative = short, positive = over).
     *
     * @return array{cash: Money, pos: Money, transfer: Money}
     */
    public function variance(): array
    {
        $expected = $this->expected();

        return [
            'cash' => ($this->counted_cash ?? Money::zero())->minus($expected['cash']),
            'pos' => ($this->counted_pos ?? Money::zero())->minus($expected['pos']),
            'transfer' => ($this->counted_transfer ?? Money::zero())->minus($expected['transfer']),
        ];
    }
}
