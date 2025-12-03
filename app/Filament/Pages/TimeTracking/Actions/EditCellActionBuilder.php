<?php

namespace App\Filament\Pages\TimeTracking\Actions;

use App\Filament\Pages\TimeTracking\TimeTrackingPage;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\HtmlString;

class EditCellActionBuilder
{
    public function __construct(
        protected TimeTrackingPage $page
    ) {}

    public static function make(TimeTrackingPage $page): self
    {
        return new self($page);
    }

    public function build(): Action
    {
        return Action::make('editCell')
            ->modalHeading(function (array $arguments): string {
                $client = $this->page->clients->firstWhere('id', $arguments['clientId']);
                $date = Carbon::parse($arguments['date'])->format('F j, Y');

                return "Time Entries - {$client->name} - {$date}";
            })
            ->fillForm(function (array $arguments): array {
                $this->page->currentEditDate = $arguments['date'];
                $key = $arguments['clientId'].'_'.$arguments['date'];
                $entries = $this->page->timeEntriesData[$key]['entries'] ?? [];

                $formEntries = collect($entries)->map(function ($entry) {
                    return [
                        'id' => $entry['id'],
                        'description' => $entry['description'],
                        'hours' => $entry['hours'],
                        'is_billed' => $entry['is_billed'],
                        'invoice_line_id' => $entry['invoice_line_id'] ?? null,
                    ];
                })->toArray();

                // If no entries exist, add a default empty entry
                if (empty($formEntries)) {
                    $formEntries = [
                        [
                            'description' => '',
                            'hours' => 1,
                            'is_billed' => false,
                        ],
                    ];
                }

                return [
                    'entries' => $formEntries,
                ];
            })
            ->schema(function (): array {
                $placeholderDate = $this->page->currentEditDate
                    ? Carbon::parse($this->page->currentEditDate)->format('M j')
                    : Carbon::now()->format('M j');
                $placeholder = "{$placeholderDate} hours";

                return [
                    Repeater::make('entries')
                        ->table([
                            TableColumn::make('Hours'),
                            TableColumn::make('Description'),
                            TableColumn::make('Billed'),
                        ])
                        ->schema([
                            TextInput::make('hours')
                                ->label('Hours')
                                ->required()
                                ->numeric()
                                ->step(.5)
                                ->minValue(.5)
                                ->maxValue(24)
                                ->suffix('hrs')
                                ->disabled(fn (Get $get): bool => $get('is_billed') ?? false),
                            TextInput::make('description')
                                ->label('Description')
                                ->placeholder($placeholder)
                                ->maxLength(1000)
                                ->disabled(fn (Get $get): bool => $get('is_billed') ?? false),
                            TextEntry::make('billed')
                                ->hiddenLabel()
                                ->state(function (Get $get): ?HtmlString {
                                    if (! ($get('is_billed') ?? false)) {
                                        return null;
                                    }

                                    $invoiceLineId = $get('invoice_line_id');
                                    if (! $invoiceLineId) {
                                        return new HtmlString('<span class="text-sm text-gray-500">Billed</span>');
                                    }

                                    $timeEntry = TimeEntry::find($get('id'));
                                    if (! $timeEntry || ! $timeEntry->invoiceLine) {
                                        return new HtmlString('<span class="text-sm text-gray-500">Billed</span>');
                                    }

                                    $invoiceId = $timeEntry->invoiceLine->invoice_id;
                                    if (! $invoiceId) {
                                        return new HtmlString('<span class="text-sm text-gray-500">Billed</span>');
                                    }

                                    $url = route('filament.admin.resources.invoices.edit', ['record' => $invoiceId]);

                                    return new HtmlString(
                                        '<a href="'.$url.'" class="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">'.
                                        'View Invoice'.
                                        '</a>'
                                    );
                                }),
                        ])
                        ->compact()
                        ->addActionLabel('Add Time Entry')
                        ->reorderable(false)
                        ->deletable(function (array $state): bool {
                            $state = reset($state);
                            $isBilled = $state['is_billed'] ?? false;

                            return ! $isBilled;
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
                $existingEntryIds = collect($this->page->timeEntriesData[$key]['entries'] ?? [])
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

                $this->page->loadData();

                Notification::make()
                    ->success()
                    ->title('Time entries saved')
                    ->send();
            })
            ->modalSubmitActionLabel('Save')
            ->modalWidth('2xl');
    }
}
