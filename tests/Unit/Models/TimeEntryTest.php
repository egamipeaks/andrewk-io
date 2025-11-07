<?php

use App\Models\Client;
use App\Models\InvoiceLine;
use App\Models\TimeEntry;

describe('TimeEntry Model', function () {
    it('has correct fillable attributes', function () {
        $timeEntry = new TimeEntry;
        expect($timeEntry->getFillable())->toBe([
            'client_id',
            'invoice_line_id',
            'date',
            'hours',
            'description',
        ]);
    });

    it('casts date correctly', function () {
        $timeEntry = TimeEntry::factory()->create([
            'date' => '2025-01-15',
        ]);

        expect($timeEntry->date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    });

    it('casts hours as decimal', function () {
        $timeEntry = TimeEntry::factory()->create([
            'hours' => 8.5,
        ]);

        expect($timeEntry->hours)->toBeString()
            ->and((float) $timeEntry->hours)->toBe(8.5);
    });

    it('belongs to a client', function () {
        $client = Client::factory()->create();
        $timeEntry = TimeEntry::factory()->create(['client_id' => $client->id]);

        expect($timeEntry->client)->toBeInstanceOf(Client::class)
            ->and($timeEntry->client->id)->toBe($client->id);
    });

    it('belongs to an invoice line', function () {
        $invoiceLine = InvoiceLine::factory()->create();
        $timeEntry = TimeEntry::factory()->create(['invoice_line_id' => $invoiceLine->id]);

        expect($timeEntry->invoiceLine)->toBeInstanceOf(InvoiceLine::class)
            ->and($timeEntry->invoiceLine->id)->toBe($invoiceLine->id);
    });

    it('can have null invoice line', function () {
        $timeEntry = TimeEntry::factory()->unbilled()->create();

        expect($timeEntry->invoice_line_id)->toBeNull()
            ->and($timeEntry->invoiceLine)->toBeNull();
    });
});

describe('TimeEntry Scopes', function () {
    it('filters unbilled entries', function () {
        TimeEntry::factory()->count(3)->unbilled()->create();
        TimeEntry::factory()->count(2)->billed()->create();

        $unbilled = TimeEntry::unbilled()->get();

        expect($unbilled)->toHaveCount(3);
    });

    it('filters billed entries', function () {
        TimeEntry::factory()->count(3)->unbilled()->create();
        TimeEntry::factory()->count(2)->billed()->create();

        $billed = TimeEntry::billed()->get();

        expect($billed)->toHaveCount(2);
    });

    it('filters by month', function () {
        TimeEntry::factory()->create(['date' => '2025-01-15']);
        TimeEntry::factory()->create(['date' => '2025-01-20']);
        TimeEntry::factory()->create(['date' => '2025-02-10']);

        $january = TimeEntry::forMonth(2025, 1)->get();

        expect($january)->toHaveCount(2);
    });

    it('filters by date range', function () {
        TimeEntry::factory()->create(['date' => '2025-01-10']);
        TimeEntry::factory()->create(['date' => '2025-01-15']);
        TimeEntry::factory()->create(['date' => '2025-01-25']);

        $entries = TimeEntry::forDateRange('2025-01-12', '2025-01-20')->get();

        expect($entries)->toHaveCount(1);
    });
});

describe('TimeEntry Attributes', function () {
    it('calculates isBilled correctly for unbilled entry', function () {
        $timeEntry = TimeEntry::factory()->unbilled()->create();

        expect($timeEntry->is_billed)->toBeFalse();
    });

    it('calculates isBilled correctly for billed entry', function () {
        $timeEntry = TimeEntry::factory()->billed()->create();

        expect($timeEntry->is_billed)->toBeTrue();
    });

    it('calculates value based on client hourly rate', function () {
        $client = Client::factory()->create(['hourly_rate' => 100]);
        $timeEntry = TimeEntry::factory()->create([
            'client_id' => $client->id,
            'hours' => 5,
        ]);

        expect($timeEntry->value)->toBe(500.0);
    });

    it('handles null client hourly rate', function () {
        $client = Client::factory()->create(['hourly_rate' => null]);
        $timeEntry = TimeEntry::factory()->create([
            'client_id' => $client->id,
            'hours' => 5,
        ]);

        expect($timeEntry->value)->toBe(0.0);
    });
});
