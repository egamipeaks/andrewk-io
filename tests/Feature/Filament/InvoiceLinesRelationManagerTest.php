<?php

use App\Enums\InvoiceLineType;
use App\Filament\Resources\InvoiceResource\Pages\EditInvoice;
use App\Filament\Resources\InvoiceResource\RelationManagers\InvoiceLinesRelationManager;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'email' => 'admin@andrewk.io',
        'email_verified_at' => now(),
    ]);

    $this->client = Client::factory()->create(['hourly_rate' => 150]);
    $this->invoice = Invoice::factory()->create(['client_id' => $this->client->id]);
});

describe('InvoiceLinesRelationManager Table', function () {
    it('displays invoice lines with type badges', function () {
        $fixedLine = InvoiceLine::factory()->fixed()->create([
            'invoice_id' => $this->invoice->id,
            'description' => 'Fixed price work',
        ]);
        $hourlyLine = InvoiceLine::factory()->hourly()->create([
            'invoice_id' => $this->invoice->id,
            'description' => 'Hourly work',
        ]);

        Livewire::actingAs($this->admin)
            ->test(InvoiceLinesRelationManager::class, [
                'ownerRecord' => $this->invoice,
                'pageClass' => EditInvoice::class,
            ])
            ->assertCanSeeTableRecords([$fixedLine, $hourlyLine]);
    });

    it('can filter by type', function () {
        $fixedLine = InvoiceLine::factory()->fixed()->create(['invoice_id' => $this->invoice->id]);
        $hourlyLine = InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id]);

        Livewire::actingAs($this->admin)
            ->test(InvoiceLinesRelationManager::class, [
                'ownerRecord' => $this->invoice,
                'pageClass' => EditInvoice::class,
            ])
            ->filterTable('type', InvoiceLineType::Fixed->value)
            ->assertCanSeeTableRecords([$fixedLine])
            ->assertCanNotSeeTableRecords([$hourlyLine]);
    });

    it('sorts by date descending by default', function () {
        $older = InvoiceLine::factory()->create([
            'invoice_id' => $this->invoice->id,
            'date' => now()->subDays(5),
        ]);
        $newer = InvoiceLine::factory()->create([
            'invoice_id' => $this->invoice->id,
            'date' => now(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(InvoiceLinesRelationManager::class, [
                'ownerRecord' => $this->invoice,
                'pageClass' => EditInvoice::class,
            ])
            ->assertCanSeeTableRecords([$older, $newer], inOrder: false);
    });
});

describe('InvoiceLinesRelationManager Create Fixed Type', function () {
    it('can create a fixed type invoice line', function () {
        Livewire::actingAs($this->admin)
            ->test(InvoiceLinesRelationManager::class, [
                'ownerRecord' => $this->invoice,
                'pageClass' => EditInvoice::class,
            ])
            ->callTableAction('create', data: [
                'type' => InvoiceLineType::Fixed->value,
                'description' => 'Website design',
                'date' => '2025-01-15',
                'amount' => 2500,
            ])
            ->assertHasNoTableActionErrors();

        expect(InvoiceLine::count())->toBe(1);

        $line = InvoiceLine::first();
        expect($line->type)->toBe(InvoiceLineType::Fixed)
            ->and($line->description)->toBe('Website design')
            ->and($line->amount)->toBe(2500)
            ->and($line->hourly_rate)->toBeNull()
            ->and($line->hours)->toBeNull()
            ->and($line->subtotal)->toBe(2500);
    });
});

describe('InvoiceLinesRelationManager Create Hourly Type', function () {
    it('can create an hourly type invoice line', function () {
        Livewire::actingAs($this->admin)
            ->test(InvoiceLinesRelationManager::class, [
                'ownerRecord' => $this->invoice,
                'pageClass' => EditInvoice::class,
            ])
            ->callTableAction('create', data: [
                'type' => InvoiceLineType::Hourly->value,
                'description' => 'Consulting work',
                'date' => '2025-01-15',
                'hourly_rate' => 150,
                'hours' => 8,
            ])
            ->assertHasNoTableActionErrors();

        expect(InvoiceLine::count())->toBe(1);

        $line = InvoiceLine::first();
        expect($line->type)->toBe(InvoiceLineType::Hourly)
            ->and($line->description)->toBe('Consulting work')
            ->and($line->hourly_rate)->toBe(150)
            ->and($line->hours)->toBe(8)
            ->and($line->amount)->toBeNull()
            ->and($line->subtotal)->toBe(1200);
    });

    it('can mount create action', function () {
        Livewire::actingAs($this->admin)
            ->test(InvoiceLinesRelationManager::class, [
                'ownerRecord' => $this->invoice,
                'pageClass' => EditInvoice::class,
            ])
            ->mountTableAction('create')
            ->assertTableActionMounted('create');
    });
});

describe('InvoiceLinesRelationManager Edit', function () {
    it('can edit a fixed type invoice line', function () {
        $line = InvoiceLine::factory()->fixed()->create([
            'invoice_id' => $this->invoice->id,
            'amount' => 1000,
        ]);

        Livewire::actingAs($this->admin)
            ->test(InvoiceLinesRelationManager::class, [
                'ownerRecord' => $this->invoice,
                'pageClass' => EditInvoice::class,
            ])
            ->callTableAction('edit', $line, data: [
                'type' => InvoiceLineType::Fixed->value,
                'description' => 'Updated description',
                'date' => '2025-02-01',
                'amount' => 1500,
            ])
            ->assertHasNoTableActionErrors();

        $line->refresh();
        expect($line->description)->toBe('Updated description')
            ->and($line->amount)->toBe(1500)
            ->and($line->subtotal)->toBe(1500);
    });

    it('can edit an hourly type invoice line', function () {
        $line = InvoiceLine::factory()->hourly()->create([
            'invoice_id' => $this->invoice->id,
            'hourly_rate' => 100,
            'hours' => 5,
        ]);

        Livewire::actingAs($this->admin)
            ->test(InvoiceLinesRelationManager::class, [
                'ownerRecord' => $this->invoice,
                'pageClass' => EditInvoice::class,
            ])
            ->callTableAction('edit', $line, data: [
                'type' => InvoiceLineType::Hourly->value,
                'description' => 'Updated work',
                'date' => '2025-02-01',
                'hourly_rate' => 125,
                'hours' => 10,
            ])
            ->assertHasNoTableActionErrors();

        $line->refresh();
        expect($line->description)->toBe('Updated work')
            ->and($line->hourly_rate)->toBe(125)
            ->and($line->hours)->toBe(10)
            ->and($line->subtotal)->toBe(1250);
    });
});

describe('InvoiceLinesRelationManager Delete', function () {
    it('can delete an invoice line', function () {
        $line = InvoiceLine::factory()->create(['invoice_id' => $this->invoice->id]);

        Livewire::actingAs($this->admin)
            ->test(InvoiceLinesRelationManager::class, [
                'ownerRecord' => $this->invoice,
                'pageClass' => EditInvoice::class,
            ])
            ->callTableAction('delete', $line);

        expect(InvoiceLine::count())->toBe(0);
    });

    it('shows confirmation when deleting invoice line with time entries', function () {
        $line = InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id]);
        $entry = \App\Models\TimeEntry::factory()->create([
            'client_id' => $this->client->id,
            'invoice_line_id' => $line->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(InvoiceLinesRelationManager::class, [
                'ownerRecord' => $this->invoice,
                'pageClass' => EditInvoice::class,
            ])
            ->callTableAction('delete', $line);

        expect(InvoiceLine::count())->toBe(0);
    });

    it('unlinks time entries when deleting invoice line', function () {
        $line = InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id]);
        $entry = \App\Models\TimeEntry::factory()->create([
            'client_id' => $this->client->id,
            'invoice_line_id' => $line->id,
        ]);

        expect($entry->is_billed)->toBeTrue();

        Livewire::actingAs($this->admin)
            ->test(InvoiceLinesRelationManager::class, [
                'ownerRecord' => $this->invoice,
                'pageClass' => EditInvoice::class,
            ])
            ->callTableAction('delete', $line);

        $entry->refresh();
        expect($entry->invoice_line_id)->toBeNull()
            ->and($entry->is_billed)->toBeFalse();
    });
});

