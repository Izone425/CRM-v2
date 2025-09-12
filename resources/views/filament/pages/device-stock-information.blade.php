<x-filament-panels::page>
    <style>
        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 16px;
            margin-bottom: 24px;
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

        .section-header {
            padding: 12px 16px;
            margin-bottom: 16px;
            color: #111827;
            font-size: 1rem;
            font-weight: 600;
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
        <!-- SECTION 1: Inventory Data -->
        <div class="flex items-center justify-between">
            <div class="section-header">Device Inventory</div>
            <span>{{ $this->getLastUpdatedTimestamp() }}</span>
        </div>
        <div class="inventory-grid">
            @php
                $themes = ['blue-theme', 'green-theme', 'purple-theme', 'indigo-theme', 'amber-theme', 'rose-theme', 'gray-theme', 'red-theme'];
                $themeMap = [];
                $themeIndex = 0;

                // Define display name mapping
                $displayNames = [
                    'Beacon-WMC007-V2' => 'TIME BEACON',
                    'NFC-WMC006-Y' => 'NFC TAG'
                ];
            @endphp

            @foreach($this->getInventoryData() as $inventory)
                @php
                    $theme = $themes[$themeIndex % count($themes)];
                    $themeMap[$inventory->name] = $theme;
                    $themeIndex++;

                    // Use friendly display name if available, otherwise use original name
                    $displayName = $displayNames[trim($inventory->name)] ?? $inventory->name;
                @endphp

                <div class="inventory-box {{ $theme }}">
                    <div class="inventory-title" title="{{ $displayName }}">
                        {{ $displayName }}
                    </div>

                    <div class="inventory-content">
                        <div class="inventory-stat">
                            <span class="stat-label">New:</span>
                            <span class="stat-value {{ $this->getColorForQuantity($inventory->new) }}">
                                {{ $inventory->new }}
                            </span>
                        </div>

                        <div class="inventory-stat">
                            <span class="stat-label">Burning:</span>
                            <span class="stat-value {{ $this->getColorForQuantity($inventory->new) }}">
                                {{ $inventory->burning }}
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
                                {{ $inventory->new + $inventory->in_stock + $inventory->burning}}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- SECTION 2: Purchase Items -->
        <div class="section-header">Device Purchase Status</div>
        <div class="inventory-grid">
            @foreach($this->getPurchaseData() as $purchase)
                @php
                    // Use the same theme for each model as was used in the inventory section
                    $theme = $themeMap[$purchase->name] ?? $themes[0];

                    // Use friendly display name if available, otherwise use original name
                    $displayName = $displayNames[trim($purchase->name)] ?? $purchase->name;
                @endphp

                <div class="inventory-box {{ $theme }}">
                    <div class="inventory-title" title="{{ $displayName }}">
                        {{ $displayName }}
                    </div>

                    <div class="inventory-content">
                        <div class="inventory-stat">
                            <span class="stat-label">Completed Order:</span>
                            <span class="stat-value {{ $this->getColorForQuantity($purchase->completed_order) }}">
                                {{ $purchase->completed_order }}
                            </span>
                        </div>

                        <div class="inventory-stat">
                            <span class="stat-label">Completed Shipping:</span>
                            <span class="stat-value {{ $this->getColorForQuantity($purchase->completed_shipping) }}">
                                {{ $purchase->completed_shipping }}
                            </span>
                        </div>

                        <div class="divider"></div>

                        <div class="inventory-stat">
                            <span class="stat-label stat-total">Total:</span>
                            <span class="font-bold stat-value highlight">
                                {{ $purchase->total_purchase }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- SECTION 3: Overall Summary -->
        <div class="section-header">Device Summary</div>
        <div class="inventory-grid">
            @foreach($this->getDeviceSummary() as $summary)
                @php
                    // Use the same theme for each model as was used in the inventory section
                    $theme = $themeMap[$summary->name] ?? $themes[0];

                    // Use friendly display name if available, otherwise use original name
                    $displayName = $displayNames[trim($summary->name)] ?? $summary->name;
                @endphp

                <div class="inventory-box {{ $theme }}">
                    <div class="inventory-title" title="{{ $displayName }}">
                        {{ $displayName }}
                    </div>

                    <div class="inventory-content">
                        <div class="inventory-stat">
                            <span class="stat-label">Summary 1:</span>
                            <span class="stat-value {{ $this->getColorForQuantity($summary->summary1) }}">
                                {{ $summary->summary1 }}
                            </span>
                        </div>

                        <div class="inventory-stat">
                            <span class="stat-label">Summary 2:</span>
                            <span class="stat-value {{ $this->getColorForQuantity($summary->summary2) }}">
                                {{ $summary->summary2 }}
                            </span>
                        </div>

                        <div class="divider"></div>

                        <div class="inventory-stat">
                            <span class="stat-label stat-total">Total:</span>
                            <span class="font-bold stat-value highlight">
                                {{ $summary->total_summary }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
