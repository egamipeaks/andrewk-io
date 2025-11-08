<?php

namespace App\Filament\Resources\Clients\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Table;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('currency')
                    ->badge()
                    ->color(fn ($state) => match ($state?->value) {
                        'USD' => 'success',
                        'CAD' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('hourly_rate')
                    ->label('Hourly Rate')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unbilled_hours')
                    ->label('Unbilled Hours')
                    ->getStateUsing(fn ($record) => $record->timeEntries()->unbilled()->sum('hours'))
                    ->numeric(decimalPlaces: 1)
                    ->placeholder('0.0')
                    ->sortable(false),
                Tables\Columns\TextColumn::make('unbilled_revenue')
                    ->label('Unbilled Revenue')
                    ->getStateUsing(function ($record) {
                        $hours = $record->timeEntries()->unbilled()->sum('hours');

                        return $hours * ($record->hourly_rate ?? 0);
                    })
                    ->money()
                    ->placeholder('$0.00')
                    ->sortable(false),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
