<x-filament::page>
    <style>
        /* Custom styling for Sales Target Analysis */
        .analysis-container {
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .analysis-filters {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        @media (min-width: 768px) {
            .analysis-filters {
                flex-direction: row;
                justify-content: space-between;
            }
        }

        .analysis-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .analysis-table th {
            background-color: #f3f4f6;
            color: #4b5563;
            font-weight: 600;
            font-size: 1rem;
            letter-spacing: 0.05em;
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            width: 16.66%; /* NO4: Make all columns equal width (1/6 = 16.66%) */
        }

        .analysis-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e5e7eb;
            font-size: 1rem;
            width: 16.66%; /* NO4: Make all columns equal width */
        }

        .analysis-table tr:hover {
            background-color: #ffdf2d;
        }

        .analysis-table tbody tr:last-child td {
            border-bottom: 2px solid #e5e7eb;
        }

        .analysis-table tfoot tr td {
            background-color: #f9fafb;
            font-weight: 600;
            border-top: 2px solid #e5e7eb;
        }

        .target-input {
            width: 100%;
            max-width: 120px;
            padding: 0.375rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .target-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }

        .negative-diff {
            color: #dc2626;
            font-weight: 600;
        }

        .positive-diff {
            color: #16a34a;
            font-weight: 600;
        }

        .select-control {
            width: 90%;
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

        .edit-button {
            background-color: #3b82f6;
            color: white;
            border: none;
            border-radius: 0.375rem;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .edit-button:hover {
            background-color: #2563eb;
        }

        .save-button {
            background-color: #10b981;
            color: white;
            border: none;
            border-radius: 0.375rem;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .save-button:hover {
            background-color: #059669;
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
    </style>

    <div class="analysis-container">
        <div class="header-actions">
            <h2 class="text-xl font-bold text-gray-800">Demo Analysis vs Actual Sales vs Sales Target</h2>

            <div class="flex items-center justify-between gap-4">
                <div class="analysis-filters">
                    <div class="flex gap-4" style="margin-top:20px;">
                        <select id="selectedYear" wire:model.live="selectedYear" class="select-control">
                            @foreach($years as $year => $label)
                                <option value="{{ $year }}">{{ $label }} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="ml-4">
                    @if($editMode)
                        <button type="button" class="save-button" wire:click="saveTargets">
                            <i class="mr-1 fa fa-check"></i> Save Targets
                        </button>
                    @else
                        <button type="button" class="edit-button" wire:click="toggleEditMode">
                            <i class="mr-1 fa fa-pencil"></i> Edit Targets
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="analysis-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th style="text-align: center;">New Demo</th>
                        <th style="text-align: center;">Webinar Demo</th>
                        <th style="text-align: right;">Actual Sales</th>
                        <th style="text-align: right;">Sales Target</th>
                        <th style="text-align: right;">Variance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($monthlyStats as $monthNumber => $stats)
                        <tr>
                            <td><strong>{{ $stats['month_name'] }}</strong></td>
                            <td class="text-center">{{ $stats['new_demo_percentage'] }}%</td>
                            <td class="text-center">{{ $stats['webinar_demo_percentage'] }}%</td>
                            <td class="text-right">{{ number_format($stats['actual_sales'], 2) }}</td>
                            <td class="text-right">
                                @if($editMode)
                                    <input
                                        type="number"
                                        min="0"
                                        class="text-right target-input"
                                        wire:model="salesTargets.{{ $monthNumber }}"
                                        wire:change="updateSalesTarget({{ $monthNumber }}, $event.target.value)"
                                    >
                                @else
                                    {{ number_format($salesTargets[$monthNumber] ?? 0, 2) }}
                                @endif
                            </td>
                            <td class="text-right">
                                <span class="{{ ($stats['raw_difference'] ?? 0) < 0 ? 'negative-diff' : 'positive-diff' }}">
                                    {{ number_format($stats['raw_difference'] ?? 0, 2) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td><strong>Total</strong></td>
                        <td class="text-center">
                            @php
                                // NO3: Calculate average of new demo percentages instead of sum of counts
                                $monthsWithData = count(array_filter($monthlyStats, function($month) {
                                    return isset($month['new_demo_percentage']);
                                }));

                                $avgNewDemoPercentage = $monthsWithData > 0
                                    ? round(array_sum(array_column($monthlyStats, 'new_demo_percentage')) / $monthsWithData)
                                    : 0;
                            @endphp
                            <strong>{{ $avgNewDemoPercentage }}%</strong>
                        </td>
                        <td class="text-center">
                            @php
                                // NO3: Calculate average of webinar demo percentages instead of sum of counts
                                $monthsWithWebinarData = count(array_filter($monthlyStats, function($month) {
                                    return isset($month['webinar_demo_percentage']);
                                }));

                                $avgWebinarDemoPercentage = $monthsWithWebinarData > 0
                                    ? round(array_sum(array_column($monthlyStats, 'webinar_demo_percentage')) / $monthsWithWebinarData)
                                    : 0;
                            @endphp
                            <strong>{{ $avgWebinarDemoPercentage }}%</strong>
                        </td>
                        <td class="text-right">
                            <strong>{{ number_format(array_sum(array_column($monthlyStats, 'actual_sales')), 2) }}</strong>
                        </td>
                        <td class="text-right">
                            <strong>{{ number_format(array_sum($salesTargets), 2) }}</strong>
                        </td>
                        <td class="text-right">
                            @php
                                $totalDifference = array_sum(array_column($monthlyStats, 'actual_sales')) - array_sum($salesTargets);
                            @endphp
                            <span class="{{ $totalDifference < 0 ? 'negative-diff' : 'positive-diff' }}">
                                <strong>{{ number_format($totalDifference, 2) }}</strong>
                            </span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</x-filament::page>
