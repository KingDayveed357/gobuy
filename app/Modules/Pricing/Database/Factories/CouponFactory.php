<?php

namespace App\Modules\Pricing\Database\Factories;

use App\Modules\Pricing\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'code' => Str::upper($this->faker->unique()->bothify('SAVE##??')),
            'type' => 'percentage',
            'value' => 10,
            'min_cart_value' => null,
            'is_active' => true,
            'eligibility' => 'both',
            'starts_at' => null,
            'expires_at' => null,
            'usage_limit_total' => null,
            'usage_limit_per_user' => null,
        ];
    }

    public function percentage(float $percent): static
    {
        return $this->state(['type' => 'percentage', 'value' => $percent]);
    }

    public function fixed(float $naira): static
    {
        return $this->state(['type' => 'fixed', 'value' => $naira]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
