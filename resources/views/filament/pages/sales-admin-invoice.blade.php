{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/sales-admin-invoice.blade.php --}}
<x-filament-panels::page>
    <style>
        .stats-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 10px;
            color: white;
            margin-bottom: 24px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 20px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .stat-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #fff;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .stat-label {
            color: rgba(255, 255, 255, 0.8);
        }

        .stat-value {
            font-weight: 600;
            color: #fff;
        }

        .jaja-value {
            color: #16eba5;
        }

        .sheena-value {
            color: #16eba5;
        }

        .total-amount {
            margin-top: 8px;
            padding: 4px 8px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: white;
            text-align: center;
            margin-bottom: 16px;
        }

        .overall-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }

        .overall-stat-card {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            padding: 16px;
            text-align: center;
        }

        .overall-stat-title {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .overall-stat-value {
            font-size: 24px;
            font-weight: 700;
            color: white;
        }

        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .stat-card {
                padding: 12px;
            }

            .stat-name {
                font-size: 16px;
            }
        }

        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .overall-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <!-- Stats Header -->
    <div class="stats-header">
        @php
            // Calculate overall totals
            $totalJajaAmount = collect($salespersonData)->sum('jaja_amount');
            $totalSheenaAmount = collect($salespersonData)->sum('sheena_amount');
            $grandTotal = $totalJajaAmount + $totalSheenaAmount;
        @endphp

        <!-- Salesperson Stats -->
        <div class="stats-grid">
            @foreach(['MUIM', 'YASMIN', 'FARHANAH', 'JOSHUA', 'AZIZ', 'BARI', 'VINCE'] as $salesperson)
                @php
                    $jajaAmount = $salespersonData[$salesperson]['jaja_amount'] ?? 0;
                    $sheenaAmount = $salespersonData[$salesperson]['sheena_amount'] ?? 0;
                    $totalAmount = $jajaAmount + $sheenaAmount;
                @endphp
                <div class="stat-card">
                    <div class="stat-name">{{ $salesperson }}</div>

                    <div class="stat-row">
                        <span class="stat-label">JJ:</span>
                        <span class="stat-value jaja-value">RM {{ number_format($jajaAmount, 2) }}</span>
                    </div>

                    <div class="stat-row">
                        <span class="stat-label">SN:</span>
                        <span class="stat-value sheena-value">RM {{ number_format($sheenaAmount, 2) }}</span>
                    </div>

                    <div class="total-amount">
                        Total: RM {{ number_format($totalAmount, 2) }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Table -->
    <div class="mb-6">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
