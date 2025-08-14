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
            'paid' => $this->faker->boolean(30), // 30% chance of being paid
            'due_date' => $this->faker->dateTimeBetween('now', '+60 days'),
            'note' => $this->faker->optional()->sentence(),
        ];
    }
}
