<x-filament-panels::page>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        /* Tab Navigation Styles */
        .currency-tabs {
            display: flex;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e5e7eb;
            position: relative;
        }

        .currency-tab {
            padding: 8px 16px;
            font-size: 16px;
            font-weight: 600;
            color: #6b7280;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            transition: all 0.3s ease-in-out;
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 8px 8px 0 0;
            margin-right: 8px;
        }

        .currency-tab.active {
            color: #2563eb;
            border-bottom-color: #2563eb;
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
        }

        .currency-tab:hover:not(.active) {
            color: #4b5563;
            border-bottom-color: #d1d5db;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            transform: translateY(-1px);
        }

        .currency-tab-content {
            display: none;
        }

        .currency-tab-content.active {
            display: block;
        }

        /* Forecast section headers */
        .forecast-section {
            margin-bottom: 2rem;
        }

        .forecast-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            text-align: center;
        }

        .forecast-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .forecast-subtitle {
            font-size: 0.875rem;
            opacity: 0.9;
        }

        /* USD specific header */
        .forecast-header.usd {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        /* Renewal Dashboard Grid */
        .renewal-dashboard-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
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
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .renewal-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.15);
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

        /* Loading spinner */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>

    <div x-data="{
        activeCurrency: 'myr',
        loading: false,

        switchCurrency(currency) {
            this.activeCurrency = currency;
            localStorage.setItem('preferredCurrency', currency);
        },

        async refreshData() {
            this.loading = true;
            try {
                await $wire.refreshAnalysisData();
                window.location.reload();
            } catch (error) {
                console.error('Error refreshing data:', error);
            } finally {
                this.loading = false;
            }
        },

        initCurrency() {
            const savedCurrency = localStorage.getItem('preferredCurrency');
            if (savedCurrency) {
                this.activeCurrency = savedCurrency;
            }
        }
    }" x-init="initCurrency()">

        <!-- Currency Tab Navigation -->
        <div class="currency-tabs">
            <div @click="switchCurrency('myr')"
                :class="{'currency-tab': true, 'active': activeCurrency === 'myr'}">
                <i class="fas fa-money-bill-alt"></i>
                MYR Analysis
            </div>
            <div @click="switchCurrency('usd')"
                :class="{'currency-tab': true, 'active': activeCurrency === 'usd'}">
                <i class="fas fa-dollar-sign"></i>
                USD Analysis
            </div>
        </div>

        <!-- MYR Analysis Content -->
        <div :class="{'currency-tab-content': true, 'active': activeCurrency === 'myr'}">

            <!-- Forecast This Month - MYR -->
            <div class="forecast-section">
                <div class="forecast-header">
                    <div class="forecast-title">Forecast This Month (MYR)</div>
                    <div class="forecast-subtitle">{{ \Carbon\Carbon::now()->format('j F Y') }} – {{ \Carbon\Carbon::now()->endOfMonth()->format('j F Y') }}</div>
                </div>
                <div class="renewal-dashboard-grid">
                    <!-- New -->
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
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastMyr('current_month')['new']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastMyr('current_month')['new']['total_via_reseller'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('current_month')['new']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastMyr('current_month')['new']['total_via_end_user'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('current_month')['new']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount purple">RM {{ number_format($this->getAnalysisForecastMyr('current_month')['new']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Confirmation -->
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
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastMyr('current_month')['pending_confirmation']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastMyr('current_month')['pending_confirmation']['total_via_reseller'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('current_month')['pending_confirmation']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastMyr('current_month')['pending_confirmation']['total_via_end_user'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('current_month')['pending_confirmation']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount orange">RM {{ number_format($this->getAnalysisForecastMyr('current_month')['pending_confirmation']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Renewal Forecast -->
                    <div class="renewal-card">
                        <div class="renewal-card-content">
                            <div class="renewal-card-layout">
                                <div class="renewal-icon-container blue">
                                    <svg class="renewal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <div class="renewal-details">
                                    <dt class="renewal-title">Renewal Forecast</dt>
                                    <dd>
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastMyr('current_month')['renewal_forecast']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastMyr('current_month')['renewal_forecast']['total_via_reseller'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('current_month')['renewal_forecast']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastMyr('current_month')['renewal_forecast']['total_via_end_user'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('current_month')['renewal_forecast']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount blue">RM {{ number_format($this->getAnalysisForecastMyr('current_month')['renewal_forecast']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Payment -->
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
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastMyr('current_month')['pending_payment']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastMyr('current_month')['pending_payment']['total_via_reseller'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('current_month')['pending_payment']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastMyr('current_month')['pending_payment']['total_via_end_user'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('current_month')['pending_payment']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount red">RM {{ number_format($this->getAnalysisForecastMyr('current_month')['pending_payment']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Forecast Next Month - MYR -->
            <div class="forecast-section">
                <div class="forecast-header">
                    <div class="forecast-title">Forecast Next Month (MYR)</div>
                    <div class="forecast-subtitle">{{ \Carbon\Carbon::now()->addMonth()->startOfMonth()->format('j F Y') }} – {{ \Carbon\Carbon::now()->addMonth()->endOfMonth()->format('j F Y') }}</div>
                </div>
                <div class="renewal-dashboard-grid">
                    <!-- New -->
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
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastMyr('next_month')['new']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastMyr('next_month')['new']['total_via_reseller'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('next_month')['new']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastMyr('next_month')['new']['total_via_end_user'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('next_month')['new']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount purple">RM {{ number_format($this->getAnalysisForecastMyr('next_month')['new']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Confirmation -->
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
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastMyr('next_month')['pending_confirmation']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastMyr('next_month')['pending_confirmation']['total_via_reseller'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('next_month')['pending_confirmation']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastMyr('next_month')['pending_confirmation']['total_via_end_user'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('next_month')['pending_confirmation']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount orange">RM {{ number_format($this->getAnalysisForecastMyr('next_month')['pending_confirmation']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Renewal Forecast -->
                    <div class="renewal-card">
                        <div class="renewal-card-content">
                            <div class="renewal-card-layout">
                                <div class="renewal-icon-container blue">
                                    <svg class="renewal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <div class="renewal-details">
                                    <dt class="renewal-title">Renewal Forecast</dt>
                                    <dd>
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastMyr('next_month')['renewal_forecast']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastMyr('next_month')['renewal_forecast']['total_via_reseller'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('next_month')['renewal_forecast']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastMyr('next_month')['renewal_forecast']['total_via_end_user'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('next_month')['renewal_forecast']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount blue">RM {{ number_format($this->getAnalysisForecastMyr('next_month')['renewal_forecast']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Payment -->
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
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastMyr('next_month')['pending_payment']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastMyr('next_month')['pending_payment']['total_via_reseller'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('next_month')['pending_payment']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastMyr('next_month')['pending_payment']['total_via_end_user'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('next_month')['pending_payment']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount red">RM {{ number_format($this->getAnalysisForecastMyr('next_month')['pending_payment']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Forecast Next Two Months - MYR -->
            <div class="forecast-section">
                <div class="forecast-header">
                    <div class="forecast-title">Forecast Next Two Months (MYR)</div>
                    <div class="forecast-subtitle">{{ \Carbon\Carbon::now()->addMonths(2)->startOfMonth()->format('j F Y') }} – {{ \Carbon\Carbon::now()->addMonths(2)->endOfMonth()->format('j F Y') }}</div>
                </div>
                <div class="renewal-dashboard-grid">
                    <!-- New -->
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
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastMyr('next_two_months')['new']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastMyr('next_two_months')['new']['total_via_reseller'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('next_two_months')['new']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastMyr('next_two_months')['new']['total_via_end_user'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('next_two_months')['new']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount purple">RM {{ number_format($this->getAnalysisForecastMyr('next_two_months')['new']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Confirmation -->
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
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastMyr('next_two_months')['pending_confirmation']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastMyr('next_two_months')['pending_confirmation']['total_via_reseller'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('next_two_months')['pending_confirmation']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastMyr('next_two_months')['pending_confirmation']['total_via_end_user'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('next_two_months')['pending_confirmation']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount orange">RM {{ number_format($this->getAnalysisForecastMyr('next_two_months')['pending_confirmation']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Renewal Forecast -->
                    <div class="renewal-card">
                        <div class="renewal-card-content">
                            <div class="renewal-card-layout">
                                <div class="renewal-icon-container blue">
                                    <svg class="renewal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <div class="renewal-details">
                                    <dt class="renewal-title">Renewal Forecast</dt>
                                    <dd>
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastMyr('next_two_months')['renewal_forecast']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastMyr('next_two_months')['renewal_forecast']['total_via_reseller'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('next_two_months')['renewal_forecast']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastMyr('next_two_months')['renewal_forecast']['total_via_end_user'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('next_two_months')['renewal_forecast']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount blue">RM {{ number_format($this->getAnalysisForecastMyr('next_two_months')['renewal_forecast']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Payment -->
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
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastMyr('next_two_months')['pending_payment']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastMyr('next_two_months')['pending_payment']['total_via_reseller'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('next_two_months')['pending_payment']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastMyr('next_two_months')['pending_payment']['total_via_end_user'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('next_two_months')['pending_payment']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount red">RM {{ number_format($this->getAnalysisForecastMyr('next_two_months')['pending_payment']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Forecast Next Three Months - MYR -->
            <div class="forecast-section">
                <div class="forecast-header">
                    <div class="forecast-title">Forecast Next Three Months (MYR)</div>
                    <div class="forecast-subtitle">{{ \Carbon\Carbon::now()->addMonths(3)->startOfMonth()->format('j F Y') }} – {{ \Carbon\Carbon::now()->addMonths(3)->endOfMonth()->format('j F Y') }}</div>
                </div>
                <div class="renewal-dashboard-grid">
                    <!-- New -->
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
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastMyr('next_three_months')['new']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastMyr('next_three_months')['new']['total_via_reseller'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('next_three_months')['new']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastMyr('next_three_months')['new']['total_via_end_user'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('next_three_months')['new']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount purple">RM {{ number_format($this->getAnalysisForecastMyr('next_three_months')['new']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Confirmation -->
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
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastMyr('next_three_months')['pending_confirmation']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastMyr('next_three_months')['pending_confirmation']['total_via_reseller'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('next_three_months')['pending_confirmation']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastMyr('next_three_months')['pending_confirmation']['total_via_end_user'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('next_three_months')['pending_confirmation']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount orange">RM {{ number_format($this->getAnalysisForecastMyr('next_three_months')['pending_confirmation']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Renewal Forecast -->
                    <div class="renewal-card">
                        <div class="renewal-card-content">
                            <div class="renewal-card-layout">
                                <div class="renewal-icon-container blue">
                                    <svg class="renewal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <div class="renewal-details">
                                    <dt class="renewal-title">Renewal Forecast</dt>
                                    <dd>
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastMyr('next_three_months')['renewal_forecast']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastMyr('next_three_months')['renewal_forecast']['total_via_reseller'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('next_three_months')['renewal_forecast']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastMyr('next_three_months')['renewal_forecast']['total_via_end_user'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('next_three_months')['renewal_forecast']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount blue">RM {{ number_format($this->getAnalysisForecastMyr('next_three_months')['renewal_forecast']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Payment -->
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
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastMyr('next_three_months')['pending_payment']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastMyr('next_three_months')['pending_payment']['total_via_reseller'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('next_three_months')['pending_payment']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastMyr('next_three_months')['pending_payment']['total_via_end_user'] }}
                                            (RM {{ number_format($this->getAnalysisForecastMyr('next_three_months')['pending_payment']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount red">RM {{ number_format($this->getAnalysisForecastMyr('next_three_months')['pending_payment']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- USD Analysis Content -->
        <div :class="{'currency-tab-content': true, 'active': activeCurrency === 'usd'}">

            <!-- Forecast This Month - USD -->
            <div class="forecast-section">
                <div class="forecast-header usd">
                    <div class="forecast-title">Forecast This Month (USD)</div>
                    <div class="forecast-subtitle">{{ \Carbon\Carbon::now()->format('j F Y') }} – {{ \Carbon\Carbon::now()->endOfMonth()->format('j F Y') }}</div>
                </div>
                <div class="renewal-dashboard-grid">
                    <!-- New -->
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
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastUsd('current_month')['new']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastUsd('current_month')['new']['total_via_reseller'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('current_month')['new']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastUsd('current_month')['new']['total_via_end_user'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('current_month')['new']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount purple">${{ number_format($this->getAnalysisForecastUsd('current_month')['new']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Confirmation -->
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
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastUsd('current_month')['pending_confirmation']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastUsd('current_month')['pending_confirmation']['total_via_reseller'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('current_month')['pending_confirmation']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastUsd('current_month')['pending_confirmation']['total_via_end_user'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('current_month')['pending_confirmation']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount orange">${{ number_format($this->getAnalysisForecastUsd('current_month')['pending_confirmation']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Renewal Forecast -->
                    <div class="renewal-card">
                        <div class="renewal-card-content">
                            <div class="renewal-card-layout">
                                <div class="renewal-icon-container blue">
                                    <svg class="renewal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <div class="renewal-details">
                                    <dt class="renewal-title">Renewal Forecast</dt>
                                    <dd>
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastUsd('current_month')['renewal_forecast']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastUsd('current_month')['renewal_forecast']['total_via_reseller'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('current_month')['renewal_forecast']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastUsd('current_month')['renewal_forecast']['total_via_end_user'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('current_month')['renewal_forecast']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount blue">${{ number_format($this->getAnalysisForecastUsd('current_month')['renewal_forecast']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Payment -->
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
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastUsd('current_month')['pending_payment']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastUsd('current_month')['pending_payment']['total_via_reseller'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('current_month')['pending_payment']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastUsd('current_month')['pending_payment']['total_via_end_user'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('current_month')['pending_payment']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount red">${{ number_format($this->getAnalysisForecastUsd('current_month')['pending_payment']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Forecast Next Month - USD -->
            <div class="forecast-section">
                <div class="forecast-header usd">
                    <div class="forecast-title">Forecast Next Month (USD)</div>
                    <div class="forecast-subtitle">{{ \Carbon\Carbon::now()->addMonth()->startOfMonth()->format('j F Y') }} – {{ \Carbon\Carbon::now()->addMonth()->endOfMonth()->format('j F Y') }}</div>
                </div>
                <div class="renewal-dashboard-grid">
                    <!-- New -->
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
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastUsd('next_month')['new']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastUsd('next_month')['new']['total_via_reseller'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('next_month')['new']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastUsd('next_month')['new']['total_via_end_user'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('next_month')['new']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount purple">${{ number_format($this->getAnalysisForecastUsd('next_month')['new']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Confirmation -->
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
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastUsd('next_month')['pending_confirmation']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastUsd('next_month')['pending_confirmation']['total_via_reseller'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('next_month')['pending_confirmation']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastUsd('next_month')['pending_confirmation']['total_via_end_user'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('next_month')['pending_confirmation']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount orange">${{ number_format($this->getAnalysisForecastUsd('next_month')['pending_confirmation']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Renewal Forecast -->
                    <div class="renewal-card">
                        <div class="renewal-card-content">
                            <div class="renewal-card-layout">
                                <div class="renewal-icon-container blue">
                                    <svg class="renewal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <div class="renewal-details">
                                    <dt class="renewal-title">Renewal Forecast</dt>
                                    <dd>
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastUsd('next_month')['renewal_forecast']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastUsd('next_month')['renewal_forecast']['total_via_reseller'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('next_month')['renewal_forecast']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastUsd('next_month')['renewal_forecast']['total_via_end_user'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('next_month')['renewal_forecast']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount blue">${{ number_format($this->getAnalysisForecastUsd('next_month')['renewal_forecast']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Payment -->
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
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastUsd('next_month')['pending_payment']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastUsd('next_month')['pending_payment']['total_via_reseller'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('next_month')['pending_payment']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastUsd('next_month')['pending_payment']['total_via_end_user'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('next_month')['pending_payment']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount red">${{ number_format($this->getAnalysisForecastUsd('next_month')['pending_payment']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Forecast Next Two Months - USD -->
            <div class="forecast-section">
                <div class="forecast-header usd">
                    <div class="forecast-title">Forecast Next Two Months (USD)</div>
                    <div class="forecast-subtitle">{{ \Carbon\Carbon::now()->addMonths(2)->startOfMonth()->format('j F Y') }} – {{ \Carbon\Carbon::now()->addMonths(2)->endOfMonth()->format('j F Y') }}</div>
                </div>
                <div class="renewal-dashboard-grid">
                    <!-- New -->
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
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastUsd('next_two_months')['new']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastUsd('next_two_months')['new']['total_via_reseller'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('next_two_months')['new']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastUsd('next_two_months')['new']['total_via_end_user'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('next_two_months')['new']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount purple">${{ number_format($this->getAnalysisForecastUsd('next_two_months')['new']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Confirmation -->
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
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastUsd('next_two_months')['pending_confirmation']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastUsd('next_two_months')['pending_confirmation']['total_via_reseller'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('next_two_months')['pending_confirmation']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastUsd('next_two_months')['pending_confirmation']['total_via_end_user'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('next_two_months')['pending_confirmation']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount orange">${{ number_format($this->getAnalysisForecastUsd('next_two_months')['pending_confirmation']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Renewal Forecast -->
                    <div class="renewal-card">
                        <div class="renewal-card-content">
                            <div class="renewal-card-layout">
                                <div class="renewal-icon-container blue">
                                    <svg class="renewal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <div class="renewal-details">
                                    <dt class="renewal-title">Renewal Forecast</dt>
                                    <dd>
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastUsd('next_two_months')['renewal_forecast']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastUsd('next_two_months')['renewal_forecast']['total_via_reseller'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('next_two_months')['renewal_forecast']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastUsd('next_two_months')['renewal_forecast']['total_via_end_user'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('next_two_months')['renewal_forecast']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount blue">${{ number_format($this->getAnalysisForecastUsd('next_two_months')['renewal_forecast']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Payment -->
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
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastUsd('next_two_months')['pending_payment']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastUsd('next_two_months')['pending_payment']['total_via_reseller'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('next_two_months')['pending_payment']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastUsd('next_two_months')['pending_payment']['total_via_end_user'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('next_two_months')['pending_payment']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount red">${{ number_format($this->getAnalysisForecastUsd('next_two_months')['pending_payment']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Forecast Next Three Months - USD -->
            <div class="forecast-section">
                <div class="forecast-header usd">
                    <div class="forecast-title">Forecast Next Three Months (USD)</div>
                    <div class="forecast-subtitle">{{ \Carbon\Carbon::now()->addMonths(3)->startOfMonth()->format('j F Y') }} – {{ \Carbon\Carbon::now()->addMonths(3)->endOfMonth()->format('j F Y') }}</div>
                </div>
                <div class="renewal-dashboard-grid">
                    <!-- New -->
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
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastUsd('next_three_months')['new']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastUsd('next_three_months')['new']['total_via_reseller'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('next_three_months')['new']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastUsd('next_three_months')['new']['total_via_end_user'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('next_three_months')['new']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount purple">${{ number_format($this->getAnalysisForecastUsd('next_three_months')['new']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Confirmation -->
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
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastUsd('next_three_months')['pending_confirmation']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastUsd('next_three_months')['pending_confirmation']['total_via_reseller'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('next_three_months')['pending_confirmation']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastUsd('next_three_months')['pending_confirmation']['total_via_end_user'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('next_three_months')['pending_confirmation']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount orange">${{ number_format($this->getAnalysisForecastUsd('next_three_months')['pending_confirmation']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Renewal Forecast -->
                    <div class="renewal-card">
                        <div class="renewal-card-content">
                            <div class="renewal-card-layout">
                                <div class="renewal-icon-container blue">
                                    <svg class="renewal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <div class="renewal-details">
                                    <dt class="renewal-title">Renewal Forecast</dt>
                                    <dd>
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastUsd('next_three_months')['renewal_forecast']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastUsd('next_three_months')['renewal_forecast']['total_via_reseller'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('next_three_months')['renewal_forecast']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastUsd('next_three_months')['renewal_forecast']['total_via_end_user'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('next_three_months')['renewal_forecast']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount blue">${{ number_format($this->getAnalysisForecastUsd('next_three_months')['renewal_forecast']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Payment -->
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
                                        <div class="renewal-subtitle">Total Company: {{ $this->getAnalysisForecastUsd('next_three_months')['pending_payment']['total_companies'] }}</div>
                                        <div class="renewal-subtitle">
                                            Via Reseller: {{ $this->getAnalysisForecastUsd('next_three_months')['pending_payment']['total_via_reseller'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('next_three_months')['pending_payment']['total_via_reseller_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-subtitle">
                                            Via End User: {{ $this->getAnalysisForecastUsd('next_three_months')['pending_payment']['total_via_end_user'] }}
                                            (${{ number_format($this->getAnalysisForecastUsd('next_three_months')['pending_payment']['total_via_end_user_amount'] ?? 0, 2) }})
                                        </div>
                                        <div class="renewal-amount red">${{ number_format($this->getAnalysisForecastUsd('next_three_months')['pending_payment']['total_amount'], 2) }}</div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
