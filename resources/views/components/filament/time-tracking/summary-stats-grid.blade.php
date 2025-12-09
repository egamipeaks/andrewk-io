<div class="grid grid-cols-2 gap-4">
    <div class="p-4 rounded-lg bg-white dark:bg-gray-800 shadow">
        <div class="text-sm text-gray-500 dark:text-gray-400">
            {{ $hoursLabel }}
        </div>
        <div class="text-2xl font-bold dark:text-white">
            {{ $formattedHours() }}
        </div>
    </div>
    <div class="p-4 rounded-lg bg-white dark:bg-gray-800 shadow">
        <div class="text-sm text-gray-500 dark:text-gray-400">
            {{ $revenueLabel }}
        </div>
        <div class="text-2xl font-bold dark:text-white">
            {{ $formattedRevenue() }}
        </div>
    </div>
</div>
