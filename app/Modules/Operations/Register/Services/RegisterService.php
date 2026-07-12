<?php

namespace App\Modules\Operations\Register\Services;

use App\Admin\Models\Admin;
use App\Modules\Operations\Register\Exceptions\RegisterException;
use App\Modules\Operations\Register\Models\CashSession;
use App\Support\Money;

/**
 * Opens and closes the cash register. Closing freezes the expected tender totals
 * (computed live from the session's walk-in sales) alongside the counted amounts,
 * so the day's reconciliation is a permanent record.
 */
class RegisterService
{
    /** Open a session with a starting cash float. Only one may be open at a time. */
    public function open(Admin $admin, Money $float): CashSession
    {
        if (CashSession::current() !== null) {
            throw new RegisterException('A register session is already open — close it before opening a new one.');
        }

        return CashSession::create([
            'opened_by_id' => $admin->id,
            'opening_float' => $float,
            'opened_at' => now(),
        ]);
    }

    /**
     * Close the session: record the counted tender, freeze what the day expected,
     * and stamp who/when. Variance is derived from these on read.
     */
    public function close(CashSession $session, Money $countedCash, Money $countedPos, Money $countedTransfer, ?string $note, Admin $admin): CashSession
    {
        if (! $session->isOpen()) {
            throw new RegisterException('This register session is already closed.');
        }

        // Snapshot expected totals from live sales BEFORE stamping closed_at.
        $expected = $session->expected();

        $session->update([
            'counted_cash' => $countedCash,
            'counted_pos' => $countedPos,
            'counted_transfer' => $countedTransfer,
            'expected_cash' => $expected['cash'],
            'expected_pos' => $expected['pos'],
            'expected_transfer' => $expected['transfer'],
            'closed_by_id' => $admin->id,
            'closed_at' => now(),
            'note' => $note,
        ]);

        return $session->fresh();
    }
}
