<?php

use App\Filament\Pages\TimeTracking;
use App\Models\Client;
use App\Models\ProjectedEntry;
use App\Models\TimeEntry;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'email' => 'admin@andrewk.io',
        'email_verified_at' => now(),
    ]);

    $this->client = Client::factory()->create([
        'is_active' => true,
        'hourly_rate' => 100,
    ]);
});

describe('ProjectedEntry Model', function () {
    it('can create projected entries', function () {
        $projection = ProjectedEntry::factory()->create([
            'client_id' => $this->client->id,
            'date' => now(),
            'hours' => 5,
        ]);

        expect($projection)->toBeInstanceOf(ProjectedEntry::class);
        expect((float) $projection->hours)->toBe(5.0);
    });

    it('has client relationship', function () {
        $projection = ProjectedEntry::factory()->create([
            'client_id' => $this->client->id,
        ]);

        expect($projection->client)->toBeInstanceOf(Client::class);
        expect($projection->client->id)->toBe($this->client->id);
    });

    it('calculates value based on client hourly rate', function () {
        $projection = ProjectedEntry::factory()->create([
            'client_id' => $this->client->id,
            'hours' => 5,
        ]);

        expect($projection->value)->toBe(500.0);
    });

    it('prevents duplicate projections for same client and date', function () {
        ProjectedEntry::factory()->create([
            'client_id' => $this->client->id,
            'date' => now(),
            'hours' => 5,
        ]);

        expect(fn () => ProjectedEntry::factory()->create([
            'client_id' => $this->client->id,
            'date' => now(),
            'hours' => 3,
        ]))->toThrow(\Exception::class);
    });

    it('has factory states for different months', function () {
        $current = ProjectedEntry::factory()->currentMonth()->create();
        $next = ProjectedEntry::factory()->nextMonth()->create();
        $future = ProjectedEntry::factory()->futureMonth()->create();

        expect($current->date->isCurrentMonth())->toBeTrue();
        expect($next->date->isNextMonth())->toBeTrue();
        expect($future->date->isFuture())->toBeTrue();
    });
});

