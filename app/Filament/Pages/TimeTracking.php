<?php

namespace App\Filament\Pages;

use App\Models\Client;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class TimeTracking extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-clock';

    protected string $view = 'filament.pages.time-tracking';

    protected static ?string $navigationLabel = 'Time Tracking';

    protected static ?string $title = 'Time Tracking';

    protected static ?int $navigationSort = 2;

    public int $year;

    public int $month;

    public Collection $clients;

    public array $timeEntriesData = [];

    public function mount(): void
    {
        $this->year = now()->year;
        $this->month = now()->month;
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->clients = Client::query()
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

    public function previousMonth(): void
    {
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
        return Action::make('editCell')
            ->modalHeading(function (array $arguments): string {
                $client = $this->clients->firstWhere('id', $arguments['clientId']);
                $date = Carbon::parse($arguments['date'])->format('F j, Y');

                return "Time Entries - {$client->name} - {$date}";
            })
            ->fillForm(function (array $arguments): array {
                $key = $arguments['clientId'].'_'.$arguments['date'];
                $entries = $this->timeEntriesData[$key]['entries'] ?? [];

                return [
                    'entries' => collect($entries)->map(function ($entry) {
                        return [
                            'id' => $entry['id'],
                            'description' => $entry['description'],
                            'hours' => $entry['hours'],
                            'is_billed' => TimeEntry::find($entry['id'])?->is_billed ?? false,
                        ];
                    })->toArray(),
                ];
            })
            ->form([
                Repeater::make('entries')
                    ->schema([
                        TextInput::make('id')
                            ->hidden()
                            ->dehydrated(),
                        Textarea::make('description')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),
                        TextInput::make('hours')
                            ->required()
                            ->numeric()
                            ->step(0.25)
                            ->minValue(0.25)
                            ->maxValue(24)
                            ->suffix('hrs'),
                        TextInput::make('is_billed')
                            ->hidden()
                            ->dehydrated(),
                    ])
                    ->columns(2)
                    ->defaultItems(0)
                    ->addActionLabel('Add Time Entry')
                    ->reorderable(false)
                    ->deletable(fn (array $state): bool => ! ($state['is_billed'] ?? false))
                    ->itemLabel(fn (array $state): ?string => $state['hours'] ? "{$state['hours']} hrs" : null),
            ])
            ->action(function (array $data, array $arguments): void {
                $clientId = $arguments['clientId'];
                $date = $arguments['date'];

                // Get existing entry IDs for this cell
                $key = $clientId.'_'.$date;
                $existingEntryIds = collect($this->timeEntriesData[$key]['entries'] ?? [])
                    ->pluck('id')
                    ->toArray();

                $processedIds = [];

                foreach ($data['entries'] as $entryData) {
                    if (! empty($entryData['id'])) {
                        // Update existing entry
                        $entry = TimeEntry::find($entryData['id']);
                        if ($entry && ! $entry->is_billed) {
                            $entry->update([
                                'description' => $entryData['description'],
                                'hours' => $entryData['hours'],
                            ]);
                        }
                        $processedIds[] = $entryData['id'];
                    } else {
                        // Create new entry
                        $newEntry = TimeEntry::create([
                            'client_id' => $clientId,
                            'date' => $date,
                            'description' => $entryData['description'],
                            'hours' => $entryData['hours'],
                        ]);
                        $processedIds[] = $newEntry->id;
                    }
                }

                // Delete entries that were removed (only unbilled ones)
                $entriesToDelete = array_diff($existingEntryIds, $processedIds);
                TimeEntry::whereIn('id', $entriesToDelete)
                    ->whereNull('invoice_line_id')
                    ->delete();

                $this->loadData();

                Notification::make()
                    ->success()
                    ->title('Time entries saved')
                    ->send();
            })
            ->modalSubmitActionLabel('Save')
            ->modalWidth('2xl');
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

    public function getGrandTotalHours(): float
    {
        return collect($this->timeEntriesData)->sum('total_hours');
    }

    public function getGrandTotalRevenue(): float
    {
        $total = 0;

        foreach ($this->clients as $client) {
            $total += $this->getTotalRevenueForClient($client->id);
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
}
