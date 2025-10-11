{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/admin-renewal-process-data-usd.blade.php --}}
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

        /* Renewal Dashboard Grid */
        .renewal-dashboard-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
        }

        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .renewal-dashboard-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .renewal-dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .renewal-dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Card styling */
        .renewal-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .renewal-card-content {
            padding: 1.25rem 0.75rem;
        }

        .renewal-card-layout {
            display: flex;
            align-items: center;
        }

        /* Icon container */
        .renewal-icon-container {
            flex-shrink: 0;
            padding: 0.75rem;
            border-radius: 0.375rem;
        }

        .renewal-icon-container.green {
            background-color: rgba(34, 197, 94, 0.1);
        }

        .renewal-icon-container.blue {
            background-color: rgba(37, 99, 235, 0.1);
        }

        .renewal-icon-container.purple {
            background-color: rgba(124, 58, 237, 0.1);
        }

        .renewal-icon-container.orange {
            background-color: rgba(249, 115, 22, 0.1);
        }

        .renewal-icon-container.red {
            background-color: rgba(220, 38, 38, 0.1);
        }

        .renewal-icon {
            width: 1.5rem;
            height: 1.5rem;
        }

        .renewal-icon-container.green .renewal-icon {
            color: rgba(34, 197, 94, 1);
        }

        .renewal-icon-container.blue .renewal-icon {
            color: rgba(37, 99, 235, 1);
        }

        .renewal-icon-container.purple .renewal-icon {
            color: rgba(124, 58, 237, 1);
        }

        .renewal-icon-container.orange .renewal-icon {
            color: rgba(249, 115, 22, 1);
        }

        .renewal-icon-container.red .renewal-icon {
            color: rgba(220, 38, 38, 1);
        }

        /* Renewal details */
        .renewal-details {
            flex: 1;
            width: 0;
            margin-left: 0.5rem;
        }

        .renewal-title {
            font-size: 0.95rem;
            font-weight: 500;
            color: #111827;
        }

        .renewal-subtitle {
            font-size: 0.75rem;
            color: #6B7280;
        }

        .renewal-amount-label {
            font-size: 1rem;
            font-weight: 500;
            color: #111827;
        }

        .renewal-amount {
            font-size: 1.25rem;
            font-weight: 700;
        }

        .renewal-amount.green {
            color: rgba(34, 197, 94, 1);
        }

        .renewal-amount.blue {
            color: rgba(37, 99, 235, 1);
        }

        .renewal-amount.purple {
            color: rgba(124, 58, 237, 1);
        }

        .renewal-amount.orange {
            color: rgba(249, 115, 22, 1);
        }

        .renewal-amount.red {
            color: rgba(220, 38, 38, 1);
        }

        .refresh-button-container {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 1rem;
        }

        .refresh-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background-color: #3B82F6;
            color: white;
            border: none;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .refresh-button:hover {
            background-color: #2563EB;
        }

        .refresh-icon {
            width: 1rem;
            height: 1rem;
        }
    </style>

    <!-- Dashboard Cards -->
    <div class="renewal-dashboard-grid">
        <!-- Box 1: New -->
        <div class="renewal-card">
            <div class="renewal-card-content">
                <div class="renewal-card-layout">
                    <div class="renewal-icon-container purple">
                        <svg class="renewal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                    <div class="renewal-details">
                        <dt class="renewal-title">New</dt>
                        <dd>
                            <div class="renewal-subtitle">Total Company: {{ $newStats['total_companies'] }}</div>
                            <div class="renewal-subtitle">
                                Via Reseller: {{ $newStats['total_via_reseller'] }}
                                (RM {{ number_format($newStats['total_via_reseller_amount'] ?? 0, 2) }})
                            </div>
                            <div class="renewal-subtitle">
                                Via End User: {{ $newStats['total_via_end_user'] }}
                                (RM {{ number_format($newStats['total_via_end_user_amount'] ?? 0, 2) }})
                            </div>
                            <div class="renewal-amount purple">RM {{ number_format($newStats['total_amount'], 2) }}</div>
                        </dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Box 2: Pending Confirmation -->
        <div class="renewal-card">
            <div class="renewal-card-content">
                <div class="renewal-card-layout">
                    <div class="renewal-icon-container orange">
                        <svg class="renewal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="renewal-details">
                        <dt class="renewal-title">Pending Confirmation</dt>
                        <dd>
                            <div class="renewal-subtitle">Total Company: {{ $pendingConfirmationStats['total_companies'] }}</div>
                            <div class="renewal-subtitle">
                                Via Reseller: {{ $pendingConfirmationStats['total_via_reseller'] }}
                                (RM {{ number_format($pendingConfirmationStats['total_via_reseller_amount'] ?? 0, 2) }})
                            </div>
                            <div class="renewal-subtitle">
                                Via End User: {{ $pendingConfirmationStats['total_via_end_user'] }}
                                (RM {{ number_format($pendingConfirmationStats['total_via_end_user_amount'] ?? 0, 2) }})
                            </div>
                            <div class="renewal-amount orange">RM {{ number_format($pendingConfirmationStats['total_amount'], 2) }}</div>
                        </dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Box 3: Renewal Forecast -->
        <div class="renewal-card">
            <div class="renewal-card-content">
                <div class="renewal-card-layout">
                    <div class="renewal-icon-container blue">
                        <svg class="renewal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="renewal-details">
                        <dt class="renewal-title">Renewal Forecast (90 Days)</dt>
                        <dd>
                            <div class="renewal-subtitle">Total Company: {{ $renewalForecastStats['total_companies'] }}</div>
                            <div class="renewal-subtitle">
                                Via Reseller: {{ $renewalForecastStats['total_via_reseller'] }}
                                (RM {{ number_format($renewalForecastStats['total_via_reseller_amount'] ?? 0, 2) }})
                            </div>
                            <div class="renewal-subtitle">
                                Via End User: {{ $renewalForecastStats['total_via_end_user'] }}
                                (RM {{ number_format($renewalForecastStats['total_via_end_user_amount'] ?? 0, 2) }})
                            </div>
                            <div class="renewal-amount blue">RM {{ number_format($renewalForecastStats['total_amount'], 2) }}</div>
                        </dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Box 4: Pending Payment -->
        <div class="renewal-card">
            <div class="renewal-card-content">
                <div class="renewal-card-layout">
                    <div class="renewal-icon-container red">
                        <svg class="renewal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="renewal-details">
                        <dt class="renewal-title">Pending Payment</dt>
                        <dd>
                            <div class="renewal-subtitle">Total Company: {{ $pendingPaymentStats['total_companies'] }}</div>
                            <div class="renewal-subtitle">
                                Via Reseller: {{ $pendingPaymentStats['total_via_reseller'] }}
                                (RM {{ number_format($pendingPaymentStats['total_via_reseller_amount'] ?? 0, 2) }})
                            </div>
                            <div class="renewal-subtitle">
                                Via End User: {{ $pendingPaymentStats['total_via_end_user'] }}
                                (RM {{ number_format($pendingPaymentStats['total_via_end_user_amount'] ?? 0, 2) }})
                            </div>
                            <div class="renewal-amount red">RM {{ number_format($pendingPaymentStats['total_amount'], 2) }}</div>
                        </dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Box 5: Renewal Forecast Current Month (Changed from Completed Renewal) -->
        <div class="renewal-card">
            <div class="renewal-card-content">
                <div class="renewal-card-layout">
                    <div class="renewal-icon-container green">
                        <svg class="renewal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="renewal-details">
                        <dt class="renewal-title">Renewal Forecast (30 Days)</dt>
                        <dd>
                            <div class="renewal-subtitle">Total Company: {{ $renewalForecastCurrentMonthStats['total_companies'] }}</div>
                            <div class="renewal-subtitle">
                                Via Reseller: {{ $renewalForecastCurrentMonthStats['total_via_reseller'] }}
                                (RM {{ number_format($renewalForecastCurrentMonthStats['total_via_reseller_amount'] ?? 0, 2) }})
                            </div>
                            <div class="renewal-subtitle">
                                Via End User: {{ $renewalForecastCurrentMonthStats['total_via_end_user'] }}
                                (RM {{ number_format($renewalForecastCurrentMonthStats['total_via_end_user_amount'] ?? 0, 2) }})
                            </div>
                            <div class="renewal-amount green">RM {{ number_format($renewalForecastCurrentMonthStats['total_amount'], 2) }}</div>
                        </dd>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filament Table -->
    {{ $this->table }}
</x-filament-panels::page>
