{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/invoices-table.blade.php --}}
<x-filament-panels::page>
    <style>
        /* Invoice Dashboard Grid */
        .invoice-dashboard-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
        }

        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .invoice-dashboard-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .invoice-dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .invoice-dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Card styling */
        .invoice-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        .dark .invoice-card {
            background-color: #374151;
            border-color: #4b5563;
        }

        .invoice-card-content {
            padding: 1rem;
        }

        .invoice-card-layout {
            display: flex;
            align-items: center; /* Changed from flex-start to center */
            gap: 0.75rem;
        }

        /* Icon container */
        .invoice-icon-container {
            flex-shrink: 0;
            padding: 0.75rem; /* Increased padding */
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .invoice-icon-container.all-year {
            background-color: rgba(79, 70, 229, 0.1);
        }

        .invoice-icon-container.current-year {
            background-color: rgba(37, 99, 235, 0.1);
        }

        .invoice-icon-container.current-month {
            background-color: rgba(124, 58, 237, 0.1);
        }

        .invoice-icon-container.hrdf {
            background-color: rgba(16, 185, 129, 0.1);
        }

        .invoice-icon-container.product {
            background-color: rgba(217, 119, 6, 0.1);
        }

        .invoice-icon {
            width: 1.5rem; /* Increased from 1.25rem */
            height: 1.5rem; /* Increased from 1.25rem */
        }

        .invoice-icon-container.all-year .invoice-icon {
            color: rgba(79, 70, 229, 1);
        }

        .invoice-icon-container.current-year .invoice-icon {
            color: rgba(37, 99, 235, 1);
        }

        .invoice-icon-container.current-month .invoice-icon {
            color: rgba(124, 58, 237, 1);
        }

        .invoice-icon-container.hrdf .invoice-icon {
            color: rgba(16, 185, 129, 1);
        }

        .invoice-icon-container.product .invoice-icon {
            color: rgba(217, 119, 6, 1);
        }

        /* Invoice details */
        .invoice-details {
            flex: 1;
            width: 0;
        }

        .invoice-title {
            font-size: 1rem; /* Increased from 0.875rem */
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.5rem;
            line-height: 1.2;
        }

        .dark .invoice-title {
            color: #f9fafb;
        }

        .invoice-data {
            display: flex;
            flex-direction: column;
            gap: 0.25rem; /* Increased from 0.125rem */
            font-size: 0.75rem; /* Increased from 0.625rem */
        }

        .payment-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.25rem 0; /* Increased from 0.125rem */
        }

        .payment-label {
            font-weight: 500;
            min-width: 0;
            flex: 1;
        }

        .payment-amount {
            font-weight: 600;
            text-align: right;
            white-space: nowrap;
        }

        .payment-full .payment-label,
        .payment-full .payment-amount {
            color: #059669;
        }

        .dark .payment-full .payment-label,
        .dark .payment-full .payment-amount {
            color: #10b981;
        }

        .payment-partial .payment-label,
        .payment-partial .payment-amount {
            color: #d97706;
        }

        .dark .payment-partial .payment-label,
        .dark .payment-partial .payment-amount {
            color: #f59e0b;
        }

        .payment-unpaid .payment-label,
        .payment-unpaid .payment-amount {
            color: #dc2626;
        }

        .dark .payment-unpaid .payment-label,
        .dark .payment-unpaid .payment-amount {
            color: #ef4444;
        }

        .payment-total {
            border-top: 1px solid #e5e7eb;
            padding-top: 0.375rem; /* Increased from 0.25rem */
            margin-top: 0.375rem; /* Increased from 0.25rem */
        }

        .dark .payment-total {
            border-top-color: #4b5563;
        }

        .payment-total .payment-label,
        .payment-total .payment-amount {
            color: #2563eb;
            font-weight: 700;
        }

        .dark .payment-total .payment-label,
        .dark .payment-total .payment-amount {
            color: #3b82f6;
        }
    </style>

    {{-- Summary Boxes --}}
    <div class="invoice-dashboard-grid">
        {{-- All Year Box --}}
        <div class="invoice-card">
            <div class="invoice-card-content">
                <h3 class="invoice-title">All Year</h3>
                <div class="invoice-card-layout">
                    <div class="invoice-icon-container all-year">
                        <svg class="invoice-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="invoice-details">
                        <div class="invoice-data">
                            <div class="payment-item payment-full">
                                <span class="payment-label">Full Payment:</span>
                                <span class="payment-amount">RM {{ number_format($summaryData['all_year']['full_payment_amount'] ?? 0, 2) }}</span>
                            </div>
                            <div class="payment-item payment-partial">
                                <span class="payment-label">Partial Payment:</span>
                                <span class="payment-amount">RM {{ number_format($summaryData['all_year']['partial_payment_amount'] ?? 0, 2) }}</span>
                            </div>
                            <div class="payment-item payment-unpaid">
                                <span class="payment-label">UnPaid:</span>
                                <span class="payment-amount">RM {{ number_format($summaryData['all_year']['unpaid_amount'] ?? 0, 2) }}</span>
                            </div>
                            <div class="payment-item payment-total">
                                <span class="payment-label">Total Amount:</span>
                                <span class="payment-amount">RM {{ number_format($summaryData['all_year']['total_amount'] ?? 0, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Current Year Box --}}
        <div class="invoice-card">
            <div class="invoice-card-content">
                <h3 class="invoice-title">Current Year</h3>
                <div class="invoice-card-layout">
                    <div class="invoice-icon-container current-year">
                        <svg class="invoice-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="invoice-details">
                        <div class="invoice-data">
                            <div class="payment-item payment-full">
                                <span class="payment-label">Full Payment:</span>
                                <span class="payment-amount">RM {{ number_format($summaryData['current_year']['full_payment_amount'] ?? 0, 2) }}</span>
                            </div>
                            <div class="payment-item payment-partial">
                                <span class="payment-label">Partial Payment:</span>
                                <span class="payment-amount">RM {{ number_format($summaryData['current_year']['partial_payment_amount'] ?? 0, 2) }}</span>
                            </div>
                            <div class="payment-item payment-unpaid">
                                <span class="payment-label">UnPaid:</span>
                                <span class="payment-amount">RM {{ number_format($summaryData['current_year']['unpaid_amount'] ?? 0, 2) }}</span>
                            </div>
                            <div class="payment-item payment-total">
                                <span class="payment-label">Total Amount:</span>
                                <span class="payment-amount">RM {{ number_format($summaryData['current_year']['total_amount'] ?? 0, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Current Month Box --}}
        <div class="invoice-card">
            <div class="invoice-card-content">
                <h3 class="invoice-title">Current Month</h3>
                <div class="invoice-card-layout">
                    <div class="invoice-icon-container current-month">
                        <svg class="invoice-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="invoice-details">
                        <div class="invoice-data">
                            <div class="payment-item payment-full">
                                <span class="payment-label">Full Payment:</span>
                                <span class="payment-amount">RM {{ number_format($summaryData['current_month']['full_payment_amount'] ?? 0, 2) }}</span>
                            </div>
                            <div class="payment-item payment-partial">
                                <span class="payment-label">Partial Payment:</span>
                                <span class="payment-amount">RM {{ number_format($summaryData['current_month']['partial_payment_amount'] ?? 0, 2) }}</span>
                            </div>
                            <div class="payment-item payment-unpaid">
                                <span class="payment-label">UnPaid:</span>
                                <span class="payment-amount">RM {{ number_format($summaryData['current_month']['unpaid_amount'] ?? 0, 2) }}</span>
                            </div>
                            <div class="payment-item payment-total">
                                <span class="payment-label">Total Amount:</span>
                                <span class="payment-amount">RM {{ number_format($summaryData['current_month']['total_amount'] ?? 0, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- HRDF Invoice Box --}}
        <div class="invoice-card">
            <div class="invoice-card-content">
                <h3 class="invoice-title">HRDF Invoice From All Year</h3>
                <div class="invoice-card-layout">
                    <div class="invoice-icon-container hrdf">
                        <svg class="invoice-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div class="invoice-details">
                        <div class="invoice-data">
                            <div class="payment-item payment-full">
                                <span class="payment-label">Full Payment:</span>
                                <span class="payment-amount">RM {{ number_format($summaryData['hrdf_all_year']['full_payment_amount'] ?? 0, 2) }}</span>
                            </div>
                            <div class="payment-item payment-partial">
                                <span class="payment-label">Partial Payment:</span>
                                <span class="payment-amount">RM {{ number_format($summaryData['hrdf_all_year']['partial_payment_amount'] ?? 0, 2) }}</span>
                            </div>
                            <div class="payment-item payment-unpaid">
                                <span class="payment-label">UnPaid:</span>
                                <span class="payment-amount">RM {{ number_format($summaryData['hrdf_all_year']['unpaid_amount'] ?? 0, 2) }}</span>
                            </div>
                            <div class="payment-item payment-total">
                                <span class="payment-label">Total Amount:</span>
                                <span class="payment-amount">RM {{ number_format($summaryData['hrdf_all_year']['total_amount'] ?? 0, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Product Invoice Box --}}
        <div class="invoice-card">
            <div class="invoice-card-content">
                <h3 class="invoice-title">Product Invoice From All Year</h3>
                <div class="invoice-card-layout">
                    <div class="invoice-icon-container product">
                        <svg class="invoice-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <div class="invoice-details">
                        <div class="invoice-data">
                            <div class="payment-item payment-full">
                                <span class="payment-label">Full Payment:</span>
                                <span class="payment-amount">RM {{ number_format($summaryData['product_all_year']['full_payment_amount'] ?? 0, 2) }}</span>
                            </div>
                            <div class="payment-item payment-partial">
                                <span class="payment-label">Partial Payment:</span>
                                <span class="payment-amount">RM {{ number_format($summaryData['product_all_year']['partial_payment_amount'] ?? 0, 2) }}</span>
                            </div>
                            <div class="payment-item payment-unpaid">
                                <span class="payment-label">UnPaid:</span>
                                <span class="payment-amount">RM {{ number_format($summaryData['product_all_year']['unpaid_amount'] ?? 0, 2) }}</span>
                            </div>
                            <div class="payment-item payment-total">
                                <span class="payment-label">Total Amount:</span>
                                <span class="payment-amount">RM {{ number_format($summaryData['product_all_year']['total_amount'] ?? 0, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    {{ $this->table }}
</x-filament-panels::page>
