<style>
    /* Container styling */
    .admin-dashboard-container {
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

    /* GROUP COLORS */
    .group-software { border-top-color: #2563eb; }
    .group-hardware { border-top-color: #f59e0b; }
    .group-repair { border-top-color: #8b5cf6; }

    .group-software .group-count { color: #2563eb; }
    .group-hardware .group-count { color: #f59e0b; }
    .group-repair .group-count { color: #8b5cf6; }

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
        grid-template-columns: repeat(4, 1fr);
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
    /* Software stats */
    .software-new-task { border-left: 4px solid #2563eb; }
    .software-new-task .stat-count { color: #2563eb; }

    .software-pending-kick-off { border-left: 4px solid #3b82f6; }
    .software-pending-kick-off .stat-count { color: #3b82f6; }

    .software-pending-license { border-left: 4px solid #60a5fa; }
    .software-pending-license .stat-count { color: #60a5fa; }

    /* Hardware stats */
    .hardware-new-task { border-left: 4px solid #f59e0b; }
    .hardware-new-task .stat-count { color: #f59e0b; }

    .hardware-pending-stock { border-left: 4px solid #fbbf24; }
    .hardware-pending-stock .stat-count { color: #fbbf24; }

    .hardware-completed-migration { border-left: 4px solid #fcd34d; }
    .hardware-completed-migration .stat-count { color: #fcd34d; }

    /* Repair stats */
    .repair-accepted-task { border-left: 4px solid #8b5cf6; }
    .repair-accepted-task .stat-count { color: #8b5cf6; }

    .repair-pending-confirmation { border-left: 4px solid #a78bfa; }
    .repair-pending-confirmation .stat-count { color: #a78bfa; }

    .repair-completed-tech { border-left: 4px solid #c4b5fd; }
    .repair-completed-tech .stat-count { color: #c4b5fd; }

    /* Selected states for categories */
    .stat-box.selected.software-new-task { background-color: rgba(37, 99, 235, 0.05); border-left-width: 6px; }
    .stat-box.selected.software-pending-kick-off { background-color: rgba(59, 130, 246, 0.05); border-left-width: 6px; }
    .stat-box.selected.software-pending-license { background-color: rgba(96, 165, 250, 0.05); border-left-width: 6px; }

    .stat-box.selected.hardware-new-task { background-color: rgba(245, 158, 11, 0.05); border-left-width: 6px; }
    .stat-box.selected.hardware-pending-stock { background-color: rgba(251, 191, 36, 0.05); border-left-width: 6px; }
    .stat-box.selected.hardware-completed-migration { background-color: rgba(252, 211, 77, 0.05); border-left-width: 6px; }

    .stat-box.selected.repair-accepted-task { background-color: rgba(139, 92, 246, 0.05); border-left-width: 6px; }
    .stat-box.selected.repair-pending-confirmation { background-color: rgba(167, 139, 250, 0.05); border-left-width: 6px; }
    .stat-box.selected.repair-completed-tech { background-color: rgba(196, 181, 253, 0.05); border-left-width: 6px; }

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
    // SOFTWARE MODULE COUNTS
    $softwareNewCount = app(\App\Livewire\SalespersonDashboard\SoftwareHandoverNew::class)
        ->getNewSoftwareHandovers()
        ->count();

    $softwarePendingKickOffCount = app(\App\Livewire\SoftwareHandoverKickOffReminder::class)
        ->getNewSoftwareHandovers()
        ->count();

    $softwarePendingLicenseCount = app(\App\Livewire\SoftwareHandoverPendingLicense::class)
        ->getNewSoftwareHandovers()
        ->count();

    $softwareTotalCount = $softwareNewCount + $softwarePendingKickOffCount + $softwarePendingLicenseCount;

    // HARDWARE MODULE COUNTS
    $hardwareNewCount = app(\App\Livewire\SalespersonDashboard\HardwareHandoverNew::class)
        ->getNewHardwareHandovers()
        ->count();

    $hardwarePendingStockCount = app(\App\Livewire\HardwareHandoverPendingStock::class)
        ->getOverdueHardwareHandovers()
        ->count();

    $hardwareCompletedMigrationCount = app(\App\Livewire\HardwareHandoverCompletedMigration::class)
        ->getOverdueHardwareHandovers()
        ->count();

    $hardwareTotalCount = $hardwareNewCount + $hardwarePendingStockCount + $hardwareCompletedMigrationCount;

    // REPAIR MODULE COUNTS
    $repairAcceptedCount = app(\App\Livewire\AdminRepairAccepted::class)
        ->getTableQuery()
        ->count();

    $repairPendingConfirmationCount = app(\App\Livewire\AdminRepairPendingConfirmation::class)
        ->getTableQuery()
        ->count();

    $repairCompletedTechCount = app(\App\Livewire\AdminRepairCompletedTechnician::class)
        ->getTableQuery()
        ->count();

    $repairTotalCount = $repairAcceptedCount + $repairPendingConfirmationCount + $repairCompletedTechCount;

    // GRAND TOTAL
    $totalTaskCount = $softwareTotalCount + $hardwareTotalCount + $repairTotalCount;
@endphp

<div id="admin-dashboard-container" class="admin-dashboard-container"
     x-data="{
         selectedGroup: 'software',
         selectedStat: 'software-new-task',

         setSelectedGroup(value) {
             if (this.selectedGroup === value) {
                 this.selectedGroup = null;
                 this.selectedStat = null;
             } else {
                 this.selectedGroup = value;

                 // Set default stat for each group
                 if (value === 'software') {
                     this.selectedStat = 'software-new-task';
                 } else if (value === 'hardware') {
                     this.selectedStat = 'hardware-new-task';
                 } else if (value === 'repair') {
                     this.selectedStat = 'repair-accepted-task';
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
             console.log('Admin Dashboard Alpine component initialized');
         }
     }"
     x-init="init()">

    <div class="dashboard-layout" wire:poll.300s>
        <!-- Left sidebar with groups -->
        <div class="group-column">
            <div class="group-container">
                <!-- Group: Software -->
                <div class="group-box group-software"
                     :class="{'selected': selectedGroup === 'software'}"
                     @click="setSelectedGroup('software')">
                    <div class="group-title">Admin Software</div>
                    <div class="group-count">{{ $softwareTotalCount }}</div>
                </div>

                <!-- Group: Hardware -->
                <div class="group-box group-hardware"
                     :class="{'selected': selectedGroup === 'hardware'}"
                     @click="setSelectedGroup('hardware')">
                    <div class="group-title">Admin Hardware</div>
                    <div class="group-count">{{ $hardwareTotalCount }}</div>
                </div>

                <!-- Group: Repair -->
                <div class="group-box group-repair"
                     :class="{'selected': selectedGroup === 'repair'}"
                     @click="setSelectedGroup('repair')">
                    <div class="group-title">Admin Repair</div>
                    <div class="group-count">{{ $repairTotalCount }}</div>
                </div>
            </div>
        </div>

        <div class="content-column">
            <!-- SOFTWARE MODULE CATEGORIES -->
            <div class="category-container" x-show="selectedGroup === 'software'">
                <div class="stat-box software-new-task"
                     :class="{'selected': selectedStat === 'software-new-task'}"
                     @click="setSelectedStat('software-new-task')">
                    <div class="stat-info">
                        <div class="stat-label">New Task</div>
                    </div>
                    <div class="stat-count">{{ $softwareNewCount }}</div>
                </div>

                <div class="stat-box software-pending-kick-off"
                     :class="{'selected': selectedStat === 'software-pending-kick-off'}"
                     @click="setSelectedStat('software-pending-kick-off')">
                    <div class="stat-info">
                        <div class="stat-label">Pending Kick Off</div>
                    </div>
                    <div class="stat-count">{{ $softwarePendingKickOffCount }}</div>
                </div>

                <div class="stat-box software-pending-license"
                     :class="{'selected': selectedStat === 'software-pending-license'}"
                     @click="setSelectedStat('software-pending-license')">
                    <div class="stat-info">
                        <div class="stat-label">Pending License</div>
                    </div>
                    <div class="stat-count">{{ $softwarePendingLicenseCount }}</div>
                </div>
            </div>

            <!-- HARDWARE MODULE CATEGORIES -->
            <div class="category-container" x-show="selectedGroup === 'hardware'">
                <div class="stat-box hardware-new-task"
                     :class="{'selected': selectedStat === 'hardware-new-task'}"
                     @click="setSelectedStat('hardware-new-task')">
                    <div class="stat-info">
                        <div class="stat-label">New Task</div>
                    </div>
                    <div class="stat-count">{{ $hardwareNewCount }}</div>
                </div>

                <div class="stat-box hardware-pending-stock"
                     :class="{'selected': selectedStat === 'hardware-pending-stock'}"
                     @click="setSelectedStat('hardware-pending-stock')">
                    <div class="stat-info">
                        <div class="stat-label">Pending Stock</div>
                    </div>
                    <div class="stat-count">{{ $hardwarePendingStockCount }}</div>
                </div>

                <div class="stat-box hardware-completed-migration"
                     :class="{'selected': selectedStat === 'hardware-completed-migration'}"
                     @click="setSelectedStat('hardware-completed-migration')">
                    <div class="stat-info">
                        <div class="stat-label">Completed Migration</div>
                    </div>
                    <div class="stat-count">{{ $hardwareCompletedMigrationCount }}</div>
                </div>
            </div>

            <!-- REPAIR MODULE CATEGORIES -->
            <div class="category-container" x-show="selectedGroup === 'repair'">
                <div class="stat-box repair-accepted-task"
                     :class="{'selected': selectedStat === 'repair-accepted-task'}"
                     @click="setSelectedStat('repair-accepted-task')">
                    <div class="stat-info">
                        <div class="stat-label">Accepted Task</div>
                    </div>
                    <div class="stat-count">{{ $repairAcceptedCount }}</div>
                </div>

                <div class="stat-box repair-pending-confirmation"
                     :class="{'selected': selectedStat === 'repair-pending-confirmation'}"
                     @click="setSelectedStat('repair-pending-confirmation')">
                    <div class="stat-info">
                        <div class="stat-label">Pending Confirmation</div>
                    </div>
                    <div class="stat-count">{{ $repairPendingConfirmationCount }}</div>
                </div>

                <div class="stat-box repair-completed-tech"
                     :class="{'selected': selectedStat === 'repair-completed-tech'}"
                     @click="setSelectedStat('repair-completed-tech')">
                    <div class="stat-info">
                        <div class="stat-label">Completed Technician</div>
                    </div>
                    <div class="stat-count">{{ $repairCompletedTechCount }}</div>
                </div>
            </div>

            <br>

            <!-- Content Area for Tables -->
            <div class="content-area">
                <!-- Display hint message when nothing is selected -->
                <div class="hint-message" x-show="selectedGroup === null || selectedStat === null" x-transition>
                    <h3 x-text="selectedGroup === null ? 'Select a module to continue' : 'Select a category to view tasks'"></h3>
                    <p x-text="selectedGroup === null ? 'Click on any of the module boxes to see categories' : 'Click on any of the category boxes to display the corresponding information'"></p>
                </div>

                <!-- SOFTWARE MODULE CONTENT -->
                <!-- New Task -->
                <div x-show="selectedStat === 'software-new-task'" x-transition>
                    @livewire('salesperson-dashboard.software-handover-new', ['limit' => 10, 'dashboard' => true])
                </div>

                <!-- Pending Kick Off -->
                <div x-show="selectedStat === 'software-pending-kick-off'" x-transition>
                    @livewire('software-handover-kick-off-reminder', ['limit' => 10, 'dashboard' => true])
                </div>

                <!-- Pending License -->
                <div x-show="selectedStat === 'software-pending-license'" x-transition>
                    @livewire('software-handover-pending-license', ['limit' => 10, 'dashboard' => true])
                </div>

                <!-- HARDWARE MODULE CONTENT -->
                <!-- New Task -->
                <div x-show="selectedStat === 'hardware-new-task'" x-transition>
                    @livewire('salesperson-dashboard.hardware-handover-new', ['limit' => 10, 'dashboard' => true])
                </div>

                <!-- Pending Stock -->
                <div x-show="selectedStat === 'hardware-pending-stock'" x-transition>
                    @livewire('hardware-handover-pending-stock', ['limit' => 10, 'dashboard' => true])
                </div>

                <!-- Completed Migration -->
                <div x-show="selectedStat === 'hardware-completed-migration'" x-transition>
                    @livewire('hardware-handover-completed-migration', ['limit' => 10, 'dashboard' => true])
                </div>

                <!-- REPAIR MODULE CONTENT -->
                <!-- Accepted Task -->
                <div x-show="selectedStat === 'repair-accepted-task'" x-transition>
                    @livewire('admin-repair-accepted', ['limit' => 10, 'dashboard' => true])
                </div>

                <!-- Pending Confirmation -->
                <div x-show="selectedStat === 'repair-pending-confirmation'" x-transition>
                    @livewire('admin-repair-pending-confirmation', ['limit' => 10, 'dashboard' => true])
                </div>

                <!-- Completed Technician Repair -->
                <div x-show="selectedStat === 'repair-completed-tech'" x-transition>
                    @livewire('admin-repair-completed-technician', ['limit' => 10, 'dashboard' => true])
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to reset the admin dashboard
        window.resetAdminDashboard = function() {
            const container = document.getElementById('admin-dashboard-container');
            if (container && container.__x) {
                container.__x.$data.selectedGroup = 'software';
                container.__x.$data.selectedStat = 'software-new-task';
                console.log('Admin dashboard reset via global function');
            }
        };

        // Listen for our custom reset event
        window.addEventListener('reset-admin-dashboard', function() {
            window.resetAdminDashboard();
        });
    });
</script>
