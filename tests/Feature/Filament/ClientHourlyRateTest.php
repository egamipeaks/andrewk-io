<?php

use App\Filament\Resources\Clients\Pages\CreateClient;
use App\Filament\Resources\Clients\Pages\EditClient;
use App\Filament\Resources\Clients\Pages\ListClients;
use App\Filament\Resources\Invoices\RelationManagers\InvoiceLinesRelationManager;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    // Create an admin user
    $this->admin = User::factory()->create([
        'email' => 'admin@andrewk.io',
        'email_verified_at' => now(),
    ]);
});

describe('Client Hourly Rate Management', function () {
    it('can create a client with hourly rate', function () {
        Livewire::actingAs($this->admin)
            ->test(CreateClient::class)
            ->fillForm([
                'name' => 'Test Client',
                'email' => 'test@example.com',
                'currency' => 'USD',
                'hourly_rate' => 150.00,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        assertDatabaseHas('clients', [
            'name' => 'Test Client',
            'email' => 'test@example.com',
            'hourly_rate' => 150.00,
        ]);
    });

    it('can update a client hourly rate', function () {
        $client = Client::factory()->create([
            'name' => 'Original Client',
            'hourly_rate' => 100.00,
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditClient::class, [
                'record' => $client->id,
            ])
            ->fillForm([
                'hourly_rate' => 175.50,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas('clients', [
            'id' => $client->id,
            'hourly_rate' => 175.50,
        ]);
    });

    it('displays hourly rate in client table', function () {
        $client = Client::factory()->create([
            'name' => 'Rate Display Client',
            'hourly_rate' => 125.75,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListClients::class)
            ->assertCanSeeTableRecords([$client])
            ->assertTableColumnFormattedStateSet('hourly_rate', '$125.75', $client);
    });

    it('allows nullable hourly rate', function () {
        Livewire::actingAs($this->admin)
            ->test(CreateClient::class)
            ->fillForm([
                'name' => 'No Rate Client',
                'email' => 'norate@example.com',
                'currency' => 'USD',
                'hourly_rate' => null,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        assertDatabaseHas('clients', [
            'name' => 'No Rate Client',
            'email' => 'norate@example.com',
            'hourly_rate' => null,
        ]);
    });
});

describe('Invoice Line Management', function () {
    it('can access invoice lines relation manager', function () {
        // Create a client with a specific hourly rate
        $client = Client::factory()->create([
            'name' => 'Test Client',
            'hourly_rate' => 150.00,
        ]);

        // Create an invoice for this client
        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
        ]);

        // Test that the relation manager loads properly
        Livewire::actingAs($this->admin)
            ->test(InvoiceLinesRelationManager::class, [
                'ownerRecord' => $invoice,
                'pageClass' => \App\Filament\Resources\Invoices\Pages\EditInvoice::class,
            ])
            ->assertOk();
    });

    it('can create invoice lines with rates', function () {
        // Create a client with hourly rate
        $client = Client::factory()->create([
            'hourly_rate' => 200.00,
        ]);

        // Create an invoice
        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
        ]);

        // Manually create an invoice line to test the functionality
        $invoiceLine = $invoice->invoiceLines()->create([
            'description' => 'Development work',
            'hourly_rate' => 175.00,
            'hours' => 8,
        ]);

        assertDatabaseHas('invoice_lines', [
            'invoice_id' => $invoice->id,
            'description' => 'Development work',
            'hourly_rate' => 175.00,
            'hours' => 8,
        ]);

        // Test that the subtotal calculation works
        expect($invoiceLine->subtotal)->toBe(1400.00);
    });
});
