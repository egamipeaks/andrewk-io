<?php

namespace App\Filament\Resources\Invoices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('due_date', 'desc')
            ->columns([
                TextColumn::make('client.name')
                    ->label('Client'),
                TextColumn::make('id'),
                TextColumn::make('currency')
                    ->badge()
                    ->color(fn ($state) => match ($state?->value) {
                        'USD' => 'success',
                        'CAD' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('total')
                    ->formatStateUsing(fn ($record): string => $record->formattedTotalUsd()),
                TextColumn::make('total_hours')
                    ->label('Hours')
                    ->numeric(decimalPlaces: 1),
                TextColumn::make('due_date')
                    ->sortable()
                    ->date(),
                IconColumn::make('sent')
                    ->label('Sent')
                    ->boolean()
                    ->getStateUsing(fn ($record): bool => $record->isSent()),
                IconColumn::make('paid')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('client')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),
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
