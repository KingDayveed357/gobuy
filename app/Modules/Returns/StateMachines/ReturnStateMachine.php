<?php

namespace App\Modules\Returns\StateMachines;

use App\Modules\Returns\Enums\ReturnStatus;
use App\Modules\Returns\Events\ReturnStatusChanged;
use App\Modules\Returns\Models\ReturnEvent;
use App\Modules\Returns\Models\ReturnRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * The single guarded entry point for moving a return through its lifecycle.
 * Enforces {@see ReturnStatus} transitions and writes an append-only
 * {@see ReturnEvent} audit row — mirrors the order's OrderStatusService.
 */
class ReturnStateMachine
{
    public function transitionTo(
        ReturnRequest $return,
        ReturnStatus $target,
        ?Model $actor = null,
        string $action = 'transition',
        array $meta = [],
    ): void {
        $current = $return->status;

        if ($current === $target) {
            return; // idempotent
        }

        if (! $current->canTransitionTo($target)) {
            throw new RuntimeException("Illegal return transition {$current->value} → {$target->value}.");
        }

        DB::transaction(function () use ($return, $current, $target, $actor, $action, $meta): void {
            $return->update(['status' => $target]);
            $this->record($return, $action, $actor, $current, $target, $meta);
        });

        Log::info('Return transitioned', [
            'reference' => $return->reference,
            'from' => $current->value,
            'to' => $target->value,
            'actor' => $actor ? $actor::class.':'.$actor->getKey() : 'system',
        ]);

        ReturnStatusChanged::dispatch($return, $current, $target);
    }

    /**
     * Append an audit event (also used for non-status notes like item inspection).
     *
     * @param  array<string, mixed>  $meta
     */
    public function record(
        ReturnRequest $return,
        string $action,
        ?Model $actor = null,
        ?ReturnStatus $from = null,
        ?ReturnStatus $to = null,
        array $meta = [],
    ): ReturnEvent {
        return $return->events()->create([
            'actor_type' => $actor?->getMorphClass(),
            'actor_id' => $actor?->getKey(),
            'from_status' => $from?->value,
            'to_status' => $to?->value,
            'action' => $action,
            'meta' => $meta ?: null,
        ]);
    }
}
