<?php

namespace App\View\Components\Filament\TimeTracking;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DayRow extends Component
{
    public Carbon $date;

    public function __construct(
        public int $year,
        public int $month,
        public int $day,
    ) {
        $this->date = Carbon::create($year, $month, $day);
    }

    public function isWeekend(): bool
    {
        return $this->date->isWeekend();
    }

    public function getRowClasses(): string
    {
        $isWeekend = $this->isWeekend();
        $rowBg = $isWeekend ? 'bg-gray-100 dark:bg-gray-800/50' : 'bg-white dark:bg-gray-900';
        $hoverBg = $isWeekend ? 'hover:bg-gray-200 dark:hover:bg-gray-700' : 'hover:bg-gray-50 dark:hover:bg-gray-800';

        return "border-t border-gray-200 dark:border-gray-800 {$rowBg} {$hoverBg}";
    }

    public function getDayCellClasses(): string
    {
        $rowBg = $this->isWeekend() ? 'bg-gray-100 dark:bg-gray-800/50' : 'bg-white dark:bg-gray-900';

        return "sticky left-0 z-10 px-3 py-2 font-medium text-gray-700 dark:text-gray-300 {$rowBg} border-r border-gray-300 dark:border-gray-700 whitespace-nowrap";
    }

    public function getDayLabel(): string
    {
        return $this->date->format('D, n/j');
    }

    public function getFormattedDate(): string
    {
        return $this->date->format('Y-m-d');
    }

    public function render(): View
    {
        return view('components.filament.time-tracking.day-row');
    }
}
