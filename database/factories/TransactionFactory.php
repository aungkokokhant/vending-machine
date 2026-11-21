<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'quantity' => 1,
            'unit_price' => fn (array $attributes): string => Product::query()
                ->findOrFail($attributes['product_id'])->price,
            'total_price' => fn (array $attributes): string => (string) BigDecimal::of($attributes['unit_price'])
                ->multipliedBy($attributes['quantity'])->toScale(3),
        ];
    }
}
