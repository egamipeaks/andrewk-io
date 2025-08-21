<?php

namespace App\Filament\Resources;

use App\Enums\Currency;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('client_id')
                    ->relationship('client', 'name')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($set, $state) {
                        if ($state) {
                            $client = \App\Models\Client::find($state);
                            if ($client) {
                                $set('currency', $client->currency->value);
                            }
                        }
                    }),
                Forms\Components\Select::make('currency')
                    ->options(Currency::class)
                    ->default(Currency::USD)
                    ->required(),
                Forms\Components\Toggle::make('paid')
                    ->required(),
                Forms\Components\DatePicker::make('due_date')
                    ->required(),
                Forms\Components\Textarea::make('note')
                    ->maxLength(65535),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client'),
                Tables\Columns\TextColumn::make('id'),
                Tables\Columns\TextColumn::make('currency')
                    ->badge()
                    ->color(fn ($state) => match ($state?->value) {
                        'USD' => 'success',
                        'CAD' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total')
                    ->formatStateUsing(fn ($record): string => $record->formattedTotal()),
                Tables\Columns\TextColumn::make('due_date')
                    ->date(),
                Tables\Columns\IconColumn::make('paid')
                    ->boolean(),
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
            RelationManagers\InvoiceLinesRelationManager::class,
            RelationManagers\InvoiceEmailSendsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderBy('id', 'desc');
    }
}
