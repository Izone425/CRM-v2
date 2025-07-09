<x-filament::page>
    <div class="p-6 bg-white rounded-lg shadow">
        <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-2">
            <!-- Year Filter -->
            <div>
                <label for="year" class="block mb-2 text-sm font-medium text-gray-700">Year</label>
                <div class="relative">
                    <select id="year" wire:model.live="selectedYear" class="w-full h-10 pl-3 pr-10 text-base border border-gray-300 rounded-md appearance-none focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                        @foreach($years as $year => $label)
                            <option value="{{ $year }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Salesperson Filter -->
            <div>
                <label for="salesperson" class="block mb-2 text-sm font-medium text-gray-700">Salesperson</label>
                <div class="relative">
                    <select id="salesperson" wire:model.live="selectedSalesperson" class="w-full h-10 pl-3 pr-10 text-base border border-gray-300 rounded-md appearance-none focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                        @foreach($salespeople as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <style>
            /* Add Calibri-like font styling with size 20px */
            .demo-table-cell {
                font-family: Calibri, 'Segoe UI', sans-serif;
                font-size: 18px;
            }
            .demo-value-below-50 {
                color: #dc2626; /* Red color */
                font-weight: bold;
            }
        </style>

        <!-- Stats Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200" style= "min-width: -webkit-fill-available;">
                <thead>
                    <tr class="bg-gray-50">
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Week</th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Date Range</th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">New Demo</th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Percentage</th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Webinar Demo</th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Percentage</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($weeklyStats as $week)
                        <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 demo-table-cell">Week {{ $week['week_number'] }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 demo-table-cell">{{ $week['date_range'] }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 demo-table-cell {{ $week['new_demo_percentage'] < 50 ? 'demo-value-below-50' : '' }}">
                                    {{ $week['new_demo_count'] }}/{{ $week['new_demo_target'] }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium demo-table-cell
                                    {{ $week['new_demo_percentage'] < 50 ? 'demo-value-below-50' :
                                       ($week['new_demo_percentage'] >= 80 ? 'bg-green-100 text-green-800' :
                                       ($week['new_demo_percentage'] >= 50 ? 'bg-yellow-100 text-yellow-800' :
                                       'bg-red-100 text-red-800')) }}">
                                    {{ $week['new_demo_percentage'] }}%
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 demo-table-cell {{ $week['webinar_demo_percentage'] < 50 ? 'demo-value-below-50' : '' }}">
                                    {{ $week['webinar_demo_count'] }}/{{ $week['webinar_demo_target'] }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium demo-table-cell
                                    {{ $week['webinar_demo_percentage'] < 50 ? 'demo-value-below-50' :
                                       ($week['webinar_demo_percentage'] >= 80 ? 'bg-green-100 text-green-800' :
                                       ($week['webinar_demo_percentage'] >= 50 ? 'bg-yellow-100 text-yellow-800' :
                                       'bg-red-100 text-red-800')) }}">
                                    {{ $week['webinar_demo_percentage'] }}%
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament::page>