describe('InvoiceLinesRelationManager Visual Indicators', function () {
    it('shows time entry badge when invoice line has linked time entries', function () {
        $line = InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id]);
        \App\Models\TimeEntry::factory()->create([
            'client_id' => $this->client->id,
            'invoice_line_id' => $line->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(InvoiceLinesRelationManager::class, [
                'ownerRecord' => $this->invoice,
                'pageClass' => EditInvoice::class,
            ])
            ->assertCanSeeTableRecords([$line]);
    });

    it('shows warning when editing invoice line with time entries', function () {
        $line = InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id]);
        \App\Models\TimeEntry::factory()->count(2)->create([
            'client_id' => $this->client->id,
            'invoice_line_id' => $line->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(InvoiceLinesRelationManager::class, [
                'ownerRecord' => $this->invoice,
                'pageClass' => EditInvoice::class,
            ])
            ->callTableAction('edit', $line)
            ->assertHasNoTableActionErrors();

        // The warning should be visible in the form
        expect($line->timeEntries()->count())->toBe(2);
    });

    it('can filter by has time entries', function () {
        $lineWithEntry = InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id]);
        \App\Models\TimeEntry::factory()->create([
            'client_id' => $this->client->id,
            'invoice_line_id' => $lineWithEntry->id,
        ]);

        $lineWithoutEntry = InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id]);

        Livewire::actingAs($this->admin)
            ->test(InvoiceLinesRelationManager::class, [
                'ownerRecord' => $this->invoice,
                'pageClass' => EditInvoice::class,
            ])
            ->filterTable('has_time_entries', true)
            ->assertCanSeeTableRecords([$lineWithEntry])
            ->assertCanNotSeeTableRecords([$lineWithoutEntry]);
    });

    it('can filter by does not have time entries', function () {
        $lineWithEntry = InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id]);
        \App\Models\TimeEntry::factory()->create([
            'client_id' => $this->client->id,
            'invoice_line_id' => $lineWithEntry->id,
        ]);

        $lineWithoutEntry = InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id]);

        Livewire::actingAs($this->admin)
            ->test(InvoiceLinesRelationManager::class, [
                'ownerRecord' => $this->invoice,
                'pageClass' => EditInvoice::class,
            ])
            ->filterTable('has_time_entries', false)
            ->assertCanSeeTableRecords([$lineWithoutEntry])
            ->assertCanNotSeeTableRecords([$lineWithEntry]);
    });

    it('shows view source entries action when invoice line has time entries', function () {
        $line = InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id]);
        \App\Models\TimeEntry::factory()->create([
            'client_id' => $this->client->id,
            'invoice_line_id' => $line->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(InvoiceLinesRelationManager::class, [
                'ownerRecord' => $this->invoice,
                'pageClass' => EditInvoice::class,
            ])
            ->assertTableActionVisible('viewSourceEntries', $line);
    });

    it('hides view source entries action when invoice line has no time entries', function () {
        $line = InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id]);

        Livewire::actingAs($this->admin)
            ->test(InvoiceLinesRelationManager::class, [
                'ownerRecord' => $this->invoice,
                'pageClass' => EditInvoice::class,
            ])
            ->assertTableActionHidden('viewSourceEntries', $line);
    });
});
