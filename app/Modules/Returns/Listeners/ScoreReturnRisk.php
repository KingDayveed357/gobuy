<?php

namespace App\Modules\Returns\Listeners;

use App\Modules\Returns\Events\ReturnRequested;
use App\Modules\Returns\Services\ReturnFraudScorer;
use App\Modules\Returns\Services\ReturnRequestService;

/**
 * Risk-scores a new return and auto-approves it when the rules allow. Runs
 * synchronously so low-risk returns are approved the moment they're submitted;
 * the event seam means scoring can be moved fully async later without touching
 * the request flow.
 */
class ScoreReturnRisk
{
    public function __construct(
        private readonly ReturnFraudScorer $scorer,
        private readonly ReturnRequestService $returns,
    ) {}

    public function handle(ReturnRequested $event): void
    {
        $return = $event->return;

        $assessment = $this->scorer->evaluate($return);
        $return->update(['risk_score' => $assessment['score'], 'risk_flags' => $assessment['flags']]);

        if ($assessment['auto_approve']) {
            $this->returns->approve($return, null, auto: true);
        }
    }
}
