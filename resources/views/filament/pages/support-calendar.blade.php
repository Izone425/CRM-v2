@php
    use Carbon\Carbon;
@endphp

<x-filament::page>
    <style>
        /* Month row styling */
        .month-cell {
            background-color: #f3f4f6;
            font-weight: 600;
            padding: 0.75rem 0.5rem;
            border-right: 1px solid #d1d5db;
            border-bottom: 1px solid #d1d5db;
            width: 16.66%; /* REQUEST 1: Equal width for all headers (1/6 = 16.66%) */
            text-align: center; /* Center text for better appearance */
        }

        /* Current month styling */
        .current-month {
            background-color: #d1fae5; /* Light green color */
            border-left: 3px solid #10b981; /* Medium green border */
        }

        /* Week header styling */
        .week-header {
            background-color: #dbeafe;
            padding: 0.75rem 0.5rem;
            text-align: center;
            font-weight: 600;
            font-size: 0.875rem;
            border-right: 1px solid #d1d5db;
            border-bottom: 1px solid #d1d5db;
            width: 16.66%; /* REQUEST 1: Equal width for all headers (1/6 = 16.66%) */
        }

        /* Date cell styling */
        .date-cell {
            padding: 0.25rem 0.5rem;
            border-right: 1px solid #d1d5db;
            border-bottom: 1px solid #d1d5db;
            vertical-align: top;
        }

        /* Date number styling */
        .date-number {
            font-size: 0.75rem;
            color: #4b5563;
            margin-bottom: 0.25rem;
        }

        /* User assignment boxes */
        .assignment-box {
            padding: 0.5rem;
            border-radius: 0.25rem;
        }

        /* Unassigned styling - now dark grey (REQUEST 2) */
        .unassigned {
            background-color: #4b5563; /* Dark grey for unassigned */
            color: #f9fafb; /* Light text for contrast */
            font-style: italic;
        }

        /* Assigned styling - now gray */
        .assigned {
            background-color: #fecaca; /* Light red for assigned */
            color: #374151;
        }

        /* Staff colors - keeping these for completed tasks or other statuses */
        .staff-color-0 { background-color: #bfdbfe; } /* Light blue */
        .staff-color-1 { background-color: #fef08a; } /* Light yellow */
        .staff-color-2 { background-color: #fecaca; } /* Light red */
        .staff-color-3 { background-color: #e9d5ff; } /* Light purple */
        .staff-color-4 { background-color: #fbcfe8; } /* Light pink */
        .staff-color-5 { background-color: #d1d5db; } /* Light gray */
        .staff-color-6 { background-color: #bbf7d0; } /* Light green for completed */

        /* Legend styling */
        .legend-container {
            margin-top: 1.5rem;
            padding: 0.75rem 1rem;
            background-color: #f3f4f6;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .legend-title {
            font-size: 1.125rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .legend-items {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
        }

        .legend-color {
            width: 1rem;
            height: 1rem;
            margin-right: 0.5rem;
            border-radius: 0.25rem;
        }

        .legend-text {
            font-size: 0.875rem;
        }

        .assigned-cell {
            background-color: #fecaca !important; /* Light red background */
        }

        /* Completed cell styling */
        .completed-cell {
            background-color: #bbf7d0 !important; /* Light green background */
        }

        /* Make sure completed takes precedence over assigned */
        .completed-cell.assigned-cell {
            background-color: #bbf7d0 !important;
        }
    </style>

    <div class="overflow-hidden bg-white rounded-lg shadow">
        <table class="w-full border border-gray-300">
            <thead>
                <tr>
                    <th class="month-cell">
                        {{ $selectedYear }}
                    </th>
                    @php
                        $maxWeeks = 0;
                        foreach ($months as $month) {
                            $weekCount = count($month['weeks']);
                            $maxWeeks = max($maxWeeks, $weekCount);
                        }
                    @endphp
                    @for ($w = 1; $w <= $maxWeeks; $w++)
                        <th class="week-header">
                            Week {{ $w }} <!-- REQUEST 3: Changed from W1 to Week 1 format -->
                        </th>
                    @endfor
                </tr>
            </thead>
            <tbody>
                @foreach ($months as $monthName => $month)
                    <tr>
                        <td class="month-cell {{ Carbon::now()->format('F') === $monthName && Carbon::now()->year == $selectedYear ? 'current-month' : '' }}">
                            {{ $monthName }}
                        </td>

                        @for ($w = 1; $w <= $maxWeeks; $w++)
                            @php
                                $weekKey = "W{$w}";
                                $week = $month['weeks'][$weekKey] ?? null;
                            @endphp

                            <td class="date-cell
                                {{ Carbon::now()->format('F') === $monthName && Carbon::now()->year == $selectedYear ? 'current-month' : '' }}
                                {{ ($editMode && $week && $week['user_id']) ? 'assigned-cell' : '' }}
                                {{ ($week && $week['status'] === 'completed') ? 'completed-cell' : '' }}">
                                @if ($week)
                                    <div class="flex flex-col h-full">
                                        <div class="date-number">{{ $week['dates'] }}</div>

                                        @if ($editMode)
                                            <select
                                                class="w-full text-sm border-gray-300 rounded-md"
                                                wire:change="assignStaff('{{ $week['date_start'] }}', $event.target.value)"
                                            >
                                                <option value="">-- Select Staff --</option>
                                                @foreach($users as $user)
                                                    <option
                                                        value="{{ $user->id }}"
                                                        {{ $week['user_id'] == $user->id ? 'selected' : '' }}
                                                    >
                                                        {{ $user->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <div class="assignment-box {{ $week['user_id'] ? 'assigned' : 'unassigned' }} {{ $week['status'] === 'completed' ? 'staff-color-6' : '' }}">
                                                <div class="{{ $week['user_id'] ? 'font-medium' : '' }}">
                                                    {{ $week['user_name'] }}
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        @endfor
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament::page>
