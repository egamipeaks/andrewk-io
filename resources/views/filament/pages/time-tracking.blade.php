<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Month Navigation --}}
        <div class="flex items-center justify-between">
            <x-filament::button wire:click="previousMonth" color="gray" size="sm">
                <x-heroicon-o-chevron-left class="w-4 h-4" />
                Previous
            </x-filament::button>

            <h2 class="text-xl font-semibold dark:text-white">
                {{ $this->getMonthName() }}
            </h2>

            <x-filament::button wire:click="nextMonth" color="gray" size="sm">
                Next
                <x-heroicon-o-chevron-right class="w-4 h-4" />
            </x-filament::button>
        </div>

        {{-- Summary Statistics --}}
        <div class="grid grid-cols-2 gap-4">
            <div class="p-4 rounded-lg bg-white dark:bg-gray-800 shadow">
                <div class="text-sm text-gray-500 dark:text-gray-400">Total Hours</div>
                <div class="text-2xl font-bold dark:text-white">
                    {{ number_format($this->getGrandTotalHours(), 2) }}
                </div>
            </div>
            <div class="p-4 rounded-lg bg-white dark:bg-gray-800 shadow">
                <div class="text-sm text-gray-500 dark:text-gray-400">Total Revenue</div>
                <div class="text-2xl font-bold dark:text-white">
                    {{ number_format($this->getGrandTotalRevenue(), 2) }}
                </div>
            </div>
        </div>

        {{-- Spreadsheet Table --}}
        <div class="overflow-x-auto rounded-lg border border-gray-300 dark:border-gray-700">
            <table class="text-sm table-auto">
                <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0 z-20">
                    <tr>
                        <th class="sticky left-0 z-30 px-3 py-2 text-left font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 border-r border-gray-300 dark:border-gray-700 whitespace-nowrap">
                            Day
                        </th>
                        @foreach ($clients as $client)
                            <th class="px-3 py-2 text-center font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 border-r border-gray-300 dark:border-gray-700 whitespace-nowrap w-24" title="{{ $client->name }}">
                                <div>{{ $client->shortName() }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 font-normal">
                                    {{ $client->currency->symbol() }}{{ number_format($client->hourly_rate, 2) }}/hr
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900">
                    @for ($day = 1; $day <= $this->getDaysInMonth(); $day++)
                        @php
                            $currentDate = \Carbon\Carbon::create($year, $month, $day);
                            $isWeekend = $currentDate->isWeekend();
                            $rowBg = $isWeekend ? 'bg-gray-100 dark:bg-gray-800/50' : 'bg-white dark:bg-gray-900';
                            $hoverBg = $isWeekend ? 'hover:bg-gray-200 dark:hover:bg-gray-700' : 'hover:bg-gray-50 dark:hover:bg-gray-800';
                        @endphp
                        <tr class="border-t border-gray-200 dark:border-gray-800 {{ $rowBg }} {{ $hoverBg }}">
                            <td class="sticky left-0 z-10 px-3 py-2 font-medium text-gray-700 dark:text-gray-300 {{ $rowBg }} border-r border-gray-300 dark:border-gray-700 whitespace-nowrap">
                                {{ $currentDate->format('D, n/j') }}
                            </td>
                            @foreach ($clients as $client)
                                @php
                                    $cellData = $this->getHoursForCell($client->id, $day);
                                    $date = $currentDate->format('Y-m-d');
                                @endphp
                                <td class="border-r border-gray-200 dark:border-gray-800 w-24">
                                    @if ($cellData)
{{--                                        {{ ($this->editCellAction)(['clientId' => $client->id, 'date' => $date]) }}--}}
                                        <div
                                            wire:click="mountAction('editCell', { clientId: '{{ $client->id }}', date: '{{ $date }}' })"
                                            class="cursor-pointer w-full text-center px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors
                                                {{ $cellData['is_billed'] ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300' : 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' }}"
                                        >
                                            <div class="font-semibold">{{ number_format($cellData['total_hours'], 2) }}</div>
                                            <div class="text-xs opacity-75">
                                                {{ $client->currency->symbol() }}{{ number_format($cellData['total_hours'] * $client->hourly_rate, 2) }}
                                            </div>
                                        </div>
                                    @else
{{--                                        {{ ($this->editCellAction)(['clientId' => $client->id, 'date' => $date]) }}--}}
                                        <div
                                            wire:click="mountAction('editCell', { clientId: '{{ $client->id }}', date: '{{ $date }}' })"
                                            class="cursor-pointer w-full text-center text-gray-400 dark:text-gray-600 hover:text-gray-600 dark:hover:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 py-2 rounded transition-colors"
                                        >
                                            --
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endfor
                    {{-- Totals Row --}}
                    <tr class="border-t-2 border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 font-semibold">
                        <td class="sticky left-0 z-10 px-3 py-2 text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 border-r border-gray-300 dark:border-gray-700 whitespace-nowrap">
                            Total
                        </td>
                        @foreach ($clients as $client)
                            <td class="px-3 py-2 border-r border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300 whitespace-nowrap text-center w-24">
                                <div>{{ number_format($this->getTotalHoursForClient($client->id), 2) }} hrs</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 font-normal">
                                    {{ $client->currency->symbol() }}{{ number_format($this->getTotalRevenueForClient($client->id), 2) }}
                                </div>
                            </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
