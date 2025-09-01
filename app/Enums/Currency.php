<?php

namespace App\Enums;

enum Currency: string
{
    case USD = 'USD';
    case CAD = 'CAD';

    public function symbol(): string
    {
        return match ($this) {
            self::USD => '$',
            self::CAD => 'C$',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::USD => 'US Dollar',
            self::CAD => 'Canadian Dollar',
        };
    }

    public function format(float $amount): string
    {
        return $this->symbol().number_format($amount, fmod($amount, 1) ? 2 : 0);
    }
}
