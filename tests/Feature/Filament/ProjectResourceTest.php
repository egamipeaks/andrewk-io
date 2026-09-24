<?php

use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\RelationManagers\TimeEntriesRelationManager;
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
});

describe('Projects table', function () {
    it('lists active projects by default', function () {
        $active = Project::factory()->create();
        $inactive = Project::factory()->inactive()->create();

        Livewire::actingAs($this->admin)
            ->test(ListProjects::class)
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$inactive]);
    });

    it('filters by client', function () {
        $client = Client::factory()->create();
        $mine = Project::factory()->create(['client_id' => $client->id]);
        $theirs = Project::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(ListProjects::class)
            ->filterTable('client', $client->id)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);
    });

    it('shows hours used and hours remaining', function () {
        $project = Project::factory()->withBudget(10)->create();
        TimeEntry::factory()->forProject($project)->create(['hours' => 4]);
        TimeEntry::factory()->forProject($project)->create(['hours' => 8]);

        Livewire::actingAs($this->admin)
            ->test(ListProjects::class)
            ->assertTableColumnStateSet('hours_used', 12.0, $project->getKey())
            ->assertTableColumnStateSet('hours_remaining', -2.0, $project->getKey());
    });

    it('shows all time hours in range when no date range is set', function () {
        $project = Project::factory()->create();
        TimeEntry::factory()->forProject($project)->create(['hours' => 5, 'date' => '2026-01-10']);

        Livewire::actingAs($this->admin)
            ->test(ListProjects::class)
            ->assertTableColumnStateSet('hours_in_range', 5.0, $project->getKey());
    });

    it('limits hours in range to the date range without hiding projects', function () {
        $project = Project::factory()->create();
        TimeEntry::factory()->forProject($project)->create(['hours' => 3, 'date' => '2026-09-10']);
        TimeEntry::factory()->forProject($project)->create(['hours' => 7, 'date' => '2026-08-10']);

        Livewire::actingAs($this->admin)
            ->test(ListProjects::class)
            ->filterTable('date_range', ['from' => '2026-09-01', 'until' => '2026-09-30'])
            ->assertCanSeeTableRecords([$project])
            ->assertTableColumnStateSet('hours_in_range', 3.0, $project->getKey())
            ->assertTableColumnStateSet('hours_used', 10.0, $project->getKey());
    });

    it('includes manual invoice line hours in used, left and in range', function () {
        $project = Project::factory()->withBudget(10)->create();
        TimeEntry::factory()->forProject($project)->create(['hours' => 3, 'date' => '2026-09-10']);
        $invoice = Invoice::factory()->create(['client_id' => $project->client_id]);
        InvoiceLine::factory()->hourly()->create([
            'invoice_id' => $invoice->id,
            'project_id' => $project->id,
            'hours' => 0.5,
            'date' => '2026-09-12',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListProjects::class)
            ->assertTableColumnStateSet('hours_used', 3.5, $project->getKey())
            ->assertTableColumnStateSet('hours_remaining', 6.5, $project->getKey())
            ->filterTable('date_range', ['from' => '2026-09-11', 'until' => '2026-09-30'])
            ->assertTableColumnStateSet('hours_in_range', 0.5, $project->getKey());
    });

    it('sorts by hours used', function () {
        $small = Project::factory()->create();
        $large = Project::factory()->create();
        TimeEntry::factory()->forProject($small)->create(['hours' => 1]);
        TimeEntry::factory()->forProject($large)->create(['hours' => 9]);

        Livewire::actingAs($this->admin)
            ->test(ListProjects::class)
            ->sortTable('hours_used', 'desc')
            ->assertCanSeeTableRecords([$large, $small], inOrder: true);
    });

    it('shows zero hours in range when nothing falls inside the range', function () {
        $project = Project::factory()->create();
        TimeEntry::factory()->forProject($project)->create(['hours' => 7, 'date' => '2026-08-10']);

        Livewire::actingAs($this->admin)
            ->test(ListProjects::class)
            ->filterTable('date_range', ['from' => '2026-09-01', 'until' => '2026-09-30'])
            ->assertTableColumnStateSet('hours_in_range', 0.0, $project->getKey());
    });
});

describe('Project form', function () {
    it('creates a project with a budget', function () {
        $client = Client::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(CreateProject::class)
            ->fillForm([
                'client_id' => $client->id,
                'name' => 'Website Redesign',
                'budget_hours' => 40,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        assertDatabaseHas('projects', [
            'client_id' => $client->id,
            'name' => 'Website Redesign',
            'budget_hours' => 40,
        ]);
    });

    it('creates a project without a budget', function () {
        $client = Client::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(CreateProject::class)
            ->fillForm([
                'client_id' => $client->id,
                'name' => 'Support',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        assertDatabaseHas('projects', ['name' => 'Support', 'budget_hours' => null]);
    });

    it('requires a client and a name', function () {
        Livewire::actingAs($this->admin)
            ->test(CreateProject::class)
            ->fillForm(['client_id' => null, 'name' => ''])
            ->call('create')
            ->assertHasFormErrors(['client_id' => 'required', 'name' => 'required']);
    });

    it('does not allow changing the client of an existing project', function () {
        $project = Project::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(EditProject::class, ['record' => $project->getRouteKey()])
            ->assertFormFieldDisabled('client_id');
    });
});

describe('Project time entries relation manager', function () {
    it('lists only the project time entries', function () {
        $project = Project::factory()->create();
        $mine = TimeEntry::factory()->forProject($project)->create();
        $unassigned = TimeEntry::factory()->create(['client_id' => $project->client_id]);

        Livewire::actingAs($this->admin)
            ->test(TimeEntriesRelationManager::class, [
                'ownerRecord' => $project,
                'pageClass' => EditProject::class,
            ])
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$unassigned]);
    });
});
