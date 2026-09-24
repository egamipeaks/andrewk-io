<?php

use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'email' => 'admin@andrewk.io',
        'email_verified_at' => now(),
    ]);
});

describe('Invoice client project guard', function () {
    it('rejects changing the client on an invoice with a project line', function () {
        $client = Client::factory()->create();
        $otherClient = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        $invoice = Invoice::factory()->create(['client_id' => $client->id, 'paid' => false]);
        InvoiceLine::factory()->hourly()->create([
            'invoice_id' => $invoice->id,
            'project_id' => $project->id,
        ]);

        $invoice->client_id = $otherClient->id;
        $invoice->save();
    })->throws(InvalidArgumentException::class);

    it('allows changing the client on an invoice with no project lines', function () {
        $client = Client::factory()->create();
        $otherClient = Client::factory()->create();
        $invoice = Invoice::factory()->create(['client_id' => $client->id, 'paid' => false]);
        InvoiceLine::factory()->hourly()->create([
            'invoice_id' => $invoice->id,
            'project_id' => null,
        ]);

        $invoice->client_id = $otherClient->id;
        $invoice->save();

        expect($invoice->fresh()->client_id)->toBe($otherClient->id);
    });

    it('disables client_id on the Filament edit form when a project line exists', function () {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        $invoice = Invoice::factory()->create(['client_id' => $client->id, 'paid' => false]);
        InvoiceLine::factory()->hourly()->create([
            'invoice_id' => $invoice->id,
            'project_id' => $project->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditInvoice::class, ['record' => $invoice->getRouteKey()])
            ->assertFormFieldDisabled('client_id');
    });

    it('enables client_id on the Filament edit form when no project line exists', function () {
        $client = Client::factory()->create();
        $invoice = Invoice::factory()->create(['client_id' => $client->id, 'paid' => false]);
        InvoiceLine::factory()->hourly()->create([
            'invoice_id' => $invoice->id,
            'project_id' => null,
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditInvoice::class, ['record' => $invoice->getRouteKey()])
            ->assertFormFieldEnabled('client_id');
    });
});
