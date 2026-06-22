<?php

namespace App\Modules\Customer\Database\Factories;

use App\Models\User;
use App\Modules\Customer\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => fake()->randomElement(['Home', 'Office', 'Warehouse']),
            'recipient_name' => fake()->name(),
            'phone' => fake()->numerify('080########'),
            'line1' => fake()->streetAddress(),
            'line2' => null,
            'city' => fake()->city(),
            'state' => fake()->randomElement(['Lagos', 'Rivers', 'Abuja', 'Oyo']),
            'country' => 'Nigeria',
            'postal_code' => null,
            'is_default_shipping' => false,
            'is_default_billing' => false,
        ];
    }

    public function defaultShipping(): static
    {
        return $this->state(fn () => ['is_default_shipping' => true]);
    }

    public function defaultBilling(): static
    {
        return $this->state(fn () => ['is_default_billing' => true]);
    }
}
