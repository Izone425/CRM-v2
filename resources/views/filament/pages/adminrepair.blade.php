<!-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/adminrepair.blade.php -->
<style>
    /* Container styling */
    .admin-repair-container {
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
    .group-all-items { border-top-color: #06b6d4; }
    .group-new-task { border-top-color: #2563eb; }
    .group-task-status { border-top-color: #f59e0b; }
    .group-pending-status { border-top-color: #8b5cf6; }
    .group-completed-status { border-top-color: #10b981; }

    .group-all-items .group-count { color: #06b6d4; }
    .group-new-task .group-count { color: #2563eb; }
    .group-task-status .group-count { color: #f59e0b; }
    .group-pending-status .group-count { color: #8b5cf6; }
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
    .stat-all-items { border-left: 4px solid #06b6d4; }
    .stat-all-items .stat-count { color: #06b6d4; }

    .stat-new { border-left: 4px solid #2563eb; }
    .stat-new .stat-count { color: #2563eb; }

    .stat-inactive { border-left: 4px solid #ef4444; }
    .stat-inactive .stat-count { color: #ef4444; }

    .stat-accepted { border-left: 4px solid #f59e0b; }
    .stat-accepted .stat-count { color: #f59e0b; }

    .stat-pending-confirmation { border-left: 4px solid #8b5cf6; }
    .stat-pending-confirmation .stat-count { color: #8b5cf6; }

    .stat-pending-onsite { border-left: 4px solid #ec4899; }
    .stat-pending-onsite .stat-count { color: #ec4899; }

    .stat-completed-tech { border-left: 4px solid #10b981; }
    .stat-completed-tech .stat-count { color: #10b981; }

    .stat-completed-admin { border-left: 4px solid #059669; }
    .stat-completed-admin .stat-count { color: #059669; }

    /* Selected states for categories */
    .stat-box.selected.stat-all-items { background-color: rgba(37, 99, 235, 0.05); border-left-width: 6px; }
    .stat-box.selected.stat-new { background-color: rgba(37, 99, 235, 0.05); border-left-width: 6px; }
    .stat-box.selected.stat-inactive { background-color: rgba(239, 68, 68, 0.05); border-left-width: 6px; }
    .stat-box.selected.stat-accepted { background-color: rgba(245, 158, 11, 0.05); border-left-width: 6px; }
    .stat-box.selected.stat-pending-confirmation { background-color: rgba(139, 92, 246, 0.05); border-left-width: 6px; }
    .stat-box.selected.stat-pending-onsite { background-color: rgba(236, 72, 153, 0.05); border-left-width: 6px; }
    .stat-box.selected.stat-completed-tech { background-color: rgba(16, 185, 129, 0.05); border-left-width: 6px; }
    .stat-box.selected.stat-completed-admin { background-color: rgba(5, 150, 105, 0.05); border-left-width: 6px; }

    /* Table grid styling */
    .table-grid-container {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 20px;
    }

    .table-grid-item {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        overflow: hidden;
    }

    .table-grid-item .fi-ta {
        margin: 0;
        height: 100%;
    }

    .table-grid-item .fi-ta-header {
        padding: 0.5rem !important;
    }

    /* Animation for tab switching */
    [x-transition] {
        transition: all 0.2s ease-out;
    }

    /* Data requirements box styling */
    .data-requirements {
        background-color: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        padding: 10px 15px;
        margin-bottom: 15px;
        font-size: 0.875rem;
    }

    .requirements-title {
        font-weight: 600;
        margin-bottom: 8px;
        color: #4b5563;
    }

    .requirements-list {
        list-style-type: disc;
        margin-left: 20px;
        color: #4b5563;
    }

    .requirements-list li {
        margin-bottom: 4px;
    }

    /* Responsive adjustments */
    @media (max-width: 1024px) {
        .dashboard-layout {
            grid-template-columns: 100%;
            grid-template-rows: auto auto;
        }

        .group-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            border-right: none;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .category-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
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
    }

    @media (max-width: 640px) {
        .group-container,
        .category-container,
        .table-grid-container {
            grid-template-columns: 1fr;
        }
    }
</style>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

@php
    // Calculate counts directly in the blade template
    use App\Models\AdminRepair;

    // Define queries for New
    $newCount = app(\App\Livewire\AdminRepairNew::class)
        ->getTableQuery()
        ->count();

    // Define queries for Inactive
    $inactiveCount = app(\App\Livewire\AdminRepairInactive::class)
        ->getTableQuery()
        ->count();

    // Define queries for Accepted
    $acceptedCount = app(\App\Livewire\AdminRepairAccepted::class)
        ->getTableQuery()
        ->count();

    // Define queries for Pending Confirmation
    $pendingConfirmationCount = app(\App\Livewire\AdminRepairPendingConfirmation::class)
        ->getTableQuery()
        ->count();

    // Define queries for Pending Onsite Repair
    $pendingOnsiteCount = app(\App\Livewire\AdminRepairPendingOnsiteRepair::class)
        ->getTableQuery()
        ->count();

    // Define queries for Completed (tech and admin)
    $completedTechnicianCount = app(\App\Livewire\AdminRepairCompletedTechnician::class)
        ->getTableQuery()
        ->count();

    $completedAdminCount = app(\App\Livewire\AdminRepairCompletedAdmin::class)
        ->getTableQuery()
        ->count();

    $completedCount = $completedTechnicianCount + $completedAdminCount;

    // Calculate all tasks count
    $allTaskCount = $newCount + $inactiveCount + $acceptedCount + $pendingConfirmationCount +
                    $pendingOnsiteCount + $completedTechnicianCount + $completedAdminCount;

    // Calculate pending status count
    $pendingStatusCount = $pendingConfirmationCount + $pendingOnsiteCount;
@endphp

<div id="admin-repair-container" class="admin-repair-container"
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
                     this.selectedStat = 'new';
                 } else if (value === 'task-status') {
                     this.selectedStat = 'accepted';
                 } else if (value === 'pending-status') {
                     this.selectedStat = 'pending-confirmation';
                 } else if (value === 'completed-status') {
                     this.selectedStat = 'completed-tech';
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
         }
     }">

    <div class="dashboard-layout" wire:poll.300s>
        <div class="group-column">
            <div class="group-container">
                <!-- Group: All Items -->
                <div class="group-box group-all-items"
                     :class="{'selected': selectedGroup === 'all-items'}"
                     @click="setSelectedGroup('all-items')">
                    <div class="group-title">All Items</div>
                    <div class="group-count">{{ $allTaskCount }}</div>
                </div>

                <!-- Group: New Task -->
                <div class="group-box group-new-task"
                     :class="{'selected': selectedGroup === 'new-task'}"
                     @click="setSelectedGroup('new-task')">
                    <div class="group-title">New Task</div>
                    <div class="group-count">{{ $newCount + $inactiveCount }}</div>
                </div>

                <!-- Group: Task Status -->
                <div class="group-box group-task-status"
                     :class="{'selected': selectedGroup === 'task-status'}"
                     @click="setSelectedGroup('task-status')">
                    <div class="group-title">Task Status</div>
                    <div class="group-count">{{ $acceptedCount }}</div>
                </div>

                <!-- Group: Pending Status -->
                <div class="group-box group-pending-status"
                     :class="{'selected': selectedGroup === 'pending-status'}"
                     @click="setSelectedGroup('pending-status')">
                    <div class="group-title">Pending Status</div>
                    <div class="group-count">{{ $pendingStatusCount }}</div>
                </div>

                <!-- Group: Completed Status -->
                <div class="group-box group-completed-status"
                     :class="{'selected': selectedGroup === 'completed-status'}"
                     @click="setSelectedGroup('completed-status')">
                    <div class="group-title">Completed Status</div>
                    <div class="group-count">{{ $completedCount }}</div>
                </div>
            </div>
        </div>

        <div class="content-column">
            <!--All Tasks-->
            <div class="category-container" x-show="selectedGroup === 'all-items'">
                <div class="stat-box stat-all-items"
                     :class="{'selected': selectedStat === 'all-items'}"
                     @click="setSelectedStat('all-items')">
                    <div class="stat-info">
                        <div class="stat-label">All Tasks</div>
                    </div>
                    <div class="stat-count">{{ $allTaskCount }}</div>
                </div>
            </div>

            <!-- New Task Categories -->
            <div class="category-container" x-show="selectedGroup === 'new-task'">
                <div class="stat-box stat-new"
                     :class="{'selected': selectedStat === 'new'}"
                     @click="setSelectedStat('new')">
                    <div class="stat-info">
                        <div class="stat-label">New Task</div>
                    </div>
                    <div class="stat-count">{{ $newCount }}</div>
                </div>
                <div class="stat-box stat-inactive"
                     :class="{'selected': selectedStat === 'inactive'}"
                     @click="setSelectedStat('inactive')">
                    <div class="stat-info">
                        <div class="stat-label">Inactive</div>
                    </div>
                    <div class="stat-count">{{ $inactiveCount }}</div>
                </div>
            </div>

            <!-- Task Status Categories -->
            <div class="category-container" x-show="selectedGroup === 'task-status'">
                <div class="stat-box stat-accepted"
                     :class="{'selected': selectedStat === 'accepted'}"
                     @click="setSelectedStat('accepted')">
                    <div class="stat-info">
                        <div class="stat-label">Accepted</div>
                    </div>
                    <div class="stat-count">{{ $acceptedCount }}</div>
                </div>
            </div>

            <!-- Pending Status Categories -->
            <div class="category-container" x-show="selectedGroup === 'pending-status'">
                <div class="stat-box stat-pending-confirmation"
                     :class="{'selected': selectedStat === 'pending-confirmation'}"
                     @click="setSelectedStat('pending-confirmation')">
                    <div class="stat-info">
                        <div class="stat-label">Pending Confirmation</div>
                    </div>
                    <div class="stat-count">{{ $pendingConfirmationCount }}</div>
                </div>
                <div class="stat-box stat-pending-onsite"
                     :class="{'selected': selectedStat === 'pending-onsite'}"
                     @click="setSelectedStat('pending-onsite')">
                    <div class="stat-info">
                        <div class="stat-label">Pending Onsite Repair</div>
                    </div>
                    <div class="stat-count">{{ $pendingOnsiteCount }}</div>
                </div>
            </div>

            <!-- Completed Status Categories -->
            <div class="category-container" x-show="selectedGroup === 'completed-status'">
                <div class="stat-box stat-completed-tech"
                     :class="{'selected': selectedStat === 'completed-tech'}"
                     @click="setSelectedStat('completed-tech')">
                    <div class="stat-info">
                        <div class="stat-label">Completed Technician Repair</div>
                    </div>
                    <div class="stat-count">{{ $completedTechnicianCount }}</div>
                </div>
                <div class="stat-box stat-completed-admin"
                     :class="{'selected': selectedStat === 'completed-admin'}"
                     @click="setSelectedStat('completed-admin')">
                    <div class="stat-info">
                        <div class="stat-label">Completed Admin Repair</div>
                    </div>
                    <div class="stat-count">{{ $completedAdminCount }}</div>
                </div>
            </div>

            <br>
            <div class="content-area">
                <!-- Display hint message when nothing is selected -->
                <div class="hint-message" x-show="selectedGroup === null || selectedStat === null" x-transition>
                    <h3 x-text="selectedGroup === null ? 'Select a group to continue' : 'Select a category to view repairs'"></h3>
                    <p x-text="selectedGroup === null ? 'Click on any of the group boxes to see categories' : 'Click on any of the category boxes to display the corresponding information'"></p>
                </div>

                <!-- All Items -->
                <div x-show="selectedGroup === 'all-items'" x-transition>
                    <livewire:admin-repair-all />
                </div>

                <!-- New Task Tables -->
                <div x-show="selectedStat === 'new'" x-transition>
                    <livewire:admin-repair-new />
                </div>

                <div x-show="selectedStat === 'inactive'" x-transition>
                    <livewire:admin-repair-inactive />
                </div>

                <!-- Task Status Tables -->
                <div x-show="selectedStat === 'accepted'" x-transition>
                    <livewire:admin-repair-accepted />
                </div>

                <!-- Pending Status Tables -->
                <div x-show="selectedStat === 'pending-confirmation'" x-transition>
                    <livewire:admin-repair-pending-confirmation />
                </div>

                <div x-show="selectedStat === 'pending-onsite'" x-transition>
                    <livewire:admin-repair-pending-onsite-repair />
                </div>

                <!-- Completed Status Tables -->
                <div x-show="selectedStat === 'completed-tech'" x-transition>
                    <livewire:admin-repair-completed-technician />
                </div>

                <div x-show="selectedStat === 'completed-admin'" x-transition>
                    <livewire:admin-repair-completed-admin />
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to reset the admin repair component
        window.resetAdminRepairDashboard = function() {
            const container = document.getElementById('admin-repair-container');
            if (container && container.__x) {
                container.__x.$data.selectedGroup = null;
                container.__x.$data.selectedStat = null;
                console.log('Admin repair dashboard reset via global function');
            }
        };

        // Listen for our custom reset event
        window.addEventListener('reset-admin-repair-dashboard', function() {
            window.resetAdminRepairDashboard();
        });
    });
</script>
