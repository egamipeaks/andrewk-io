<?php

use App\Filament\Resources\ClientResource\Pages\ListClients;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\TimeEntry;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'email' => 'admin@andrewk.io',
        'email_verified_at' => now(),
    ]);
});

describe('Client Unbilled Hours Column', function () {
    it('displays unbilled hours correctly', function () {
        $client = Client::factory()->create(['hourly_rate' => 150]);

        TimeEntry::factory()->unbilled()->create([
            'client_id' => $client->id,
            'hours' => 8.5,
        ]);

        TimeEntry::factory()->unbilled()->create([
            'client_id' => $client->id,
            'hours' => 4.0,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListClients::class)
            ->assertCanSeeTableRecords([$client]);

        expect($client->timeEntries()->unbilled()->sum('hours'))->toBe(12.5);
    });

    it('shows zero for clients with no unbilled hours', function () {
        $client = Client::factory()->create(['hourly_rate' => 150]);

        Livewire::actingAs($this->admin)
            ->test(ListClients::class)
            ->assertCanSeeTableRecords([$client]);

        expect($client->timeEntries()->unbilled()->sum('hours'))->toBe(0);
    });

    it('excludes billed hours from unbilled hours column', function () {
        $client = Client::factory()->create(['hourly_rate' => 150]);

        TimeEntry::factory()->unbilled()->create([
            'client_id' => $client->id,
            'hours' => 8.0,
        ]);

        TimeEntry::factory()->billed()->create([
            'client_id' => $client->id,
            'hours' => 5.0,
        ]);

        expect($client->timeEntries()->unbilled()->sum('hours'))->toBe(8);
    });
});

describe('Client Unbilled Revenue Column', function () {
    it('calculates unbilled revenue correctly', function () {
        $client = Client::factory()->create(['hourly_rate' => 150]);

        TimeEntry::factory()->unbilled()->create([
            'client_id' => $client->id,
            'hours' => 8.0,
        ]);

        TimeEntry::factory()->unbilled()->create([
            'client_id' => $client->id,
            'hours' => 4.0,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListClients::class)
            ->assertCanSeeTableRecords([$client]);

        $hours = $client->timeEntries()->unbilled()->sum('hours');
        $revenue = $hours * $client->hourly_rate;

        expect($revenue)->toBe(1800);
    });

    it('handles clients with no hourly rate', function () {
        $client = Client::factory()->create(['hourly_rate' => null]);

        TimeEntry::factory()->unbilled()->create([
            'client_id' => $client->id,
            'hours' => 8.0,
        ]);

        $hours = $client->timeEntries()->unbilled()->sum('hours');
        $revenue = $hours * ($client->hourly_rate ?? 0);

        expect($revenue)->toBe(0);
    });

    it('shows zero revenue for clients with no unbilled hours', function () {
        $client = Client::factory()->create(['hourly_rate' => 150]);

        $hours = $client->timeEntries()->unbilled()->sum('hours');
        $revenue = $hours * ($client->hourly_rate ?? 0);

        expect($revenue)->toBe(0);
    });

    it('excludes billed entries from revenue calculation', function () {
        $client = Client::factory()->create(['hourly_rate' => 150]);

        TimeEntry::factory()->unbilled()->create([
            'client_id' => $client->id,
            'hours' => 10.0,
        ]);

        TimeEntry::factory()->billed()->create([
            'client_id' => $client->id,
            'hours' => 20.0,
        ]);

        $hours = $client->timeEntries()->unbilled()->sum('hours');
        $revenue = $hours * $client->hourly_rate;

        expect($hours)->toBe(10)
            ->and($revenue)->toBe(1500);
    });
});

describe('Client Unbilled Columns Update', function () {
    it('updates when time entry is billed', function () {
        $client = Client::factory()->create(['hourly_rate' => 150]);

        $entry = TimeEntry::factory()->unbilled()->create([
            'client_id' => $client->id,
            'hours' => 8.0,
        ]);

        expect($client->timeEntries()->unbilled()->sum('hours'))->toBe(8);

        // Simulate billing the entry
        $invoiceLine = InvoiceLine::factory()->hourly()->create([
            'invoice_id' => Invoice::factory()->create(['client_id' => $client->id])->id,
        ]);

        $entry->update(['invoice_line_id' => $invoiceLine->id]);

        expect($client->timeEntries()->unbilled()->sum('hours'))->toBe(0);
    });

    it('updates when time entry is unlinked', function () {
        $client = Client::factory()->create(['hourly_rate' => 150]);

        $entry = TimeEntry::factory()->billed()->create([
            'client_id' => $client->id,
            'hours' => 8.0,
        ]);

        expect($client->timeEntries()->unbilled()->sum('hours'))->toBe(0);

        // Unlink the entry
        $entry->update(['invoice_line_id' => null]);

        expect($client->timeEntries()->unbilled()->sum('hours'))->toBe(8);
    });
});
