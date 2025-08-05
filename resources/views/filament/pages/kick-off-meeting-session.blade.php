<x-filament-panels::page>
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
            border-radius: 8px;
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
            color: #374151;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            line-height: 1;
            margin: 0;
        }

        .blue-theme .stat-card-header { background-color: #3b82f6; }
        .blue-theme .stat-value { color: #3b82f6; }

        .green-theme .stat-card-header { background-color: #10b981; }
        .green-theme .stat-value { color: #10b981; }

        .purple-theme .stat-card-header { background-color: #8b5cf6; }
        .purple-theme .stat-value { color: #8b5cf6; }

        .indigo-theme .stat-card-header { background-color: #6366f1; }
        .indigo-theme .stat-value { color: #6366f1; }

        .red-theme .stat-card-header { background-color: #ef4444; }
        .red-theme .stat-value { color: #ef4444; }

        .gray-theme .stat-card-header { background-color: #6b7280; }
        .gray-theme .stat-value { color: #6b7280; }
    </style>

    <div class="stats-container">
        <!-- All Appointments Stats -->
        <div class="stat-card gray-theme">
            <div class="stat-card-header"></div>
            <div class="stat-card-content">
                <div class="stat-title">ALL MEETINGS</div>
                <div class="stat-value">{{ $this->getStatusCount() }}</div>
            </div>
        </div>

        <!-- New Appointments Stats -->
        <div class="stat-card blue-theme">
            <div class="stat-card-header"></div>
            <div class="stat-card-content">
                <div class="stat-title">NEW</div>
                <div class="stat-value">{{ $this->getStatusCount('New') }}</div>
            </div>
        </div>

        <!-- Done Appointments Stats -->
        <div class="stat-card green-theme">
            <div class="stat-card-header"></div>
            <div class="stat-card-content">
                <div class="stat-title">DONE</div>
                <div class="stat-value">{{ $this->getStatusCount('Done') }}</div>
            </div>
        </div>

        <!-- Cancelled Appointments Stats -->
        <div class="stat-card red-theme">
            <div class="stat-card-header"></div>
            <div class="stat-card-content">
                <div class="stat-title">CANCELLED</div>
                <div class="stat-value">{{ $this->getStatusCount('Cancelled') }}</div>
            </div>
        </div>
    </div>

    <!-- Main Table -->
    {{ $this->table }}
</x-filament-panels::page>
