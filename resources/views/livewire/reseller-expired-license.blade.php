<div>
    <style>
        .title-section {
            padding: 0;
            margin-bottom: 1.5rem;
        }

        .title-section h2 {
            color: #111827;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }

        .title-section p {
            color: #6b7280;
            font-size: 0.875rem;
            margin: 0.25rem 0 0 0;
        }

        .search-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            pointer-events: none;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            background: white;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
        }

        .custom-table thead {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }

        .custom-table th {
            padding: 1rem 1.5rem;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #e2e8f0;
        }

        .custom-table th button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
            font-weight: 600;
            transition: color 0.2s;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .custom-table th button:hover {
            color: #667eea;
        }

        .custom-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s ease;
        }

        .custom-table tbody tr:hover {
            background: linear-gradient(90deg, #f8fafc 0%, #ffffff 100%);
        }

        .custom-table tbody tr.row-expanded {
            background: linear-gradient(90deg, #f0f4ff 0%, #e8efff 100%);
        }

        .custom-table td {
            padding: 1rem 1.5rem;
            font-size: 0.875rem;
            color: #1f2937;
        }

        .company-name {
            font-weight: 600;
            color: #111827;
        }

        .date-cell {
            color: #6b7280;
        }

        .status-badge {
            display: inline-flex;
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 20px;
            letter-spacing: 0.025em;
        }

        .status-red {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .status-orange {
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            color: #9a3412;
            border: 1px solid #fdba74;
        }

        .status-yellow {
            background: linear-gradient(135deg, #fefce8 0%, #fef9c3 100%);
            color: #854d0e;
            border: 1px solid #fde047;
        }

        .status-green {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            color: #166534;
            border: 1px solid #86efac;
        }

        .expand-arrow {
            display: inline-block;
            transition: all 0.3s ease;
            opacity: 0;
            color: #ea6666;
            font-size: 0.875rem;
        }

        tr:hover .expand-arrow {
            opacity: 1;
        }

        .expand-arrow.rotated {
            opacity: 0;
            visibility: hidden;
        }

        .details-section {
            padding: 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .invoice-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            margin-bottom: 1rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .invoice-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .invoice-header {
            font-weight: 600;
            color: #667eea;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
        }

        .product-table thead {
            background: #f9fafb;
        }

        .product-table th {
            padding: 0.625rem 1rem;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            border-bottom: 1px solid #e5e7eb;
        }

        .product-table td {
            padding: 0.625rem 1rem;
            font-size: 0.8125rem;
            color: #374151;
            border-bottom: 1px solid #f3f4f6;
        }

        .product-table tbody tr:hover {
            background: #f9fafb;
        }

        .empty-state {
            padding: 3rem 1.5rem;
            text-align: center;
            color: #9ca3af;
        }

        .sort-icon {
            width: 1rem;
            height: 1rem;
        }

        .tabs-container {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .tab-button {
            padding: 0.75rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #6b7280;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            bottom: -2px;
        }

        .tab-button:hover {
            color: #667eea;
            background: #f9fafb;
        }

        .tab-button.active {
            color: #667eea;
            border-bottom-color: #667eea;
            background: #f0f4ff;
        }

        .tab-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.5rem;
            height: 1.5rem;
            padding: 0 0.5rem;
            margin-left: 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 12px;
            background: #e5e7eb;
            color: #6b7280;
        }

        .tab-button.active .tab-count {
            background: #667eea;
            color: white;
        }

        .pagination-wrapper {
            padding: 1.5rem;
            background: white;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: center;
        }

        .pagination-wrapper nav {
            display: flex;
            gap: 0.5rem;
        }

        .pagination-wrapper a,
        .pagination-wrapper span {
            padding: 0.5rem 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .pagination-wrapper a {
            color: #667eea;
            cursor: pointer;
        }

        .pagination-wrapper a:hover {
            background: #f0f4ff;
            border-color: #667eea;
        }

        .pagination-wrapper span.current {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        /* License Summary Styles */
        .license-summary-table {
            margin-bottom: 1.5rem;
        }

        .license-summary-table table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .license-summary-table th {
            padding: 12px 8px;
            text-align: center;
            border: 1px solid #e5e7eb;
            vertical-align: middle;
            font-weight: 600;
            font-size: 14px;
        }

        /* Module column widths - 3/4 of each pair */
        .module-col {
            width: 18.75% !important;
            text-align: center !important;
            padding-left: 12px !important;
        }

        /* Headcount column widths - 1/4 of each pair */
        .headcount-col {
            width: 6.25% !important;
            text-align: center !important;
            font-weight: bold !important;
        }

        /* Color themes for each module */
        .attendance-module {
            background-color: rgba(34, 197, 94, 0.1) !important;
            color: rgba(34, 197, 94, 1) !important;
        }
        .attendance-count {
            background-color: rgba(34, 197, 94, 1) !important;
            color: white !important;
        }

        .leave-module {
            background-color: rgba(37, 99, 235, 0.1) !important;
            color: rgba(37, 99, 235, 1) !important;
        }
        .leave-count {
            background-color: rgba(37, 99, 235, 1) !important;
            color: white !important;
        }

        .claim-module {
            background-color: rgba(124, 58, 237, 0.1) !important;
            color: rgba(124, 58, 237, 1) !important;
        }
        .claim-count {
            background-color: rgba(124, 58, 237, 1) !important;
            color: white !important;
        }

        .payroll-module {
            background-color: rgba(249, 115, 22, 0.1) !important;
            color: rgba(249, 115, 22, 1) !important;
        }
        .payroll-count {
            background-color: rgba(249, 115, 22, 1) !important;
            color: white !important;
        }

        /* Product row background colors */
        .product-row-ta {
            background-color: rgba(34, 197, 94, 0.1) !important;
        }
        .product-row-leave {
            background-color: rgba(37, 99, 235, 0.1) !important;
        }
        .product-row-claim {
            background-color: rgba(124, 58, 237, 0.1) !important;
        }
        .product-row-payroll {
            background-color: rgba(249, 115, 22, 0.1) !important;
        }
    </style>

    <!-- Tabs -->
    <div class="tabs-container">
        <button
            wire:click="switchTab('90days')"
            class="tab-button {{ $activeTab === '90days' ? 'active' : '' }}">
            Expired within 90 Days
            <span class="tab-count">{{ $expiredWithin90DaysCount ?? 0 }}</span>
        </button>
        <button
            wire:click="switchTab('all')"
            class="tab-button {{ $activeTab === 'all' ? 'active' : '' }}">
            All Expired Licenses
            <span class="tab-count">{{ $allExpiredCount ?? 0 }}</span>
        </button>
    </div>

    <!-- Search Input -->
    <div class="search-wrapper">
        <div class="search-icon">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
        <input
            type="text"
            wire:model.live="search"
            placeholder="Search by company name"
            class="search-input"
        >
    </div>

    <!-- Table -->
    <div class="table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Company Name</th>
                    <th>
                        <button wire:click="sortBy('f_expiry_date')">
                            Expiry Date
                            <svg class="sort-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($sortField === 'f_expiry_date')
                                    @if($sortDirection === 'desc')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    @endif
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                @endif
                            </svg>
                        </button>
                    </th>
                    <th>
                        <button wire:click="sortBy('days_until_expiry')">
                            Days Until Expiry
                            <svg class="sort-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($sortField === 'days_until_expiry')
                                    @if($sortDirection === 'desc')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    @endif
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                @endif
                            </svg>
                        </button>
                    </th>
                    <th style="width: 100px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($companies as $company)
                    <tr wire:click="toggleExpand('{{ $company->f_id }}')"
                        wire:key="company-{{ $company->f_id }}"
                        class="{{ $expandedCompany == $company->f_id ? 'row-expanded' : '' }}"
                        style="cursor: pointer;">
                        <td>{{ $loop->iteration }}</td>
                        <td class="company-name">
                            {{ strtoupper($company->f_company_name) }}
                        </td>
                        <td class="date-cell">
                            {{ date('Y-m-d', strtotime($company->f_expiry_date)) }}
                        </td>
                        <td>
                            <span class="status-badge
                                @if($company->days_until_expiry == 0) status-red
                                @elseif($company->days_until_expiry < 7) status-orange
                                @elseif($company->days_until_expiry < 14) status-yellow
                                @else status-green
                                @endif">
                                {{ $company->days_until_expiry }} days
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <span class="expand-arrow {{ $expandedCompany == $company->f_id ? 'rotated' : '' }}">
                                + More Details
                            </span>
                        </td>
                    </tr>

                    @if($expandedCompany == $company->f_id)
                        <tr wire:key="details-{{ $company->f_id }}">
                            <td colspan="5" style="padding: 0;">
                                <div class="details-section">
                                    @if(!empty($invoiceDetails))
                                        <!-- License Summary Table -->
                                        @if(isset($invoiceDetails['_summary']))
                                            <div class="license-summary-table">
                                                <table>
                                                    <thead>
                                                        <tr>
                                                            <th class="module-col attendance-module">ATTENDANCE</th>
                                                            <th class="headcount-col attendance-count">{{ $invoiceDetails['_summary']['attendance'] }}</th>
                                                            <th class="module-col leave-module">LEAVE</th>
                                                            <th class="headcount-col leave-count">{{ $invoiceDetails['_summary']['leave'] }}</th>
                                                            <th class="module-col claim-module">CLAIM</th>
                                                            <th class="headcount-col claim-count">{{ $invoiceDetails['_summary']['claim'] }}</th>
                                                            <th class="module-col payroll-module">PAYROLL</th>
                                                            <th class="headcount-col payroll-count">{{ $invoiceDetails['_summary']['payroll'] }}</th>
                                                        </tr>
                                                    </thead>
                                                </table>
                                            </div>
                                        @endif

                                        @foreach($invoiceDetails as $invoiceNo => $invoice)
                                            @if($invoiceNo === '_summary') @continue @endif
                                            <div class="invoice-card">
                                                <div class="invoice-header">
                                                    Invoice: {{ $invoiceNo }}
                                                </div>

                                                <table class="product-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Product Name</th>
                                                            <th style="width: 20%;">Total User</th>
                                                            <th style="width: 12%;">Cycle</th>
                                                            <th style="width: 20%;">Start Date</th>
                                                            <th style="width: 20%;">Expiry Date</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($invoice['products'] as $product)
                                                            @php
                                                                $productClass = '';
                                                                if (strpos($product['f_name'], 'TimeTec TA') !== false) {
                                                                    $productClass = 'product-row-ta';
                                                                } elseif (strpos($product['f_name'], 'TimeTec Leave') !== false) {
                                                                    $productClass = 'product-row-leave';
                                                                } elseif (strpos($product['f_name'], 'TimeTec Claim') !== false) {
                                                                    $productClass = 'product-row-claim';
                                                                } elseif (strpos($product['f_name'], 'TimeTec Payroll') !== false) {
                                                                    $productClass = 'product-row-payroll';
                                                                }
                                                            @endphp
                                                            <tr class="{{ $productClass }}">
                                                                <td>{{ $product['f_name'] }}</td>
                                                                <td>{{ $product['f_total_user'] }}</td>
                                                                <td>{{ $product['billing_cycle'] }}</td>
                                                                <td>{{ date('Y-m-d', strtotime($product['f_start_date'])) }}</td>
                                                                <td>{{ date('Y-m-d', strtotime($product['f_expiry_date'])) }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endforeach
                                    @else
                                        <p style="color: #6b7280; font-size: 0.875rem;">No invoice details available.</p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="5" class="empty-state">
                            No licenses found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
