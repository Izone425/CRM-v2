<x-filament::page>
    <head>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
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
                gap: 30px;
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
                width: 70px;
                height: 115px;
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
            .cursor-pointer:hover {
                transform: scale(1.02);
                transition: all 0.2s;
            }
        </style>
    </head>
    <div class="flex flex-col items-center justify-between mb-6 md:flex-row">
        <h1 class="text-2xl font-bold tracking-tight fi-header-heading text-gray-950 dark:text-white sm:text-3xl">Marketing Analysis</h1>
        <div class="flex items-center mb-6">
            @if(auth()->user()->role_id == 1 || auth()->user()->role_id == 3)
                <!-- Salesperson Filter -->
                <div>
                    <select wire:model="selectedUser" id="userFilter" class="mt-1 border-gray-300 rounded-md shadow-sm">
                        <option value="">All Salespersons</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <div>
                    <select wire:model="selectedLeadOwner" id="leadOwnerFilter" class="mt-1 border-gray-300 rounded-md shadow-sm">
                        <option value="">All Lead Owners</option>
                        @foreach ($leadOwners as $owner)
                            <option value="{{ $owner->id }}">{{ $owner->name }}</option>
                        @endforeach
                    </select>
                </div>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            @endif
            <div class="flex items-center ml-10 space-x-4">
                <div>
                    <input wire:model="startDate" type="date" id="startDate" class="mt-1 border-gray-300 rounded-md shadow-sm" />
                </div>
                &nbsp;- &nbsp;
                <div>
                    <input wire:model="endDate" type="date" id="endDate" class="mt-1 border-gray-300 rounded-md shadow-sm" />
                </div>
            </div>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <div class="relative ml-10">
                <!-- Toggle Button -->
                <div class="ml-10">
                    <button
                        wire:click="toggleUtmFilters"
                        class="px-6 py-2 text-white rounded-lg shadow-md hover:bg-blue-700" style= "background-color:#3730a3;"
                    >
                        {{ 'Advanced Filter' }}
                    </button>
                </div>

                <!-- UTM Filter Box -->
                @if ($showUtmFilters)
                    <div style="position: fixed; top: 150px; right: 20px; width: 450px; max-width: 90vw; background: white; box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1); border-radius: 8px 0 0 8px; padding: 16px; z-index: 50; border-left: 2px solid #ddd;">
                        <!-- Header -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <h3 style="font-size: 18px; font-weight: bold; color: #333;">UTM Filter Options</h3>
                            <button wire:click="toggleUtmFilters" style="font-size: 16px; color: #777; cursor: pointer;">✕</button>
                        </div>

                        <!-- Filter Inputs -->
                        <div style="display: grid; grid-template-columns: repeat(2, minmax(200px, 1fr)); gap: 12px;">
                            <input wire:model.debounce.500ms="utmCampaign" type="text" placeholder="UTM Campaign" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;" />
                            <input wire:model.debounce.500ms="utmAdgroup" type="text" placeholder="UTM Adgroup" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;" />
                            <input wire:model.debounce.500ms="utmTerm" type="text" placeholder="UTM Term" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;" />
                            <input wire:model.debounce.500ms="utmMatchtype" type="text" placeholder="Match Type" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;" />
                            <input wire:model.debounce.500ms="referrername" type="text" placeholder="Referrer Name" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;" />
                            <input wire:model.debounce.500ms="device" type="text" placeholder="Device" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;" />
                            <input wire:model.debounce.500ms="utmCreative" type="text" placeholder="UTM Creative" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;" />
                            <select
                                id="lead_code"
                                wire:model="selectedLeadCode"
                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >
                                <option value="">-- All --</option>
                                @foreach ($leadCodes as $code)
                                    <option value="{{ $code }}">{{ $code }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div class="p-6 bg-white rounded-lg shadow-lg" wire:poll.1s>
            <div class="flex items-center space-x-2">
                <i class="text-lg text-gray-500 fa fa-layer-group"></i>&nbsp;&nbsp;
                <h2 class="text-lg font-bold text-gray-800">Lead Breakdown</h2>
            </div>
            <div class="lead-summary-box">
                <div class="lead-count">
                    <p class="lead-number">{{ array_sum($leadStatusData) }}</p>
                    <p class="lead-label">Total Leads</p>
                </div>
                <div class="lead-divider"></div>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <div class="lead-progress">
                    <h3 class="status-title">Lead Status</h3>
                    <div style="max-height: 180px; overflow-y: auto; padding-right: 10px;">
                        @foreach ($leadStatusData as $status => $count)
                            @php
                                $percentage = array_sum($leadStatusData) > 0 ? round(($count / array_sum($leadStatusData)) * 100, 2) : 0;
                                $color = match($status) {
                                    'New' => '#D2E9D2',
                                    'RFQ-Transfer' => '#FFE29C',
                                    'Pending Demo' => '#FFD59C',
                                    'Under Review' => '#FF8A8A',
                                    'Demo Cancelled' => '#D0D3D9',
                                    'Demo-Assigned' => '#ff7c7c',
                                    'RFQ-Follow Up' => '#B3B0F7',
                                    'Hot' => '#FFD7D7',
                                    'Warm' => '#FFB066',
                                    'Cold' => '#B3F5F5',
                                    'Closed' => '#82CEC2',
                                    'Lost' => '#A0A0A0',
                                    'On Hold' => '#B3B3B3',
                                    'No Response' => '#C6C6C6',
                                    'Junk' => '#C6C6C6',
                                    default => '#E2E8F0',
                                };
                                $bgcolor = match($status) {
                                    'New' => '#F2FAF2',
                                    'RFQ-Transfer' => '#FFF9EB',
                                    'Pending Demo' => '#FFF5E9',
                                    'Under Review' => '#FFEDED',
                                    'Demo Cancelled' => '#F5F7F9',
                                    'Demo-Assigned' => '#fdcccc',
                                    'RFQ-Follow Up' => '#F0EEFC',
                                    'Hot' => '#FFF1F1',
                                    'Warm' => '#FFF2E3',
                                    'Cold' => '#ECFFFF',
                                    'Closed' => '#E3F7F5',
                                    'Lost' => '#E5E5E5',
                                    'On Hold' => '#ECECEC',
                                    'No Response' => '#F7F7F7',
                                    'Junk' => '#F7F7F7',
                                    default => '#E2E8F0',
                                };
                            @endphp
                            <div class="text-center cursor-pointer" wire:click="openLeadStatusSlideOver('{{ $status }}')">
                                <div class="progress-info">
                                    <span>{{ $status }}</span>
                                    <span>{{ $count }} ({{ $percentage }}%)</span>
                                </div>
                                <div class="progress-bar" style="background-color: {{ $bgcolor }};">
                                    <div class="progress-fill" style="width: {{ $percentage }}%; background-color: {{ $color }};"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 bg-white rounded-lg shadow-lg" wire:poll.1s>
            <div class="flex items-center space-x-2">
                <i class="text-lg text-gray-500 fa fa-layer-group"></i>&nbsp;&nbsp;
                <h2 class="text-lg font-bold text-gray-800">Lead Source Breakdown</h2>
            </div>
            <div class="bars-container" style="display: flex; overflow-x: auto; height: 250px; padding: 10px; align-items: flex-end; gap: 40px;">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                @php
                    $leadTypeCounts = $this->getLeadTypeCounts();
                    $totalLeadTypes = array_sum($leadTypeCounts);
                    $colors = ['#805ba7', '#3d78f4', '#4cc5fd', '#4dd8e3', '#00a1c5', '#02bb52', '#92dc61', '#fecd1a', '#5861a9'];
                @endphp

                @foreach($leadTypeCounts as $source => $count)
                    @php
                        $percentage = round(($count / max($totalLeadTypes, 1)) * 100, 2);
                        $barColor = $colors[$loop->index % count($colors)];
                    @endphp

                    <div class="cursor-pointer bar-group" wire:click="openLeadSourceSlideOver('{{ $source }}')">
                        <p class="percentage-label">{{ $count }}</p>
                        <div class="bar-wrapper" style="background-color: #F7F7F7;">
                            <div class="bar-fill" style="height: {{ $percentage }}%; background-color: {{ $barColor }};"></div>
                        </div>
                        <p class="size-label" style="word-wrap: break-word; white-space: normal; margin-top: 5px; min-height: 36px;">{{ $source }}</p>
                        <div class="hover-message">{{ $percentage }}%</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="p-6 bg-white rounded-lg shadow-lg" wire:poll.1s>
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-2">
                    <i class="text-lg text-gray-500 fa fa-layer-group"></i>&nbsp;&nbsp;
                    <h2 class="text-lg font-bold text-gray-800">Company Size Distribution</h2>
                </div>
                <i class="bi bi-question-circle" title="All Leads Breakdown without Junk (w/ & w/o demo id), On Hold (w/ & w/o demo id) & Lost (w/o demo id)"></i>
            </div>
            <div class="lead-summary-box">
                <div class="lead-count">
                    <p class="lead-number">{{ array_sum($companySizeDistribution) }}</p>
                    <p class="lead-label">Total Leads</p>
                </div>
                <div class="lead-divider"></div>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <div class="lead-progress">
                    <h3 class="status-title">Company Size</h3>
                    @foreach ($companySizeDistribution as $companySize => $count)
                    @php
                        $percentage = array_sum($companySizeDistribution) > 0
                            ? round(($count / array_sum($companySizeDistribution)) * 100, 2)
                            : 0;

                        $color = match($companySize) {
                            'Small' => '#D2E9D2',
                            'Medium' => '#FFE29C',
                            'Large' => '#FFD59C',
                            'Enterprise' => '#FF8A8A',
                            default => '#E2E8F0', // fallback color
                        };

                        $bgcolor = match($companySize) {
                            'Small' => '#F2FAF2',
                            'Medium' => '#FFF9EB',
                            'Large' => '#FFF5E9',
                            'Enterprise' => '#FFEDED',
                            default => '#F3F4F6', // fallback background
                        };
                    @endphp

                        <div wire:click="openCompanySizeSlideOver('{{ $companySize }}')" class="cursor-pointer">
                            <div class="progress-info">
                                <span>{{ ucfirst($companySize) }}</span>
                                <span>{{ $count }} ({{ $percentage }}%)</span>
                            </div>
                            <div class="progress-bar" style="background-color: {{ $bgcolor }};">
                                <div class="progress-fill" style="width: {{ $percentage }}%; background-color: {{ $color }};"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="p-6 bg-white rounded-lg shadow-lg" wire:poll.1s>
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-2">
                    <i class="text-lg text-gray-500 fa fa-layer-group"></i>&nbsp;&nbsp;
                    <h2 class="text-lg font-bold text-gray-800">Company Size Distribution (Demos)</h2>
                </div>
                <i class="bi bi-question-circle" title="With Demo Assigned, RFQ-Follow Up, Hot, Warm, Cold, Closed, Lost (w/ demo id) & No Response (w/ demo id)"></i>
            </div>
            <div class="lead-summary-box">
                <div class="lead-count">
                    <p class="lead-number">{{ array_sum($demoCompanySizeData) }}</p>
                    <p class="lead-label">Total Leads</p>
                </div>
                <div class="lead-divider"></div>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <div class="lead-progress">
                    <h3 class="status-title">Company Size</h3>
                    @foreach ($demoCompanySizeData as $companySize => $count)
                        @php
                            $percentage = array_sum($demoCompanySizeData) > 0 ? round(($count / array_sum($demoCompanySizeData)) * 100, 2) : 0;
                            $color = match($companySize) {
                                'Small' => '#D2E9D2',
                                'Medium' => '#FFE29C',
                                'Large' => '#FFD59C',
                                'Enterprise' => '#FF8A8A',
                                default => '#E2E8F0',
                            };
                            $bgcolor = match($companySize) {
                                'Small' => '#F2FAF2',
                                'Medium' => '#FFF9EB',
                                'Large' => '#FFF5E9',
                                'Enterprise' => '#FFEDED',
                                default => '#F9FAFB',
                            };
                        @endphp

                        <div wire:click="openDemoCompanySizeSlideOver('{{ $companySize }}')" class="cursor-pointer">
                            <div class="progress-info">
                                <span>{{ ucfirst($companySize) }}</span>
                                <span>{{ $count }} ({{ $percentage }}%)</span>
                            </div>
                            <div class="progress-bar" style="background-color: {{ $bgcolor }};">
                                <div class="progress-fill" style="width: {{ $percentage }}%; background-color: {{ $color }};"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="p-6 bg-white rounded-lg shadow-lg" wire:poll.1s>
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-2">
                    <i class="text-lg text-indigo-500 fa fa-calendar-check"></i>&nbsp;&nbsp;
                    <h2 class="text-lg font-bold text-gray-800">Demo Type Distribution</h2>
                </div>
                <i class="bi bi-question-circle" title="Actual Total Demo for the Period"></i>
            </div>

            <div class="lead-summary-box">
                <div class="lead-count">
                    <p class="lead-number">{{ array_sum($demoTypeData) }}</p>
                    <p class="lead-label">Total Leads</p>
                </div>
                <div class="lead-divider"></div>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <div class="flex flex-wrap justify-center gap-6">
                    @foreach ($demoTypeData as $type => $count)
                        @php
                            $total = array_sum($demoTypeData);
                            $percentage = $total > 0 ? round(($count / $total) * 100, 2) : 0;
                            $color = match($type) {
                                'New Demo' => '#3B82F6',
                                'Webinar Demo' => '#F59E0B',
                                default => '#9CA3AF',
                            };
                        @endphp

                        <div
                            class="flex flex-col items-center justify-center flex-1 min-w-[200px] p-6 rounded-lg shadow-inner cursor-pointer"
                            wire:click="openDemoTypeSlideOver('{{ $type }}')"
                        >
                            <div class="relative text-center group">
                                <div class="relative w-28 h-28">
                                    <svg width="130" height="130" viewBox="0 0 36 36">
                                        <circle cx="18" cy="18" r="14" stroke="#E5E7EB" stroke-width="5" fill="none"></circle>
                                        <circle cx="18" cy="18" r="14" stroke="{{ $color }}" stroke-width="5" fill="none"
                                                stroke-dasharray="88"
                                                stroke-dashoffset="{{ 88 - (88 * ($percentage / 100)) }}"
                                                stroke-linecap="round"
                                                transform="rotate(-90 18 18)">
                                        </circle>
                                    </svg>

                                    <!-- Count in center -->
                                    <div class="absolute inset-0 flex items-center justify-center text-lg font-bold text-gray-900">
                                        {{ $count }}
                                    </div>

                                    <!-- Hover Tooltip -->
                                    <div class="hover-message">
                                        {{ $percentage }}%
                                    </div>
                                </div>
                                <p class="mt-2 text-sm text-gray-700">{{ $type }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="p-6 bg-white rounded-lg shadow-lg" wire:poll.1s>
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-2">
                    <i class="text-lg text-purple-500 fa fa-percentage"></i> &nbsp;&nbsp;
                    <h2 class="text-lg font-bold text-gray-800">Demos Rate</h2>
                </div>
                <i class="bi bi-question-circle" title="Company Size Distribution (Demos) / by Company Size Distribution"></i>
            </div>
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="text-gray-600 border-b">
                        <th class="py-2">Company Size</th>
                        <th class="py-2">Total Leads</th>
                        <th class="py-2">Demo Leads</th>
                        <th class="py-2">Demo Rate (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($demoRateBySize as $size => $rate)
                        @php
                            $total = $companySizeData[$size] ?? 0;
                            $demo = $demoCompanySizeData[$size] ?? 0;
                            $color = match($size) {
                                'Small' => '#F2FAF2',
                                'Medium' => '#FFF9EB',
                                'Large' => '#FFF5E9',
                                'Enterprise' => '#FFEDED',
                                default => '#E2E8F0',
                            };
                        @endphp
                        <tr class="border-b hover:bg-gray-50" style="background-color: {{ $color }}">
                            <td class="py-2 font-medium">{{ $size }}</td>
                            <td class="py-2">{{ $total }}</td>
                            <td class="py-2">{{ $demo }}</td>
                            <td class="py-2 font-semibold text-blue-600">{{ $rate }}%</td>
                        </tr>
                    @endforeach
                </tbody>
                @php
                    $totalLeads = array_sum($companySizeData);
                    $totalDemos = array_sum($demoCompanySizeData);
                    $overallRate = $totalLeads > 0 ? round(($totalDemos / $totalLeads) * 100, 2) : 0;
                @endphp
                <tfoot>
                    <tr class="font-bold text-gray-800 bg-gray-100 border-t">
                        <td class="py-2">Total</td>
                        <td class="py-2">{{ $totalLeads }}</td>
                        <td class="py-2">{{ $totalDemos }}</td>
                        <td class="py-2 text-blue-700">{{ $overallRate }}%</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="p-6 bg-white rounded-lg shadow-lg" wire:poll.1s>
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-2">
                    <i class="text-lg text-blue-500 fa fa-users"></i> &nbsp;&nbsp;
                    <h2 class="text-lg font-bold text-gray-800">Webinar Demo Distribution</h2>
                </div>
                <i class="bi bi-question-circle" title="Average of Companies under Webinar Demo per Session"></i>
            </div>
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="text-gray-600 border-b">
                        <th class="py-2">Salesperson</th>
                        <th class="py-2">Webinar Sessions</th>
                        <th class="py-2">Total Leads</th>
                        <th class="py-2">Avg. Leads per Webinar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($webinarDemoAverages as $name => $data)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-2 font-medium text-blue-600 cursor-pointer hover:underline"
                                wire:click="openWebinarLeadList('{{ $name }}')">
                                {{ $name }}
                            </td>
                            <td class="py-2">{{ $data['webinar_count'] }}</td>
                            <td class="py-2">{{ $data['total_leads'] }}</td>
                            <td class="py-2 font-semibold text-blue-600">{{ $data['average_per_webinar'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-4 text-center text-gray-500">No webinar demo data found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-6 bg-white rounded-lg shadow-lg" wire:poll.1s>
            <div class="flex items-center space-x-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trophy-fill" viewBox="0 0 16 16">
                    <path d="M2.5.5A.5.5 0 0 1 3 0h10a.5.5 0 0 1 .5.5q0 .807-.034 1.536a3 3 0 1 1-1.133 5.89c-.79 1.865-1.878 2.777-2.833 3.011v2.173l1.425.356c.194.048.377.135.537.255L13.3 15.1a.5.5 0 0 1-.3.9H3a.5.5 0 0 1-.3-.9l1.838-1.379c.16-.12.343-.207.537-.255L6.5 13.11v-2.173c-.955-.234-2.043-1.146-2.833-3.012a3 3 0 1 1-1.132-5.89A33 33 0 0 1 2.5.5m.099 2.54a2 2 0 0 0 .72 3.935c-.333-1.05-.588-2.346-.72-3.935m10.083 3.935a2 2 0 0 0 .72-3.935c-.133 1.59-.388 2.885-.72 3.935"/>
                  </svg>&nbsp;&nbsp;
                <h2 class="text-lg font-bold text-gray-800">Close Won Amount</h2>
            </div>

            <div class="mt-4 text-3xl font-bold text-green-600">
                RM {{ number_format($closeWonAmount, 2) }}
            </div>
            <p class="mt-1 text-sm text-gray-500">
                {{ $closedDealsCount }} deals closed
            </p>

            <!-- Simple Bar Chart -->
            <div class="mt-6">
                <h3 class="mb-2 text-sm font-semibold text-gray-600">Last 6 Months</h3>
                <div class="flex flex-wrap justify-center gap-8 overflow-visible">
                    @php
                        $maxAmount = count($monthlyDealAmounts) > 0 ? max($monthlyDealAmounts) : 1;
                        $barColors = ['#D7C7F4', '#B9D6F8', '#B9D6F8', '#65E4EA', '#B4F7F7', '#FFE99E'];
                        $barBgColors = ['#F3EEFC', '#EDF5FD', '#E6F2FC', '#E0FBFC', '#ECFFFE', '#FFF8E0'];
                        $previousAmount = null;
                    @endphp

                    @foreach ($monthlyDealAmounts as $month => $amount)
                    @php
                        $heightPercent = $maxAmount > 0 ? ($amount / $maxAmount) * 100 : 0;
                        $color = $barColors[$loop->index % count($barColors)];
                        $bgcolor = $barBgColors[$loop->index % count($barBgColors)];
                        $label = Carbon\Carbon::parse($month)->format('M');

                        if ($previousAmount === null || $previousAmount == 0) {
                            $percentChange = 0;
                        } else {
                            $percentChange = (($amount - $previousAmount) / $previousAmount) * 100;
                        }

                        $previousAmount = $amount;
                    @endphp

                    <div class="relative text-center cursor-pointer bar-group group" wire:click="openMonthlyDealsSlideOver('{{ $month }}')">
                        @php
                            $labelcolor = $percentChange > 0
                                ? '#16a34a'
                                : ($percentChange < 0 ? '#dc2626' : '#374151');
                        @endphp

                        <p style="margin-bottom: 4px; font-size: 12px; font-weight: 600; color: {{ $labelcolor }};">
                            {{ $percentChange >= 0 ? '+' : '' }}{{ number_format($percentChange, 2) }}%
                        </p>

                        <div class="w-6 bg-gray-200 rounded bar-wrapper" style="height: 140px; background-color: {{ $bgcolor }};">
                            <div class="rounded bar-fill" style="height: {{ $heightPercent }}%; background-color: {{ $color }};"></div>
                        </div>

                        <p class="mt-2 text-xs text-gray-600">{{ $label }}</p>

                        <div class="hover-message">
                            RM {{ number_format($amount, 2) }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div
    x-data="{ open: @entangle('showSlideOver') }"
    x-show="open"
    @click.self="open = false"
    @keydown.window.escape="open = false"
    class="fixed inset-0 z-[200] flex justify-end bg-black/40 backdrop-blur-sm transition-opacity duration-200"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    >
        <div class="w-full h-full max-w-md p-6 overflow-y-auto bg-white shadow-xl">
            <br><br>

            <div class="flex items-center justify-between p-4 border-b">
                <h2 class="text-lg font-bold text-gray-800">{{ $slideOverTitle }}</h2>
                <button @click="open = false" class="text-2xl leading-none text-gray-500 hover:text-gray-700">&times;</button>
            </div>

            <div class="flex-1 p-4 space-y-2 overflow-y-auto">
                @forelse ($slideOverList as $item)
                    @php
                        $companyName = $item->companyDetail->company_name ?? 'N/A';
                        $shortened = strtoupper(\Illuminate\Support\Str::limit($companyName, 20, '...'));
                        $encryptedId = \App\Classes\Encryptor::encrypt($item->id);
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
