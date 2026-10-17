<?php

namespace App\Filament\Resources\Clients\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class HoursUpdateSendsRelationManager extends RelationManager
{
    protected static string $relationship = 'hoursUpdateSends';

    protected static ?string $title = 'Hours Updates Sent';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->label('Email Address'),
                Tables\Columns\TextColumn::make('hours')
                    ->numeric(2),
                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Sent At')
                    ->dateTime(timezone: 'America/Chicago')
                    ->sortable(),
            ])
            ->defaultSort('sent_at', 'desc');
    }
}
