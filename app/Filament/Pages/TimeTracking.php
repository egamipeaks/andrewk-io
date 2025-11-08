<?php

namespace App\Filament\Pages;

use App\Models\Client;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
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

    protected static bool $shouldRegisterNavigation = true;

    public int $year;

    public int $month;

    public Collection $clients;

    public array $timeEntriesData = [];

    public ?string $currentEditDate = null;

    public function mount(): void
    {
        $this->year = now()->year;
        $this->month = now()->month;
        $this->loadData();
    }

    public function getHeading(): string
    {
        return '';
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
                $this->currentEditDate = $arguments['date'];
                $key = $arguments['clientId'].'_'.$arguments['date'];
                $entries = $this->timeEntriesData[$key]['entries'] ?? [];

                $formEntries = collect($entries)->map(function ($entry) {
                    return [
                        'id' => $entry['id'],
                        'description' => $entry['description'],
                        'hours' => $entry['hours'],
                        'is_billed' => $entry['is_billed'],
                    ];
                })->toArray();

                // If no entries exist, add a default empty entry
                if (empty($formEntries)) {
                    $formEntries = [
                        [
                            'description' => '',
                            'hours' => null,
                            'is_billed' => false,
                        ],
                    ];
                }

                return [
                    'entries' => $formEntries,
                ];
            })
            ->schema(function (): array {
                $placeholderDate = $this->currentEditDate
                    ? Carbon::parse($this->currentEditDate)->format('M j')
                    : Carbon::now()->format('M j');
                $placeholder = "{$placeholderDate} hours";

                return [
                    Repeater::make('entries')
                        ->table([
                            TableColumn::make('Hours'),
                            TableColumn::make('Description'),
                        ])
                        ->schema([
                            TextInput::make('hours')
                                ->label('Hours')
                                ->required()
                                ->numeric()
                                ->step(1)
                                ->minValue(1)
                                ->maxValue(24)
                                ->suffix('hrs'),
                            TextInput::make('description')
                                ->label('Description')
                                ->placeholder($placeholder)
                                ->maxLength(1000),
                        ])
                        ->compact()
                        ->addActionLabel('Add Time Entry')
                        ->reorderable(false)
                        ->deletable(function (array $state): bool {
                            return ! ($state['is_billed'] ?? false);
                        }),
                ];
            })
            ->action(function (array $data, array $arguments): void {
                $clientId = $arguments['clientId'];
                $date = $arguments['date'];

                // Generate default description
                $defaultDescription = Carbon::parse($date)->format('M j').' hours';

                // Get existing entry IDs for this cell
                $key = $clientId.'_'.$date;
                $existingEntryIds = collect($this->timeEntriesData[$key]['entries'] ?? [])
                    ->pluck('id')
                    ->toArray();

                $processedIds = [];

                foreach ($data['entries'] as $entryData) {
                    // Use default description if empty
                    $description = ! empty($entryData['description'])
                        ? $entryData['description']
                        : $defaultDescription;

                    $entryId = $entryData['id'] ?? null;

                    if (is_numeric($entryId)) {
                        $entry = TimeEntry::find($entryId);
                        if ($entry && ! $entry->is_billed) {
                            $entry->update([
                                'description' => $description,
                                'hours' => $entryData['hours'],
                            ]);
                        }
                        $processedIds[] = $entryId;
                    } else {
                        $newEntry = TimeEntry::create([
                            'client_id' => $clientId,
                            'date' => $date,
                            'description' => $description,
                            'hours' => $entryData['hours'],
                        ]);

                        $processedIds[] = $newEntry->id;
                    }
                }

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
