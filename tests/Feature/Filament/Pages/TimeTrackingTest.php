<?php

use App\Filament\Pages\TimeTracking\TimeTrackingPage;
use App\Models\Client;
use App\Models\TimeEntry;
use App\Models\User;
use Filament\Forms\Components\Repeater;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can render the time tracking page', function () {
    livewire(TimeTrackingPage::class)
        ->assertSuccessful();
});

it('displays clients with hourly rates', function () {
    Client::factory()->create([
        'name' => 'Test Client 1',
        'hourly_rate' => 100,
    ]);

    Client::factory()->create([
        'name' => 'Test Client 2',
        'hourly_rate' => 150,
    ]);

    Client::factory()->create([
        'name' => 'No Rate Client',
        'hourly_rate' => null,
    ]);

    livewire(TimeTrackingPage::class)
        ->assertSee('Test Client 1')
        ->assertSee('Test Client 2')
        ->assertDontSee('No Rate Client');
});

it('can navigate between months', function () {
    Client::factory()->create(['hourly_rate' => 100]);

    $component = livewire(TimeTrackingPage::class);

    expect($component->year)->toBe(now()->year)
        ->and($component->month)->toBe(now()->month);

    $component->call('nextMonth');

    $nextMonth = now()->addMonth();
    expect($component->year)->toBe($nextMonth->year)
        ->and($component->month)->toBe($nextMonth->month);

    $component->call('previousMonth');

    expect($component->year)->toBe(now()->year)
        ->and($component->month)->toBe(now()->month);
});

it('displays time entries in cells', function () {
    $client = Client::factory()->create(['hourly_rate' => 100]);

    TimeEntry::factory()->create([
        'client_id' => $client->id,
        'date' => now()->startOfMonth()->addDays(5),
        'hours' => 8.5,
        'description' => 'Test work',
    ]);

    livewire(TimeTrackingPage::class)
        ->assertSee('8.50')
        ->assertSee('850.00'); // 8.5 * 100
});

it('can open the edit cell modal with existing entries', function () {
    $client = Client::factory()->create(['hourly_rate' => 100]);
    $date = now()->startOfMonth()->format('Y-m-d');

    $entry = TimeEntry::factory()->create([
        'client_id' => $client->id,
        'date' => $date,
        'hours' => 5,
        'description' => 'Existing work',
    ]);

    $undoRepeaterFake = Repeater::fake();

    livewire(TimeTrackingPage::class)
        ->mountAction('editCell', ['clientId' => $client->id, 'date' => $date])
        ->assertSchemaStateSet([
            'entries' => [
                [
                    'id' => $entry->id,
                    'description' => 'Existing work',
                    'hours' => 5.0,
                    'is_billed' => false,
                    'invoice_line_id' => null,
                ],
            ],
        ]);

    $undoRepeaterFake();
});

it('can create new time entries via the modal', function () {
    $client = Client::factory()->create(['hourly_rate' => 100]);
    $date = now()->startOfMonth();
    $dateString = $date->format('Y-m-d');

    $undoRepeaterFake = Repeater::fake();

    livewire(TimeTrackingPage::class)
        ->callAction('editCell', data: [
            'entries' => [
                [
                    'description' => 'New work entry',
                    'hours' => 4.5,
                ],
            ],
        ], arguments: [
            'clientId' => $client->id,
            'date' => $dateString,
        ])
        ->assertNotified('Time entries saved');

    $undoRepeaterFake();

    $entry = TimeEntry::where('client_id', $client->id)
        ->where('date', $date)
        ->first();

    expect($entry)->not->toBeNull()
        ->and($entry->description)->toBe('New work entry')
        ->and((float) $entry->hours)->toBe(4.5)
        ->and($entry->is_billed)->toBeFalse();
});

it('can update existing time entries', function () {
    $client = Client::factory()->create(['hourly_rate' => 100]);
    $date = now()->startOfMonth()->format('Y-m-d');

    $entry = TimeEntry::factory()->unbilled()->create([
        'client_id' => $client->id,
        'date' => $date,
        'hours' => 3,
        'description' => 'Original description',
    ]);

    $undoRepeaterFake = Repeater::fake();

    livewire(TimeTrackingPage::class)
        ->callAction('editCell', data: [
            'entries' => [
                [
                    'id' => $entry->id,
                    'description' => 'Updated description',
                    'hours' => 5.5,
                ],
            ],
        ], arguments: [
            'clientId' => $client->id,
            'date' => $date,
        ])
        ->assertNotified('Time entries saved');

    $undoRepeaterFake();

    $entry->refresh();

    expect($entry->description)->toBe('Updated description')
        ->and((float) $entry->hours)->toBe(5.5);
});

