<?php

namespace App\Modules\Order\Database\Factories;

use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Enums\PaymentStatus;
use App\Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = fake()->numberBetween(5000, 200000);
        $delivery = 1500;

        return [
            'order_number' => 'GB-'.strtoupper(Str::random(8)),
            'user_id' => null,
            'customer_type' => 'retail',
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->numerify('080########'),
            'address_line' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => 'Lagos',
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Unpaid,
            'subtotal' => $subtotal,
            'delivery_fee' => $delivery,
            'total' => $subtotal + $delivery,
            'placed_at' => now(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Paid,
            'payment_status' => PaymentStatus::Paid,
        ]);
    }
}
