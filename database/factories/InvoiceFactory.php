<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
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
            'paid' => fake()->randomElement([0, 1]), // 50% chance of being paid
            'currency' => fake()->randomElement(['USD', 'CAD']),
            'due_date' => fake()->dateTimeBetween('now', '+60 days'),
            'note' => fake()->optional()->sentence(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'paid' => true,
            'due_date' => fake()->dateTimeBetween('-6 months', '-1 month'),
        ]);
    }

    public function unpaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'paid' => false,
            'due_date' => fake()->dateTimeBetween('now', '+60 days'),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'paid' => false,
            'due_date' => fake()->dateTimeBetween('-60 days', '-1 day'),
        ]);
    }

    public function withCadConversionRate(): static
    {
        return $this->state(fn (array $attributes) => [
            'conversion_rate' => fake()->randomFloat(2, 1.35, 1.45),
        ]);
    }
}
