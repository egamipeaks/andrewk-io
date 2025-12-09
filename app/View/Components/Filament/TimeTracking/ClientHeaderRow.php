<?php

namespace App\View\Components\Filament\TimeTracking;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class ClientHeaderRow extends Component
{
    public function __construct(
        public Collection $clients,
    ) {}

    public function render(): View
    {
        return view('components.filament.time-tracking.client-header-row');
    }
}
