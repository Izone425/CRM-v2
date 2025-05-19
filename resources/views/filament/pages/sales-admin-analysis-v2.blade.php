<x-filament::page>
    <head>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <style>
            /* Container */
            .session-container {
                display: flex;
                align-items: center;
                padding: 20px;
                border-radius: 8px;
            }

            /* Left Section */
            .session-count {
                flex: 1;
                text-align: center;
            }
            .session-number {
                font-size: 3rem;
                font-weight: bold;
                color: #333;
                margin-top: 10px;
            }
            .session-label {
                font-size: 0.9rem;
                color: #777;
            }

            /* Middle Divider */
            .session-divider {
                flex: 0.005;
                height: 150px;
                background: #ccc;
                width: 0.5px;
            }

            /* Right Section */
            .session-bars {
                flex: 3;
                display: flex;
                justify-content: center;
                align-items: flex-end;
                gap: 32px; /* Space between bars */
            }

            .bar-group {
                display: flex;
                flex-direction: column;
                align-items: center;
                width: 60px;
                position: relative;
            }

            .percentage-label {
                margin-bottom: 5px;
                font-size: 12px;
                font-weight: bold;
                color: #333;
            }

            .bar-wrapper {
                width: 40px;
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

            .session-type {
                margin-top: 8px;
                font-size: 12px;
                font-weight: 500;
                text-align: center;
                color: #374151;
            }

            /* Tooltip (Hover Message) */
            .hover-message {
                position: absolute;
                bottom: 110%;
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

            .wrapper-container {
                background-color: white;
                border-radius: 10px;
                padding: 20px;
                box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
                width: 200%;
            }

            .grid-container {
                display: grid;
                grid-template-columns: 1fr 1fr 2fr; /* 1:1:2 Ratio */
                gap: 16px;
                width: 100%;
            }

            /* Total Leads Box */
            .lead-card {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 40px;
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
                font-size: 2rem;
                font-weight: bold;
                color: #1F2937;
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
                height: 220px;
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
                height: 220px;
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

            .group:hover .hover-message {
                opacity: 1;
                visibility: visible;
            }

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
                transition: opacity 0.3sease-in-out;
            }
            .cursor-pointer:hover {
                transform: scale(1.02);
                transition: all 0.2s;
            }
        </style>
    </head>
    <div class="flex flex-col items-center justify-between mb-6 md:flex-row">
            <!-- Title -->
        <h1 class="text-2xl font-bold tracking-tight fi-header-heading text-gray-950 dark:text-white sm:text-3xl">Sales Admin Analysis V2</h1>
        <div class="flex items-center mb-6">
            <!-- Month Filter (Added Margin) -->
            <div class="ml-10">  <!-- Manually added space using margin-left -->
                <input wire:model="selectedMonth" type="month" id="monthFilter" class="mt-1 border-gray-300 rounded-md shadow-sm">
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div class="w-full p-6 overflow-hidden bg-white rounded-lg shadow-lg" wire:poll.1s>
            <div class="max-w-full overflow-x-auto lead-summary-box">
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
                    <h3 class="mb-4 text-sm font-semibold text-center text-gray-700 uppercase">Status</h3>
                    <div class="flex justify-center space-x-10">
                        @foreach ([
                            ['label' => 'New', 'percentage' => $newPercentage, 'count' => $newLeads, 'color' => '#5c6bc0', 'bg-color' => '#daddee'],
                            ['label' => 'Jaja', 'percentage' => $jajaPercentage, 'count' => $jajaLeads, 'color' => '#6a1b9a', 'bg-color' => '#ddcde7'],
                            ['label' => 'Shahilah', 'percentage' => $shahilahPercentage, 'count' => $shahilahLeads, 'color' => '#00796b', 'bg-color' => '#c8e6e1'],
                            ['label' => 'Afifah', 'percentage' => $afifahPercentage, 'count' => $afifahLeads, 'color' => '#b1365b', 'bg-color' => '#ebd3da'],
                        ] as $data)
                            <div class="relative text-center cursor-pointer group" wire:click="openLeadBreakdownSlideOver('{{ $data['label'] }}')">
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

        <div class="w-full p-6 overflow-hidden bg-white rounded-lg shadow-lg" wire:poll.1s>
            <div class="max-w-full overflow-x-auto lead-summary-box">
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
                                <div class="relative text-center cursor-pointer group" wire:click="openLeadCategorySlideOver('{{ $data['label'] }}')">
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

        <div class="p-6 bg-white rounded-lg shadow-lg" wire:poll.1s>
            <div class="flex items-center space-x-3">
                <p class="m-0 lead-label">Total Leads (Admin Jaja):</p>&nbsp;
                <p class="m-0 lead-number">{{ array_sum($adminJajaLeadStats) }}</p>
            </div>

            <div class="lead-summary-box">
                <div class="lead-progress">
                    <h3 class="mb-4 text-center status-title">Lead Categories (Admin Jaja)</h3>

                    <div class="flex justify-center space-x-10">
                        @foreach ($adminJajaLeadStats as $category => $count)
                            @php
                                $totalLeads = array_sum($adminJajaLeadStats);
                                $percentage = $totalLeads > 0 ? round(($count / $totalLeads) * 100, 2) : 0;

                                $categoryColors = [
                                    'Active'   => '#00c7b1',  // Teal
                                    'Sales'    => '#fb8c00',  // Orange
                                    'Inactive' => '#a6a6a6',  // Gray
                                ];

                                $categoryBgColors = [
                                    'Active'   => '#c8f0eb',
                                    'Sales'    => '#fae4c8',
                                    'Inactive' => '#e9e9e9',
                                ];

                                $color = $categoryColors[$category] ?? '#6B7280';
                                $bgColor = $categoryBgColors[$category] ?? '#E5E7EB';
                            @endphp

                            <div class="relative text-center cursor-pointer group" wire:click="openJajaLeadCategorySlideOver('{{ $category }}')">
                                <div class="relative w-28 h-28">
                                    <svg width="100" height="100" viewBox="0 0 36 36">
                                        <circle cx="18" cy="18" r="14" stroke="{{ $bgColor }}" stroke-opacity="0.3" stroke-width="5" fill="none"></circle>
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
                                    <!-- Hover message (hidden by default) -->
                                    <div class="hover-message">
                                        {{ $percentage }}%
                                    </div>
                                </div>
                                <p class="mt-2 text-sm text-gray-700">{{ ucfirst($category) }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Separator Line -->
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<div class="lead-divider"></div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                <div class="mt-4 lead-progress">
                    <h3 class="text-lg font-bold text-center text-gray-800">Summary Active</h3>

                    @foreach ($activeLeadsDataJaja as $status => $count)
                        @php
                            $percentage = $totalActiveLeadsJaja > 0 ? round(($count / $totalActiveLeadsJaja) * 100, 2) : 0;

                            // Color mapping from your existing code
                            $color = match($status) {
                                'Active 24 Below' => '#7bbaff',  // Blue
                                'Active 25 Above' => '#00c6ff',  // Green
                                'Call Attempt 24 Below' => '#00ebff',  // Yellow
                                'Call Attempt 25 Above' => '#00edd1', // Red
                                default => '#D1D5DB',  // Gray (fallback)
                            };
                            $barBgColor = match($status) {
                                'Active 24 Below' => '#e0edfb',  // Blue
                                'Active 25 Above' => '#c7effb',  // Green
                                'Call Attempt 24 Below' => '#c7f6fb',  // Yellow
                                'Call Attempt 25 Above' => '#c7f7f1', // Red
                                default => '#E5E7EB',  // Gray (fallback)
                            };

                            // Simplify status labels for display
                            $displayStatus = str_replace(['Active ', 'Call Attempt '], ['', 'Call '], $status);
                        @endphp

                        <!-- Clickable Slide-Over Trigger -->
                        <div class="mb-4 cursor-pointer" wire:click="openActiveLeadsJajaSlideOver('{{ $status }}')">
                            <div class="progress-info">
                                <span>{{ $displayStatus }}</span>
                                <span>{{ $count }} ({{ $percentage }}%)</span>
                            </div>

                            <div class="progress-bar" style="background-color: {{ $barBgColor }};">
                                <div class="progress-fill" style="width: {{ $percentage }}%; background-color: {{ $color }};"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Separator Line -->
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<div class="lead-divider"></div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;


                <div class="mt-4 lead-progress">
                    <h3 class="text-lg font-bold text-center text-gray-800">Summary Salesperson</h3>
                    @foreach ($transferStagesDataJaja as $stage => $count)
                        @php
                            $percentage = $totalTransferLeadsJaja > 0 ? round(($count / $totalTransferLeadsJaja) * 100, 2) : 0;

                            $color = match($stage) {
                                'Transfer' => '#ffde59',  /* Light Yellow */
                                'Demo' => '#ffa83c',      /* Orange */
                                'Follow Up' => '#ff914d', /* Dark Orange */
                                default => '#D1D5DB',     // Gray
                            };

                            $barBgColor = match($stage) {
                                'Transfer' => '#fff8dd',  /* Light Yellow bg */
                                'Demo' => '#ffedd7',      /* Orange bg */
                                'Follow Up' => '#ffe8da', /* Dark Orange bg */
                                default => '#E5E7EB',     // Gray bg
                            };
                        @endphp

                        <!-- Clickable Slide-Over Trigger -->
                        <div class="mb-4 cursor-pointer" wire:click="openTransferLeadsJajaSlideOver('{{ $stage }}')">
                            <div class="progress-info">
                                <span>{{ $stage }}</span>
                                <span>{{ $count }} ({{ $percentage }}%)</span>
                            </div>

                            <div class="progress-bar" style="background-color: {{ $barBgColor }};">
                                <div class="progress-fill" style="width: {{ $percentage }}%; background-color: {{ $color }};"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Separator Line -->
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<div class="lead-divider"></div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                <!-- Lead Status Summary -->

                <div class="mt-4 lead-progress">
                    <h3 class="mt-6 text-lg font-bold text-center text-gray-800">Summary Inactive</h3>

                    @foreach ($inactiveLeadDataJaja as $status => $count)
                        @php
                            $percentage = $totalInactiveLeadsJaja > 0 ? round(($count / $totalInactiveLeadsJaja) * 100, 2) : 0;

                            $color = match($status) {
                                'Junk' => '#545454',
                                'Lost' => '#737373',
                                'On Hold' => '#99948f',
                                'No Response' => '#c8c4bd',
                                default => '#D1D5DB',
                            };

                            $barBgColor = match($status) {
                                'Junk' => '#dcdcdc',
                                'Lost' => '#e2e2e2',
                                'On Hold' => '#eae9e8',
                                'No Response' => '#f3f3f1',
                                default => '#E5E7EB',
                            };
                        @endphp

                        <!-- Clickable Slide-Over Trigger -->
                        <div class="mb-4 cursor-pointer" wire:click="openInactiveLeadsJajaSlideOver('{{ $status }}')">
                            <div class="progress-info">
                                <span>{{ $status }}</span>
                                <span>{{ $count }} ({{ $percentage }}%)</span>
                            </div>

                            <div class="progress-bar" style="background-color: {{ $barBgColor }};">
                                <div class="progress-fill" style="width: {{ $percentage }}%; background-color: {{ $color }};"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="p-6 bg-white rounded-lg shadow-lg" wire:poll.1s>
            <div class="flex items-center space-x-3">
                <p class="m-0 lead-label">Total Leads (Admin Shahilah):</p>&nbsp;
                <p class="m-0 lead-number">{{ array_sum($adminShahilahLeadStats) }}</p>
            </div>

            <div class="lead-summary-box">
                <div class="lead-progress">
                    <h3 class="mb-4 text-center status-title">Lead Categories (Admin Shahilah)</h3>

                    <div class="flex justify-center w-full space-x-10">
                        @foreach ($adminShahilahLeadStats as $category => $count)
                            @php
                                $totalLeads = array_sum($adminShahilahLeadStats);
                                $percentage = $totalLeads > 0 ? round(($count / $totalLeads) * 100, 2) : 0;

                                $categoryColors = [
                                    'Active'   => '#00c7b1',  // Teal
                                    'Sales'    => '#fb8c00',  // Orange
                                    'Inactive' => '#a6a6a6',  // Gray
                                ];

                                $categoryBgColors = [
                                    'Active'   => '#c8f0eb',
                                    'Sales'    => '#fae4c8',
                                    'Inactive' => '#e9e9e9',
                                ];

                                $color = $categoryColors[$category] ?? '#6B7280';
                                $bgColor = $categoryBgColors[$category] ?? '#E5E7EB';
                            @endphp

                            <div class="relative text-center cursor-pointer group" wire:click="openShahilahLeadCategorySlideOver('{{ $category }}')">
                                <div class="relative w-28 h-28">
                                    <svg width="100" height="100" viewBox="0 0 36 36">
                                        <circle cx="18" cy="18" r="14" stroke="{{ $bgColor }}" stroke-opacity="0.3" stroke-width="5" fill="none"></circle>
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
                                    <!-- Hover message (hidden by default) -->
                                    <div class="hover-message">
                                        {{ $percentage }}%
                                    </div>
                                </div>
                                <p class="mt-2 text-sm text-gray-700">{{ ucfirst($category) }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Separator Line -->
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<div class="lead-divider"></div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                <div class="lead-progress">
                    <h3 class="text-lg font-bold text-center text-gray-800">Summary Active</h3>

                    <div class="mt-4">
                        @foreach ($activeLeadsDataShahilah as $status => $count)
                            @php
                                $percentage = $totalActiveLeadsShahilah > 0 ? round(($count / $totalActiveLeadsShahilah) * 100, 2) : 0;

                                // Color mapping from your existing code
                                $color = match($status) {
                                    'Active 24 Below' => '#7bbaff',  // Blue
                                    'Active 25 Above' => '#00c6ff',  // Green
                                    'Call Attempt 24 Below' => '#00ebff',  // Yellow
                                    'Call Attempt 25 Above' => '#00edd1', // Red
                                    default => '#D1D5DB',  // Gray (fallback)
                                };
                                $barBgColor = match($status) {
                                    'Active 24 Below' => '#e0edfb',  // Blue
                                    'Active 25 Above' => '#c7effb',  // Green
                                    'Call Attempt 24 Below' => '#c7f6fb',  // Yellow
                                    'Call Attempt 25 Above' => '#c7f7f1', // Red
                                    default => '#E5E7EB',  // Gray (fallback)
                                };

                                // Simplify status labels for display
                                $displayStatus = str_replace(['Active ', 'Call Attempt '], ['', 'Call '], $status);
                            @endphp

                            <!-- Clickable Slide-Over Trigger -->
                            <div class="mb-4 cursor-pointer" wire:click="openActiveLeadsShahilahSlideOver('{{ $status }}')">
                                <div class="progress-info">
                                    <span>{{ $displayStatus }}</span>
                                    <span>{{ $count }} ({{ $percentage }}%)</span>
                                </div>

                                <div class="progress-bar" style="background-color: {{ $barBgColor }};">
                                    <div class="progress-fill" style="width: {{ $percentage }}%; background-color: {{ $color }};"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Separator Line -->
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<div class="lead-divider"></div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                <div class="lead-progress">
                    <h3 class="text-lg font-bold text-center text-gray-800">Summary Salesperson</h3>
                    <div class="mt-4">
                        @foreach ($transferStagesDataShahilah as $stage => $count)
                            @php
                                $percentage = $totalTransferLeadsShahilah > 0 ? round(($count / $totalTransferLeadsShahilah) * 100, 2) : 0;

                                $color = match($stage) {
                                    'Transfer' => '#ffde59',  /* Light Yellow */
                                    'Demo' => '#ffa83c',      /* Orange */
                                    'Follow Up' => '#ff914d', /* Dark Orange */
                                    default => '#D1D5DB',     // Gray
                                };

                                $barBgColor = match($stage) {
                                    'Transfer' => '#fff8dd',  /* Light Yellow bg */
                                    'Demo' => '#ffedd7',      /* Orange bg */
                                    'Follow Up' => '#ffe8da', /* Dark Orange bg */
                                    default => '#E5E7EB',     // Gray bg
                                };
                            @endphp

                            <!-- Clickable Slide-Over Trigger -->
                            <div class="mb-4 cursor-pointer" wire:click="openTransferLeadsShahilahSlideOver('{{ $stage }}')">
                                <div class="progress-info">
                                    <span>{{ $stage }}</span>
                                    <span>{{ $count }} ({{ $percentage }}%)</span>
                                </div>

                                <div class="progress-bar" style="background-color: {{ $barBgColor }};">
                                    <div class="progress-fill" style="width: {{ $percentage }}%; background-color: {{ $color }};"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Separator Line -->
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<div class="lead-divider"></div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                <!-- Lead Status Summary -->
                <div class="lead-progress">
                    <h3 class="text-lg font-bold text-center text-gray-800">Summary Inactive</h3>

                    <div class="mt-4">
                        @foreach ($inactiveLeadDataShahilah as $status => $count)
                            @php
                                $percentage = $totalInactiveLeadsShahilah > 0 ? round(($count / $totalInactiveLeadsShahilah) * 100, 2) : 0;

                                $color = match($status) {
                                    'Junk' => '#545454',
                                    'Lost' => '#737373',
                                    'On Hold' => '#99948f',
                                    'No Response' => '#c8c4bd',
                                    default => '#D1D5DB',
                                };

                                $barBgColor = match($status) {
                                    'Junk' => '#dcdcdc',
                                    'Lost' => '#e2e2e2',
                                    'On Hold' => '#eae9e8',
                                    'No Response' => '#f3f3f1',
                                    default => '#E5E7EB',
                                };
                            @endphp

                            <!-- Clickable Slide-Over Trigger -->
                            <div class="mb-4 cursor-pointer" wire:click="openInactiveLeadsShahilahSlideOver('{{ $status }}')">
                                <div class="progress-info">
                                    <span>{{ $status }}</span>
                                    <span>{{ $count }} ({{ $percentage }}%)</span>
                                </div>

                                <div class="progress-bar" style="background-color: {{ $barBgColor }};">
                                    <div class="progress-fill" style="width: {{ $percentage }}%; background-color: {{ $color }};"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 bg-white rounded-lg shadow-lg" wire:poll.1s>
            <div class="flex items-center space-x-3">
                <p class="m-0 lead-label">Total Leads (Admin Afifah):</p>&nbsp;
                <p class="m-0 lead-number">{{ array_sum($adminAfifahLeadStats) }}</p>
            </div>

            <div class="lead-summary-box">
                <div class="lead-progress">
                    <h3 class="mb-4 text-center status-title">Lead Categories (Admin Afifah)</h3>

                    <div class="flex justify-center w-full space-x-10">
                        @foreach ($adminAfifahLeadStats as $category => $count)
                            @php
                                $totalLeads = array_sum($adminAfifahLeadStats);
                                $percentage = $totalLeads > 0 ? round(($count / $totalLeads) * 100, 2) : 0;

                                $categoryColors = [
                                    'Active'   => '#00c7b1',  // Teal
                                    'Sales'    => '#fb8c00',  // Orange
                                    'Inactive' => '#a6a6a6',  // Gray
                                ];

                                $categoryBgColors = [
                                    'Active'   => '#c8f0eb',
                                    'Sales'    => '#fae4c8',
                                    'Inactive' => '#e9e9e9',
                                ];

                                $color = $categoryColors[$category] ?? '#6B7280';
                                $bgColor = $categoryBgColors[$category] ?? '#E5E7EB';
                            @endphp

                            <div class="relative text-center cursor-pointer group" wire:click="openAfifahLeadCategorySlideOver('{{ $category }}')">
                                <div class="relative w-28 h-28">
                                    <svg width="100" height="100" viewBox="0 0 36 36">
                                        <circle cx="18" cy="18" r="14" stroke="{{ $bgColor }}" stroke-opacity="0.3" stroke-width="5" fill="none"></circle>
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
                                    <!-- Hover message (hidden by default) -->
                                    <div class="hover-message">
                                        {{ $percentage }}%
                                    </div>
                                </div>
                                <p class="mt-2 text-sm text-gray-700">{{ ucfirst($category) }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Separator Line -->
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<div class="lead-divider"></div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                <div class="lead-progress">
                    <h3 class="text-lg font-bold text-center text-gray-800">Summary Active</h3>

                    <div class="mt-4">
                        @foreach ($activeLeadsDataAfifah as $status => $count)
                            @php
                                $percentage = $totalActiveLeadsAfifah > 0 ? round(($count / $totalActiveLeadsAfifah) * 100, 2) : 0;

                                // Color mapping from your existing code
                                $color = match($status) {
                                    'Active 24 Below' => '#7bbaff',  // Blue
                                    'Active 25 Above' => '#00c6ff',  // Green
                                    'Call Attempt 24 Below' => '#00ebff',  // Yellow
                                    'Call Attempt 25 Above' => '#00edd1', // Red
                                    default => '#D1D5DB',  // Gray (fallback)
                                };
                                $barBgColor = match($status) {
                                    'Active 24 Below' => '#e0edfb',  // Blue
                                    'Active 25 Above' => '#c7effb',  // Green
                                    'Call Attempt 24 Below' => '#c7f6fb',  // Yellow
                                    'Call Attempt 25 Above' => '#c7f7f1', // Red
                                    default => '#E5E7EB',  // Gray (fallback)
                                };

                                // Simplify status labels for display
                                $displayStatus = str_replace(['Active ', 'Call Attempt '], ['', 'Call '], $status);
                            @endphp

                            <!-- Clickable Slide-Over Trigger -->
                            <div class="mb-4 cursor-pointer" wire:click="openActiveLeadsAfifahSlideOver('{{ $status }}')">
                                <div class="progress-info">
                                    <span>{{ $displayStatus }}</span>
                                    <span>{{ $count }} ({{ $percentage }}%)</span>
                                </div>

                                <div class="progress-bar" style="background-color: {{ $barBgColor }};">
                                    <div class="progress-fill" style="width: {{ $percentage }}%; background-color: {{ $color }};"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Separator Line -->
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<div class="lead-divider"></div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                <div class="lead-progress">
                    <h3 class="text-lg font-bold text-center text-gray-800">Summary Salesperson</h3>
                    <div class="mt-4">
                        @foreach ($transferStagesDataAfifah as $stage => $count)
                            @php
                                $percentage = $totalTransferLeadsAfifah > 0 ? round(($count / $totalTransferLeadsAfifah) * 100, 2) : 0;

                                $color = match($stage) {
                                    'Transfer' => '#ffde59',  /* Light Yellow */
                                    'Demo' => '#ffa83c',      /* Orange */
                                    'Follow Up' => '#ff914d', /* Dark Orange */
                                    default => '#D1D5DB',     // Gray
                                };

                                $barBgColor = match($stage) {
                                    'Transfer' => '#fff8dd',  /* Light Yellow bg */
                                    'Demo' => '#ffedd7',      /* Orange bg */
                                    'Follow Up' => '#ffe8da', /* Dark Orange bg */
                                    default => '#E5E7EB',     // Gray bg
                                };
                            @endphp

                            <!-- Clickable Slide-Over Trigger -->
                            <div class="mb-4 cursor-pointer" wire:click="openTransferLeadsAfifahSlideOver('{{ $stage }}')">
                                <div class="progress-info">
                                    <span>{{ $stage }}</span>
                                    <span>{{ $count }} ({{ $percentage }}%)</span>
                                </div>

                                <div class="progress-bar" style="background-color: {{ $barBgColor }};">
                                    <div class="progress-fill" style="width: {{ $percentage }}%; background-color: {{ $color }};"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Separator Line -->
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<div class="lead-divider"></div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                <!-- Lead Status Summary -->
                <div class="lead-progress">
                    <h3 class="text-lg font-bold text-center text-gray-800">Summary Inactive</h3>

                    <div class="mt-4">
                        @foreach ($inactiveLeadDataAfifah as $status => $count)
                            @php
                                $percentage = $totalInactiveLeadsAfifah > 0 ? round(($count / $totalInactiveLeadsAfifah) * 100, 2) : 0;

                                $color = match($status) {
                                    'Junk' => '#545454',
                                    'Lost' => '#737373',
                                    'On Hold' => '#99948f',
                                    'No Response' => '#c8c4bd',
                                    default => '#D1D5DB',
                                };

                                $barBgColor = match($status) {
                                    'Junk' => '#dcdcdc',
                                    'Lost' => '#e2e2e2',
                                    'On Hold' => '#eae9e8',
                                    'No Response' => '#f3f3f1',
                                    default => '#E5E7EB',
                                };
                            @endphp

                            <!-- Clickable Slide-Over Trigger -->
                            <div class="mb-4 cursor-pointer" wire:click="openInactiveLeadsAfifahSlideOver('{{ $status }}')">
                                <div class="progress-info">
                                    <span>{{ $status }}</span>
                                    <span>{{ $count }} ({{ $percentage }}%)</span>
                                </div>

                                <div class="progress-bar" style="background-color: {{ $barBgColor }};">
                                    <div class="progress-fill" style="width: {{ $percentage }}%; background-color: {{ $color }};"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

    <!-- Slide-over Modal -->
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
    >
        <div
            class="w-full h-full max-w-md p-6 overflow-y-auto bg-white shadow-xl"
            @click.away="open = false"
        >
            <!-- Header -->
            <br><br>
            <div class="flex items-center justify-between p-4 border-b">
                <h2 class="text-lg font-bold text-gray-800">{{ $slideOverTitle }}</h2>
                <button @click="open = false" class="text-2xl leading-none text-gray-500 hover:text-gray-700">&times;</button>
            </div>

            <!-- Scrollable content -->
            <div class="flex-1 p-4 space-y-2 overflow-y-auto">
                @forelse ($leadList as $lead)
                    @php
                        $companyName = $lead->companyDetail->company_name ?? 'N/A';
                        $shortened = strtoupper(\Illuminate\Support\Str::limit($companyName, 20, '...'));
                        $encryptedId = \App\Classes\Encryptor::encrypt($lead->id);
                    @endphp

                    <a
                        href="{{ url('admin/leads/' . $encryptedId) }}"
                        target="_blank"
                        title="{{ $companyName }}"
                        class="block px-4 py-2 text-sm font-medium text-blue-600 transition border rounded bg-gray-50 hover:bg-blue-50 hover:text-blue-800"
                    >
                        {{ $shortened }}
                    </a>
                @empty
                    <div class="text-sm text-gray-500">No data found.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-filament::page>
