<x-filament-panels::page>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        .stats-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 8px;
        }

        .stat-card {
            flex: 1;
            min-width: 160px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .stat-card-header {
            height: 4px;
        }

        .stat-card-content {
            padding: 16px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            flex-grow: 1;
        }

        .stat-title {
            font-size: 14px;
            font-weight: 500;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            line-height: 1;
            margin: 0;
        }

        .tier-heading {
            font-size: 16px;
            font-weight: 600;
            margin-top: 24px;
            margin-bottom: 12px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e5e7eb;
            color: #111827;
        }

        .implementer-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            border: 2px solid #374151;
        }

        .implementer-table th,
        .implementer-table td {
            padding: 8px 12px;
            text-align: center;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .implementer-table th[colspan="2"] {
            /* This targets the "COUNT BY IMPLEMENTER" header */
            text-align: center;
        }

        .implementer-table th[colspan="3"] {
            /* This targets the "STATUS - ONGOING" header */
            text-align: center;
        }

        /* Ensure the name column stays left aligned */
        .name-column {
            font-weight: 500;
            text-align: left !important;
        }

        .implementer-table th {
            background-color: #f3f4f6;
            font-weight: 500;
            color: #374151;
        }

        .implementer-table tbody tr:hover {
            background-color: rgba(243, 244, 246, 0.5);
        }

        .tier-header {
            background-color: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
            font-weight: 600;
        }

        .status-header {
            text-align: center;
            font-weight: 600;
            background-color: #f3f4f6;
            color: #6b7280;
        }

        .status-ongoing-header {
            text-align: center;
            font-weight: 600;
            background-color: rgba(79, 209, 197, 0.2);
            color: #4fd1c5;
        }

        .name-column {
            font-weight: 500;
            text-align: left !important; /* Ensure left alignment for names */
        }

        .total-row {
            font-weight: 600;
            background-color: rgba(243, 244, 246, 0.8);
        }

        /* Module colors */
        .module-chart-1 .stat-card-header { background-color: #3182ce; }
        .module-chart-1 .stat-value { color: #3182ce; }

        .module-chart-2 .stat-card-header { background-color: #4fd1c5; }
        .module-chart-2 .stat-value { color: #4fd1c5; }

        .module-chart-3 .stat-card-header { background-color: #f6ad55; }
        .module-chart-3 .stat-value { color: #f6ad55; }

        .module-chart-4 .stat-card-header { background-color: #9f7aea; }
        .module-chart-4 .stat-value { color: #9f7aea; }

        .module-chart-5 .stat-card-header { background-color: #38b2ac; }
        .module-chart-5 .stat-value { color: #38b2ac; }

        .module-chart-6 .stat-card-header { background-color: #f05252; }
        .module-chart-6 .stat-value { color: #f05252; }

        .module-chart-7 .stat-card-header { background-color: #3b82f6; }
        .module-chart-7 .stat-value { color: #3b82f6; }

        .module-chart-8 .stat-card-header { background-color: #6b7280; }
        .module-chart-8 .stat-value { color: #6b7280; }

        /* Adding thick borders between column groups */
        .border-right-thick {
            border-right: 3px solid #374151 !important;
        }

        .border-left-thick {
            border-left: 2px solid #374151 !important;
        }

        /* Override border for header cells */
        .implementer-table thead th.status-header,
        .implementer-table thead th.status-ongoing-header {
            border: 1px solid #374151;
        }

        /* Group border styles */
        .col-group-end {
            border-right: 2px solid #374151 !important;
        }

        /* Style for column group headers */
        .col-group-header {
            font-weight: 700;
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
            padding: 1.25rem 1.5rem;
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
            text-decoration: none;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .company-item.has-lead {
            color: #2563eb;
        }

        .company-item.no-lead {
            color: #111827;
        }

        .company-item:hover {
            transform: translateY(-2px);
            background-color: #f9fafb;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

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

        /* Improved scrollbar styling */
        .slide-over-content::-webkit-scrollbar {
            width: 6px;
        }

        .slide-over-content::-webkit-scrollbar-track {
            background: #f3f4f6;
        }

        .slide-over-content::-webkit-scrollbar-thumb {
            background-color: #d1d5db;
            border-radius: 3px;
        }

        .slide-over-content::-webkit-scrollbar-thumb:hover {
            background-color: #9ca3af;
        }

        .clickable {
            cursor: pointer;
            transition: all 0.2s;
        }
        .clickable:hover {
            background-color: rgba(59, 130, 246, 0.1);
        }
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

        .analysis-tabs {
            display: flex;
            margin-bottom: 12px;
            border-bottom: 1px solid #e5e7eb;
            position: relative;
        }

        .analysis-tab {
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 500;
            color: #6b7280;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .analysis-tab.active {
            color: #2563eb;
            border-bottom-color: #2563eb;
            font-weight: 600;
        }

        .analysis-tab:hover:not(.active) {
            color: #4b5563;
            border-bottom-color: #d1d5db;
        }

        .analysis-tab-content {
            display: none;
        }

        .analysis-tab-content.active {
            display: block;
        }
    </style>

    <div x-data="{
            activeTab: 'analysis1',
            switchTab(tab) {
                this.activeTab = tab;
                // Store preference in local storage
                localStorage.setItem('preferredAnalysisTab', tab);

                // Trigger an event that can be caught by Livewire
                window.dispatchEvent(new CustomEvent('analysis-tab-changed', { detail: { tab } }));
            },
            initTabs() {
                // Get preferred tab from local storage or default to analysis1
                const savedTab = localStorage.getItem('preferredAnalysisTab');
                if (savedTab) {
                    this.activeTab = savedTab;
                }
            }
        }" x-init="initTabs()">

    <!-- Tab Navigation -->
    <div class="analysis-tabs">
        <div @click="switchTab('analysis1')"
            :class="{'analysis-tab': true, 'active': activeTab === 'analysis1'}">
            <i class="fas fa-chart-bar"></i>
            Analysis by Implementer
        </div>
        <div @click="switchTab('analysis2')"
            :class="{'analysis-tab': true, 'active': activeTab === 'analysis2'}">
            <i class="fas fa-chart-line"></i>
            Analysis by Statistic
        </div>
        <div @click="switchTab('analysis3')"
            :class="{'analysis-tab': true, 'active': activeTab === 'analysis3'}">
            <i class="bi bi-pie-chart"></i>
            Analysis by Company Size
        </div>
    </div>

    <div :class="{'analysis-tab-content': true, 'active': activeTab === 'analysis1'}" id="analysis1Content">
        @php
            $stats = $this->getStatusCounts();
        @endphp
        <!-- Module Stats Cards -->
        <div class="stats-container">
            <!-- Total Stats -->
                <div class="stat-card module-chart-1 clickable" wire:click="openAllHandoversSlideOver">
                    <div class="stat-card-header"></div>
                    <div class="stat-card-content">
                        <div class="stat-title">Total</div>
                        <div class="stat-value">{{ $stats['total'] }}</div>
                    </div>
                </div>

                <!-- Closed Stats -->
                <div class="stat-card module-chart-2 clickable" wire:click="openClosedHandoversSlideOver">
                    <div class="stat-card-header"></div>
                    <div class="stat-card-content">
                        <div class="stat-title">Closed</div>
                        <div class="stat-value">{{ $stats['closed'] }}</div>
                    </div>
                </div>

                <!-- Ongoing Stats -->
                <div class="stat-card module-chart-3 clickable" wire:click="openOngoingHandoversSlideOver">
                    <div class="stat-card-header"></div>
                    <div class="stat-card-content">
                        <div class="stat-title">Ongoing</div>
                        <div class="stat-value">{{ $stats['ongoing'] }}</div>
                    </div>
                </div>

                <!-- Open Stats -->
                <div class="stat-card module-chart-4 clickable" wire:click="openOpenHandoversSlideOver">
                    <div class="stat-card-header"></div>
                    <div class="stat-card-content">
                        <div class="stat-title">Open</div>
                        <div class="stat-value">{{ $stats['open'] }}</div>
                    </div>
                </div>

                <!-- Delay Stats -->
                <div class="stat-card module-chart-5 clickable" wire:click="openDelayHandoversSlideOver">
                    <div class="stat-card-header"></div>
                    <div class="stat-card-content">
                        <div class="stat-title">Delay</div>
                        <div class="stat-value">{{ $stats['delay'] }}</div>
                    </div>
                </div>

                <!-- Inactive Stats -->
                <div class="stat-card module-chart-6 clickable" wire:click="openInactiveHandoversSlideOver">
                    <div class="stat-card-header"></div>
                    <div class="stat-card-content">
                        <div class="stat-title">Inactive</div>
                        <div class="stat-value">{{ $stats['inactive'] }}</div>
                    </div>
                </div>
        </div>

        <div class="mb-8 overflow-x-auto">
            <table class="implementer-table">
                <thead>
                    <tr>
                        <th colspan="2" class="status-header col-group-end" style='background-color: #ffff00'>Count By Implementer</th>
                        <th colspan="2" class="status-header col-group-end" style='background-color: #f1a983'>Status</th>
                        <th colspan="3" class="status-ongoing-header" style='background-color: #0f9ed5'>Status - OnGoing</th>
                    </tr>
                    <tr>
                        <td class="tier-header" style="width:28%;">Active Implementer / Tier 1</td>
                        <td style="width:12%; background-color: #f2f25e" class="col-group-end">Total</td>
                        <td style="width:12%; background-color: #fbe2d5">Closed</td>
                        <td style="width:12%; background-color: #fbe2d5" class="col-group-end">OnGoing</td>
                        <td style="width:12%; background-color: #caedfb">Open</td>
                        <td style="width:12%; background-color: #caedfb">Delay</td>
                        <td style="width:12%; background-color: #caedfb">InActive</td>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->getTier1Implementers() as $implementer)
                        <tr>
                            <td class="name-column clickable" wire:click="openImplementerHandoversSlideOver('{{ $implementer }}')">
                                {{ $implementer }}
                            </td>
                            <td class="col-group-end clickable" wire:click="openImplementerHandoversSlideOver('{{ $implementer }}')">
                                {{ $this->getImplementerTotal($implementer) }}
                            </td>
                            <td class="clickable" wire:click="openImplementerHandoversSlideOver('{{ $implementer }}', 'CLOSED')">
                                {{ $this->getImplementerClosedCount($implementer) }}
                            </td>
                            <td class="col-group-end clickable" wire:click="openOngoingHandoversSlideOver('{{ $implementer }}')">
                                {{ $this->getImplementerOngoingCount($implementer) }}
                            </td>
                            <td class="clickable" wire:click="openImplementerHandoversSlideOver('{{ $implementer }}', 'OPEN')">
                                {{ $this->getImplementerStatusCount($implementer, 'OPEN') }}
                            </td>
                            <td class="clickable" wire:click="openImplementerHandoversSlideOver('{{ $implementer }}', 'DELAY')">
                                {{ $this->getImplementerStatusCount($implementer, 'DELAY') }}
                            </td>
                            <td class="clickable" wire:click="openImplementerHandoversSlideOver('{{ $implementer }}', 'INACTIVE')">
                                {{ $this->getImplementerStatusCount($implementer, 'INACTIVE') }}
                            </td>
                        </tr>
                    @endforeach

                    <!-- Tier 2 -->
                    <tr>
                        <td class="tier-header">Active Implementer / Tier 2</td>
                        <td style="width:12%; background-color: #f2f25e" class="col-group-end">Total</td>
                        <td style="width:12%; background-color: #fbe2d5">Closed</td>
                        <td style="width:12%; background-color: #fbe2d5" class="col-group-end">OnGoing</td>
                        <td style="width:12%; background-color: #caedfb">Open</td>
                        <td style="width:12%; background-color: #caedfb">Delay</td>
                        <td style="width:12%; background-color: #caedfb">InActive</td>
                    </tr>
                    @foreach($this->getTier2Implementers() as $implementer)
                        <tr>
                            <td class="name-column clickable" wire:click="openImplementerHandoversSlideOver('{{ $implementer }}')">
                                {{ $implementer }}
                            </td>
                            <td class="col-group-end clickable" wire:click="openImplementerHandoversSlideOver('{{ $implementer }}')">
                                {{ $this->getImplementerTotal($implementer) }}
                            </td>
                            <td class="clickable" wire:click="openImplementerHandoversSlideOver('{{ $implementer }}', 'CLOSED')">
                                {{ $this->getImplementerClosedCount($implementer) }}
                            </td>
                            <td class="col-group-end clickable" wire:click="openOngoingHandoversSlideOver('{{ $implementer }}')">
                                {{ $this->getImplementerOngoingCount($implementer) }}
                            </td>
                            <td class="clickable" wire:click="openImplementerHandoversSlideOver('{{ $implementer }}', 'OPEN')">
                                {{ $this->getImplementerStatusCount($implementer, 'OPEN') }}
                            </td>
                            <td class="clickable" wire:click="openImplementerHandoversSlideOver('{{ $implementer }}', 'DELAY')">
                                {{ $this->getImplementerStatusCount($implementer, 'DELAY') }}
                            </td>
                            <td class="clickable" wire:click="openImplementerHandoversSlideOver('{{ $implementer }}', 'INACTIVE')">
                                {{ $this->getImplementerStatusCount($implementer, 'INACTIVE') }}
                            </td>
                        </tr>
                    @endforeach

                    <!-- Tier 3 -->
                    <tr>
                        <td class="tier-header">Active Implementer / Tier 3</td>
                        <td style="width:12%; background-color: #f2f25e" class="col-group-end">Total</td>
                        <td style="width:12%; background-color: #fbe2d5">Closed</td>
                        <td style="width:12%; background-color: #fbe2d5" class="col-group-end">OnGoing</td>
                        <td style="width:12%; background-color: #caedfb">Open</td>
                        <td style="width:12%; background-color: #caedfb">Delay</td>
                        <td style="width:12%; background-color: #caedfb">InActive</td>
                    </tr>
                    @foreach($this->getTier3Implementers() as $implementer)
                        <tr>
                            <td class="name-column clickable" wire:click="openImplementerHandoversSlideOver('{{ $implementer }}')">
                                {{ $implementer }}
                            </td>
                            <td class="col-group-end clickable" wire:click="openImplementerHandoversSlideOver('{{ $implementer }}')">
                                {{ $this->getImplementerTotal($implementer) }}
                            </td>
                            <td class="clickable" wire:click="openImplementerHandoversSlideOver('{{ $implementer }}', 'CLOSED')">
                                {{ $this->getImplementerClosedCount($implementer) }}
                            </td>
                            <td class="col-group-end clickable" wire:click="openOngoingHandoversSlideOver('{{ $implementer }}')">
                                {{ $this->getImplementerOngoingCount($implementer) }}
                            </td>
                            <td class="clickable" wire:click="openImplementerHandoversSlideOver('{{ $implementer }}', 'OPEN')">
                                {{ $this->getImplementerStatusCount($implementer, 'OPEN') }}
                            </td>
                            <td class="clickable" wire:click="openImplementerHandoversSlideOver('{{ $implementer }}', 'DELAY')">
                                {{ $this->getImplementerStatusCount($implementer, 'DELAY') }}
                            </td>
                            <td class="clickable" wire:click="openImplementerHandoversSlideOver('{{ $implementer }}', 'INACTIVE')">
                                {{ $this->getImplementerStatusCount($implementer, 'INACTIVE') }}
                            </td>
                        </tr>
                    @endforeach

                    <!-- Inactive -->
                    <tr>
                        <td class="tier-header">InActive Implementers</td>
                        <td style="width:12%; background-color: #f2f25e" class="col-group-end">Total</td>
                        <td style="width:12%; background-color: #fbe2d5">Closed</td>
                        <td style="width:12%; background-color: #fbe2d5" class="col-group-end">OnGoing</td>
                        <td style="width:12%; background-color: #caedfb">Open</td>
                        <td style="width:12%; background-color: #caedfb">Delay</td>
                        <td style="width:12%; background-color: #caedfb">InActive</td>
                    </tr>
                    @foreach($this->getInactiveImplementers() as $implementer)
                        <tr>
                            <td class="name-column clickable" wire:click="openImplementerHandoversSlideOver('{{ $implementer['dbName'] }}')">
                                {{ $implementer['name'] }}
                            </td>
                            <td class="col-group-end clickable" wire:click="openImplementerHandoversSlideOver('{{ $implementer['dbName'] }}')">
                                {{ $implementer['total'] }}
                            </td>
                            <td class="clickable" wire:click="openImplementerHandoversSlideOver('{{ $implementer['dbName'] }}', 'CLOSED')">
                                {{ $implementer['closed'] }}
                            </td>
                            <td class="col-group-end clickable" wire:click="openOngoingHandoversSlideOver('{{ $implementer['dbName'] }}')">
                                {{ $implementer['ongoing'] }}
                            </td>
                            <td class="clickable" wire:click="openImplementerHandoversSlideOver('{{ $implementer['dbName'] }}', 'OPEN')">
                                {{ $implementer['open'] }}
                            </td>
                            <td class="clickable" wire:click="openImplementerHandoversSlideOver('{{ $implementer['dbName'] }}', 'DELAY')">
                                {{ $implementer['delay'] }}
                            </td>
                            <td class="clickable" wire:click="openImplementerHandoversSlideOver('{{ $implementer['dbName'] }}', 'INACTIVE')">
                                {{ $implementer['inactive'] }}
                            </td>
                        </tr>
                    @endforeach

                    <!-- Total Row -->
                    <tr class="total-row">
                        <td>TOTAL</td>
                        <td class="col-group-end clickable" wire:click="openAllHandoversSlideOver()">
                            {{ $stats['total'] }}
                        </td>
                        <td class="clickable" wire:click="openClosedHandoversSlideOver()">
                            {{ $stats['closed'] }}
                        </td>
                        <td class="col-group-end clickable" wire:click="openOngoingHandoversSlideOver()">
                            {{ $stats['ongoing'] }}
                        </td>
                        <td class="clickable" wire:click="openOpenHandoversSlideOver()">
                            {{ $stats['open'] }}
                        </td>
                        <td class="clickable" wire:click="openDelayHandoversSlideOver()">
                            {{ $stats['delay'] }}
                        </td>
                        <td class="clickable" wire:click="openInactiveHandoversSlideOver()">
                            {{ $stats['inactive'] }}
                        </td>
                    </tr>
                </tbody>
            </table>
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
                    @if (empty($handoverList) || count($handoverList) === 0)
                        <div class="empty-state">
                            <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 14h.01M20 4v7a4 4 0 01-4 4H8a4 4 0 01-4-4V4m0 0h16M4 4v2m16-2v2" />
                            </svg>
                            <p>No handovers found for this selection.</p>
                        </div>
                    @elseif ($handoverList instanceof \Illuminate\Support\Collection && $handoverList->first() instanceof \Illuminate\Support\Collection)
                        <!-- Grouped display -->
                        @foreach ($handoverList as $companySize => $handovers)
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
                                                $companyName = $handover->company_name ?? 'N/A';
                                                $shortened = strtoupper(\Illuminate\Support\Str::limit($companyName, 40, '...'));
                                                $encryptedId = $handover->lead_id ? \App\Classes\Encryptor::encrypt($handover->lead_id) : null;
                                            } catch (\Exception $e) {
                                                $shortened = 'Error loading company';
                                                $encryptedId = null;
                                                $companyName = 'Error: ' . $e->getMessage();
                                            }
                                        @endphp

                                        @if ($encryptedId)
                                            <a href="{{ url('admin/leads/' . $encryptedId) }}"
                                                target="_blank"
                                                title="{{ $companyName }}"
                                                class="company-item has-lead">
                                                {{ $shortened }}
                                                <i class="ml-1 text-xs fas fa-external-link-alt"></i>
                                            </a>
                                        @else
                                            <div class="company-item no-lead">
                                                {{ $shortened }}
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @else
                        <!-- Legacy non-grouped display as fallback -->
                        @foreach ($handoverList as $handover)
                            @php
                                try {
                                    $companyName = $handover->company_name ?? 'N/A';
                                    $shortened = strtoupper(\Illuminate\Support\Str::limit($companyName, 40, '...'));
                                    $encryptedId = $handover->lead_id ? \App\Classes\Encryptor::encrypt($handover->lead_id) : null;
                                } catch (\Exception $e) {
                                    $shortened = 'Error loading company';
                                    $encryptedId = null;
                                    $companyName = 'Error: ' . $e->getMessage();
                                }
                            @endphp

                            @if ($encryptedId)
                                <a href="{{ url('admin/leads/' . $encryptedId) }}"
                                    target="_blank"
                                    title="{{ $companyName }}"
                                    class="company-item has-lead">
                                    {{ $shortened }}
                                    <i class="ml-1 text-xs fas fa-external-link-alt"></i>
                                </a>
                            @else
                                <div class="company-item no-lead">
                                    {{ $shortened }}
                                </div>
                            @endif
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div :class="{'analysis-tab-content': true, 'active': activeTab === 'analysis2'}" id="analysis2Content">
        @include('filament.pages.software-handover-analysis-v2')
    </div>
    <div :class="{'analysis-tab-content': true, 'active': activeTab === 'analysis3'}" id="analysis3Content">
        @include('filament.pages.software-handover-analysis-v3')
    </div>
</x-filament-panels::page>
