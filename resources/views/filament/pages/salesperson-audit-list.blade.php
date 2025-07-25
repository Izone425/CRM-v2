<!-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/salesperson-audit-list.blade.php -->
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
            background-color:rgb(218, 231, 36);
        }

        .progress-bar--enterprise {
            background-color:rgb(209, 59, 32);
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
            font-size: 1rem;
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
            grid-template-columns: repeat(4, 1fr); /* 5 columns per row */
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
            width: 230px;
        }
        .group-container {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            border-right: none;
            padding-right: 0;
            padding-bottom: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        .group-box {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px 15px;
            cursor: pointer;
            transition: all 0.2s;
            border-top: 4px solid #e5e7eb; /* default */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin-bottom: 15px;
            width: 100%;
            min-width: 150px;
            text-align: center;
            max-height: 82px;
            max-width: 220px;
        }
        .group-small { border-top-color: #2563eb; }
        .group-medium { border-top-color: #10b981; }
        .group-box.selected {
            background-color: #f9fafb;
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .group-title {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .group-count {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
        }
        .content-column {
            min-height: 600px;
        }
        .content-area {
            min-height: 600px;
            background: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            padding: 2rem;
        }
        .hint-message {
            text-align: center;
            background-color: #f9fafb;
            border-radius: 0.5rem;
            border: 1px dashed #d1d5db;
            height: 530px;
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
        }
        .toggle-group {
            display: flex;
            gap: 0.5rem;
            margin-left: 1rem;
        }
        .toggle-btn {
            background: #f3f4f6;
            border: none;
            padding: 0.5rem 1.25rem;
            border-radius: 9999px;
            font-weight: 600;
            cursor: pointer;
            color: #374151;
            transition: background 0.2s, color 0.2s;
        }
        .toggle-btn.active {
            background: #2563eb;
            color: #fff;
        }
    </style>

    <div x-data="{ selectedRank: 'rank1' }">
        <div class="flex items-center mb-6">
            <h2 class="mr-4 text-xl font-bold">Salesperson Audit</h2>
            <div class="toggle-group">
                <button class="toggle-btn" :class="{ 'active': selectedRank === 'rank1' }" @click="selectedRank = 'rank1'">Rank 1</button>
                <button class="toggle-btn" :class="{ 'active': selectedRank === 'rank2' }" @click="selectedRank = 'rank2'">Rank 2</button>
            </div>
        </div>
        <br>

        <template x-if="selectedRank === 'rank1'">
            <div>
                <div class="stats-summary">
                    <div class="stats-card" style="flex:1;">
                        <div class="stats-card__body">
                            <div class="stats-label" style="margin-bottom:4px;">Latest Demo Assigned</div>
                            @if($latestDemoInfoRank1)
                                <div class="stats-value" style="font-size:1.1rem;">{{ $latestDemoInfoRank1['salesperson'] }}</div>
                                <div class="stats-label">Company: {{ $latestDemoInfoRank1['company'] }}</div>
                                <div class="stats-label">Date: {{ \Carbon\Carbon::parse($latestDemoInfoRank1['date'])->format('d M Y') }}</div>
                            @else
                                <div class="stats-label">No demo data found.</div>
                            @endif
                        </div>
                    </div>
                    <div class="stats-card" style="flex:1;">
                        <div class="stats-card__body">
                            <div class="stats-label" style="margin-bottom:4px;">Latest RFQ Assigned</div>
                            @if($latestRfqInfoRank1)
                                <div class="stats-value" style="font-size:1.1rem;">{{ $latestRfqInfoRank1['salesperson'] }}</div>
                                <div class="stats-label">Company: {{ $latestRfqInfoRank1['company'] }}</div>
                                <div class="stats-label">Date: {{ \Carbon\Carbon::parse($latestRfqInfoRank1['date'])->format('d M Y') }}</div>
                            @else
                                <div class="stats-label">No RFQ data found.</div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="stats-grid">
                    @foreach($rank1 as $spId)
                        <div class="stats-card">
                            <div class="stats-card__header" style="background-color: rgba({{ implode(',', $this->getSalespersonColor($salespersonNames[$spId] ?? '')) }},0.1);">
                                <div class="flex-between">
                                    <h3 class="font-medium">{{ $salespersonNames[$spId] ?? $spId }}</h3>
                                    <span class="group-count">{{ array_sum($rank1DemoStats[$spId] ?? []) + array_sum($rank1RfqStats[$spId] ?? []) }}</span>
                                </div>
                            </div>
                            <div class="stats-card__body">
                                <!-- Add Demo Section -->
                                <div class="stats-subsection">
                                    <div class="stats-section-title">
                                        Add Demo
                                        <span class="stats-value" style="margin-left:8px;">
                                            {{ array_sum($rank1DemoStats[$spId] ?? []) }}
                                        </span>
                                    </div>
                                    <div class="mb-1 flex-between">
                                        <span class="stats-label">Small Companies</span>
                                        <span class="stats-label">{{ $rank1DemoStats[$spId]['1-24'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--small" style="width: {{ array_sum($rank1DemoStats[$spId] ?? []) > 0 ? round(($rank1DemoStats[$spId]['1-24'] ?? 0) / array_sum($rank1DemoStats[$spId] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Medium Companies</span>
                                        <span class="stats-label">{{ $rank1DemoStats[$spId]['25-99'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--medium" style="width: {{ array_sum($rank1DemoStats[$spId] ?? []) > 0 ? round(($rank1DemoStats[$spId]['25-99'] ?? 0) / array_sum($rank1DemoStats[$spId] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Large Companies</span>
                                        <span class="stats-label">{{ $rank1DemoStats[$spId]['100-500'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--large" style="width: {{ array_sum($rank1DemoStats[$spId] ?? []) > 0 ? round(($rank1DemoStats[$spId]['100-500'] ?? 0) / array_sum($rank1DemoStats[$spId] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Enterprise Companies</span>
                                        <span class="stats-label">{{ $rank1DemoStats[$spId]['501 and Above'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--enterprise" style="width: {{ array_sum($rank1DemoStats[$spId] ?? []) > 0 ? round(($rank1DemoStats[$spId]['501 and Above'] ?? 0) / array_sum($rank1DemoStats[$spId] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                </div>
                                <hr style="margin: 18px 0; border: none; border-top: 1px solid #e5e7eb;">

                                <!-- Add RFQ Section -->
                                <div class="stats-subsection">
                                    <div class="stats-section-title">
                                        Add RFQ
                                        <span class="stats-value" style="margin-left:8px;">
                                            {{ array_sum($rank1RfqStats[$spId] ?? []) }}
                                        </span>
                                    </div>
                                    <div class="mb-1 flex-between">
                                        <span class="stats-label">Small Companies</span>
                                        <span class="stats-label">{{ $rank1RfqStats[$spId]['1-24'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--small" style="width: {{ array_sum($rank1RfqStats[$spId] ?? []) > 0 ? round(($rank1RfqStats[$spId]['1-24'] ?? 0) / array_sum($rank1RfqStats[$spId] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Medium Companies</span>
                                        <span class="stats-label">{{ $rank1RfqStats[$spId]['25-99'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--medium" style="width: {{ array_sum($rank1RfqStats[$spId] ?? []) > 0 ? round(($rank1RfqStats[$spId]['25-99'] ?? 0) / array_sum($rank1RfqStats[$spId] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Large Companies</span>
                                        <span class="stats-label">{{ $rank1RfqStats[$spId]['100-500'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--large" style="width: {{ array_sum($rank1RfqStats[$spId] ?? []) > 0 ? round(($rank1RfqStats[$spId]['100-500'] ?? 0) / array_sum($rank1RfqStats[$spId] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Enterprise Companies</span>
                                        <span class="stats-label">{{ $rank1RfqStats[$spId]['501 and Above'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--enterprise" style="width: {{ array_sum($rank1RfqStats[$spId] ?? []) > 0 ? round(($rank1RfqStats[$spId]['501 and Above'] ?? 0) / array_sum($rank1RfqStats[$spId] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div id="implementer-audit-container" class="hardware-handover-container"
                    x-data="{
                        selectedType: 'small',
                        setSelectedType(value) {
                            if (this.selectedType === value) {
                                this.selectedType = null;
                            } else {
                                this.selectedType = value;
                            }
                        },
                        init() {
                            this.selectedType = 'small';
                        }
                    }"
                    x-init="init()">
                    <div class="dashboard-layout">
                        <!-- Left sidebar with type selection -->
                        <div class="group-column">
                            <div class="group-container">
                                <div
                                    class="group-box group-small"
                                    :class="{ 'selected': selectedType === 'small' }"
                                    @click="setSelectedType('small')"
                                >
                                    <div class="group-title">Small Companies</div>
                                    <div class="group-count">
                                        1
                                    </div>
                                </div>
                                <div
                                    class="group-box group-medium"
                                    :class="{ 'selected': selectedType === 'medium' }"
                                    @click="setSelectedType('medium')"
                                >
                                    <div class="group-title">Medium Companies</div>
                                    <div class="group-count">
                                        23
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right content area -->
                        <div class="content-column">
                            <div class="hint-message" x-show="!selectedType" x-transition>
                                <h3>Select company type to view data</h3>
                                <p>Click on Small or Medium to display the assignments table</p>
                            </div>
                            <template x-if="selectedType === 'small'">
                                <div>
                                    <livewire:salesperson-sequence-small />
                                </div>
                            </template>
                            <template x-if="selectedType === 'medium'">
                                <div>
                                    <livewire:salesperson-sequence-medium />
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="selectedRank === 'rank2'">
            <div>
                <div class="stats-summary">
                    <div class="stats-card" style="flex:1;">
                        <div class="stats-card__body">
                            <div class="stats-label" style="margin-bottom:4px;">Latest Demo Assigned</div>
                            @if($latestDemoInfoRank2)
                                <div class="stats-value" style="font-size:1.1rem;">{{ $latestDemoInfoRank2['salesperson'] }}</div>
                                <div class="stats-label">Company: {{ $latestDemoInfoRank2['company'] }}</div>
                                <div class="stats-label">Date: {{ \Carbon\Carbon::parse($latestDemoInfoRank2['date'])->format('d M Y') }}</div>
                            @else
                                <div class="stats-label">No demo data found.</div>
                            @endif
                        </div>
                    </div>
                    <div class="stats-card" style="flex:1;">
                        <div class="stats-card__body">
                            <div class="stats-label" style="margin-bottom:4px;">Latest RFQ Assigned</div>
                            @if($latestRfqInfoRank2)
                                <div class="stats-value" style="font-size:1.1rem;">{{ $latestRfqInfoRank2['salesperson'] }}</div>
                                <div class="stats-label">Company: {{ $latestRfqInfoRank2['company'] }}</div>
                                <div class="stats-label">Date: {{ \Carbon\Carbon::parse($latestRfqInfoRank2['date'])->format('d M Y') }}</div>
                            @else
                                <div class="stats-label">No RFQ data found.</div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="stats-grid">
                    @foreach($rank2 as $spId)
                        <div class="stats-card">
                            <div class="stats-card__header" style="background-color: rgba({{ implode(',', $this->getSalespersonColor($salespersonNames[$spId] ?? '')) }},0.1);">
                                <div class="flex-between">
                                    <h3 class="font-medium">{{ $salespersonNames[$spId] ?? $spId }}</h3>
                                    <span class="group-count">{{ array_sum($rank2DemoStats[$spId] ?? []) + array_sum($rank2RfqStats[$spId] ?? []) }}</span>
                                </div>
                            </div>
                            <div class="stats-card__body">
                                <!-- Add Demo Section -->
                                <div class="stats-subsection">
                                    <div class="stats-section-title">
                                        Add Demo
                                        <span class="stats-value" style="margin-left:8px;">
                                            {{ array_sum($rank2DemoStats[$spId] ?? []) }}
                                        </span>
                                    </div>
                                    <div class="mb-1 flex-between">
                                        <span class="stats-label">Small Companies</span>
                                        <span class="stats-label">{{ $rank2DemoStats[$spId]['1-24'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--small" style="width: {{ array_sum($rank2DemoStats[$spId] ?? []) > 0 ? round(($rank2DemoStats[$spId]['1-24'] ?? 0) / array_sum($rank2DemoStats[$spId] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Medium Companies</span>
                                        <span class="stats-label">{{ $rank2DemoStats[$spId]['25-99'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--medium" style="width: {{ array_sum($rank2DemoStats[$spId] ?? []) > 0 ? round(($rank2DemoStats[$spId]['25-99'] ?? 0) / array_sum($rank2DemoStats[$spId] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Large Companies</span>
                                        <span class="stats-label">{{ $rank2DemoStats[$spId]['100-500'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--large" style="width: {{ array_sum($rank2DemoStats[$spId] ?? []) > 0 ? round(($rank2DemoStats[$spId]['100-500'] ?? 0) / array_sum($rank2DemoStats[$spId] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Enterprise Companies</span>
                                        <span class="stats-label">{{ $rank2DemoStats[$spId]['501 and Above'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--enterprise" style="width: {{ array_sum($rank2DemoStats[$spId] ?? []) > 0 ? round(($rank2DemoStats[$spId]['501 and Above'] ?? 0) / array_sum($rank2DemoStats[$spId] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                </div>
                                <hr style="margin: 18px 0; border: none; border-top: 1px solid #e5e7eb;">

                                <!-- Add RFQ Section -->
                                <div class="stats-subsection">
                                    <div class="stats-section-title">
                                        Add RFQ
                                        <span class="stats-value" style="margin-left:8px;">
                                            {{ array_sum($rank2RfqStats[$spId] ?? []) }}
                                        </span>
                                    </div>
                                    <div class="mb-1 flex-between">
                                        <span class="stats-label">Small Companies</span>
                                        <span class="stats-label">{{ $rank2RfqStats[$spId]['1-24'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--small" style="width: {{ array_sum($rank2RfqStats[$spId] ?? []) > 0 ? round(($rank2RfqStats[$spId]['1-24'] ?? 0) / array_sum($rank2RfqStats[$spId] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Medium Companies</span>
                                        <span class="stats-label">{{ $rank2RfqStats[$spId]['25-99'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--medium" style="width: {{ array_sum($rank2RfqStats[$spId] ?? []) > 0 ? round(($rank2RfqStats[$spId]['25-99'] ?? 0) / array_sum($rank2RfqStats[$spId] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Large Companies</span>
                                        <span class="stats-label">{{ $rank2RfqStats[$spId]['100-500'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--large" style="width: {{ array_sum($rank2RfqStats[$spId] ?? []) > 0 ? round(($rank2RfqStats[$spId]['100-500'] ?? 0) / array_sum($rank2RfqStats[$spId] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Enterprise Companies</span>
                                        <span class="stats-label">{{ $rank2RfqStats[$spId]['501 and Above'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--enterprise" style="width: {{ array_sum($rank2RfqStats[$spId] ?? []) > 0 ? round(($rank2RfqStats[$spId]['501 and Above'] ?? 0) / array_sum($rank2RfqStats[$spId] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div id="implementer-audit-container" class="hardware-handover-container"
                    x-data="{
                        selectedType: 'large',
                        setSelectedType(value) {
                            if (this.selectedType === value) {
                                this.selectedType = null;
                            } else {
                                this.selectedType = value;
                            }
                        },
                        init() {
                            this.selectedType = 'large';
                        }
                    }"
                    x-init="init()">
                    <div class="dashboard-layout">
                        <!-- Left sidebar with type selection -->
                        <div class="group-column">
                            <div class="group-container">
                                <div
                                    class="group-box group-medium"
                                    :class="{ 'selected': selectedType === 'large' }"
                                    @click="setSelectedType('large')"
                                >
                                    <div class="group-title">Large Companies</div>
                                    <div class="group-count">
                                        12
                                    </div>
                                </div>
                                <div
                                    class="group-box group-medium"
                                    :class="{ 'selected': selectedType === 'enterprise' }"
                                    @click="setSelectedType('enterprise')"
                                >
                                    <div class="group-title">Enterprise Companies</div>
                                    <div class="group-count">
                                        13
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Right content area -->
                        <div class="content-column">
                            <div class="hint-message" x-show="!selectedType" x-transition>
                                <h3>Select company type to view data</h3>
                                <p>Click on Large or Enterprise to display the assignments table</p>
                            </div>
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
        </template>
    </div>
</x-filament-panels::page>
