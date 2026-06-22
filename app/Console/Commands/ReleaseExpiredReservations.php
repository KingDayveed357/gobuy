<?php

namespace App\Console\Commands;

use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Console\Command;

class ReleaseExpiredReservations extends Command
{
    protected $signature = 'inventory:release-reservations';

    protected $description = 'Release expired add-to-cart stock reservations back to available stock';

    public function handle(InventoryService $inventory): int
    {
        $released = $inventory->releaseExpired();

        $this->info("Released {$released} expired stock reservation(s).");

        return self::SUCCESS;
    }
}
