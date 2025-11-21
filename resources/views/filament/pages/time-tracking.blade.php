<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Month Navigation --}}
        <div class="flex items-center justify-between">
            <x-filament::button
                wire:click="previousMonth"
                color="gray"
                size="sm"
                :disabled="!$this->canGoToPreviousMonth()"
            >
                <x-heroicon-o-chevron-left class="w-4 h-4" />
                Previous
            </x-filament::button>

            <div class="text-center">
                @if($viewMode === 'projection')
                    <div class="text-xs text-amber-600 dark:text-amber-400 font-medium uppercase tracking-wide mb-1">
                        Projection Mode
                    </div>
                @endif
                <h2 class="text-xl font-semibold dark:text-white">
                    {{ $this->getMonthName() }}
                </h2>
            </div>

            <x-filament::button wire:click="nextMonth" color="gray" size="sm">
                Next
                <x-heroicon-o-chevron-right class="w-4 h-4" />
            </x-filament::button>
        </div>

        {{-- Summary Statistics --}}
        <div class="grid grid-cols-2 gap-4">
            <div class="p-4 rounded-lg bg-white dark:bg-gray-800 shadow">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    @if($viewMode === 'projection')
                        Projected Hours
                    @else
                        Total Hours
                    @endif
                </div>
                <div class="text-2xl font-bold dark:text-white">
                    @if($viewMode === 'projection')
                        {{ number_format($this->getGrandTotalProjectedHours(), 2) }}
                    @else
                        {{ number_format($this->getGrandTotalHours(), 2) }}
                    @endif
                </div>
            </div>
            <div class="p-4 rounded-lg bg-white dark:bg-gray-800 shadow">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    @if($viewMode === 'projection')
                        Projected Revenue
                    @else
                        Total Revenue
                    @endif
                </div>
                <div class="text-2xl font-bold dark:text-white">
                    @if($viewMode === 'projection')
                        {{ number_format($this->getGrandTotalProjectedRevenue(), 2) }}
                    @else
                        {{ number_format($this->getGrandTotalRevenue(), 2) }}
                    @endif
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
                                    {{ $client->formattedHourlyRate() }} / hr
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
                                    $key = $client->id . '_' . $date;
                                    $projectedHours = $this->getProjectedHoursForCell($client->id, $day);
                                @endphp
                                <td class="border-r border-gray-200 dark:border-gray-800 w-24">
                                    @if($viewMode === 'projection')
                                        {{-- Projection Mode: Show input --}}
                                        <div class="px-2 py-1">
                                            <input
                                                type="number"
                                                step="0.5"
                                                min="0"
                                                wire:model.lazy="projectedHours.{{ $key }}"
                                                wire:change="saveProjectedEntry({{ $client->id }}, '{{ $date }}')"
                                                placeholder="0"
                                                class="w-full text-center border-0 bg-transparent focus:bg-amber-50 dark:focus:bg-amber-900/20 focus:ring-2 focus:ring-amber-500 dark:focus:ring-amber-400 rounded px-1 py-1 text-sm"
                                            />
                                            @if($projectedHours)
                                                <div class="text-xs text-center text-gray-500 dark:text-gray-400 mt-1">
                                                    {{ $client->currency->symbol() }}{{ number_format($projectedHours * $client->hourly_rate, 2) }}
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        {{-- Actual Mode: Show clickable cell --}}
                                        @if ($cellData)
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
                                            <div
                                                wire:click="mountAction('editCell', { clientId: '{{ $client->id }}', date: '{{ $date }}' })"
                                                class="cursor-pointer w-full text-center text-gray-400 dark:text-gray-600 hover:text-gray-600 dark:hover:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 py-2 rounded transition-colors"
                                            >
                                                --
                                            </div>
                                        @endif
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
                                @if($viewMode === 'projection')
                                    <div>{{ number_format($this->getTotalProjectedHoursForClient($client->id), 2) }} hrs</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 font-normal">
                                        {{ $this->getFormattedTotalProjectedRevenueForClient($client->id) }}
                                    </div>
                                @else
                                    <div>{{ number_format($this->getTotalHoursForClient($client->id), 2) }} hrs</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 font-normal">
                                        {{ $this->getFormattedTotalRevenueForClient($client->id) }}
                                    </div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
