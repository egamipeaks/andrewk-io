<thead class="bg-gray-50 dark:bg-gray-800 sticky top-0 z-20">
    <tr>
        <th class="sticky left-0 z-30 px-3 py-2 text-left font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 border-r border-gray-300 dark:border-gray-700 whitespace-nowrap">
            Day
        </th>
        @foreach($clients as $client)
            <th class="px-3 py-2 text-center font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 border-r border-gray-300 dark:border-gray-700 whitespace-nowrap w-24" title="{{ $client->name }}">
                <div>{{ $client->shortName() }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 font-normal">
                    {{ $client->formattedHourlyRate() }} / hr
                </div>
            </th>
        @endforeach
    </tr>
</thead>