<?php

use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'email' => 'admin@andrewk.io',
        'email_verified_at' => now(),
    ]);

    $this->client = Client::factory()->create([
        'email' => 'client@example.com',
    ]);
});

describe('Invoice Table Sent Column', function () {
    it('shows sent icon when invoice has been sent', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
        ]);

        // Send the invoice to create an email send record
        $invoice->emailSends()->create([
            'email' => $this->client->email,
            'sent_at' => now(),
        ]);

        expect($invoice->isSent())->toBeTrue();

        Livewire::actingAs($this->admin)
            ->test(ListInvoices::class)
            ->assertCanSeeTableRecords([$invoice])
            ->assertTableColumnStateSet('sent', true, $invoice);
    });

    it('does not show sent icon when invoice has not been sent', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
        ]);

        expect($invoice->isSent())->toBeFalse();

        Livewire::actingAs($this->admin)
            ->test(ListInvoices::class)
            ->assertCanSeeTableRecords([$invoice])
            ->assertTableColumnStateSet('sent', false, $invoice);
    });

    it('shows sent icon after invoice is sent via sendInvoiceEmail', function () {
        Mail::fake();

        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
        ]);

        expect($invoice->isSent())->toBeFalse();

        $invoice->sendInvoiceEmail();

        expect($invoice->isSent())->toBeTrue();

        Livewire::actingAs($this->admin)
            ->test(ListInvoices::class)
            ->assertCanSeeTableRecords([$invoice])
            ->assertTableColumnStateSet('sent', true, $invoice);
    });
});
