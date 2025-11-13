<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\Currency;
use App\Models\Client;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                        if (! $state) {
                            return;
                        }

                        if (! $client = Client::find($state)) {
                            return;
                        }

                        $currency = $client->currency;
                        $set('currency', $currency->value);
                        $set('conversion_rate', $currency->fromUsdRate());
                    }),
                Select::make('currency')
                    ->options(Currency::class)
                    ->live()
                    ->columnSpan(function ($get) {
                        /** @var Currency $currency */
                        $currency = $get('currency');

                        return $currency && $currency->isUsd() ? 2 : 1;
                    })
                    ->afterStateUpdated(function ($set, $state) {
                        if (! $state) {
                            return;
                        }

                        $currency = $state;
                        $set('currency', $currency->value);
                        $set('conversion_rate', $currency->fromUsdRate());
                    })
                    ->required(),
                TextInput::make('conversion_rate')
                    ->label('Conversion Rate (USD to Client Currency)')
                    ->numeric()
                    ->hidden(function ($get) {
                        /** @var Currency $currency */
                        $currency = $get('currency');

                        if (! $currency) {
                            return true;
                        }

                        return $currency->isUsd();
                    })
                    ->disabled(function ($state, $record) {
                        if (! $record) {
                            return false;
                        }

                        return $record->isSent() || $record->isPaid();
                    })
                    ->step(0.000001)
                    ->helperText('Locked at invoice send or paid. Shows how USD amounts convert to client currency.'),
                DatePicker::make('due_date')
                    ->default(now()->addDays(15)->addMonth()->startOfMonth())
                    ->required(),
                Toggle::make('paid')
                    ->required(),
                Textarea::make('note')
                    ->maxLength(65535),
                TextInput::make('total')
                    ->disabled()
                    ->formatStateUsing(fn ($record) => $record?->formattedTotalUsd())
                    ->label('Total (USD)'),
            ]);
    }
}
