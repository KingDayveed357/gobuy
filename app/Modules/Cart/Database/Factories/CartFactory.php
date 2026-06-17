<?php

namespace App\Modules\Cart\Database\Factories;

use App\Modules\Cart\Models\Cart;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Cart>
 */
class CartFactory extends Factory
{
    protected $model = Cart::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'session_token' => (string) Str::uuid(),
        ];
    }

    public function forUser(int $userId): static
    {
        return $this->state(fn () => ['user_id' => $userId, 'session_token' => null]);
    }
}
