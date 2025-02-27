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
                padding: 12px;
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
            /* .hover-message {
                visibility: hidden;
            } */
            .group:hover .hover-message {
                visibility: visible;
                opacity: 1;
            }
        </style>
    </head>

    <div class="flex items-center mb-6">
        @if(auth()->user()->role_id == 1)
            <!-- Salesperson Filter -->
            <div>
                <select wire:model="selectedUser" id="userFilter" class="mt-1 border-gray-300 rounded-md shadow-sm">
                    <option value="">All Salespersons</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        @endif

        <!-- Month Filter (Added Margin) -->
        <div class="ml-10">  <!-- Manually added space using margin-left -->
            <input wire:model="selectedMonth" type="month" id="monthFilter" class="mt-1 border-gray-300 rounded-md shadow-sm">
        </div>
    </div>

    <!-- Call FetchLeads Data -->
    <div class="wrapper-container">
        <div class="flex items-center space-x-2">
            <i class="text-lg text-gray-500 fa fa-bookmark"></i>&nbsp;&nbsp;
            <h2 class="text-lg font-bold text-gray-800">All Leads</h2>
        </div>
        <div class="grid-container">
            <!-- Total Leads Box (1/4) -->
            <div class="lead-card">
                <div class="icon-container">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <p class="lead-number">{{ $totalLeads }}</p>
                <p class="lead-text">Total Leads</p>
            </div>

            <!-- Status Breakdown (1/4) -->
            <div class="p-6 rounded-lg shadow bg-gray-50">
                <h3 class="mb-4 text-sm font-semibold text-center text-gray-700 uppercase">Status</h3>

                <div class="flex justify-center space-x-10"> <!-- Increased spacing between circles -->
                    <!-- Active Leads Doughnut -->
                    <div class="flex justify-center space-x-10"> <!-- Ensures proper spacing -->
                        <!-- Active Leads Doughnut -->
                        <div class="text-center">
                            <div class="relative w-28 h-28 group"> <!-- Group for hover -->
                                <svg width="130" height="130" viewBox="0 0 36 36">
                                    <!-- Background Circle -->
                                    <circle cx="18" cy="18" r="14" stroke="#E5E7EB" stroke-width="5" fill="none"></circle>
                                    <!-- Progress Indicator -->
                                    <circle cx="18" cy="18" r="14" stroke="#3B82F6" stroke-width="5" fill="none"
                                            stroke-dasharray="88"
                                            stroke-dashoffset="{{ 88 - (88 * ($activePercentage / 100)) }}"
                                            stroke-linecap="round"
                                            transform="rotate(-90 18 18)">
                                    </circle>
                                </svg>
                                <!-- Hover Message for Count -->
                                <div class="hover-message">
                                    {{ $activeLeads }} Leads
                                </div>
                                <!-- Percentage in the Center -->
                                <div class="absolute inset-0 flex items-center justify-center text-xl font-bold text-gray-900">
                                    {{ $activePercentage }}%
                                </div>
                            </div>
                            <p class="mt-2 text-sm text-gray-700">Active</p>
                        </div>

                        <!-- Inactive Leads Doughnut -->
                        <div class="text-center">
                            <div class="relative w-28 h-28 group"> <!-- Group for hover -->
                                <svg width="130" height="130" viewBox="0 0 36 36">
                                    <!-- Background Circle -->
                                    <circle cx="18" cy="18" r="14" stroke="#E5E7EB" stroke-width="5" fill="none"></circle>
                                    <!-- Progress Indicator -->
                                    <circle cx="18" cy="18" r="14" stroke="#6B7280" stroke-width="5" fill="none"
                                            stroke-dasharray="100, 100"
                                            stroke-dashoffset="{{ 100 - $inactivePercentage }}"
                                            stroke-linecap="round"
                                            transform="rotate(-90 18 18)"></circle>
                                </svg>
                                <!-- Hover Message for Count -->
                                <div class="hover-message">
                                    {{ $inactiveLeads }} Leads
                                </div>
                                <!-- Percentage in the Center -->
                                <div class="absolute inset-0 flex items-center justify-center text-xl font-bold text-gray-900">
                                    {{ $inactivePercentage }}%
                                </div>
                            </div>
                            <p class="mt-2 text-sm text-gray-700">Inactive</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Company Size Distribution (2/4) -->
            <div class="company-size-container">
                <h3 class="title">Company Size</h3>
                <div class="bars-container">
                    @foreach($companySizeData as $size => $count)
                        @php
                            $percentage = round(($count / max($totalLeads, 1)) * 100, 2);
                            $barColor = match($loop->index) {
                                0 => '#EF4444',  // Small - Red
                                1 => '#FB923C',  // Medium - Orange
                                2 => '#FACC15',  // Large - Yellow
                                default => '#10B981', // Enterprise - Green
                            };
                        @endphp

                        <div class="bar-group">
                            <p class="percentage-label">{{ $count }}</p>
                            <div class="bar-wrapper">
                                <div class="bar-fill" style="height: {{ $percentage }}%; background-color: {{ $barColor }};"></div>
                            </div>
                            <p class="size-label">{{ ucfirst($size) }}</p>
                            <div class="hover-message">{{ $percentage }}%</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div class="p-6 bg-white rounded-lg shadow-lg" wire:poll.1s>
            <div class="flex items-center mb-6 space-x-2">
                <i class="text-lg text-gray-500 fa fa-bookmark"></i>&nbsp;&nbsp;
                <h3 class="text-xl font-bold text-gray-800">Active</h3>
            </div>
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
                    @foreach ($stagesData as $stage => $count)
                        @php
                            $percentage = $totalActiveLeads > 0 ? round(($count / $totalActiveLeads) * 100, 2) : 0;
                            $color = match($stage) {
                                'Transfer' => '#3B82F6',  /* Light Blue */
                                'Demo' => '#6366F1',      /* Purple */
                                'Follow Up' => '#1E40AF', /* Dark Blue */
                                default => '#D1D5DB',    /* Gray */
                            };
                        @endphp

                        <!-- Stage Title & Count -->
                        <div class="progress-info">
                            <span>{{ ucfirst($stage) }}</span>
                            <span>{{ $count }} ({{ $percentage }}%)</span>
                        </div>

                        <!-- Progress Bar -->
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ $percentage }}%; background-color: {{ $color }};"></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="p-6 bg-white rounded-lg shadow-lg" wire:poll.1s>
            <div class="flex items-center mb-6 space-x-2">
                <i class="text-lg text-gray-500 fa fa-bookmark"></i>&nbsp;&nbsp;
                <h3 class="text-xl font-bold text-gray-800">Inactive</h3>
            </div>
            <div class="lead-summary-box">
                <div class="lead-count">
                    <p class="lead-number">{{ $totalInactiveLeads }}</p>
                    <p class="lead-label">Total Inactive Leads</p>
                </div>

                <div class="lead-divider"></div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                <div class="lead-progress">
                    <h3 class="status-title">Status</h3>
                    @foreach ($inactiveStatusData as $status => $count)
                        @php
                            $percentage = $totalInactiveLeads > 0 ? round(($count / $totalInactiveLeads) * 100, 2) : 0;
                            $color = match($status) {
                                'Closed' => '#10B981',
                                'Lost' => '#EF4444',
                                'On Hold' => '#9ba2af',
                                'No Response' => '#71797E',
                                default => '#D1D5DB',
                            };
                        @endphp

                        <div class="progress-info">
                            <span>{{ ucfirst($status) }}</span>
                            <span>{{ $count }} ({{ $percentage }}%)</span>
                        </div>

                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ $percentage }}%; background-color: {{ $color }};"></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="p-6 bg-white rounded-lg shadow" wire:poll.1s>
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-2">
                    <i class="text-lg text-gray-500 fa fa-bookmark"></i>&nbsp;&nbsp;
                    <h2 class="text-lg font-bold text-gray-800">Transfer</h2>
                </div>
                <span class="text-lg font-bold text-gray-500">Count: {{ $totalTransferLeads }}</span>
            </div>

            <div class="flex justify-center space-x-8">
                @foreach ($transferStatusData as $status => $count)
                    @php
                        $percentage = $totalTransferLeads > 0 ? round(($count / $totalTransferLeads) * 100, 2) : 0;
                        $color = match($status) {
                            'RFQ-Transfer' => '#FACC15', // Yellow
                            'Pending Demo' => '#F59E0B', // Orange-Yellow
                            'Demo Cancelled' => '#FB923C', // Orange
                            default => '#D1D5DB', // Fallback Gray
                        };
                    @endphp

                    <div class="text-center">
                        <div class="relative w-28 h-28">
                            <svg width="130" height="130" viewBox="0 0 36 36">
                                <!-- Background Circle -->
                                <circle cx="18" cy="18" r="14" stroke="#E5E7EB" stroke-width="5" fill="none"></circle>
                                <!-- Progress Indicator -->
                                <circle cx="18" cy="18" r="14" stroke="{{ $color }}" stroke-width="5" fill="none"
                                        stroke-dasharray="88"
                                        stroke-dashoffset="{{ 88 - (88 * ($percentage / 100)) }}"
                                        stroke-linecap="round"
                                        transform="rotate(-90 18 18)">
                                </circle>
                            </svg>
                            <!-- Number in Center -->
                            <div class="absolute inset-0 flex items-center justify-center text-lg font-bold text-gray-900">
                                {{ $count }}
                            </div>
                        </div>
                        <p class="mt-2 text-sm text-gray-700">{{ $status }}</p>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="p-6 bg-white rounded-lg shadow" wire:poll.1s>
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-2">
                    <i class="text-lg text-gray-500 fa fa-bookmark"></i>&nbsp;&nbsp;
                    <h2 class="text-lg font-bold text-gray-800">Follow Up</h2>
                </div>
                <span class="text-lg font-bold text-gray-500">Count: {{ $totalFollowUpLeads }}</span>
            </div>

            <div class="flex justify-center space-x-8">
                @foreach ($followUpStatusData as $status => $count)
                    @php
                        $percentage = $totalFollowUpLeads > 0 ? round(($count / $totalFollowUpLeads) * 100, 2) : 0;
                        $color = match($status) {
                            'RFQ-Follow Up' => '#6B7280', // Gray
                            'Hot' => '#EF4444', // Red
                            'Warm' => '#FB923C', // Orange
                            'Cold' => '#3B82F6', // Blue
                            default => '#D1D5DB', // Fallback Gray
                        };
                    @endphp

                    <div class="text-center">
                        <div class="relative w-28 h-28">
                            <svg width="130" height="130" viewBox="0 0 36 36">
                                <!-- Background Circle -->
                                <circle cx="18" cy="18" r="14" stroke="#E5E7EB" stroke-width="5" fill="none"></circle>
                                <!-- Progress Indicator -->
                                <circle cx="18" cy="18" r="14" stroke="{{ $color }}" stroke-width="5" fill="none"
                                        stroke-dasharray="88"
                                        stroke-dashoffset="{{ 88 - (88 * ($percentage / 100)) }}"
                                        stroke-linecap="round"
                                        transform="rotate(-90 18 18)">
                                </circle>
                            </svg>
                            <!-- Number in Center -->
                            <div class="absolute inset-0 flex items-center justify-center text-lg font-bold text-gray-900">
                                {{ $count }}
                            </div>
                        </div>
                        <p class="mt-2 text-sm text-gray-700">{{ $status }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament::page>
