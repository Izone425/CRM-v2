<x-filament-panels::page>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        .analysis-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .analysis-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .analysis-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }

        .filter-container {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .filter-select {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 6px;
            color: white;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .filter-select option {
            background: #4a5568;
            color: white;
        }

        .date-input {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 6px;
            color: white;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .date-input::-webkit-calendar-picker-indicator {
            filter: invert(1);
        }

        /* Custom Chart Styles */
        .custom-chart-container {
            padding: 1rem;
            background: #f8fafc;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .chart-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2d3748;
        }

        .chart-period {
            font-size: 0.875rem;
            color: #718096;
        }

        .svg-chart {
            width: 100%;
            height: 450px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .grid-line {
            stroke: #e2e8f0;
            stroke-width: 1;
        }

        .axis-line {
            stroke: #cbd5e0;
            stroke-width: 2;
        }

        .axis-text {
            font-size: 11px;
            fill: #718096;
            font-family: system-ui, -apple-system, sans-serif;
        }

        .data-line {
            fill: none;
            stroke-width: 2.5;
            stroke-linecap: round;
            stroke-linejoin: round;
            transition: opacity 0.3s ease;
        }

        .data-point {
            r: 4;
            stroke: white;
            stroke-width: 2;
            transition: opacity 0.3s ease;
        }

        .data-point:hover {
            r: 6;
            stroke-width: 3;
        }

        .data-line.hidden,
        .data-point.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .tooltip {
            position: absolute;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            pointer-events: none;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .legend-container {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            padding: 1rem;
            background: white;
            border-top: 1px solid #e2e8f0;
            justify-content: center;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #4a5568;
            cursor: pointer;
            transition: opacity 0.3s ease;
            user-select: none;
        }

        .legend-item:hover {
            opacity: 0.8;
        }

        .legend-item.inactive {
            opacity: 0.4;
        }

        .legend-item.inactive .legend-color {
            background-color: #9ca3af !important;
        }

        .legend-color {
            width: 20px;
            height: 3px;
            border-radius: 2px;
            transition: background-color 0.3s ease;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            padding: 1rem;
            background: #f7fafc;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #718096;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .incoming-stat {
            color: #10b981;
        }

        .outgoing-stat {
            color: #3b82f6;
        }

        .loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
            color: #718096;
        }

        .spinner {
            width: 2rem;
            height: 2rem;
            border: 2px solid #e2e8f0;
            border-top: 2px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 0.5rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .no-data {
            text-align: center;
            padding: 4rem;
            color: #718096;
            font-size: 1.1rem;
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .analysis-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .filter-container {
                flex-wrap: wrap;
                justify-content: center;
            }

            .custom-chart-container {
                padding: 1rem;
            }

            .svg-chart {
                height: 300px;
            }
        }
    </style>

    <div x-data="{
        loading: false,
        tooltip: {
            visible: false,
            x: 0,
            y: 0,
            content: ''
        },
        moduleHiddenDatasets: {},
        supportHiddenDatasets: {},

        showTooltip(event, content) {
            this.tooltip = {
                visible: true,
                x: event.clientX + 10,
                y: event.clientY - 10,
                content: content
            };
        },

        hideTooltip() {
            this.tooltip.visible = false;
        },

        toggleModuleDataset(index) {
            this.moduleHiddenDatasets[index] = !this.moduleHiddenDatasets[index];
            this.updateChartVisibility('module', index);
        },

        toggleSupportDataset(index) {
            this.supportHiddenDatasets[index] = !this.supportHiddenDatasets[index];
            this.updateChartVisibility('support', index);
        },

        updateChartVisibility(chartType, index) {
            const isHidden = chartType === 'module' ? this.moduleHiddenDatasets[index] : this.supportHiddenDatasets[index];

            // Update line visibility
            const line = document.querySelector(`#${chartType}-chart .data-line-${index}`);
            if (line) {
                if (isHidden) {
                    line.classList.add('hidden');
                } else {
                    line.classList.remove('hidden');
                }
            }

            // Update points visibility
            const points = document.querySelectorAll(`#${chartType}-chart .data-point-${index}`);
            points.forEach(point => {
                if (isHidden) {
                    point.classList.add('hidden');
                } else {
                    point.classList.remove('hidden');
                }
            });

            // Update legend item appearance
            const legendItem = document.querySelector(`#${chartType}-legend .legend-item-${index}`);
            if (legendItem) {
                if (isHidden) {
                    legendItem.classList.add('inactive');
                } else {
                    legendItem.classList.remove('inactive');
                }
            }
        }
    }">

        <!-- Tooltip -->
        <div class="tooltip"
             x-show="tooltip.visible"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             :style="`left: ${tooltip.x}px; top: ${tooltip.y}px; opacity: ${tooltip.visible ? 1 : 0}`"
             x-html="tooltip.content">
        </div>

        <!-- Analysis by Module -->
        <div class="analysis-container">
            <div class="analysis-header">
                <h2 class="analysis-title">
                    <i class="mr-2 fas fa-chart-line"></i>
                    Analysis by Module
                </h2>
                <div class="filter-container">
                    <select wire:model.live="dateRange" class="filter-select">
                        @foreach($this->getDateRangeOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    <!-- Custom Date Range Inputs -->
                    <div x-show="$wire.dateRange === 'custom_range'" class="flex items-center gap-2">
                        <input type="date" wire:model.live="startDate" class="date-input" />
                        <span class="text-white">to</span>
                        <input type="date" wire:model.live="endDate" class="date-input" />
                    </div>

                    <!-- Month/Year selectors for current_month option -->
                    <select wire:model.live="selectedYear" class="filter-select" x-show="$wire.dateRange === 'current_month'">
                        @foreach($this->getAvailableYears() as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="selectedMonth" class="filter-select" x-show="$wire.dateRange === 'current_month'">
                        @foreach($this->getAvailableMonths() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="custom-chart-container" x-show="!loading">
                <div class="chart-header">
                    <div class="chart-title">Module Support Trends</div>
                    <div class="chart-period">
                        {{ $this->getFormattedDateRange() }}
                    </div>
                </div>

                @if(!empty($moduleAnalysisData['datasets']))
                    @php
                        $maxValue = 0;
                        foreach($moduleAnalysisData['datasets'] as $dataset) {
                            if (is_array($dataset['data']) && !empty($dataset['data'])) {
                                $maxValue = max($maxValue, max($dataset['data']));
                            }
                        }
                        $maxValue = max($maxValue, 10); // Minimum scale

                        $chartWidth = 900;
                        $chartHeight = 350;
                        $padding = 60;
                        $plotWidth = $chartWidth - (2 * $padding);
                        $plotHeight = $chartHeight - (2 * $padding);

                        $dateCount = count($moduleAnalysisData['dates'] ?? []);
                        $stepX = $dateCount > 1 ? $plotWidth / ($dateCount - 1) : 0;

                        $colors = ['#10B981', '#3B82F6', '#F59E0B', '#EF4444', '#8B5CF6', '#6B7280'];
                    @endphp

                    <svg id="module-chart" class="svg-chart" viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" preserveAspectRatio="xMidYMid meet">
                        <!-- Grid lines -->
                        @for($i = 0; $i <= 8; $i++)
                            @php $y = $padding + ($plotHeight * $i / 8); @endphp
                            <line x1="{{ $padding }}" y1="{{ $y }}" x2="{{ $chartWidth - $padding }}" y2="{{ $y }}" class="grid-line" />
                            <text x="{{ $padding - 10 }}" y="{{ $y + 4 }}" class="axis-text" text-anchor="end">
                                {{ round($maxValue - ($maxValue * $i / 8)) }}
                            </text>
                        @endfor

                        <!-- X-axis date labels -->
                        @if(!empty($moduleAnalysisData['dates']))
                            @foreach($moduleAnalysisData['dates'] as $index => $date)
                                @if($index % max(1, floor(count($moduleAnalysisData['dates']) / 10)) == 0) {{-- Dynamic spacing based on date range --}}
                                    @php
                                        $x = $padding + ($stepX * $index);
                                        $formattedDate = \Carbon\Carbon::parse($date)->format('j');
                                        if (count($moduleAnalysisData['dates']) <= 7) {
                                            $formattedDate = \Carbon\Carbon::parse($date)->format('M j');
                                        }
                                    @endphp
                                    <text x="{{ $x }}" y="{{ $chartHeight - 20 }}" class="axis-text" text-anchor="middle">{{ $formattedDate }}</text>
                                @endif
                            @endforeach
                        @endif

                        <!-- Axis lines -->
                        <line x1="{{ $padding }}" y1="{{ $padding }}" x2="{{ $padding }}" y2="{{ $chartHeight - $padding }}" class="axis-line" />
                        <line x1="{{ $padding }}" y1="{{ $chartHeight - $padding }}" x2="{{ $chartWidth - $padding }}" y2="{{ $chartHeight - $padding }}" class="axis-line" />

                        <!-- Data lines -->
                        @foreach($moduleAnalysisData['datasets'] as $datasetIndex => $dataset)
                            @if(is_array($dataset['data']) && !empty($dataset['data']))
                                @php
                                    $color = $colors[$datasetIndex % count($colors)];
                                    $points = [];
                                    foreach($dataset['data'] as $pointIndex => $value) {
                                        $x = $padding + ($stepX * $pointIndex);
                                        $y = $chartHeight - $padding - (($value / $maxValue) * $plotHeight);
                                        $points[] = "$x,$y";
                                    }
                                    $pathData = 'M ' . implode(' L ', $points);
                                @endphp

                                <!-- Line -->
                                <path d="{{ $pathData }}" class="data-line data-line-{{ $datasetIndex }}" stroke="{{ $color }}" />

                                <!-- Points -->
                                @foreach($dataset['data'] as $pointIndex => $value)
                                    @php
                                        $x = $padding + ($stepX * $pointIndex);
                                        $y = $chartHeight - $padding - (($value / $maxValue) * $plotHeight);
                                        $dateLabel = isset($moduleAnalysisData['dates'][$pointIndex]) ? \Carbon\Carbon::parse($moduleAnalysisData['dates'][$pointIndex])->format('M j') : '';
                                    @endphp
                                    <circle cx="{{ $x }}" cy="{{ $y }}" class="data-point data-point-{{ $datasetIndex }}"
                                            fill="{{ $color }}"
                                            @mouseenter="showTooltip($event, '{{ $dataset['label'] }}<br>{{ $dateLabel }}: {{ $value }} calls')"
                                            @mouseleave="hideTooltip()" />
                                @endforeach
                            @endif
                        @endforeach
                    </svg>

                    <div id="module-legend" class="legend-container">
                        @foreach($moduleAnalysisData['datasets'] as $index => $dataset)
                            <div class="legend-item legend-item-{{ $index }}" @click="toggleModuleDataset({{ $index }})">
                                <div class="legend-color" style="background-color: {{ $colors[$index % count($colors)] }}"></div>
                                <span>{{ $dataset['label'] }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="stats-grid">
                        @php
                            $totalCalls = 0;
                            $peakDay = '';
                            $peakCount = 0;

                            foreach($moduleAnalysisData['datasets'] as $dataset) {
                                if (is_array($dataset['data']) && !empty($dataset['data'])) {
                                    $dataSum = array_sum($dataset['data']);
                                    $totalCalls += $dataSum;

                                    $maxIndex = array_keys($dataset['data'], max($dataset['data']))[0] ?? 0;
                                    if (max($dataset['data']) > $peakCount) {
                                        $peakCount = max($dataset['data']);
                                        $peakDay = isset($moduleAnalysisData['dates'][$maxIndex]) ? \Carbon\Carbon::parse($moduleAnalysisData['dates'][$maxIndex])->format('j M') : 'N/A';
                                    }
                                }
                            }
                        @endphp

                        <div class="stat-card">
                            <div class="stat-value">{{ $totalCalls }}</div>
                            <div class="stat-label">Total Calls</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">{{ count($moduleAnalysisData['modules'] ?? []) }}</div>
                            <div class="stat-label">Active Modules</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">{{ $peakDay ?: 'N/A' }}</div>
                            <div class="stat-label">Peak Day</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">{{ count($moduleAnalysisData['dates'] ?? []) > 0 ? round($totalCalls / count($moduleAnalysisData['dates']), 1) : 0 }}</div>
                            <div class="stat-label">Avg/Day</div>
                        </div>
                    </div>
                @else
                    <div class="no-data">
                        <i class="fas fa-chart-line"></i>
                        <div>No module data available for the selected period</div>
                    </div>
                @endif
            </div>

            <div x-show="loading" class="loading-spinner">
                <div class="spinner"></div>
                Loading module analysis...
            </div>
        </div>

        <!-- Analysis by Phone Call (Support Staff) -->
        <div class="analysis-container">
            <div class="analysis-header">
                <h2 class="analysis-title">
                    <i class="mr-2 fas fa-phone"></i>
                    Analysis by Phone Call (Support Staff)
                </h2>
                <div class="filter-container">
                    <select wire:model.live="dateRange" class="filter-select">
                        @foreach($this->getDateRangeOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    <!-- Custom Date Range Inputs -->
                    <div x-show="$wire.dateRange === 'custom_range'" class="flex items-center gap-2">
                        <input type="date" wire:model.live="startDate" class="date-input" />
                        <span class="text-white">to</span>
                        <input type="date" wire:model.live="endDate" class="date-input" />
                    </div>

                    <!-- Month/Year selectors for current_month option -->
                    <select wire:model.live="selectedYear" class="filter-select" x-show="$wire.dateRange === 'current_month'">
                        @foreach($this->getAvailableYears() as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="selectedMonth" class="filter-select" x-show="$wire.dateRange === 'current_month'">
                        @foreach($this->getAvailableMonths() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="custom-chart-container" x-show="!loading">
                <div class="chart-header">
                    <div class="chart-title">
                        @php
                            $totalIncomingCalls = 0;
                            if (!empty($supportAnalysisData['datasets'])) {
                                foreach($supportAnalysisData['datasets'] as $dataset) {
                                    if (is_array($dataset['data'])) {
                                        $totalIncomingCalls += array_sum($dataset['data']);
                                    }
                                }
                            }
                        @endphp
                        <span class="incoming-stat">{{ $totalIncomingCalls }} Calls</span>
                        <span class="mx-3">•</span>
                        <span class="outgoing-stat">{{ count($supportAnalysisData['staff'] ?? []) }} Support Staff</span>
                    </div>
                    <div class="chart-period">
                        {{ $this->getFormattedDateRange() }}
                    </div>
                </div>

                @if(!empty($supportAnalysisData['datasets']))
                    @php
                        $maxSupportValue = 0;
                        foreach($supportAnalysisData['datasets'] as $dataset) {
                            if (is_array($dataset['data']) && !empty($dataset['data'])) {
                                $maxSupportValue = max($maxSupportValue, max($dataset['data']));
                            }
                        }
                        $maxSupportValue = max($maxSupportValue, 10); // Minimum scale

                        $supportColors = ['#10B981', '#3B82F6', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#14B8A6', '#F97316'];
                    @endphp

                    <svg id="support-chart" class="svg-chart" viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" preserveAspectRatio="xMidYMid meet">
                        <!-- Grid lines -->
                        @for($i = 0; $i <= 8; $i++)
                            @php $y = $padding + ($plotHeight * $i / 8); @endphp
                            <line x1="{{ $padding }}" y1="{{ $y }}" x2="{{ $chartWidth - $padding }}" y2="{{ $y }}" class="grid-line" />
                            <text x="{{ $padding - 10 }}" y="{{ $y + 4 }}" class="axis-text" text-anchor="end">
                                {{ round($maxSupportValue - ($maxSupportValue * $i / 8)) }}
                            </text>
                        @endfor

                        <!-- X-axis date labels -->
                        @if(!empty($supportAnalysisData['dates']))
                            @foreach($supportAnalysisData['dates'] as $index => $date)
                                @if($index % max(1, floor(count($supportAnalysisData['dates']) / 10)) == 0)
                                    @php
                                        $x = $padding + ($stepX * $index);
                                        $formattedDate = \Carbon\Carbon::parse($date)->format('j');
                                        if (count($supportAnalysisData['dates']) <= 7) {
                                            $formattedDate = \Carbon\Carbon::parse($date)->format('M j');
                                        }
                                    @endphp
                                    <text x="{{ $x }}" y="{{ $chartHeight - 20 }}" class="axis-text" text-anchor="middle">{{ $formattedDate }}</text>
                                @endif
                            @endforeach
                        @endif

                        <!-- Axis lines -->
                        <line x1="{{ $padding }}" y1="{{ $padding }}" x2="{{ $padding }}" y2="{{ $chartHeight - $padding }}" class="axis-line" />
                        <line x1="{{ $padding }}" y1="{{ $chartHeight - $padding }}" x2="{{ $chartWidth - $padding }}" y2="{{ $chartHeight - $padding }}" class="axis-line" />

                        <!-- Support Staff Data lines -->
                        @foreach($supportAnalysisData['datasets'] as $datasetIndex => $dataset)
                            @if(is_array($dataset['data']) && !empty($dataset['data']))
                                @php
                                    $color = $supportColors[$datasetIndex % count($supportColors)];
                                    $points = [];
                                    foreach($dataset['data'] as $pointIndex => $value) {
                                        $x = $padding + ($stepX * $pointIndex);
                                        $y = $chartHeight - $padding - (($value / $maxSupportValue) * $plotHeight);
                                        $points[] = "$x,$y";
                                    }
                                    $pathData = 'M ' . implode(' L ', $points);
                                @endphp

                                <!-- Line -->
                                <path d="{{ $pathData }}" class="data-line data-line-{{ $datasetIndex }}" stroke="{{ $color }}" />

                                <!-- Points -->
                                @foreach($dataset['data'] as $pointIndex => $value)
                                    @php
                                        $x = $padding + ($stepX * $pointIndex);
                                        $y = $chartHeight - $padding - (($value / $maxSupportValue) * $plotHeight);
                                        $dateLabel = isset($supportAnalysisData['dates'][$pointIndex]) ? \Carbon\Carbon::parse($supportAnalysisData['dates'][$pointIndex])->format('M j') : '';
                                    @endphp
                                    <circle cx="{{ $x }}" cy="{{ $y }}" class="data-point data-point-{{ $datasetIndex }}"
                                            fill="{{ $color }}"
                                            @mouseenter="showTooltip($event, '{{ $dataset['label'] }}<br>{{ $dateLabel }}: {{ $value }} calls')"
                                            @mouseleave="hideTooltip()" />
                                @endforeach
                            @endif
                        @endforeach
                    </svg>

                    <div id="support-legend" class="legend-container">
                        @foreach($supportAnalysisData['datasets'] as $index => $dataset)
                            <div class="legend-item legend-item-{{ $index }}" @click="toggleSupportDataset({{ $index }})">
                                <div class="legend-color" style="background-color: {{ $supportColors[$index % count($supportColors)] }}"></div>
                                <span>{{ $dataset['label'] }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="stats-grid">
                        @php
                            $totalSupportCalls = 0;
                            $mostActiveStaff = '';
                            $maxStaffCalls = 0;

                            foreach($supportAnalysisData['datasets'] as $dataset) {
                                if (is_array($dataset['data'])) {
                                    $staffTotal = array_sum($dataset['data']);
                                    $totalSupportCalls += $staffTotal;

                                    if ($staffTotal > $maxStaffCalls) {
                                        $maxStaffCalls = $staffTotal;
                                        $mostActiveStaff = $dataset['label'];
                                    }
                                }
                            }
                        @endphp

                        <div class="stat-card">
                            <div class="stat-value">{{ $totalSupportCalls }}</div>
                            <div class="stat-label">Total Calls</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">{{ count($supportAnalysisData['staff'] ?? []) }}</div>
                            <div class="stat-label">Support Staff</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">{{ $mostActiveStaff ?: 'N/A' }}</div>
                            <div class="stat-label">Most Active</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">{{ count($supportAnalysisData['dates'] ?? []) > 0 ? round($totalSupportCalls / count($supportAnalysisData['dates']), 1) : 0 }}</div>
                            <div class="stat-label">Avg/Day</div>
                        </div>
                    </div>
                @else
                    <div class="no-data">
                        <i class="fas fa-phone"></i>
                        <div>No support call data available for the selected period</div>
                    </div>
                @endif
            </div>

            <div x-show="loading" class="loading-spinner">
                <div class="spinner"></div>
                Loading support analysis...
            </div>
        </div>
    </div>
</x-filament-panels::page>
