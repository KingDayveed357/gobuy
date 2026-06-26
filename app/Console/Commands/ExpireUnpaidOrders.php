<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Services\OrderStatusService;

class ExpireUnpaidOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire unpaid orders that have passed their checkout TTL.';

    /**
     * Execute the console command.
     */
    public function handle(OrderStatusService $statusService)
    {
        $orders = Order::where('status', OrderStatus::Pending)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($orders as $order) {
            try {
                $statusService->transitionTo($order, OrderStatus::Cancelled, 'Automatically cancelled due to checkout expiration.');
                $count++;
            } catch (\Throwable $e) {
                $this->error("Failed to expire order {$order->order_number}: {$e->getMessage()}");
            }
        }

        $this->info("Expired {$count} unpaid orders.");
    }
}
