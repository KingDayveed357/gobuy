<?php

namespace App\Livewire\Wishlist;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The single source of truth for the navbar wishlist badge. Driven entirely by
 * the `wishlist-updated` event: every surface (PDP button, wishlist page, guest
 * page) dispatches it with the authoritative count, so the badge never desyncs.
 *
 * VPS note: after the initial mount it does ZERO database queries — the count
 * always arrives in the event payload (auth toggle response or guest
 * localStorage length), so there is no per-interaction lookup.
 */
class WishlistCount extends Component
{
    public int $count = 0;

    public function mount(): void
    {
        // Auth users have a server-side count; guests start at 0 and the page's
        // JS immediately pushes their localStorage count via the event.
        $this->count = (int) (Auth::guard('web')->user()?->wishlistItems()->count() ?? 0);
    }

    #[On('wishlist-updated')]
    public function syncCount(?int $count = null): void
    {
        if ($count !== null) {
            $this->count = max(0, $count);

            return;
        }

        $this->count = (int) (Auth::guard('web')->user()?->wishlistItems()->count() ?? 0);
    }

    public function render()
    {
        return view('livewire.wishlist.wishlist-count');
    }
}
