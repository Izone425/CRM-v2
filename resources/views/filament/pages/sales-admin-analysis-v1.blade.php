<x-filament::page>
    <head>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <style>
            .grid-container {
                display: grid;
                grid-template-columns: 1fr 1fr 2fr; /* 1:1:2 Ratio */
                gap: 16px;
                width: 100%;
            }

            .wrapper-container {
                background-color: white;
                border-radius: 10px;
                padding: 20px;
                box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
                width: 205%;
            }

            .grid-layout {
                display: grid;
                grid-template-columns: 1fr 1fr; /* Left side (3 charts), Right side (box) */
                gap: 16px;
                width: 100%;
            }

            .left-side {
                display: flex;
                flex-direction: column;
                gap: 16px;
            }

            .right-side {
                background: #ffffff;
                border-radius: 10px;
                padding: 20px;
                box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
                text-align: center;
            }

            /* Total Leads Box */
            .lead-card {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 60px;
                border-radius: 10px;
                text-align: center;
            }

            .icon-container {
                background-color: #DBEAFE;
                padding: 8px;
                border-radius: 8px;
            }

            .icon-container svg {
                width: 30px;
                height: 30px;
                color: #3B82F6;
            }

            .lead-number {
                font-size: 1.5rem;
                font-weight: bold;
                color: #1F2937;
                margin-top: 8px;
            }

            .lead-text {
                font-size: 0.8rem;
                color: #6B7280;
            }

            /* Status & Progress Circles */
            .status-box {
                padding: 16px;
                background: #F9FAFB;
                border-radius: 10px;
                text-align: center;
            }

            .progress-circle {
                position: relative;
                width: 80px;
                height: 80px;
            }

            .progress-label {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                font-size: 14px;
                font-weight: bold;
                color: #333;
            }

            /* Company Size Chart */
            .company-size-container {
                padding: 16px;
                background-color: white;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                text-align: center;
            }

            .bars-container {
                display: flex;
                justify-content: center;
                align-items: flex-end;
                height: 160px;
                gap: 75px;
            }

            .bar-group {
                display: flex;
                flex-direction: column;
                align-items: center;
                width: 50px;
                position: relative;
            }

            .percentage-label {
                margin-bottom: 5px;
                font-size: 12px;
                font-weight: bold;
                color: #333;
            }

            .bar-wrapper {
                width: 60px;
                height: 100px;
                background-color: #E5E7EB; /* Light gray */
                border-radius: 8px;
                position: relative;
                overflow: hidden;
            }

            .bar-fill {
                position: absolute;
                bottom: 0;
                width: 100%;
                border-radius: 8px;
                transition: height 0.5s ease-in-out;
            }

            .size-label {
                margin-top: 8px;
                font-size: 12px;
                font-weight: 500;
                color: #374151;
            }

            /* Hover Message */
            .hover-message {
                position: absolute;
                bottom: 110%;
                left: 50%;
                transform: translateX(-50%);
                background-color: rgba(0, 0, 0, 0.75);
                color: white;
                padding: 5px 10px;
                font-size: 12px;
                border-radius: 5px;
                white-space: nowrap;
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.3s ease-in-out;
            }

            .bar-group:hover .hover-message {
                opacity: 1;
                visibility: visible;
            }

            .lead-summary-box {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 20px;
                border-radius: 10px;
            }

            /* Left Section (30%) */
            .lead-count {
                flex: 3;
                text-align: center;
            }
            .lead-number {
                font-size: 2rem;
                font-weight: bold;
                color: #333;
            }
            .lead-label {
                font-size: 0.9rem;
                color: #777;
            }

            /* Middle Divider (5%) */
            .lead-divider {
                flex: 0.02;
                height: 150px;
                background: #ccc;
                width: 0.5px;
            }

            /* Right Section (65%) */
            .lead-progress {
                flex: 6.5;
            }
            .status-title {
                font-size: 1rem;
                font-weight: bold;
                margin-bottom: 10px;
            }

            /* Progress Bar */
            .progress-info {
                display: flex;
                justify-content: space-between;
                font-size: 0.9rem;
                color: #555;
            }
            .progress-bar {
                width: 100%;
                height: 10px;
                background: #e0e0e0;
                border-radius: 5px;
                margin-top: 5px;
                position: relative;
                margin-bottom: 10px;
            }
            .progress-fill {
                height: 100%;
                border-radius: 5px;
            }

            /* Left Section (30%) */
            .lead-count {
                flex: 3;
                text-align: center;
            }
            .lead-number {
                font-size: 2rem;
                font-weight: bold;
                color: #333;
            }
            .lead-label {
                font-size: 0.9rem;
                color: #777;
            }

            /* Middle Divider (5%) */
            .lead-divider {
                flex: 0.02;
                height: 160px;
                background: #ccc;
                width: 0.5px;
            }

            /* Right Section (65%) */
            .lead-progress {
                flex: 6.5;
            }
            .status-title {
                font-size: 1rem;
                font-weight: bold;
                margin-bottom: 10px;
            }

            /* Progress Bar */
            .progress-info {
                display: flex;
                justify-content: space-between;
                font-size: 0.9rem;
                color: #555;
            }
            .progress-bar {
                width: 100%;
                height: 10px;
                background: #e0e0e0;
                border-radius: 5px;
                margin-top: 5px;
                position: relative;
                margin-bottom: 10px;
            }
            .progress-fill {
                height: 100%;
                border-radius: 5px;
            }
            /* .hover-message {
                visibility: hidden;
            } */
            .group:hover .hover-message {
                opacity: 1;
                visibility: visible;
            }
        </style>
    </head>

    <div class="flex flex-col items-center justify-between mb-6 md:flex-row">
            <!-- Title -->
        <h1 class="text-2xl font-bold tracking-tight fi-header-heading text-gray-950 dark:text-white sm:text-3xl">Sales Admin Analysis V1</h1>
        <div class="flex items-center mb-6">
            <!-- Month Filter (Added Margin) -->
            <div class="ml-10">  <!-- Manually added space using margin-left -->
                <input wire:model="selectedMonth" type="month" id="monthFilter" class="mt-1 border-gray-300 rounded-md shadow-sm">
            </div>
        </div>
    </div>

    <div class="grid-layout">
        <div class="left-side">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2" wire:poll.1s>
                <div class="w-full overflow-hidden wrapper-container">
                    <div class="max-w-full overflow-x-auto grid-container">
                        <!-- Total Leads Box -->
                        <div class="lead-card">
                            <div class="icon-container">
                                <i class="text-2xl text-blue-500 fa fa-users"></i>
                            </div>
                            <p class="lead-number">{{ $totalLeads }}</p>
                            <p class="lead-text">Total Leads</p>
                        </div>

                        <!-- Status Breakdown -->
                        <div class="p-6 rounded-lg shadow-lg bg-gray-50" >
                            <h3 class="mb-4 text-sm font-semibold text-center text-gray-700 uppercase">Status</h3>
                            <div class="flex justify-center space-x-10">
                                @foreach ([
                                    ['label' => 'New', 'percentage' => $newPercentage, 'count' => $newLeads, 'color' => '#5c6bc0', 'bg-color' => '#daddee'],
                                    ['label' => 'Jaja', 'percentage' => $jajaPercentage, 'count' => $jajaLeads, 'color' => '#6a1b9a', 'bg-color' => '#ddcde7'],
                                    ['label' => 'Afifah', 'percentage' => $afifahPercentage, 'count' => $afifahLeads, 'color' => '#b1365b', 'bg-color' => '#ebd3da']
                                ] as $data)
                                    <div class="relative text-center group">
                                        <div class="relative w-28 h-28">
                                            <svg width="130" height="130" viewBox="0 0 36 36">
                                                <circle cx="18" cy="18" r="14" stroke="{{ $data['bg-color'] }}" stroke-opacity="0.3" stroke-width="5" fill="none"></circle>
                                                <circle cx="18" cy="18" r="14" stroke="{{ $data['color'] }}" stroke-width="5" fill="none"
                                                        stroke-dasharray="88"
                                                        stroke-dashoffset="{{ 88 - (88 * ($data['percentage'] / 100)) }}"
                                                        stroke-linecap="round"
                                                        transform="rotate(-90 18 18)">
                                                </circle>
                                            </svg>
                                            <div class="absolute inset-0 flex items-center justify-center text-lg font-bold text-gray-900">
                                                {{ $data['count'] }}
                                            </div>
                                            <!-- Hover message (hidden by default) -->
                                            <div class="hover-message">
                                                {{ $data['percentage'] }}%
                                            </div>
                                        </div>
                                        <p class="mt-2 text-sm text-gray-700">{{ $data['label'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2" wire:poll.1s>
                <div class="w-full overflow-hidden wrapper-container">
                    <div class="max-w-full overflow-x-auto grid-container">
                        <!-- Total Leads Box -->
                        <div class="lead-card">
                            <div class="icon-container">
                                <i class="text-2xl text-blue-500 fa fa-users"></i>
                            </div>
                            <p class="lead-number">{{ $totalLeads }}</p>
                            <p class="lead-text">Total Leads</p>
                        </div>

                        <!-- Status Breakdown -->
                        <div class="p-6 rounded-lg shadow bg-gray-50">
                            <h3 class="mb-4 text-sm font-semibold text-center text-gray-700 uppercase">Lead Categories</h3>
                            <div class="flex justify-center space-x-10">
                                @foreach ([
                                    ['label' => 'New', 'count' => $categoriesData['New'] ?? 0, 'color' => '#5c6bc0', 'bg-color' => '#daddee'],
                                    ['label' => 'Active', 'count' => $categoriesData['Active'] ?? 0, 'color' => '#00c7b1', 'bg-color' => '#c8f0eb'],
                                    ['label' => 'Sales', 'count' => $categoriesData['Sales'] ?? 0, 'color' => '#fb8c00', 'bg-color' => '#fae4c8'],
                                    ['label' => 'Inactive', 'count' => $categoriesData['Inactive'] ?? 0, 'color' => '#a6a6a6', 'bg-color' => '#e9e9e9']
                                ] as $data)
                                    @php
                                        $percentage = $totalLeads > 0 ? round(($data['count'] / $totalLeads) * 100, 2) : 0;
                                    @endphp
                                    <div class="relative text-center group">
                                        <div class="relative w-28 h-28">
                                            <svg width="100" height="100" viewBox="0 0 36 36">
                                                <circle cx="18" cy="18" r="14" stroke="{{ $data['bg-color'] }}" stroke-opacity="0.3" stroke-width="5" fill="none"></circle>
                                                <circle cx="18" cy="18" r="14" stroke="{{ $data['color'] }}" stroke-width="5" fill="none"
                                                        stroke-dasharray="88"
                                                        stroke-dashoffset="{{ 88 - (88 * ($percentage / 100)) }}"
                                                        stroke-linecap="round"
                                                        transform="rotate(-90 18 18)">
                                                </circle>
                                            </svg>
                                            <div class="absolute inset-0 flex items-center justify-center text-lg font-bold text-gray-900">
                                                {{ $data['count'] }}
                                            </div>
                                            <!-- Hover message (hidden by default) -->
                                            <div class="hover-message">
                                                {{ $percentage }}%
                                            </div>
                                        </div>
                                        <p class="mt-2 text-sm text-gray-700">{{ $data['label'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2" wire:poll.1s>
                <div class="w-full overflow-hidden wrapper-container">
                    <div class="max-w-full overflow-x-auto grid-container">
                        <!-- Total Leads Box -->
                        <div class="lead-card">
                            <div class="icon-container">
                                <i class="text-2xl text-blue-500 fa fa-users"></i>
                            </div>
                            <p class="lead-number">{{ $totalLeads }}</p>
                            <p class="lead-text">Total Leads</p>
                        </div>

                        <!-- Status Breakdown -->
                        <div class="p-6 rounded-lg shadow bg-gray-50">
                            <h3 class="mb-4 text-sm font-semibold text-center text-gray-700 uppercase">Company Size Distribution</h3>
                            <div class="flex justify-center space-x-10">
                                @foreach ($companySizeData as $size => $count)
                                    @php
                                        $percentage = $totalLeads > 0 ? round(($count / $totalLeads) * 100, 2) : 0;
                                        $color = match($size) {
                                            'Small' => '#81c784',   // Blue
                                            'Medium' => '#ffb400',  // Green
                                            'Large' => '#ff9472',   // Yellow
                                            'Enterprise' => '#ff5757', // Red
                                            default => '#9CA3AF'   // Gray (fallback)
                                        };
                                        $bgcolor = match($size) {
                                            'Small' => '#e4f1e5',   // Blue
                                            'Medium' => '#f4e9cc',  // Green
                                            'Large' => '#fde2d9',   // Yellow
                                            'Enterprise' => '#fed7d7', // Red
                                            default => '#9CA3AF'   // Gray (fallback)
                                        };
                                    @endphp
                                    <div class="relative text-center group">
                                        <div class="relative w-28 h-28">
                                            <svg width="100" height="100" viewBox="0 0 36 36">
                                                <circle cx="18" cy="18" r="14" stroke="{{ $bgcolor }}" stroke-width="5" fill="none"></circle>
                                                <circle cx="18" cy="18" r="14" stroke="{{ $color }}" stroke-width="5" fill="none"
                                                        stroke-dasharray="88"
                                                        stroke-dashoffset="{{ 88 - (88 * ($percentage / 100)) }}"
                                                        stroke-linecap="round"
                                                        transform="rotate(-90 18 18)">
                                                </circle>
                                            </svg>
                                            <div class="absolute inset-0 flex items-center justify-center text-lg font-bold text-gray-900">
                                                {{ $count }}
                                            </div>
                                            <div class="hover-message">
                                                {{ $percentage }}%
                                            </div>
                                        </div>
                                        <p class="mt-2 text-sm text-gray-700">{{ $size }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="right-side">
            <!-- Left Side: Total Leads -->
            <div class="flex items-center p-6 space-x-6 bg-white rounded-lg shadow-md">
                <!-- Left Side: Leads Overview -->
                <div class="flex-1">
                    <h2 class="text-lg font-semibold text-gray-800">Leads Overview</h2>
                    <p class="text-4xl font-bold text-blue-600">{{ $totalLeads }}</p>
                    <p class="mt-2 text-gray-600">Total Leads</p>
                </div>

                <!-- Right Side: New Leads -->
                <div class="flex flex-col items-center justify-center w-1/3 p-6 bg-gray-100 rounded-lg shadow-inner">
                    <h3 class="text-lg font-semibold text-gray-700">New Leads</h3>
                    <p class="text-3xl font-bold text-green-600">{{ $newLeads }}</p>
                </div>
            </div>

            <hr class="my-6 border-t border-gray-300">

            <div class="lead-summary-box">
                <!-- Left Section (30%) -->
                <div class="lead-count">
                    <p class="lead-number">{{ $totalActiveLeads }}</p>
                    <p class="lead-label">Total Active Lead</p>
                </div>

                <!-- Middle Divider (5%) -->
                <div class="lead-divider"></div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                <!-- Right Section (65%) -->
                <div class="lead-progress">
                    <h3 class="status-title">Status</h3>
                    @foreach ($activeLeadsData as $stage => $count)
                        @php
                            $percentage = $totalActiveLeads > 0 ? round(($count / $totalActiveLeads) * 100, 2) : 0;
                            $color = match($stage) {
                                'Active 24 Below' => '#7bbaff',  // Blue
                                'Active 25 Above' => '#00c6ff',  // Green
                                'Call Attempt 24 Below' => '#00ebff',  // Yellow
                                'Call Attempt 25 Above' => '#00edd1', // Red
                                default => '#D1D5DB',  // Gray (fallback)
                            };
                            $bgcolor = match($stage) {
                                'Active 24 Below' => '#e0edfb',  // Blue
                                'Active 25 Above' => '#c7effb',  // Green
                                'Call Attempt 24 Below' => '#c7f6fb',  // Yellow
                                'Call Attempt 25 Above' => '#c7f7f1', // Red
                                default => '#D1D5DB',  // Gray (fallback)
                            };
                        @endphp

                        <!-- Stage Title & Count -->
                        <div class="progress-info">
                            <span>{{ ucfirst($stage) }}</span>
                            <span>{{ $count }} ({{ $percentage }}%)</span>
                        </div>

                        <!-- Progress Bar -->
                        <div class="progress-bar" style="background-color: {{ $bgcolor }};">
                            <div class="progress-fill" style="width: {{ $percentage }}%; background-color: {{ $color }};"></div>
                        </div>
                    @endforeach
                </div>
            </div>

            <hr class="my-6 border-t border-gray-300">

            <div class="lead-summary-box">
                <div class="lead-count">
                    <p class="lead-number">{{ $totalTransferLeads }}</p>
                    <p class="lead-label">Total Transfer Leads</p>
                </div>

                <div class="lead-divider"></div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                <div class="lead-progress">
                    <h3 class="status-title">Stages</h3>
                    @foreach ($transferStagesData as $stage => $count)
                        @php
                            $percentage = $totalTransferLeads > 0 ? round(($count / $totalTransferLeads) * 100, 2) : 0;
                            $color = match($stage) {
                                'Transfer' => '#ffde59',  /* Light Blue */
                                'Demo' => '#ffa83c',      /* Purple */
                                'Follow Up' => '#ff914d', /* Dark Blue */
                                default => '#D1D5DB',     // Gray
                            };
                            $bgcolor = match($stage) {
                                'Transfer' => '#fff8dd',  /* Light Blue */
                                'Demo' => '#ffedd7',      /* Purple */
                                'Follow Up' => '#ffe8da', /* Dark Blue */
                                default => '#D1D5DB',     // Gray
                            };
                        @endphp

                        <div class="progress-info">
                            <span>{{ ucfirst($stage) }}</span>
                            <span>{{ $count }} ({{ $percentage }}%)</span>
                        </div>

                        <div class="progress-bar" style="background-color: {{ $bgcolor }};">
                            <div class="progress-fill" style="width: {{ $percentage }}%; background-color: {{ $color }};"></div>
                        </div>
                    @endforeach
                </div>
            </div>

            <hr class="my-6 border-t border-gray-300">

            <div class="lead-summary-box">
                <div class="lead-count">
                    <p class="lead-number">{{ $totalInactiveLeads }}</p>
                    <p class="lead-label">Total Inactive Leads</p>
                </div>

                <div class="lead-divider"></div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                <div class="lead-progress">
                    <h3 class="status-title">Stages</h3>
                    @foreach ($inactiveLeadData as $status => $count)
                        @php
                            $percentage = $totalInactiveLeads > 0 ? round(($count / $totalInactiveLeads) * 100, 2) : 0;
                            $color = match($status) {
                                'Junk' => '#545454',
                                'Lost' => '#737373',
                                'On Hold' => '#99948f',
                                'No Response' => '#c8c4bd',
                                default => '#D1D5DB',
                            };
                            $bgcolor = match($status) {
                                'Junk' => '#dcdcdc',  /* Light Blue */
                                'Lost' => '#e2e2e2',      /* Purple */
                                'On Hold' => '#eae9e8', /* Dark Blue */
                                'No Response' => '#f3f3f1',
                                default => '#f3f3f1',     // Gray
                            };
                        @endphp

                        <div class="progress-info">
                            <span>{{ ucfirst($status) }}</span>
                            <span>{{ $count }} ({{ $percentage }}%)</span>
                        </div>

                        <div class="progress-bar" style="background-color: {{ $bgcolor }};">
                            <div class="progress-fill" style="width: {{ $percentage }}%; background-color: {{ $color }};"></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-filament::page>
