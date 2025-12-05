<?php

namespace App\Filament\Pages\TimeTracking\Schema;

use App\Filament\Pages\TimeTracking\TimeTrackingPage;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\HtmlString;

class TimeEntryForm
{
    public function __construct(
        protected TimeTrackingPage $page
    ) {}

    public static function make(TimeTrackingPage $page): self
    {
        return new self($page);
    }

    public function build(): array
    {
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
                        ->state(fn (Get $get): ?HtmlString => $this->getBilledState($get)),
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
    }

    protected function getBilledState(Get $get): ?HtmlString
    {
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
    }
}
