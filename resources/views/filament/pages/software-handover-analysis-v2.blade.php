<x-filament-panels::page>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

    <style>
        .dashboard-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .dashboard-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 10px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: #111827;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-title i {
            color: #6b7280;
        }

        .chart-container {
            position: relative;
            height: 150px;
            width: 100%;
        }

        .mini-charts-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .mini-chart-card {
            flex: 1;
            min-width: 240px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 10px;
        }

        .mini-chart-container {
            position: relative;
            height: 100px;
            width: 100%;
        }

        .year-selector {
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
            background-color: #f9fafb;
            font-size: 14px;
        }

        .status-pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-open {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-delay {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-inactive {
            background-color: #e5e7eb;
            color: #374151;
        }

        .status-closed {
            background-color: #d1fae5;
            color: #065f46;
        }

        .horizontal-bar-container {
            margin-top: 6px;
        }

        .horizontal-bar-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
        }

        .bar-label {
            width: 80px;
            font-size: 14px;
            font-weight: 500;
        }

        .bar-track {
            flex-grow: 1;
            height: 12px;
            background-color: #f3f4f6;
            border-radius: 6px;
            overflow: hidden;
            margin: 0 12px;
        }

        .bar-fill {
            height: 100%;
            border-radius: 6px;
        }

        .bar-fill-open {
            background-color: #10b981;
        }

        .bar-fill-delay {
            background-color: #3b82f6;
        }

        .bar-fill-inactive {
            background-color: #eab308;
        }

        .bar-fill-closed {
            background-color: #ef4444;
        }

        .bar-fill-small {
            background-color: #10b981;
        }

        .bar-fill-medium {
            background-color: #3b82f6;
        }

        .bar-fill-large {
            background-color: #eab308;
        }

        .bar-fill-enterprise {
            background-color: #ef4444;
        }

        .bar-value {
            width: 90px;
            font-size: 14px;
            font-weight: 600;
        }

        .salesperson-metrics {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 16px;
            margin-top: 12px;
        }

        .salesperson-metric {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100px;
        }

        .metric-chart {
            margin-bottom: 8px;
        }

        .circular-progress {
            position: relative;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: conic-gradient(
                var(--color) calc(var(--percentage) * 3.6deg),
                #e5e7eb 0deg
            );
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .circular-progress::before {
            content: "";
            position: absolute;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: white;
        }

        .circular-progress .inner {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1;
        }

        .circular-progress .value {
            font-size: 16px;
            font-weight: 600;
        }

        .metric-info {
            text-align: center;
        }

        .salesperson-name {
            font-size: 14px;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }

        .salesperson-rank {
            font-size: 12px;
            color: #6b7280;
        }

        .toggle-container {
            display: flex;
            border: 1px solid #d1d5db;
            border-radius: 9999px;
            overflow: hidden;
        }

        .toggle-button {
            background-color: #f9fafb;
            border: none;
            padding: 4px 12px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .toggle-button.active {
            background-color: #dbeafe;
            color: #1e40af;
            font-weight: 600;
        }

        [x-cloak] {
            display: none !important;
        }

        .circular-progress {
            position: relative;
        }

        .circle-tooltip {
            position: absolute;
            top: -35px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0, 0, 0, 0.75);
            color: white;
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s, visibility 0.2s;
            z-index: 10;
            white-space: nowrap;
            pointer-events: none;
        }

        .circular-progress:hover .circle-tooltip {
            opacity: 1;
            visibility: visible;
        }

        .horizontal-bar-tooltip {
            position: absolute;
            right: -5px;
            top: -25px;
            transform: translateX(0);
            background-color: rgba(0, 0, 0, 0.75);
            color: white;
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s, visibility 0.2s;
            white-space: nowrap;
            pointer-events: none;
        }

        /* Make bar-fill position relative so tooltip positioning works */
        .bar-fill {
            position: relative;
            height: 100%;
            border-radius: 6px;
        }

        /* Show tooltip on hover */
        .bar-fill:hover .horizontal-bar-tooltip {
            opacity: 1;
            visibility: visible;
        }

        /* Make bar-fill position relative so tooltip positioning works */
        .bar-fill {
            position: relative;
            height: 100%;
            border-radius: 6px;
        }

        /* Show tooltip on hover */
        .bar-fill:hover .horizontal-bar-tooltip {
            opacity: 1;
            visibility: visible;
        }

        .target-container {
            width: 100%;
            height: 160px;
            position: relative;
        }

        .target-chart {
            width: 95%;
            height: 120px;
            position: relative;
            border-left: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
            margin-left: 35px;
            margin-bottom: 30px;
        }

        .month-label {
            position: absolute;
            bottom: -25px;
            transform: translateX(-50%);
            font-size: 12px;
            font-weight: 500;
            color: #6b7280;
            text-align: center;
            width: 40px;
        }

        .new-projects-bar {
            position: absolute;
            bottom: 0;
            background-color: #10b981; /* Green */
            border-radius: 3px 3px 0 0;
        }

        .closed-projects-bar {
            position: absolute;
            bottom: 0;
            background-color: #f59e0b; /* Amber/Yellow */
            border-radius: 3px 3px 0 0;
        }

        .target-line {
            position: absolute;
            height: 3px;
            background-color: #ef4444; /* Red */
            z-index: 3;
            width: 6%; /* Fixed width for target line */
        }

        .target-value {
            color: #ef4444;
            position: absolute;
            top: -5px;
            left: 105%;
            font-weight: 600;
            font-size: 11px;
        }

        .target-legend {
            display: flex;
            justify-content: center;
            gap: 20px;
            padding: 10px 0;
        }

        .target-legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 500;
        }

        .legend-box {
            width: 12px;
            height: 12px;
            border-radius: 2px;
        }

        .new-projects-bar,
        .closed-projects-bar {
            cursor: pointer;
        }

        .bar-tooltip {
            position: absolute;
            top: -40px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            white-space: nowrap;
            z-index: 10;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s, visibility 0.2s;
            pointer-events: none;
        }

        .new-projects-bar:hover .bar-tooltip,
        .closed-projects-bar:hover .bar-tooltip {
            opacity: 1;
            visibility: visible;
        }

        .target-color { background-color: #ef4444; }
        .new-color { background-color: #10b981; }
        .closed-color { background-color: #f59e0b; }

        /* Fixed dimensions for module chart */
        .module-container {
            width: 100%;
            height: 150px;
            position: relative;
            margin-top: 10px;
        }

        .module-chart {
            width: 93%;
            height: 120px;
            position: relative;
            border-left: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
            margin-left: 35px;
            margin-bottom: 30px;
        }

        .grid-lines {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            pointer-events: none;
        }

        .grid-line {
            height: 1px;
            width: 100%;
            background-color: #f3f4f6;
        }

        .quarter-label {
            position: absolute;
            bottom: -25px;
            transform: translateX(-50%);
            font-size: 12px;
            font-weight: 500;
            color: #6b7280;
            text-align: center;
            width: 60px;
        }

        .line-point {
            position: absolute;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            transform: translate(-50%, -50%);
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            border: 2px solid white;
            cursor: pointer;
        }

        .value-indicator {
            position: absolute;
            left: -35px;
            transform: translateY(-50%);
            font-size: 11px;
            color: #6b7280;
            width: 30px;
            text-align: right;
        }

        .point-tooltip {
            position: absolute;
            background-color: rgba(0,0,0,0.85);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            transform: translate(-50%, -120%);
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s;
            white-space: nowrap;
        }

        .line-point:hover + .point-tooltip {
            opacity: 1;
        }

        .module-legend {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            padding: 10px 0;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 500;
        }

        .legend-color {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .ta-color { background-color: #8b5cf6; stroke: #8b5cf6; }
        .tl-color { background-color: #ef4444; stroke: #ef4444; }
        .tc-color { background-color: #10b981; stroke: #10b981; }
        .tp-color { background-color: #3b82f6; stroke: #3b82f6; }

        .svg-line {
            fill: none;
            stroke-width: 3.5px; /* Change from 0.5px to a consistent 2px */
            stroke-linecap: round;
            stroke-linejoin: round;
            vector-effect: non-scaling-stroke; /* This is the key addition */
        }

        .line-point {
            position: absolute;
            width: 8px; /* Changed from 10px to 8px */
            height: 8px; /* Changed from 10px to 8px */
            border-radius: 50%;
            transform: translate(-50%, -50%);
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            border: 2px solid white;
            cursor: pointer;
        }

        .legend-color {
            width: 8px; /* Changed from 10px to 8px */
            height: 8px; /* Changed from 10px to 8px */
            border-radius: 50%;
            flex-shrink: 0;
        }

        .chart-svg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        .tooltip-divider {
            height: 1px;
            background-color: rgba(255, 255, 255, 0.5);
            margin: 5px 0;
            width: 100%;
        }

        /* Make the tooltip slightly wider to accommodate the content better */
        .bar-tooltip {
            min-width: 110px;
        }

        .cursor-pointer {
            cursor: pointer;
        }

        .cursor-pointer:hover {
            transform: scale(1.02);
            transition: all 0.2s;
        }

        .slide-over-modal {
            height: 100vh !important;
            display: flex;
            flex-direction: column;
            background-color: white;
            box-shadow: -4px 0 24px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            margin-top: 55px; /* Add this to push modal down */
            max-height: calc(100vh - 55px); /* Reduce maximum height */
            border-radius: 12px 0 0 0; /* Round top-left corner */
        }

        .slide-over-header {
            position: sticky;
            top: 0;
            background-color: white;
            z-index: 50;
            border-bottom: 1px solid #e5e7eb;
            padding: 1.25rem 1.5rem; /* Increase padding for better visibility */
            min-height: 70px;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-radius: 12px 0 0 0; /* Match the modal's border radius */
        }

        .slide-over-content {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            height: calc(100vh - 64px); /* Calculate remaining height */
            padding-bottom: 80px; /* Add bottom padding for scroll space */
        }

        /* Company item styles */
        .company-item {
            display: block;
            padding: 0.75rem 1rem;
            margin-bottom: 0.75rem;
            background-color: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            transition: all 0.2s;
            font-size: 0.875rem;
            font-weight: 500;
            color: #2563eb;
            text-decoration: none;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .company-item:hover {
            transform: translateY(-2px);
            background-color: #eff6ff;
            border-color: #bfdbfe;
            color: #1e40af;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        /* Group header styles */
        .group-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            margin-top: 0.75rem;
            background: linear-gradient(to right, #2563eb, #3b82f6);
            border-radius: 0.375rem 0.375rem 0 0;
            color: white;
            font-weight: 500;
            cursor: pointer;
        }

        .group-header:hover {
            background: linear-gradient(to right, #1d4ed8, #3b82f6);
        }

        .group-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 1.5rem;
            height: 1.5rem;
            background-color: white;
            color: #2563eb;
            font-weight: 600;
            font-size: 0.75rem;
            border-radius: 9999px;
            margin-right: 0.5rem;
        }

        .group-content {
            padding: 1rem;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-top: none;
            border-radius: 0 0 0.375rem 0.375rem;
        }

        /* Empty state styling */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 1.5rem;
            text-align: center;
            background-color: #f9fafb;
            border-radius: 0.5rem;
            border: 1px dashed #d1d5db;
            color: #6b7280;
        }

        .empty-state-icon {
            width: 3rem;
            height: 3rem;
            color: #9ca3af;
            margin-bottom: 1rem;
        }
    </style>

    <div class="dashboard-container">
        <!-- Section 1: Monthly Software Handover Status -->
        <!-- Monthly Target vs. New vs. Closed Projects -->
        <div class="dashboard-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-bullseye"></i>
                    <span>Total Software Handover by Month</span>&nbsp;&nbsp;&nbsp;&nbsp;
                    <div class="target-legend">
                        <div class="target-legend-item">
                            <div class="legend-box target-color"></div>
                            <span>Target (100/month)</span>
                        </div>
                        <div class="target-legend-item">
                            <div class="legend-box new-color"></div>
                            <span>New Projects</span>
                        </div>
                        <div class="target-legend-item">
                            <div class="legend-box closed-color"></div>
                            <span>Closed Projects</span>
                        </div>
                    </div>
                </div>
                <select class="year-selector" wire:model="selectedTargetYear" wire:change="updateTargetYear">
                    <option value="2026">2026&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</option>
                    <option value="2025">2025&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</option>
                    <option value="2024">2024&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</option>
                </select>
            </div>

            <div class="target-container">
                <div class="target-chart">
                    @php
                        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                        $monthlyData = $this->getHandoversByMonthAndStatus($selectedTargetYear ?? now()->year);
                        $maxHeight = 120; // Max height of chart
                        $targetValue = 100; // Fixed target value per month

                        // Find maximum value for proper scaling
                        $maxValue = $targetValue; // Start with target as minimum max
                        foreach ($monthlyData as $data) {
                            $maxValue = max($maxValue, ($data['total'] ?? 0), ($data['closed'] ?? 0));
                        }

                        // Ensure maximum is at least 125 instead of 100
                        $maxValue = max(125, ceil($maxValue / 25) * 25);

                        // Calculate heights based on this max value
                        $targetHeight = ($targetValue / $maxValue) * $maxHeight;

                        // Create fixed scale intervals at 0, 25, 50, 75, 100, 125
                        $scaleValues = [125, 100, 75, 50, 25, 0];
                        $scalePositions = [];
                        foreach ($scaleValues as $index => $value) {
                            $scalePositions[$index] = 100 - (($value / $maxValue) * 100);
                        }
                    @endphp

                    <!-- Fixed value indicators on the left -->
                    @foreach($scaleValues as $index => $value)
                        @if($value <= $maxValue)
                            <div style="position: absolute; left: -35px; top: {{ $scalePositions[$index] }}%; transform: translateY(-50%); font-size: 11px; color: #6b7280;">
                                {{ $value }}
                            </div>

                            <!-- Horizontal grid lines -->
                            @if($value > 0)
                                <div style="position: absolute; left: 0; right: 0; top: {{ $scalePositions[$index] }}%; height: 1px; background-color: #f3f4f6;"></div>
                            @endif
                        @endif
                    @endforeach

                    <!-- Target line - single continuous line across the chart at the 100 mark -->
                    <div style="position: absolute; left: 0; right: 0; bottom: {{ $targetHeight }}px; height: 2px; background-color: #ef4444;">
                        <span style="position: absolute; right: -40px; top: -8px; color: #ef4444; font-weight: 600; font-size: 12px;">Target</span>
                    </div>

                    @foreach($months as $index => $month)
                        @php
                            $monthData = $monthlyData[$index] ?? null;
                            $newProjects = $monthData ? $monthData['total'] : 0;
                            $newHeight = min(($newProjects / $maxValue) * $maxHeight, $maxHeight);

                            $closedProjects = $monthData ? $monthData['closed'] : 0;
                            $closedHeight = min(($closedProjects / $maxValue) * $maxHeight, $maxHeight);

                            // Calculate the width of each month section
                            $monthWidth = 8.2; // Each month takes 7.5% of the chart width
                            $barWidth = 3; // Individual bar width in percent
                            $spacing = 0.1; // Space between bars in percent

                            // Calculate the center position for this month
                            $monthCenter = ($index * $monthWidth) + ($monthWidth / 2);

                            // Position the bars on either side of the center
                            $newBarX = $monthCenter - $barWidth - ($spacing / 2);
                            $closedBarX = $monthCenter + ($spacing / 2);
                        @endphp

                        <!-- Month label -->
                        <div class="month-label" style="left: {{ $monthCenter }}%">{{ $month }}</div>

                        <!-- New projects bar (green) with tooltip -->
                        <div class="cursor-pointer new-projects-bar" style="left: {{ $newBarX }}%; height: {{ $newHeight }}px; width: {{ $barWidth }}%;" wire:click="openMonthlyHandoverDetailsSlideOver('{{ $month }}', 'new')">
                            <div class="bar-tooltip">
                                New: {{ $newProjects }}
                                <div class="tooltip-divider"></div>
                                Small: {{ $monthData['small'] ?? 0 }}<br>
                                Medium: {{ $monthData['medium'] ?? 0 }}<br>
                                Large: {{ $monthData['large'] ?? 0 }}<br>
                                Enterprise: {{ $monthData['enterprise'] ?? 0 }}
                            </div>
                        </div>

                        <!-- Closed projects bar (yellow) with tooltip -->
                        <div class="cursor-pointer closed-projects-bar" style="left: {{ $closedBarX }}%; height: {{ $closedHeight }}px; width: {{ $barWidth }}%;" wire:click="openMonthlyHandoverDetailsSlideOver('{{ $month }}', 'closed')">
                            <div class="bar-tooltip">
                                Closed: {{ $closedProjects }}
                                <div class="tooltip-divider"></div>
                                Small: {{ $monthData['closed_small'] ?? 0 }}<br>
                                Medium: {{ $monthData['closed_medium'] ?? 0 }}<br>
                                Large: {{ $monthData['closed_large'] ?? 0 }}<br>
                                Enterprise: {{ $monthData['closed_enterprise'] ?? 0 }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Section 2: Top Salespersons -->
        <div class="mini-charts-container">
            <div class="mini-chart-card" style="flex: 2;" x-data="{
                rankView: 'rank1',
                get totalCount() {
                    return this.rankView === 'rank1'
                        ? {{ array_sum(array_column($this->getHandoversBySalesPersonRank1()->toArray(), 'total')) }}
                        : {{ array_sum(array_column($this->getHandoversBySalesPersonRank2()->toArray(), 'total')) }};
                }
            }">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-user-tie"></i>
                        <span>by Salesperson</span>
                        <span class="total-count" x-text="'| Project Count (' + totalCount + ')'"></span>
                    </div>
                    <div class="toggle-container">
                        <button
                            class="toggle-button"
                            :class="{ 'active': rankView === 'rank1' }"
                            @click="rankView = 'rank1'">
                            Rank 1
                        </button>
                        <button
                            class="toggle-button"
                            :class="{ 'active': rankView === 'rank2' }"
                            @click="rankView = 'rank2'">
                            Rank 2
                        </button>
                    </div>
                </div>
                <div class="salesperson-metrics" x-show="rankView === 'rank1'">
                    @php
                        $rank1Data = $this->getHandoversBySalesPersonRank1();
                        $totalHandovers = max(array_sum(array_column($rank1Data->toArray(), 'total')), 1);
                        $colors = ['#3b82f6', '#06b6d4', '#10b981', '#f97316', '#8b5cf6']; // Added an extra color for Others
                    @endphp

                    @foreach($rank1Data as $index => $person)
                        @php
                            $percentage = round(($person->total / $totalHandovers) * 100, 1);
                        @endphp
                        <div class="salesperson-metric">
                            <div class="metric-chart">
                                <div class="cursor-pointer circular-progress"
                                    style="--percentage: {{ $percentage }}; --color: {{ $colors[$index % 5] }};"
                                    wire:click="openSalespersonHandoversSlideOver('{{ $person->salesperson }}')">
                                    <div class="inner">
                                        <span class="value">{{ $person->total }}</span>
                                    </div>
                                    <div class="circle-tooltip">{{ $percentage }}%</div>
                                </div>
                            </div>
                            <div class="metric-info">
                                <div class="salesperson-name">{{ $person->salesperson }}</div>
                                <div class="salesperson-rank">
                                    @if($person->salesperson === 'Others')
                                        Others
                                    @else
                                        Top {{ $index + 1 }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="salesperson-metrics" x-show="rankView === 'rank2'" x-cloak>
                    @php
                        $rank2Data = $this->getHandoversBySalesPersonRank2();
                        $totalHandovers = max(array_sum(array_column($rank2Data->toArray(), 'total')), 1);
                        $colors = ['#3b82f6', '#06b6d4', '#10b981', '#f97316'];
                    @endphp

                    @foreach($rank2Data as $index => $person)
                        @php
                            $percentage = round(($person->total / $totalHandovers) * 100, 1);
                        @endphp
                        <div class="salesperson-metric">
                            <div class="metric-chart">
                                <div class="cursor-pointer circular-progress"
                                    style="--percentage: {{ $percentage }}; --color: {{ $colors[$index % 4] }};"
                                    wire:click="openSalespersonHandoversSlideOver('{{ $person->salesperson }}')">
                                    <div class="inner">
                                        <span class="value">{{ $person->total }}</span>
                                    </div>
                                    <div class="circle-tooltip">{{ $percentage }}%</div>
                                </div>
                            </div>
                            <div class="metric-info">
                                <div class="salesperson-name">{{ $person->salesperson }}</div>
                                <div class="salesperson-rank">
                                    Top {{ $index + 1 }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Section 3: Status Distribution -->
            <div class="mini-chart-card" style="flex: 1;">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-chart-pie"></i>
                        <span>by Project Status</span>
                        @php
                            $statusData = $this->getHandoversByStatus();
                            $totalStatus = array_sum($statusData);
                        @endphp
                        <span class="total-count">| Project Count ({{ $totalStatus }})</span>
                    </div>
                </div>
                <div class="horizontal-bar-container">
                    @php
                        $statusData = $this->getHandoversByStatus();
                        $maxStatus = max($statusData);
                        $totalStatus = array_sum($statusData);
                    @endphp

                    <div class="horizontal-bar-item">
                        <span class="bar-label">Open</span>
                        <div class="bar-track">
                            <div class="bar-fill bar-fill-open" style="width: {{ ($statusData['open'] / $maxStatus) * 100 }}%">
                            </div>
                        </div>
                        <span class="bar-value">{{ $statusData['open'] }} ({{ round(($statusData['open'] / $totalStatus) * 100, 1) }}% )</span>
                    </div>

                    <div class="horizontal-bar-item">
                        <span class="bar-label">Delay</span>
                        <div class="bar-track">
                            <div class="bar-fill bar-fill-delay" style="width: {{ ($statusData['delay'] / $maxStatus) * 100 }}%">
                            </div>
                        </div>
                        <span class="bar-value">{{ $statusData['delay'] }} ({{ round(($statusData['delay'] / $totalStatus) * 100, 1) }}%)</span>
                    </div>

                    <div class="horizontal-bar-item">
                        <span class="bar-label">Inactive</span>
                        <div class="bar-track">
                            <div class="bar-fill bar-fill-inactive" style="width: {{ ($statusData['inactive'] / $maxStatus) * 100 }}%">
                            </div>
                        </div>
                        <span class="bar-value">{{ $statusData['inactive'] }} ({{ round(($statusData['inactive'] / $totalStatus) * 100, 1) }}%)</span>
                    </div>

                    <div class="horizontal-bar-item">
                        <span class="bar-label">Closed</span>
                        <div class="bar-track">
                            <div class="bar-fill bar-fill-closed" style="width: {{ ($statusData['closed'] / $maxStatus) * 100 }}%">
                            </div>
                        </div>
                        <span class="bar-value">{{ $statusData['closed'] }} ({{ round(($statusData['closed'] / $totalStatus) * 100, 1) }}%)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 4: Company Size and Section 5: Modules -->
        <div class="mini-charts-container">
            <!-- Section 4: Company Size -->
            <div class="mini-chart-card" style='flex: 1;'>
                <div class="card-header">
                    <div class="card-title" style="padding-bottom: 10px;">
                        <i class="fas fa-building"></i>
                        <span>by Company Size</span>
                        @php
                            $sizeData = $this->getHandoversByCompanySize();
                            $totalSize = array_sum($sizeData);
                        @endphp
                        <span class="total-count">| Project Count ({{ $totalSize }})</span>
                    </div>
                </div>
                <div class="horizontal-bar-container">
                    @php
                        $sizeData = $this->getHandoversByCompanySize();
                        $maxSize = max($sizeData);
                    @endphp

                    <div class="horizontal-bar-item">
                        <span class="bar-label">Small</span>
                        <div class="bar-track">
                            <div class="bar-fill bar-fill-small" style="width: {{ ($sizeData['Small'] / $maxSize) * 100 }}%"></div>
                        </div>
                        <span class="bar-value">{{ $sizeData['Small'] }} ({{ round(($sizeData['Small'] / $totalSize) * 100, 1) }}%)</span>
                    </div>

                    <div class="horizontal-bar-item">
                        <span class="bar-label">Medium</span>
                        <div class="bar-track">
                            <div class="bar-fill bar-fill-medium" style="width: {{ ($sizeData['Medium'] / $maxSize) * 100 }}%"></div>
                        </div>
                        <span class="bar-value">{{ $sizeData['Medium'] }} ({{ round(($sizeData['Medium'] / $totalSize) * 100, 1) }}%)</span>
                    </div>

                    <div class="horizontal-bar-item">
                        <span class="bar-label">Large</span>
                        <div class="bar-track">
                            <div class="bar-fill bar-fill-large" style="width: {{ ($sizeData['Large'] / $maxSize) * 100 }}%"></div>
                        </div>
                        <span class="bar-value">{{ $sizeData['Large'] }} ({{ round(($sizeData['Large'] / $totalSize) * 100, 1) }}%)</span>
                    </div>

                    <div class="horizontal-bar-item">
                        <span class="bar-label">Enterprise</span>
                        <div class="bar-track">
                            <div class="bar-fill bar-fill-enterprise" style="width: {{ ($sizeData['Enterprise'] / $maxSize) * 100 }}%"></div>
                        </div>
                        <span class="bar-value">{{ $sizeData['Enterprise'] }} ({{ round(($sizeData['Enterprise'] / $totalSize) * 100, 1) }}%)</span>
                    </div>
                </div>
            </div>

            <div class="mini-chart-card" style="flex: 2;">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-chart-line"></i>
                        <span>by TimeTec Module</span>
                        <span class="total-count">| Project Count ({{ $totalStatus }})</span>
                    </div>
                    <div class="module-legend">
                        <div class="legend-item">
                            <div class="legend-color ta-color"></div>
                            <span>TimeTec Attendance</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color tl-color"></div>
                            <span>TimeTec Leave</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color tc-color"></div>
                            <span>TimeTec Claim</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color tp-color"></div>
                            <span>TimeTec Payroll</span>
                        </div>
                    </div>
                </div>

                <div class="module-container">
                    <div class="module-chart">
                        @php
                            $moduleData = $this->getModulesByQuarter();
                            $maxValue = 0;

                            // Find max value for scaling
                            foreach ($moduleData as $item) {
                                $maxValue = max(
                                    $maxValue,
                                    $item['ta'] ?? 0,
                                    $item['tl'] ?? 0,
                                    $item['tc'] ?? 0,
                                    $item['tp'] ?? 0
                                );
                            }
                            // Set a minimum max value to prevent division by zero
                            $maxValue = max(10, ceil($maxValue * 1.1)); // Add 10% padding
                        @endphp

                        <!-- Grid lines -->
                        <div class="grid-lines">
                            @for ($i = 0; $i < 5; $i++)
                                <div class="grid-line"></div>
                                <div class="value-indicator" style="top: {{ $i * 25 }}%">
                                    {{ ceil($maxValue * (1 - $i/4)) }}
                                </div>
                            @endfor
                        </div>

                        <!-- Quarter labels -->
                        @foreach ($moduleData as $index => $item)
                            @php
                                $x = ($index / max(1, count($moduleData) - 1)) * 100;
                            @endphp
                            <div class="quarter-label" style="left: {{ $x }}%">{{ $item['quarter'] }}</div>
                        @endforeach

                        <!-- SVG for curved lines -->
                        <svg class="chart-svg" viewBox="0 0 100 100" preserveAspectRatio="none">
                            <!-- TimeTec Attendance path -->
                            <path class="svg-line ta-color" d="
                                @php
                                    $points = [];
                                    foreach ($moduleData as $index => $item) {
                                        $x = ($index / max(1, count($moduleData) - 1)) * 100;
                                        $y = 100 - ((($item['ta'] ?? 0) / $maxValue) * 100);
                                        $y = max(0, min(100, $y));
                                        $points[] = ['x' => $x, 'y' => $y];
                                    }

                                    // Generate SVG path with curved lines
                                    $path = '';
                                    foreach ($points as $index => $point) {
                                        if ($index === 0) {
                                            $path .= "M{$point['x']} {$point['y']}";
                                        } else {
                                            $prevPoint = $points[$index - 1];
                                            // Calculate control points for smooth curve
                                            $cpx1 = $prevPoint['x'] + ($point['x'] - $prevPoint['x']) / 2;
                                            $cpy1 = $prevPoint['y'];
                                            $cpx2 = $prevPoint['x'] + ($point['x'] - $prevPoint['x']) / 2;
                                            $cpy2 = $point['y'];

                                            $path .= " C{$cpx1} {$cpy1}, {$cpx2} {$cpy2}, {$point['x']} {$point['y']}";
                                        }
                                    }
                                    echo $path;
                                @endphp
                            "/>

                            <!-- TimeTec Leave path -->
                            <path class="svg-line tl-color" d="
                                @php
                                    $points = [];
                                    foreach ($moduleData as $index => $item) {
                                        $x = ($index / max(1, count($moduleData) - 1)) * 100;
                                        $y = 100 - ((($item['tl'] ?? 0) / $maxValue) * 100);
                                        $y = max(0, min(100, $y));
                                        $points[] = ['x' => $x, 'y' => $y];
                                    }

                                    // Generate SVG path with curved lines
                                    $path = '';
                                    foreach ($points as $index => $point) {
                                        if ($index === 0) {
                                            $path .= "M{$point['x']} {$point['y']}";
                                        } else {
                                            $prevPoint = $points[$index - 1];
                                            // Calculate control points for smooth curve
                                            $cpx1 = $prevPoint['x'] + ($point['x'] - $prevPoint['x']) / 2;
                                            $cpy1 = $prevPoint['y'];
                                            $cpx2 = $prevPoint['x'] + ($point['x'] - $prevPoint['x']) / 2;
                                            $cpy2 = $point['y'];

                                            $path .= " C{$cpx1} {$cpy1}, {$cpx2} {$cpy2}, {$point['x']} {$point['y']}";
                                        }
                                    }
                                    echo $path;
                                @endphp
                            "/>

                            <!-- TimeTec Claim path -->
                            <path class="svg-line tc-color" d="
                                @php
                                    $points = [];
                                    foreach ($moduleData as $index => $item) {
                                        $x = ($index / max(1, count($moduleData) - 1)) * 100;
                                        $y = 100 - ((($item['tc'] ?? 0) / $maxValue) * 100);
                                        $y = max(0, min(100, $y));
                                        $points[] = ['x' => $x, 'y' => $y];
                                    }

                                    // Generate SVG path with curved lines
                                    $path = '';
                                    foreach ($points as $index => $point) {
                                        if ($index === 0) {
                                            $path .= "M{$point['x']} {$point['y']}";
                                        } else {
                                            $prevPoint = $points[$index - 1];
                                            // Calculate control points for smooth curve
                                            $cpx1 = $prevPoint['x'] + ($point['x'] - $prevPoint['x']) / 2;
                                            $cpy1 = $prevPoint['y'];
                                            $cpx2 = $prevPoint['x'] + ($point['x'] - $prevPoint['x']) / 2;
                                            $cpy2 = $point['y'];

                                            $path .= " C{$cpx1} {$cpy1}, {$cpx2} {$cpy2}, {$point['x']} {$point['y']}";
                                        }
                                    }
                                    echo $path;
                                @endphp
                            "/>

                            <!-- TimeTec Payroll path -->
                            <path class="svg-line tp-color" d="
                                @php
                                    $points = [];
                                    foreach ($moduleData as $index => $item) {
                                        $x = ($index / max(1, count($moduleData) - 1)) * 100;
                                        $y = 100 - ((($item['tp'] ?? 0) / $maxValue) * 100);
                                        $y = max(0, min(100, $y));
                                        $points[] = ['x' => $x, 'y' => $y];
                                    }

                                    // Generate SVG path with curved lines
                                    $path = '';
                                    foreach ($points as $index => $point) {
                                        if ($index === 0) {
                                            $path .= "M{$point['x']} {$point['y']}";
                                        } else {
                                            $prevPoint = $points[$index - 1];
                                            // Calculate control points for smooth curve
                                            $cpx1 = $prevPoint['x'] + ($point['x'] - $prevPoint['x']) / 2;
                                            $cpy1 = $prevPoint['y'];
                                            $cpx2 = $prevPoint['x'] + ($point['x'] - $prevPoint['x']) / 2;
                                            $cpy2 = $point['y'];

                                            $path .= " C{$cpx1} {$cpy1}, {$cpx2} {$cpy2}, {$point['x']} {$point['y']}";
                                        }
                                    }
                                    echo $path;
                                @endphp
                            "/>
                        </svg>

                        <!-- Data points -->
                        <div class="data-points">
                            <!-- TimeTec Attendance points -->
                            @foreach ($moduleData as $index => $item)
                                @php
                                    $x = ($index / max(1, count($moduleData) - 1)) * 100;
                                    $y = 100 - ((($item['ta'] ?? 0) / $maxValue) * 100);
                                    $y = max(0, min(100, $y));
                                @endphp
                                <div class="line-point ta-color" style="left: {{ $x }}%; top: {{ $y }}%"></div>
                                <div class="point-tooltip" style="left: {{ $x }}%; top: {{ $y }}%">
                                    {{ $item['quarter'] }}: {{ $item['ta'] ?? 0 }}
                                </div>
                            @endforeach

                            <!-- TimeTec Leave points -->
                            @foreach ($moduleData as $index => $item)
                                @php
                                    $x = ($index / max(1, count($moduleData) - 1)) * 100;
                                    $y = 100 - ((($item['tl'] ?? 0) / $maxValue) * 100);
                                    $y = max(0, min(100, $y));
                                @endphp
                                <div class="line-point tl-color" style="left: {{ $x }}%; top: {{ $y }}%"></div>
                                <div class="point-tooltip" style="left: {{ $x }}%; top: {{ $y }}%">
                                    {{ $item['quarter'] }}: {{ $item['tl'] ?? 0 }}
                                </div>
                            @endforeach

                            <!-- TimeTec Claim points -->
                            @foreach ($moduleData as $index => $item)
                                @php
                                    $x = ($index / max(1, count($moduleData) - 1)) * 100;
                                    $y = 100 - ((($item['tc'] ?? 0) / $maxValue) * 100);
                                    $y = max(0, min(100, $y));
                                @endphp
                                <div class="line-point tc-color" style="left: {{ $x }}%; top: {{ $y }}%"></div>
                                <div class="point-tooltip" style="left: {{ $x }}%; top: {{ $y }}%">
                                    {{ $item['quarter'] }}: {{ $item['tc'] ?? 0 }}
                                </div>
                            @endforeach

                            <!-- TimeTec Payroll points -->
                            @foreach ($moduleData as $index => $item)
                                @php
                                    $x = ($index / max(1, count($moduleData) - 1)) * 100;
                                    $y = 100 - ((($item['tp'] ?? 0) / $maxValue) * 100);
                                    $y = max(0, min(100, $y));
                                @endphp
                                <div class="line-point tp-color" style="left: {{ $x }}%; top: {{ $y }}%"></div>
                                <div class="point-tooltip" style="left: {{ $x }}%; top: {{ $y }}%">
                                    {{ $item['quarter'] }}: {{ $item['tp'] ?? 0 }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div
        x-data="{ open: @entangle('showSlideOver'), expandedGroups: {} }"
        x-show="open"
        @keydown.window.escape="open = false"
        class="fixed inset-0 z-[200] flex justify-end bg-black/40 backdrop-blur-sm transition-opacity duration-200"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div
            class="w-full h-full max-w-md overflow-hidden slide-over-modal"
            @click.away="open = false"
        >
            <!-- Header -->
            <div class="slide-over-header">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-800">{{ $slideOverTitle }}</h2>
                    <button @click="open = false" class="p-1 text-2xl leading-none text-gray-500 hover:text-gray-700">&times;</button>
                </div>
            </div>

            <!-- Scrollable content -->
            <div class="slide-over-content">
                @if ($handoversList instanceof \Illuminate\Support\Collection && $handoversList->isEmpty())
                    <div class="empty-state">
                        <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 14h.01M20 4v7a4 4 0 01-4 4H8a4 4 0 01-4-4V4m0 0h16M4 4v2m16-2v2" />
                        </svg>
                        <p>No data found for this selection.</p>
                    </div>
                @elseif ($handoversList instanceof \Illuminate\Support\Collection && $handoversList->first() instanceof \Illuminate\Support\Collection)
                    <!-- Grouped display -->
                    @foreach ($handoversList as $companySize => $handovers)
                        <div class="mb-4">
                            <!-- Group header -->
                            <div
                                class="group-header"
                                x-on:click="expandedGroups['{{ $companySize }}'] = !expandedGroups['{{ $companySize }}']"
                            >
                                <div class="flex items-center">
                                    <span class="group-badge">{{ $handovers->count() }}</span>
                                    <span>{{ $companySize }}</span>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 transition-transform"
                                    :class="expandedGroups['{{ $companySize }}'] ? 'transform rotate-180' : ''"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>

                            <!-- Group content (collapsible) -->
                            <div class="group-content" x-show="expandedGroups['{{ $companySize }}']" x-collapse>
                                @foreach ($handovers as $handover)
                                    @php
                                        try {
                                            // Use company_name directly from SoftwareHandover model
                                            $companyName = $handover->company_name ?? 'N/A';
                                            $shortened = strtoupper(\Illuminate\Support\Str::limit($companyName, 30, '...'));
                                            $encryptedId = \App\Classes\Encryptor::encrypt($handover->id);
                                        } catch (\Exception $e) {
                                            $shortened = 'Error loading company';
                                            $encryptedId = '#';
                                            $companyName = 'Error: ' . $e->getMessage();
                                        }
                                    @endphp

                                    <div class="company-item">
                                        {{ $shortened }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @else
                    <!-- Regular flat list -->
                    @forelse ($handoversList as $handover)
                        @php
                            try {
                                // Use company_name directly from SoftwareHandover model
                                $companyName = $handover->company_name ?? 'N/A';
                                $shortened = strtoupper(\Illuminate\Support\Str::limit($companyName, 30, '...'));
                                $encryptedId = \App\Classes\Encryptor::encrypt($handover->id);
                            } catch (\Exception $e) {
                                $shortened = 'Error loading company';
                                $encryptedId = '#';
                                $companyName = 'Error: ' . $e->getMessage();
                            }
                        @endphp

                        <div class="company-item">
                            {{ $shortened }}
                        </div>
                    @empty
                        <div class="empty-state">
                            <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 14h.01M20 4v7a4 4 0 01-4 4H8a4 4 0 01-4-4V4m0 0h16M4 4v2m16-2v2" />
                            </svg>
                            <p>No company data available.</p>
                        </div>
                    @endforelse
                @endif
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Wait a moment before rendering charts to ensure containers are fully loaded
            setTimeout(() => {
                // Console log to check if data is coming through
                console.log('Quarterly Module Data:', @json($this->getModulesByQuarter()));

                // MODULE QUARTERLY CHART (SECTION 5)
                const quarterlyModuleData = @json($this->getModulesByQuarter());
                const quarters = quarterlyModuleData.map(item => item.quarter);

                const moduleQuarterlyChart = document.getElementById('moduleQuarterlyChart');
                if (moduleQuarterlyChart) {
                    const moduleChart = new Chart(moduleQuarterlyChart, {
                        type: 'line',
                        data: {
                            labels: quarters,
                            datasets: [
                                {
                                    label: 'TimeTec Attendance',
                                    data: quarterlyModuleData.map(item => item.ta),
                                    borderColor: '#8b5cf6', // Purple
                                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                                    borderWidth: 3,
                                    tension: 0.4,
                                    pointRadius: 4,
                                    pointBackgroundColor: '#8b5cf6'
                                },
                                {
                                    label: 'TimeTec Leave',
                                    data: quarterlyModuleData.map(item => item.tl),
                                    borderColor: '#ef4444', // Red
                                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                    borderWidth: 3,
                                    tension: 0.4,
                                    pointRadius: 4,
                                    pointBackgroundColor: '#ef4444'
                                },
                                {
                                    label: 'TimeTec Claim',
                                    data: quarterlyModuleData.map(item => item.tc),
                                    borderColor: '#10b981', // Green
                                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                    borderWidth: 3,
                                    tension: 0.4,
                                    pointRadius: 4,
                                    pointBackgroundColor: '#10b981'
                                },
                                {
                                    label: 'TimeTec Payroll',
                                    data: quarterlyModuleData.map(item => item.tp),
                                    borderColor: '#3b82f6', // Blue
                                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                    borderWidth: 3,
                                    tension: 0.4,
                                    pointRadius: 4,
                                    pointBackgroundColor: '#3b82f6'
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                x: {
                                    grid: {
                                        display: false
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: '#f3f4f6'
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        boxWidth: 12,
                                        usePointStyle: true,
                                        pointStyle: 'circle',
                                    }
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false
                                }
                            }
                        }
                    });

                    // Handle window resize events to ensure chart stays properly sized
                    window.addEventListener('resize', function() {
                        // Use setTimeout to debounce the resize event
                        clearTimeout(window.resizedFinished);
                        window.resizedFinished = setTimeout(function() {
                            moduleChart.resize();
                        }, 250);
                    });

                    // Trigger initial resize to ensure proper display
                    setTimeout(() => {
                        moduleChart.resize();
                    }, 100);
                } else {
                    console.error('moduleQuarterlyChart container not found');
                }

                // SALESPERSON CHART
                const salespersonChart = document.getElementById('salespersonChart');
                if (salespersonChart) {
                    const salespersonData = @json($this->getHandoversBySalesPerson());
                    const salespersonNames = salespersonData.map(item => item.salesperson);
                    const salespersonCounts = salespersonData.map(item => item.total);
                    const colorsList = ['#3b82f6', '#06b6d4', '#10b981', '#f97316'];

                    const personChart = new Chart(salespersonChart, {
                        type: 'doughnut',
                        data: {
                            labels: salespersonNames,
                            datasets: [
                                {
                                    data: salespersonCounts,
                                    backgroundColor: colorsList,
                                    borderColor: '#ffffff',
                                    borderWidth: 2,
                                    hoverOffset: 10
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '60%',
                            plugins: {
                                legend: {
                                    position: 'right',
                                    labels: {
                                        boxWidth: 12,
                                        usePointStyle: true,
                                        pointStyle: 'circle',
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.raw;
                                            return `${label}: ${value}`;
                                        }
                                    }
                                }
                            }
                        }
                    });

                    // Handle resize for this chart as well
                    window.addEventListener('resize', function() {
                        clearTimeout(window.personChartResized);
                        window.personChartResized = setTimeout(function() {
                            personChart.resize();
                        }, 250);
                    });
                }

                // Handle Alpine.js interactions with charts
                document.addEventListener('alpine:initialized', () => {
                    // When Alpine components initialize, check if we need to refresh charts
                    setTimeout(() => {
                        if (moduleChart) moduleChart.resize();
                        if (personChart) personChart.resize();
                    }, 100);
                });

                // Handle tab visibility changes
                document.addEventListener('visibilitychange', function() {
                    if (document.visibilityState === 'visible') {
                        // User returned to the tab, resize charts
                        setTimeout(() => {
                            if (moduleChart) moduleChart.resize();
                            if (personChart) personChart.resize();
                        }, 100);
                    }
                });

                // Fix chart display on page load complete
                window.addEventListener('load', function() {
                    setTimeout(() => {
                        if (moduleChart) moduleChart.resize();
                        if (personChart) personChart.resize();
                    }, 100);
                });
            }, 200); // Small delay to ensure DOM is ready
        });
    </script>
</x-filament-panels::page>
