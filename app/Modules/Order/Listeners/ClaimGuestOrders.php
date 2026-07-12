<?php

namespace App\Modules\Order\Listeners;

use App\Models\User;
use App\Modules\Order\Models\Order;
use Illuminate\Auth\Events\Verified;

/**
 * When a customer's email becomes verified — via our OTP flow or a social
 * provider — adopt any past guest orders placed with that same email so their
 * history isn't stranded. Runs only on a verified email, so orders can never be
 * claimed by someone who hasn't proven ownership of the address.
 */
class ClaimGuestOrders
{
    public function handle(Verified $event): void
    {
        $user = $event->user;

        if (! $user instanceof User || ! $user->email) {
            return;
        }

        Order::query()
            ->whereNull('user_id')
            ->where('customer_email', $user->email)
            ->update(['user_id' => $user->id]);
    }
}
