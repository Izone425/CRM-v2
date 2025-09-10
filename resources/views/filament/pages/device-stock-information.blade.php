<!-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/device-stock-information.blade.php -->
<x-filament-panels::page>
    <style>
        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 16px;
        }

        .inventory-box {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s ease-in-out;
        }

        .inventory-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .inventory-header {
            height: 4px;
        }

        .inventory-title {
            padding: 12px 16px;
            font-weight: 600;
            font-size: 0.875rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #374151;
            border-bottom: 1px solid #f3f4f6;
            color: white;
        }

        .inventory-content {
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .inventory-stat {
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
        }

        .stat-total {
            font-weight: 700;
        }

        .divider {
            height: 1px;
            background-color: #e5e7eb;
            margin: 8px 0;
        }

        .timestamp-box {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            color: #6b7280;
            font-size: 0.875rem;
        }

        .legend-box {
            background-color: white;
            padding: 16px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            margin-top: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .legend-title {
            font-size: 1.125rem;
            font-weight: 500;
            margin-bottom: 12px;
            color: #111827;
        }

        .legend-items {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
        }

        .legend-item {
            display: flex;
            align-items: center;
        }

        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            margin-right: 8px;
        }

        /* Color themes from hardware dashboard */
        .blue-theme .inventory-title { background-color: #3b82f6; }
        .blue-theme .stat-value.highlight { color: #3b82f6; }

        .green-theme .inventory-title { background-color: #10b981; }
        .green-theme .stat-value.highlight { color: #10b981; }

        .purple-theme .inventory-title { background-color: #8b5cf6; }
        .purple-theme .stat-value.highlight { color: #8b5cf6; }

        .indigo-theme .inventory-title { background-color: #6366f1; }
        .indigo-theme .stat-value.highlight { color: #6366f1; }

        .amber-theme .inventory-title { background-color: #f59e0b; }
        .amber-theme .stat-value.highlight { color: #f59e0b; }

        .rose-theme .inventory-title { background-color: #f43f5e; }
        .rose-theme .stat-value.highlight { color: #f43f5e; }

        .gray-theme .inventory-title { background-color: #6b7280; }
        .gray-theme .stat-value.highlight { color: #6b7280; }

        .red-theme .inventory-title { background-color: #ef4444; }
        .red-theme .stat-value.highlight { color: #ef4444; }

        /* Responsive adjustments */
        @media (max-width: 1536px) {
            .inventory-grid {
                grid-template-columns: repeat(6, 1fr);
            }
        }

        @media (max-width: 1280px) {
            .inventory-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 768px) {
            .inventory-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .inventory-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div>
        <!-- Last Updated Timestamp -->
        <div class="timestamp-box">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-semibold text-gray-800">Device Stock Information</h3>
                <span>{{ $this->getLastUpdatedTimestamp() }}</span>
            </div>
        </div>

        <!-- Inventory Grid -->
        <div class="inventory-grid">
            @php
                $themes = ['blue-theme', 'green-theme', 'purple-theme', 'indigo-theme', 'amber-theme', 'rose-theme', 'gray-theme', 'red-theme'];
                $themeIndex = 0;
            @endphp

            @foreach($this->getInventoryData() as $inventory)
                @php
                    $theme = $themes[$themeIndex % count($themes)];
                    $themeIndex++;
                @endphp

                <div class="inventory-box {{ $theme }}">
                    <div class="inventory-title" title="{{ $inventory->name }}">
                        {{ $inventory->name }}
                    </div>

                    <div class="inventory-content">
                        <div class="inventory-stat">
                            <span class="stat-label">New:</span>
                            <span class="stat-value {{ $this->getColorForQuantity($inventory->new) }}">
                                {{ $inventory->new }}
                            </span>
                        </div>

                        <div class="inventory-stat">
                            <span class="stat-label">In Stock:</span>
                            <span class="stat-value {{ $this->getColorForQuantity($inventory->in_stock) }}">
                                {{ $inventory->in_stock }}
                            </span>
                        </div>

                        <div class="divider"></div>

                        <div class="inventory-stat">
                            <span class="stat-label stat-total">Total:</span>
                            <span class="font-bold stat-value highlight">
                                {{ $inventory->new + $inventory->in_stock }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Legend -->
    {{-- <div class="p-4">
        <div class="legend-box">
            <h3 class="legend-title">Stock Status Legend</h3>
            <div class="legend-items">
                <div class="legend-item">
                    <span class="bg-red-100 legend-color"></span>
                    <span>Low Stock (≤ 5)</span>
                </div>
                <div class="legend-item">
                    <span class="bg-yellow-100 legend-color"></span>
                    <span>Medium Stock (6-15)</span>
                </div>
                <div class="legend-item">
                    <span class="bg-green-100 legend-color"></span>
                    <span>Good Stock (> 15)</span>
                </div>
            </div>
        </div>
    </div> --}}
</x-filament-panels::page>
