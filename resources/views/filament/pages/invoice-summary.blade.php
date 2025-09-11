<x-filament-panels::page>
    <style>
        .invoice-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .invoice-box {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s ease-in-out;
        }

        .invoice-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .invoice-title {
            padding: 12px 16px;
            font-weight: 600;
            font-size: 0.875rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: white;
            background-color: #3b82f6;
        }

        .invoice-content {
            padding: 10px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .invoice-stat {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-label {
            color: #4b5563;
            font-weight: 500;
            font-size: 0.875rem;
        }

        .stat-value {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .stat-total {
            font-weight: 700;
        }

        .divider {
            height: 1px;
            background-color: #e5e7eb;
            margin: 8px 0;
        }

        .summary-header {
            background-color: white;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #e5e7eb;
        }

        .ytd-sales {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
        }

        .filters {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-label {
            font-weight: 500;
            color: #4b5563;
        }

        /* Status colors */
        .bg-green-100 {
            background-color: #d1fae5;
        }

        .text-green-800 {
            color: #065f46;
        }

        .bg-yellow-100 {
            background-color: #fef3c7;
        }

        .text-yellow-800 {
            color: #92400e;
        }

        .bg-red-100 {
            background-color: #fee2e2;
        }

        .text-red-800 {
            color: #991b1b;
        }

        .highlight {
            color: #3b82f6;
        }

        /* Month themes */
        .jan-theme .invoice-title { background-color: #3b82f6; }
        .feb-theme .invoice-title { background-color: #8b5cf6; }
        .mar-theme .invoice-title { background-color: #ec4899; }
        .apr-theme .invoice-title { background-color: #10b981; }
        .may-theme .invoice-title { background-color: #f59e0b; }
        .jun-theme .invoice-title { background-color: #6366f1; }
        .jul-theme .invoice-title { background-color: #ef4444; }
        .aug-theme .invoice-title { background-color: #0ea5e9; }
        .sep-theme .invoice-title { background-color: #84cc16; }
        .oct-theme .invoice-title { background-color: #f97316; }
        .nov-theme .invoice-title { background-color: #14b8a6; }
        .dec-theme .invoice-title { background-color: #9333ea; }

        /* Responsive adjustments */
        @media (max-width: 1536px) {
            .invoice-grid {
                grid-template-columns: repeat(6, 1fr);
            }
        }

        @media (max-width: 1280px) {
            .invoice-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 768px) {
            .invoice-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .invoice-grid {
                grid-template-columns: 1fr;
            }

            .summary-header {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }

            .filters {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }
    </style>

    <div>
        <!-- Summary Header -->
        <div class="summary-header">
            <div>
                <div class="ytd-sales">Year To Date Sales: {{ $this->formatCurrency($this->getYearToDateTotal()) }}</div>
                <div class="text-sm text-gray-500">{{ $this->getLastUpdatedTimestamp() }}</div>
            </div>
            <div class="filters">
                <div class="filter-group">
                    <span class="filter-label">Year:</span>
                    <select wire:model.live="selectedYear" class="border-gray-300 rounded">
                        @foreach($this->getYearsOptions() as $year => $label)
                            <option value="{{ $year }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <span class="filter-label">Salesperson:</span>
                    <select wire:model.live="selectedSalesPerson" class="border-gray-300 rounded">
                        @foreach($this->getSalesPersonOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- First Row (January to June) -->
        <div class="invoice-grid">
            @php
                $monthClasses = [
                    1 => 'jan-theme',
                    2 => 'feb-theme',
                    3 => 'mar-theme',
                    4 => 'apr-theme',
                    5 => 'may-theme',
                    6 => 'jun-theme'
                ];
                $monthlyData = $this->getMonthlyData();
            @endphp

            @for($month = 1; $month <= 6; $month++)
                <div class="invoice-box {{ $monthClasses[$month] }}">
                    <div class="invoice-title">
                        {{ $monthlyData[$month]['month_name'] }} {{ $this->selectedYear }}
                    </div>
                    <div class="invoice-content">
                        <div class="invoice-stat">
                            <span class="stat-label">F/ Payment:</span>
                            <span class="stat-value {{ $this->getColorForStatus('fully_paid') }}">
                                {{ $this->formatCurrency($monthlyData[$month]['fully_paid']) }}
                            </span>
                        </div>

                        <div class="invoice-stat">
                            <span class="stat-label">P/ Payment:</span>
                            <span class="stat-value {{ $this->getColorForStatus('partially_paid') }}">
                                {{ $this->formatCurrency($monthlyData[$month]['partially_paid']) }}
                            </span>
                        </div>

                        <div class="invoice-stat">
                            <span class="stat-label">Unpaid:</span>
                            <span class="stat-value {{ $this->getColorForStatus('unpaid') }}">
                                {{ $this->formatCurrency($monthlyData[$month]['unpaid']) }}
                            </span>
                        </div>

                        <div class="divider"></div>

                        <div class="invoice-stat">
                            <span class="stat-label stat-total">Total:</span>
                            <span class="font-bold stat-value {{ $this->getColorForStatus('total') }}">
                                {{ $this->formatCurrency($monthlyData[$month]['total']) }}
                            </span>
                        </div>
                    </div>
                </div>
            @endfor
        </div>

        <!-- Second Row (July to December) -->
        <div class="invoice-grid">
            @php
                $monthClasses = [
                    7 => 'jul-theme',
                    8 => 'aug-theme',
                    9 => 'sep-theme',
                    10 => 'oct-theme',
                    11 => 'nov-theme',
                    12 => 'dec-theme'
                ];
            @endphp

            @for($month = 7; $month <= 12; $month++)
                <div class="invoice-box {{ $monthClasses[$month] }}">
                    <div class="invoice-title">
                        {{ $monthlyData[$month]['month_name'] }} {{ $this->selectedYear }}
                    </div>
                    <div class="invoice-content">
                        <div class="invoice-stat">
                            <span class="stat-label">F/ Payment:</span>
                            <span class="stat-value {{ $this->getColorForStatus('fully_paid') }}">
                                {{ $this->formatCurrency($monthlyData[$month]['fully_paid']) }}
                            </span>
                        </div>

                        <div class="invoice-stat">
                            <span class="stat-label">P/ Payment:</span>
                            <span class="stat-value {{ $this->getColorForStatus('partially_paid') }}">
                                {{ $this->formatCurrency($monthlyData[$month]['partially_paid']) }}
                            </span>
                        </div>

                        <div class="invoice-stat">
                            <span class="stat-label">Unpaid:</span>
                            <span class="stat-value {{ $this->getColorForStatus('unpaid') }}">
                                {{ $this->formatCurrency($monthlyData[$month]['unpaid']) }}
                            </span>
                        </div>

                        <div class="divider"></div>

                        <div class="invoice-stat">
                            <span class="stat-label stat-total">Total:</span>
                            <span class="font-bold stat-value {{ $this->getColorForStatus('total') }}">
                                {{ $this->formatCurrency($monthlyData[$month]['total']) }}
                            </span>
                        </div>
                    </div>
                </div>
            @endfor
        </div>
    </div>
</x-filament-panels::page>
