<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\Currency;
use App\Models\Client;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema, bool $isCreating = false): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('client_id')
                    ->relationship('client', 'name')
                    ->required()
                    ->columnSpanFull()
                    ->live()
                    ->afterStateUpdated(function ($set, $state) {
                        if ($state) {
                            $client = Client::find($state);
                            if ($client) {
                                $set('currency', $client->currency->value);
                            }
                        }
                    }),
                Select::make('currency')
                    ->options(Currency::class)
                    ->default(Currency::USD)
                    ->required(),
                DatePicker::make('due_date')
                    ->default(now()->addDays(15)->addMonth()->startOfMonth())
                    ->required(),
                Toggle::make('paid')
                    ->required(),
                Textarea::make('note')
                    ->maxLength(65535),
            ]);
    }
}
