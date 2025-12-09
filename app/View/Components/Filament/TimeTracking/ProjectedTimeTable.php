<?php

namespace App\View\Components\Filament\TimeTracking;

use App\Filament\Pages\TimeTracking\IncomeProjectionPage;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class ProjectedTimeTable extends Component
{
    public function __construct(
        public Collection $clients,
        public int $year,
        public int $month,
        public IncomeProjectionPage $page,
    ) {}

    public function getDaysInMonth(): int
    {
        return Carbon::create($this->year, $this->month)->daysInMonth;
    }

    public function getProjectedHoursForCell(int $clientId, int $day): ?float
    {
        return $this->page->getProjectedHoursForCell($clientId, $day);
    }

    public function getTotalProjectedHoursForClient(int $clientId): float
    {
        return $this->page->getTotalProjectedHoursForClient($clientId);
    }

    public function getFormattedTotalProjectedRevenueForClient(int $clientId): string
    {
        return $this->page->getFormattedTotalProjectedRevenueForClient($clientId);
    }

    public function render(): View
    {
        return view('components.filament.time-tracking.projected-time-table');
    }
}
