{{-- Main Dashboard Content with Left Sidebar Layout --}}

<style>
    /* Container styling */
    .hr-dashboard-container {
        grid-column: 1 / -1;
        width: 100%;
    }

    /* Main layout with grid setup */
    .dashboard-layout {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 15px;
    }

    /* Group column styling */
    .group-column {
        padding-right: 10px;
        width: 230px;
    }

    .group-box {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 20px 15px;
        cursor: pointer;
        transition: all 0.2s;
        border-top: 4px solid transparent;
        display: flex;
        flex-direction: column;
        justify-content: center;
        margin-bottom: 15px;
        width: 100%;
        text-align: center;
        max-height: 82px;
        max-width: 220px;
    }

    .group-box:hover {
        background-color: #f9fafb;
        transform: translateX(3px);
    }

    .group-box.selected {
        background-color: #f9fafb;
        transform: translateX(5px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .group-info {
        display: flex;
        flex-direction: column;
    }

    .group-title {
        font-size: 15px;
        font-weight: 600;
        margin-bottom: 8px;
        text-align: left;
    }

    .group-count {
        font-size: 24px;
        font-weight: bold;
    }

    /* Color coding for different groups */
    .group-all { border-top-color: #64748b; }
    .group-conversions { border-top-color: #2563eb; }
    .group-resellers { border-top-color: #10b981; }
    .group-distributors { border-top-color: #8b5cf6; }
    .group-signups { border-top-color: #06b6d4; }

    /* Group container */
    .group-container {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        border-right: none;
        padding-right: 0;
        padding-bottom: 20px;
        margin-bottom: 20px;
        text-align: center;
    }

    /* Top metrics styling */
    .top-metrics {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-bottom: 20px;
    }

    .metric-card-compact {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 15px;
        border-left: 4px solid;
    }

    .metric-card-compact.blue { border-left-color: #2563eb; }
    .metric-card-compact.orange { border-left-color: #f59e0b; }
    .metric-card-compact.purple { border-left-color: #8b5cf6; }
    .metric-card-compact.red { border-left-color: #ef4444; }

    .metric-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .metric-title {
        font-size: 13px;
        color: #6b7280;
        font-weight: 500;
    }

    .metric-value {
        font-size: 32px;
        font-weight: bold;
        color: #111827;
    }

    /* Content area */
    .content-column {
        min-height: 600px;
    }

    .content-area {
        background-color: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
</style>

<div class="hr-dashboard-container"
     x-data="{
         selectedView: 'overview',
         selectView(view) {
             this.selectedView = view;
             // Re-initialize chart when switching to overview
             if (view === 'overview' && typeof initializeProductChart === 'function') {
                 setTimeout(() => {
                     console.log('View switched to overview, re-initializing chart...');
                     initializeProductChart();
                 }, 100);
             }
         }
     }"
     x-init="
         // Initialize chart when Alpine is ready
         $nextTick(() => {
             setTimeout(() => {
                 if (typeof initializeProductChart === 'function') {
                     console.log('Alpine x-init: Initializing chart...');
                     initializeProductChart();
                 }
             }, 200);
         });
     ">

    <div class="dashboard-layout">
        <!-- Left Sidebar with Category Cards -->
        <div class="group-column">
            <div class="group-container">
                <!-- All Metrics -->
                <div class="group-box group-all"
                     :class="{ 'selected': selectedView === 'overview' }"
                     @click="selectView('overview')">
                    <div class="group-info">
                        <div class="group-title">All Metrics</div>
                        <div class="group-count">
                            {{ number_format($trialToPaidConversion + $totalActiveResellers + $totalActiveDistributors + $newSignUpsThisMonth) }}
                        </div>
                    </div>
                </div>

                <!-- Trial Conversions -->
                <div class="group-box group-conversions"
                     :class="{ 'selected': selectedView === 'conversions' }"
                     @click="selectView('conversions')">
                    <div class="group-info">
                        <div class="group-title">Trial Conversions</div>
                        <div class="group-count">{{ number_format($trialToPaidConversion) }}</div>
                    </div>
                </div>

                <!-- Active Resellers -->
                <div class="group-box group-resellers"
                     :class="{ 'selected': selectedView === 'resellers' }"
                     @click="selectView('resellers')">
                    <div class="group-info">
                        <div class="group-title">Active Resellers</div>
                        <div class="group-count">{{ number_format($totalActiveResellers) }}</div>
                    </div>
                </div>

                <!-- Active Distributors -->
                <div class="group-box group-distributors"
                     :class="{ 'selected': selectedView === 'distributors' }"
                     @click="selectView('distributors')">
                    <div class="group-info">
                        <div class="group-title">Active Distributors</div>
                        <div class="group-count">{{ number_format($totalActiveDistributors) }}</div>
                    </div>
                </div>

                <!-- New Sign Ups -->
                <div class="group-box group-signups"
                     :class="{ 'selected': selectedView === 'signups' }"
                     @click="selectView('signups')">
                    <div class="group-info">
                        <div class="group-title">New Sign Ups</div>
                        <div class="group-count">{{ number_format($newSignUpsThisMonth) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Content Area -->
        <div class="content-column">
            <!-- Top Horizontal Metrics -->
            <div class="top-metrics">
                <!-- Trial to Paid -->
                <div class="metric-card-compact blue">
                    <div class="metric-header">
                        <span class="metric-title">Trial to Paid</span>
                    </div>
                    <div class="metric-value">{{ number_format($trialToPaidConversion) }}</div>
                    @if($compareWithPrevious && isset($trends['conversion']))
                        <div style="font-size: 12px; color: {{ $trends['conversion'] >= 0 ? '#10b981' : '#ef4444' }}; margin-top: 5px;">
                            {{ $trends['conversion'] >= 0 ? '↑' : '↓' }} {{ abs($trends['conversion']) }}%
                        </div>
                    @endif
                </div>

                <!-- Active Partners -->
                <div class="metric-card-compact orange">
                    <div class="metric-header">
                        <span class="metric-title">Active Partners</span>
                    </div>
                    <div class="metric-value">{{ number_format($totalActiveResellers + $totalActiveDistributors) }}</div>
                </div>

                <!-- New Signups -->
                <div class="metric-card-compact purple">
                    <div class="metric-header">
                        <span class="metric-title">New Sign Ups</span>
                    </div>
                    <div class="metric-value">{{ number_format($newSignUpsThisMonth) }}</div>
                    @if($compareWithPrevious && isset($trends['signups']))
                        <div style="font-size: 12px; color: {{ $trends['signups'] >= 0 ? '#10b981' : '#ef4444' }}; margin-top: 5px;">
                            {{ $trends['signups'] >= 0 ? '↑' : '↓' }} {{ abs($trends['signups']) }}%
                        </div>
                    @endif
                </div>

                <!-- Total Active Customers -->
                <div class="metric-card-compact red">
                    <div class="metric-header">
                        <span class="metric-title">Active Customers</span>
                    </div>
                    <div class="metric-value">
                        {{ number_format(
                            $customersByProduct['ta']['count'] +
                            $customersByProduct['leave']['count'] +
                            $customersByProduct['patrol']['count'] +
                            $customersByProduct['fcc']['count']
                        ) }}
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div style="background: white; border-radius: 8px; padding: 15px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-2">Month</label>
                        <select wire:model.live="selectedMonth"
                                class="block w-36 px-4 py-2 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20 transition-all font-medium text-gray-900 bg-gray-50 hover:bg-white">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}">{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-2">Year</label>
                        <select wire:model.live="selectedYear"
                                class="block w-28 px-4 py-2 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20 transition-all font-medium text-gray-900 bg-gray-50 hover:bg-white">
                            @for($y = 2020; $y <= date('Y') + 1; $y++)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    <div style="margin-top: 24px;">
                        <input type="checkbox" id="compareToggle" wire:model.live="compareWithPrevious"
                               class="w-5 h-5 text-blue-600 border-2 border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20 transition-all cursor-pointer">
                        <label for="compareToggle" class="ml-2 text-sm font-semibold text-gray-700 cursor-pointer select-none">
                            Compare with previous month
                        </label>
                    </div>

                    <div style="margin-left: auto; margin-top: 24px;">
                        <button wire:click="exportData"
                                class="px-5 py-2 text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-md hover:shadow-lg flex items-center gap-2">
                            <i class="bi bi-download"></i>
                            <span>Export</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Dynamic Content Area -->
            <div class="content-area">
                <!-- Overview View (Default) -->
                <div x-show="selectedView === 'overview'">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Dashboard Overview</h2>

                    <!-- Charts Grid -->
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px;">
                        <!-- Top Products Chart -->
                        <div style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <h3 class="text-lg font-bold text-gray-900 mb-4">Top Products in Sales</h3>
                            <div id="topProductsChart" style="height: 300px;"></div>

                            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                                @foreach($topProductsData['labels'] as $index => $label)
                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #f9fafb; border-radius: 6px; margin-bottom: 8px;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div style="width: 12px; height: 12px; border-radius: 3px; background: {{ $topProductsData['colors'][$index] }};"></div>
                                            <span style="font-size: 13px; font-weight: 600; color: #374151;">{{ $label }}</span>
                                        </div>
                                        <span style="font-size: 14px; font-weight: bold; color: #111827;">{{ $topProductsData['values'][$index] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Sign Up Growth Chart -->
                        <div style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <h3 class="text-lg font-bold text-gray-900 mb-4">Sign Up Growth</h3>
                            <div style="display: flex; justify-content: center; align-items: end; height: 300px; gap: 40px;">
                                <div style="text-align: center;">
                                    <div style="font-size: 24px; font-weight: bold; color: #06b6d4; margin-bottom: 10px;">{{ number_format($newSignUpsThisMonth) }}</div>
                                    <div style="width: 80px; height: {{ min(($newSignUpsThisMonth / max($newSignUpsThisMonth, ($previousMetrics['signups'] ?? 1), 1)) * 200, 200) }}px; background: linear-gradient(to top, #06b6d4, #22d3ee); border-radius: 6px; margin: 0 auto;"></div>
                                    <div style="font-size: 12px; color: #6b7280; margin-top: 10px;">This Month</div>
                                </div>
                                @if($compareWithPrevious && isset($previousMetrics['signups']))
                                    <div style="text-align: center; opacity: 0.7;">
                                        <div style="font-size: 24px; font-weight: bold; color: #f59e0b; margin-bottom: 10px;">{{ number_format($previousMetrics['signups']) }}</div>
                                        <div style="width: 80px; height: {{ min(($previousMetrics['signups'] / max($newSignUpsThisMonth, $previousMetrics['signups'], 1)) * 200, 200) }}px; background: linear-gradient(to top, #f59e0b, #fbbf24); border-radius: 6px; margin: 0 auto;"></div>
                                        <div style="font-size: 12px; color: #6b7280; margin-top: 10px;">Last Month</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Active Customers by Product -->
                    <div style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Active Customers by Product</h3>
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                            @foreach(['ta' => ['name' => 'TimeTec TA', 'color' => '#06B6D4'], 'leave' => ['name' => 'TimeTec Leave', 'color' => '#F59E0B'], 'patrol' => ['name' => 'TimeTec Patrol', 'color' => '#3B82F6'], 'fcc' => ['name' => 'FCC', 'color' => '#EF4444']] as $key => $product)
                                <div style="text-align: center;">
                                    <div style="position: relative; width: 120px; height: 120px; margin: 0 auto;">
                                        <svg style="width: 120px; height: 120px; transform: rotate(-90deg);">
                                            <circle cx="60" cy="60" r="50" stroke="#E5E7EB" stroke-width="10" fill="none" />
                                            <circle cx="60" cy="60" r="50"
                                                    stroke="{{ $product['color'] }}"
                                                    stroke-width="10"
                                                    fill="none"
                                                    stroke-dasharray="314"
                                                    stroke-dashoffset="{{ 314 - (314 * ($customersByProduct[$key]['percentage'] / 100)) }}"
                                                    stroke-linecap="round" />
                                        </svg>
                                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 20px; font-weight: bold; color: #111827;">
                                            {{ $customersByProduct[$key]['count'] }}
                                        </div>
                                    </div>
                                    <div style="font-size: 13px; font-weight: 600; color: #374151; margin-top: 10px;">{{ $product['name'] }}</div>
                                    <div style="font-size: 12px; color: #6b7280;">{{ $customersByProduct[$key]['percentage'] }}%</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Conversions View -->
                <div x-show="selectedView === 'conversions'" x-cloak>
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Trial to Paid Conversions</h2>
                    <div style="background: #f9fafb; border-radius: 8px; padding: 40px; text-align: center;">
                        <div style="font-size: 72px; font-weight: bold; color: #2563eb;">{{ number_format($trialToPaidConversion) }}</div>
                        <div style="font-size: 16px; color: #6b7280; margin-top: 10px;">Conversions this month</div>
                        @if($compareWithPrevious && isset($trends['conversion']))
                            <div style="font-size: 24px; color: {{ $trends['conversion'] >= 0 ? '#10b981' : '#ef4444' }}; margin-top: 20px; font-weight: 600;">
                                {{ $trends['conversion'] >= 0 ? '↑' : '↓' }} {{ abs($trends['conversion']) }}% from last month
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Resellers View -->
                <div x-show="selectedView === 'resellers'" x-cloak>
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Active Resellers</h2>
                    <div style="background: #f9fafb; border-radius: 8px; padding: 40px; text-align: center;">
                        <div style="font-size: 72px; font-weight: bold; color: #10b981;">{{ number_format($totalActiveResellers) }}</div>
                        <div style="font-size: 16px; color: #6b7280; margin-top: 10px;">Total active resellers</div>
                        <div style="display: inline-flex; align-items: center; gap: 8px; margin-top: 20px; padding: 8px 16px; background: rgba(16, 185, 129, 0.1); border-radius: 20px;">
                            <span style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; animation: pulse 2s infinite;"></span>
                            <span style="font-size: 14px; color: #10b981; font-weight: 600;">Active Now</span>
                        </div>
                    </div>
                </div>

                <!-- Distributors View -->
                <div x-show="selectedView === 'distributors'" x-cloak>
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Active Distributors</h2>
                    <div style="background: #f9fafb; border-radius: 8px; padding: 40px; text-align: center;">
                        <div style="font-size: 72px; font-weight: bold; color: #8b5cf6;">{{ number_format($totalActiveDistributors) }}</div>
                        <div style="font-size: 16px; color: #6b7280; margin-top: 10px;">Total active distributors</div>
                        <div style="display: inline-flex; align-items: center; gap: 8px; margin-top: 20px; padding: 8px 16px; background: rgba(139, 92, 246, 0.1); border-radius: 20px;">
                            <span style="width: 8px; height: 8px; background: #8b5cf6; border-radius: 50%; animation: pulse 2s infinite;"></span>
                            <span style="font-size: 14px; color: #8b5cf6; font-weight: 600;">Active Now</span>
                        </div>
                    </div>
                </div>

                <!-- Sign Ups View -->
                <div x-show="selectedView === 'signups'" x-cloak>
                    <h2 class="text-xl font-bold text-gray-900 mb-4">New Sign Ups This Month</h2>
                    <div style="background: #f9fafb; border-radius: 8px; padding: 40px; text-align: center;">
                        <div style="font-size: 72px; font-weight: bold; color: #06b6d4;">{{ number_format($newSignUpsThisMonth) }}</div>
                        <div style="font-size: 16px; color: #6b7280; margin-top: 10px;">New sign-ups in {{ date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear)) }}</div>
                        @if($compareWithPrevious && isset($trends['signups']))
                            <div style="font-size: 24px; color: {{ $trends['signups'] >= 0 ? '#10b981' : '#ef4444' }}; margin-top: 20px; font-weight: 600;">
                                {{ $trends['signups'] >= 0 ? '↑' : '↓' }} {{ abs($trends['signups']) }}% from last month
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    [x-cloak] { display: none !important; }
</style>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    // Global chart variable
    let productChart = null;

    function initializeProductChart() {
        try {
            // Check if ApexCharts is loaded
            if (typeof ApexCharts === 'undefined') {
                console.error('ApexCharts library not loaded');
                return;
            }

            // Get the chart container
            const chartElement = document.querySelector("#topProductsChart");

            if (!chartElement) {
                console.error('Chart container #topProductsChart not found');
                return;
            }

            // Check if element is visible
            const isVisible = chartElement.offsetParent !== null;
            console.log('Chart container visible:', isVisible);
            console.log('Chart container dimensions:', chartElement.offsetWidth, 'x', chartElement.offsetHeight);

            // Destroy existing chart if any
            if (productChart) {
                productChart.destroy();
            }

            // Chart data from backend
            const productData = @js($topProductsData['values']);
            const productLabels = @js($topProductsData['labels']);
            const productColors = @js($topProductsData['colors']);

            console.log('Initializing chart with data:', {
                data: productData,
                labels: productLabels,
                colors: productColors
            });

            // Validate data
            if (!productData || productData.length === 0) {
                console.error('No product data available');
                return;
            }

            const productOptions = {
                series: productData,
                chart: {
                    type: 'donut',
                    height: 300,
                    width: '100%',
                    fontFamily: 'Inter, sans-serif',
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800,
                        animateGradually: {
                            enabled: true,
                            delay: 150
                        }
                    }
                },
                labels: productLabels,
                colors: productColors,
                plotOptions: {
                    pie: {
                        donut: {
                            size: '65%',
                            labels: {
                                show: true,
                                name: {
                                    show: true,
                                    fontSize: '14px',
                                    fontWeight: 600,
                                    color: '#374151'
                                },
                                value: {
                                    show: true,
                                    fontSize: '20px',
                                    fontWeight: 700,
                                    color: '#111827',
                                    formatter: function(val) {
                                        return val
                                    }
                                },
                                total: {
                                    show: true,
                                    label: 'Total',
                                    fontSize: '12px',
                                    fontWeight: 600,
                                    color: '#6b7280',
                                    formatter: function(w) {
                                        const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                        return total;
                                    }
                                }
                            }
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val, opts) {
                        return Math.round(val) + '%'
                    },
                    style: {
                        fontSize: '11px',
                        fontWeight: 600,
                        colors: ['#fff']
                    },
                    dropShadow: {
                        enabled: true,
                        top: 1,
                        left: 1,
                        blur: 1,
                        color: '#000',
                        opacity: 0.45
                    }
                },
                legend: {
                    show: false
                },
                stroke: {
                    width: 2,
                    colors: ['#ffffff']
                },
                tooltip: {
                    enabled: true,
                    theme: 'light',
                    y: {
                        formatter: function(val, opts) {
                            const total = opts.globals.seriesTotals.reduce((a, b) => a + b, 0);
                            const percentage = ((val / total) * 100).toFixed(1);
                            return val + ' (' + percentage + '%)';
                        }
                    }
                },
                states: {
                    hover: {
                        filter: {
                            type: 'lighten',
                            value: 0.15
                        }
                    },
                    active: {
                        filter: {
                            type: 'darken',
                            value: 0.15
                        }
                    }
                },
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: {
                            height: 250
                        }
                    }
                }]
            };

            // Initialize the chart
            productChart = new ApexCharts(chartElement, productOptions);

            productChart.render().then(() => {
                console.log('Chart rendered successfully');
            }).catch((error) => {
                console.error('Chart rendering error:', error);
            });

        } catch (error) {
            console.error('Error initializing chart:', error);
        }
    }

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM Content Loaded');

        // Wait for Alpine.js to show the element (delay for x-show to process)
        setTimeout(() => {
            console.log('Attempting to initialize chart...');
            initializeProductChart();
        }, 500);
    });

    // Also try on window load as backup
    window.addEventListener('load', function() {
        if (!productChart) {
            console.log('Window loaded, retrying chart initialization...');
            setTimeout(initializeProductChart, 300);
        }
    });

    // Refresh chart on Livewire updates
    if (typeof Livewire !== 'undefined') {
        Livewire.on('refresh-hr-dashboard', () => {
            console.log('Livewire refresh triggered');
            setTimeout(initializeProductChart, 200);
        });
    }

    // Listen for Alpine.js view changes
    document.addEventListener('alpine:initialized', () => {
        console.log('Alpine initialized');
        setTimeout(initializeProductChart, 300);
    });
</script>
@endpush
