<?php

use App\Enums\InvoiceLineType;
use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Filament\Resources\Invoices\RelationManagers\InvoiceLinesRelationManager;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'email' => 'admin@andrewk.io',
        'email_verified_at' => now(),
    ]);

    $this->client = Client::factory()->create(['hourly_rate' => 150]);
    $this->invoice = Invoice::factory()->create(['client_id' => $this->client->id, 'paid' => false]);
    $this->project = Project::factory()->create(['client_id' => $this->client->id, 'name' => 'Website Redesign']);

    $this->linesManager = fn (Invoice $invoice, User $admin) => Livewire::actingAs($admin)->test(InvoiceLinesRelationManager::class, [
        'ownerRecord' => $invoice,
        'pageClass' => EditInvoice::class,
    ]);
});

describe('Importing time entries', function () {
    it('copies the project onto the invoice line', function () {
        $withProject = TimeEntry::factory()->forProject($this->project)->create(['date' => now()->subDays(2)]);
        $withoutProject = TimeEntry::factory()->create(['client_id' => $this->client->id, 'date' => now()->subDays(2)]);

        Livewire::actingAs($this->admin)
            ->test(EditInvoice::class, ['record' => $this->invoice->getRouteKey()])
            ->callAction('Import Time Entries', data: [
                'date_from' => now()->subWeek()->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
                'time_entry_ids' => [$withProject->id, $withoutProject->id],
            ]);

        expect($withProject->fresh()->invoiceLine->project_id)->toBe($this->project->id)
            ->and($withoutProject->fresh()->invoiceLine->project_id)->toBeNull();
    });
});

describe('Invoice line project field', function () {
    it('creates a manual line with a project', function () {
        ($this->linesManager)($this->invoice, $this->admin)
            ->callTableAction('create', data: [
                'type' => InvoiceLineType::Fixed->value,
                'project_id' => $this->project->id,
                'description' => 'Discovery workshop',
                'date' => now()->format('Y-m-d'),
                'amount' => 500,
            ])
            ->assertHasNoTableActionErrors();

        assertDatabaseHas('invoice_lines', [
            'invoice_id' => $this->invoice->id,
            'description' => 'Discovery workshop',
            'project_id' => $this->project->id,
        ]);
    });

    it('shows the project name in the table', function () {
        InvoiceLine::factory()->hourly()->create([
            'invoice_id' => $this->invoice->id,
            'project_id' => $this->project->id,
        ]);

        ($this->linesManager)($this->invoice, $this->admin)
            ->assertSee('Website Redesign');
    });
});

describe('Merging hourly lines', function () {
    it('merges lines from the same project and keeps the project', function () {
        $lines = InvoiceLine::factory()->hourly()->count(2)->create([
            'invoice_id' => $this->invoice->id,
            'project_id' => $this->project->id,
            'hourly_rate' => 150,
            'hours' => 2,
        ]);

        ($this->linesManager)($this->invoice, $this->admin)
            ->callTableBulkAction('mergeHourlyLines', $lines)
            ->assertNotified('Lines Merged Successfully');

        $merged = $this->invoice->invoiceLines()->sole();

        expect($merged->project_id)->toBe($this->project->id)
            ->and((float) $merged->hours)->toBe(4.0);
    });

    it('refuses to merge lines from different projects', function () {
        $otherProject = Project::factory()->create(['client_id' => $this->client->id]);
        $a = InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id, 'project_id' => $this->project->id, 'hourly_rate' => 150]);
        $b = InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id, 'project_id' => $otherProject->id, 'hourly_rate' => 150]);

        ($this->linesManager)($this->invoice, $this->admin)
            ->callTableBulkAction('mergeHourlyLines', [$a, $b])
            ->assertNotified('Cannot Merge');

        expect($this->invoice->invoiceLines()->count())->toBe(2);
    });

    it('refuses to merge a project line with a line that has no project', function () {
        $a = InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id, 'project_id' => $this->project->id, 'hourly_rate' => 150]);
        $b = InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id, 'project_id' => null, 'hourly_rate' => 150]);

        ($this->linesManager)($this->invoice, $this->admin)
            ->callTableBulkAction('mergeHourlyLines', [$a, $b])
            ->assertNotified('Cannot Merge');

        expect($this->invoice->invoiceLines()->count())->toBe(2);
    });
});
