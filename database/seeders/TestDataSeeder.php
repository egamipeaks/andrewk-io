<?php

namespace Database\Seeders;

use App\Enums\Currency;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\TimeEntry;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $clientCount = 8;

        $billableClientCount = (int) ($clientCount * 0.85);

        $billableClients = Client::factory()
            ->count($billableClientCount)
            ->withHourlyRate()
            ->create();

        // Create pro bono clients (15% without hourly rates)
        $proBonoClientCount = $clientCount - $billableClientCount;

        if ($proBonoClientCount > 0) {
            Client::factory()
                ->count($proBonoClientCount)
                ->withoutHourlyRate()
                ->create();
        }

        // For each billable client, create invoices and time entries
        $billableClients->each(function (Client $client) {
            $paidCount = fake()->numberBetween(0, 4);

            if ($paidCount > 0) {
                Invoice::factory()
                    ->count($paidCount)
                    ->paid()
                    ->for($client)
                    ->state(fn () => [
                        'currency' => $client->currency,
                        'conversion_rate' => $client->currency === Currency::CAD
                            ? fake()->randomFloat(2, 1.35, 1.45)
                            : null,
                    ])
                    ->has(
                        InvoiceLine::factory()
                            ->count(fake()->numberBetween(1, 6))
                            ->state(function () use ($client) {
                                // 70% hourly, 30% fixed
                                return fake()->boolean(70)
                                    ? ['type' => 'hourly', 'hourly_rate' => $client->hourly_rate]
                                    : ['type' => 'fixed'];
                            }),
                        'invoiceLines'
                    )
                    ->create();
            }

            $unpaidCount = fake()->numberBetween(0, 2);

            if ($unpaidCount > 0) {
                for ($i = 0; $i < $unpaidCount; $i++) {
                    $invoiceFactory = fake()->boolean(40)
                        ? Invoice::factory()->overdue()
                        : Invoice::factory()->unpaid();

                    $invoice = $invoiceFactory
                        ->for($client)
                        ->state(fn () => [
                            'currency' => $client->currency,
                            'conversion_rate' => $client->currency === Currency::CAD
                                ? fake()->randomFloat(2, 1.35, 1.45)
                                : null,
                        ])
                        ->create();

                    // Create 1-8 invoice lines per unpaid invoice
                    $lineCount = fake()->numberBetween(1, 8);

                    for ($j = 0; $j < $lineCount; $j++) {
                        $isHourly = fake()->boolean(75);

                        if ($isHourly) {
                            // 60% chance to include linked time entries
                            $lineFactory = InvoiceLine::factory()
                                ->hourly()
                                ->state(['hourly_rate' => $client->hourly_rate]);

                            if (fake()->boolean(60)) {
                                $lineFactory = $lineFactory->withTimeEntries(fake()->numberBetween(1, 4));
                            }

                            $lineFactory->for($invoice)->create();
                        } else {
                            InvoiceLine::factory()
                                ->fixed()
                                ->for($invoice)
                                ->create();
                        }
                    }
                }
            }

            // Create unbilled time entries for billable clients
            // Last month entries (3-8)
            TimeEntry::factory()
                ->count(fake()->numberBetween(3, 8))
                ->unbilled()
                ->lastMonth()
                ->for($client)
                ->create();

            // Current month entries (4-12)
            TimeEntry::factory()
                ->count(fake()->numberBetween(4, 12))
                ->unbilled()
                ->currentMonth()
                ->for($client)
                ->create();

            // Occasional older unbilled entries (40% chance for 1-3 entries)
            if (fake()->boolean(40)) {
                TimeEntry::factory()
                    ->count(fake()->numberBetween(1, 3))
                    ->unbilled()
                    ->older()
                    ->for($client)
                    ->create();
            }
        });
    }
}
