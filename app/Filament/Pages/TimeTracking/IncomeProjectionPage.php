<?php

namespace App\Filament\Pages\TimeTracking;

use App\Enums\Currency;
use App\Filament\Pages\TimeTracking\Concerns\HasMonthNavigation;
use App\Models\ProjectedEntry;
use App\Models\TimeEntry;
use App\Services\ProjectedEntryService;
use App\Services\TimeEntryService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class IncomeProjectionPage extends Page
{
    use HasMonthNavigation;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-chart-bar';

    protected string $view = 'filament.pages.time-tracking.income-projection-page';

    protected static ?string $navigationLabel = 'Income Projection';

    protected static ?string $title = 'Income Projection';

    protected static ?string $slug = 'income-projection';

    protected static ?int $navigationSort = 3;

    protected static bool $shouldRegisterNavigation = false;

    public array $projectedEntriesData = [];

    public array $projectedHours = [];

    public function mount(): void
    {
        $this->initializeMonth();
        $this->loadData();
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('timeTracking')
                ->label('Time Tracking')
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->url(TimeTrackingPage::getUrl([
                    'year' => $this->year,
                    'month' => $this->month,
                ])),
            Action::make('incomeProjection')
                ->label('Income Projection')
                ->icon('heroicon-o-chart-bar')
                ->color('primary'),
            Action::make('syncActuals')
                ->label('Sync Actuals')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->visible(fn () => $this->year == now()->year && $this->month == now()->month)
                ->action(fn () => $this->syncActuals()),
        ];
    }

    public function canGoToPreviousMonth(): bool
    {
        $currentDate = now()->startOfMonth();
        $navDate = Carbon::create($this->year, $this->month, 1);

        return ! $navDate->isSameMonth($currentDate);
    }

    public function loadData(): void
    {
        $this->loadClients();

        $projectedEntries = app(ProjectedEntryService::class)
            ->getProjectedEntries(
                $this->clients->pluck('id'),
                $this->getStartDate(),
                $this->getEndDate()
            );

        $this->projectedEntriesData = $projectedEntries->map(fn (Collection $entries) => [
            'total_hours' => $entries->sum('hours'),
            'entries' => $entries->toArray(),
        ])->toArray();

        $this->projectedHours = [];
        foreach ($projectedEntries as $key => $entries) {
            $this->projectedHours[$key] = $entries->sum('hours');
        }

        $this->prefillActualsForPastDays();
    }

    protected function prefillActualsForPastDays(): void
    {
        if ($this->year != now()->year || $this->month != now()->month) {
            return;
        }

        $today = now()->startOfDay();

        $actualEntries = app(TimeEntryService::class)
            ->getTimeEntries(
                $this->clients->pluck('id'),
                $this->getStartDate(),
                $today
            );

        foreach ($actualEntries as $key => $entries) {
            if (! isset($this->projectedHours[$key])) {
                $totalHours = $entries->sum('hours');
                if ($totalHours > 0) {
                    $this->projectedHours[$key] = $totalHours;
                }
            }
        }
    }

    public function getProjectedHoursForCell(int $clientId, int $day): ?float
    {
        $date = $this->getStartDate()->day($day)->format('Y-m-d');
        $key = "{$clientId}_{$date}";

        return $this->projectedHours[$key] ?? null;
    }

    public function getTotalProjectedHoursForClient(int $clientId): float
    {
        $total = 0;

        foreach ($this->projectedHours as $key => $hours) {
            if (str_starts_with($key, "{$clientId}_")) {
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

        if ($hours === '' || $hours == 0) {
            ProjectedEntry::query()
                ->where('client_id', $clientId)
                ->where('date', $date)
                ->delete();
        } else {
            ProjectedEntry::updateOrCreate(
                ['client_id' => $clientId, 'date' => $date],
                ['hours' => $hours]
            );
        }

        $this->loadData();
    }

    public function syncActuals(): void
    {
        $startDate = $this->getStartDate();
        $today = now()->startOfDay();

        $synced = 0;
        $skipped = 0;

        foreach ($this->clients as $client) {
            for ($day = 1; $day <= $today->day; $day++) {
                $date = $startDate->copy()->day($day);

                $actualHours = TimeEntry::query()
                    ->where('client_id', $client->id)
                    ->where('date', $date)
                    ->sum('hours');

                if ($actualHours > 0) {
                    ProjectedEntry::updateOrCreate(
                        ['client_id' => $client->id, 'date' => $date],
                        ['hours' => $actualHours]
                    );
                    $synced++;
                } else {
                    $exists = ProjectedEntry::query()
                        ->where('client_id', $client->id)
                        ->where('date', $date)
                        ->exists();

                    if ($exists) {
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

        $this->loadData();
    }
}
