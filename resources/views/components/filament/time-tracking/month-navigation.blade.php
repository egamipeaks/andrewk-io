<div class="flex items-center justify-between">
    <x-filament::button
        wire:click="previousMonth"
        color="gray"
        size="sm"
        :disabled="!$canGoPrevious"
    >
        <x-heroicon-o-chevron-left class="w-4 h-4" />
        Previous
    </x-filament::button>

    <div class="text-center">
        {{ $subtitle ?? '' }}
        <h2 class="text-xl font-semibold dark:text-white">
            {{ $monthName }}
        </h2>
    </div>

    <x-filament::button wire:click="nextMonth" color="gray" size="sm">
        Next
        <x-heroicon-o-chevron-right class="w-4 h-4" />
    </x-filament::button>
</div>
