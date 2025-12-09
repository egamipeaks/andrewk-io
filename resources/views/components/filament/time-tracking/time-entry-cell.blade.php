@if($hasData())
    <div
        wire:click="mountAction('editCell', { clientId: '{{ $clientId }}', date: '{{ $date }}' })"
        class="cursor-pointer w-full text-center px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors {{ $cellClasses() }}"
    >
        <div class="font-semibold">{{ $formattedHours() }}</div>
        <div class="text-xs opacity-75">
            {{ $formattedRevenue() }}
        </div>
    </div>
@else
    <div
        wire:click="mountAction('editCell', { clientId: '{{ $clientId }}', date: '{{ $date }}' })"
        class="cursor-pointer w-full text-center text-gray-400 dark:text-gray-600 hover:text-gray-600 dark:hover:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 py-2 rounded transition-colors"
    >
        --
    </div>
@endif
