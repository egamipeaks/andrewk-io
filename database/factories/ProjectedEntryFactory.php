<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectedEntry>
 */
class ProjectedEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'date' => fake()->dateTimeBetween('now', '+3 months'),
            'hours' => fake()->randomFloat(2, 0.5, 10),
        ];
    }

    public function currentMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => fake()->dateTimeBetween(now()->startOfMonth(), now()->endOfMonth()),
        ]);
    }

    public function nextMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => fake()->dateTimeBetween(
                now()->addMonth()->startOfMonth(),
                now()->addMonth()->endOfMonth()
            ),
        ]);
    }

    public function futureMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => fake()->dateTimeBetween('+2 months', '+6 months'),
        ]);
    }
}
