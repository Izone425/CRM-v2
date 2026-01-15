<x-filament-panels::page>
<style>
    /* Container styling */
    .hardware-handover-container {
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
        text-align: center; /* Changed from center to left */
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

    .group-desc {
        font-size: 12px;
        color: #6b7280;
    }

    .group-count {
        font-size: 24px;
        font-weight: bold;
    }

    /* GROUP COLORS */
    .group-all-items { border-top-color: #6b7280; }
    .group-reseller { border-top-color: #2563eb; }

    .group-all-items .group-count { color: #6b7280; }
    .group-reseller .group-count { color: #2563eb; }

    /* Group container layout */
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

    /* Stat box styling */
    .stat-box {
        background-color: white;
        width: 100%;
        min-height: 65px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        cursor: pointer;
        transition: all 0.2s ease;
        border-left: 4px solid transparent;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 15px;
        margin-bottom: 8px;
    }

    .stat-box:hover {
        background-color: #f9fafb;
        transform: translateX(3px);
    }

    .stat-box.selected {
        background-color: #f9fafb;
        transform: translateX(5px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.15);
    }

    .stat-info {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
    }

    .stat-count {
        font-size: 20px;
        font-weight: bold;
        margin: 0;
        line-height: 1.2;
    }

    .stat-label {
        color: #6b7280;
        font-size: 13px;
        font-weight: 500;
        line-height: 1.2;
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

    /* NEW COLOR CODING FOR STAT BOXES */
    .all-items { border-left: 4px solid #6b7280; }
    .all-items .stat-count { color: #6b7280; }

    .reseller-all { border-left: 4px solid #3b82f6; }
    .reseller-all .stat-count { color: #3b82f6; }

    .reseller-new { border-left: 4px solid #10b981; }
    .reseller-new .stat-count { color: #10b981; }

    .reseller-pending-invoice { border-left: 4px solid #f59e0b; }
    .reseller-pending-invoice .stat-count { color: #f59e0b; }

    .reseller-pending-license { border-left: 4px solid #8b5cf6; }
    .reseller-pending-license .stat-count { color: #8b5cf6; }

    .reseller-completed { border-left: 4px solid #06b6d4; }
    .reseller-completed .stat-count { color: #06b6d4; }

    /* Selected states for categories */
    .stat-box.selected.all-items { background-color: rgba(107, 114, 128, 0.05); border-left-width: 6px; }
    .stat-box.selected.reseller-all { background-color: rgba(59, 130, 246, 0.05); border-left-width: 6px; }
    .stat-box.selected.reseller-new { background-color: rgba(16, 185, 129, 0.05); border-left-width: 6px; }
    .stat-box.selected.reseller-pending-invoice { background-color: rgba(245, 158, 11, 0.05); border-left-width: 6px; }
    .stat-box.selected.reseller-pending-license { background-color: rgba(139, 92, 246, 0.05); border-left-width: 6px; }
    .stat-box.selected.reseller-completed { background-color: rgba(6, 182, 212, 0.05); border-left-width: 6px; }

    /* Animation for tab switching */
    [x-transition] {
        transition: all 0.2s ease-out;
    }

    /* Responsive adjustments */
    @media (max-width: 1200px) {
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
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            border-right: none;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 15px;
            margin-bottom: 15px;
            max-height: none;
        }

        .stat-box:hover, .stat-box.selected {
            transform: translateY(-3px);
        }
    }

    @media (max-width: 768px) {
        .group-container {
            grid-template-columns: repeat(2, 1fr);
        }
        .category-container {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 640px) {
        .group-container,
        .category-container {
            grid-template-columns: 1fr;
        }
    }
</style>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    @php
        $allCount = \App\Models\ResellerHandover::count();
        $newCount = \App\Models\ResellerHandover::where('status', 'new')->count();
        $pendingInvoiceCount = \App\Models\ResellerHandover::where('status', 'pending_timetec_invoice')->count();
        $pendingLicenseCount = \App\Models\ResellerHandover::where('status', 'pending_timetec_license')->count();
        $completedCount = \App\Models\ResellerHandover::where('status', 'completed')->count();

        // Finance Invoice counts
        $resellerPortalCount = \App\Models\FinanceInvoice::where('portal_type', 'reseller')->count();
        $adminPortalCount = \App\Models\FinanceInvoice::where('portal_type', 'admin')->count();
        $totalInvoiceCount = $resellerPortalCount + $adminPortalCount;

        // Admin Portal Finance Invoice counts
        $adminPortalNewCount = \App\Models\FinanceInvoice::where('portal_type', 'admin')->where('status', 'new')->count();
        $adminPortalCompletedCount = \App\Models\FinanceInvoice::where('portal_type', 'admin')->where('status', 'completed')->count();
        $adminPortalAllCount = $adminPortalNewCount + $adminPortalCompletedCount;
    @endphp

    <div id="admin-reseller-container" class="hardware-handover-container"
        x-data="{
            selectedGroup: null,
            selectedStat: null,
            allCount: {{ $allCount }},
            newCount: {{ $newCount }},
            pendingInvoiceCount: {{ $pendingInvoiceCount }},
            pendingLicenseCount: {{ $pendingLicenseCount }},
            completedCount: {{ $completedCount }},
            totalInvoiceCount: {{ $totalInvoiceCount }},
            resellerPortalCount: {{ $resellerPortalCount }},
            adminPortalCount: {{ $adminPortalCount }},
            adminPortalNewCount: {{ $adminPortalNewCount }},
            adminPortalCompletedCount: {{ $adminPortalCompletedCount }},
            adminPortalAllCount: {{ $adminPortalAllCount }},

            setSelectedGroup(value) {
                if (this.selectedGroup === value) {
                    this.selectedGroup = null;
                    this.selectedStat = null;
                } else {
                    this.selectedGroup = value;
                    if (value === 'reseller-handover') {
                        this.selectedStat = 'reseller-all';
                    } else if (value === 'generate-invoice') {
                        this.selectedStat = 'invoice-reseller-portal';
                    } else if (value === 'admin-portal') {
                        this.selectedStat = 'admin-portal-all';
                    }
                }
            },

            setSelectedStat(value) {
                if (this.selectedStat === value) {
                    this.selectedStat = null;
                } else {
                    this.selectedStat = value;
                }
            },

            init() {
                console.log('Admin reseller Alpine component initialized');
            }
        }"
        x-init="init()"
        @refresh-leadowner-tables.window="
            fetch('{{ route('admin.reseller-handover.counts') }}')
                .then(response => response.json())
                .then(data => {
                    allCount = data.new + data.pending_timetec_invoice + data.pending_timetec_license + data.completed;
                    newCount = data.new;
                    pendingInvoiceCount = data.pending_timetec_invoice;
                    pendingLicenseCount = data.pending_timetec_license;
                    completedCount = data.completed;
                })
                .catch(error => console.error('Error fetching counts:', error));
        ">

        <div class="dashboard-layout" wire:poll.300s>
            <!-- Left sidebar with groups -->
            <div class="group-column">
                <div class="group-container">
                    <div class="group-box group-reseller"
                            :class="{'selected': selectedGroup === 'reseller-handover'}"
                            @click="setSelectedGroup('reseller-handover')">
                        <div class="group-title">Reseller Handover</div>
                        <div class="group-count" x-text="allCount"></div>
                    </div>

                    <div class="group-box group-reseller"
                            :class="{'selected': selectedGroup === 'generate-invoice'}"
                            @click="setSelectedGroup('generate-invoice')">
                        <div class="group-title">Generate Invoice</div>
                        <div class="group-count" x-text="totalInvoiceCount"></div>
                    </div>

                    <div class="group-box group-reseller"
                            :class="{'selected': selectedGroup === 'admin-portal'}"
                            @click="setSelectedGroup('admin-portal')">
                        <div class="group-title">Admin Portal</div>
                        <div class="group-count" x-text="adminPortalAllCount"></div>
                    </div>
                </div>
            </div>

            <!-- Right content area -->
            <div class="content-column">
                <!-- Category Container -->
                <div class="category-container" x-show="selectedGroup === 'reseller-handover'">
                    <div class="stat-box reseller-all"
                            :class="{'selected': selectedStat === 'reseller-all'}"
                            @click="setSelectedStat('reseller-all')">
                        <div class="stat-info">
                            <div class="stat-label">All Handovers</div>
                        </div>
                        <div class="stat-count" x-text="allCount"></div>
                    </div>

                    <div class="stat-box reseller-new"
                            :class="{'selected': selectedStat === 'reseller-new'}"
                            @click="setSelectedStat('reseller-new')">
                        <div class="stat-info">
                            <div class="stat-label">New</div>
                        </div>
                        <div class="stat-count" x-text="newCount"></div>
                    </div>

                    <div class="stat-box reseller-pending-invoice"
                            :class="{'selected': selectedStat === 'reseller-pending-invoice'}"
                            @click="setSelectedStat('reseller-pending-invoice')">
                        <div class="stat-info">
                            <div class="stat-label">Pending TimeTec Invoice</div>
                        </div>
                        <div class="stat-count" x-text="pendingInvoiceCount"></div>
                    </div>

                    <div class="stat-box reseller-pending-license"
                            :class="{'selected': selectedStat === 'reseller-pending-license'}"
                            @click="setSelectedStat('reseller-pending-license')">
                        <div class="stat-info">
                            <div class="stat-label">Pending TimeTec License</div>
                        </div>
                        <div class="stat-count" x-text="pendingLicenseCount"></div>
                    </div>

                    <div class="stat-box reseller-completed"
                            :class="{'selected': selectedStat === 'reseller-completed'}"
                            @click="setSelectedStat('reseller-completed')">
                        <div class="stat-info">
                            <div class="stat-label">Completed</div>
                        </div>
                        <div class="stat-count" x-text="completedCount"></div>
                    </div>
                </div>

                <!-- Generate Invoice Categories -->
                <div class="category-container" x-show="selectedGroup === 'generate-invoice'">
                    <div class="stat-box reseller-new"
                            :class="{'selected': selectedStat === 'invoice-reseller-portal'}"
                            @click="setSelectedStat('invoice-reseller-portal')">
                        <div class="stat-info">
                            <div class="stat-label">Reseller Portal</div>
                        </div>
                        <div class="stat-count" x-text="resellerPortalCount"></div>
                    </div>

                    <div class="stat-box reseller-pending-invoice"
                            :class="{'selected': selectedStat === 'invoice-admin-portal'}"
                            @click="setSelectedStat('invoice-admin-portal')">
                        <div class="stat-info">
                            <div class="stat-label">Admin Portal</div>
                        </div>
                        <div class="stat-count" x-text="adminPortalCount"></div>
                    </div>
                </div>

                <!-- Admin Portal Categories -->
                <div class="category-container" x-show="selectedGroup === 'admin-portal'">
                    <div class="stat-box reseller-new"
                            :class="{'selected': selectedStat === 'admin-portal-new'}"
                            @click="setSelectedStat('admin-portal-new')">
                        <div class="stat-info">
                            <div class="stat-label">New</div>
                        </div>
                        <div class="stat-count" x-text="adminPortalNewCount"></div>
                    </div>

                    <div class="stat-box reseller-completed"
                            :class="{'selected': selectedStat === 'admin-portal-completed'}"
                            @click="setSelectedStat('admin-portal-completed')">
                        <div class="stat-info">
                            <div class="stat-label">Completed</div>
                        </div>
                        <div class="stat-count" x-text="adminPortalCompletedCount"></div>
                    </div>
                </div>
                <br>
                <!-- Content Area for Tables -->
                <div class="content-area">
                    <!-- Display hint message when nothing is selected -->
                    <div class="hint-message" x-show="selectedGroup === null || selectedStat === null" x-transition>
                        <h3 x-text="selectedGroup === null ? 'Select a dashboard to continue' : 'Select a category to view data'"></h3>
                        <p x-text="selectedGroup === null ? 'Click on the Reseller Handover box to see categories' : 'Click on any of the category boxes to display the corresponding information'"></p>
                    </div>

                    <!-- All Handovers -->
                    <div x-show="selectedStat === 'reseller-all'" x-transition>
                        <livewire:admin-reseller-handover-all />
                    </div>

                    <!-- New -->
                    <div x-show="selectedStat === 'reseller-new'" x-transition>
                        <livewire:reseller-handover-new />
                    </div>

                    <!-- Pending Invoice -->
                    <div x-show="selectedStat === 'reseller-pending-invoice'" x-transition>
                        <livewire:reseller-handover-pending-timetec-invoice />
                    </div>

                    <!-- Pending License -->
                    <div x-show="selectedStat === 'reseller-pending-license'" x-transition>
                        <livewire:reseller-handover-pending-timetec-license />
                    </div>

                    <!-- Completed -->
                    <div x-show="selectedStat === 'reseller-completed'" x-transition>
                        <livewire:admin-reseller-handover-completed />
                    </div>

                    <!-- Generate Invoice - Reseller Portal -->
                    <div x-show="selectedStat === 'invoice-reseller-portal'" x-transition>
                        <livewire:finance-invoice.generate-invoice-reseller-portal />
                    </div>

                    <!-- Generate Invoice - Admin Portal -->
                    <div x-show="selectedStat === 'invoice-admin-portal'" x-transition>
                        <livewire:finance-invoice.generate-invoice-admin-portal />
                    </div>

                    <!-- Admin Portal - New -->
                    <div x-show="selectedStat === 'admin-portal-new'" x-transition>
                        <livewire:admin-portal-finance-invoice-new />
                    </div>

                    <!-- Admin Portal - Completed -->
                    <div x-show="selectedStat === 'admin-portal-completed'" x-transition>
                        <livewire:admin-portal-finance-invoice-completed />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // When the page loads, setup handlers for admin reseller component
        document.addEventListener('DOMContentLoaded', function() {
            // Function to reset the admin reseller component
            window.resetAdminReseller = function() {
                const container = document.getElementById('admin-reseller-container');
                if (container && container.__x) {
                    container.__x.$data.selectedGroup = null;
                    container.__x.$data.selectedStat = null;
                    console.log('Admin reseller reset via global function');
                }
            };

            // Listen for our custom reset event
            window.addEventListener('reset-admin-reseller', function() {
                window.resetAdminReseller();
            });
        });

        document.addEventListener('livewire:init', () => {
            Livewire.on('refresh-leadowner-tables', () => {
                console.log('Refresh event received');
            });
        });
    </script>
</x-filament-panels::page>
