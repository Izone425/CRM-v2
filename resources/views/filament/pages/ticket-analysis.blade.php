<x-filament::page>
    <head>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <style>
            .wrapper-container {
                background-color: white;
                border-radius: 10px;
                padding: 20px;
                box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
                width: 100%;
                margin-bottom: 20px;
            }

            .chart-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
            }

            .chart-grid .full-width-chart {
                grid-column: span 2;
            }

            @media (max-width: 768px) {
                .chart-grid {
                    grid-template-columns: 1fr;
                }
                .chart-grid .full-width-chart {
                    grid-column: span 1;
                }
            }

            .chart-container {
                background: white;
                border-radius: 10px;
                padding: 20px;
                box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            }

            .chart-title {
                font-size: 1rem;
                font-weight: 600;
                color: #374151;
                margin-bottom: 16px;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            /* Donut Chart Styles */
            .donut-chart-wrapper {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 20px;
                width: 100%;
                padding: 10px;
            }

            .donut-chart {
                position: relative;
                width: 180px;
                height: 180px;
                flex-shrink: 0;
            }

            .donut-legend {
                display: flex;
                flex-direction: column;
                gap: 8px;
                flex: 1;
                max-height: 220px;
                overflow-y: auto;
            }

            .legend-item {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 6px 10px;
                border-radius: 6px;
                cursor: pointer;
                transition: background-color 0.2s;
            }

            .legend-item:hover {
                background-color: #F3F4F6;
            }

            .legend-color {
                width: 12px;
                height: 12px;
                border-radius: 3px;
            }

            .legend-text {
                font-size: 0.875rem;
                color: #374151;
                flex: 1;
            }

            .legend-count {
                font-weight: 600;
                color: #1F2937;
                margin-left: auto;
            }

            .legend-count {
                font-weight: 600;
                margin-left: auto;
            }

            /* Bar Chart Styles */
            .bar-chart {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }

            .bar-item {
                cursor: pointer;
                transition: transform 0.2s;
            }

            .bar-item:hover {
                transform: translateX(4px);
            }

            .bar-label {
                display: flex;
                justify-content: space-between;
                font-size: 0.875rem;
                color: #374151;
                margin-bottom: 4px;
            }

            .bar-wrapper {
                height: 24px;
                background: #E5E7EB;
                border-radius: 6px;
                overflow: hidden;
            }

            .bar-fill {
                height: 100%;
                border-radius: 6px;
                transition: width 0.5s ease-in-out;
            }

            /* Stacked Bar Styles */
            .bar-item-stacked {
                margin-bottom: 12px;
            }

            .stacked-bar {
                display: flex;
                cursor: pointer;
            }

            .bar-segment {
                height: 100%;
                transition: all 0.3s ease;
            }

            .bar-segment:first-child {
                border-radius: 6px 0 0 6px;
            }

            .bar-segment:last-child {
                border-radius: 0 6px 6px 0;
            }

            .bar-segment:only-child {
                border-radius: 6px;
            }

            .bar-segment:hover {
                opacity: 0.8;
                transform: scaleY(1.1);
            }

            .priority-breakdown {
                margin-top: 8px;
                margin-left: 24px;
                padding: 8px 12px;
                background: #F9FAFB;
                border-radius: 6px;
                font-size: 0.8rem;
            }

            .breakdown-item {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 4px 0;
            }

            .breakdown-color {
                width: 12px;
                height: 12px;
                border-radius: 3px;
                flex-shrink: 0;
            }

            .breakdown-name {
                flex: 1;
                color: #374151;
            }

            .breakdown-count {
                font-weight: 600;
                color: #1F2937;
            }

            .priority-legend {
                font-size: 0.75rem;
            }

            /* Line Chart Styles */
            .line-chart-container {
                width: 100%;
                padding: 20px 0;
            }

            .line-chart-svg {
                width: 100%;
                height: 200px;
            }

            /* Full Width Chart */
            .full-width-chart {
                grid-column: span 2;
            }

            /* Slide Over Styles */
            .slide-over-item {
                display: block;
                padding: 12px 16px;
                margin-bottom: 8px;
                background: #F9FAFB;
                border-radius: 8px;
                border: 1px solid #E5E7EB;
                transition: all 0.2s;
            }

            .slide-over-item:hover {
                background: #EFF6FF;
                border-color: #3B82F6;
            }

            .ticket-id {
                font-weight: 600;
                color: #3B82F6;
            }

            .ticket-title {
                font-size: 0.875rem;
                color: #374151;
                margin-top: 4px;
            }

            .ticket-meta {
                font-size: 0.75rem;
                color: #6B7280;
                margin-top: 4px;
            }

            @media (max-width: 768px) {
                .summary-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
                .chart-grid {
                    grid-template-columns: 1fr;
                }
                .full-width-chart {
                    grid-column: span 1;
                }
            }
        </style>
    </head>

    <!-- Header with Filters -->
    <div class="flex flex-col items-center justify-between mb-6 md:flex-row">
        <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">Ticket Analysis</h1>

        <div class="flex items-center gap-4 mt-4 md:mt-0">
            <!-- All Tickets Toggle -->
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model="showAllTickets" wire:change="applyFilters" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                <span class="text-sm text-gray-600">All Tickets</span>
            </label>

            <!-- Date Range Filters -->
            <div class="flex items-center gap-2 {{ $showAllTickets ? 'opacity-50 pointer-events-none' : '' }}">
                <label class="text-sm text-gray-600">From:</label>
                <input type="date" wire:model="startDate" wire:change="applyFilters" class="border-gray-300 rounded-md shadow-sm text-sm" {{ $showAllTickets ? 'disabled' : '' }}>
            </div>
            <div class="flex items-center gap-2 {{ $showAllTickets ? 'opacity-50 pointer-events-none' : '' }}">
                <label class="text-sm text-gray-600">To:</label>
                <input type="date" wire:model="endDate" wire:change="applyFilters" class="border-gray-300 rounded-md shadow-sm text-sm" {{ $showAllTickets ? 'disabled' : '' }}>
            </div>

            <!-- Product Filter -->
            <select wire:model="selectedProduct" wire:change="applyFilters" class="border-gray-300 rounded-md shadow-sm text-sm">
                <option value="v1">Version 1</option>
                <option value="v2">Version 2</option>
            </select>
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="chart-grid">
        <!-- Priority Distribution (Donut Chart) -->
        <div class="chart-container">
            <div class="chart-title">
                <i class="fa fa-chart-pie text-gray-500"></i>
                <span>By Priority</span>
            </div>

            @if(count($priorityData) > 0)
                <div class="donut-chart-wrapper">
                    <!-- SVG Donut Chart -->
                    <div class="donut-chart">
                        <svg viewBox="0 0 36 36" width="180" height="180">
                            @php
                                $colors = ['#EF4444', '#F59E0B', '#3B82F6', '#10B981', '#8B5CF6', '#EC4899', '#6366F1', '#14B8A6'];
                                $offset = 0;
                                $total = collect($priorityData)->sum('count');
                            @endphp

                            <!-- Background circle -->
                            <circle cx="18" cy="18" r="14" fill="none" stroke="#E5E7EB" stroke-width="5"></circle>

                            @foreach($priorityData as $index => $item)
                                @php
                                    $percentage = $total > 0 ? ($item['count'] / $total) * 100 : 0;
                                    $dashArray = ($percentage * 88) / 100;
                                    $color = $colors[$index % count($colors)];
                                @endphp
                                <circle
                                    cx="18" cy="18" r="14"
                                    fill="none"
                                    stroke="{{ $color }}"
                                    stroke-width="5"
                                    stroke-dasharray="{{ $dashArray }} {{ 88 - $dashArray }}"
                                    stroke-dashoffset="{{ -$offset }}"
                                    transform="rotate(-90 18 18)"
                                    class="cursor-pointer hover:opacity-80"
                                    wire:click="openPrioritySlideOver({{ $item['id'] }})"
                                >
                                    <title>{{ $item['name'] }}: {{ $item['count'] }} ({{ number_format($percentage, 1) }}%)</title>
                                </circle>
                                @php $offset += $dashArray; @endphp
                            @endforeach
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-xl font-bold text-gray-700">{{ $totalTickets }}</span>
                        </div>
                    </div>

                    <!-- Legend -->
                    <div class="donut-legend">
                        @foreach($priorityData as $index => $item)
                            @php
                                $color = $colors[$index % count($colors)];
                                $legendPercentage = $total > 0 ? ($item['count'] / $total) * 100 : 0;
                            @endphp
                            <div class="legend-item" wire:click="openPrioritySlideOver({{ $item['id'] }})" title="{{ $item['name'] }}: {{ $item['count'] }} ({{ number_format($legendPercentage, 1) }}%)">
                                <span class="legend-color" style="background-color: {{ $color }};"></span>
                                <span class="legend-text">{{ \Illuminate\Support\Str::limit($item['name'], 20) }}</span>
                                <span class="legend-count">{{ $item['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="flex items-center justify-center h-48 text-gray-500">
                    No priority data available
                </div>
            @endif
        </div>

        <!-- Module Distribution (Donut Chart) -->
        <div class="chart-container">
            <div class="chart-title">
                <i class="fa fa-chart-pie text-gray-500"></i>
                <span>By Module</span>
            </div>

            @if(count($moduleData) > 0)
                <div class="donut-chart-wrapper">
                    <!-- SVG Donut Chart -->
                    <div class="donut-chart">
                        <svg viewBox="0 0 36 36" width="180" height="180">
                            @php
                                $moduleColors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#6366F1', '#14B8A6', '#F97316', '#06B6D4'];
                                $moduleOffset = 0;
                                $moduleTotal = collect($moduleData)->sum('count');
                            @endphp

                            <!-- Background circle -->
                            <circle cx="18" cy="18" r="14" fill="none" stroke="#E5E7EB" stroke-width="5"></circle>

                            @foreach($moduleData as $index => $item)
                                @php
                                    $modulePercentage = $moduleTotal > 0 ? ($item['count'] / $moduleTotal) * 100 : 0;
                                    $moduleDashArray = ($modulePercentage * 88) / 100;
                                    $moduleColor = $moduleColors[$index % count($moduleColors)];
                                @endphp
                                <circle
                                    cx="18" cy="18" r="14"
                                    fill="none"
                                    stroke="{{ $moduleColor }}"
                                    stroke-width="5"
                                    stroke-dasharray="{{ $moduleDashArray }} {{ 88 - $moduleDashArray }}"
                                    stroke-dashoffset="{{ -$moduleOffset }}"
                                    transform="rotate(-90 18 18)"
                                    class="cursor-pointer hover:opacity-80"
                                    wire:click="openModuleSlideOver({{ $item['id'] }})"
                                >
                                    <title>{{ $item['name'] }}: {{ $item['count'] }} ({{ number_format($modulePercentage, 1) }}%)</title>
                                </circle>
                                @php $moduleOffset += $moduleDashArray; @endphp
                            @endforeach
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-xl font-bold text-gray-700">{{ $moduleTotal }}</span>
                        </div>
                    </div>

                    <!-- Legend -->
                    <div class="donut-legend">
                        @foreach($moduleData as $index => $item)
                            @php
                                $moduleColor = $moduleColors[$index % count($moduleColors)];
                                $moduleLegendPercentage = $moduleTotal > 0 ? ($item['count'] / $moduleTotal) * 100 : 0;
                            @endphp
                            <div class="legend-item" wire:click="openModuleSlideOver({{ $item['id'] }})" title="{{ $item['name'] }}: {{ $item['count'] }} ({{ number_format($moduleLegendPercentage, 1) }}%)">
                                <span class="legend-color" style="background-color: {{ $moduleColor }};"></span>
                                <span class="legend-text">{{ \Illuminate\Support\Str::limit($item['name'], 18) }}</span>
                                <span class="legend-count">{{ $item['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="flex items-center justify-center h-48 text-gray-500">
                    No module data available
                </div>
            @endif
        </div>

        <!-- Priority Distribution (Bar Chart with Module Breakdown) -->
        <div class="chart-container">
            <div class="chart-title">
                <i class="fa fa-chart-bar text-gray-500"></i>
                <span>By Priority</span>
            </div>

            @if(count($priorityModuleData) > 0)
                <div class="bar-chart">
                    @foreach($priorityModuleData as $index => $item)
                        <div class="bar-item-stacked" x-data="{ showBreakdown: false }">
                            <div class="bar-label cursor-pointer" @click="showBreakdown = !showBreakdown">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4 transition-transform" :class="showBreakdown ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                    {{ \Illuminate\Support\Str::limit($item['name'], 25) }}
                                </span>
                                <span class="font-semibold">{{ $item['count'] }}</span>
                            </div>
                            <!-- Stacked Bar -->
                            <div class="bar-wrapper stacked-bar">
                                @if(!empty($item['breakdown']))
                                    @foreach($item['breakdown'] as $module)
                                        <div class="bar-segment"
                                             wire:click="openPriorityBarSlideOver({{ $item['id'] }}, {{ $module['module_id'] }})"
                                             style="width: {{ ($module['count'] / $item['count']) * $item['percentage'] }}%; background-color: {{ $module['color'] }};"
                                             title="{{ $module['name'] }}: {{ $module['count'] }} ({{ $module['percentage'] }}%)">
                                        </div>
                                    @endforeach
                                @else
                                    <div class="bar-fill" wire:click="openPriorityBarSlideOver({{ $item['id'] }})" style="width: {{ $item['percentage'] }}%; background-color: #6B7280;"></div>
                                @endif
                            </div>
                            <!-- Module Breakdown Details (expandable) -->
                            <div x-show="showBreakdown" x-collapse class="priority-breakdown">
                                @if(!empty($item['breakdown']))
                                    @foreach($item['breakdown'] as $module)
                                        <div class="breakdown-item">
                                            <span class="breakdown-color" style="background-color: {{ $module['color'] }};"></span>
                                            <span class="breakdown-name">{{ $module['name'] }}</span>
                                            <span class="breakdown-count">{{ $module['count'] }} ({{ $module['percentage'] }}%)</span>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

            @else
                <div class="flex items-center justify-center h-48 text-gray-500">
                    No priority data available
                </div>
            @endif
        </div>

        {{-- Resolution Time Trend - HIDDEN
        <!-- Resolution Time Trend (Line Chart) - Full Width -->
        <div class="chart-container full-width-chart">
            <div class="chart-title flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa fa-chart-line text-gray-500"></i>
                    <span>Average Resolution Time (Days) - Daily Trend</span>
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <label class="text-gray-600">From:</label>
                    <input type="date" wire:model="trendStartDate" wire:change="loadData" class="border-gray-300 rounded-md shadow-sm text-sm px-2 py-1">
                    <label class="text-gray-600">To:</label>
                    <input type="date" wire:model="trendEndDate" wire:change="loadData" class="border-gray-300 rounded-md shadow-sm text-sm px-2 py-1">
                </div>
            </div>

            @if(count($durationData) > 0)
                <div class="line-chart-container" style="width: 100%; overflow-x: auto;">
                    @php
                        $maxDays = max(array_column($durationData, 'avg_days')) ?: 1;
                        $dataCount = count($durationData);
                        $chartWidth = 1200; // Fixed wide width
                        $chartHeight = 300;
                        $paddingLeft = 50;
                        $paddingRight = 30;
                        $paddingTop = 40;
                        $paddingBottom = 70;
                        $graphWidth = $chartWidth - $paddingLeft - $paddingRight;
                        $graphHeight = $chartHeight - $paddingTop - $paddingBottom;
                    @endphp

                    <svg width="100%" height="{{ $chartHeight }}" viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" preserveAspectRatio="none" style="width: 100%;">
                        <!-- Background -->
                        <rect x="0" y="0" width="{{ $chartWidth }}" height="{{ $chartHeight }}" fill="#FAFAFA"/>

                        <!-- Grid lines -->
                        @for($i = 0; $i <= 4; $i++)
                            @php $y = $paddingTop + ($graphHeight * $i / 4); @endphp
                            <line x1="{{ $paddingLeft }}" y1="{{ $y }}" x2="{{ $chartWidth - $paddingRight }}" y2="{{ $y }}" stroke="#E5E7EB" stroke-width="1"/>
                            <text x="{{ $paddingLeft - 8 }}" y="{{ $y + 4 }}" fill="#6B7280" font-size="11" text-anchor="end">{{ round($maxDays * (4 - $i) / 4, 1) }}</text>
                        @endfor

                        <!-- Line path -->
                        @php
                            $points = [];
                            foreach ($durationData as $index => $item) {
                                $x = $paddingLeft + ($graphWidth * $index / max($dataCount - 1, 1));
                                $y = $paddingTop + $graphHeight - ($graphHeight * ($item['avg_days'] / max($maxDays, 0.1)));
                                $points[] = "$x,$y";
                            }
                            $pathD = 'M ' . implode(' L ', $points);
                        @endphp

                        <!-- Area fill -->
                        <defs>
                            <linearGradient id="areaGradientNew" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#818CF8" stop-opacity="0.4"/>
                                <stop offset="100%" stop-color="#818CF8" stop-opacity="0.05"/>
                            </linearGradient>
                        </defs>
                        <path d="{{ $pathD }} L {{ $paddingLeft + $graphWidth }},{{ $paddingTop + $graphHeight }} L {{ $paddingLeft }},{{ $paddingTop + $graphHeight }} Z" fill="url(#areaGradientNew)"/>

                        <!-- Line -->
                        <path d="{{ $pathD }}" fill="none" stroke="#6366F1" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>

                        <!-- Data points and labels -->
                        @foreach($durationData as $index => $item)
                            @php
                                $x = $paddingLeft + ($graphWidth * $index / max($dataCount - 1, 1));
                                $y = $paddingTop + $graphHeight - ($graphHeight * ($item['avg_days'] / max($maxDays, 0.1)));
                            @endphp
                            <!-- Data point circle -->
                            <circle cx="{{ $x }}" cy="{{ $y }}" r="5" fill="#6366F1" stroke="#fff" stroke-width="2"/>

                            <!-- X-axis label (date) - rotated -->
                            <text x="{{ $x }}" y="{{ $paddingTop + $graphHeight + 15 }}" fill="#6B7280" font-size="10" text-anchor="end" transform="rotate(-45, {{ $x }}, {{ $paddingTop + $graphHeight + 15 }})">{{ $item['month'] }}</text>

                            <!-- Value label above point -->
                            @if($item['avg_days'] > 0)
                                <text x="{{ $x }}" y="{{ $y - 10 }}" fill="#374151" font-size="10" text-anchor="middle" font-weight="600">{{ $item['avg_days'] }}d</text>
                            @endif
                        @endforeach

                        <!-- X-axis line -->
                        <line x1="{{ $paddingLeft }}" y1="{{ $paddingTop + $graphHeight }}" x2="{{ $chartWidth - $paddingRight }}" y2="{{ $paddingTop + $graphHeight }}" stroke="#9CA3AF" stroke-width="1"/>

                        <!-- Y-axis line -->
                        <line x1="{{ $paddingLeft }}" y1="{{ $paddingTop }}" x2="{{ $paddingLeft }}" y2="{{ $paddingTop + $graphHeight }}" stroke="#9CA3AF" stroke-width="1"/>

                        <!-- Y-axis label -->
                        <text x="15" y="{{ $paddingTop + $graphHeight / 2 }}" fill="#6B7280" font-size="11" text-anchor="middle" transform="rotate(-90, 15, {{ $paddingTop + $graphHeight / 2 }})">Days</text>
                    </svg>
                </div>
            @else
                <div class="flex items-center justify-center h-48 text-gray-500">
                    No resolution time data available
                </div>
            @endif
        </div>
        --}}
    </div>

    {{-- Frontend Tickets Section - HIDDEN
    <!-- Frontend Tickets Section -->
    <div class="wrapper-container mt-6">
        <div class="flex items-center space-x-2 mb-4">
            <i class="fa fa-globe text-lg text-blue-500"></i>
            <h2 class="text-lg font-bold text-gray-800">Frontend Submitted Tickets</h2>
            <span class="bg-blue-100 text-blue-800 text-sm font-medium px-3 py-1 rounded-full">
                {{ number_format($frontendTotalTickets) }} total
            </span>
        </div>

        @if($frontendTotalTickets > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Left: By User -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-700 mb-4 flex items-center gap-2">
                        <i class="fa fa-user text-gray-400"></i>
                        By User (Top 10)
                    </h3>
                    <div class="space-y-3">
                        @foreach($frontendUserData as $user)
                            @php
                                $percentage = $frontendTotalTickets > 0 ? ($user['count'] / $frontendTotalTickets) * 100 : 0;
                                $isSelected = $selectedFrontendUserId === $user['id'];
                            @endphp
                            <div class="cursor-pointer rounded p-2 transition-colors {{ $isSelected ? 'bg-indigo-100 ring-2 ring-indigo-400' : 'hover:bg-white' }}" wire:click="selectFrontendUser({{ $user['id'] }})">
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="{{ $isSelected ? 'text-indigo-700 font-semibold' : 'text-gray-600' }}">{{ \Illuminate\Support\Str::limit($user['name'], 20) }}</span>
                                    <span class="font-semibold">{{ $user['count'] }} ({{ round($percentage, 1) }}%)</span>
                                </div>
                                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-indigo-500 rounded-full" style="width: {{ $percentage }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Right: Selected User's Module Chart -->
                <div class="bg-gray-50 rounded-lg p-4">
                    @if($selectedFrontendUserId && count($selectedFrontendUserModuleData) > 0)
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold text-gray-700 flex items-center gap-2">
                                <i class="fa fa-chart-bar text-gray-400"></i>
                                {{ $selectedFrontendUserName }} - By Module
                                <span class="bg-indigo-100 text-indigo-800 text-xs font-medium px-2 py-0.5 rounded-full">
                                    {{ array_sum(array_column($selectedFrontendUserModuleData, 'count')) }}
                                </span>
                            </h3>
                            <button wire:click="clearFrontendUserChart" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <div class="bar-chart">
                            @foreach($selectedFrontendUserModuleData as $index => $item)
                                <div class="bar-item-stacked" x-data="{ showBreakdown: false }">
                                    <div class="bar-label cursor-pointer" @click="showBreakdown = !showBreakdown">
                                        <span class="flex items-center gap-2">
                                            <svg class="w-4 h-4 transition-transform" :class="showBreakdown ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                            {{ \Illuminate\Support\Str::limit($item['name'], 25) }}
                                        </span>
                                        <span class="font-semibold">{{ $item['count'] }}</span>
                                    </div>
                                    <!-- Stacked Bar -->
                                    <div class="bar-wrapper stacked-bar">
                                        @if(!empty($item['breakdown']))
                                            @foreach($item['breakdown'] as $priority)
                                                <div class="bar-segment"
                                                     wire:click="openFrontendUserModuleSlideOver({{ $item['id'] }})"
                                                     style="width: {{ ($priority['count'] / $item['count']) * $item['percentage'] }}%; background-color: {{ $priority['color'] }};"
                                                     title="{{ $priority['name'] }}: {{ $priority['count'] }} ({{ $priority['percentage'] }}%)">
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="bar-fill" wire:click="openFrontendUserModuleSlideOver({{ $item['id'] }})" style="width: {{ $item['percentage'] }}%; background-color: #6B7280;"></div>
                                        @endif
                                    </div>
                                    <!-- Priority Breakdown Details (expandable) -->
                                    <div x-show="showBreakdown" x-collapse class="priority-breakdown">
                                        @if(!empty($item['breakdown']))
                                            @foreach($item['breakdown'] as $priority)
                                                <div class="breakdown-item">
                                                    <span class="breakdown-color" style="background-color: {{ $priority['color'] }};"></span>
                                                    <span class="breakdown-name">{{ $priority['name'] }}</span>
                                                    <span class="breakdown-count">{{ $priority['count'] }} ({{ $priority['percentage'] }}%)</span>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Priority Legend -->
                        <div class="priority-legend mt-3 pt-3 border-t">
                            <div class="flex flex-wrap gap-2">
                                <div class="flex items-center gap-1">
                                    <span class="w-2 h-2 rounded" style="background-color: #EF4444;"></span>
                                    <span class="text-xs text-gray-500">Bugs</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <span class="w-2 h-2 rounded" style="background-color: #F59E0B;"></span>
                                    <span class="text-xs text-gray-500">Backend</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <span class="w-2 h-2 rounded" style="background-color: #8B5CF6;"></span>
                                    <span class="text-xs text-gray-500">Critical</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <span class="w-2 h-2 rounded" style="background-color: #10B981;"></span>
                                    <span class="text-xs text-gray-500">Non-Critical</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <span class="w-2 h-2 rounded" style="background-color: #3B82F6;"></span>
                                    <span class="text-xs text-gray-500">Paid</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center h-full min-h-[200px] text-gray-400">
                            <i class="fa fa-hand-pointer text-4xl mb-3"></i>
                            <p class="text-sm">Select a user to view their ticket breakdown</p>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="text-center text-gray-500 py-8">
                No frontend tickets found for the selected period
            </div>
        @endif
    </div>
    --}}

    <!-- Slide Over Modal -->
    <div
        x-data="{ open: @entangle('showSlideOver') }"
        x-show="open"
        @keydown.window.escape="open = false"
        class="fixed top-0 right-0 bottom-0 left-0 z-[9999] flex justify-end bg-black/40 backdrop-blur-sm transition-opacity duration-200"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        style="display: none;"
    >
        <div
            class="w-full max-w-md bg-white shadow-xl flex flex-col ml-auto"
            style="margin-top: 64px; height: calc(100vh - 64px); position: fixed; right: 0; top: 0;"
            @click.away="open = false"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
        >
            <!-- Header -->
            <div class="bg-white border-b p-4 flex-shrink-0">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-800">{{ $slideOverTitle }}</h2>
                    <button @click="open = false" wire:click="closeSlideOver" class="text-2xl text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <div class="flex items-center justify-between mt-2">
                    <p class="text-sm text-gray-500">{{ count($ticketList) }} tickets found</p>
                    @if(count($ticketsByPriority) > 0)
                        <button
                            wire:click="exportToExcel"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-black bg-green-600 rounded-lg hover:bg-green-700 transition-colors"
                        >
                            <svg wire:loading.remove wire:target="exportToExcel" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <svg wire:loading wire:target="exportToExcel" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="exportToExcel">Export to Excel</span>
                            <span wire:loading wire:target="exportToExcel">Exporting...</span>
                        </button>
                    @endif
                </div>
            </div>

            <!-- Content -->
            <div class="p-4 overflow-y-auto flex-1" id="slide-over-content">
                @if(count($ticketsByPriority) > 0)
                    {{-- Grouped by Priority --}}
                    @foreach($ticketsByPriority as $index => $priorityGroup)
                        @php
                            // Auto-expand only if this is the focused priority, or expand all if no focus
                            $isFocused = $focusPriorityId && $priorityGroup['id'] == $focusPriorityId;
                            $shouldExpand = $focusPriorityId ? $isFocused : true;
                        @endphp
                        <div
                            class="mb-4 priority-group {{ $isFocused ? 'focused-priority' : '' }}"
                            id="priority-group-{{ $priorityGroup['id'] }}"
                            x-data="{ expanded: {{ $shouldExpand ? 'true' : 'false' }} }"
                            @if($isFocused)
                            x-init="$nextTick(() => { $el.scrollIntoView({ behavior: 'smooth', block: 'start' }) })"
                            @endif
                        >
                            {{-- Priority Header (collapsible) --}}
                            <div
                                class="flex items-center justify-between p-3 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors {{ $isFocused ? 'bg-blue-50 ring-2 ring-blue-300' : 'bg-gray-50' }}"
                                @click="expanded = !expanded"
                            >
                                <div class="flex items-center gap-3">
                                    <span class="w-4 h-4 rounded" style="background-color: {{ $priorityGroup['color'] }};"></span>
                                    <span class="font-semibold text-gray-800">{{ $priorityGroup['name'] }}</span>
                                    <span class="text-sm text-gray-500 bg-white px-2 py-0.5 rounded-full border">{{ $priorityGroup['count'] }}</span>
                                </div>
                                <svg
                                    class="w-5 h-5 text-gray-500 transition-transform duration-200"
                                    :class="expanded ? 'rotate-180' : ''"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>

                            {{-- Tickets in this priority group --}}
                            <div x-show="expanded" x-collapse class="mt-2 ml-2 border-l-2 pl-3" style="border-color: {{ $priorityGroup['color'] }};">
                                @foreach($priorityGroup['tickets'] as $ticket)
                                    <a
                                        href="{{ url('admin/ticket-list') }}?ticket={{ $ticket['id'] }}"
                                        target="_blank"
                                        class="slide-over-item"
                                    >
                                        <div class="ticket-id">#{{ $ticket['ticket_id'] }}</div>
                                        <div class="ticket-title">{{ \Illuminate\Support\Str::limit($ticket['title'] ?? 'No Title', 60) }}</div>
                                        <div class="ticket-meta">
                                            {{ $ticket['company_name'] ?? 'N/A' }} &bull;
                                            {{ $ticket['status'] ?? 'N/A' }} &bull;
                                            {{ isset($ticket['created_date']) ? \Carbon\Carbon::parse($ticket['created_date'])->format('d M Y') : 'N/A' }}
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @elseif(count($ticketList) > 0)
                    {{-- Flat list fallback (for priority or status slide-overs) --}}
                    @foreach($ticketList as $ticket)
                        <a
                            href="{{ url('admin/ticket-list') }}?ticket={{ $ticket['id'] }}"
                            target="_blank"
                            class="slide-over-item"
                        >
                            <div class="ticket-id">#{{ $ticket['ticket_id'] }}</div>
                            <div class="ticket-title">{{ \Illuminate\Support\Str::limit($ticket['title'] ?? 'No Title', 60) }}</div>
                            <div class="ticket-meta">
                                {{ $ticket['company_name'] ?? 'N/A' }} &bull;
                                {{ $ticket['status'] ?? 'N/A' }} &bull;
                                {{ $ticket['created_date'] ? \Carbon\Carbon::parse($ticket['created_date'])->format('d M Y') : 'N/A' }}
                            </div>
                        </a>
                    @endforeach
                @else
                    <div class="text-center text-gray-500 py-8">No tickets found</div>
                @endif
            </div>
        </div>
    </div>
</x-filament::page>
