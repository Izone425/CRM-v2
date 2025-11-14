<x-filament-panels::page>
    <style>
        /* Debtor Dashboard Grid */
        .debtor-dashboard-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
        }

        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .debtor-dashboard-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .debtor-dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .debtor-dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Card styling */
        .debtor-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .debtor-card-content {
            padding: 1.25rem 1rem;
        }

        .debtor-card-layout {
            display: flex;
            align-items: center;
        }

        /* Icon container */
        .debtor-icon-container {
            flex-shrink: 0;
            padding: 0.75rem;
            border-radius: 0.375rem;
        }

        .debtor-icon-container.primary {
            background-color: rgba(79, 70, 229, 0.1);
        }

        .debtor-icon-container.blue {
            background-color: rgba(37, 99, 235, 0.1);
        }

        .debtor-icon-container.purple {
            background-color: rgba(124, 58, 237, 0.1);
        }

        .debtor-icon-container.red {
            background-color: rgba(220, 38, 38, 0.1);
        }

        .debtor-icon-container.amber {
            background-color: rgba(217, 119, 6, 0.1);
        }

        .debtor-icon {
            width: 1.5rem;
            height: 1.5rem;
        }

        .debtor-icon-container.primary .debtor-icon {
            color: rgba(79, 70, 229, 1);
        }

        .debtor-icon-container.blue .debtor-icon {
            color: rgba(37, 99, 235, 1);
        }

        .debtor-icon-container.purple .debtor-icon {
            color: rgba(124, 58, 237, 1);
        }

        .debtor-icon-container.red .debtor-icon {
            color: rgba(220, 38, 38, 1);
        }

        .debtor-icon-container.amber .debtor-icon {
            color: rgba(217, 119, 6, 1);
        }

        /* Debtor details */
        .debtor-details {
            flex: 1;
            width: 0;
            margin-left: 1.25rem;
        }

        .debtor-title {
            font-size: 1rem;
            font-weight: 500;
            color: #111827;
        }

        .debtor-subtitle {
            font-size: 0.875rem;
            color: #6B7280;
        }

        .debtor-amount-label {
            font-size: 1rem;
            font-weight: 500;
            color: #111827;
        }

        .debtor-amount {
            font-size: 1.25rem;
            font-weight: 700;
        }

        .debtor-amount.primary {
            color: rgba(79, 70, 229, 1);
        }

        .debtor-amount.blue {
            color: rgba(37, 99, 235, 1);
        }

        .debtor-amount.purple {
            color: rgba(124, 58, 237, 1);
        }

        .debtor-amount.red {
            color: rgba(220, 38, 38, 1);
        }

        .debtor-amount.amber {
            color: rgba(217, 119, 6, 1);
        }
    </style>
    <!-- Dashboard Cards -->
    <div class="debtor-dashboard-grid" wire:poll.10s>
        <!-- Box 1: All Debtor -->
        <div class="debtor-card">
            <div class="debtor-card-content">
                <div class="debtor-card-layout">
                    <div class="debtor-icon-container primary">
                        <svg class="debtor-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="debtor-details">
                        <dt class="debtor-title">All Debtor</dt>
                        <dd>
                            <div class="debtor-subtitle">Total Invoice: {{ $allDebtorStats['total_invoices'] }}</div>
                            <div class="debtor-amount-label">Total Amount:</div>
                            <div class="debtor-amount primary">{{ $allDebtorStats['formatted_amount'] }}</div>
                        </dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Box 2: HRDF Debtor -->
        <div class="debtor-card">
            <div class="debtor-card-content">
                <div class="debtor-card-layout">
                    <div class="debtor-icon-container blue">
                        <svg class="debtor-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div class="debtor-details">
                        <dt class="debtor-title">HRDF Debtor</dt>
                        <dd>
                            <div class="debtor-subtitle">Total Invoice: {{ $hrdfDebtorStats['total_invoices'] }}</div>
                            <div class="debtor-amount-label">Total Amount:</div>
                            <div class="debtor-amount blue">{{ $hrdfDebtorStats['formatted_amount'] }}</div>
                        </dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Box 3: Product Debtor -->
        <div class="debtor-card">
            <div class="debtor-card-content">
                <div class="debtor-card-layout">
                    <div class="debtor-icon-container purple">
                        <svg class="debtor-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <div class="debtor-details">
                        <dt class="debtor-title">Product Debtor</dt>
                        <dd>
                            <div class="debtor-subtitle">Total Invoice: {{ $productDebtorStats['total_invoices'] }}</div>
                            <div class="debtor-amount-label">Total Amount:</div>
                            <div class="debtor-amount purple">{{ $productDebtorStats['formatted_amount'] }}</div>
                        </dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Box 4: Unpaid Debtor -->
        <div class="debtor-card">
            <div class="debtor-card-content">
                <div class="debtor-card-layout">
                    <div class="debtor-icon-container red">
                        <svg class="debtor-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="debtor-details">
                        <dt class="debtor-title">UnPaid Debtor</dt>
                        <dd>
                            <div class="debtor-subtitle">Total Invoice: {{ $unpaidDebtorStats['total_invoices'] }}</div>
                            <div class="debtor-amount-label">Total Amount:</div>
                            <div class="debtor-amount red">{{ $unpaidDebtorStats['formatted_amount'] }}</div>
                        </dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Box 5: Partial Payment Debtor -->
        <div class="debtor-card">
            <div class="debtor-card-content">
                <div class="debtor-card-layout">
                    <div class="debtor-icon-container amber">
                        <svg class="debtor-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="debtor-details">
                        <dt class="debtor-title">Partial Payment Debtor</dt>
                        <dd>
                            <div class="debtor-subtitle">Total Invoice: {{ $partialPaymentDebtorStats['total_invoices'] }}</div>
                            <div class="debtor-amount-label">Total Amount:</div>
                            <div class="debtor-amount amber">{{ $partialPaymentDebtorStats['formatted_amount'] }}</div>
                        </dd>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filament Table -->
    {{ $this->table }}
</x-filament-panels::page>
