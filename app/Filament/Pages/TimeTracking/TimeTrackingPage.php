<?php

namespace App\Filament\Pages\TimeTracking;

use App\Enums\Currency;
use App\Filament\Pages\TimeTracking\Actions\EditCellActionBuilder;
use App\Filament\Pages\TimeTracking\Concerns\HasMonthNavigation;
use App\Models\TimeEntry;
use App\Services\TimeEntryService;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class TimeTrackingPage extends Page
{
    use HasMonthNavigation;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-clock';

    protected string $view = 'filament.pages.time-tracking.time-tracking-page';

    protected static ?string $navigationLabel = 'Time Tracking';

    protected static ?string $title = 'Time Tracking';

    protected static ?string $slug = 'time-tracking';

    protected static ?int $navigationSort = 2;

    protected static bool $shouldRegisterNavigation = true;

    public array $timeEntriesData = [];

    public ?string $currentEditDate = null;

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
                ->color('primary'),
            Action::make('incomeProjection')
                ->label('Income Projection')
                ->icon('heroicon-o-chart-bar')
                ->color('gray')
                ->url(IncomeProjectionPage::getUrl([
                    'year' => $this->year,
                    'month' => $this->month,
                ])),
        ];
    }

    public function loadData(): void
    {
        $this->loadClients();

        $timeEntries = app(TimeEntryService::class)
            ->getTimeEntries(
                $this->clients->pluck('id'),
                $this->getStartDate(),
                $this->getEndDate()
            );

        $this->timeEntriesData = $timeEntries->map(fn (Collection $entries) => [
            'total_hours' => $entries->sum('hours'),
            'is_billed' => $entries->every(fn (TimeEntry $entry) => $entry->is_billed),
            'entries' => $entries->toArray(),
        ])->toArray();
    }

    public function editCellAction(): Action
    {
        return EditCellActionBuilder::make($this)->build();
    }

    public function getHoursForCell(string $clientId, int $day): ?array
    {
        $date = $this->getStartDate()->day($day)->format('Y-m-d');
        $key = "{$clientId}_{$date}";

        return $this->timeEntriesData[$key] ?? null;
    }

    public function getTotalHoursForClient(string $clientId): float
    {
        $total = 0;

        foreach ($this->timeEntriesData as $key => $data) {
            if (str_starts_with($key, "{$clientId}_")) {
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
}
