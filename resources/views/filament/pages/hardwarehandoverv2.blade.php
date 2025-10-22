{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/hardwarehandoverv2.blade.php --}}
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
    .group-total { border-top-color: #374151; }
    .group-initial-stage { border-top-color: #6b7280; }
    .group-courier { border-top-color: #2563eb; }
    .group-self-pickup { border-top-color: #f59e0b; }
    .group-external-installation { border-top-color: #8b5cf6; }
    .group-internal-installation { border-top-color: #10b981; }

    .group-total .group-count { color: #374151; }
    .group-initial-stage .group-count { color: #6b7280; }
    .group-courier .group-count { color: #2563eb; }
    .group-self-pickup .group-count { color: #f59e0b; }
    .group-external-installation .group-count { color: #8b5cf6; }
    .group-internal-installation .group-count { color: #10b981; }

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

    /* Column headers */
    .column-header {
        font-size: 14px;
        font-weight: 600;
        color: #4b5563;
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 1px solid #e5e7eb;
    }

    /* NEW COLOR CODING FOR STAT BOXES */
    /* Initial Stage */
    .new-task { border-left: 4px solid #2563eb; }
    .new-task .stat-count { color: #2563eb; }

    .auto-pump { border-left: 4px solid #0ea5e9; }
    .auto-pump .stat-count { color: #0ea5e9; }

    .rejected { border-left: 4px solid #ef4444; }
    .rejected .stat-count { color: #ef4444; }

    .pending-stock { border-left: 4px solid #f59e0b; }
    .pending-stock .stat-count { color: #f59e0b; }

    .pending-migration { border-left: 4px solid #8b5cf6; }
    .pending-migration .stat-count { color: #8b5cf6; }

    .pending-payment { border-left: 4px solid #ec4899; }
    .pending-payment .stat-count { color: #ec4899; }

    /* Courier */
    .pending-courier { border-left: 4px solid #2563eb; }
    .pending-courier .stat-count { color: #2563eb; }

    .completed-courier { border-left: 4px solid #10b981; }
    .completed-courier .stat-count { color: #10b981; }

    /* Self Pickup */
    .pending-admin-pickup { border-left: 4px solid #f59e0b; }
    .pending-admin-pickup .stat-count { color: #f59e0b; }

    .pending-customer-pickup { border-left: 4px solid #f97316; }
    .pending-customer-pickup .stat-count { color: #f97316; }

    .completed-pickup { border-left: 4px solid #10b981; }
    .completed-pickup .stat-count { color: #10b981; }

    /* External Installation */
    .pending-external { border-left: 4px solid #8b5cf6; }
    .pending-external .stat-count { color: #8b5cf6; }

    .completed-external { border-left: 4px solid #10b981; }
    .completed-external .stat-count { color: #10b981; }

    /* Internal Installation */
    .pending-internal { border-left: 4px solid #059669; }
    .pending-internal .stat-count { color: #059669; }

    .completed-internal { border-left: 4px solid #10b981; }
    .completed-internal .stat-count { color: #10b981; }

    /* Selected states for categories */
    .stat-box.selected.new-task { background-color: rgba(37, 99, 235, 0.05); border-left-width: 6px; }
    .stat-box.selected.auto-pump { background-color: rgba(14, 165, 233, 0.05); border-left-width: 6px; }
    .stat-box.selected.rejected { background-color: rgba(239, 68, 68, 0.05); border-left-width: 6px; }
    .stat-box.selected.pending-stock { background-color: rgba(245, 158, 11, 0.05); border-left-width: 6px; }
    .stat-box.selected.pending-migration { background-color: rgba(139, 92, 246, 0.05); border-left-width: 6px; }
    .stat-box.selected.pending-payment { background-color: rgba(236, 72, 153, 0.05); border-left-width: 6px; }

    .stat-box.selected.pending-courier { background-color: rgba(37, 99, 235, 0.05); border-left-width: 6px; }
    .stat-box.selected.completed-courier { background-color: rgba(16, 185, 129, 0.05); border-left-width: 6px; }

    .stat-box.selected.pending-admin-pickup { background-color: rgba(245, 158, 11, 0.05); border-left-width: 6px; }
    .stat-box.selected.pending-customer-pickup { background-color: rgba(249, 115, 22, 0.05); border-left-width: 6px; }
    .stat-box.selected.completed-pickup { background-color: rgba(16, 185, 129, 0.05); border-left-width: 6px; }

    .stat-box.selected.pending-external { background-color: rgba(139, 92, 246, 0.05); border-left-width: 6px; }
    .stat-box.selected.completed-external { background-color: rgba(16, 185, 129, 0.05); border-left-width: 6px; }

    .stat-box.selected.pending-internal { background-color: rgba(5, 150, 105, 0.05); border-left-width: 6px; }
    .stat-box.selected.completed-internal { background-color: rgba(16, 185, 129, 0.05); border-left-width: 6px; }

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
            grid-template-columns: repeat(3, 1fr);
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
    use App\Models\HardwareHandoverV2;

    // Get actual counts from database
    $totalCount = HardwareHandoverV2::count();

    // Initial Stage counts
    $newTaskCount = HardwareHandoverV2::where('status', 'New')->count();
    $rejectedCount = HardwareHandoverV2::where('status', 'Rejected')->count();
    $pendingStockCount = HardwareHandoverV2::where('status', 'Pending Stock')
        ->where('sales_order_status', 'packed')->count();
    $pendingMigrationCount = HardwareHandoverV2::where('status', 'Pending Migration')->count();
    $pendingPaymentCount = HardwareHandoverV2::where('status', 'Pending Payment')->count();

    // Courier counts
    $pendingCourierCount = HardwareHandoverV2::where('status', 'Pending: Courier')->count();
    $completedCourierCount = HardwareHandoverV2::where('status', 'Completed: Courier')->count();

    // Self Pickup counts
    $pendingAdminPickupCount = HardwareHandoverV2::where('status', 'Pending Admin: Self Pick-Up')->count();
    $pendingCustomerPickupCount = HardwareHandoverV2::where('status', 'Pending Customer: Self Pick-Up')->count();
    $completedPickupCount = HardwareHandoverV2::where('status', 'Completed: Self Pick-Up')->count();

    // External Installation counts
    $pendingExternalCount = HardwareHandoverV2::where('status', 'Pending: External Installation')->count();
    $completedExternalCount = HardwareHandoverV2::where('status', 'Completed: External Installation')->count();

    // Internal Installation counts
    $pendingInternalCount = HardwareHandoverV2::where('status', 'Pending: Internal Installation')->count();
    $completedInternalCount = HardwareHandoverV2::where('status', 'Completed: Internal Installation')->count();

    // Calculate group totals
    $initialStageTotal = $newTaskCount + $pendingStockCount;
    $courierTotal = $pendingCourierCount;
    $selfPickupTotal = $pendingAdminPickupCount;
    $externalInstallationTotal = $pendingExternalCount;
    $internalInstallationTotal = $pendingInternalCount;
@endphp

<div id="hardware-handover-container" class="hardware-handover-container"
     x-data="{
         selectedGroup: null,
         selectedStat: null,

         setSelectedGroup(value) {
             if (this.selectedGroup === value) {
                 this.selectedGroup = null;
                 this.selectedStat = null;
             } else {
                 this.selectedGroup = value;

                 // Set default stat for each group
                 if (value === 'initial-stage') {
                     this.selectedStat = 'new-task';
                 } else if (value === 'courier') {
                     this.selectedStat = 'pending-courier';
                 } else if (value === 'self-pickup') {
                     this.selectedStat = 'pending-admin-pickup';
                 } else if (value === 'external-installation') {
                     this.selectedStat = 'pending-external';
                 } else if (value === 'internal-installation') {
                     this.selectedStat = 'pending-internal';
                 } else {
                     this.selectedStat = null;
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
             console.log('Hardware handover v2 Alpine component initialized');
         }
     }"
     x-init="init()">

    <!-- New container structure -->
    <div class="dashboard-layout" wire:poll.300s>
        <!-- Left sidebar with groups -->
        <div class="group-column">
            <div class="group-container">
                <!-- Group: Total -->
                <div class="group-box group-total"
                     :class="{'selected': selectedGroup === 'total'}"
                     @click="setSelectedGroup('total')">
                    <div class="group-title">Hardware Handover</div>
                    <div class="group-count">{{ $totalCount }}</div>
                </div>

                <!-- Group: Initial Stage -->
                <div class="group-box group-initial-stage"
                     :class="{'selected': selectedGroup === 'initial-stage'}"
                     @click="setSelectedGroup('initial-stage')">
                    <div class="group-title">Initial Stage</div>
                    <div class="group-count">{{ $initialStageTotal }}</div>
                </div>

                <!-- Group: Courier -->
                <div class="group-box group-courier"
                     :class="{'selected': selectedGroup === 'courier'}"
                     @click="setSelectedGroup('courier')">
                    <div class="group-title">Courier</div>
                    <div class="group-count">{{ $courierTotal }}</div>
                </div>

                <!-- Group: Self Pick-Up -->
                <div class="group-box group-self-pickup"
                     :class="{'selected': selectedGroup === 'self-pickup'}"
                     @click="setSelectedGroup('self-pickup')">
                    <div class="group-title">Pick-Up</div>
                    <div class="group-count">{{ $selfPickupTotal }}</div>
                </div>

                <!-- Group: External Installation -->
                <div class="group-box group-external-installation"
                     :class="{'selected': selectedGroup === 'external-installation'}"
                     @click="setSelectedGroup('external-installation')">
                    <div class="group-title">External Installation</div>
                    <div class="group-count">{{ $externalInstallationTotal }}</div>
                </div>

                <!-- Group: Internal Installation -->
                <div class="group-box group-internal-installation"
                     :class="{'selected': selectedGroup === 'internal-installation'}"
                     @click="setSelectedGroup('internal-installation')">
                    <div class="group-title">Internal Installation</div>
                    <div class="group-count">{{ $internalInstallationTotal }}</div>
                </div>
            </div>
        </div>

        <!-- Right content area -->
        <div class="content-column">
            <!-- Total -->
            <div class="category-container" x-show="selectedGroup === 'total'">
                <div class="stat-box new-task"
                     :class="{'selected': selectedStat === 'total-handover'}"
                     @click="setSelectedStat('total-handover')">
                    <div class="stat-info">
                        <div class="stat-label">All Task</div>
                    </div>
                    <div class="stat-count">{{ $totalCount }}</div>
                </div>
            </div>
            <!-- INITIAL STAGE Categories -->
            <div class="category-container" x-show="selectedGroup === 'initial-stage'">
                <div class="stat-box new-task"
                     :class="{'selected': selectedStat === 'new-task'}"
                     @click="setSelectedStat('new-task')">
                    <div class="stat-info">
                        <div class="stat-label">New Task</div>
                    </div>
                    <div class="stat-count">{{ $newTaskCount }}</div>
                </div>

                <div class="stat-box rejected"
                     :class="{'selected': selectedStat === 'rejected'}"
                     @click="setSelectedStat('rejected')">
                    <div class="stat-info">
                        <div class="stat-label">Rejected</div>
                    </div>
                    <div class="stat-count">{{ $rejectedCount }}</div>
                </div>

                <div class="stat-box pending-stock"
                     :class="{'selected': selectedStat === 'pending-stock'}"
                     @click="setSelectedStat('pending-stock')">
                    <div class="stat-info">
                        <div class="stat-label">Pending Stock</div>
                    </div>
                    <div class="stat-count">{{ $pendingStockCount }}</div>
                </div>

                <div class="stat-box pending-migration"
                     :class="{'selected': selectedStat === 'pending-migration'}"
                     @click="setSelectedStat('pending-migration')">
                    <div class="stat-info">
                        <div class="stat-label">Pending Migration</div>
                    </div>
                    <div class="stat-count">{{ $pendingMigrationCount }}</div>
                </div>

                <div class="stat-box pending-payment"
                     :class="{'selected': selectedStat === 'pending-payment'}"
                     @click="setSelectedStat('pending-payment')">
                    <div class="stat-info">
                        <div class="stat-label">Pending Payment</div>
                    </div>
                    <div class="stat-count">{{ $pendingPaymentCount }}</div>
                </div>
            </div>

            <!-- COURIER Categories -->
            <div class="category-container" x-show="selectedGroup === 'courier'">
                <div class="stat-box pending-courier"
                     :class="{'selected': selectedStat === 'pending-courier'}"
                     @click="setSelectedStat('pending-courier')">
                    <div class="stat-info">
                        <div class="stat-label">Pending: Courier</div>
                    </div>
                    <div class="stat-count">{{ $pendingCourierCount }}</div>
                </div>

                <div class="stat-box completed-courier"
                     :class="{'selected': selectedStat === 'completed-courier'}"
                     @click="setSelectedStat('completed-courier')">
                    <div class="stat-info">
                        <div class="stat-label">Completed: Courier</div>
                    </div>
                    <div class="stat-count">{{ $completedCourierCount }}</div>
                </div>
            </div>

            <!-- SELF PICK-UP Categories -->
            <div class="category-container" x-show="selectedGroup === 'self-pickup'">
                <div class="stat-box pending-admin-pickup"
                     :class="{'selected': selectedStat === 'pending-admin-pickup'}"
                     @click="setSelectedStat('pending-admin-pickup')">
                    <div class="stat-info">
                        <div class="stat-label">Pending Admin: Pick-Up</div>
                    </div>
                    <div class="stat-count">{{ $pendingAdminPickupCount }}</div>
                </div>

                <div class="stat-box pending-customer-pickup"
                     :class="{'selected': selectedStat === 'pending-customer-pickup'}"
                     @click="setSelectedStat('pending-customer-pickup')">
                    <div class="stat-info">
                        <div class="stat-label">Pending Customer: Pick-Up</div>
                    </div>
                    <div class="stat-count">{{ $pendingCustomerPickupCount }}</div>
                </div>

                <div class="stat-box completed-pickup"
                     :class="{'selected': selectedStat === 'completed-pickup'}"
                     @click="setSelectedStat('completed-pickup')">
                    <div class="stat-info">
                        <div class="stat-label">Completed: Pick-Up</div>
                    </div>
                    <div class="stat-count">{{ $completedPickupCount }}</div>
                </div>
            </div>

            <!-- EXTERNAL INSTALLATION Categories -->
            <div class="category-container" x-show="selectedGroup === 'external-installation'">
                <div class="stat-box pending-external"
                     :class="{'selected': selectedStat === 'pending-external'}"
                     @click="setSelectedStat('pending-external')">
                    <div class="stat-info">
                        <div class="stat-label">Pending:</div>
                        <div class="stat-label">External Installation</div>
                    </div>
                    <div class="stat-count">{{ $pendingExternalCount }}</div>
                </div>

                <div class="stat-box completed-external"
                     :class="{'selected': selectedStat === 'completed-external'}"
                     @click="setSelectedStat('completed-external')">
                    <div class="stat-info">
                        <div class="stat-label">Completed:</div>
                        <div class="stat-label">External Installation</div>
                    </div>
                    <div class="stat-count">{{ $completedExternalCount }}</div>
                </div>
            </div>

            <!-- INTERNAL INSTALLATION Categories -->
            <div class="category-container" x-show="selectedGroup === 'internal-installation'">
                <div class="stat-box pending-internal"
                     :class="{'selected': selectedStat === 'pending-internal'}"
                     @click="setSelectedStat('pending-internal')">
                    <div class="stat-info">
                        <div class="stat-label">Pending:</div>
                        <div class="stat-label">Internal Installation</div>
                    </div>
                    <div class="stat-count">{{ $pendingInternalCount }}</div>
                </div>

                <div class="stat-box completed-internal"
                     :class="{'selected': selectedStat === 'completed-internal'}"
                     @click="setSelectedStat('completed-internal')">
                    <div class="stat-info">
                        <div class="stat-label">Completed:</div>
                        <div class="stat-label">Internal Installation</div>
                    </div>
                    <div class="stat-count">{{ $completedInternalCount }}</div>
                </div>
            </div>
            <br>
            <!-- Content Area for Tables -->
            <div class="content-area">
                <!-- Display hint message when nothing is selected -->
                <div class="hint-message" x-show="selectedGroup === null || selectedStat === null" x-transition>
                    <h3 x-text="selectedGroup === null ? 'Select a stage to continue' : 'Select a category to view data'"></h3>
                    <p x-text="selectedGroup === null ? 'Click on any of the stage boxes to see categories' : 'Click on any of the category boxes to display the corresponding information'"></p>
                </div>

                <div x-show="selectedStat === 'total-handover'" x-transition>
                    @livewire('admin-hardware-v2-dashboard.hardware-v2-all-table')
                </div>

                <!-- Initial Stage Tables -->
                <div x-show="selectedStat === 'new-task'" x-transition>
                    @livewire('admin-hardware-v2-dashboard.hardware-v2-new-table')
                </div>

                <div x-show="selectedStat === 'rejected'" x-transition>
                    @livewire('admin-hardware-v2-dashboard.hardware-v2-rejected-table')
                </div>

                <div x-show="selectedStat === 'pending-stock'" x-transition>
                    @livewire('admin-hardware-v2-dashboard.hardware-v2-pending-stock-table')
                </div>

                <div x-show="selectedStat === 'pending-migration'" x-transition>
                    @livewire('admin-hardware-v2-dashboard.hardware-v2-pending-migration-table')
                </div>

                <div x-show="selectedStat === 'pending-payment'" x-transition>
                    @livewire('admin-hardware-v2-dashboard.hardware-v2-pending-payment-table')
                </div>

                <!-- Courier Tables -->
                <div x-show="selectedStat === 'pending-courier'" x-transition>
                    @livewire('admin-hardware-v2-dashboard.hardware-v2-pending-courier-table')
                </div>

                <div x-show="selectedStat === 'completed-courier'" x-transition>
                    @livewire('admin-hardware-v2-dashboard.hardware-v2-completed-courier-table')
                </div>

                <!-- Self Pick-Up Tables -->
                <div x-show="selectedStat === 'pending-admin-pickup'" x-transition>
                    @livewire('admin-hardware-v2-dashboard.hardware-v2-pending-admin-self-pick-up-table')
                </div>

                <div x-show="selectedStat === 'pending-customer-pickup'" x-transition>
                    @livewire('admin-hardware-v2-dashboard.hardware-v2-pending-customer-self-pick-up-table')
                </div>

                <div x-show="selectedStat === 'completed-pickup'" x-transition>
                    @livewire('admin-hardware-v2-dashboard.hardware-v2-completed-self-pick-up-table')
                </div>

                <!-- External Installation Tables -->
                <div x-show="selectedStat === 'pending-external'" x-transition>
                    @livewire('admin-hardware-v2-dashboard.hardware-v2-pending-external-installation-table')
                </div>

                <div x-show="selectedStat === 'completed-external'" x-transition>
                    @livewire('admin-hardware-v2-dashboard.hardware-v2-completed-external-installation-table')
                </div>

                <!-- Internal Installation Tables -->
                <div x-show="selectedStat === 'pending-internal'" x-transition>
                    @livewire('admin-hardware-v2-dashboard.hardware-v2-pending-internal-installation-table')
                </div>

                <div x-show="selectedStat === 'completed-internal'" x-transition>
                    @livewire('admin-hardware-v2-dashboard.hardware-v2-completed-internal-installation-table')
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // When the page loads, setup handlers for this component
    document.addEventListener('DOMContentLoaded', function() {
        // Function to reset the hardware component v2
        window.resetHardwareHandoverV2 = function() {
            const container = document.getElementById('hardware-handover-container');
            if (container && container.__x) {
                container.__x.$data.selectedGroup = null;
                container.__x.$data.selectedStat = null;
                console.log('Hardware handover v2 reset via global function');
            }
        };

        // Listen for our custom reset event
        window.addEventListener('reset-hardware-dashboard-v2', function() {
            window.resetHardwareHandoverV2();
        });
    });
</script>
