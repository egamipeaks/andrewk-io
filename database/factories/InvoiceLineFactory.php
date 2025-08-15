<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceLine>
 */
class InvoiceLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isHourly = fake()->randomElement([true, false]);

        if ($isHourly) {
            return [
                'invoice_id' => Invoice::factory(),
                'description' => fake()->sentence(),
                'amount' => null,
                'hourly_rate' => fake()->randomFloat(2, 50, 200),
                'hours' => fake()->randomFloat(2, 0.5, 40),
            ];
        } else {
            return [
                'invoice_id' => Invoice::factory(),
                'description' => fake()->sentence(),
                'amount' => fake()->randomFloat(2, 100, 5000),
                'hourly_rate' => null,
                'hours' => null,
            ];
        }
    }
}
