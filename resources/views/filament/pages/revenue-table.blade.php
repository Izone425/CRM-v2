<x-filament::page>
    <div class="revenue-container">
        <div class="header-actions">
            <div class="flex items-center justify-between gap-4 mb-4">
                <h2 class="text-xl font-bold text-gray-800">TimeTec HR - Sales Revenue</h2>

                <div class="flex items-center gap-4">
                    <div class="filters">
                        <div class="flex items-center gap-4">
                            <label for="selectedYear" class="form-label">Year:</label>
                            <select id="selectedYear" wire:model.live="selectedYear" wire:loading.class="opacity-50" class="select-control">
                                @foreach($years as $year => $label)
                                    <option value="{{ $year }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <div wire:loading wire:target="selectedYear" class="ml-2">
                                <svg class="w-5 h-5 text-gray-500 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse revenue-table">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left border border-gray-400">{{ $selectedYear }}</th>
                        @foreach($salespeople as $person)
                            <th class="px-4 py-2 bg-yellow-200 border border-gray-400">{{ $person }}</th>
                        @endforeach
                        <th class="px-4 py-2 bg-yellow-300 border border-gray-400">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($revenueData as $monthNum => $data)
                        <tr class="{{ ($isCurrentYear && $monthNum == $currentMonth) ? 'current-month-row' : '' }}">
                            <td class="px-4 py-2 font-bold border border-gray-400 text-left {{ ($isCurrentYear && $monthNum == $currentMonth) ? 'current-month' : '' }}">
                                {{ $data['month_name'] }}
                                @if($isCurrentYear && $monthNum == $currentMonth)
                                    <span class="ml-2 current-month-indicator">•</span>
                                @endif
                            </td>

                            @foreach($salespeople as $person)
                                <td class="px-4 py-2 border border-gray-400 numeric" style= "width: 146px;">
                                    @if($data['salespeople'][$person] > 0)
                                        {{ number_format($data['salespeople'][$person], 2) }}
                                    @else
                                        0.00
                                    @endif
                                </td>
                            @endforeach

                            <td class="px-4 py-2 border border-gray-400 numeric total-cell">
                                {{ number_format($data['total'], 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td class="px-4 py-2 font-bold text-left border border-gray-400">TOTAL</td>

                        @foreach($salespeople as $person)
                            <td class="px-4 py-2 border border-gray-400 numeric footer-total">
                                @php
                                    $personTotal = 0;
                                    foreach ($revenueData as $monthNum => $monthData) {
                                        $personTotal += $monthData['salespeople'][$person] ?? 0;
                                    }
                                @endphp
                                {{ number_format($personTotal, 2) }}
                            </td>
                        @endforeach

                        <td class="px-4 py-2 border border-gray-400 numeric footer-grand-total">
                            @php
                                $grandTotal = 0;
                                foreach ($revenueData as $monthNum => $monthData) {
                                    $grandTotal += $monthData['total'];
                                }
                            @endphp
                            {{ number_format($grandTotal, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <style>
        .revenue-table {
            width: 100%;
            border-collapse: collapse;
        }

        .revenue-table th,
        .revenue-table td {
            border: 1px solid #000;
            padding: 8px;
        }

        .revenue-table th {
            background-color: #fff8e1;
        }

        .revenue-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .numeric {
            text-align: right;
            padding-right: 15px;
        }

        .total-cell {
            background-color: #f0f4ff;
            font-weight: 600;
        }

        .footer-total {
            background-color: #e6e9f0;
            font-weight: 600;
        }

        .footer-grand-total {
            background-color: #d6dbe7;
            font-weight: 700;
        }

        tr:hover td {
            background-color: #f1f5f9;
        }

        .current-month-row {
            background-color: #ffeb3b !important;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }

        .current-month {
            color: #d97706;
            font-weight: bold;
            position: relative;
        }

        .current-month-indicator {
            color: #d97706;
            font-size: 1.5rem;
            line-height: 0;
            vertical-align: middle;
        }

        .select-control {
            width: 100px;
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            background-color: white;
        }

        .select-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }

        .form-label {
            display: block;
            font-size: 1rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }
    </style>
</x-filament::page>
