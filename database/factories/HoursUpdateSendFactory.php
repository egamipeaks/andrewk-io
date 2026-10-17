<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HoursUpdateSend>
 */
class HoursUpdateSendFactory extends Factory
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
            'email' => fake()->companyEmail(),
            'hours' => fake()->randomFloat(2, 1, 40),
            'sent_at' => now(),
        ];
    }
}
