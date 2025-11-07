<?php

use App\Enums\InvoiceLineType;
use App\Models\Invoice;
use App\Models\InvoiceLine;

describe('InvoiceLine with Fixed Type', function () {
    it('calculates subtotal using amount', function () {
        $invoiceLine = InvoiceLine::factory()->fixed()->create([
            'amount' => 500,
            'hourly_rate' => null,
            'hours' => null,
        ]);

        expect($invoiceLine->subtotal)->toBe(500);
    });

    it('has type set to Fixed', function () {
        $invoiceLine = InvoiceLine::factory()->fixed()->create();

        expect($invoiceLine->type)->toBe(InvoiceLineType::Fixed);
    });

    it('includes amount in fillable attributes', function () {
        $invoice = Invoice::factory()->create();
        $invoiceLine = InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'description' => 'Fixed price project',
            'date' => now(),
            'type' => InvoiceLineType::Fixed,
            'amount' => 1000,
        ]);

        expect($invoiceLine->amount)->toBe(1000)
            ->and($invoiceLine->subtotal)->toBe(1000);
    });
});

describe('InvoiceLine with Hourly Type', function () {
    it('calculates subtotal using hourly rate and hours', function () {
        $invoiceLine = InvoiceLine::factory()->hourly()->create([
            'amount' => null,
            'hourly_rate' => 150,
            'hours' => 8,
        ]);

        expect($invoiceLine->subtotal)->toBe(1200);
    });

    it('has type set to Hourly', function () {
        $invoiceLine = InvoiceLine::factory()->hourly()->create();

        expect($invoiceLine->type)->toBe(InvoiceLineType::Hourly);
    });

    it('includes hourly_rate and hours in fillable attributes', function () {
        $invoice = Invoice::factory()->create();
        $invoiceLine = InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'description' => 'Consulting work',
            'date' => now(),
            'type' => InvoiceLineType::Hourly,
            'hourly_rate' => 125,
            'hours' => 10,
        ]);

        expect($invoiceLine->hourly_rate)->toBe(125)
            ->and($invoiceLine->hours)->toBe(10)
            ->and($invoiceLine->subtotal)->toBe(1250);
    });
});

describe('InvoiceLine Date Field', function () {
    it('stores and retrieves date correctly', function () {
        $invoiceLine = InvoiceLine::factory()->create([
            'date' => '2025-01-15',
        ]);

        expect($invoiceLine->date)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
            ->and($invoiceLine->date->format('Y-m-d'))->toBe('2025-01-15');
    });

    it('can have null date', function () {
        $invoiceLine = InvoiceLine::factory()->create([
            'date' => null,
        ]);

        expect($invoiceLine->date)->toBeNull();
    });
});

describe('InvoiceLine Subtotal Logic', function () {
    it('prioritizes amount over hourly calculation', function () {
        $invoiceLine = InvoiceLine::factory()->create([
            'amount' => 500,
            'hourly_rate' => 100,
            'hours' => 10,
        ]);

        expect($invoiceLine->subtotal)->toBe(500);
    });

    it('returns zero when no amount or hourly data', function () {
        $invoiceLine = InvoiceLine::factory()->create([
            'amount' => null,
            'hourly_rate' => null,
            'hours' => null,
        ]);

        expect($invoiceLine->subtotal)->toBe(0);
    });

    it('returns zero when only hourly_rate is set', function () {
        $invoiceLine = InvoiceLine::factory()->create([
            'amount' => null,
            'hourly_rate' => 100,
            'hours' => null,
        ]);

        expect($invoiceLine->subtotal)->toBe(0);
    });

    it('returns zero when only hours is set', function () {
        $invoiceLine = InvoiceLine::factory()->create([
            'amount' => null,
            'hourly_rate' => null,
            'hours' => 10,
        ]);

        expect($invoiceLine->subtotal)->toBe(0);
    });
});
