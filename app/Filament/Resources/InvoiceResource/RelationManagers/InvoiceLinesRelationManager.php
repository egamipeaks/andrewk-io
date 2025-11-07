<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use App\Enums\InvoiceLineType;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class InvoiceLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoiceLines';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
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
            ->defaultSort('date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (InvoiceLineType $state): string => $state->color())
                    ->formatStateUsing(fn (InvoiceLineType $state): string => $state->label())
                    ->sortable(),
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
            ])
            ->headerActions([
                Actions\CreateAction::make(),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make(),
            ]);
    }
}
