{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/hrdf-claim-tracker.blade.php --}}
<x-filament-panels::page>
    <style>
        /* Force sticky headers for Filament tables */
        .fi-ta-table {
            position: relative;
        }

        .fi-ta-table thead {
            position: sticky !important;
            top: 0 !important;
            z-index: 20 !important;
        }

        .fi-ta-table thead th {
            position: sticky !important;
            top: 0 !important;
            z-index: 20 !important;
            background-color: rgb(250, 250, 250) !important;
            border-bottom: 2px solid #e5e7eb !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
        }

        /* Dark mode support */
        .dark .fi-ta-table thead th {
            background-color: rgb(17 24 39) !important;
            border-bottom: 2px solid rgb(55 65 81) !important;
            color: rgb(229 231 235) !important;
        }

        /* Table container with fixed height */
        .fi-ta-content {
            max-height: calc(100vh - 250px) !important;
            overflow: auto !important;
        }

        /* Ensure proper scrolling */
        .fi-ta-ctn {
            overflow: visible !important;
        }

        /* Fix for filter dropdowns to appear above sticky headers */
        [x-data*="dropdown"], .fi-dropdown-panel {
            z-index: 30 !important;
        }

        /* HRDF Claim Dashboard Grid */
        .hrdf-dashboard-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
        }

        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .hrdf-dashboard-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .hrdf-dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .hrdf-dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Card styling */
        .hrdf-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .hrdf-card-content {
            padding: 1.25rem 0.75rem;
        }

        .hrdf-card-layout {
            display: flex;
            align-items: center;
        }

        /* Icon container */
        .hrdf-icon-container {
            flex-shrink: 0;
            padding: 0.75rem;
            border-radius: 0.375rem;
        }

        .hrdf-icon-container.purple {
            background-color: rgba(124, 58, 237, 0.1);
        }

        .hrdf-icon-container.orange {
            background-color: rgba(249, 115, 22, 0.1);
        }

        .hrdf-icon-container.blue {
            background-color: rgba(37, 99, 235, 0.1);
        }

        .hrdf-icon-container.green {
            background-color: rgba(34, 197, 94, 0.1);
        }

        .hrdf-icon-container.red {
            background-color: rgba(220, 38, 38, 0.1);
        }

        .hrdf-icon {
            width: 1.5rem;
            height: 1.5rem;
        }

        .hrdf-icon-container.purple .hrdf-icon {
            color: rgba(124, 58, 237, 1);
        }

        .hrdf-icon-container.orange .hrdf-icon {
            color: rgba(249, 115, 22, 1);
        }

        .hrdf-icon-container.blue .hrdf-icon {
            color: rgba(37, 99, 235, 1);
        }

        .hrdf-icon-container.green .hrdf-icon {
            color: rgba(34, 197, 94, 1);
        }

        .hrdf-icon-container.red .hrdf-icon {
            color: rgba(220, 38, 38, 1);
        }

        /* HRDF details */
        .hrdf-details {
            flex: 1;
            width: 0;
            margin-left: 0.5rem;
        }

        .hrdf-title {
            font-size: 0.95rem;
            font-weight: 500;
            color: #111827;
            margin-bottom: 0.25rem;
        }

        .hrdf-subtitle {
            font-size: 0.75rem;
            color: #6B7280;
            margin-bottom: 0.125rem;
        }

        .hrdf-amount {
            font-size: 1.25rem;
            font-weight: 700;
            margin-top: 0.25rem;
        }

        .hrdf-amount.purple {
            color: rgba(124, 58, 237, 1);
        }

        .hrdf-amount.orange {
            color: rgba(249, 115, 22, 1);
        }

        .hrdf-amount.blue {
            color: rgba(37, 99, 235, 1);
        }

        .hrdf-amount.green {
            color: rgba(34, 197, 94, 1);
        }

        .hrdf-amount.red {
            color: rgba(220, 38, 38, 1);
        }
    </style>

    {{-- Dashboard Cards --}}
    <div class="hrdf-dashboard-grid">
        @php
            $cards = [
                'all' => [
                    'title' => 'All',
                    'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                    'color' => 'purple',
                ],
                'pending' => [
                    'title' => 'Pending',
                    'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                    'color' => 'orange',
                ],
                'submitted' => [
                    'title' => 'Submitted',
                    'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                    'color' => 'blue',
                ],
                'approved' => [
                    'title' => 'Approved',
                    'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                    'color' => 'green',
                ],
                'received' => [
                    'title' => 'Received',
                    'icon' => 'M5 13l4 4L19 7',
                    'color' => 'red',
                ],
            ];
        @endphp

        @foreach($this->getStats() as $key => $stat)
            @php
                $card = $cards[$key];
            @endphp
            <div class="hrdf-card">
                <div class="hrdf-card-content">
                    <div class="hrdf-card-layout">
                        <div class="hrdf-icon-container {{ $card['color'] }}">
                            <svg class="hrdf-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"></path>
                            </svg>
                        </div>
                        <div class="hrdf-details">
                            <dt class="hrdf-title">{{ $card['title'] }}</dt>
                            <dd>
                                <div class="hrdf-subtitle">Invoice Count: {{ $stat['count'] }}</div>
                                <div class="hrdf-amount {{ $card['color'] }}">RM {{ number_format($stat['amount'], 2) }}</div>
                            </dd>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Table --}}
    {{ $this->table }}
</x-filament-panels::page>
