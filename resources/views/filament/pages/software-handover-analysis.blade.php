<!-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/software-handover-analysis.blade.php -->
<x-filament-panels::page>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        .stats-container {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
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
        }

        .implementer-table th,
        .implementer-table td {
            padding: 8px 12px;
            text-align: left;
            border: 1px solid #e5e7eb;
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

        .module-pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            margin-right: 4px;
            margin-bottom: 4px;
        }

        .module-ta {
            background-color: rgba(49, 130, 206, 0.2);
            color: #3182ce;
        }

        .module-tl {
            background-color: rgba(79, 209, 197, 0.2);
            color: #4fd1c5;
        }

        .module-tc {
            background-color: rgba(246, 173, 85, 0.2);
            color: #f6ad55;
        }

        .module-tp {
            background-color: rgba(159, 122, 234, 0.2);
            color: #9f7aea;
        }

        .module-tapp {
            background-color: rgba(56, 178, 172, 0.2);
            color: #38b2ac;
        }

        .module-thire {
            background-color: rgba(240, 82, 82, 0.2);
            color: #f05252;
        }

        .module-tacc {
            background-color: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
        }

        .module-tpbi {
            background-color: #f3f4f6;
            color: #6b7280;
        }
    </style>

    @php
        $stats = $this->getStatusCounts();
    @endphp
    <!-- Module Stats Cards -->
    <div class="stats-container">
        <!-- TA Stats -->
        <div class="stat-card module-chart-1">
            <div class="stat-card-header"></div>
            <div class="stat-card-content">
                <div class="stat-title">Total</div>
                <div class="stat-value">{{ $stats['total'] }}</div>
            </div>
        </div>

        <!-- TL Stats -->
        <div class="stat-card module-chart-2">
            <div class="stat-card-header"></div>
            <div class="stat-card-content">
                <div class="stat-title">Closed</div>
                <div class="stat-value">{{ $stats['closed'] }}</div>
            </div>
        </div>

        <!-- TC Stats -->
        <div class="stat-card module-chart-3">
            <div class="stat-card-header"></div>
            <div class="stat-card-content">
                <div class="stat-title">Ongoing</div>
                <div class="stat-value">{{ $stats['ongoing'] }}</div>
            </div>
        </div>

        <!-- TP Stats -->
        <div class="stat-card module-chart-4">
            <div class="stat-card-header"></div>
            <div class="stat-card-content">
                <div class="stat-title">Open</div>
                <div class="stat-value">{{ $stats['open'] }}</div>
            </div>
        </div>

        <!-- TAPP Stats -->
        <div class="stat-card module-chart-5">
            <div class="stat-card-header"></div>
            <div class="stat-card-content">
                <div class="stat-title">Delay</div>
                <div class="stat-value">{{ $stats['delay'] }}</div>
            </div>
        </div>

        <!-- THIRE Stats -->
        <div class="stat-card module-chart-6">
            <div class="stat-card-header"></div>
            <div class="stat-card-content">
                <div class="stat-title">Inactive</div>
                <div class="stat-value">{{ $stats['inactive'] }}</div>
            </div>
        </div>
    </div>

    <!-- Implementation Status Table -->
    <h2 class="tier-heading">Implementation Status by Implementer</h2>

    <div class="mb-8 overflow-x-auto">
        <table class="implementer-table">
            <thead>
                <tr>
                    <th colspan="2" class="status-header" style='background-color: #ffff00'>COUNT BY IMPLEMENTER</th>
                    <th colspan="2" class="status-header" style='background-color: #f1a983'>STATUS</th>
                    <th colspan="3" class="status-ongoing-header" style='background-color: #0f9ed5'>STATUS - ONGOING</th>
                </tr>
                <tr>
                    <th style='background-color: #f2f25e'>FROM</th>
                    <th style='background-color: #f2f25e'>TOTAL</th>
                    <th style='background-color: #fbe2d5'>CLOSED</th>
                    <th style='background-color: #fbe2d5'>ONGOING</th>
                    <th style='background-color: #caedfb'>OPEN</th>
                    <th style='background-color: #caedfb'>DELAY</th>
                    <th style='background-color: #caedfb'>INACTIVE</th>
                </tr>
            </thead>
            <tbody>
                <!-- Tier 1 -->
                <tr>
                    <td colspan="8" class="tier-header">Active Implementer / Tier 1</td>
                </tr>
                @foreach($this->getTier1Implementers() as $implementer)
                    <tr>
                        <td class="name-column">
                            <button class="hover:underline" wire:click="openImplementerSlideOver('{{ $implementer }}')">
                                {{ $implementer }}
                            </button>
                        </td>
                        <td>{{ $this->getImplementerTotal($implementer) }}</td>
                        <td>{{ $this->getImplementerClosedCount($implementer) }}</td>
                        <td>{{ $this->getImplementerOngoingCount($implementer) }}</td>
                        <td>{{ $this->getImplementerStatusCount($implementer, 'OPEN') }}</td>
                        <td>{{ $this->getImplementerStatusCount($implementer, 'DELAY') }}</td>
                        <td>{{ $this->getImplementerStatusCount($implementer, 'INACTIVE') }}</td>
                    </tr>
                @endforeach

                <!-- Tier 2 -->
                <tr>
                    <td colspan="8" class="tier-header">Active Implementer / Tier 2</td>
                </tr>
                @foreach($this->getTier2Implementers() as $implementer)
                    <tr>
                        <td class="name-column">
                            <button class="hover:underline" wire:click="openImplementerSlideOver('{{ $implementer }}')">
                                {{ $implementer }}
                            </button>
                        </td>
                        <td>{{ $this->getImplementerTotal($implementer) }}</td>
                        <td>{{ $this->getImplementerClosedCount($implementer) }}</td>
                        <td>{{ $this->getImplementerOngoingCount($implementer) }}</td>
                        <td>{{ $this->getImplementerStatusCount($implementer, 'OPEN') }}</td>
                        <td>{{ $this->getImplementerStatusCount($implementer, 'DELAY') }}</td>
                        <td>{{ $this->getImplementerStatusCount($implementer, 'INACTIVE') }}</td>
                    </tr>
                @endforeach

                <!-- Tier 3 -->
                <tr>
                    <td colspan="8" class="tier-header">Active Implementer / Tier 3</td>
                </tr>
                @foreach($this->getTier3Implementers() as $implementer)
                    <tr>
                        <td class="name-column">
                            <button class="hover:underline" wire:click="openImplementerSlideOver('{{ $implementer }}')">
                                {{ $implementer }}
                            </button>
                        </td>
                        <td>{{ $this->getImplementerTotal($implementer) }}</td>
                        <td>{{ $this->getImplementerClosedCount($implementer) }}</td>
                        <td>{{ $this->getImplementerOngoingCount($implementer) }}</td>
                        <td>{{ $this->getImplementerStatusCount($implementer, 'OPEN') }}</td>
                        <td>{{ $this->getImplementerStatusCount($implementer, 'DELAY') }}</td>
                        <td>{{ $this->getImplementerStatusCount($implementer, 'INACTIVE') }}</td>
                    </tr>
                @endforeach

                <!-- Inactive -->
                <tr>
                    <td colspan="8" class="tier-header">Inactive Implementers</td>
                </tr>
                @foreach($this->getInactiveImplementersList() as $implementer)
                    <tr>
                        <td class="name-column">
                            <button class="hover:underline" wire:click="openImplementerSlideOver('{{ $implementer }}')">
                                {{ $implementer }}
                            </button>
                        </td>
                        <td>{{ $this->getImplementerTotal($implementer) }}</td>
                        <td>{{ $this->getImplementerClosedCount($implementer) }}</td>
                        <td>{{ $this->getImplementerOngoingCount($implementer) }}</td>
                        <td>{{ $this->getImplementerStatusCount($implementer, 'OPEN') }}</td>
                        <td>{{ $this->getImplementerStatusCount($implementer, 'DELAY') }}</td>
                        <td>{{ $this->getImplementerStatusCount($implementer, 'INACTIVE') }}</td>
                    </tr>
                @endforeach

                <!-- Total Row -->
                <tr class="total-row">
                    <td>TOTAL</td>
                    <td>{{ $stats['total'] }}</td>
                    <td>{{ $stats['closed'] }}</td>
                    <td>{{ $stats['ongoing'] }}</td>
                    <td>{{ $stats['open'] }}</td>
                    <td>{{ $stats['delay'] }}</td>
                    <td>{{ $stats['inactive'] }}</td>
                </tr>
            </tbody>
        </table>
    </div>

</x-filament-panels::page>