describe('View Mode Switching', function () {
    it('defaults to actual mode', function () {
        $component = Livewire::actingAs($this->admin)->test(TimeTracking::class);

        expect($component->viewMode)->toBe('actual');
    });

    it('can switch to projection mode', function () {
        $component = Livewire::actingAs($this->admin)
            ->test(TimeTracking::class)
            ->call('switchViewMode', 'projection');

        expect($component->viewMode)->toBe('projection');
    });

    it('can switch back to actual mode', function () {
        $component = Livewire::actingAs($this->admin)
            ->test(TimeTracking::class)
            ->call('switchViewMode', 'projection')
            ->call('switchViewMode', 'actual');

        expect($component->viewMode)->toBe('actual');
    });

    it('loads projected data when switching to projection mode', function () {
        ProjectedEntry::factory()->currentMonth()->create([
            'client_id' => $this->client->id,
            'hours' => 5,
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(TimeTracking::class)
            ->call('switchViewMode', 'projection');

        expect($component->projectedEntriesData)->not->toBeEmpty();
    });
});

describe('Month Navigation in Projection Mode', function () {
    it('can go to previous month in actual mode', function () {
        $component = Livewire::actingAs($this->admin)
            ->test(TimeTracking::class, ['year' => now()->year, 'month' => now()->month])
            ->call('previousMonth');

        $expectedDate = now()->subMonth();
        expect($component->year)->toBe($expectedDate->year);
        expect($component->month)->toBe($expectedDate->month);
    });

    it('cannot go before current month in projection mode', function () {
        $component = Livewire::actingAs($this->admin)
            ->test(TimeTracking::class, [
                'year' => now()->year,
                'month' => now()->month,
                'viewMode' => 'projection',
            ])
            ->call('previousMonth');

        expect($component->year)->toBe(now()->year);
        expect($component->month)->toBe(now()->month);
    });

    it('can go to next month in projection mode', function () {
        $component = Livewire::actingAs($this->admin)
            ->test(TimeTracking::class, ['viewMode' => 'projection'])
            ->call('nextMonth');

        $expectedDate = now()->addMonth();
        expect($component->year)->toBe($expectedDate->year);
        expect($component->month)->toBe($expectedDate->month);
    });

    it('canGoToPreviousMonth returns false for current month in projection mode', function () {
        $component = Livewire::actingAs($this->admin)
            ->test(TimeTracking::class, [
                'year' => now()->year,
                'month' => now()->month,
                'viewMode' => 'projection',
            ]);

        expect($component->instance()->canGoToPreviousMonth())->toBeFalse();
    });

    it('canGoToPreviousMonth returns true for future month in projection mode', function () {
        $nextMonth = now()->addMonth();
        $component = Livewire::actingAs($this->admin)
            ->test(TimeTracking::class, [
                'year' => $nextMonth->year,
                'month' => $nextMonth->month,
                'viewMode' => 'projection',
            ]);

        expect($component->instance()->canGoToPreviousMonth())->toBeTrue();
    });
});

describe('Projection Data Loading', function () {
    it('loads projected entries for current month', function () {
        ProjectedEntry::factory()->currentMonth()->create([
            'client_id' => $this->client->id,
            'hours' => 8,
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(TimeTracking::class, ['viewMode' => 'projection']);

        expect($component->projectedEntriesData)->not->toBeEmpty();
    });

    it('pre-fills past days with actual time entry totals', function () {
        $yesterday = now()->subDay();

        TimeEntry::factory()->create([
            'client_id' => $this->client->id,
            'date' => $yesterday,
            'hours' => 5,
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(TimeTracking::class, [
                'year' => now()->year,
                'month' => now()->month,
                'viewMode' => 'projection',
            ]);

        $key = $this->client->id.'_'.$yesterday->format('Y-m-d');
        expect($component->projectedHours[$key] ?? null)->toBe(5.0);
    });

    it('does not overwrite existing projections with actual data', function () {
        $yesterday = now()->subDay();

        TimeEntry::factory()->create([
            'client_id' => $this->client->id,
            'date' => $yesterday,
            'hours' => 5,
        ]);

        ProjectedEntry::factory()->create([
            'client_id' => $this->client->id,
            'date' => $yesterday,
            'hours' => 8,
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(TimeTracking::class, [
                'year' => now()->year,
                'month' => now()->month,
                'viewMode' => 'projection',
            ]);

        $key = $this->client->id.'_'.$yesterday->format('Y-m-d');
        expect($component->projectedHours[$key])->toBe(8.0);
    });
});

describe('Auto-save Functionality', function () {
    it('can save and load projected entry', function () {
        $tomorrow = now()->addDay();

        ProjectedEntry::create([
            'client_id' => $this->client->id,
            'date' => $tomorrow,
            'hours' => 5,
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(TimeTracking::class, [
                'year' => $tomorrow->year,
                'month' => $tomorrow->month,
                'viewMode' => 'projection',
            ]);

        $key = $this->client->id.'_'.$tomorrow->format('Y-m-d');
        expect($component->projectedHours[$key] ?? null)->toBe(5.0);
    });

    it('deletes projected entry when hours cleared', function () {
        $tomorrow = now()->addDay();

        ProjectedEntry::factory()->create([
            'client_id' => $this->client->id,
            'date' => $tomorrow,
            'hours' => 5,
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(TimeTracking::class, ['viewMode' => 'projection']);

        $key = $this->client->id.'_'.$tomorrow->format('Y-m-d');
        $component->set("projectedHours.{$key}", 0);
        $component->call('saveProjectedEntry', $this->client->id, $tomorrow->format('Y-m-d'));

        expect(ProjectedEntry::where('client_id', $this->client->id)
            ->where('date', $tomorrow->format('Y-m-d'))
            ->exists())->toBeFalse();
    });
});

describe('Sync Actuals Functionality', function () {
    it('calls syncActuals without errors', function () {
        Livewire::actingAs($this->admin)
            ->test(TimeTracking::class, [
                'year' => now()->year,
                'month' => now()->month,
                'viewMode' => 'projection',
            ])
            ->call('syncActuals')
            ->assertNotified();
    });

    it('removes projections for past days with no actual hours', function () {
        $yesterday = now()->subDay();

        ProjectedEntry::factory()->create([
            'client_id' => $this->client->id,
            'date' => $yesterday,
            'hours' => 5,
        ]);

        Livewire::actingAs($this->admin)
            ->test(TimeTracking::class, [
                'year' => now()->year,
                'month' => now()->month,
                'viewMode' => 'projection',
            ])
            ->call('syncActuals');

        expect(ProjectedEntry::where('client_id', $this->client->id)
            ->where('date', $yesterday->format('Y-m-d'))
            ->exists())->toBeFalse();
    });

    it('shows success notification after syncing', function () {
        $yesterday = now()->subDay();

        TimeEntry::factory()->create([
            'client_id' => $this->client->id,
            'date' => $yesterday,
            'hours' => 6,
        ]);

        Livewire::actingAs($this->admin)
            ->test(TimeTracking::class, [
                'year' => now()->year,
                'month' => now()->month,
                'viewMode' => 'projection',
            ])
            ->call('syncActuals')
            ->assertNotified();
    });
});

describe('Projection Calculations', function () {
    it('calculates total projected hours for client', function () {
        $dates = [
            now()->addDay(1)->format('Y-m-d'),
            now()->addDay(2)->format('Y-m-d'),
            now()->addDay(3)->format('Y-m-d'),
        ];

        $component = Livewire::actingAs($this->admin)
            ->test(TimeTracking::class, ['viewMode' => 'projection']);

        foreach ($dates as $date) {
            $key = $this->client->id.'_'.$date;
            $component->set("projectedHours.{$key}", 5);
        }

        expect($component->instance()->getTotalProjectedHoursForClient($this->client->id))->toBe(15.0);
    });

    it('calculates total projected revenue for client', function () {
        $dates = [
            now()->addDay(1)->format('Y-m-d'),
            now()->addDay(2)->format('Y-m-d'),
        ];

        $component = Livewire::actingAs($this->admin)
            ->test(TimeTracking::class, ['viewMode' => 'projection']);

        foreach ($dates as $date) {
            $key = $this->client->id.'_'.$date;
            $component->set("projectedHours.{$key}", 5);
        }

        expect($component->instance()->getTotalProjectedRevenueForClient($this->client->id))->toBe(1000.0);
    });

    it('calculates grand total projected hours', function () {
        $client2 = Client::factory()->create(['hourly_rate' => 150, 'is_active' => true]);

        $component = Livewire::actingAs($this->admin)
            ->test(TimeTracking::class, ['viewMode' => 'projection']);

        $key1 = $this->client->id.'_'.now()->addDay(1)->format('Y-m-d');
        $key2 = $this->client->id.'_'.now()->addDay(2)->format('Y-m-d');
        $key3 = $client2->id.'_'.now()->addDay(1)->format('Y-m-d');
        $key4 = $client2->id.'_'.now()->addDay(2)->format('Y-m-d');

        $component->set("projectedHours.{$key1}", 5);
        $component->set("projectedHours.{$key2}", 5);
        $component->set("projectedHours.{$key3}", 3);
        $component->set("projectedHours.{$key4}", 3);

        expect($component->instance()->getGrandTotalProjectedHours())->toBe(16.0);
    });

    it('formats projected revenue with currency', function () {
        ProjectedEntry::factory()->currentMonth()->create([
            'client_id' => $this->client->id,
            'hours' => 5,
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(TimeTracking::class, ['viewMode' => 'projection']);

        $formatted = $component->instance()->getFormattedTotalProjectedRevenueForClient($this->client->id);

        expect($formatted)->toContain('$');
        expect($formatted)->toContain('500');
    });
});
