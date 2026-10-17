<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceEmailSend;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceEmailSend>
 */
class InvoiceEmailSendFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'sent_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
