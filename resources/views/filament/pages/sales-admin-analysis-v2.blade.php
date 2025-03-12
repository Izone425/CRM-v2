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
                font-size: 3rem;
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
        </style>
    </head>
    <div class="flex items-center mb-6">
        <!-- Month Filter (Added Margin) -->
        <div class="ml-10">  <!-- Manually added space using margin-left -->
            <input wire:model="selectedMonth" type="month" id="monthFilter" class="mt-1 border-gray-300 rounded-md shadow-sm">
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div class="p-6 bg-white rounded-lg shadow-lg" wire:poll.1s>
            <div class="grid-container">
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
                            ['label' => 'New', 'percentage' => $newPercentage, 'count' => $newLeads, 'color' => '#3B82F6'],
                            ['label' => 'Jaja', 'percentage' => $jajaPercentage, 'count' => $jajaLeads, 'color' => '#EF4444'],
                            ['label' => 'Afifah', 'percentage' => $afifahPercentage, 'count' => $afifahLeads, 'color' => '#10B981']
                        ] as $data)
                            <div class="relative text-center group">
                                <div class="relative w-28 h-28">
                                    <svg width="130" height="130" viewBox="0 0 36 36">
                                        <circle cx="18" cy="18" r="14" stroke="#E5E7EB" stroke-width="5" fill="none"></circle>
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

        <div class="p-6 bg-white rounded-lg shadow-lg" wire:poll.1s>
            <div class="lead-summary-box">
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
                                ['label' => 'New', 'count' => $categoriesData['New'] ?? 0, 'color' => '#3B82F6'],
                                ['label' => 'Active', 'count' => $categoriesData['Active'] ?? 0, 'color' => '#10B981'],
                                ['label' => 'Sales', 'count' => $categoriesData['Sales'] ?? 0, 'color' => '#FACC15'],
                                ['label' => 'Inactive', 'count' => $categoriesData['Inactive'] ?? 0, 'color' => '#9CA3AF']
                            ] as $data)
                                @php
                                    $percentage = $totalLeads > 0 ? round(($data['count'] / $totalLeads) * 100, 2) : 0;
                                @endphp
                                <div class="relative text-center group">
                                    <div class="relative w-28 h-28">
                                        <svg width="100" height="100" viewBox="0 0 36 36">
                                            <circle cx="18" cy="18" r="14" stroke="#E5E7EB" stroke-width="5" fill="none"></circle>
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

        <div class="p-6 bg-white rounded-lg shadow-lg" wire:poll.1s>
            <div class="lead-summary-box">
                <!-- Left Section (30%) -->
                <div class="lead-count">
                    <p class="lead-number">{{ array_sum($adminJajaLeadStats) }}</p>
                    <p class="lead-label">Total Leads <br> (Admin Jaja)</p>
                </div>

                <!-- Middle Divider -->
                <div class="lead-divider"></div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                <!-- Right Section (65%) -->
                <div class="lead-progress">
                    <h3 class="status-title">Lead Categories (Admin Jaja)</h3>

                    @foreach ($adminJajaLeadStats as $category => $count)
                        @php
                            $totalLeads = array_sum($adminJajaLeadStats); // Calculate total leads
                            $percentage = $totalLeads > 0 ? round(($count / $totalLeads) * 100, 2) : 0;

                            // Define category colors
                            $categoryColors = [
                                'Active' => '#10B981',     // Green
                                'Sales' => '#3B82F6',        // Blue
                                'Inactive' => '#9CA3AF',   // Gray
                            ];
                            $color = $categoryColors[$category] ?? '#6B7280'; // Default fallback color
                        @endphp

                        <!-- Category Name & Count -->
                        <div class="progress-info">
                            <span>{{ ucfirst($category) }}</span>
                            <span>{{ $count }} ({{ $percentage }}%)</span>
                        </div>

                        <!-- Progress Bar -->
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ $percentage }}%; background-color: {{ $color }};"></div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Separator Line -->
            <div class="mt-6 mb-6 border-t border-gray-300"></div>

            <!-- Lead Status Summary -->
            <h3 class="text-lg font-bold text-center text-gray-800">Summary Active</h3>

            <div class="flex justify-center mt-4 space-x-6">
                @foreach ($activeLeadsDataJaja as $status => $count)
                    @php
                        $percentage = $totalActiveLeadsJaja > 0 ? round(($count / $totalActiveLeadsJaja) * 100, 2) : 0;
                        $color = match($status) {
                            'Active 24 Below' => '#3B82F6',  // Blue
                            'Active 25 Above' => '#10B981',  // Green
                            'Call Attempt 24 Below' => '#FACC15',  // Yellow
                            'Call Attempt 25 Above' => '#EF4444', // Red
                            default => '#D1D5DB',  // Gray (fallback)
                        };
                    @endphp

                    <div class="relative text-center group">
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

                            <div class="absolute inset-0 flex items-center justify-center text-lg font-bold text-gray-900">
                                {{ $count }}
                            </div>
                            <div class=" hover-message">
                                {{ $percentage }}%
                            </div>
                        </div>
                        <p class="mt-2 text-sm text-center text-gray-700" style="max-width: 130px; word-wrap: break-word; white-space: normal;">
                            {{ $status }}
                        </p>
                    </div>
                @endforeach
            </div>

            <!-- Separator Line -->
            <div class="mt-6 mb-6 border-t border-gray-300"></div>

            <!-- Lead Status Summary -->
            <h3 class="text-lg font-bold text-center text-gray-800">Summary Salesperson</h3>

            <div class="flex justify-center mt-4 space-x-6">
                @foreach ($transferStagesDataJaja as $stage => $count)
                    @php
                        $percentage = $totalTransferLeadsJaja > 0 ? round(($count / $totalTransferLeadsJaja) * 100, 2) : 0;
                        $color = match($stage) {
                            'Transfer' => '#3B82F6',  /* Light Blue */
                            'Demo' => '#6366F1',      /* Purple */
                            'Follow Up' => '#1E40AF', /* Dark Blue */
                            default => '#D1D5DB',     // Gray
                        };
                    @endphp

                    <div class="relative text-center group">
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
                            <div class=" hover-message">
                                {{ $percentage }}%
                            </div>
                        </div>
                        <p class="mt-2 text-sm text-center text-gray-700" style="max-width: 130px; word-wrap: break-word; white-space: normal;">
                            {{ $stage }}
                        </p>
                    </div>
                @endforeach
            </div>

            <!-- Separator Line -->
            <div class="mt-6 mb-6 border-t border-gray-300"></div>

            <!-- Lead Status Summary -->
            <h3 class="text-lg font-bold text-center text-gray-800">Summary Inactive</h3>

            <div class="flex justify-center mt-4 space-x-6">
                @foreach ($inactiveLeadDataJaja as $status => $count)
                    @php
                        $percentage = $totalInactiveLeadsJaja > 0 ? round(($count / $totalInactiveLeadsJaja) * 100, 2) : 0;
                        $color = match($status) {
                            'Junk' => '#BBBCB6',
                            'Lost' => '#cccccc',
                            'On Hold' => '#bbbbbb',
                            'No Response' => '#aaaaaa',
                            default => '#D1D5DB',
                        };
                    @endphp

                    <div class="relative text-center group">
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
                            <div class=" hover-message">
                                {{ $percentage }}%
                            </div>
                        </div>
                        <p class="mt-2 text-sm text-center text-gray-700" style="max-width: 130px; word-wrap: break-word; white-space: normal;">
                            {{ $status }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="p-6 bg-white rounded-lg shadow-lg" wire:poll.1s>
            <div class="lead-summary-box">
                <!-- Left Section (30%) -->
                <div class="lead-count">
                    <p class="lead-number">{{ array_sum($adminAfifahLeadStats) }}</p>
                    <p class="lead-label">Total Leads <br> (Admin Afifah)</p>
                </div>

                <!-- Middle Divider -->
                <div class="lead-divider"></div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                <!-- Right Section (65%) -->
                <div class="lead-progress">
                    <h3 class="status-title">Lead Categories (Admin Afifah)</h3>

                    @foreach ($adminAfifahLeadStats as $category => $count)
                        @php
                            $totalLeads = array_sum($adminAfifahLeadStats); // Calculate total leads
                            $percentage = $totalLeads > 0 ? round(($count / $totalLeads) * 100, 2) : 0;

                            // Define category colors
                            $categoryColors = [
                                'Active' => '#10B981',     // Green
                                'Sales' => '#3B82F6',        // Blue
                                'Inactive' => '#9CA3AF',   // Gray
                            ];
                            $color = $categoryColors[$category] ?? '#6B7280'; // Default fallback color
                        @endphp

                        <!-- Category Name & Count -->
                        <div class="progress-info">
                            <span>{{ ucfirst($category) }}</span>
                            <span>{{ $count }} ({{ $percentage }}%)</span>
                        </div>

                        <!-- Progress Bar -->
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ $percentage }}%; background-color: {{ $color }};"></div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-6 mb-6 border-t border-gray-300"></div>

            <!-- Lead Status Summary -->
            <h3 class="text-lg font-bold text-center text-gray-800">Summary Active</h3>

            <div class="flex justify-center mt-4 space-x-6">
                @foreach ($activeLeadsDataAfifah as $status => $count)
                    @php
                        $percentage = $totalActiveLeadsAfifah > 0 ? round(($count / $totalActiveLeadsAfifah) * 100, 2) : 0;
                        $color = match($status) {
                            'Active 24 Below' => '#3B82F6',  // Blue
                            'Active 25 Above' => '#10B981',  // Green
                            'Call Attempt 24 Below' => '#FACC15',  // Yellow
                            'Call Attempt 25 Above' => '#EF4444', // Red
                            default => '#D1D5DB',  // Gray (fallback)
                        };
                    @endphp

                    <div class="relative text-center group">
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
                            <!-- Hover Message (Styled & Positioned Properly) -->
                            <div class=" hover-message">
                                {{ $percentage }}%
                            </div>
                        </div>
                        <p class="mt-2 text-sm text-center text-gray-700" style="max-width: 130px; word-wrap: break-word; white-space: normal;">
                            {{ $status }}
                        </p>
                    </div>
                @endforeach
            </div>

            <!-- Separator Line -->
            <div class="mt-6 mb-6 border-t border-gray-300"></div>

            <!-- Lead Status Summary -->
            <h3 class="text-lg font-bold text-center text-gray-800">Summary Salesperson</h3>

            <div class="flex justify-center mt-4 space-x-6">
                @foreach ($transferStagesDataAfifah as $stage => $count)
                    @php
                        $percentage = $totalTransferLeadsAfifah > 0 ? round(($count / $totalTransferLeadsAfifah) * 100, 2) : 0;
                        $color = match($stage) {
                            'Transfer' => '#3B82F6',  /* Light Blue */
                            'Demo' => '#6366F1',      /* Purple */
                            'Follow Up' => '#1E40AF', /* Dark Blue */
                            default => '#D1D5DB',     // Gray
                        };
                    @endphp

                    <div class="relative text-center group">
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
                            <div class=" hover-message">
                                {{ $percentage }}%
                            </div>
                        </div>
                        <p class="mt-2 text-sm text-center text-gray-700" style="max-width: 130px; word-wrap: break-word; white-space: normal;">
                            {{ $stage }}
                        </p>
                    </div>
                @endforeach
            </div>

            <!-- Separator Line -->
            <div class="mt-6 mb-6 border-t border-gray-300"></div>

            <!-- Lead Status Summary -->
            <h3 class="text-lg font-bold text-center text-gray-800">Summary Inactive</h3>

            <div class="flex justify-center mt-4 space-x-6">
                @foreach ($inactiveLeadDataAfifah as $status => $count)
                    @php
                        $percentage = $totalInactiveLeadsAfifah > 0 ? round(($count / $totalInactiveLeadsAfifah) * 100, 2) : 0;
                        $color = match($status) {
                            'Junk' => '#BBBCB6',
                            'Lost' => '#cccccc',
                            'On Hold' => '#bbbbbb',
                            'No Response' => '#aaaaaa',
                            default => '#D1D5DB',
                        };
                    @endphp

                    <div class="relative text-center group">
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
                            <div class=" hover-message">
                                {{ $percentage }}%
                            </div>
                        </div>
                        <p class="mt-2 text-sm text-center text-gray-700" style="max-width: 130px; word-wrap: break-word; white-space: normal;">
                            {{ $status }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament::page>
