<?php

use App\Enums\Currency;
use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Mail\InvoiceEmail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'email' => 'admin@andrewk.io',
        'email_verified_at' => now(),
    ]);

    $this->client = Client::factory()->create();
});

describe('Invoice Currency Model', function () {
    it('defaults to USD currency', function () {
        $invoice = new Invoice;
        $invoice->client_id = $this->client->id;
        $invoice->paid = false;
        $invoice->due_date = now()->addDays(30);
        $invoice->save();

        expect($invoice->fresh()->currency)->toBe(Currency::USD);
    });

    it('can be created with CAD currency', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'currency' => Currency::CAD,
        ]);

        expect($invoice->currency)->toBe(Currency::CAD);
        assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'currency' => 'CAD',
        ]);
    });

    it('formats total with USD currency', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'currency' => Currency::USD,
        ]);

        InvoiceLine::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 1500.50,
        ]);

        expect($invoice->formattedTotal())->toBe('$1,500.50');
    });

    it('formats total with CAD currency', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'currency' => Currency::CAD,
        ]);

        InvoiceLine::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 1500.50,
        ]);

        expect($invoice->formattedTotal())->toBe('C$1,500.50');
    });

    it('converts USD invoice total to USD without change', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'currency' => Currency::USD,
        ]);

        InvoiceLine::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 1000.00,
        ]);

        expect($invoice->totalUsd())->toBe(1000.0);
        expect($invoice->formattedTotalUsd())->toBe('$1,000');
    });

    it('converts CAD invoice total to USD', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'currency' => Currency::CAD,
        ]);

        InvoiceLine::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 1000.00,
        ]);

        // 1000 CAD × 0.71 = 710 USD
        expect($invoice->totalUsd())->toBe(710.0);
        expect($invoice->formattedTotalUsd())->toBe('$710');
    });

    it('converts CAD invoice with decimal total to USD', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'currency' => Currency::CAD,
        ]);

        InvoiceLine::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 1500.50,
        ]);

        // 1500.50 CAD × 0.71 = 1065.355 → rounds to 1065.36 USD
        expect($invoice->totalUsd())->toBe(1065.36);
        expect($invoice->formattedTotalUsd())->toBe('$1,065.36');
    });
});

describe('Invoice Line Currency Formatting', function () {
    it('formats subtotal using invoice currency USD', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'currency' => Currency::USD,
        ]);

        $line = InvoiceLine::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => null,
            'hourly_rate' => 100,
            'hours' => 5,
        ]);

        expect($line->formattedSubTotal())->toBe('$500');
        expect($line->formattedHourlyRate())->toBe('$100');
    });

    it('formats subtotal using invoice currency CAD', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'currency' => Currency::CAD,
        ]);

        $line = InvoiceLine::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => null,
            'hourly_rate' => 100,
            'hours' => 5,
        ]);

        expect($line->formattedSubTotal())->toBe('C$500');
        expect($line->formattedHourlyRate())->toBe('C$100');
    });
});

describe('Filament Currency Management', function () {
    it('can create invoice with USD currency', function () {
        Livewire::actingAs($this->admin)
            ->test(CreateInvoice::class)
            ->fillForm([
                'client_id' => $this->client->id,
                'currency' => Currency::USD->value,
                'paid' => false,
                'due_date' => now()->addDays(30)->format('Y-m-d'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        assertDatabaseHas('invoices', [
            'client_id' => $this->client->id,
            'currency' => 'USD',
        ]);
    });

    it('can create invoice with CAD currency', function () {
        Livewire::actingAs($this->admin)
            ->test(CreateInvoice::class)
            ->fillForm([
                'client_id' => $this->client->id,
                'currency' => Currency::CAD->value,
                'paid' => false,
                'due_date' => now()->addDays(30)->format('Y-m-d'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        assertDatabaseHas('invoices', [
            'client_id' => $this->client->id,
            'currency' => 'CAD',
        ]);
    });

    it('can edit invoice currency', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'currency' => Currency::USD,
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditInvoice::class, [
                'record' => $invoice->id,
            ])
            ->fillForm([
                'currency' => Currency::CAD->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'currency' => 'CAD',
        ]);
    });
});

describe('Currency Enum', function () {
    it('has correct USD properties', function () {
        $usd = Currency::USD;

        expect($usd->value)->toBe('USD');
        expect($usd->symbol())->toBe('$');
        expect($usd->label())->toBe('US Dollar');
        expect($usd->format(1234.56))->toBe('$1,234.56');
    });

    it('has correct CAD properties', function () {
        $cad = Currency::CAD;

        expect($cad->value)->toBe('CAD');
        expect($cad->symbol())->toBe('C$');
        expect($cad->label())->toBe('Canadian Dollar');
        expect($cad->format(1234.56))->toBe('C$1,234.56');
    });
});

describe('Invoice Email with Currency', function () {
    it('sends email with correct currency formatting', function () {
        Mail::fake();

        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'currency' => Currency::CAD,
        ]);

        InvoiceLine::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => null,
            'hourly_rate' => 150,
            'hours' => 10,
        ]);

        $invoice->sendInvoiceEmail();

        Mail::assertSent(InvoiceEmail::class, function ($mail) use ($invoice) {
            return $mail->invoice->id === $invoice->id;
        });

        expect($invoice->formattedTotal())->toBe('C$1,500');
    });
});