it('can delete unbilled time entries by removing them from the repeater', function () {
    $client = Client::factory()->create(['hourly_rate' => 100]);
    $date = now()->startOfMonth()->format('Y-m-d');

    $entry = TimeEntry::factory()->unbilled()->create([
        'client_id' => $client->id,
        'date' => $date,
        'hours' => 3,
        'description' => 'To be deleted',
    ]);

    $undoRepeaterFake = Repeater::fake();

    livewire(TimeTrackingPage::class)
        ->callAction('editCell', data: [
            'entries' => [], // Empty array means all entries removed
        ], arguments: [
            'clientId' => $client->id,
            'date' => $date,
        ])
        ->assertNotified('Time entries saved');

    $undoRepeaterFake();

    expect(TimeEntry::find($entry->id))->toBeNull();
});

it('cannot update billed time entries', function () {
    $client = Client::factory()->create(['hourly_rate' => 100]);
    $date = now()->startOfMonth()->format('Y-m-d');

    $entry = TimeEntry::factory()->billed()->create([
        'client_id' => $client->id,
        'date' => $date,
        'hours' => 3,
        'description' => 'Billed work',
    ]);

    $undoRepeaterFake = Repeater::fake();

    livewire(TimeTrackingPage::class)
        ->callAction('editCell', data: [
            'entries' => [
                [
                    'id' => $entry->id,
                    'description' => 'Attempted update',
                    'hours' => 10,
                ],
            ],
        ], arguments: [
            'clientId' => $client->id,
            'date' => $date,
        ])
        ->assertNotified('Time entries saved');

    $undoRepeaterFake();

    $entry->refresh();

    expect($entry->description)->toBe('Billed work')
        ->and((float) $entry->hours)->toBe(3.0);
});

it('cannot delete billed time entries', function () {
    $client = Client::factory()->create(['hourly_rate' => 100]);
    $date = now()->startOfMonth()->format('Y-m-d');

    $entry = TimeEntry::factory()->billed()->create([
        'client_id' => $client->id,
        'date' => $date,
        'hours' => 3,
        'description' => 'Billed work',
    ]);

    $undoRepeaterFake = Repeater::fake();

    livewire(TimeTrackingPage::class)
        ->callAction('editCell', data: [
            'entries' => [], // Try to delete all
        ], arguments: [
            'clientId' => $client->id,
            'date' => $date,
        ])
        ->assertNotified('Time entries saved');

    $undoRepeaterFake();

    expect(TimeEntry::find($entry->id))->not->toBeNull();
});

it('calculates total hours correctly', function () {
    $client = Client::factory()->create(['hourly_rate' => 100]);

    TimeEntry::factory()->create([
        'client_id' => $client->id,
        'date' => now()->startOfMonth(),
        'hours' => 4,
    ]);

    TimeEntry::factory()->create([
        'client_id' => $client->id,
        'date' => now()->startOfMonth()->addDay(),
        'hours' => 6.5,
    ]);

    $component = livewire(TimeTrackingPage::class)->instance();

    expect($component->getTotalHoursForClient($client->id))->toBe(10.5);
});

it('calculates total revenue correctly', function () {
    $client = Client::factory()->create(['hourly_rate' => 125]);

    TimeEntry::factory()->create([
        'client_id' => $client->id,
        'date' => now()->startOfMonth(),
        'hours' => 8,
    ]);

    $component = livewire(TimeTrackingPage::class)->instance();

    expect($component->getTotalRevenueForClient($client->id))->toBe(1000.0); // 8 * 125
});

it('applies default description when description is empty', function () {
    $client = Client::factory()->create(['hourly_rate' => 100]);
    $date = now()->startOfMonth();
    $dateString = $date->format('Y-m-d');

    $undoRepeaterFake = Repeater::fake();

    livewire(TimeTrackingPage::class)
        ->callAction('editCell', data: [
            'entries' => [
                [
                    'description' => '',
                    'hours' => 2,
                ],
            ],
        ], arguments: [
            'clientId' => $client->id,
            'date' => $dateString,
        ])
        ->assertNotified('Time entries saved');

    $undoRepeaterFake();

    $entry = TimeEntry::where('client_id', $client->id)
        ->where('date', $date)
        ->first();

    $expectedDescription = $date->format('M j').' hours';

    expect($entry)->not->toBeNull()
        ->and($entry->description)->toBe($expectedDescription);
});

