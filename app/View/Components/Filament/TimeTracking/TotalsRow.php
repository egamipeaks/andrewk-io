<?php

namespace App\View\Components\Filament\TimeTracking;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TotalsRow extends Component
{
    public function getRowClasses(): string
    {
        return 'border-t-2 border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 font-semibold';
    }

    public function getLabelCellClasses(): string
    {
        return 'sticky left-0 z-10 px-3 py-2 text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 border-r border-gray-300 dark:border-gray-700 whitespace-nowrap';
    }

    public function render(): View
    {
        return view('components.filament.time-tracking.totals-row');
    }
}
