<x-filament-panels::page>
    <style>
        /* Card styles */
        .stats-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .stats-card__header {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
        }

        .stats-card__body {
            padding: 1rem;
        }

        /* Progress bar styles */
        .progress-container {
            width: 100%;
            background-color: #e5e7eb;
            border-radius: 9999px;
            height: 0.625rem;
            margin-bottom: 0.5rem;
        }

        .progress-bar {
            height: 0.625rem;
            border-radius: 9999px;
        }

        .progress-bar--small {
            background-color: #2563eb;
        }

        .progress-bar--medium {
            background-color: #10b981;
        }

        /* Text styles */
        .stats-title {
            font-size: 1.125rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .stats-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #6b7280;
        }

        .stats-value {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .stats-subtitle {
            font-size: 0.75rem;
            color: #6b7280;
        }

        /* Badge styles */
        .badge {
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.25rem 0.625rem;
            border-radius: 0.25rem;
            background-color: #e5e7eb;
        }

        .badge--blue {
            background-color: #dbeafe;
            color: #1e40af;
        }

        /* Layout styles */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr); /* 5 columns per row */
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stats-summary {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .flex-between {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Footer section */
        .stats-footer {
            border-top: 1px solid #f3f4f6;
            padding-top: 0.75rem;
            margin-top: 0.75rem;
            font-size: 0.75rem;
            color: #6b7280;
        }
    </style>

    <!-- Implementer Statistics Section -->
    <div class="mb-6">
        <div class="space-y-4">
            <!-- Overall Stats Summary -->
            <div class="stats-summary">
                <div class="stats-card">
                    <div class="stats-card__body">
                        <h3 class="stats-label">Total Assignments</h3>
                        <p class="stats-value">{{ $statsData['overall']['totalAssignments'] ?? 0 }}</p>
                        <p class="stats-subtitle">{{ $statsData['overall']['periodLabel'] ?? '' }}</p>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="stats-card__body">
                        <h3 class="stats-label">Most Active Implementer</h3>
                        <div class="flex-between">
                            <p class="stats-value">{{ $statsData['overall']['mostActive'] ?? 'None' }}</p>
                            <span class="badge badge--blue">
                                {{ $statsData['overall']['mostActiveCount'] ?? 0 }} assignments
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Individual Implementer Stats: 5 per row -->
            <div class="stats-grid">
                @foreach($implementers as $implementer)
                    <div class="stats-card">
                        <div class="stats-card__header" style="background-color: {{ $statsData[$implementer]['color'] }}10;">
                            <div class="flex-between">
                                <h3 class="font-medium">{{ $implementer }}</h3>
                                <span class="badge">
                                    {{ $statsData[$implementer]['total'] }} total
                                </span>
                            </div>
                        </div>
                        <div class="stats-card__body">
                            <div class="mb-1 flex-between">
                                <span class="stats-label">Small Companies</span>
                                <span class="stats-label">{{ $statsData[$implementer]['small'] }}</span>
                            </div>
                            <div class="progress-container">
                                <div class="progress-bar progress-bar--small" style="width: {{ $statsData[$implementer]['percentSmall'] }}%"></div>
                            </div>

                            <div class="mt-3 mb-1 flex-between">
                                <span class="stats-label">Medium Companies</span>
                                <span class="stats-label">{{ $statsData[$implementer]['medium'] }}</span>
                            </div>
                            <div class="progress-container">
                                <div class="progress-bar progress-bar--medium" style="width: {{ $statsData[$implementer]['percentMedium'] }}%"></div>
                            </div>

                            <div class="stats-footer">
                                Latest assignment: {{ $statsData[$implementer]['latestAssignment'] }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Original Content: Tables -->
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="md:col-span-1">
            <livewire:implementer-sequence-small />
        </div>
        <div class="md:col-span-1">
            <livewire:implementer-sequence-medium />
        </div>
    </div>
</x-filament-panels::page>
