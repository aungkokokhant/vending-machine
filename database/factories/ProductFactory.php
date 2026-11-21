<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('#####'),
            'price' => '3.990',
            'quantity_available' => 20,
        ];
    }

    public function pricedAt(string $price): static
    {
        return $this->state(fn (array $attributes): array => ['price' => $price]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes): array => ['quantity_available' => 0]);
    }
}
