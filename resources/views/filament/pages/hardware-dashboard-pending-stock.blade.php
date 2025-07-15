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

        .amber-theme .stat-card-header { background-color: #f59e0b; }
        .amber-theme .stat-value { color: #f59e0b; }

        .rose-theme .stat-card-header { background-color: #f43f5e; }
        .rose-theme .stat-value { color: #f43f5e; }

        .gray-theme .stat-card-header { background-color: #6b7280; }
        .gray-theme .stat-value { color: #6b7280; }
    </style>

    <!-- Device Statistics Section -->
    <h2 style="margin-bottom: 16px; font-size: 18px; font-weight: 600; color: #374151;">Device Inventory Summary</h2>

    <div class="stats-container">
        <!-- TC10 Stats -->
        <div class="stat-card blue-theme">
            <div class="stat-card-header"></div>
            <div class="stat-card-content">
                <div class="stat-title">TC10</div>
                <div class="stat-value">{{ $this->getDeviceCount('tc10_quantity') }}</div>
            </div>
        </div>

        <!-- TC20 Stats -->
        <div class="stat-card green-theme">
            <div class="stat-card-header"></div>
            <div class="stat-card-content">
                <div class="stat-title">TC20</div>
                <div class="stat-value">{{ $this->getDeviceCount('tc20_quantity') }}</div>
            </div>
        </div>

        <!-- FACE ID5 Stats -->
        <div class="stat-card purple-theme">
            <div class="stat-card-header"></div>
            <div class="stat-card-content">
                <div class="stat-title">FACE ID5</div>
                <div class="stat-value">{{ $this->getDeviceCount('face_id5_quantity') }}</div>
            </div>
        </div>

        <!-- FACE ID6 Stats -->
        <div class="stat-card indigo-theme">
            <div class="stat-card-header"></div>
            <div class="stat-card-content">
                <div class="stat-title">FACE ID6</div>
                <div class="stat-value">{{ $this->getDeviceCount('face_id6_quantity') }}</div>
            </div>
        </div>

        <!-- TA100C / R Stats -->
        <div class="stat-card indigo-theme">
            <div class="stat-card-header"></div>
            <div class="stat-card-content">
                <div class="stat-title">TA100C / R</div>
                <div class="stat-value">{{ $this->getDeviceCount('ta100cr_quantity') }}</div>
            </div>
        </div>

        <!-- TA100C / MF Stats -->
        <div class="stat-card indigo-theme">
            <div class="stat-card-header"></div>
            <div class="stat-card-content">
                <div class="stat-title">TA100C / MF</div>
                <div class="stat-value">{{ $this->getDeviceCount('ta100cmf_quantity') }}</div>
            </div>
        </div>

        <!-- TA100C / HID Stats -->
        <div class="stat-card indigo-theme">
            <div class="stat-card-header"></div>
            <div class="stat-card-content">
                <div class="stat-title">TA100C / HID</div>
                <div class="stat-value">{{ $this->getDeviceCount('ta100chid_quantity') }}</div>
            </div>
        </div>

        <!-- TA100C / R / W Stats -->
        <div class="stat-card indigo-theme">
            <div class="stat-card-header"></div>
            <div class="stat-card-content">
                <div class="stat-title">TA100C / R / W</div>
                <div class="stat-value">{{ $this->getDeviceCount('ta100crw_quantity') }}</div>
            </div>
        </div>

        <!-- TA100C / MF / W Stats -->
        <div class="stat-card indigo-theme">
            <div class="stat-card-header"></div>
            <div class="stat-card-content">
                <div class="stat-title">TA100C / MF / W</div>
                <div class="stat-value">{{ $this->getDeviceCount('ta100cmfw_quantity') }}</div>
            </div>
        </div>

        <!-- TA100C / HID / W Stats -->
        <div class="stat-card indigo-theme">
            <div class="stat-card-header"></div>
            <div class="stat-card-content">
                <div class="stat-title">TA100C / HID / W</div>
                <div class="stat-value">{{ $this->getDeviceCount('ta100chidw_quantity') }}</div>
            </div>
        </div>

        <!-- TIME BEACON Stats -->
        <div class="stat-card amber-theme">
            <div class="stat-card-header"></div>
            <div class="stat-card-content">
                <div class="stat-title">TIME BEACON</div>
                <div class="stat-value">{{ $this->getDeviceCount('time_beacon_quantity') }}</div>
            </div>
        </div>

        <!-- NFC TAG Stats -->
        <div class="stat-card rose-theme">
            <div class="stat-card-header"></div>
            <div class="stat-card-content">
                <div class="stat-title">NFC TAG</div>
                <div class="stat-value">{{ $this->getDeviceCount('nfc_tag_quantity') }}</div>
            </div>
        </div>
    </div>

    <!-- Pending Stock Table -->
    {{ $this->table }}

</x-filament-panels::page>
