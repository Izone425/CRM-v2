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

        .progress-bar--large {
            background-color: #f59e0b;
        }

        .progress-bar--enterprise {
            background-color: #dc2626;
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
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
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

        .hardware-handover-container {
            grid-column: 1 / -1;
            width: 100%;
        }

        .dashboard-layout {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 15px;
        }

        .group-column {
            padding-right: 10px;
            width: 250px;
        }

        .group-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .group-box {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px 15px;
            cursor: pointer;
            transition: all 0.2s;
            border-top: 4px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            text-align: left;
            max-height: 82px;
        }

        .group-small { border-top-color: #2563eb; }
        .group-medium { border-top-color: #10b981; }
        .group-large { border-top-color: #f59e0b; }
        .group-enterprise { border-top-color: #dc2626; }

        .group-box:hover {
            background-color: #f9fafb;
            transform: translateX(3px);
        }

        .group-box.selected {
            background-color: #f9fafb;
            transform: translateX(5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .group-title {
            font-size: 15px;
            font-weight: 600;
        }

        .group-count {
            font-size: 24px;
            font-weight: bold;
        }

        .group-small .group-count { color: #2563eb; }
        .group-medium .group-count { color: #10b981; }
        .group-large .group-count { color: #f59e0b; }
        .group-enterprise .group-count { color: #dc2626; }

        .content-column {
            min-height: 600px;
        }

        .hint-message {
            text-align: center;
            background-color: #f9fafb;
            border-radius: 0.5rem;
            border: 1px dashed #d1d5db;
            height: 330px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .hint-message h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .hint-message p {
            color: #6b7280;
        }

        @media (max-width: 1200px) {
            .dashboard-layout {
                grid-template-columns: 100%;
                grid-template-rows: auto auto;
            }
            .group-container {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
                padding-bottom: 15px;
                margin-bottom: 15px;
            }
        }

        @media (max-width: 768px) {
            .group-container {
                grid-template-columns: 1fr;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div>
        <h2 class="mb-6 text-xl font-bold">Implementer Project Assignments</h2>
        <br>
        <!-- Implementer Statistics Cards -->
        <div class="mb-6">
            <div class="stats-grid">
                @foreach($implementers as $implementer)
                    <div class="stats-card">
                        <div class="stats-card__header" style="background-color: rgba({{ implode(',', $statsData[$implementer]['color']) }},0.1);">
                            <div class="flex-between">
                                <h3 class="font-medium">{{ $implementer }}</h3>
                                <span class="badge">
                                    {{ $statsData[$implementer]['total'] }} total
                                </span>
                            </div>
                        </div>
                        <div class="stats-card__body">
                            <!-- Small Companies -->
                            <div class="mb-1 flex-between">
                                <span class="stats-label">Small (1-24)</span>
                                <span class="stats-label">{{ $statsData[$implementer]['small'] }} ({{ $statsData[$implementer]['percentSmall'] }}%)</span>
                            </div>
                            <div class="progress-container">
                                <div class="progress-bar progress-bar--small" style="width: {{ $statsData[$implementer]['percentSmall'] }}%"></div>
                            </div>

                            <!-- Medium Companies -->
                            <div class="mt-3 mb-1 flex-between">
                                <span class="stats-label">Medium (25-99)</span>
                                <span class="stats-label">{{ $statsData[$implementer]['medium'] }} ({{ $statsData[$implementer]['percentMedium'] }}%)</span>
                            </div>
                            <div class="progress-container">
                                <div class="progress-bar progress-bar--medium" style="width: {{ $statsData[$implementer]['percentMedium'] }}%"></div>
                            </div>

                            <!-- Large Companies -->
                            <div class="mt-3 mb-1 flex-between">
                                <span class="stats-label">Large (100-500)</span>
                                <span class="stats-label">{{ $statsData[$implementer]['large'] }} ({{ $statsData[$implementer]['percentLarge'] }}%)</span>
                            </div>
                            <div class="progress-container">
                                <div class="progress-bar progress-bar--large" style="width: {{ $statsData[$implementer]['percentLarge'] }}%"></div>
                            </div>

                            <!-- Enterprise Companies -->
                            <div class="mt-3 mb-1 flex-between">
                                <span class="stats-label">Enterprise (501+)</span>
                                <span class="stats-label">{{ $statsData[$implementer]['enterprise'] }} ({{ $statsData[$implementer]['percentEnterprise'] }}%)</span>
                            </div>
                            <div class="progress-container">
                                <div class="progress-bar progress-bar--enterprise" style="width: {{ $statsData[$implementer]['percentEnterprise'] }}%"></div>
                            </div>

                            <div class="stats-footer">
                                Latest assignment: {{ $statsData[$implementer]['latestAssignment'] }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Tables Section -->
        <div class="hardware-handover-container"
            x-data="{
                selectedType: null,
                setSelectedType(value) {
                    if (this.selectedType === value) {
                        this.selectedType = null;
                    } else {
                        this.selectedType = value;
                    }
                },
                init() {
                    this.selectedType = null;
                }
            }"
            x-init="init()">
            <div class="dashboard-layout">
                <!-- Left sidebar -->
                <div class="group-column">
                    <div class="group-container">
                        <div
                            class="group-box group-small"
                            :class="{ 'selected': selectedType === 'small' }"
                            @click="setSelectedType('small')"
                        >
                            <div class="group-title">Small Companies</div>
                            <div class="group-count">
                                {{ array_sum(array_map(fn($imp) => $statsData[$imp]['small'] ?? 0, $implementers)) }}
                            </div>
                        </div>
                        <div
                            class="group-box group-medium"
                            :class="{ 'selected': selectedType === 'medium' }"
                            @click="setSelectedType('medium')"
                        >
                            <div class="group-title">Medium Companies</div>
                            <div class="group-count">
                                {{ array_sum(array_map(fn($imp) => $statsData[$imp]['medium'] ?? 0, $implementers)) }}
                            </div>
                        </div>
                        <div
                            class="group-box group-large"
                            :class="{ 'selected': selectedType === 'large' }"
                            @click="setSelectedType('large')"
                        >
                            <div class="group-title">Large Companies</div>
                            <div class="group-count">
                                {{ array_sum(array_map(fn($imp) => $statsData[$imp]['large'] ?? 0, $implementers)) }}
                            </div>
                        </div>
                        <div
                            class="group-box group-enterprise"
                            :class="{ 'selected': selectedType === 'enterprise' }"
                            @click="setSelectedType('enterprise')"
                        >
                            <div class="group-title">Enterprise Companies</div>
                            <div class="group-count">
                                {{ array_sum(array_map(fn($imp) => $statsData[$imp]['enterprise'] ?? 0, $implementers)) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right content area -->
                <div class="content-column">
                    <div class="hint-message" x-show="!selectedType" x-transition>
                        <h3>Select company type to view data</h3>
                        <p>Click on any company size category to display the assignments table</p>
                    </div>
                    <template x-if="selectedType === 'small'">
                        <div>
                            <livewire:implementer-sequence-small />
                        </div>
                    </template>
                    <template x-if="selectedType === 'medium'">
                        <div>
                            <livewire:implementer-sequence-medium />
                        </div>
                    </template>
                    <template x-if="selectedType === 'large'">
                        <div>
                            <livewire:implementer-sequence-large />
                        </div>
                    </template>
                    <template x-if="selectedType === 'enterprise'">
                        <div>
                            <livewire:implementer-sequence-enterprise />
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
