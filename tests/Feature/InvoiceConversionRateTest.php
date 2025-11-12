<?php

use App\Enums\Currency;
use App\Enums\InvoiceLineType;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceLine;

describe('Currency Conversion Rate', function () {
    it('sets conversion_rate to 1.0 for USD invoices', function () {
        $client = Client::factory()->create([
            'currency' => Currency::USD,
        ]);

        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'currency' => Currency::USD,
            'conversion_rate' => Currency::USD->fromUsdRate(),
        ]);

        expect($invoice->conversion_rate)->toBe(1.0);
    });

    it('sets conversion_rate to approximately 1.408 for CAD invoices', function () {
        $client = Client::factory()->create([
            'currency' => Currency::CAD,
        ]);

        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'currency' => Currency::CAD,
            'conversion_rate' => Currency::CAD->fromUsdRate(),
        ]);

        expect($invoice->conversion_rate)->toBeGreaterThan(1.4);
        expect($invoice->conversion_rate)->toBeLessThan(1.42);
    });

    it('converts USD invoice total to client currency (USD)', function () {
        $invoice = Invoice::factory()->create([
            'currency' => Currency::USD,
            'conversion_rate' => 1.0,
        ]);

        InvoiceLine::factory()->create([
            'invoice_id' => $invoice->id,
            'type' => InvoiceLineType::Fixed,
            'amount' => 500.00,
        ]);

        expect($invoice->fresh()->totalInClientCurrency())->toBe(500.0);
        expect($invoice->fresh()->formattedTotalInClientCurrency())->toBe('$500');
    });

    it('converts USD invoice total to client currency (CAD)', function () {
        $invoice = Invoice::factory()->create([
            'currency' => Currency::CAD,
            'conversion_rate' => 1.408,
        ]);

        InvoiceLine::factory()->create([
            'invoice_id' => $invoice->id,
            'type' => InvoiceLineType::Fixed,
            'amount' => 500.00,
        ]);

        expect($invoice->fresh()->totalInClientCurrency())->toBe(704.0);
        expect($invoice->fresh()->formattedTotalInClientCurrency())->toBe('C$704');
    });

    it('converts invoice line hourly rate to client currency', function () {
        $invoice = Invoice::factory()->create([
            'currency' => Currency::CAD,
            'conversion_rate' => 1.408,
        ]);

        $line = InvoiceLine::factory()->create([
            'invoice_id' => $invoice->id,
            'type' => InvoiceLineType::Hourly,
            'hourly_rate' => 100.00,
            'hours' => 5,
        ]);

        expect($line->hourlyRateInClientCurrency())->toBe(140.8);
        expect($line->formattedHourlyRate())->toBe('C$140.80');
    });

    it('converts invoice line subtotal to client currency', function () {
        $invoice = Invoice::factory()->create([
            'currency' => Currency::CAD,
            'conversion_rate' => 1.408,
        ]);

        $line = InvoiceLine::factory()->hourly()->create([
            'invoice_id' => $invoice->id,
            'hourly_rate' => 100.00,
            'hours' => 5,
        ]);

        expect($line->subtotalInClientCurrency())->toBe(704.0);
        expect($line->formattedSubTotal())->toBe('C$704');
    });

    it('converts fixed amount invoice line to client currency', function () {
        $invoice = Invoice::factory()->create([
            'currency' => Currency::CAD,
            'conversion_rate' => 1.408,
        ]);

        $line = InvoiceLine::factory()->fixed()->create([
            'invoice_id' => $invoice->id,
            'amount' => 1000.00,
        ]);

        expect($line->amountInClientCurrency())->toBe(1408.0);
        expect($line->subtotalInClientCurrency())->toBe(1408.0);
        expect($line->formattedSubTotal())->toBe('C$1,408');
    });

    it('uses locked conversion_rate for historical accuracy', function () {
        $invoice1 = Invoice::factory()->create([
            'currency' => Currency::CAD,
            'conversion_rate' => 1.40,
        ]);

        InvoiceLine::factory()->create([
            'invoice_id' => $invoice1->id,
            'type' => InvoiceLineType::Fixed,
            'amount' => 100.00,
        ]);

        $invoice2 = Invoice::factory()->create([
            'currency' => Currency::CAD,
            'conversion_rate' => 1.408,
        ]);

        InvoiceLine::factory()->create([
            'invoice_id' => $invoice2->id,
            'type' => InvoiceLineType::Fixed,
            'amount' => 100.00,
        ]);

        expect($invoice1->fresh()->totalInClientCurrency())->toBe(140.0);
        expect($invoice2->fresh()->totalInClientCurrency())->toBe(140.8);
    });

    it('falls back to current rate when conversion_rate is null', function () {
        $invoice = Invoice::factory()->create([
            'currency' => Currency::CAD,
            'conversion_rate' => null,
        ]);

        InvoiceLine::factory()->create([
            'invoice_id' => $invoice->id,
            'type' => InvoiceLineType::Fixed,
            'amount' => 100.00,
        ]);

        $expectedRate = Currency::CAD->fromUsdRate();
        $expectedTotal = round(100.00 * $expectedRate, 2);

        expect($invoice->fresh()->totalInClientCurrency())->toBe($expectedTotal);
    });

    it('stores invoice line amounts in USD but displays in client currency', function () {
        $invoice = Invoice::factory()->create([
            'currency' => Currency::CAD,
            'conversion_rate' => 1.408,
        ]);

        $line = InvoiceLine::factory()->create([
            'invoice_id' => $invoice->id,
            'type' => InvoiceLineType::Hourly,
            'hourly_rate' => 100.00,
            'hours' => 1,
            'amount' => null,
        ]);

        expect($line->hourly_rate)->toBe(100.0);
        expect($line->subtotal)->toBe(100.0);

        expect($line->hourlyRateInClientCurrency())->toBe(140.8);
        expect($line->subtotalInClientCurrency())->toBe(140.8);
    });

    it('totalUsd returns the USD amount directly', function () {
        $invoice = Invoice::factory()->create([
            'currency' => Currency::CAD,
            'conversion_rate' => 1.408,
        ]);

        InvoiceLine::factory()->create([
            'invoice_id' => $invoice->id,
            'type' => InvoiceLineType::Fixed,
            'amount' => 500.00,
        ]);

        expect($invoice->fresh()->totalUsd())->toBe(500.0);
        expect($invoice->fresh()->formattedTotalUsd())->toBe('$500');
    });
});

describe('Currency Enum Methods', function () {
    it('fromUsdRate returns 1.0 for USD', function () {
        expect(Currency::USD->fromUsdRate())->toBe(1.0);
    });

    it('fromUsdRate returns approximately 1.408 for CAD', function () {
        $rate = Currency::CAD->fromUsdRate();
        expect($rate)->toBeGreaterThan(1.4);
        expect($rate)->toBeLessThan(1.42);
    });

    it('fromUsd converts USD amount to client currency', function () {
        expect(Currency::USD->fromUsd(100))->toBe(100.0);
        expect(Currency::CAD->fromUsd(100))->toBeGreaterThan(140);
    });
});
