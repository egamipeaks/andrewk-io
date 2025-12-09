<?php

namespace App\View\Components\Filament\TimeTracking;

use App\Filament\Pages\TimeTracking\TimeTrackingPage;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class TimeTrackingTable extends Component
{
    public function __construct(
        public Collection $clients,
        public int $year,
        public int $month,
        public TimeTrackingPage $page,
    ) {}

    public function getDaysInMonth(): int
    {
        return Carbon::create($this->year, $this->month)->daysInMonth;
    }

    /** @return array{total_hours: float, is_billed: bool}|null */
    public function getHoursForCell(int $clientId, int $day): ?array
    {
        return $this->page->getHoursForCell($clientId, $day);
    }

    public function getTotalHoursForClient(int $clientId): float
    {
        return $this->page->getTotalHoursForClient($clientId);
    }

    public function getFormattedTotalRevenueForClient(int $clientId): string
    {
        return $this->page->getFormattedTotalRevenueForClient($clientId);
    }

    public function render(): View
    {
        return view('components.filament.time-tracking.time-tracking-table');
    }
}
