<!-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/salesperson-lead-sequence-v2.blade.php -->
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
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stats-summary {
            display: grid;
            grid-template-columns: 1fr 1fr;
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
            border-top: 4px solid #e5e7eb;
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
        .group-large { border-top-color: #dae724; }
        .group-enterprise { border-top-color: #D13B20; }

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

        /* Main Tab Styles */
        .main-tabs {
            display: flex;
            gap: 0.5rem;
            margin-left: 1rem;
        }

        .main-tab {
            background: #f3f4f6;
            border: none;
            padding: 0.5rem 1.25rem;
            border-radius: 9999px;
            font-weight: 600;
            cursor: pointer;
            color: #374151;
            transition: background 0.2s, color 0.2s;
        }

        .main-tab.active {
            background: #2563eb;
            color: #fff;
        }

        .stat-box {
            background-color: white;
            width: 100%;
            min-height: 65px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            margin-bottom: 8px;
        }

        .stat-box:hover {
            background-color: #f9fafb;
            transform: translateX(3px);
        }

        .stat-box.selected {
            background-color: #f9fafb;
            transform: translateX(5px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.15);
        }

        .stat-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
        }

        .stat-count {
            font-size: 20px;
            font-weight: bold;
            margin: 0;
            line-height: 1.2;
        }

        .stat-label {
            color: #6b7280;
            font-size: 13px;
            font-weight: 500;
            line-height: 1.2;
        }

        /* Color coding for stat boxes */
        .demo-today { border-left: 4px solid #2563eb; }
        .demo-today .stat-count { color: #2563eb; }

        .follow-up-lead { border-left: 4px solid #d946ef; }
        .follow-up-lead .stat-count { color: #d946ef; }

        /* Selected states */
        .stat-box.selected.demo-today { background-color: rgba(37, 99, 235, 0.05); border-left-width: 6px; }
        .stat-box.selected.follow-up-lead { background-color: rgba(217, 70, 239, 0.05); border-left-width: 6px; }

        /* Transition animations */
        [x-transition:enter] {
            transition: all 0.2s ease-out;
        }
        [x-transition:enter-start] {
            opacity: 0;
            transform: translateY(10px);
        }
        [x-transition:enter-end] {
            opacity: 1;
            transform: translateY(0);
        }

        /* Category container */
        .category-container {
            display: flex;
            flex-direction: row;
            gap: 10px;
            margin-bottom: 15px;
        }

        /* Responsive adjustments */
        @media (max-width: 640px) {
            .category-container {
                flex-direction: column;
            }
        }
    </style>

    @php
        // Calculate counts for all salespersons combined (from both rank1 and rank2)
        $smallDemoCount = app(\App\Livewire\SalespersonAudit\SalespersonSequenceV2SmallDemo::class)
            ->getTableQuery()
            ->count();

        $smallRfqCount = app(\App\Livewire\SalespersonAudit\SalespersonSequenceV2SmallRfq::class)
            ->getTableQuery()
            ->count();

        $mediumDemoCount = app(\App\Livewire\SalespersonAudit\SalespersonSequenceV2MediumDemo::class)
            ->getTableQuery()
            ->count();

        $mediumRfqCount = app(\App\Livewire\SalespersonAudit\SalespersonSequenceV2MediumRfq::class)
            ->getTableQuery()
            ->count();

        $largeDemoCount = app(\App\Livewire\SalespersonAudit\SalespersonSequenceV2LargeDemo::class)
            ->getTableQuery()
            ->count();

        $largeRfqCount = app(\App\Livewire\SalespersonAudit\SalespersonSequenceV2LargeRfq::class)
            ->getTableQuery()
            ->count();

        $enterpriseDemoCount = app(\App\Livewire\SalespersonAudit\SalespersonSequenceV2EnterpriseDemo::class)
            ->getTableQuery()
            ->count();

        $enterpriseRfqCount = app(\App\Livewire\SalespersonAudit\SalespersonSequenceV2EnterpriseRfq::class)
            ->getTableQuery()
            ->count();
    @endphp

    <div x-data="{ selectedMainTab: 'demo' }">
        <div class="flex items-center mb-6">
            <h2 class="mr-4 text-xl font-bold">SalesPerson Lead Sequence</h2>
            <div class="main-tabs">
                <button class="main-tab" :class="{ 'active': selectedMainTab === 'demo' }" @click="selectedMainTab = 'demo'">Demo</button>
                <button class="main-tab" :class="{ 'active': selectedMainTab === 'rfq' }" @click="selectedMainTab = 'rfq'">RFQ</button>
            </div>
        </div>
        <br>
        <!-- Demo Tab Content -->
        <template x-if="selectedMainTab === 'demo'">
            <div>
                <div class="stats-grid">
                    @foreach($allSalespersons as $spId)
                        <div class="stats-card">
                            <div class="stats-card__header" style="background-color: rgba({{ implode(',', $this->getSalespersonColor($salespersonNames[$spId] ?? '')) }},0.1);">
                                <div class="flex-between">
                                    <h3 class="font-medium">{{ $salespersonNames[$spId] ?? $spId }}</h3>
                                    <span class="group-count">{{ array_sum($demoStats[$spId] ?? []) }}</span>
                                </div>
                            </div>
                            <div class="stats-card__body">
                                <div class="stats-subsection">
                                    <div class="mb-1 flex-between">
                                        <span class="stats-label">Small </span>
                                        <span class="stats-label">{{ $demoStats[$spId]['1-24'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--small" style="width: {{ array_sum($demoStats[$spId] ?? []) > 0 ? round(($demoStats[$spId]['1-24'] ?? 0) / array_sum($demoStats[$spId] ?? []) * 100) : 0 }}%"></div>
                                    </div>

                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Medium </span>
                                        <span class="stats-label">{{ $demoStats[$spId]['25-99'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--medium" style="width: {{ array_sum($demoStats[$spId] ?? []) > 0 ? round(($demoStats[$spId]['25-99'] ?? 0) / array_sum($demoStats[$spId] ?? []) * 100) : 0 }}%"></div>
                                    </div>

                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Large </span>
                                        <span class="stats-label">{{ $demoStats[$spId]['100-500'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--large" style="width: {{ array_sum($demoStats[$spId] ?? []) > 0 ? round(($demoStats[$spId]['100-500'] ?? 0) / array_sum($demoStats[$spId] ?? []) * 100) : 0 }}%"></div>
                                    </div>

                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Enterprise </span>
                                        <span class="stats-label">{{ $demoStats[$spId]['501 and Above'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--enterprise" style="width: {{ array_sum($demoStats[$spId] ?? []) > 0 ? round(($demoStats[$spId]['501 and Above'] ?? 0) / array_sum($demoStats[$spId] ?? []) * 100) : 0 }}%"></div>
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
                        <div class="group-column">
                            <div class="group-container">
                                <div class="group-box group-small"
                                    :class="{ 'selected': selectedType === 'small' }"
                                    @click="setSelectedType('small')">
                                    <div class="group-title">Small </div>
                                    <div class="group-count">{{ $smallDemoCount }}</div>
                                </div>

                                <div class="group-box group-medium"
                                    :class="{ 'selected': selectedType === 'medium' }"
                                    @click="setSelectedType('medium')">
                                    <div class="group-title">Medium </div>
                                    <div class="group-count">{{ $mediumDemoCount }}</div>
                                </div>

                                <div class="group-box group-large"
                                    :class="{ 'selected': selectedType === 'large' }"
                                    @click="setSelectedType('large')">
                                    <div class="group-title">Large </div>
                                    <div class="group-count">{{ $largeDemoCount }}</div>
                                </div>

                                <div class="group-box group-enterprise"
                                    :class="{ 'selected': selectedType === 'enterprise' }"
                                    @click="setSelectedType('enterprise')">
                                    <div class="group-title">Enterprise </div>
                                    <div class="group-count">{{ $enterpriseDemoCount }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Right content area - FIXED: Single table only -->
                        <div class="content-column">
                            <template x-if="selectedType === 'small'">
                                <div>
                                    <livewire:salesperson-audit.salesperson-sequence-v2-small-demo
                                        :start-date="'2025-12-08'"
                                        :all-salespersons="$allSalespersons" />
                                </div>
                            </template>
                            <template x-if="selectedType === 'medium'">
                                <div>
                                    <livewire:salesperson-audit.salesperson-sequence-v2-medium-demo
                                        :start-date="'2025-12-08'"
                                        :all-salespersons="$allSalespersons" />
                                </div>
                            </template>
                            <template x-if="selectedType === 'large'">
                                <div>
                                    <livewire:salesperson-audit.salesperson-sequence-v2-large-demo
                                        :start-date="'2025-12-08'"
                                        :all-salespersons="$allSalespersons" />
                                </div>
                            </template>
                            <template x-if="selectedType === 'enterprise'">
                                <div>
                                    <livewire:salesperson-audit.salesperson-sequence-v2-enterprise-demo
                                        :start-date="'2025-12-08'"
                                        :all-salespersons="$allSalespersons" />
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- RFQ Tab Content -->
        <template x-if="selectedMainTab === 'rfq'">
            <div>
                <div class="stats-grid">
                    @foreach($allSalespersons as $spId)
                        <div class="stats-card">
                            <div class="stats-card__header" style="background-color: rgba({{ implode(',', $this->getSalespersonColor($salespersonNames[$spId] ?? '')) }},0.1);">
                                <div class="flex-between">
                                    <h3 class="font-medium">{{ $salespersonNames[$spId] ?? $spId }}</h3>
                                    <span class="group-count">{{ array_sum($rfqStats[$spId] ?? []) }}</span>
                                </div>
                            </div>
                            <div class="stats-card__body">
                                <div class="stats-subsection">
                                    <div class="mb-1 flex-between">
                                        <span class="stats-label">Small </span>
                                        <span class="stats-label">{{ $rfqStats[$spId]['1-24'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--small" style="width: {{ array_sum($rfqStats[$spId] ?? []) > 0 ? round(($rfqStats[$spId]['1-24'] ?? 0) / array_sum($rfqStats[$spId] ?? []) * 100) : 0 }}%"></div>
                                    </div>

                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Medium </span>
                                        <span class="stats-label">{{ $rfqStats[$spId]['25-99'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--medium" style="width: {{ array_sum($rfqStats[$spId] ?? []) > 0 ? round(($rfqStats[$spId]['25-99'] ?? 0) / array_sum($rfqStats[$spId] ?? []) * 100) : 0 }}%"></div>
                                    </div>

                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Large </span>
                                        <span class="stats-label">{{ $rfqStats[$spId]['100-500'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--large" style="width: {{ array_sum($rfqStats[$spId] ?? []) > 0 ? round(($rfqStats[$spId]['100-500'] ?? 0) / array_sum($rfqStats[$spId] ?? []) * 100) : 0 }}%"></div>
                                    </div>

                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Enterprise </span>
                                        <span class="stats-label">{{ $rfqStats[$spId]['501 and Above'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--enterprise" style="width: {{ array_sum($rfqStats[$spId] ?? []) > 0 ? round(($rfqStats[$spId]['501 and Above'] ?? 0) / array_sum($rfqStats[$spId] ?? []) * 100) : 0 }}%"></div>
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
                        <div class="group-column">
                            <div class="group-container">
                                <div class="group-box group-small"
                                    :class="{ 'selected': selectedType === 'small' }"
                                    @click="setSelectedType('small')">
                                    <div class="group-title">Small </div>
                                    <div class="group-count">{{ $smallRfqCount }}</div>
                                </div>

                                <div class="group-box group-medium"
                                    :class="{ 'selected': selectedType === 'medium' }"
                                    @click="setSelectedType('medium')">
                                    <div class="group-title">Medium </div>
                                    <div class="group-count">{{ $mediumRfqCount }}</div>
                                </div>

                                <div class="group-box group-large"
                                    :class="{ 'selected': selectedType === 'large' }"
                                    @click="setSelectedType('large')">
                                    <div class="group-title">Large </div>
                                    <div class="group-count">{{ $largeRfqCount }}</div>
                                </div>

                                <div class="group-box group-enterprise"
                                    :class="{ 'selected': selectedType === 'enterprise' }"
                                    @click="setSelectedType('enterprise')">
                                    <div class="group-title">Enterprise </div>
                                    <div class="group-count">{{ $enterpriseRfqCount }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Right content area - FIXED: Single table only -->
                        <div class="content-column">
                            <template x-if="selectedType === 'small'">
                                <div>
                                    <livewire:salesperson-audit.salesperson-sequence-v2-small-rfq
                                        :start-date="'2025-12-08'"
                                        :all-salespersons="$allSalespersons" />
                                </div>
                            </template>
                            <template x-if="selectedType === 'medium'">
                                <div>
                                    <livewire:salesperson-audit.salesperson-sequence-v2-medium-rfq
                                        :start-date="'2025-12-08'"
                                        :all-salespersons="$allSalespersons" />
                                </div>
                            </template>
                            <template x-if="selectedType === 'large'">
                                <div>
                                    <livewire:salesperson-audit.salesperson-sequence-v2-large-rfq
                                        :start-date="'2025-12-08'"
                                        :all-salespersons="$allSalespersons" />
                                </div>
                            </template>
                            <template x-if="selectedType === 'enterprise'">
                                <div>
                                    <livewire:salesperson-audit.salesperson-sequence-v2-enterprise-rfq
                                        :start-date="'2025-12-08'"
                                        :all-salespersons="$allSalespersons" />
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-filament-panels::page>
