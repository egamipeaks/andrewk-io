<?php

use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    // Create an admin user
    $this->admin = User::factory()->create([
        'email' => 'admin@andrewk.io',
        'email_verified_at' => now(),
    ]);
});

describe('Invoice Resource Client Filter', function () {
    it('can filter invoices by client', function () {
        // Create multiple clients
        $client1 = Client::factory()->create(['name' => 'Client One']);
        $client2 = Client::factory()->create(['name' => 'Client Two']);
        $client3 = Client::factory()->create(['name' => 'Client Three']);

        // Create invoices for different clients
        $invoice1 = Invoice::factory()->create(['client_id' => $client1->id]);
        $invoice2 = Invoice::factory()->create(['client_id' => $client2->id]);
        $invoice3 = Invoice::factory()->create(['client_id' => $client1->id]);
        $invoice4 = Invoice::factory()->create(['client_id' => $client3->id]);

        // Test filtering by client1
        Livewire::actingAs($this->admin)
            ->test(ListInvoices::class)
            ->assertCanSeeTableRecords([$invoice1, $invoice2, $invoice3, $invoice4])
            ->filterTable('client', $client1->id)
            ->assertCanSeeTableRecords([$invoice1, $invoice3])
            ->assertCanNotSeeTableRecords([$invoice2, $invoice4]);
    });

    it('can filter invoices by different clients', function () {
        // Create clients
        $client1 = Client::factory()->create(['name' => 'Alpha Client']);
        $client2 = Client::factory()->create(['name' => 'Beta Client']);

        // Create invoices
        $invoice1 = Invoice::factory()->create(['client_id' => $client1->id]);
        $invoice2 = Invoice::factory()->create(['client_id' => $client2->id]);

        // Test filtering by client2
        Livewire::actingAs($this->admin)
            ->test(ListInvoices::class)
            ->filterTable('client', $client2->id)
            ->assertCanSeeTableRecords([$invoice2])
            ->assertCanNotSeeTableRecords([$invoice1]);
    });

    it('shows all invoices when no client filter is applied', function () {
        // Create clients and invoices
        $client1 = Client::factory()->create();
        $client2 = Client::factory()->create();

        $invoice1 = Invoice::factory()->create(['client_id' => $client1->id]);
        $invoice2 = Invoice::factory()->create(['client_id' => $client2->id]);

        // Test no filter shows all
        Livewire::actingAs($this->admin)
            ->test(ListInvoices::class)
            ->assertCanSeeTableRecords([$invoice1, $invoice2]);
    });

    it('can clear client filter to show all invoices', function () {
        // Create test data
        $client1 = Client::factory()->create(['name' => 'Filtered Client']);
        $client2 = Client::factory()->create(['name' => 'Other Client']);

        $invoice1 = Invoice::factory()->create(['client_id' => $client1->id]);
        $invoice2 = Invoice::factory()->create(['client_id' => $client2->id]);

        // Apply filter then clear it
        Livewire::actingAs($this->admin)
            ->test(ListInvoices::class)
            ->filterTable('client', $client1->id)
            ->assertCanSeeTableRecords([$invoice1])
            ->assertCanNotSeeTableRecords([$invoice2])
            ->filterTable('client', null) // Clear filter
            ->assertCanSeeTableRecords([$invoice1, $invoice2]);
    });
});
