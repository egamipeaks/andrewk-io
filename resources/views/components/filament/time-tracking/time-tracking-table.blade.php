<div class="overflow-x-auto rounded-lg border border-gray-300 dark:border-gray-700">
    <table class="text-sm table-auto">
        <x-filament.time-tracking.client-header-row :clients="$clients" />
        <tbody class="bg-white dark:bg-gray-900">
            @for($day = 1; $day <= $getDaysInMonth(); $day++)
                <x-filament.time-tracking.day-row :year="$year" :month="$month" :day="$day">
                    @foreach($clients as $client)
                        @php
                            $cellData = $getHoursForCell($client->id, $day);
                            $date = \Carbon\Carbon::create($year, $month, $day)->format('Y-m-d');
                        @endphp
                        <td class="border-r border-gray-200 dark:border-gray-800 w-24">
                            <x-filament.time-tracking.time-entry-cell
                                :client-id="$client->id"
                                :date="$date"
                                :hours="$cellData['total_hours'] ?? null"
                                :revenue="($cellData['total_hours'] ?? 0) * $client->hourly_rate"
                                :is-billed="$cellData['is_billed'] ?? false"
                            />
                        </td>
                    @endforeach
                </x-filament.time-tracking.day-row>
            @endfor
            <x-filament.time-tracking.totals-row>
                @foreach($clients as $client)
                    <x-filament.time-tracking.client-totals-cell
                        :hours="$getTotalHoursForClient($client->id)"
                        :formatted-revenue="$getFormattedTotalRevenueForClient($client->id)"
                    />
                @endforeach
            </x-filament.time-tracking.totals-row>
        </tbody>
    </table>
</div>