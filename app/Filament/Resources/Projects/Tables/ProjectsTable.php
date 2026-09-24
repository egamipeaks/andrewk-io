<?php

namespace App\Filament\Resources\Projects\Tables;

use App\Models\Project;
use Carbon\Carbon;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with('client')
                ->withSum('timeEntries as hours_used', 'hours'))
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Project')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('budget_hours')
                    ->label('Budget')
                    ->numeric(decimalPlaces: 1)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('hours_used')
                    ->label('Used')
                    ->state(fn (Project $record): float => $record->hoursUsed())
                    ->numeric(decimalPlaces: 1)
                    ->sortable(),
                Tables\Columns\TextColumn::make('hours_remaining')
                    ->label('Left')
                    ->state(fn (Project $record): ?float => $record->hoursRemaining())
                    ->numeric(decimalPlaces: 1)
                    ->color(fn (?float $state): ?string => $state !== null && $state < 0 ? 'danger' : null)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('hours_in_range')
                    ->label('In range')
                    ->state(fn (Project $record, HasTable $livewire): float => self::hoursInRange($record, $livewire))
                    ->numeric(decimalPlaces: 1),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('client')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->default(true),
                Filter::make('date_range')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->indicateUsing(fn (array $data): ?string => self::dateRangeIndicator(
                        $data['from'] ?? null,
                        $data['until'] ?? null,
                    )),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    protected static function hoursInRange(Project $record, HasTable $livewire): float
    {
        $data = $livewire->getTableFilterState('date_range') ?? [];

        $from = $data['from'] ?? null;
        $until = $data['until'] ?? null;

        if (blank($from) && blank($until)) {
            return $record->hoursUsed();
        }

        return (float) $record->timeEntries()
            ->when($from, fn (Builder $query, string $from) => $query->whereDate('date', '>=', $from))
            ->when($until, fn (Builder $query, string $until) => $query->whereDate('date', '<=', $until))
            ->sum('hours');
    }

    protected static function dateRangeIndicator(?string $from, ?string $until): ?string
    {
        if (blank($from) && blank($until)) {
            return null;
        }

        $fromLabel = filled($from) ? Carbon::parse($from)->format('M j, Y') : 'the start';
        $untilLabel = filled($until) ? Carbon::parse($until)->format('M j, Y') : 'today';

        return "Hours from {$fromLabel} to {$untilLabel}";
    }
}
