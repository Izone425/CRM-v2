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
            background-color: #3b82f6;
        }

        .bar-fill-delay {
            background-color: #f97316;
        }

        .bar-fill-inactive {
            background-color: #6a7282;
        }

        .bar-fill-closed {
            background-color: #10b981;
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
            background-color: #84cc16; /* Green */
            border-radius: 3px 3px 0 0;
        }

        .closed-projects-bar {
            position: absolute;
            bottom: 0;
            background-color: #eab308; /* Amber/Yellow */
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
            top: -120px;
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
        .new-color { background-color: #84cc16; }
        .closed-color { background-color: #eab308; }

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
            min-width: 100px;
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

        .mini-charts-container {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
        }

        .stat-card2 {
            display: flex;
            align-items: center;
            flex: 1;
            min-width: 200px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 20px;
            transition: all 0.2s ease;
        }

        .stat-card2:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            overflow: visible;
        }

        .stat-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            margin-right: 16px;
            font-size: 20px;
            flex-shrink: 0;
        }

        .stat-content {
            flex: 1;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            line-height: 1.2;
        }

        .stat-label {
            margin: 4px 0;
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
        }

        .stat-trend {
            display: block;
            font-size: 12px;
            font-weight: 500;
            margin-top: 4px;
        }

        .stat-trend-up {
            color: #10b981;
        }

        .stat-trend-down {
            color: #ef4444;
        }

        /* Module-specific styling */
        .ta-card .stat-icon {
            background-color: rgba(250,90,124,255);
            color: white;
        }

        .tl-card .stat-icon {
            background-color: rgba(254,148,122,255);
            color: white;
        }

        .tc-card .stat-icon {
            background-color: rgba(59,215,85,255);
            color: white;
        }

        .tp-card .stat-icon {
            background-color: rgba(191,131,255,255);
            color: white;
        }

        .module-tooltip {
            position: absolute;
            top: 0;
            right: 75px;
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s, visibility 0.2s;
            z-index: 10;
            pointer-events: none;
            transform: translate(-5px, -120%);
        }

        .stat-card2:hover .module-tooltip {
            opacity: 1;
            visibility: visible;
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
                @php
                    $totalCount = $this->getAllSalespersonHandovers();
                    $salespersonData = $this->getHandoversBySalesPerson();
                @endphp
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-user-tie"></i>
                        <span>by Salesperson</span>
                        <span class="total-count">| Project Count ({{ $totalCount }})</span>
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
                        $totalAllHandovers = $this->getAllSalespersonHandovers();
                        $colors = ['#3b82f6', '#06b6d4', '#10b981', '#84cc16', '#8b5cf6']; // Added an extra color for Others
                    @endphp

                    @foreach($rank1Data as $index => $person)
                        @php
                            // Safely calculate percentage with fallback to zero if division by zero would occur
                            $percentage = $totalAllHandovers > 0 ? round(($person->total / $totalAllHandovers) * 100, 1) : 0;
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
                                        Top 1
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
                        $totalAllHandovers = $this->getAllSalespersonHandovers();
                        $colors = ['#f59e0b', '#ef4444', '#ec4899', '#c026d3'];
                    @endphp

                    @foreach($rank2Data as $index => $person)
                        @php
                            // Safely calculate percentage with fallback to zero if division by zero would occur
                            $percentage = $totalAllHandovers > 0 ? round(($person->total / $totalAllHandovers) * 100, 1) : 0;
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
                    <div class="card-title">
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
            <!-- Section 5: Modules -->
            <div class="mini-charts-container" style="flex: 2;">
                @php
                    $moduleData = $this->getHandoversByModule();
                    $totalModules = array_sum($moduleData);

                    // Get yesterday and today data (these should be methods in your component)
                    $yesterdayData = $this->getYesterdayHandoversByModule() ?? [
                        'ta' => 0,
                        'tl' => 0,
                        'tc' => 0,
                        'tp' => 0
                    ];

                    $todayData = $this->getTodayHandoversByModule() ?? [
                        'ta' => 0,
                        'tl' => 0,
                        'tc' => 0,
                        'tp' => 0
                    ];
                @endphp

                <!-- TimeTec Attendance Card -->
                <div class="stat-card2 ta-card" style="background-color: rgba(255,226,230,255); position: relative;">
                    <div class="stat-content">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <br>
                        <h3 class="stat-value" title="TimeTec Attendance">{{ $moduleData['ta'] }}</h3>
                        <p class="stat-label">TimeTec Attendance</p>
                        @php
                            $taChange = $todayData['ta'] - $yesterdayData['ta'];
                            $taDirection = $taChange >= 0 ? 'up' : 'down';
                            $taChangeAbs = abs($taChange);
                            $taPercentage = $totalModules > 0 ? round(($moduleData['ta'] / $this->getAllSalespersonHandovers()) * 100, 1) : 0;
                        @endphp
                        <span class="stat-trend stat-trend-{{ $taDirection }}">
                            <i class="fas fa-arrow-{{ $taDirection }}"></i> {{ $taChangeAbs }} from yesterday
                        </span>
                        <div class="module-tooltip">{{ $taPercentage }}%</div>
                    </div>
                </div>

                <!-- TimeTec Leave Card -->
                <div class="stat-card2 tl-card" style="background-color: rgba(255,244,222,255); position: relative;">
                    <div class="stat-content">
                        <div class="stat-icon">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <br>
                        <h3 class="stat-value" title="TimeTec Leave">{{ $moduleData['tl'] }}</h3>
                        <p class="stat-label">TimeTec Leave</p>
                        @php
                            $tlChange = $todayData['tl'] - $yesterdayData['tl'];
                            $tlDirection = $tlChange >= 0 ? 'up' : 'down';
                            $tlChangeAbs = abs($tlChange);
                            $tlPercentage = $totalModules > 0 ? round(($moduleData['tl'] / $this->getAllSalespersonHandovers()) * 100, 1) : 0;
                        @endphp
                        <span class="stat-trend stat-trend-{{ $tlDirection }}">
                            <i class="fas fa-arrow-{{ $tlDirection }}"></i> {{ $tlChangeAbs }} from yesterday
                        </span>
                        <div class="module-tooltip">{{ $tlPercentage }}%</div>
                    </div>
                </div>

                <!-- TimeTec Claim Card -->
                <div class="stat-card2 tc-card" style="background-color: rgba(220,252,231,255); position: relative;">
                    <div class="stat-content">
                        <div class="stat-icon">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <br>
                        <h3 class="stat-value" title="TimeTec Claim">{{ $moduleData['tc'] }}</h3>
                        <p class="stat-label">TimeTec Claim</p>
                        @php
                            $tcChange = $todayData['tc'] - $yesterdayData['tc'];
                            $tcDirection = $tcChange >= 0 ? 'up' : 'down';
                            $tcChangeAbs = abs($tcChange);
                            $tcPercentage = $totalModules > 0 ? round(($moduleData['tc'] / $this->getAllSalespersonHandovers()) * 100, 1) : 0;
                        @endphp
                        <span class="stat-trend stat-trend-{{ $tcDirection }}">
                            <i class="fas fa-arrow-{{ $tcDirection }}"></i> {{ $tcChangeAbs }} from yesterday
                        </span>
                        <div class="module-tooltip">{{ $tcPercentage }}%</div>
                    </div>
                </div>

                <!-- TimeTec Payroll Card -->
                <div class="stat-card2 tp-card" style="background-color: rgba(244,232,255,255); position: relative;">
                    <div class="stat-content">
                        <div class="stat-icon">
                            <i class="fas fa-money-check-alt"></i>
                        </div>
                        <br>
                        <h3 class="stat-value" title="TimeTec Payroll">{{ $moduleData['tp'] }}</h3>
                        <p class="stat-label">TimeTec Payroll</p>
                        @php
                            $tpChange = $todayData['tp'] - $yesterdayData['tp'];
                            $tpDirection = $tpChange >= 0 ? 'up' : 'down';
                            $tpChangeAbs = abs($tpChange);
                            $tpPercentage = $totalModules > 0 ? round(($moduleData['tp'] / $this->getAllSalespersonHandovers()) * 100, 1) : 0;
                        @endphp
                        <span class="stat-trend stat-trend-{{ $tpDirection }}">
                            <i class="fas fa-arrow-{{ $tpDirection }}"></i> {{ $tpChangeAbs }} from yesterday
                        </span>
                        <div class="module-tooltip">{{ $tpPercentage }}%</div>
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
