<?php

namespace App\Filament\Resources\Invoices\RelationManagers;

use App\Enums\InvoiceLineType;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class InvoiceLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoiceLines';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Forms\Components\Placeholder::make('time_entries_warning')
                    ->content(function ($record) {
                        if (! $record) {
                            return null;
                        }

                        $count = $record->timeEntries()->count();
                        if ($count === 0) {
                            return null;
                        }

                        return '⚠️ This line was created from '.$count.' time '.
                               ($count === 1 ? 'entry' : 'entries').
                               ". Changes here won't affect the source entries.";
                    })
                    ->visible(fn ($record) => $record && $record->timeEntries()->count() > 0)
                    ->columnSpanFull(),
                Forms\Components\Select::make('type')
                    ->label('Type')
                    ->options([
                        InvoiceLineType::Fixed->value => InvoiceLineType::Fixed->label(),
                        InvoiceLineType::Hourly->value => InvoiceLineType::Hourly->label(),
                    ])
                    ->default(InvoiceLineType::Hourly->value)
                    ->required()
                    ->live()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->columnSpanFull()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('date')
                    ->label('Date')
                    ->default(now())
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('amount')
                    ->label('Amount')
                    ->numeric()
                    ->prefix('$')
                    ->step(0.01)
                    ->required()
                    ->visible(fn (Get $get): bool => $get('type') === InvoiceLineType::Fixed->value),
                Forms\Components\TextInput::make('hourly_rate')
                    ->label('Hourly Rate')
                    ->numeric()
                    ->prefix('$')
                    ->step(0.01)
                    ->default(function ($livewire) {
                        return $livewire->getOwnerRecord()->client->hourly_rate ?? null;
                    })
                    ->required()
                    ->visible(fn (Get $get): bool => $get('type') === InvoiceLineType::Hourly->value),
                Forms\Components\TextInput::make('hours')
                    ->label('Hours')
                    ->numeric()
                    ->step(0.25)
                    ->default(1)
                    ->placeholder('8.0')
                    ->required()
                    ->visible(fn (Get $get): bool => $get('type') === InvoiceLineType::Hourly->value),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(30)
            ->paginationPageOptions([30, 60])
            ->defaultSort('date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (InvoiceLineType $state): string => $state->color())
                    ->formatStateUsing(fn (InvoiceLineType $state): string => $state->label())
                    ->sortable(),
                Tables\Columns\TextColumn::make('source')
                    ->label('Source')
                    ->badge()
                    ->color('warning')
                    ->getStateUsing(function ($record) {
                        $count = $record->timeEntries()->count();
                        if ($count === 0) {
                            return null;
                        }

                        return $count === 1 ? 'Time Entry' : "{$count} Time Entries";
                    })
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->limit(80)
                    ->sortable(),
                Tables\Columns\TextColumn::make('hourly_rate')
                    ->label('Hourly Rate')
                    ->money()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('hours')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->formatStateUsing(fn ($record): string => $record->formattedSubTotal())
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        InvoiceLineType::Fixed->value => InvoiceLineType::Fixed->label(),
                        InvoiceLineType::Hourly->value => InvoiceLineType::Hourly->label(),
                    ]),
                Tables\Filters\TernaryFilter::make('has_time_entries')
                    ->label('Has Time Entries')
                    ->queries(
                        true: fn ($query) => $query->has('timeEntries'),
                        false: fn ($query) => $query->doesntHave('timeEntries'),
                    ),
            ])
            ->headerActions([
                Actions\CreateAction::make(),
            ])
            ->actions([
                Actions\Action::make('viewSourceEntries')
                    ->label('Entries')
                    ->icon('heroicon-o-clock')
                    ->color('info')
                    ->visible(fn ($record) => $record->timeEntries()->count() > 0)
                    ->modalHeading('Source Time Entries')
                    ->modalContent(fn ($record) => view('filament.components.time-entries-list', [
                        'entries' => $record->timeEntries,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Actions\EditAction::make(),
                Actions\DeleteAction::make()
                    ->before(function ($record, Actions\DeleteAction $action) {
                        $timeEntriesCount = $record->timeEntries()->count();
                        if ($timeEntriesCount > 0) {
                            $record->timeEntries()->update(['invoice_line_id' => null]);
                        }
                    })
                    ->requiresConfirmation(fn ($record) => $record->timeEntries()->count() > 0)
                    ->modalDescription(function ($record) {
                        $count = $record->timeEntries()->count();
                        if ($count > 0) {
                            return "This invoice line has {$count} linked time ".
                                   ($count === 1 ? 'entry' : 'entries').
                                   '. They will be unlinked and marked as unbilled.';
                        }

                        return null;
                    }),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make(),
                Actions\BulkAction::make('mergeHourlyLines')
                    ->label('Merge Hourly Lines')
                    ->icon('heroicon-o-arrows-pointing-in')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Merge Hourly Lines')
                    ->modalDescription(function (Collection $records) {
                        $hourlyLines = $records->filter(fn ($record) => $record->type === InvoiceLineType::Hourly);

                        if ($hourlyLines->count() < 2) {
                            return 'Please select at least 2 hourly invoice lines to merge.';
                        }

                        $rateGroups = $hourlyLines->groupBy('hourly_rate');
                        $groupCount = $rateGroups->count();

                        if ($groupCount === 1) {
                            return "This will merge {$hourlyLines->count()} hourly lines into 1 invoice line.";
                        }

                        return "This will merge {$hourlyLines->count()} hourly lines into {$groupCount} invoice lines (grouped by rate).";
                    })
                    ->modalSubmitActionLabel('Merge Lines')
                    ->action(function (Collection $records) {
                        $this->mergeHourlyLines($records);
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    protected function mergeHourlyLines(Collection $records): void
    {
        $hourlyLines = $records->filter(fn ($record) => $record->type === InvoiceLineType::Hourly);

        if ($hourlyLines->count() < 2) {
            Notification::make()
                ->title('Cannot Merge')
                ->body('Please select at least 2 hourly invoice lines to merge.')
                ->warning()
                ->send();

            return;
        }

        $invoice = $this->getOwnerRecord();
        $rateGroups = $hourlyLines->groupBy('hourly_rate');
        $mergedLinesCreated = 0;
        $totalLinesProcessed = 0;

        foreach ($rateGroups as $rate => $lines) {
            $totalHours = $lines->sum('hours');
            $latestDate = $lines->max('date');
            $firstOfMonth = Carbon::parse($latestDate)->startOfMonth();
            $monthName = $firstOfMonth->format('F');

            $mergedLine = $invoice->invoiceLines()->create([
                'type' => InvoiceLineType::Hourly,
                'description' => "Hours for {$monthName} (at {$rate}/hr)",
                'date' => $firstOfMonth,
                'hourly_rate' => $rate,
                'hours' => $totalHours,
            ]);

            foreach ($lines as $line) {
                $line->timeEntries()->update(['invoice_line_id' => $mergedLine->id]);
                $line->delete();
                $totalLinesProcessed++;
            }

            $mergedLinesCreated++;
        }

        $message = $mergedLinesCreated === 1
            ? "Merged {$totalLinesProcessed} lines into 1 invoice line"
            : "Merged {$totalLinesProcessed} lines into {$mergedLinesCreated} invoice lines (grouped by rate)";

        Notification::make()
            ->title('Lines Merged Successfully')
            ->body($message)
            ->success()
            ->send();
    }
}
