<?php

namespace App\Modules\Returns\Services;

use App\Modules\Order\Models\Order;
use App\Modules\Returns\Models\ReturnRequest;
use App\Support\Money;

/**
 * Scores a return for abuse risk (0–100) from behavioural signals and decides
 * whether it qualifies for rule-based auto-approval. Decision support only —
 * a human can always override.
 */
class ReturnFraudScorer
{
    /**
     * @return array{score: int, flags: array<int, string>, auto_approve: bool}
     */
    public function evaluate(ReturnRequest $return): array
    {
        $score = 0;
        $flags = [];

        $userId = $return->user_id;
        $lookback = now()->subDays((int) config('gobuy.returns.fraud.lookback_days'));

        // 1. Serial returner — many returns in the lookback window.
        if ($userId !== null) {
            $recent = ReturnRequest::where('user_id', $userId)
                ->where('id', '!=', $return->id)
                ->where('created_at', '>=', $lookback)
                ->count();

            if ($recent >= 5) {
                $score += 45; // on its own enough to exceed the auto-approve cap
                $flags[] = 'frequent_returner';
            } elseif ($recent >= 3) {
                $score += 20;
                $flags[] = 'repeat_returner';
            }
        }

        // 2. High-value return — bigger incentive to game.
        $returnValue = $this->returnValue($return);
        if ($returnValue->kobo >= Money::fromNaira((int) config('gobuy.returns.fraud.high_value_naira'))->kobo) {
            $score += 20;
            $flags[] = 'high_value';
        }

        // 3. Hard-to-verify claims dominate this customer's history (wardrobing
        //    / "arrived damaged" abuse).
        if ($userId !== null) {
            $past = ReturnRequest::where('user_id', $userId)->where('id', '!=', $return->id)->count();
            if ($past >= 2) {
                $hardClaims = ReturnRequest::where('user_id', $userId)
                    ->where('id', '!=', $return->id)
                    ->whereIn('reason_code', ['damaged', 'not_as_described', 'defective'])
                    ->count();

                if ($hardClaims / $past >= 0.5) {
                    $score += 20;
                    $flags[] = 'frequent_damage_claims';
                }
            }
        }

        // 4. Brand-new account.
        if ($return->user && $return->user->created_at->gt(now()->subDays((int) config('gobuy.returns.fraud.new_account_days')))) {
            $score += 15;
            $flags[] = 'new_account';
        }

        // 5. Returning a large share of lifetime spend.
        if ($userId !== null) {
            $spent = (int) Order::where('user_id', $userId)->sum('total');
            if ($spent > 0 && $returnValue->kobo / $spent >= 0.6) {
                $score += 15;
                $flags[] = 'high_return_ratio';
            }
        }

        $score = min(100, $score);

        return [
            'score' => $score,
            'flags' => $flags,
            'auto_approve' => $this->qualifiesForAutoApproval($return, $score, $returnValue),
        ];
    }

    /**
     * Gross value of the items on this return (what the customer paid).
     */
    public function returnValue(ReturnRequest $return): Money
    {
        $return->loadMissing('items');

        $kobo = $return->items->sum(fn ($item) => $item->unit_price_snapshot->kobo * $item->quantity);

        return Money::fromKobo((int) $kobo);
    }

    private function qualifiesForAutoApproval(ReturnRequest $return, int $score, Money $value): bool
    {
        $cfg = config('gobuy.returns.auto_approve');

        return ($cfg['enabled'] ?? false)
            && $score <= ($cfg['max_score'] ?? 0)
            && in_array($return->reason_code, $cfg['reasons'] ?? [], true)
            && $value->kobo <= Money::fromNaira((int) ($cfg['max_value_naira'] ?? 0))->kobo;
    }
}
