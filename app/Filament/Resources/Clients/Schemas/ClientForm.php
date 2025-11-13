<?php

namespace App\Filament\Resources\Clients\Schemas;

use App\Enums\Currency;
use Filament\Forms;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema, bool $isCreating = false): Schema
    {
        $components = [
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
                ->required()
                ->live(),
            Forms\Components\TextInput::make('hourly_rate')
                ->label('Hourly Rate (USD)')
                ->numeric()
                ->prefix('$')
                ->step(0.01)
                ->placeholder('100.00')
                ->helperText('All hourly rates are stored in USD. Invoices will be sent in the client\'s preferred currency using the current exchange rate.'),
            Forms\Components\Toggle::make('is_active')
                ->label('Active')
                ->default(true)
                ->inline(false)
                ->helperText('Inactive clients won\'t appear on the Time Tracking page.'),
        ];

        return $schema->components($components);
    }
}
