<?php

namespace App\Modules\Returns\Enums;

use App\Modules\Returns\StateMachines\ReturnStateMachine;

/**
 * Lifecycle of a return request. Forward transitions are guarded by the
 * {@see ReturnStateMachine}; terminal states
 * have no onward moves.
 */
enum ReturnStatus: string
{
    case Requested = 'requested';
    case InfoRequested = 'info_requested';
    case Approved = 'approved';
    case AwaitingShipment = 'awaiting_shipment';
    case InTransit = 'in_transit';
    case Received = 'received';
    case Inspecting = 'inspecting';
    case Refunded = 'refunded';     // money returned to original method
    case Credited = 'credited';     // settled as store credit
    case Closed = 'closed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Refunded, self::Credited, self::Closed, self::Rejected, self::Cancelled], true);
    }

    public function isSettled(): bool
    {
        return in_array($this, [self::Refunded, self::Credited, self::Closed], true);
    }

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Requested => [self::Approved, self::InfoRequested, self::Rejected, self::Cancelled],
            self::InfoRequested => [self::Requested, self::Rejected, self::Cancelled],
            self::Approved => [self::AwaitingShipment, self::Received, self::Cancelled], // Received = keep-item/digital skip
            self::AwaitingShipment => [self::InTransit, self::Received, self::Expired, self::Cancelled],
            self::InTransit => [self::Received, self::Expired],
            self::Received => [self::Inspecting, self::Refunded, self::Credited, self::Rejected],
            self::Inspecting => [self::Refunded, self::Credited, self::Rejected],
            self::Refunded, self::Credited => [self::Closed],
            self::Closed, self::Rejected, self::Cancelled, self::Expired => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
