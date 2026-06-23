<?php

namespace App\Console\Commands;

use App\Modules\Returns\Models\StoreCreditEntry;
use App\Modules\Returns\Services\StoreCreditService;
use Illuminate\Console\Command;

class ExpireStoreCredit extends Command
{
    protected $signature = 'store-credit:expire';

    protected $description = 'Expire unspent store-credit grants past their expiry date';

    public function handle(StoreCreditService $storeCredit): int
    {
        $count = 0;

        StoreCreditEntry::query()
            ->whereIn('type', [StoreCreditEntry::TYPE_REFUND_CREDIT, StoreCreditEntry::TYPE_ADMIN_ADJUST])
            ->where('amount', '>', 0)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->chunkById(200, function ($grants) use ($storeCredit, &$count): void {
                foreach ($grants as $grant) {
                    $storeCredit->expireEntry($grant);
                    $count++;
                }
            });

        $this->info("Processed {$count} expired store-credit grant(s).");

        return self::SUCCESS;
    }
}
