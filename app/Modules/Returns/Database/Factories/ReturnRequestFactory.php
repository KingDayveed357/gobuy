<?php

namespace App\Modules\Returns\Database\Factories;

use App\Modules\Order\Models\Order;
use App\Modules\Returns\Enums\ReturnReason;
use App\Modules\Returns\Enums\ReturnStatus;
use App\Modules\Returns\Models\ReturnRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ReturnRequest>
 */
class ReturnRequestFactory extends Factory
{
    protected $model = ReturnRequest::class;

    public function definition(): array
    {
        return [
            'reference' => 'RMA-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
            'order_id' => Order::factory(),
            'status' => ReturnStatus::Requested,
            'reason_code' => ReturnReason::ChangedMind->value,
            'refund_destination' => 'store_credit',
            'return_shipping_payer' => 'customer',
            'window_expires_at' => now()->addDays(14),
        ];
    }

    public function status(ReturnStatus $status): static
    {
        return $this->state(['status' => $status]);
    }
}
