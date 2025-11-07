<?php

namespace App\Enums;

enum InvoiceLineType: string
{
    case Fixed = 'fixed';
    case Hourly = 'hourly';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Fixed Amount',
            self::Hourly => 'Hourly',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Fixed => 'success',
            self::Hourly => 'info',
        };
    }
}
