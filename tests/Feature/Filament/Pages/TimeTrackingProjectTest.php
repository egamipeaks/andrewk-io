<?php

use App\Filament\Pages\TimeTracking\TimeTrackingPage;
use App\Models\Client;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Filament\Forms\Components\Repeater;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    $this->client = Client::factory()->create(['hourly_rate' => 100]);
    $this->date = now()->startOfMonth()->format('Y-m-d');
});

it('saves a new entry with a project', function () {
    $project = Project::factory()->create(['client_id' => $this->client->id]);
    $undoRepeaterFake = Repeater::fake();

    livewire(TimeTrackingPage::class)
        ->callAction('editCell', data: [
            'entries' => [
                ['description' => 'Header layout', 'hours' => 3, 'project_id' => $project->id],
            ],
        ], arguments: ['clientId' => $this->client->id, 'date' => $this->date])
        ->assertNotified('Time entries saved');

    $undoRepeaterFake();

    assertDatabaseHas('time_entries', [
        'client_id' => $this->client->id,
        'description' => 'Header layout',
        'project_id' => $project->id,
    ]);
});

it('saves a new entry without a project', function () {
    $undoRepeaterFake = Repeater::fake();

    livewire(TimeTrackingPage::class)
        ->callAction('editCell', data: [
            'entries' => [
                ['description' => 'General support', 'hours' => 1, 'project_id' => null],
            ],
        ], arguments: ['clientId' => $this->client->id, 'date' => $this->date])
        ->assertNotified('Time entries saved');

    $undoRepeaterFake();

    assertDatabaseHas('time_entries', [
        'description' => 'General support',
        'project_id' => null,
    ]);
});

it('clears the project from an existing entry', function () {
    $project = Project::factory()->create(['client_id' => $this->client->id]);
    $entry = TimeEntry::factory()->forProject($project)->create(['date' => $this->date]);
    $undoRepeaterFake = Repeater::fake();

    livewire(TimeTrackingPage::class)
        ->callAction('editCell', data: [
            'entries' => [
                ['id' => $entry->id, 'description' => $entry->description, 'hours' => 2, 'project_id' => null],
            ],
        ], arguments: ['clientId' => $this->client->id, 'date' => $this->date])
        ->assertNotified('Time entries saved');

    $undoRepeaterFake();

    expect($entry->fresh()->project_id)->toBeNull();
});

it('keeps an inactive project on an existing entry when saved unchanged', function () {
    $project = Project::factory()->inactive()->create(['client_id' => $this->client->id]);
    $entry = TimeEntry::factory()->forProject($project)->create(['date' => $this->date]);
    $undoRepeaterFake = Repeater::fake();

    livewire(TimeTrackingPage::class)
        ->callAction('editCell', data: [
            'entries' => [
                ['id' => $entry->id, 'description' => 'Still on old project', 'hours' => 2, 'project_id' => $project->id],
            ],
        ], arguments: ['clientId' => $this->client->id, 'date' => $this->date])
        ->assertNotified('Time entries saved');

    $undoRepeaterFake();

    expect($entry->fresh())
        ->project_id->toBe($project->id)
        ->description->toBe('Still on old project');
});

it('fills the form with the entry project and remembers the client', function () {
    $project = Project::factory()->create(['client_id' => $this->client->id]);
    $entry = TimeEntry::factory()->forProject($project)->create(['date' => $this->date, 'hours' => 2]);
    $undoRepeaterFake = Repeater::fake();

    $component = livewire(TimeTrackingPage::class)
        ->mountAction('editCell', ['clientId' => $this->client->id, 'date' => $this->date])
        ->assertSchemaStateSet([
            'entries' => [
                [
                    'id' => $entry->id,
                    'description' => $entry->description,
                    'hours' => 2.0,
                    'project_id' => (string) $project->id,
                    'is_billed' => false,
                    'invoice_line_id' => null,
                ],
            ],
        ]);

    $undoRepeaterFake();

    expect($component->get('currentEditClientId'))->toBe($this->client->id);
});
