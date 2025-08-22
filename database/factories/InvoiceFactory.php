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
}
