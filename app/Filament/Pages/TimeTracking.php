<?php

namespace App\Filament\Pages;

use App\Enums\Currency;
use App\Filament\Pages\TimeTracking\Actions\EditCellActionBuilder;
use App\Models\Client;
use App\Models\ProjectedEntry;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

class TimeTracking extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-clock';

    protected string $view = 'filament.pages.time-tracking';

    protected static ?string $navigationLabel = 'Time Tracking';

    protected static ?string $title = 'Time Tracking';

    protected static ?int $navigationSort = 2;

    protected static bool $shouldRegisterNavigation = true;

    #[Url]
    public int $year;

    #[Url]
    public int $month;

    #[Url]
    public string $viewMode = 'actual';

    public Collection $clients;

    public array $timeEntriesData = [];

    public array $projectedEntriesData = [];

    public array $projectedHours = [];

    public ?string $currentEditDate = null;

    public function mount(): void
    {
        if (! isset($this->year)) {
            $this->year = now()->year;
        }

        if (! isset($this->month)) {
            $this->month = now()->month;
        }

        $this->loadData();
    }

    public function getHeading(): string
    {
        return '';
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('timeTracking')
                ->label('Time Tracking')
                ->icon('heroicon-o-clock')
                ->color($this->viewMode === 'actual' ? 'primary' : 'gray')
                ->action(fn () => $this->switchViewMode('actual')),
            Action::make('incomeProjection')
                ->label('Income Projection')
                ->icon('heroicon-o-chart-bar')
                ->color($this->viewMode === 'projection' ? 'primary' : 'gray')
                ->action(fn () => $this->switchViewMode('projection')),
            Action::make('syncActuals')
                ->label('Sync Actuals')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->visible(fn () => $this->viewMode === 'projection'
                    && $this->year == now()->year
                    && $this->month == now()->month)
                ->action(fn () => $this->syncActuals()),
        ];
    }

    public function loadData(): void
    {
        if ($this->viewMode === 'projection') {
            $this->loadProjectedData();

            return;
        }

        $this->clients = Client::query()
            ->where('is_active', true)
            ->whereNotNull('hourly_rate')
            ->where('hourly_rate', '>', 0)
            ->orderBy('name')
            ->get();

        $startDate = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $timeEntries = TimeEntry::query()
            ->whereIn('client_id', $this->clients->pluck('id'))
            ->whereBetween('date', [$startDate, $endDate])
            ->with('client')
            ->get()
            ->groupBy(fn (TimeEntry $entry) => $entry->client_id.'_'.$entry->date->format('Y-m-d'));

        $this->timeEntriesData = $timeEntries->map(function (Collection $entries) {
            return [
                'total_hours' => $entries->sum('hours'),
                'is_billed' => $entries->every(fn (TimeEntry $entry) => $entry->is_billed),
                'entries' => $entries->toArray(),
            ];
        })->toArray();
    }

    public function loadProjectedData(): void
    {
        $this->clients = Client::query()
            ->where('is_active', true)
            ->whereNotNull('hourly_rate')
            ->where('hourly_rate', '>', 0)
            ->orderBy('name')
            ->get();

        $startDate = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $today = now()->startOfDay();

        $projectedEntries = ProjectedEntry::query()
            ->whereIn('client_id', $this->clients->pluck('id'))
            ->whereBetween('date', [$startDate, $endDate])
            ->with('client')
            ->get()
            ->groupBy(fn (ProjectedEntry $entry) => $entry->client_id.'_'.$entry->date->format('Y-m-d'));

        $this->projectedEntriesData = $projectedEntries->map(function (Collection $entries) {
            return [
                'total_hours' => $entries->sum('hours'),
                'entries' => $entries->toArray(),
            ];
        })->toArray();

        // Pre-fill projectedHours array for binding
        $this->projectedHours = [];
        foreach ($projectedEntries as $key => $entries) {
            $this->projectedHours[$key] = $entries->sum('hours');
        }

        // For current month, pre-fill past days with actual time entry totals
        if ($this->year == now()->year && $this->month == now()->month) {
            $actualEntries = TimeEntry::query()
                ->whereIn('client_id', $this->clients->pluck('id'))
                ->whereBetween('date', [$startDate, $today])
                ->get()
                ->groupBy(fn (TimeEntry $entry) => $entry->client_id.'_'.$entry->date->format('Y-m-d'));

            foreach ($actualEntries as $key => $entries) {
                // Only pre-fill if no projection exists
                if (! isset($this->projectedHours[$key])) {
                    $totalHours = $entries->sum('hours');
                    if ($totalHours > 0) {
                        $this->projectedHours[$key] = $totalHours;
                    }
                }
            }
        }
    }

    public function previousMonth(): void
    {
        // In projection mode, prevent going before current month
        if ($this->viewMode === 'projection') {
            $currentDate = now()->startOfMonth();
            $navDate = Carbon::create($this->year, $this->month, 1);

            if ($navDate->isSameMonth($currentDate)) {
                return;
            }
        }

        $date = Carbon::create($this->year, $this->month, 1)->subMonth();
        $this->year = $date->year;
        $this->month = $date->month;
        $this->loadData();
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->addMonth();
        $this->year = $date->year;
        $this->month = $date->month;
        $this->loadData();
    }

    public function editCellAction(): Action
    {
        return EditCellActionBuilder::make($this)->build();
    }

    public function getTotalHoursForClient(string $clientId): float
    {
        $total = 0;

        foreach ($this->timeEntriesData as $key => $data) {
            if (str_starts_with($key, $clientId.'_')) {
                $total += $data['total_hours'];
            }
        }

        return $total;
    }

    public function getTotalRevenueForClient(string $clientId): float
    {
        $client = $this->clients->firstWhere('id', $clientId);

        if (! $client) {
            return 0;
        }

        return $this->getTotalHoursForClient($clientId) * $client->hourly_rate;
    }

    public function getFormattedTotalRevenueForClient(string $clientId): string
    {
        $client = $this->clients->firstWhere('id', $clientId);

        if (! $client) {
            return '';
        }

        $revenue = $this->getTotalRevenueForClient($clientId);

        return Currency::USD->format($revenue);
    }

    public function getGrandTotalHours(): float
    {
        return collect($this->timeEntriesData)->sum('total_hours');
    }

    public function getGrandTotalRevenue(): float
    {
        $total = 0;

        foreach ($this->clients as $client) {
            $revenue = $this->getTotalRevenueForClient($client->id);
            $total += $client->currency->toUsd($revenue);
        }

        return $total;
    }

    public function getDaysInMonth(): int
    {
        return Carbon::create($this->year, $this->month, 1)->daysInMonth;
    }

    public function getMonthName(): string
    {
        return Carbon::create($this->year, $this->month, 1)->format('F Y');
    }

    public function getHoursForCell(string $clientId, int $day): ?array
    {
        $date = Carbon::create($this->year, $this->month, $day)->format('Y-m-d');
        $key = $clientId.'_'.$date;

        return $this->timeEntriesData[$key] ?? null;
    }

    public function getEntriesForCell(): array
    {
        if (! $this->selectedClientId || ! $this->selectedDate) {
            return [];
        }

        $key = $this->selectedClientId.'_'.$this->selectedDate;

        return $this->timeEntriesData[$key]['entries'] ?? [];
    }

    public function canGoToPreviousMonth(): bool
    {
        if ($this->viewMode === 'projection') {
            $currentDate = now()->startOfMonth();
            $navDate = Carbon::create($this->year, $this->month, 1);

            return ! $navDate->isSameMonth($currentDate);
        }

        return true;
    }

    public function getProjectedHoursForCell(int $clientId, int $day): ?float
    {
        $date = Carbon::create($this->year, $this->month, $day)->format('Y-m-d');
        $key = $clientId.'_'.$date;

        return $this->projectedHours[$key] ?? null;
    }

    public function getTotalProjectedHoursForClient(int $clientId): float
    {
        $total = 0;

        foreach ($this->projectedHours as $key => $hours) {
            if (str_starts_with($key, $clientId.'_')) {
                $total += (float) $hours;
            }
        }

        return $total;
    }

    public function getTotalProjectedRevenueForClient(int $clientId): float
    {
        $client = $this->clients->firstWhere('id', $clientId);

        if (! $client) {
            return 0;
        }

        return $this->getTotalProjectedHoursForClient($clientId) * $client->hourly_rate;
    }

    public function getFormattedTotalProjectedRevenueForClient(int $clientId): string
    {
        $client = $this->clients->firstWhere('id', $clientId);

        if (! $client) {
            return '';
        }

        $revenue = $this->getTotalProjectedRevenueForClient($clientId);

        return Currency::USD->format($revenue);
    }

    public function getGrandTotalProjectedHours(): float
    {
        return array_sum($this->projectedHours);
    }

    public function getGrandTotalProjectedRevenue(): float
    {
        $total = 0;

        foreach ($this->clients as $client) {
            $revenue = $this->getTotalProjectedRevenueForClient($client->id);
            $total += $client->currency->toUsd($revenue);
        }

        return $total;
    }

    public function saveProjectedEntry(int $clientId, string $date): void
    {
        $key = "{$clientId}_{$date}";
        $hours = $this->projectedHours[$key] ?? null;

        if ($hours === null || $hours === '' || $hours == 0) {
            // Delete existing projection if hours cleared
            ProjectedEntry::query()
                ->where('client_id', $clientId)
                ->where('date', $date)
                ->delete();
        } else {
            // Upsert projection
            ProjectedEntry::updateOrCreate(
                ['client_id' => $clientId, 'date' => $date],
                ['hours' => $hours]
            );
        }

        // Reload to update totals
        $this->loadProjectedData();
    }

    public function syncActuals(): void
    {
        $startDate = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $today = now()->startOfDay();

        $synced = 0;
        $skipped = 0;

        foreach ($this->clients as $client) {
            for ($day = 1; $day <= $today->day; $day++) {
                $date = $startDate->copy()->day($day);

                // Get actual hours for this client/date
                $actualHours = TimeEntry::query()
                    ->where('client_id', $client->id)
                    ->where('date', $date)
                    ->sum('hours');

                if ($actualHours > 0) {
                    // Update or create projected entry with actual hours
                    ProjectedEntry::updateOrCreate(
                        ['client_id' => $client->id, 'date' => $date],
                        ['hours' => $actualHours]
                    );
                    $synced++;
                } else {
                    // Check if projection exists to count skips
                    $exists = ProjectedEntry::query()
                        ->where('client_id', $client->id)
                        ->where('date', $date)
                        ->exists();

                    if ($exists) {
                        // Remove projection if no actual hours
                        ProjectedEntry::query()
                            ->where('client_id', $client->id)
                            ->where('date', $date)
                            ->delete();
                        $skipped++;
                    }
                }
            }
        }

        $message = $synced > 0 ? "Synced {$synced} projection(s)" : 'No actuals to sync';
        if ($skipped > 0) {
            $message .= " (removed {$skipped} empty projection(s))";
        }

        Notification::make()
            ->title($message)
            ->success()
            ->send();

        $this->loadProjectedData();
    }

    public function switchViewMode(string $mode): void
    {
        $this->viewMode = $mode;
        $this->loadData();
    }
}
