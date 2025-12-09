<?php

namespace App\View\Components\Filament\TimeTracking;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ClientTotalsCell extends Component
{
    public function __construct(
        public float $hours,
        public string $formattedRevenue,
    ) {}

    public function formattedHours(): string
    {
        return number_format($this->hours, 2).' hrs';
    }

    public function render(): View
    {
        return view('components.filament.time-tracking.client-totals-cell');
    }
}
