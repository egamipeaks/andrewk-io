<?php

namespace App\Filament\Resources;

use App\Enums\Currency;
use App\Filament\Resources\ClientResource\Pages;
use App\Models\Client;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email_from')
                    ->label('Send Invoices From')
                    ->email()
                    ->maxLength(255)
                    ->placeholder(config('mail.from.address'))
                    ->helperText('Email address to use when sending invoices to this client. Leave blank to use the default.'),
                Forms\Components\Select::make('currency')
                    ->options(Currency::class)
                    ->default(Currency::USD)
                    ->required(),
                Forms\Components\TextInput::make('hourly_rate')
                    ->label('Hourly Rate')
                    ->numeric()
                    ->prefix('$')
                    ->step(0.01)
                    ->placeholder('150.00'),
            ]);
    }

    public static function table(Table $table): Table
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
            ->actions([
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
