<x-filament::page>
    <div class="p-6 bg-white rounded-lg shadow">
        <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-2" style="margin-bottom:10px;">
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

            <!-- Implementer Filter -->
            <div>
                <label for="implementer" class="block mb-2 text-sm font-medium text-gray-700">Implementer</label>
                <div class="relative">
                    <select id="implementer" wire:model.live="selectedImplementer" class="w-full h-10 pl-3 pr-10 text-base border border-gray-300 rounded-md appearance-none focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                        @foreach($implementers as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <style>
            /* Calibri-like font styling with size 18px */
            .implementer-table-cell {
                font-family: Calibri, 'Segoe UI', sans-serif;
                font-size: 18px;
            }
            /* Highlighted values for emphasis */
            .highlight-value {
                font-weight: bold;
            }
            /* Color coding for different session types */
            .data-migration-cell {
                color: #1d4ed8; /* Blue */
            }
            .system-setting-cell {
                color: #047857; /* Green */
            }
            .weekly-follow-up-cell {
                color: #b45309; /* Amber */
            }
            .total-sessions-cell {
                color: #4338ca; /* Indigo */
                font-weight: bold;
            }
            .current-week {
                background-color: #f8aea1 !important; /* Light amber background */
                border-left: 4px solid #ec5b41; /* Amber left border */
            }
            .total-row {
                background-color: #e5e7eb !important;
                border-top: 2px solid #9ca3af;
                font-weight: bold;
            }

            .total-cell {
                font-size: 20px !important;
                font-weight: bold !important;
            }
        </style>

        <!-- Stats Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200" style="min-width: -webkit-fill-available;">
                <thead>
                    <tr class="bg-gray-50">
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Week</th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Date Range</th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Data Migration<br>Session</th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">System Setting<br>Session</th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Weekly Follow Up<br>Session</th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Total<br>Sessions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($weeklyStats as $week)
                        <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }} {{ $week['week_number'] === $currentWeekNumber ? 'current-week' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 implementer-table-cell">
                                    Week {{ $week['week_number'] }}
                                    @if($week['week_number'] === $currentWeekNumber)
                                        <span class="ml-2 px-2 py-0.5 text-xs font-medium bg-amber-100 text-amber-800 rounded-full">Current</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 implementer-table-cell">{{ $week['date_range'] }}</div>
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <div class="text-sm implementer-table-cell data-migration-cell">
                                    {{ $week['data_migration_count'] }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <div class="text-sm implementer-table-cell system-setting-cell">
                                    {{ $week['system_setting_count'] }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <div class="text-sm implementer-table-cell weekly-follow-up-cell">
                                    {{ $week['weekly_follow_up_count'] }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <div class="text-sm implementer-table-cell total-sessions-cell">
                                    {{ $week['total_sessions'] }}
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td class="px-6 py-4 whitespace-nowrap" colspan="2">
                            <div class="text-sm text-gray-900 implementer-table-cell total-cell">
                                TOTAL
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center whitespace-nowrap">
                            <div class="text-sm implementer-table-cell data-migration-cell total-cell">
                                {{ $totals['data_migration_count'] }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center whitespace-nowrap">
                            <div class="text-sm implementer-table-cell system-setting-cell total-cell">
                                {{ $totals['system_setting_count'] }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center whitespace-nowrap">
                            <div class="text-sm implementer-table-cell weekly-follow-up-cell total-cell">
                                {{ $totals['weekly_follow_up_count'] }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center whitespace-nowrap">
                            <div class="text-sm implementer-table-cell total-sessions-cell total-cell">
                                {{ $totals['total_sessions'] }}
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</x-filament::page>
