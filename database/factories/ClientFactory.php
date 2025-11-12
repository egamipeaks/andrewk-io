<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
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
}
