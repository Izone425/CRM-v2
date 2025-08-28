<x-filament::page>
    <div class="revenue-container">
        <div class="header-actions">
            <div class="flex items-center justify-between gap-4 mb-4">
                <h2 class="text-xl font-bold text-gray-800">By Year</h2>

                <div class="flex items-center gap-4">
                    <div class="filters">
                        <div class="flex items-center gap-4">
                            <label for="selectedYear" class="form-label">Year:</label>
                            <select id="selectedYear" wire:model.live="selectedYear" class="select-control">
                                @foreach($years as $year => $label)
                                    <option value="{{ $year }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="ml-4">
                        @if($editMode)
                            <button type="button" class="save-button" wire:click="saveRevenueValues">
                                <i class="mr-1 fa fa-check"></i> Save Values
                            </button>
                        @else
                            <button type="button" class="edit-button" wire:click="toggleEditMode">
                                <i class="mr-1 fa fa-pencil"></i> Edit Values
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse revenue-table">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border border-gray-400">{{ $selectedYear }}</th>
                        @foreach($salespeople as $person)
                            <th class="px-4 py-2 uppercase bg-yellow-200 border border-gray-400">{{ $person }}</th>
                        @endforeach
                        <th class="px-4 py-2 bg-yellow-200 border border-gray-400">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($revenueData as $monthNum => $data)
                        <tr>
                            <td class="px-4 py-2 font-bold border border-gray-400">{{ $data['month_name'] }}</td>

                            @foreach($salespeople as $person)
                                <td class="px-4 py-2 text-right border border-gray-400">
                                    @if($editMode)
                                        <input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="w-full text-right"
                                            value="{{ $revenueValues[$monthNum][$person] ?? ($data['salespeople'][$person] > 0 ? $data['salespeople'][$person] : 0) }}"
                                            wire:change="updateRevenueValue({{ $monthNum }}, '{{ $person }}', $event.target.value)"
                                        >
                                    @else
                                        @if(isset($revenueValues[$monthNum][$person]))
                                            RM{{ number_format($revenueValues[$monthNum][$person], 2) }}
                                        @elseif($data['salespeople'][$person] > 0)
                                            RM{{ number_format($data['salespeople'][$person], 2) }}
                                        @else
                                            RM 0
                                        @endif
                                    @endif
                                </td>
                            @endforeach

                            <td class="px-4 py-2 text-right border border-gray-400">
                                @php
                                    $rowTotal = 0;
                                    foreach ($salespeople as $person) {
                                        $rowTotal += $revenueValues[$monthNum][$person] ??
                                            ($data['salespeople'][$person] > 0 ? $data['salespeople'][$person] : 0);
                                    }
                                @endphp
                                RM{{ number_format($rowTotal, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td class="px-4 py-2 font-bold border border-gray-400">TOTAL</td>

                        @foreach($salespeople as $person)
                            <td class="px-4 py-2 text-right border border-gray-400">
                                @php
                                    $personTotal = 0;
                                    foreach ($revenueData as $monthNum => $monthData) {
                                        $personTotal += $revenueValues[$monthNum][$person] ??
                                            ($monthData['salespeople'][$person] > 0 ? $monthData['salespeople'][$person] : 0);
                                    }
                                @endphp
                                RM{{ number_format($personTotal, 2) }}
                            </td>
                        @endforeach

                        <td class="px-4 py-2 text-right border border-gray-400">
                            @php
                                $grandTotal = 0;
                                foreach ($revenueData as $monthNum => $monthData) {
                                    foreach ($salespeople as $person) {
                                        $grandTotal += $revenueValues[$monthNum][$person] ??
                                            ($monthData['salespeople'][$person] > 0 ? $monthData['salespeople'][$person] : 0);
                                    }
                                }
                            @endphp
                            RM{{ number_format($grandTotal, 2) }}
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

        .revenue-table th:first-child,
        .revenue-table td:first-child {
            text-align: left;
        }

        .revenue-table th {
            background-color: #ffeb3b;
        }

        .revenue-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .edit-button, .save-button {
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: 500;
            color: white;
        }

        .edit-button {
            background-color: #3b82f6;
        }

        .save-button {
            background-color: #10b981;
        }

        input[type="number"] {
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 4px;
        }
    </style>
</x-filament::page>
