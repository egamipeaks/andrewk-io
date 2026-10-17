<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'email' => fake()->unique()->companyEmail(),
            'currency' => fake()->randomElement(['USD', 'CAD']),
            'hourly_rate' => fake()->randomFloat(2, 80, 150),
            'is_active' => true,
        ];
    }

    public function withoutHourlyRate(): static
    {
        return $this->state(fn (array $attributes) => [
            'hourly_rate' => null,
        ]);
    }

    public function withHourlyRate(float $min = 80, float $max = 150): static
    {
        return $this->state(fn (array $attributes) => [
            'hourly_rate' => fake()->randomFloat(2, $min, $max),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
