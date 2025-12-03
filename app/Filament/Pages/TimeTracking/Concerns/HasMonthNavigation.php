<?php

namespace App\Filament\Pages\TimeTracking\Concerns;

use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

trait HasMonthNavigation
{
    #[Url]
    public int $year;

    #[Url]
    public int $month;

    public Collection $clients;

    public function initializeMonth(): void
    {
        if (! isset($this->year)) {
            $this->year = now()->year;
        }

        if (! isset($this->month)) {
            $this->month = now()->month;
        }
    }

    public function loadClients(): void
    {
        $this->clients = Client::query()
            ->where('is_active', true)
            ->whereNotNull('hourly_rate')
            ->where('hourly_rate', '>', 0)
            ->orderBy('name')
            ->get();
    }

    public function getStartDate(): Carbon
    {
        return Carbon::create($this->year, $this->month, 1)->startOfMonth();
    }

    public function getEndDate(): Carbon
    {
        return $this->getStartDate()->copy()->endOfMonth();
    }

    public function previousMonth(): void
    {
        if (! $this->canGoToPreviousMonth()) {
            return;
        }

        $date = Carbon::create($this->year, $this->month, 1)->subMonth();
        $this->year = $date->year;
        $this->month = $date->month;
        $this->loadData();
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->addMonth();
        $this->year = $date->year;
        $this->month = $date->month;
        $this->loadData();
    }

    public function canGoToPreviousMonth(): bool
    {
        return true;
    }

    public function getDaysInMonth(): int
    {
        return Carbon::create($this->year, $this->month, 1)->daysInMonth;
    }

    public function getMonthName(): string
    {
        return Carbon::create($this->year, $this->month, 1)->format('F Y');
    }

    public function getHeading(): string
    {
        return '';
    }

    abstract public function loadData(): void;
}
