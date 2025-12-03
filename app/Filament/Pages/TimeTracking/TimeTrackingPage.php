<?php

namespace App\Filament\Pages\TimeTracking;

use App\Enums\Currency;
use App\Filament\Pages\TimeTracking\Actions\EditCellActionBuilder;
use App\Filament\Pages\TimeTracking\Concerns\HasMonthNavigation;
use App\Models\TimeEntry;
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

        $timeEntries = TimeEntry::query()
            ->whereIn('client_id', $this->clients->pluck('id'))
            ->whereBetween('date', [$this->getStartDate(), $this->getEndDate()])
            ->with('client')
            ->get()
            ->groupBy(fn (TimeEntry $entry) => "{$entry->client_id}_{$entry->date->format('Y-m-d')}");

        $this->timeEntriesData = $timeEntries->map(function (Collection $entries) {
            return [
                'total_hours' => $entries->sum('hours'),
                'is_billed' => $entries->every(fn (TimeEntry $entry) => $entry->is_billed),
                'entries' => $entries->toArray(),
            ];
        })->toArray();
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

    public function getEntriesForCell(): array
    {
        if (! $this->selectedClientId || ! $this->selectedDate) {
            return [];
        }

        $key = "{$this->selectedClientId}_{$this->selectedDate}";

        return $this->timeEntriesData[$key]['entries'] ?? [];
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
