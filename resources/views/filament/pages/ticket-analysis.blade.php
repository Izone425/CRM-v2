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

            .summary-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 16px;
                width: 100%;
            }

            .stat-card {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 20px;
                border-radius: 10px;
                text-align: center;
                background: #F9FAFB;
                cursor: pointer;
                transition: transform 0.2s, box-shadow 0.2s;
            }

            .stat-card:hover {
                transform: scale(1.02);
                box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.15);
            }

            .stat-icon {
                padding: 12px;
                border-radius: 10px;
                margin-bottom: 10px;
            }

            .stat-number {
                font-size: 2rem;
                font-weight: bold;
                color: #1F2937;
            }

            .stat-label {
                font-size: 0.875rem;
                color: #6B7280;
            }

            .chart-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
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
                justify-content: center;
                gap: 30px;
            }

            .donut-chart {
                position: relative;
                width: 180px;
                height: 180px;
            }

            .donut-legend {
                display: flex;
                flex-direction: column;
                gap: 8px;
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
            <!-- Date Range Filters -->
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600">From:</label>
                <input type="date" wire:model.live="startDate" class="border-gray-300 rounded-md shadow-sm text-sm">
            </div>
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600">To:</label>
                <input type="date" wire:model.live="endDate" class="border-gray-300 rounded-md shadow-sm text-sm">
            </div>

            <!-- Product Filter -->
            <select wire:model.live="selectedProduct" class="border-gray-300 rounded-md shadow-sm text-sm">
                <option value="all">All Products</option>
                <option value="v1">Version 1</option>
                <option value="v2">Version 2</option>
            </select>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="wrapper-container">
        <div class="flex items-center space-x-2 mb-4">
            <i class="fa fa-chart-line text-lg text-gray-500"></i>
            <h2 class="text-lg font-bold text-gray-800">Summary</h2>
        </div>

        <div class="summary-grid">
            <!-- Total Tickets -->
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #DBEAFE;">
                    <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                    </svg>
                </div>
                <p class="stat-number">{{ number_format($totalTickets) }}</p>
                <p class="stat-label">Total Tickets</p>
            </div>

            <!-- Open Tickets -->
            <div class="stat-card" wire:click="openStatusSlideOver('open')">
                <div class="stat-icon" style="background-color: #FEF3C7;">
                    <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="stat-number">{{ number_format($openTickets) }}</p>
                <p class="stat-label">Open Tickets</p>
            </div>

            <!-- Completed Tickets -->
            <div class="stat-card" wire:click="openStatusSlideOver('completed')">
                <div class="stat-icon" style="background-color: #D1FAE5;">
                    <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="stat-number">{{ number_format($completedTickets) }}</p>
                <p class="stat-label">Completed</p>
            </div>

            <!-- Avg Resolution -->
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #EDE9FE;">
                    <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <p class="stat-number">{{ $avgResolutionDays }}</p>
                <p class="stat-label">Avg. Days to Resolve</p>
            </div>
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
                                ></circle>
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
                            @php $color = $colors[$index % count($colors)]; @endphp
                            <div class="legend-item" wire:click="openPrioritySlideOver({{ $item['id'] }})">
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

        <!-- Module Distribution (Bar Chart) -->
        <div class="chart-container">
            <div class="chart-title">
                <i class="fa fa-th-large text-gray-500"></i>
                <span>By Module (Top 10)</span>
            </div>

            @if(count($moduleData) > 0)
                <div class="bar-chart">
                    @php
                        $barColors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#6366F1', '#14B8A6', '#F97316', '#84CC16'];
                    @endphp

                    @foreach($moduleData as $index => $item)
                        @php $color = $barColors[$index % count($barColors)]; @endphp
                        <div class="bar-item" wire:click="openModuleSlideOver({{ $item['id'] }})">
                            <div class="bar-label">
                                <span>{{ \Illuminate\Support\Str::limit($item['name'], 25) }}</span>
                                <span class="font-semibold">{{ $item['count'] }}</span>
                            </div>
                            <div class="bar-wrapper">
                                <div class="bar-fill" style="width: {{ $item['percentage'] }}%; background-color: {{ $color }};"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex items-center justify-center h-48 text-gray-500">
                    No module data available
                </div>
            @endif
        </div>

        <!-- Resolution Time Trend (Line Chart) - Full Width -->
        <div class="chart-container full-width-chart">
            <div class="chart-title">
                <i class="fa fa-chart-line text-gray-500"></i>
                <span>Average Resolution Time (Days) - Monthly Trend</span>
            </div>

            @if(count($durationData) > 0)
                <div class="line-chart-container">
                    @php
                        $maxDays = max(array_column($durationData, 'avg_days')) ?: 1;
                        $dataCount = count($durationData);
                        $chartWidth = 100;
                        $chartHeight = 200;
                        $padding = 40;
                        $graphWidth = $chartWidth - ($padding * 2);
                        $graphHeight = $chartHeight - ($padding * 2);
                    @endphp

                    <svg class="line-chart-svg" viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" preserveAspectRatio="xMidYMid meet">
                        <!-- Grid lines -->
                        @for($i = 0; $i <= 4; $i++)
                            @php $y = $padding + ($graphHeight * $i / 4); @endphp
                            <line x1="{{ $padding }}" y1="{{ $y }}" x2="{{ $chartWidth - $padding }}" y2="{{ $y }}" stroke="#E5E7EB" stroke-width="0.2"/>
                            <text x="{{ $padding - 2 }}" y="{{ $y + 1 }}" fill="#9CA3AF" font-size="3" text-anchor="end">{{ round($maxDays * (4 - $i) / 4, 0) }}</text>
                        @endfor

                        <!-- Line path -->
                        @php
                            $points = [];
                            foreach ($durationData as $index => $item) {
                                $x = $padding + ($graphWidth * $index / max($dataCount - 1, 1));
                                $y = $padding + $graphHeight - ($graphHeight * ($item['avg_days'] / max($maxDays, 1)));
                                $points[] = "$x,$y";
                            }
                            $pathD = 'M ' . implode(' L ', $points);
                        @endphp

                        <!-- Area fill -->
                        <path d="{{ $pathD }} L {{ $padding + $graphWidth }},{{ $padding + $graphHeight }} L {{ $padding }},{{ $padding + $graphHeight }} Z" fill="url(#areaGradient)" opacity="0.3"/>

                        <!-- Gradient definition -->
                        <defs>
                            <linearGradient id="areaGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#3B82F6"/>
                                <stop offset="100%" stop-color="#3B82F6" stop-opacity="0"/>
                            </linearGradient>
                        </defs>

                        <!-- Line -->
                        <path d="{{ $pathD }}" fill="none" stroke="#3B82F6" stroke-width="0.5" stroke-linecap="round" stroke-linejoin="round"/>

                        <!-- Data points and labels -->
                        @foreach($durationData as $index => $item)
                            @php
                                $x = $padding + ($graphWidth * $index / max($dataCount - 1, 1));
                                $y = $padding + $graphHeight - ($graphHeight * ($item['avg_days'] / max($maxDays, 1)));
                            @endphp
                            <circle cx="{{ $x }}" cy="{{ $y }}" r="1" fill="#3B82F6"/>
                            <text x="{{ $x }}" y="{{ $padding + $graphHeight + 5 }}" fill="#6B7280" font-size="2.5" text-anchor="middle">{{ $item['month'] }}</text>
                            <text x="{{ $x }}" y="{{ $y - 3 }}" fill="#374151" font-size="2.5" text-anchor="middle" font-weight="bold">{{ $item['avg_days'] }}d</text>
                        @endforeach
                    </svg>
                </div>
            @else
                <div class="flex items-center justify-center h-48 text-gray-500">
                    No resolution time data available
                </div>
            @endif
        </div>
    </div>

    <!-- Slide Over Modal -->
    <div
        x-data="{ open: @entangle('showSlideOver') }"
        x-show="open"
        @keydown.window.escape="open = false"
        class="fixed inset-0 z-[200] flex justify-end bg-black/40 backdrop-blur-sm transition-opacity duration-200"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        style="display: none;"
    >
        <div
            class="w-full h-full max-w-md overflow-y-auto bg-white shadow-xl"
            @click.away="open = false"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
        >
            <!-- Header -->
            <div class="sticky top-0 bg-white border-b p-4 z-10">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-800">{{ $slideOverTitle }}</h2>
                    <button @click="open = false" wire:click="closeSlideOver" class="text-2xl text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <p class="text-sm text-gray-500 mt-1">{{ count($ticketList) }} tickets found</p>
            </div>

            <!-- Content -->
            <div class="p-4">
                @forelse ($ticketList as $ticket)
                    <a
                        href="{{ url('admin/ticket-list') }}?ticket={{ $ticket->id }}"
                        target="_blank"
                        class="slide-over-item"
                    >
                        <div class="ticket-id">#{{ $ticket->ticket_id }}</div>
                        <div class="ticket-title">{{ \Illuminate\Support\Str::limit($ticket->title ?? 'No Title', 60) }}</div>
                        <div class="ticket-meta">
                            {{ $ticket->company_name ?? 'N/A' }} &bull;
                            {{ $ticket->status ?? 'N/A' }} &bull;
                            {{ $ticket->created_date ? $ticket->created_date->format('d M Y') : 'N/A' }}
                        </div>
                    </a>
                @empty
                    <div class="text-center text-gray-500 py-8">No tickets found</div>
                @endforelse
            </div>
        </div>
    </div>
</x-filament::page>
