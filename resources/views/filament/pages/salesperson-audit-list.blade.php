<!-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/salesperson-audit-list.blade.php -->
<x-filament-panels::page>
    <style>
        .toggle-group { display: flex; gap: 0.5rem; margin-left: 1rem; }
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
        .toggle-btn.active { background: #2563eb; color: #fff; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stats-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .stats-card__header { padding: 1rem; border-bottom: 1px solid #f3f4f6; }
        .stats-card__body { padding: 1rem; }
        .stats-label { font-size: 0.875rem; font-weight: 500; color: #6b7280; }
        .stats-value { font-size: 1.5rem; font-weight: 700; }
        .font-medium { font-weight: 500; }
        .group-title { font-size: 15px; font-weight: 600; margin-bottom: 8px; }
        .group-count { font-size: 24px; font-weight: bold; color: #2563eb; }
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
        .flex-between {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stats-footer {
            border-top: 1px solid #f3f4f6;
            padding-top: 0.75rem;
            margin-top: 0.75rem;
            font-size: 0.75rem;
            color: #6b7280;
        }
    </style>

    <div x-data="{ selectedRank: 'rank1', selectedTab: 'demo' }">
        <div class="flex items-center mb-6">
            <h2 class="mr-4 text-xl font-bold">Salesperson Audit</h2>
            <div class="toggle-group">
                <button class="toggle-btn" :class="{ 'active': selectedRank === 'rank1' }" @click="selectedRank = 'rank1'">Rank 1</button>
                <button class="toggle-btn" :class="{ 'active': selectedRank === 'rank2' }" @click="selectedRank = 'rank2'">Rank 2</button>
            </div>
            <div class="ml-6 toggle-group">
                <button class="toggle-btn" :class="{ 'active': selectedTab === 'demo' }" @click="selectedTab = 'demo'">Add Demo</button>
                <button class="toggle-btn" :class="{ 'active': selectedTab === 'rfq' }" @click="selectedTab = 'rfq'">Add RFQ</button>
            </div>
        </div>
        <br>

        <template x-if="selectedRank === 'rank1'">
            <div>
                <template x-if="selectedTab === 'demo'">
                    <div class="stats-grid">
                        @foreach($rank1 as $sp)
                            <div class="stats-card">
                                <div class="stats-card__header">
                                    <div class="flex-between">
                                        <h3 class="font-medium">{{ $sp }}</h3>
                                        <span class="group-count">{{ array_sum($rank1DemoStats[$sp] ?? []) }}</span>
                                    </div>
                                </div>
                                <div class="stats-card__body">
                                    <div class="mb-1 flex-between">
                                        <span class="stats-label">Small Companies</span>
                                        <span class="stats-label">{{ $rank1DemoStats[$sp]['1-24'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--small" style="width: {{ array_sum($rank1DemoStats[$sp] ?? []) > 0 ? round(($rank1DemoStats[$sp]['1-24'] ?? 0) / array_sum($rank1DemoStats[$sp] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Medium Companies</span>
                                        <span class="stats-label">{{ $rank1DemoStats[$sp]['25-99'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--medium" style="width: {{ array_sum($rank1DemoStats[$sp] ?? []) > 0 ? round(($rank1DemoStats[$sp]['25-99'] ?? 0) / array_sum($rank1DemoStats[$sp] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Large Companies</span>
                                        <span class="stats-label">{{ $rank1DemoStats[$sp]['100-500'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--medium" style="width: {{ array_sum($rank1DemoStats[$sp] ?? []) > 0 ? round(($rank1DemoStats[$sp]['100-500'] ?? 0) / array_sum($rank1DemoStats[$sp] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Enterprise Companies</span>
                                        <span class="stats-label">{{ $rank1DemoStats[$sp]['501 and Above'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--medium" style="width: {{ array_sum($rank1DemoStats[$sp] ?? []) > 0 ? round(($rank1DemoStats[$sp]['501 and Above'] ?? 0) / array_sum($rank1DemoStats[$sp] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                    <div class="stats-footer"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </template>
                <template x-if="selectedTab === 'rfq'">
                    <div class="stats-grid">
                        @foreach($rank1 as $sp)
                            <div class="stats-card">
                                <div class="stats-card__header">
                                    <div class="flex-between">
                                        <h3 class="font-medium">{{ $sp }}</h3>
                                        <span class="group-count">{{ array_sum($rank1RfqStats[$sp] ?? []) }}</span>
                                    </div>
                                </div>
                                <div class="stats-card__body">
                                    <div class="mb-1 flex-between">
                                        <span class="stats-label">Small Companies</span>
                                        <span class="stats-label">{{ $rank1RfqStats[$sp]['1-24'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--small" style="width: {{ array_sum($rank1RfqStats[$sp] ?? []) > 0 ? round(($rank1RfqStats[$sp]['1-24'] ?? 0) / array_sum($rank1RfqStats[$sp] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Medium Companies</span>
                                        <span class="stats-label">{{ $rank1RfqStats[$sp]['25-99'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--medium" style="width: {{ array_sum($rank1RfqStats[$sp] ?? []) > 0 ? round(($rank1RfqStats[$sp]['25-99'] ?? 0) / array_sum($rank1RfqStats[$sp] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Large Companies</span>
                                        <span class="stats-label">{{ $rank1RfqStats[$sp]['100-500'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--medium" style="width: {{ array_sum($rank1RfqStats[$sp] ?? []) > 0 ? round(($rank1RfqStats[$sp]['100-500'] ?? 0) / array_sum($rank1RfqStats[$sp] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Enterprise Companies</span>
                                        <span class="stats-label">{{ $rank1RfqStats[$sp]['501 and Above'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--medium" style="width: {{ array_sum($rank1RfqStats[$sp] ?? []) > 0 ? round(($rank1RfqStats[$sp]['501 and Above'] ?? 0) / array_sum($rank1RfqStats[$sp] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                    <div class="stats-footer"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </template>
            </div>
        </template>

        <template x-if="selectedRank === 'rank2'">
            <div>
                <template x-if="selectedTab === 'demo'">
                    <div class="stats-grid">
                        @foreach($rank2 as $sp)
                            <div class="stats-card">
                                <div class="stats-card__header">
                                    <div class="flex-between">
                                        <h3 class="font-medium">{{ $sp }}</h3>
                                        <span class="group-count">{{ array_sum($rank2DemoStats[$sp] ?? []) }}</span>
                                    </div>
                                </div>
                                <div class="stats-card__body">
                                    <div class="mb-1 flex-between">
                                        <span class="stats-label">Small Companies</span>
                                        <span class="stats-label">{{ $rank2DemoStats[$sp]['1-24'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--small" style="width: {{ array_sum($rank2DemoStats[$sp] ?? []) > 0 ? round(($rank2DemoStats[$sp]['1-24'] ?? 0) / array_sum($rank2DemoStats[$sp] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Medium Companies</span>
                                        <span class="stats-label">{{ $rank2DemoStats[$sp]['25-99'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--medium" style="width: {{ array_sum($rank2DemoStats[$sp] ?? []) > 0 ? round(($rank2DemoStats[$sp]['25-99'] ?? 0) / array_sum($rank2DemoStats[$sp] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Large Companies</span>
                                        <span class="stats-label">{{ $rank2DemoStats[$sp]['100-500'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--medium" style="width: {{ array_sum($rank2DemoStats[$sp] ?? []) > 0 ? round(($rank2DemoStats[$sp]['100-500'] ?? 0) / array_sum($rank2DemoStats[$sp] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Enterprise Companies</span>
                                        <span class="stats-label">{{ $rank2DemoStats[$sp]['501 and Above'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--medium" style="width: {{ array_sum($rank2DemoStats[$sp] ?? []) > 0 ? round(($rank2DemoStats[$sp]['501 and Above'] ?? 0) / array_sum($rank2DemoStats[$sp] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                    <div class="stats-footer"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </template>
                <template x-if="selectedTab === 'rfq'">
                    <div class="stats-grid">
                        @foreach($rank2 as $sp)
                            <div class="stats-card">
                                <div class="stats-card__header">
                                    <div class="flex-between">
                                        <h3 class="font-medium">{{ $sp }}</h3>
                                        <span class="group-count">{{ array_sum($rank2RfqStats[$sp] ?? []) }}</span>
                                    </div>
                                </div>
                                <div class="stats-card__body">
                                    <div class="mb-1 flex-between">
                                        <span class="stats-label">Small Companies</span>
                                        <span class="stats-label">{{ $rank2RfqStats[$sp]['1-24'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--small" style="width: {{ array_sum($rank2RfqStats[$sp] ?? []) > 0 ? round(($rank2RfqStats[$sp]['1-24'] ?? 0) / array_sum($rank2RfqStats[$sp] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Medium Companies</span>
                                        <span class="stats-label">{{ $rank2RfqStats[$sp]['25-99'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--medium" style="width: {{ array_sum($rank2RfqStats[$sp] ?? []) > 0 ? round(($rank2RfqStats[$sp]['25-99'] ?? 0) / array_sum($rank2RfqStats[$sp] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Large Companies</span>
                                        <span class="stats-label">{{ $rank2RfqStats[$sp]['100-500'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--medium" style="width: {{ array_sum($rank2RfqStats[$sp] ?? []) > 0 ? round(($rank2RfqStats[$sp]['100-500'] ?? 0) / array_sum($rank2RfqStats[$sp] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                    <div class="mt-3 mb-1 flex-between">
                                        <span class="stats-label">Enterprise Companies</span>
                                        <span class="stats-label">{{ $rank2RfqStats[$sp]['501 and Above'] ?? 0 }}</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-bar--medium" style="width: {{ array_sum($rank2RfqStats[$sp] ?? []) > 0 ? round(($rank2RfqStats[$sp]['501 and Above'] ?? 0) / array_sum($rank2RfqStats[$sp] ?? []) * 100) : 0 }}%"></div>
                                    </div>
                                    <div class="stats-footer"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </template>
            </div>
        </template>
    </div>
</x-filament-panels::page>
