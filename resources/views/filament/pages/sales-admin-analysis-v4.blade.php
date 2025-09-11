<x-filament::page>
    <style>
        /* Summary cards styling */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .summary-card {
            padding: 1.25rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.2s ease-in-out;
            cursor: pointer;
        }

        .group-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 1.5rem;
            height: 1.5rem;
            background-color: #2563eb;
            color: white;
            font-weight: 600;
            font-size: 0.75rem;
            border-radius: 9999px;
            margin-right: 0.5rem;
        }

        .summary-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.12);
        }

        .card-total { background: linear-gradient(to bottom right, #ebf5ff, #dbeafe); border: 1px solid #bfdbfe; }
        .card-outgoing { background: linear-gradient(to bottom right, #fee2e2, #fecaca); border: 1px solid #fca5a5; }
        .card-incoming { background: linear-gradient(to bottom right, #ecfdf5, #d1fae5); border: 1px solid #a7f3d0; }
        .card-time { background: linear-gradient(to bottom right, #fffbeb, #fef3c7); border: 1px solid #fde68a; }

        .card-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .card-total .card-value { color: #2563eb; }
        .card-outgoing .card-value { color: #dc2626; }
        .card-incoming .card-value { color: #059669; }
        .card-time .card-value { color: #d97706; }

        .card-label {
            font-size: 0.875rem;
            color: #4b5563;
            font-weight: 500;
        }

        .section-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .staff-number {
            font-size: 1.5rem;
            font-weight: 700;
            padding: 0.25rem 0.75rem;
            border-radius: 0.5rem;
            min-width: 3rem;
            text-align: center;
        }

        .staff-number-total {
            background-color: #dbeafe;
            color: #2563eb;
        }

        .staff-number-outgoing {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .staff-number-incoming {
            background-color: #d1fae5;
            color: #059669;
        }

        /* Update the staff-name to not have a margin-bottom since they're on the same line now */
        .staff-name {
            font-size: 1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0;
        }

        .slide-over-overlay {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            z-index: 99999 !important;
        }

        .slide-over-modal {
            position: fixed !important; /* Change from relative to fixed */
            top: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100% !important;
            max-width: 500px !important;
            height: 100vh !important;
            background-color: white;
            box-shadow: -4px 0 24px rgba(0, 0, 0, 0.25);
            z-index: 100000 !important; /* Extremely high z-index */
            border-radius: 12px 0 0 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .slide-over-header {
            position: sticky;
            top: 0;
            background-color: white;
            z-index: 100001 !important; /* Even higher than modal */
            border-bottom: 1px solid #e5e7eb;
            padding: 1.25rem 1.5rem;
            min-height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .staff-stats-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.25rem;
            margin-bottom: 1rem;
            border-left: 4px solid #3b82f6;
        }

        .slide-over-content {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            padding-bottom: 80px;
        }

        .staff-name {
            font-size: 1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0;
        }

        .staff-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
        }

        .stat-item {
            background-color: #f9fafb;
            padding: 0.75rem;
            border-radius: 0.375rem;
            text-align: center;
        }

        .stat-item-value {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .stat-item-label {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }

        @media (max-width: 1024px) {
            .summary-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 640px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }

            .staff-stats {
                grid-template-columns: 1fr;
            }
        }

        .staff-stats-card {
            background-color: white;
            border-radius: 0.375rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            padding: 0.5rem 1rem;
            margin-bottom: 0.5rem;
            border-left: 3px solid #3b82f6;
        }

        .staff-name {
            font-size: 0.875rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0;
        }

        .group-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 1.25rem;
            height: 1.25rem;
            background-color: #2563eb;
            color: white;
            font-weight: 600;
            font-size: 0.7rem;
            border-radius: 9999px;
            margin-right: 0.25rem;
        }

        .staff-number {
            font-size: 1rem;
            font-weight: 700;
            padding: 0.125rem 0.5rem;
            border-radius: 0.375rem;
            min-width: 2.5rem;
            text-align: center;
        }

        .staff-number-time {
            color: #d97706;
            font-size: 0.875rem;
            font-weight: 700;
            padding: 0.125rem 0.5rem;
            border-radius: 0.375rem;
            min-width: 2.5rem;
            text-align: center;
        }

        .total-time-day {
            background-color: #c8c7fe;
            color: #0637d9;
            font-size: 0.875rem;
            font-weight: 700;
            padding: 0.125rem 0.5rem;
            border-radius: 0.375rem;
            min-width: 2.5rem;
            text-align: center;
        }

        .slide-over-total {
            padding: 1rem;
            margin-bottom: 1rem;
            text-align: center;
            border-radius: 0.375rem;
        }

        .slide-over-total-medium {
            background-color: rgba(217, 119, 6, 0.1);
            border: 1px solid rgba(217, 119, 6, 0.2);
        }

        .slide-over-total-label {
            font-size: 0.875rem;
            color: rgb(107, 114, 128);
        }

        .slide-over-total-value {
            margin-left: 0.25rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: rgb(55, 65, 81);
        }

        .implementer-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            margin-bottom: 0.25rem;
            background-color: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            transition: all 0.2s;
            font-size: 0.875rem;
            cursor: pointer;
        }

        .implementer-name {
            font-weight: 600;
            color: rgb(55, 65, 81);
            display: flex;
            align-items: center;
        }

        .implementer-name svg {
            margin-left: 0.5rem;
            width: 0.875rem;
            height: 0.875rem;
            transition: transform 0.15s ease;
        }

        .implementer-name.expanded svg {
            transform: rotate(180deg);
        }

        .date-list {
            margin-top: 0.25rem;
            margin-bottom: 1rem;
            margin-left: 1rem;
            padding-left: 0.5rem;
            border-left: 2px solid #e5e7eb;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .date-list.expanded {
            max-height: 1000px;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }

        .date-list table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }

        .date-list td {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .date-list tr:last-child td {
            border-bottom: none;
        }

        /* Adjust the expanded state to account for the table */
        .date-list.expanded {
            max-height: 1500px; /* Increase this to accommodate the table */
        }
    </style>

    <div class="mb-6">
        <h2 class="section-title">Sales Admin Call Analysis</h2>

        <div class="summary-grid">
            <div class="summary-card card-total" wire:click="openStaffStatsSlideOver('all')">
                <div class="card-value">
                    @php
                        // Get sales & admin staff extensions
                        $salesAdminExtensions = \App\Models\PhoneExtension::where('is_support_staff', false)
                            ->where('is_active', true)
                            ->pluck('extension')
                            ->toArray();

                        $totalCount = \App\Models\CallLog::query()
                            ->where(function ($query) use ($salesAdminExtensions) {
                                $query->whereIn('caller_number', $salesAdminExtensions)
                                    ->orWhereIn('receiver_number', $salesAdminExtensions);
                            })
                            ->where('call_status', '!=', 'NO ANSWER')
                            // Exclude calls with duration less than 5 seconds
                            ->where(function($query) {
                                $query->where('call_duration', '>=', 5)
                                    ->orWhereNull('call_duration');
                            })
                            ->count();
                    @endphp
                    {{ $totalCount }}
                </div>
                <div class="card-label">Total Calls</div>
            </div>

            <div class="summary-card card-incoming" wire:click="openStaffStatsSlideOver('completed')">
                <div class="card-value">
                    @php
                        // Get sales & admin staff extensions
                        $salesAdminExtensions = \App\Models\PhoneExtension::where('is_support_staff', false)
                            ->where('is_active', true)
                            ->pluck('extension')
                            ->toArray();

                        $completedCount = \App\Models\CallLog::query()
                            ->where(function ($query) use ($salesAdminExtensions) {
                                $query->whereIn('caller_number', $salesAdminExtensions)
                                    ->orWhereIn('receiver_number', $salesAdminExtensions);
                            })
                            ->where('call_status', '!=', 'NO ANSWER')
                            ->where(function($query) {
                                $query->where('call_duration', '>=', 5)
                                    ->orWhereNull('call_duration');
                            })
                            ->where('task_status', 'Completed')
                            ->count();
                    @endphp
                    {{ $completedCount }}
                </div>
                <div class="card-label">Completed Tasks</div>
            </div>

            <!-- Replace the incoming calls card with pending tasks card -->
            <div class="summary-card card-outgoing" wire:click="openStaffStatsSlideOver('pending')">
                <div class="card-value">
                    @php
                        // Reuse the extensions from above
                        $pendingCount = \App\Models\CallLog::query()
                            ->where(function ($query) use ($salesAdminExtensions) {
                                $query->whereIn('caller_number', $salesAdminExtensions)
                                    ->orWhereIn('receiver_number', $salesAdminExtensions);
                            })
                            ->where('call_status', '!=', 'NO ANSWER')
                            ->where(function($query) {
                                $query->where('call_duration', '>=', 5)
                                    ->orWhereNull('call_duration');
                            })
                            ->where('task_status', 'Pending')
                            ->count();
                    @endphp
                    {{ $pendingCount }}
                </div>
                <div class="card-label">Pending Tasks</div>
            </div>

            <div class="summary-card card-time" wire:click="openStaffStatsSlideOver('duration')">
                <div class="card-value">
                    @php
                        // Reuse the extensions from above
                        $totalDuration = \App\Models\CallLog::query()
                            ->where(function ($query) use ($salesAdminExtensions) {
                                $query->whereIn('caller_number', $salesAdminExtensions)
                                    ->orWhereIn('receiver_number', $salesAdminExtensions);
                            })
                            ->where('call_status', '!=', 'NO ANSWER')
                            ->where(function($query) {
                                $query->where('call_duration', '>=', 5)
                                    ->orWhereNull('call_duration');
                            })
                            ->sum('call_duration');

                        $hours = floor($totalDuration / 3600);
                        $minutes = floor(($totalDuration % 3600) / 60);
                    @endphp
                    {{ $hours }}h {{ $minutes }}m
                </div>
                <div class="card-label">Total Call Time</div>
            </div>
        </div>
    </div>

    <!-- Slide Over Panel for Staff Stats -->
    <template x-teleport="body"> <!-- Add the comment about teleporting to body -->
        <div
            x-data="{ open: @entangle('showStaffStats') }"
            x-show="open"
            @keydown.window.escape="open = false"
            class="slide-over-overlay"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            style="display: none;"
        >
            <div
                class="slide-over-modal"
                @click.away="open = false"
            >
                <!-- Header -->
                <div class="slide-over-header">
                    <h2 class="text-lg font-bold text-gray-800">{{ $slideOverTitle }}</h2>
                    <button @click="open = false" class="p-1 text-2xl leading-none text-gray-500 hover:text-gray-700">&times;</button>
                </div>

                <!-- Content remains the same -->
                <div class="slide-over-content">
                    @if($type === 'duration')
                        <!-- Total count - similar to project-priority style -->
                        <div class="slide-over-total slide-over-total-medium">
                            <span class="slide-over-total-label">Total Call Time:</span>
                            <span class="slide-over-total-value">
                                @php
                                    $totalHours = 0;
                                    $totalMinutes = 0;

                                    foreach($staffStats as $staff) {
                                        if (isset($staff['total_duration'])) {
                                            $hours = floor($staff['total_duration'] / 3600);
                                            $minutes = floor(($staff['total_duration'] % 3600) / 60);
                                            $totalHours += $hours;
                                            $totalMinutes += $minutes;
                                        }
                                    }

                                    // Convert excess minutes to hours
                                    $totalHours += floor($totalMinutes / 60);
                                    $totalMinutes = $totalMinutes % 60;
                                @endphp
                                {{ $totalHours }}h {{ $totalMinutes }}m
                            </span>
                        </div>

                        <!-- Staff list with expandable date lists -->
                        <div class="space-y-0">
                            @foreach($staffStats as $staff)
                                @if(isset($staff['name']))
                                    <!-- Staff item -->
                                    <div class="implementer-item" wire:click="toggleStaff('{{ $staff['name'] }}')">
                                        <div class="implementer-name {{ in_array($staff['name'], $expandedStaff ?? []) ? 'expanded' : '' }}">
                                            {{ $staff['name'] }}

                                            <!-- Chevron icon -->
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 011.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="implementer-count">
                                            {{ isset($staff['formatted_time']) ? $staff['formatted_time'] : '0h 0m' }}
                                        </div>
                                    </div>

                                    <!-- Date list - expandable -->
                                    <div class="date-list {{ in_array($staff['name'], $expandedStaff ?? []) ? 'expanded' : '' }}">
                                        @if(isset($staffDateTimes[$staff['name']]))
                                            <table class="w-full">
                                                <tbody>
                                                    @foreach($staffDateTimes[$staff['name']] as $dateData)
                                                        <tr class="date-item">
                                                            <td class="py-1">{{ $dateData['display_date'] }}</td>
                                                            <td class="py-1">
                                                                @php
                                                                    // Convert the display date to a DateTime object and get day name
                                                                    $date = \DateTime::createFromFormat('j M Y', $dateData['display_date']);
                                                                    $dayName = $date ? $date->format('D') : '';
                                                                @endphp
                                                                <span class="text-gray-600">{{ $dayName }}</span>
                                                            </td>
                                                            <td style= "width: 45%"></td>
                                                            <td class="py-1 text-right">
                                                                <span class="px-2 py-1 text-xs staff-number-time">{{ $dateData['formatted_time'] }}</span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <!-- Regular non-hierarchical view for other stats -->
                        @foreach ($staffStats as $staff)
                            <div class="px-3 py-2 mb-1 staff-stats-card">
                                <!-- Existing code for non-duration stats -->
                                <div class="flex items-center justify-between">
                                    <div class="text-sm staff-name">{{ $staff['name'] }}</div>

                                    <!-- Show the right number based on type -->
                                    @if($type === 'completed')
                                        <div class="w-5 h-5 text-xs group-badge">{{ $staff['completed_tasks'] }}</div>
                                    @elseif($type === 'pending')
                                        <div class="w-5 h-5 text-xs group-badge">{{ $staff['pending_tasks'] }}</div>
                                    @else
                                        <div class="w-5 h-5 text-xs group-badge">{{ $staff['total_calls'] }}</div>
                                    @endif
                                </div>

                                <!-- Add extension info -->
                                <div class="mt-0.5 text-xs text-gray-500">
                                    Extension: {{ $staff['extension'] }}
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </template>

    {{ $this->table }}
</x-filament::page>
