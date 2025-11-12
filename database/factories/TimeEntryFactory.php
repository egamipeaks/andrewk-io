<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\InvoiceLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimeEntry>
 */
class TimeEntryFactory extends Factory
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
            'invoice_line_id' => null,
            'date' => fake()->dateTimeBetween('-3 months', 'now'),
            'hours' => fake()->randomFloat(2, 0.5, 10),
            'description' => fake()->sentence(),
        ];
    }

    public function billed(): static
    {
        return $this->state(fn (array $attributes) => [
            'invoice_line_id' => InvoiceLine::factory(),
        ]);
    }

    public function unbilled(): static
    {
        return $this->state(fn (array $attributes) => [
            'invoice_line_id' => null,
        ]);
    }

    public function currentMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => fake()->dateTimeBetween(now()->startOfMonth(), now()),
        ]);
    }

    public function lastMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => fake()->dateTimeBetween(
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth()
            ),
        ]);
    }

    public function older(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => fake()->dateTimeBetween('-4 months', '-2 months'),
        ]);
    }
}
