<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament.time-tracking.month-navigation
            :month-name="$this->getMonthName()"
            :can-go-previous="$this->canGoToPreviousMonth()"
        />

        <x-filament.time-tracking.summary-stats-grid
            hours-label="Total Hours"
            :hours-value="$this->getGrandTotalHours()"
            revenue-label="Total Revenue"
            :revenue-value="$this->getGrandTotalRevenue()"
        />

        <x-filament.time-tracking.time-tracking-table
            :clients="$clients"
            :year="$year"
            :month="$month"
            :page="$this"
        />
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
