<?php

namespace App\View\Components\Filament\TimeTracking;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TimeEntryCell extends Component
{
    public function __construct(
        public int|string $clientId,
        public string $date,
        public ?float $hours = null,
        public ?float $revenue = null,
        public bool $isBilled = false,
    ) {}

    public function hasData(): bool
    {
        return $this->hours !== null;
    }

    public function formattedHours(): string
    {
        return number_format($this->hours ?? 0, 2);
    }

    public function formattedRevenue(): string
    {
        return '$'.number_format($this->revenue ?? 0, 2);
    }

    public function cellClasses(): string
    {
        return $this->isBilled
            ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300'
            : 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300';
    }

    public function render(): View
    {
        return view('components.filament.time-tracking.time-entry-cell');
    }
}
