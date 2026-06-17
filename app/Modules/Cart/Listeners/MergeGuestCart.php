<?php

namespace App\Modules\Cart\Listeners;

use App\Models\User;
use App\Modules\Cart\Services\CartService;
use Illuminate\Auth\Events\Login;

class MergeGuestCart
{
    public function __construct(private readonly CartService $cart) {}

    public function handle(Login $event): void
    {
        if ($event->user instanceof User) {
            $this->cart->mergeGuestCartIntoUser($event->user);
        }
    }
}
