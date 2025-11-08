<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Enums\InvoiceLineType;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\TimeEntry;
use Filament\Actions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Collection;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Preview Email')
                ->action(fn () => redirect()->route('invoice.email.preview', $this->record))
                ->color('warning')
                ->icon('heroicon-o-eye')
                ->openUrlInNewTab(),
            Actions\Action::make('Send')->action('sendInvoiceEmail'),
            Actions\Action::make('Send Test Email')
                ->action('sendTestEmail')
                ->color('info')
                ->icon('heroicon-o-beaker'),
            Actions\Action::make('Import Time Entries')
                ->icon('heroicon-o-clock')
                ->color('info')
                ->visible(fn () => $this->record->client->timeEntries()->unbilled()->exists())
                ->disabled(fn () => $this->record->paid)
                ->schema([
                    DatePicker::make('date_from')
                        ->label('From Date')
                        ->live()
                        ->closeOnDateSelection()
                        ->maxDate(now())
                        ->default(now()->subMonth()->startOfMonth())
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            $filteredIds = $this->getFilteredUnbilledEntries($get('date_from'), $get('date_to'))
                                ->pluck('id')
                                ->toArray();
                            $set('time_entry_ids', $filteredIds);
                        }),
                    DatePicker::make('date_to')
                        ->label('To Date')
                        ->live()
                        ->closeOnDateSelection()
                        ->maxDate(now())
                        ->default(now()->subMonth()->endOfMonth())
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            $filteredIds = $this->getFilteredUnbilledEntries($get('date_from'), $get('date_to'))
                                ->pluck('id')
                                ->toArray();
                            $set('time_entry_ids', $filteredIds);
                        }),
                    CheckboxList::make('time_entry_ids')
                        ->bulkToggleable()
                        ->label('Select Time Entries to Import')
                        ->options(function (Get $get) {
                            return $this->getFilteredUnbilledEntries($get('date_from'), $get('date_to'))
                                ->mapWithKeys(fn ($entry) => [
                                    $entry->id => $entry->date->format('M d, Y').' - '.
                                                  number_format($entry->hours, 2).'h - '.
                                                  $entry->description,
                                ]);
                        })
                        ->default(function (Get $get) {
                            return $this->getFilteredUnbilledEntries($get('date_from'), $get('date_to'))
                                ->pluck('id')
                                ->toArray();
                        })
                        ->required()
                        ->columns(1)
                        ->live(),
                ])
                ->action(function (array $data) {
                    $this->importTimeEntries($data);
                }),
            Actions\DeleteAction::make(),
        ];
    }

    public function sendInvoiceEmail()
    {
        $this->record->sendInvoiceEmail();

        Notification::make()
            ->title('Email Sent')
            ->success()
            ->send();
    }

    public function sendTestEmail()
    {
        $this->record->sendTestEmail();
        $adminEmail = config('mail.admin_email');

        Notification::make()
            ->title('Test Email Sent')
            ->body('Test email sent to: '.$adminEmail)
            ->success()
            ->send();
    }

    protected function getFilteredUnbilledEntries(?string $dateFrom, ?string $dateTo): Collection
    {
        $query = $this->record->client->timeEntries()->unbilled();

        if ($dateFrom && $dateTo) {
            $query->forDateRange($dateFrom, $dateTo);
        } elseif ($dateFrom) {
            $query->where('date', '>=', $dateFrom);
        } elseif ($dateTo) {
            $query->where('date', '<=', $dateTo);
        }

        return $query->orderBy('date', 'desc')->get();
    }

    protected function importTimeEntries(array $data): void
    {
        $timeEntries = TimeEntry::whereIn('id', $data['time_entry_ids'])->get();

        foreach ($timeEntries as $entry) {
            $invoiceLine = $this->record->invoiceLines()->create([
                'type' => InvoiceLineType::Hourly,
                'description' => $entry->description,
                'date' => $entry->date,
                'hourly_rate' => $this->record->client->hourly_rate,
                'hours' => $entry->hours,
            ]);

            $entry->update(['invoice_line_id' => $invoiceLine->id]);
        }

        Notification::make()
            ->title('Time Entries Imported')
            ->body("Imported {$timeEntries->count()} time entries as invoice lines")
            ->success()
            ->send();
    }
}
