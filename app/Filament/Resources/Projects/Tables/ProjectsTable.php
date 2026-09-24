<?php

namespace App\Filament\Resources\Projects\Tables;

use App\Models\Project;
use Carbon\Carbon;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables;
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
                ->withHours())
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
                    ->numeric(decimalPlaces: 2)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('hours_used')
                    ->label('Used')
                    ->state(fn (Project $record): float => $record->hoursUsed())
                    ->numeric(decimalPlaces: 2)
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->orderByRaw("COALESCE(hours_used_from_entries, 0) + COALESCE(hours_used_from_lines, 0) {$direction}")),
                Tables\Columns\TextColumn::make('hours_remaining')
                    ->label('Left')
                    ->state(fn (Project $record): ?float => $record->hoursRemaining())
                    ->numeric(decimalPlaces: 2)
                    ->color(fn (?float $state): ?string => $state !== null && $state < 0 ? 'danger' : null)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('hours_in_range')
                    ->label('In range')
                    ->state(fn (Project $record): float => self::hoursInRange($record))
                    ->numeric(decimalPlaces: 2),
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
                    ->baseQuery(fn (Builder $query, array $data): Builder => self::withHoursInRange(
                        $query,
                        $data['from'] ?? null,
                        $data['until'] ?? null,
                    ))
                    ->indicateUsing(fn (array $data): ?string => self::dateRangeIndicator(
                        $data['from'] ?? null,
                        $data['until'] ?? null,
                    )),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    protected static function withHoursInRange(Builder $query, ?string $from, ?string $until): Builder
    {
        if (blank($from) && blank($until)) {
            return $query;
        }

        return $query->withHours('hours_in_range', $from, $until);
    }

    protected static function hoursInRange(Project $record): float
    {
        if ($record->hasPreloadedHours('hours_in_range')) {
            return $record->preloadedHours('hours_in_range');
        }

        return $record->hoursUsed();
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
