{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/sales-admin-closed-deal.blade.php --}}

<x-filament-panels::page>
    <style>
        .stats-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 24px;
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 16px;
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

        .conversion-rate {
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
    </style>

    <!-- Stats Header -->
    <div class="stats-header">
        <div class="page-title">Sales Admin - Closed Deals</div>

        @php
            $overallStats = $this->getOverallStats();
        @endphp

        <!-- Salesperson Stats -->
        <div class="stats-grid">
            @foreach($this->getSalespersonStats() as $stat)
                <div class="stat-card">
                    <div class="stat-name">{{ $stat['name'] }}</div>

                    <div class="stat-row">
                        <span class="stat-label">Total Leads:</span>
                        <span class="stat-value">{{ number_format($stat['total_leads']) }}</span>
                    </div>

                    <div class="stat-row">
                        <span class="stat-label">Total Closed:</span>
                        <span class="stat-value">{{ number_format($stat['closed_leads']) }}</span>
                    </div>

                    <div class="conversion-rate">
                        Conversion: {{ $stat['conversion_rate'] }}%
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
