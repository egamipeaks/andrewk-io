<?php

namespace App\Filament\Pages\TimeTracking\Actions;

use App\Filament\Pages\TimeTracking\Schema\TimeEntryForm;
use App\Filament\Pages\TimeTracking\TimeTrackingPage;
use App\Services\TimeEntryService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class EditCellActionBuilder
{
    public function __construct(
        protected TimeTrackingPage $page,
        protected TimeEntryService $timeEntryService
    ) {}

    public function build(): Action
    {
        return Action::make('editCell')
            ->modalHeading(fn (array $arguments): string => $this->getModalHeading($arguments))
            ->fillForm(fn (array $arguments): array => $this->fillForm($arguments))
            ->schema(fn (): array => TimeEntryForm::make($this->page)->build())
            ->action(fn (array $data, array $arguments) => $this->saveEntries($data, $arguments))
            ->modalSubmitActionLabel('Save')
            ->modalWidth('2xl');
    }

    protected function fillForm(array $arguments): array
    {
        $this->page->currentEditDate = $arguments['date'];

        return ['entries' => $this->getFormEntries($arguments)];
    }

    protected function getFormEntries(array $arguments): array
    {
        $key = $arguments['clientId'].'_'.$arguments['date'];
        $entries = $this->page->timeEntriesData[$key]['entries'] ?? [];

        $formEntries = collect($entries)->map(fn ($entry) => [
            'id' => $entry['id'],
            'description' => $entry['description'],
            'hours' => $entry['hours'],
            'is_billed' => $entry['is_billed'],
            'invoice_line_id' => $entry['invoice_line_id'] ?? null,
        ])->toArray();

        if (empty($formEntries)) {
            return [
                [
                    'description' => '',
                    'hours' => 1,
                    'is_billed' => false,
                ],
            ];
        }

        return $formEntries;
    }

    public static function make(TimeTrackingPage $page): self
    {
        return new self($page, app(TimeEntryService::class));
    }

    protected function getModalHeading(array $arguments): string
    {
        $client = $this->page->clients->firstWhere('id', $arguments['clientId']);
        $date = Carbon::parse($arguments['date'])->format('F j, Y');

        return "Time Entries - {$client->name} - {$date}";
    }

    protected function saveEntries(array $data, array $arguments): void
    {
        $key = $this->buildCellKey($arguments['clientId'], $arguments['date']);
        $existingEntryIds = collect($this->page->timeEntriesData[$key]['entries'] ?? [])
            ->pluck('id')
            ->toArray();

        $this->timeEntryService->syncEntriesForCell(
            $arguments['clientId'],
            $arguments['date'],
            $data['entries'],
            $existingEntryIds
        );

        $this->page->loadData();

        Notification::make()
            ->success()
            ->title('Time entries saved')
            ->send();
    }

    protected function buildCellKey(int|string $clientId, string $date): string
    {
        return "{$clientId}_{$date}";
    }
}
