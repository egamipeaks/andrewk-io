<div class="px-2 py-1">
    <input
        type="number"
        step="0.5"
        min="0"
        wire:model.lazy="projectedHours.{{ $wireModelKey }}"
        wire:change="saveProjectedEntry({{ $clientId }}, '{{ $date }}')"
        placeholder="0"
        class="w-full text-center border-0 bg-transparent focus:bg-amber-50 dark:focus:bg-amber-900/20 focus:ring-2 focus:ring-amber-500 dark:focus:ring-amber-400 rounded px-1 py-1 text-sm"
    />
    @if($currentValue)
        <div class="text-xs text-center text-gray-500 dark:text-gray-400 mt-1">
            {{ $formattedRevenue() }}
        </div>
    @endif
</div>
