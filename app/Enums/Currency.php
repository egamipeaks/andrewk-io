<?php

namespace App\Enums;

enum Currency: string
{
    case USD = 'USD';
    case CAD = 'CAD';

    public function isUsd(): bool
    {
        return $this === self::USD;
    }

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

    public function toUsdRate(): float
    {
        return match ($this) {
            self::USD => 1.0,
            self::CAD => 0.71,
        };
    }

    public function toUsd(float $amount): float
    {
        return round($amount * $this->toUsdRate(), 2);
    }

    public function fromUsdRate(): float
    {
        $toUsdRate = $this->toUsdRate();

        $rate = match ($this) {
            self::USD => 1.0,
            self::CAD => 1.0 / $toUsdRate,
        };

        return round($rate, 3);
    }

    public function fromUsd(float $amountInUsd): float
    {
        return round($amountInUsd * $this->fromUsdRate(), 2);
    }
}
