<!-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/admindebtor.blade.php -->
<style>
    /* Container styling */
    .debtor-admin-container {
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
        align-items: center;
        margin-bottom: 15px;
        width: 100%;
        min-width: 150px;
        text-align: center;
        max-height: 82px;
        max-width: 220px;
    }

    .group-box:hover {
        transform: translateY(-3px);
        background-color: #f9fafb;
    }

    .group-box.selected {
        background-color: #f9fafb;
        transform: translateY(-5px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .group-title {
        font-size: 15px;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .group-count {
        font-size: 24px;
        font-weight: bold;
    }

    /* Color coding for different groups */
    .group-raw-data { border-top-color: #64748b; }
    .group-raw-data .group-count { color: #64748b; }

    .group-outstanding { border-top-color: #2563eb; }
    .group-outstanding .group-count { color: #2563eb; }

    .group-2025-outstanding { border-top-color: #10b981; }
    .group-2025-outstanding .group-count { color: #10b981; }

    /* Update group container */
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

    /* Category column styling */
    .category-column {
        padding-right: 10px;
    }

    /* Category container */
    .category-container {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 10px;
        border-right: 1px solid #e5e7eb;
        padding-right: 10px;
        max-height: 80vh;
        overflow-y: auto;
    }

    /* Tab styling */
    .tab-box {
        background-color: white;
        width: 100%;
        min-height: 65px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        cursor: pointer;
        transition: all 0.2s ease;
        border-left: 4px solid transparent;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 15px;
        margin-bottom: 8px;
    }

    .tab-box:hover {
        background-color: #f9fafb;
        transform: translateY(-2px);
    }

    .tab-box.selected {
        background-color: #f9fafb;
        transform: translateY(-3px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.15);
    }

    .tab-label {
        font-weight: 500;
        font-size: 14px;
        text-align: center;
    }

    /* Content area */
    .content-column {
        min-height: 600px;
    }

    .content-area {
        min-height: 600px;
    }

    .content-area .fi-ta {
        margin-top: 0;
    }

    .content-area .fi-ta-content {
        padding: 0.75rem !important;
    }

    /* Hint message */
    .hint-message {
        text-align: center;
        background-color: #f9fafb;
        border-radius: 0.5rem;
        border: 1px dashed #d1d5db;
        height: 530px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    .hint-message h3 {
        font-size: 1.25rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
    }

    .hint-message p {
        color: #6b7280;
    }

    /* Column headers */
    .column-header {
        font-size: 14px;
        font-weight: 600;
        color: #4b5563;
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 1px solid #e5e7eb;
    }

    /* Color coding for different tabs */
    /* Raw Data tabs */
    .tab-all { border-left-color: #64748b; }
    .tab-all .tab-count { color: #64748b; }

    .tab-negative { border-left-color: #ef4444; }
    .tab-negative .tab-count { color: #ef4444; }

    .tab-internal { border-left-color: #8b5cf6; }
    .tab-internal .tab-count { color: #8b5cf6; }

    .tab-staff-loan { border-left-color: #f59e0b; }
    .tab-staff-loan .tab-count { color: #f59e0b; }

    .tab-outstanding-raw { border-left-color: #10b981; }
    .tab-outstanding-raw .tab-count { color: #10b981; }

    /* Outstanding Payment tabs */
    .tab-year-filter { border-left-color: #2563eb; }
    .tab-year-filter .tab-count { color: #2563eb; }

    .tab-quarter-filter { border-left-color: #06b6d4; }
    .tab-quarter-filter .tab-count { color: #06b6d4; }

    /* 2025 Outstanding tabs */
    .tab-sales-hrdf { border-left-color: #10b981; }
    .tab-sales-hrdf .tab-count { color: #10b981; }

    .tab-sales-product { border-left-color: #14b8a6; }
    .tab-sales-product .tab-count { color: #14b8a6; }

    .tab-others-hrdf { border-left-color: #a855f7; }
    .tab-others-hrdf .tab-count { color: #a855f7; }

    .tab-others-product { border-left-color: #d946ef; }
    .tab-others-product .tab-count { color: #d946ef; }

    /* Selected state styling */
    .tab-box.selected.tab-all { background-color: rgba(100, 116, 139, 0.05); border-left-width: 6px; }
    .tab-box.selected.tab-negative { background-color: rgba(239, 68, 68, 0.05); border-left-width: 6px; }
    .tab-box.selected.tab-internal { background-color: rgba(139, 92, 246, 0.05); border-left-width: 6px; }
    .tab-box.selected.tab-staff-loan { background-color: rgba(245, 158, 11, 0.05); border-left-width: 6px; }
    .tab-box.selected.tab-outstanding-raw { background-color: rgba(16, 185, 129, 0.05); border-left-width: 6px; }
    .tab-box.selected.tab-year-filter { background-color: rgba(37, 99, 235, 0.05); border-left-width: 6px; }
    .tab-box.selected.tab-quarter-filter { background-color: rgba(6, 182, 212, 0.05); border-left-width: 6px; }
    .tab-box.selected.tab-sales-hrdf { background-color: rgba(16, 185, 129, 0.05); border-left-width: 6px; }
    .tab-box.selected.tab-sales-product { background-color: rgba(20, 184, 166, 0.05); border-left-width: 6px; }
    .tab-box.selected.tab-others-hrdf { background-color: rgba(168, 85, 247, 0.05); border-left-width: 6px; }
    .tab-box.selected.tab-others-product { background-color: rgba(217, 70, 239, 0.05); border-left-width: 6px; }

    /* Animation for tab switching */
    [x-transition] {
        transition: all 0.2s ease-out;
    }

    /* Responsive adjustments */
    @media (max-width: 1024px) {
        .dashboard-layout {
            grid-template-columns: 100%;
            grid-template-rows: auto auto;
        }

        .group-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            border-right: none;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .category-container {
            overflow-x: auto;
            padding-bottom: 5px;
        }

        .tab-box {
            min-width: 120px;
        }
    }

    @media (max-width: 768px) {
        .group-container {
            grid-template-columns: repeat(2, 1fr);
        }

        .category-container {
            overflow-x: auto;
        }
    }

    @media (max-width: 640px) {
        .group-container {
            grid-template-columns: 1fr;
        }

        .category-container {
            flex-direction: column;
            align-items: stretch;
        }

        .tab-box {
            width: 100%;
        }
    }
</style>

<div id="admin-debtor-container" class="debtor-admin-container"
    x-data="{
        selectedGroup: null,
        selectedTab: null,

        setSelectedGroup(value) {
            if (this.selectedGroup === value) {
                this.selectedGroup = null;
                this.selectedTab = null;
            } else {
                this.selectedGroup = value;

                // Set default tab based on selected group
                if (value === 'raw-data') {
                    this.selectedTab = 'all';
                } else if (value === 'outstanding') {
                    this.selectedTab = 'year-filter';
                } else if (value === '2025-outstanding') {
                    this.selectedTab = 'sales-hrdf';
                } else {
                    this.selectedTab = null;
                }
            }
        },

        setSelectedTab(value) {
            if (this.selectedTab === value) {
                this.selectedTab = null;
            } else {
                this.selectedTab = value;
            }
        }
    }">

    <div class="dashboard-layout" wire:poll.300s>
        <div class="group-column">
            <div class="group-container">
                <!-- Main Dashboard 1: Raw Data -->
                <div class="group-box group-raw-data"
                     :class="{'selected': selectedGroup === 'raw-data'}"
                     @click="setSelectedGroup('raw-data')">
                    <div class="group-title">Raw Data</div>
                    <div class="group-count">{{ $rawDataCount ?? 0 }}</div>
                </div>

                <!-- Main Dashboard 2: All Outstanding Payment -->
                <div class="group-box group-outstanding"
                     :class="{'selected': selectedGroup === 'outstanding'}"
                     @click="setSelectedGroup('outstanding')">
                    <div class="group-title">All Outstanding</div>
                    <div class="group-count">{{ $outstandingCount ?? 0 }}</div>
                </div>

                <!-- Main Dashboard 3: 2025 Outstanding Payment -->
                <div class="group-box group-2025-outstanding"
                     :class="{'selected': selectedGroup === '2025-outstanding'}"
                     @click="setSelectedGroup('2025-outstanding')">
                    <div class="group-title">2025 Outstanding</div>
                    <div class="group-count">{{ $outstanding2025Count ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="content-column">
            <!-- Raw Data Sub Tabs -->
            <div class="category-container" x-show="selectedGroup === 'raw-data'">
                <div class="tab-box tab-all"
                     :class="{'selected': selectedTab === 'all'}"
                     @click="setSelectedTab('all')">
                    <div class="tab-label">All</div>
                </div>

                <div class="tab-box tab-negative"
                     :class="{'selected': selectedTab === 'negative'}"
                     @click="setSelectedTab('negative')">
                    <div class="tab-label">Negative Value</div>
                </div>

                <div class="tab-box tab-internal"
                     :class="{'selected': selectedTab === 'internal'}"
                     @click="setSelectedTab('internal')">
                    <div class="tab-label">Internal Company</div>
                </div>

                <div class="tab-box tab-staff-loan"
                     :class="{'selected': selectedTab === 'staff-loan'}"
                     @click="setSelectedTab('staff-loan')">
                    <div class="tab-label">Staff Loan</div>
                </div>

                <div class="tab-box tab-outstanding-raw"
                     :class="{'selected': selectedTab === 'outstanding-raw'}"
                     @click="setSelectedTab('outstanding-raw')">
                    <div class="tab-label">Outstanding Payment</div>
                </div>
            </div>

            <!-- All Outstanding Payment Sub Tabs -->
            <div class="category-container" x-show="selectedGroup === 'outstanding'">
                <div class="tab-box tab-year-filter"
                     :class="{'selected': selectedTab === 'year-filter'}"
                     @click="setSelectedTab('year-filter')">
                    <div class="tab-label">Year 24/23/22</div>
                </div>

                <div class="tab-box tab-quarter-filter"
                     :class="{'selected': selectedTab === 'quarter-filter'}"
                     @click="setSelectedTab('quarter-filter')">
                    <div class="tab-label">Year 2025</div>
                </div>
            </div>

            <!-- 2025 Outstanding Payment Sub Tabs -->
            <div class="category-container" x-show="selectedGroup === '2025-outstanding'">
                <div class="tab-box tab-sales-hrdf"
                     :class="{'selected': selectedTab === 'sales-hrdf'}"
                     @click="setSelectedTab('sales-hrdf')">
                    <div class="tab-label">SalesPerson - HRDF</div>
                </div>

                <div class="tab-box tab-sales-product"
                     :class="{'selected': selectedTab === 'sales-product'}"
                     @click="setSelectedTab('sales-product')">
                    <div class="tab-label">SalesPerson - Product</div>
                </div>

                <div class="tab-box tab-others-hrdf"
                     :class="{'selected': selectedTab === 'others-hrdf'}"
                     @click="setSelectedTab('others-hrdf')">
                    <div class="tab-label">Others - HRDF</div>
                </div>

                <div class="tab-box tab-others-product"
                     :class="{'selected': selectedTab === 'others-product'}"
                     @click="setSelectedTab('others-product')">
                    <div class="tab-label">Others - Product</div>
                </div>
            </div>

            <div class="content-area">
                <!-- Display hint message when nothing is selected -->
                <div class="hint-message" x-show="selectedGroup === null || selectedTab === null" x-transition>
                    <h3 x-text="selectedGroup === null ? 'Select a dashboard to continue' : 'Select a tab to view data'"></h3>
                    <p x-text="selectedGroup === null ? 'Click on any of the dashboard boxes above' : 'Click on any of the tabs to display the corresponding information'"></p>
                </div>

                <!-- Raw Data Content -->
                <div x-show="selectedTab === 'all'" x-transition :key="selectedTab + '-all'">
                    <livewire:admin-debtor-dashboard.debtor-raw-data-all-table />
                </div>

                <div x-show="selectedTab === 'negative'" x-transition :key="selectedTab + '-negative'">
                    <livewire:admin-debtor-dashboard.debtor-raw-data-negative-table />
                </div>

                <div x-show="selectedTab === 'internal'" x-transition :key="selectedTab + '-internal'">
                    <livewire:admin-debtor-dashboard.debtor-raw-data-internal-table />
                </div>

                <div x-show="selectedTab === 'staff-loan'" x-transition :key="selectedTab + '-staff-loan'">
                    <livewire:admin-debtor-dashboard.debtor-raw-data-staff-loan-table />
                </div>

                <div x-show="selectedTab === 'outstanding-raw'" x-transition :key="selectedTab + '-outstanding-raw'">
                    <livewire:admin-debtor-dashboard.debtor-raw-data-outstanding-table />
                </div>

                <!-- All Outstanding Payment Content -->
                <div x-show="selectedTab === 'year-filter'" x-transition :key="selectedTab + '-year-filter'">
                    {{-- <livewire:admin-debtor-dashboard.outstanding-year-filter-table /> --}}
                </div>

                <div x-show="selectedTab === 'quarter-filter'" x-transition :key="selectedTab + '-quarter-filter'">
                    {{-- <livewire:admin-debtor-dashboard.outstanding-quarter-filter-table /> --}}
                </div>

                <!-- 2025 Outstanding Payment Content -->
                <div x-show="selectedTab === 'sales-hrdf'" x-transition :key="selectedTab + '-sales-hrdf'">
                    {{-- <livewire:admin-debtor-dashboard.outstanding-2025-sales-hrdf-table /> --}}
                </div>

                <div x-show="selectedTab === 'sales-product'" x-transition :key="selectedTab + '-sales-product'">
                    {{-- <livewire:admin-debtor-dashboard.outstanding-2025-sales-product-table /> --}}
                </div>

                <div x-show="selectedTab === 'others-hrdf'" x-transition :key="selectedTab + '-others-hrdf'">
                    {{-- <livewire:admin-debtor-dashboard.outstanding-2025-others-hrdf-table /> --}}
                </div>

                <div x-show="selectedTab === 'others-product'" x-transition :key="selectedTab + '-others-product'">
                    {{-- <livewire:admin-debtor-dashboard.outstanding-2025-others-product-table /> --}}
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to reset the admin debtor component
        window.resetAdminDebtor = function() {
            const container = document.getElementById('admin-debtor-container');
            if (container && container.__x) {
                container.__x.$data.selectedGroup = null;
                container.__x.$data.selectedTab = null;
                console.log('Admin debtor dashboard reset via global function');
            }
        };

        // Listen for our custom reset event
        window.addEventListener('reset-admin-debtor-dashboard', function() {
            window.resetAdminDebtor();
        });
    });
</script>
