<?php

namespace Database\Factories;

use App\Enums\InvoiceLineType;
use App\Models\Invoice;
use App\Models\TimeEntry;
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
                'date' => fake()->dateTimeBetween('-1 year', 'now'),
                'type' => InvoiceLineType::Hourly,
                'amount' => null,
                'hourly_rate' => fake()->randomFloat(2, 80, 150),
                'hours' => fake()->randomFloat(2, 0.5, 10),
            ];
        } else {
            return [
                'invoice_id' => Invoice::factory(),
                'description' => fake()->sentence(),
                'date' => fake()->dateTimeBetween('-1 year', 'now'),
                'type' => InvoiceLineType::Fixed,
                'amount' => fake()->randomFloat(2, 100, 3000),
                'hourly_rate' => null,
                'hours' => null,
            ];
        }
    }

    public function fixed(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => InvoiceLineType::Fixed,
            'amount' => fake()->randomFloat(2, 100, 3000),
            'hourly_rate' => null,
            'hours' => null,
        ]);
    }

    public function hourly(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => InvoiceLineType::Hourly,
            'amount' => null,
            'hourly_rate' => fake()->randomFloat(2, 80, 150),
            'hours' => fake()->randomFloat(2, 0.5, 10),
        ]);
    }

    public function withTimeEntries(?int $count = null): static
    {
        $count = $count ?? fake()->numberBetween(1, 4);

        return $this->afterCreating(function ($invoiceLine) use ($count) {
            if ($invoiceLine->type !== InvoiceLineType::Hourly) {
                return;
            }

            $totalHours = $invoiceLine->hours;
            $remainingHours = $totalHours;

            for ($i = 0; $i < $count; $i++) {
                $isLastEntry = ($i === $count - 1);
                $hours = $isLastEntry
                    ? $remainingHours
                    : fake()->randomFloat(2, 0.5, min($remainingHours - 0.5, 8));

                TimeEntry::factory()->create([
                    'client_id' => $invoiceLine->invoice->client_id,
                    'invoice_line_id' => $invoiceLine->id,
                    'date' => $invoiceLine->date->copy()->addDays(fake()->numberBetween(0, 5)),
                    'hours' => $hours,
                ]);

                $remainingHours -= $hours;
                if ($remainingHours <= 0) {
                    break;
                }
            }
        });
    }
}
