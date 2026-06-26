<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Services\PaymentService;

class ReconcilePendingPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:reconcile';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically verify pending payments that are older than 10 minutes';

    /**
     * Execute the console command.
     */
    public function handle(PaymentService $payments)
    {
        $stuckPayments = Payment::where('status', 'pending')
            ->where('created_at', '<=', now()->subMinutes(10))
            ->get();

        $count = 0;
        foreach ($stuckPayments as $payment) {
            try {
                $payments->verifyAndComplete($payment->reference);
                $count++;
            } catch (\Throwable $e) {
                $this->error("Failed to reconcile payment {$payment->reference}: {$e->getMessage()}");
            }
        }

        $this->info("Reconciled {$count} stuck pending payments.");
    }
}
