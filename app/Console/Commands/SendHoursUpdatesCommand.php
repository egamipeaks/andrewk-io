<?php

namespace App\Console\Commands;

use App\Enums\HoursUpdateFrequency;
use App\Models\Client;
use Illuminate\Console\Command;

class SendHoursUpdatesCommand extends Command
{
    protected $signature = 'send-hours-updates';

    protected $description = 'Email clients an update of un-invoiced hours worked this month';

    public function handle(): void
    {
        $today = today('America/Chicago');

        $clients = Client::query()
            ->where('is_active', true)
            ->where('hours_update_frequency', '!=', HoursUpdateFrequency::Off)
            ->get()
            ->filter(fn (Client $client): bool => $client->hours_update_frequency->isDueOn($today));

        $sentCount = 0;

        $clients->each(function (Client $client) use ($today, &$sentCount): void {
            $this->info("Sending hours update to `{$client->name}`...");

            if (! $client->sendHoursUpdate($today)) {
                $this->comment('Skipped, no un-invoiced hours this month.');

                return;
            }

            $sentCount++;
        });

        $this->comment("Sent {$sentCount} of {$clients->count()} due hours updates.");
    }
}
