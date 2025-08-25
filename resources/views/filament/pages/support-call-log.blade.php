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

        .summary-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.12);
        }

        .card-total { background: linear-gradient(to bottom right, #ebf5ff, #dbeafe); border: 1px solid #bfdbfe; }
        .card-completed { background: linear-gradient(to bottom right, #ecfdf5, #d1fae5); border: 1px solid #a7f3d0; }
        .card-pending { background: linear-gradient(to bottom right, #fee2e2, #fecaca); border: 1px solid #fca5a5; }
        .card-time { background: linear-gradient(to bottom right, #fffbeb, #fef3c7); border: 1px solid #fde68a; }

        .card-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .card-total .card-value { color: #2563eb; }
        .card-completed .card-value { color: #059669; }
        .card-pending .card-value { color: #dc2626; }
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

        .staff-number-completed {
            background-color: #d1fae5;
            color: #059669;
        }

        .staff-number-pending {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .staff-number-time {
            background-color: #fef3c7;
            color: #d97706;
        }

        /* Update the staff-name to not have a margin-bottom since they're on the same line now */
        .staff-name {
            font-size: 1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0; /* Changed from 0.5rem */
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

        .slide-over-content {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            padding-bottom: 80px;
        }

        .staff-stats-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.25rem;
            margin-bottom: 1rem;
            border-left: 4px solid #3b82f6;
        }

        .staff-name {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1f2937;
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
            color: #3b82f6;
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
    </style>

    <div class="mb-6">
        <h2 class="section-title">Call Log List</h2>

        <div class="summary-grid">
            <div class="summary-card card-total" wire:click="openStaffStatsSlideOver('all')">
                <div class="card-value">
                    @php
                        $totalCount = \App\Models\CallLog::query()
                            ->where(function ($query) {
                                $query->whereIn('caller_number', ['100', '323', '324', '333', '343'])
                                    ->orWhereIn('receiver_number', ['323', '324', '333', '343']);
                            })
                            ->count();
                    @endphp
                    {{ $totalCount }}
                </div>
                <div class="card-label">Total Tasks</div>
            </div>

            <div class="summary-card card-completed" wire:click="openStaffStatsSlideOver('completed')">
                <div class="card-value">
                    @php
                        $completedCount = \App\Models\CallLog::query()
                            ->where(function ($query) {
                                $query->whereIn('caller_number', ['100', '323', '324', '333', '343'])
                                    ->orWhereIn('receiver_number', ['323', '324', '333', '343']);
                            })
                            ->where('task_status', 'Completed')
                            ->count();
                    @endphp
                    {{ $completedCount }}
                </div>
                <div class="card-label">Completed Tasks</div>
            </div>

            <div class="summary-card card-pending" wire:click="openStaffStatsSlideOver('pending')">
                <div class="card-value">
                    @php
                        $pendingCount = \App\Models\CallLog::query()
                            ->where(function ($query) {
                                $query->whereIn('caller_number', ['100', '323', '324', '333', '343'])
                                    ->orWhereIn('receiver_number', ['323', '324', '333', '343']);
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
                        $totalDuration = \App\Models\CallLog::query()
                            ->where(function ($query) {
                                $query->whereIn('caller_number', ['100', '323', '324', '333', '343'])
                                    ->orWhereIn('receiver_number', ['323', '324', '333', '343']);
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
    <template x-teleport="body"> <!-- Teleport to body to avoid z-index issues -->
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

                <!-- Scrollable content -->
                <div class="slide-over-content">
                    @foreach ($staffStats as $staff)
                        <div class="staff-stats-card">
                            <!-- Name and number on the same line -->
                            <div class="flex items-center justify-between">
                                <div class="staff-name">{{ $staff['name'] }}</div>

                                <!-- Show the right number based on type -->
                                @if($type === 'duration')
                                    <div class="staff-number staff-number-time">{{ $staff['total_time'] }}</div>
                                @elseif($type === 'completed')
                                    <div class="group-badge">{{ $staff['completed_calls'] }}</div>
                                @elseif($type === 'pending')
                                    <div class="group-badge">{{ $staff['pending_calls'] }}</div>
                                @else
                                    <div class="group-badge">{{ $staff['total_calls'] }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </template>

    {{ $this->table }}
</x-filament::page>
