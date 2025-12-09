<?php

namespace App\View\Components\Filament\TimeTracking;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ProjectionCell extends Component
{
    public function __construct(
        public int $clientId,
        public string $date,
        public string $wireModelKey,
        public float $hourlyRate,
        public ?float $currentValue = null,
    ) {}

    public function projectedRevenue(): ?float
    {
        if ($this->currentValue === null) {
            return null;
        }

        return $this->currentValue * $this->hourlyRate;
    }

    public function formattedRevenue(): string
    {
        $revenue = $this->projectedRevenue();

        if ($revenue === null) {
            return '';
        }

        return '$'.number_format($revenue, 2);
    }

    public function render(): View
    {
        return view('components.filament.time-tracking.projection-cell');
    }
}