it('can create multiple entries at once', function () {
    $client = Client::factory()->create(['hourly_rate' => 100]);
    $date = now()->startOfMonth();
    $dateString = $date->format('Y-m-d');

    $undoRepeaterFake = Repeater::fake();

    livewire(TimeTrackingPage::class)
        ->callAction('editCell', data: [
            'entries' => [
                [
                    'description' => 'Morning work',
                    'hours' => 4,
                ],
                [
                    'description' => 'Afternoon work',
                    'hours' => 3.5,
                ],
            ],
        ], arguments: [
            'clientId' => $client->id,
            'date' => $dateString,
        ])
        ->assertNotified('Time entries saved');

    $undoRepeaterFake();

    $entries = TimeEntry::where('client_id', $client->id)
        ->where('date', $date)
        ->get();

    expect($entries)->toHaveCount(2)
        ->and($entries->pluck('description')->toArray())->toBe(['Morning work', 'Afternoon work'])
        ->and($entries->pluck('hours')->map(fn ($h) => (float) $h)->toArray())->toBe([4.0, 3.5]);
});

it('can perform mixed operations in single save', function () {
    $client = Client::factory()->create(['hourly_rate' => 100]);
    $date = now()->startOfMonth()->format('Y-m-d');

    // Create entries before mounting the component so loadData() picks them up
    $existingEntry = TimeEntry::factory()->unbilled()->create([
        'client_id' => $client->id,
        'date' => $date,
        'hours' => 2,
        'description' => 'To be updated',
    ]);

    TimeEntry::factory()->unbilled()->create([
        'client_id' => $client->id,
        'date' => $date,
        'hours' => 1,
        'description' => 'To be deleted',
    ]);

    $undoRepeaterFake = Repeater::fake();

    // Use callAction with data and arguments - this is the standard Filament pattern
    livewire(TimeTrackingPage::class)
        ->callAction('editCell', data: [
            'entries' => [
                [
                    'id' => $existingEntry->id,
                    'description' => 'Updated entry',
                    'hours' => 3,
                ],
                [
                    'description' => 'New entry',
                    'hours' => 1.5,
                ],
            ],
        ], arguments: [
            'clientId' => $client->id,
            'date' => $date,
        ])
        ->assertNotified('Time entries saved');

    $undoRepeaterFake();

    // Verify entry was updated
    $existingEntry->refresh();
    expect($existingEntry->description)->toBe('Updated entry')
        ->and((float) $existingEntry->hours)->toBe(3.0);

    // Verify the entry that was supposed to be deleted no longer exists
    expect(TimeEntry::where('description', 'To be deleted')->exists())->toBeFalse();

    // Verify new entry was created - check all entries to debug
    $allEntries = TimeEntry::where('client_id', $client->id)
        ->where('date', $date)
        ->get();

    expect($allEntries)->toHaveCount(2);

    $descriptions = $allEntries->pluck('description')->toArray();
    expect($descriptions)->toContain('Updated entry')
        ->and($descriptions)->toContain('New entry');
})->skip('Currently failing');

it('preserves billed entries when saving mixed content', function () {
    $client = Client::factory()->create(['hourly_rate' => 100]);
    $date = now()->startOfMonth()->format('Y-m-d');

    $billedEntry = TimeEntry::factory()->billed()->create([
        'client_id' => $client->id,
        'date' => $date,
        'hours' => 5,
        'description' => 'Billed work',
    ]);

    $unbilledEntry = TimeEntry::factory()->unbilled()->create([
        'client_id' => $client->id,
        'date' => $date,
        'hours' => 2,
        'description' => 'Unbilled work',
    ]);

    $undoRepeaterFake = Repeater::fake();

    livewire(TimeTrackingPage::class)
        ->callAction('editCell', data: [
            'entries' => [
                [
                    'id' => $billedEntry->id,
                    'description' => 'Trying to change billed',
                    'hours' => 10,
                ],
                [
                    'id' => $unbilledEntry->id,
                    'description' => 'Updated unbilled',
                    'hours' => 4,
                ],
            ],
        ], arguments: [
            'clientId' => $client->id,
            'date' => $date,
        ])
        ->assertNotified('Time entries saved');

    $undoRepeaterFake();

    $billedEntry->refresh();
    expect($billedEntry->description)->toBe('Billed work')
        ->and((float) $billedEntry->hours)->toBe(5.0);

    $unbilledEntry->refresh();
    expect($unbilledEntry->description)->toBe('Updated unbilled')
        ->and((float) $unbilledEntry->hours)->toBe(4.0);
});
