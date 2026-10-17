<?php

namespace App\Console\Commands;

use App\Enums\HoursUpdateFrequency;
use App\Models\Client;
use Illuminate\Console\Command;
use Throwable;

class SendHoursUpdatesCommand extends Command
{
    protected $signature = 'send-hours-updates';

    protected $description = 'Email clients an update of un-invoiced hours worked this month';

    public function handle(): int
    {
        $today = today('America/Chicago');

        $clients = Client::query()
            ->where('is_active', true)
            ->where('hours_update_frequency', '!=', HoursUpdateFrequency::Off)
            ->get()
            ->filter(fn (Client $client): bool => $client->hours_update_frequency->isDueOn($today));

        $sentCount = 0;
        $failedCount = 0;

        foreach ($clients as $client) {
            $this->info("Sending hours update to `{$client->name}`...");

            if ($client->hoursUpdateSends()->where('sent_at', '>=', $today->copy()->utc())->exists()) {
                $this->comment('Skipped, already sent today.');

                continue;
            }

            try {
                $sent = $client->sendHoursUpdate($today);
            } catch (Throwable $exception) {
                report($exception);
                $this->error("Failed: {$exception->getMessage()}");
                $failedCount++;

                continue;
            }

            if (! $sent) {
                $this->comment('Skipped, no un-invoiced hours this month.');

                continue;
            }

            $sentCount++;
        }

        $this->comment("Sent {$sentCount} of {$clients->count()} due hours updates, {$failedCount} failed.");

        return $failedCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}
