<?php

namespace App\View\Components\Filament\TimeTracking;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class MonthNavigation extends Component
{
    public function __construct(
        public string $monthName,
        public bool $canGoPrevious = true,
    ) {}

    public function render(): View
    {
        return view('components.filament.time-tracking.month-navigation');
    }
}
