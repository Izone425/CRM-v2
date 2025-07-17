<!-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/hardwarehandover.blade.php -->
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
        width: 220px;
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
        min-height: 82px;
        max-height: 95px;
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
    .group-all-items { border-top-color: #6b7280; }
    .group-new-task { border-top-color: #2563eb; }
    .group-stock-status { border-top-color: #f59e0b; }
    .group-migration-status { border-top-color: #8b5cf6; }
    .group-completed-status { border-top-color: #10b981; }

    .group-all-items .group-count { color: #6b7280; }
    .group-new-task .group-count { color: #2563eb; }
    .group-stock-status .group-count { color: #f59e0b; }
    .group-migration-status .group-count { color: #8b5cf6; }
    .group-completed-status .group-count { color: #10b981; }

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
    .all-items { border-left: 4px solid #6b7280; }
    .all-items .stat-count { color: #6b7280; }

    .new-task { border-left: 4px solid #2563eb; }
    .new-task .stat-count { color: #2563eb; }

    .rejected { border-left: 4px solid #ef4444; }
    .rejected .stat-count { color: #ef4444; }

    .pending-stock { border-left: 4px solid #f59e0b; }
    .pending-stock .stat-count { color: #f59e0b; }

    .pending-migration { border-left: 4px solid #8b5cf6; }
    .pending-migration .stat-count { color: #8b5cf6; }

    .completed-migration { border-left: 4px solid #10b981; }
    .completed-migration .stat-count { color: #10b981; }

    .completed-installation { border-left: 4px solid #16a34a; }
    .completed-installation .stat-count { color: #16a34a; }

    .completed-courier { border-left: 4px solid #0891b2; }
    .completed-courier .stat-count { color: #0891b2; }

    /* Selected states for categories */
    .stat-box.selected.all-items { background-color: rgba(107, 114, 128, 0.05); border-left-width: 6px; }
    .stat-box.selected.new-task { background-color: rgba(37, 99, 235, 0.05); border-left-width: 6px; }
    .stat-box.selected.rejected { background-color: rgba(239, 68, 68, 0.05); border-left-width: 6px; }
    .stat-box.selected.pending-stock { background-color: rgba(245, 158, 11, 0.05); border-left-width: 6px; }
    .stat-box.selected.pending-migration { background-color: rgba(139, 92, 246, 0.05); border-left-width: 6px; }
    .stat-box.selected.completed-migration { background-color: rgba(16, 185, 129, 0.05); border-left-width: 6px; }
    .stat-box.selected.completed-installation { background-color: rgba(22, 163, 74, 0.05); border-left-width: 6px; }
    .stat-box.selected.completed-courier { background-color: rgba(8, 145, 178, 0.05); border-left-width: 6px; }

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
    // Calculate counts directly in the blade template
    use App\Models\HardwareHandover;

    // Define queries for different statuses
    $newTaskCount = app(\App\Livewire\SalespersonDashboard\HardwareHandoverNew::class)
        ->getNewHardwareHandovers()
        ->count();

    $rejectedCount = app(\App\Livewire\HardwareHandoverAddon::class)
        ->getNewHardwareHandovers()
        ->count();

    $pendingStockCount = app(\App\Livewire\HardwareHandoverPendingStock::class)
        ->getOverdueHardwareHandovers()
        ->count();

    $pendingMigrationCount = app(\App\Livewire\HardwareHandoverPendingMigration::class)
        ->getOverdueHardwareHandovers()
        ->count();

    $completedMigrationCount = app(\App\Livewire\HardwareHandoverCompletedMigration::class)
        ->getOverdueHardwareHandovers()
        ->count();

    $completedInstallationCount = app(\App\Livewire\HardwareHandoverCompletedInstallation::class)
        ->getOverdueHardwareHandovers()
        ->count();

    $completedCourierCount = app(\App\Livewire\HardwareHandoverCompletedCourier::class)
        ->getOverdueHardwareHandovers()
        ->count();

    // Calculate combined counts
    $allItemsCount = $newTaskCount + $rejectedCount + $pendingStockCount + $pendingMigrationCount +
                   $completedMigrationCount + $completedInstallationCount + $completedCourierCount;

    $newTaskGroupCount = $newTaskCount + $rejectedCount;
    $stockStatusCount = $pendingStockCount;
    $migrationStatusCount = $pendingMigrationCount + $completedMigrationCount;
    $completedStatusCount = $completedInstallationCount + $completedCourierCount;
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
                 if (value === 'all-items') {
                     this.selectedStat = 'all-items';
                 } else if (value === 'new-task') {
                     this.selectedStat = 'new-task';
                 } else if (value === 'stock-status') {
                     this.selectedStat = 'pending-stock';
                 } else if (value === 'migration-status') {
                     this.selectedStat = 'pending-migration';
                 } else if (value === 'completed-status') {
                     this.selectedStat = 'completed-installation';
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
             console.log('Hardware handover Alpine component initialized');
         }
     }"
     x-init="init()">

    <!-- New container structure -->
    <div class="dashboard-layout" wire:poll.300s>
        <!-- Left sidebar with groups -->
        <div class="group-column">
            <div class="group-container">
                <!-- Group: All Items -->
                <div class="group-box group-all-items"
                     :class="{'selected': selectedGroup === 'all-items'}"
                     @click="setSelectedGroup('all-items')">
                    <div class="group-title">All Items</div>
                    <div class="group-count">{{ $allItemsCount }}</div>
                </div>

                <!-- Group: New Task -->
                <div class="group-box group-new-task"
                     :class="{'selected': selectedGroup === 'new-task'}"
                     @click="setSelectedGroup('new-task')">
                    <div class="group-title">New Task</div>
                    <div class="group-count">{{ $newTaskGroupCount }}</div>
                </div>

                <!-- Group: Stock Status -->
                <div class="group-box group-stock-status"
                     :class="{'selected': selectedGroup === 'stock-status'}"
                     @click="setSelectedGroup('stock-status')">
                    <div class="group-title">Stock Status</div>
                    <div class="group-count">{{ $stockStatusCount }}</div>
                </div>

                <!-- Group: Migration Status -->
                <div class="group-box group-migration-status"
                     :class="{'selected': selectedGroup === 'migration-status'}"
                     @click="setSelectedGroup('migration-status')">
                    <div class="group-title">Migration Status</div>
                    <div class="group-count">{{ $migrationStatusCount }}</div>
                </div>

                <!-- Group: Completed Status -->
                <div class="group-box group-completed-status"
                     :class="{'selected': selectedGroup === 'completed-status'}"
                     @click="setSelectedGroup('completed-status')">
                    <div class="group-title">Completed Status</div>
                    <div class="group-count">{{ $completedStatusCount }}</div>
                </div>
            </div>
        </div>

        <!-- Right content area -->
        <div class="content-column">
            <!-- ALL ITEMS Categories -->
            <div class="category-container" x-show="selectedGroup === 'all-items'">
                <div class="stat-box all-items"
                     :class="{'selected': selectedStat === 'all-items'}"
                     @click="setSelectedStat('all-items')">
                    <div class="stat-info">
                        <div class="stat-label">All Items</div>
                    </div>
                    <div class="stat-count">{{ $allItemsCount }}</div>
                </div>
            </div>

            <!-- NEW TASK Categories -->
            <div class="category-container" x-show="selectedGroup === 'new-task'">
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
            </div>

            <!-- STOCK STATUS Categories -->
            <div class="category-container" x-show="selectedGroup === 'stock-status'">
                <div class="stat-box pending-stock"
                     :class="{'selected': selectedStat === 'pending-stock'}"
                     @click="setSelectedStat('pending-stock')">
                    <div class="stat-info">
                        <div class="stat-label">Pending Stock</div>
                    </div>
                    <div class="stat-count">{{ $pendingStockCount }}</div>
                </div>
            </div>

            <!-- MIGRATION STATUS Categories -->
            <div class="category-container" x-show="selectedGroup === 'migration-status'">
                <div class="stat-box pending-migration"
                     :class="{'selected': selectedStat === 'pending-migration'}"
                     @click="setSelectedStat('pending-migration')">
                    <div class="stat-info">
                        <div class="stat-label">Pending Migration</div>
                    </div>
                    <div class="stat-count">{{ $pendingMigrationCount }}</div>
                </div>

                <div class="stat-box completed-migration"
                     :class="{'selected': selectedStat === 'completed-migration'}"
                     @click="setSelectedStat('completed-migration')">
                    <div class="stat-info">
                        <div class="stat-label">Completed Migration</div>
                    </div>
                    <div class="stat-count">{{ $completedMigrationCount }}</div>
                </div>
            </div>

            <!-- COMPLETED STATUS Categories -->
            <div class="category-container" x-show="selectedGroup === 'completed-status'">
                <div class="stat-box completed-installation"
                     :class="{'selected': selectedStat === 'completed-installation'}"
                     @click="setSelectedStat('completed-installation')">
                    <div class="stat-info">
                        <div class="stat-label">Completed: Installation</div>
                    </div>
                    <div class="stat-count">{{ $completedInstallationCount }}</div>
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

            <!-- Content Area for Tables -->
            <div class="content-area">
                <!-- Display hint message when nothing is selected -->
                <div class="hint-message" x-show="selectedGroup === null || selectedStat === null" x-transition>
                    <h3 x-text="selectedGroup === null ? 'Select a group to continue' : 'Select a category to view data'"></h3>
                    <p x-text="selectedGroup === null ? 'Click on any of the group boxes to see categories' : 'Click on any of the category boxes to display the corresponding information'"></p>
                </div>

                <!-- All Items -->
                <div x-show="selectedStat === 'all-items'" x-transition>
                    <livewire:hardware-handover-all />
                </div>

                <!-- New Task -->
                <div x-show="selectedStat === 'new-task'" x-transition>
                    <livewire:salesperson-dashboard.hardware-handover-new />
                </div>

                <!-- Rejected -->
                <div x-show="selectedStat === 'rejected'" x-transition>
                    <livewire:hardware-handover-addon />
                </div>

                <!-- Pending Stock -->
                <div x-show="selectedStat === 'pending-stock'" x-transition>
                    <livewire:hardware-handover-pending-stock />
                </div>

                <!-- Pending Migration -->
                <div x-show="selectedStat === 'pending-migration'" x-transition>
                    <livewire:hardware-handover-pending-migration />
                </div>

                <!-- Completed Migration -->
                <div x-show="selectedStat === 'completed-migration'" x-transition>
                    <livewire:hardware-handover-completed-migration />
                </div>

                <!-- Completed Installation -->
                <div x-show="selectedStat === 'completed-installation'" x-transition>
                    <livewire:hardware-handover-completed-installation />
                </div>

                <!-- Completed Courier -->
                <div x-show="selectedStat === 'completed-courier'" x-transition>
                    <livewire:hardware-handover-completed-courier />
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // When the page loads, setup handlers for this component
    document.addEventListener('DOMContentLoaded', function() {
        // Function to reset the hardware component
        window.resetHardwareHandover = function() {
            const container = document.getElementById('hardware-handover-container');
            if (container && container.__x) {
                container.__x.$data.selectedGroup = null;
                container.__x.$data.selectedStat = null;
                console.log('Hardware handover reset via global function');
            }
        };

        // Listen for our custom reset event
        window.addEventListener('reset-hardware-dashboard', function() {
            window.resetHardwareHandover();
        });
    });
</script>
