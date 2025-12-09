<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament.time-tracking.month-navigation
            :month-name="$this->getMonthName()"
            :can-go-previous="$this->canGoToPreviousMonth()"
        >
            <x-slot:subtitle>
                <div class="text-xs text-amber-600 dark:text-amber-400 font-medium uppercase tracking-wide mb-1">
                    Projection Mode
                </div>
            </x-slot:subtitle>
        </x-filament.time-tracking.month-navigation>

        <x-filament.time-tracking.summary-stats-grid
            hours-label="Projected Hours"
            :hours-value="$this->getGrandTotalProjectedHours()"
            revenue-label="Projected Revenue"
            :revenue-value="$this->getGrandTotalProjectedRevenue()"
        />

        <x-filament.time-tracking.projected-time-table
            :clients="$clients"
            :year="$year"
            :month="$month"
            :page="$this"
        />
    </div>
</x-filament-panels::page>
