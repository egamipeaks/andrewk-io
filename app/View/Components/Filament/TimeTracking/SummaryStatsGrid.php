<?php

namespace App\View\Components\Filament\TimeTracking;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SummaryStatsGrid extends Component
{
    public function __construct(
        public string $hoursLabel,
        public float $hoursValue,
        public string $revenueLabel,
        public float $revenueValue,
    ) {}

    public function formattedHours(): string
    {
        return number_format($this->hoursValue, 2);
    }

    public function formattedRevenue(): string
    {
        return number_format($this->revenueValue, 2);
    }

    public function render(): View
    {
        return view('components.filament.time-tracking.summary-stats-grid');
    }
}
