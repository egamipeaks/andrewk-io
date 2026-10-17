<?php

namespace App\Enums;

use Carbon\CarbonInterface;
use Filament\Support\Contracts\HasLabel;

enum HoursUpdateFrequency: string implements HasLabel
{
    case Off = 'off';
    case Weekly = 'weekly';
    case MidMonth = 'mid_month';

    public function getLabel(): string
    {
        return match ($this) {
            self::Off => 'Off',
            self::Weekly => 'Weekly (Mondays)',
            self::MidMonth => 'Mid-month (the 15th)',
        };
    }

    public function isDueOn(CarbonInterface $date): bool
    {
        return match ($this) {
            self::Off => false,
            self::Weekly => $date->isMonday(),
            self::MidMonth => $date->day === 15,
        };
    }
}
