<x-filament-panels::page>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

    <style>
        .dashboard-container {
            display: flex;
            flex-direction: column;
            gap: 24px;
            margin-bottom: 32px;
        }

        .dashboard-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 16px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: #111827;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-title i {
            color: #6b7280;
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        .mini-charts-container {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
        }

        .mini-chart-card {
            flex: 1;
            min-width: 240px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 16px;
        }

        .mini-chart-container {
            position: relative;
            height: 100px;
            width: 100%;
        }

        .year-selector {
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
            background-color: #f9fafb;
            font-size: 14px;
        }

        .status-pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-open {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-delay {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-inactive {
            background-color: #e5e7eb;
            color: #374151;
        }

        .status-closed {
            background-color: #d1fae5;
            color: #065f46;
        }

        .horizontal-bar-container {
            margin-top: 16px;
        }

        .horizontal-bar-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
        }

        .bar-label {
            width: 80px;
            font-size: 14px;
            font-weight: 500;
        }

        .bar-track {
            flex-grow: 1;
            height: 12px;
            background-color: #f3f4f6;
            border-radius: 6px;
            overflow: hidden;
            margin: 0 12px;
        }

        .bar-fill {
            height: 100%;
            border-radius: 6px;
        }

        .bar-fill-open {
            background-color: #10b981;
        }

        .bar-fill-delay {
            background-color: #3b82f6;
        }

        .bar-fill-inactive {
            background-color: #eab308;
        }

        .bar-fill-closed {
            background-color: #ef4444;
        }

        .bar-fill-small {
            background-color: #10b981;
        }

        .bar-fill-medium {
            background-color: #3b82f6;
        }

        .bar-fill-large {
            background-color: #eab308;
        }

        .bar-fill-enterprise {
            background-color: #ef4444;
        }

        .bar-value {
            width: 40px;
            font-size: 14px;
            font-weight: 600;
            text-align: right;
        }

        .salesperson-metrics {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 16px;
            margin-top: 12px;
        }

        .salesperson-metric {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100px;
        }

        .metric-chart {
            margin-bottom: 8px;
        }

        .circular-progress {
            position: relative;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: conic-gradient(
                var(--color) calc(var(--percentage) * 3.6deg),
                #e5e7eb 0deg
            );
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .circular-progress::before {
            content: "";
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: white;
        }

        .circular-progress .inner {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1;
        }

        .circular-progress .value {
            font-size: 16px;
            font-weight: 600;
        }

        .metric-info {
            text-align: center;
        }

        .salesperson-name {
            font-size: 14px;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100px;
        }

        .salesperson-rank {
            font-size: 12px;
            color: #6b7280;
        }
    </style>

    <div class="dashboard-container">
        <!-- Section 1: Monthly Software Handover Status -->
        <div class="dashboard-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-chart-line"></i>
                    <span>Total Software Handover by Month</span>
                </div>
                <select class="year-selector">
                    <option value="2025">2025</option>
                    <option value="2024" selected>2024</option>
                    <option value="2023">2023</option>
                </select>
            </div>
            <div class="chart-container">
                <canvas id="monthlyHandoversChart"></canvas>
            </div>
        </div>

        <!-- Section 2: Top Salespersons -->
        <div class="mini-charts-container">
            <div class="mini-chart-card" style="flex: 2;">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-user-tie"></i>
                        <span>by Salesperson</span>
                    </div>
                    <div>
                        <span class="status-pill status-open">Top 1-4</span>
                    </div>
                </div>
                <div class="salesperson-metrics">
                    @php
                        $salespersonData = $this->getHandoversBySalesPerson();
                        $totalHandovers = 100;
                        $colors = ['#3b82f6', '#06b6d4', '#10b981', '#f97316'];
                    @endphp

                    @foreach($salespersonData as $index => $person)
                        <div class="salesperson-metric">
                            <div class="metric-chart">
                                <div class="circular-progress" style="--percentage: {{ ($person->total / $totalHandovers) * 100 }}; --color: {{ $colors[$index % 4] }};">
                                    <div class="inner">
                                        <span class="value">{{ $person->total }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="metric-info">
                                <div class="salesperson-name">{{ $person->salesperson }}</div>
                                <div class="salesperson-rank">Top {{ $index + 1 }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Section 3: Status Distribution -->
            <div class="mini-chart-card" style="flex: 1;">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-chart-pie"></i>
                        <span>by Project Status</span>
                    </div>
                </div>
                <div class="horizontal-bar-container">
                    @php
                        $statusData = $this->getHandoversByStatus();
                        $maxStatus = max($statusData);
                    @endphp

                    <div class="horizontal-bar-item">
                        <span class="bar-label">Open</span>
                        <div class="bar-track">
                            <div class="bar-fill bar-fill-open" style="width: {{ ($statusData['open'] / $maxStatus) * 100 }}%"></div>
                        </div>
                        <span class="bar-value">{{ $statusData['open'] }}</span>
                    </div>

                    <div class="horizontal-bar-item">
                        <span class="bar-label">Delay</span>
                        <div class="bar-track">
                            <div class="bar-fill bar-fill-delay" style="width: {{ ($statusData['delay'] / $maxStatus) * 100 }}%"></div>
                        </div>
                        <span class="bar-value">{{ $statusData['delay'] }}</span>
                    </div>

                    <div class="horizontal-bar-item">
                        <span class="bar-label">Inactive</span>
                        <div class="bar-track">
                            <div class="bar-fill bar-fill-inactive" style="width: {{ ($statusData['inactive'] / $maxStatus) * 100 }}%"></div>
                        </div>
                        <span class="bar-value">{{ $statusData['inactive'] }}</span>
                    </div>

                    <div class="horizontal-bar-item">
                        <span class="bar-label">Closed</span>
                        <div class="bar-track">
                            <div class="bar-fill bar-fill-closed" style="width: {{ ($statusData['closed'] / $maxStatus) * 100 }}%"></div>
                        </div>
                        <span class="bar-value">{{ $statusData['closed'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 4: Company Size and Section 5: Modules -->
        <div class="mini-charts-container">
            <!-- Section 4: Company Size -->
            <div class="mini-chart-card" style='flex: 1;'>
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-building"></i>
                        <span>by Company Size</span>
                    </div>
                </div>
                <div class="horizontal-bar-container">
                    @php
                        $sizeData = $this->getHandoversByCompanySize();
                        $maxSize = max($sizeData);
                    @endphp

                    <div class="horizontal-bar-item">
                        <span class="bar-label">Small</span>
                        <div class="bar-track">
                            <div class="bar-fill bar-fill-small" style="width: {{ ($sizeData['Small'] / $maxSize) * 100 }}%"></div>
                        </div>
                        <span class="bar-value">{{ $sizeData['Small'] }}</span>
                    </div>

                    <div class="horizontal-bar-item">
                        <span class="bar-label">Medium</span>
                        <div class="bar-track">
                            <div class="bar-fill bar-fill-medium" style="width: {{ ($sizeData['Medium'] / $maxSize) * 100 }}%"></div>
                        </div>
                        <span class="bar-value">{{ $sizeData['Medium'] }}</span>
                    </div>

                    <div class="horizontal-bar-item">
                        <span class="bar-label">Large</span>
                        <div class="bar-track">
                            <div class="bar-fill bar-fill-large" style="width: {{ ($sizeData['Large'] / $maxSize) * 100 }}%"></div>
                        </div>
                        <span class="bar-value">{{ $sizeData['Large'] }}</span>
                    </div>

                    <div class="horizontal-bar-item">
                        <span class="bar-label">Enterprise</span>
                        <div class="bar-track">
                            <div class="bar-fill bar-fill-enterprise" style="width: {{ ($sizeData['Enterprise'] / $maxSize) * 100 }}%"></div>
                        </div>
                        <span class="bar-value">{{ $sizeData['Enterprise'] }}</span>
                    </div>
                </div>
            </div>

            <!-- Section 5: Modules -->
            <div class="mini-chart-card" style='flex: 2;'>
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-puzzle-piece"></i>
                        <span>by Module</span>
                    </div>
                </div>
                <div class="horizontal-bar-container">
                    @php
                        $moduleData = $this->getHandoversByModule();
                        $maxModule = max($moduleData);

                        $moduleNames = [
                            'ta' => 'TimeTec Attendance',
                            'tl' => 'TimeTec Leave',
                            'tc' => 'TimeTec Claim',
                            'tp' => 'TimeTec Payroll',
                        ];

                        // Define colors for each module
                        $moduleColors = [
                            'ta' => 'blue',
                            'tl' => 'green',
                            'tc' => 'teal',
                            'tp' => 'lime',
                        ];
                    @endphp

                    @foreach($moduleData as $key => $value)
                        <div class="horizontal-bar-item">
                            <span class="bar-label">{{ substr($moduleNames[$key] ?? $key, 8) }}</span>
                            <div class="bar-track">
                                <div class="bar-fill bar-fill-{{ $key }}" style="width: {{ ($value / $maxModule) * 100 }}%; background-color: var(--color-{{ $moduleColors[$key] }})"></div>
                            </div>
                            <span class="bar-value">{{ $value }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Monthly Handovers Chart (Section 1)
            const monthlyData = @json($this->getHandoversByMonthAndStatus());
            const months = monthlyData.map(item => item.month);
            const closedData = monthlyData.map(item => item.closed);
            const ongoingData = monthlyData.map(item => item.ongoing);
            const totalData = monthlyData.map(item => item.total);

            new Chart(
                document.getElementById('monthlyHandoversChart'),
                {
                    type: 'bar',
                    data: {
                        labels: months,
                        datasets: [
                            {
                                label: 'Total',
                                data: totalData,
                                backgroundColor: '#ef4444',
                                borderRadius: 4,
                                barPercentage: 0.5,
                                categoryPercentage: 0.8,
                                stack: 'Stack 1',
                            },
                            {
                                label: 'Ongoing',
                                data: ongoingData,
                                backgroundColor: '#84cc16',
                                borderRadius: 4,
                                barPercentage: 0.5,
                                categoryPercentage: 0.8,
                                stack: 'Stack 2',
                            },
                            {
                                label: 'Closed',
                                data: closedData,
                                backgroundColor: '#eab308',
                                borderRadius: 4,
                                barPercentage: 0.5,
                                categoryPercentage: 0.8,
                                stack: 'Stack 3',
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: '#f3f4f6'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    boxWidth: 12,
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                }
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false
                            }
                        }
                    }
                }
            );

            // Salesperson Chart (Section 2)
            const salespersonData = @json($this->getHandoversBySalesPerson());
            const salespersonNames = salespersonData.map(item => item.salesperson);
            const salespersonCounts = salespersonData.map(item => item.total);
            const colorsList = ['#3b82f6', '#06b6d4', '#10b981', '#f97316'];

            new Chart(
                document.getElementById('salespersonChart'),
                {
                    type: 'doughnut',
                    data: {
                        labels: salespersonNames,
                        datasets: [
                            {
                                data: salespersonCounts,
                                backgroundColor: colorsList,
                                borderColor: '#ffffff',
                                borderWidth: 2,
                                hoverOffset: 10
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '60%',
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    boxWidth: 12,
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.raw;
                                        return `${label}: ${value}`;
                                    }
                                }
                            }
                        }
                    }
                }
            );

            // Module Chart (Section 5)
            const moduleData = @json($this->getHandoversByModule());
            const moduleNames = {
                'ta': 'TimeTec Attendance',
                'tl': 'TimeTec Leave',
                'tc': 'TimeTec Claim',
                'tp': 'TimeTec Payroll',
                'tapp': 'TimeTec Mobile',
                'thire': 'TimeTec Hire',
                'tacc': 'TimeTec Access',
                'tpbi': 'TimeTec BI'
            };

            // Define operational and strategic modules
            const operationalModules = ['ta', 'tl', 'tc', 'tp'];
            const strategicModules = ['tapp', 'thire', 'tacc', 'tpbi'];

            const moduleLabels = [];
            const moduleValues = [];
            const moduleColors = [];

            const operationalColors = ['#4287f5', '#06b6d4', '#10b981', '#84cc16'];
            const strategicColors = ['#f472b6', '#c026d3', '#8b5cf6', '#3b82f6'];

            // Create "Operational Modules" section data
            let colorIndex = 0;
            operationalModules.forEach(module => {
                if (moduleData[module] > 0) {
                    moduleLabels.push(moduleNames[module]);
                    moduleValues.push(moduleData[module]);
                    moduleColors.push(operationalColors[colorIndex % operationalColors.length]);
                    colorIndex++;
                }
            });

            // Create "Strategic Modules" section data
            colorIndex = 0;
            strategicModules.forEach(module => {
                if (moduleData[module] > 0) {
                    moduleLabels.push(moduleNames[module]);
                    moduleValues.push(moduleData[module]);
                    moduleColors.push(strategicColors[colorIndex % strategicColors.length]);
                    colorIndex++;
                }
            });

            new Chart(
                document.getElementById('moduleChart'),
                {
                    type: 'bar',
                    data: {
                        labels: moduleLabels,
                        datasets: [
                            {
                                data: moduleValues,
                                backgroundColor: moduleColors,
                                borderRadius: 4,
                                barPercentage: 0.6,
                                categoryPercentage: 0.8
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        scales: {
                            x: {
                                beginAtZero: true,
                                grid: {
                                    color: '#f3f4f6'
                                }
                            },
                            y: {
                                grid: {
                                    display: false
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    title: function(context) {
                                        return context[0].label;
                                    },
                                    label: function(context) {
                                        return `Count: ${context.raw}`;
                                    }
                                }
                            }
                        }
                    }
                }
            );
        });
    </script>
</x-filament-panels::page>
