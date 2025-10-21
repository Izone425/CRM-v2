<!-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/softwarehandover.blade.php -->
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
    .group-new-task { border-top-color: #2563eb; }
    .group-pending-task { border-top-color: #f59e0b; }
    .group-completed { border-top-color: #10b981; }

    .group-all-items .group-count { color: #6b7280; }
    .group-new-task .group-count { color: #2563eb; }
    .group-pending-task .group-count { color: #f59e0b; }
    .group-completed .group-count { color: #10b981; }

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

    /* NEW COLOR CODING FOR STAT BOXES */
    .all-items { border-left: 4px solid #6b7280; }
    .all-items .stat-count { color: #6b7280; }

    .new-task { border-left: 4px solid #2563eb; }
    .new-task .stat-count { color: #2563eb; }

    .rejected { border-left: 4px solid #ef4444; }
    .rejected .stat-count { color: #ef4444; }

    .pending-kick-off { border-left: 4px solid #f59e0b; }
    .pending-kick-off .stat-count { color: #f59e0b; }

    .pending-license { border-left: 4px solid #8b5cf6; }
    .pending-license .stat-count { color: #8b5cf6; }

    .completed-task { border-left: 4px solid #10b981; }
    .completed-task .stat-count { color: #10b981; }

    /* Selected states for categories */
    .stat-box.selected.all-items { background-color: rgba(107, 114, 128, 0.05); border-left-width: 6px; }
    .stat-box.selected.new-task { background-color: rgba(37, 99, 235, 0.05); border-left-width: 6px; }
    .stat-box.selected.rejected { background-color: rgba(239, 68, 68, 0.05); border-left-width: 6px; }
    .stat-box.selected.pending-kick-off { background-color: rgba(245, 158, 11, 0.05); border-left-width: 6px; }
    .stat-box.selected.pending-license { background-color: rgba(139, 92, 246, 0.05); border-left-width: 6px; }
    .stat-box.selected.completed-task { background-color: rgba(16, 185, 129, 0.05); border-left-width: 6px; }

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
    // Calculate counts directly in the blade template
    use App\Models\SoftwareHandover;

    // Define queries for New
    $newCount = app(\App\Livewire\SalespersonDashboard\SoftwareHandoverNew::class)
        ->getNewSoftwareHandovers()
        ->count();

    // Define queries for Rejected
    $draftRejectedCount = app(\App\Livewire\SoftwareHandoverAddon::class)
        ->getNewSoftwareHandovers()
        ->count();

    // Define queries for Pending Kick Off
    $pendingKickOffCount = app(\App\Livewire\SoftwareHandoverKickOffReminder::class)
        ->getNewSoftwareHandovers()
        ->count();

    // Define queries for Pending License
    $pendingLicenseCount = app(\App\Livewire\SoftwareHandoverPendingLicense::class)
        ->getNewSoftwareHandovers()
        ->count();

    // Define queries for Completed
    $completedCount = app(\App\Livewire\SalespersonDashboard\SoftwareHandoverCompleted::class)
        ->getNewSoftwareHandovers()
        ->count();

    // Calculate combined counts for groups
    $allTaskCount = App\Models\SoftwareHandover::count();
    $newTaskGroupCount = $newCount;
    $pendingTaskGroupCount = $pendingLicenseCount;
@endphp

<div id="software-handover-container" class="hardware-handover-container"
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
                 } else if (value === 'pending-task') {
                     this.selectedStat = 'pending-license';
                 } else if (value === 'completed-task') {
                     this.selectedStat = 'completed-task';
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
             console.log('Software handover Alpine component initialized');
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
                     :class="{'selected': selectedGroup === 'all-items'}">
                    <div class="group-title">Software Handover</div>
                    <div class="group-count">{{ $allTaskCount }}</div>
                </div>

                <!-- Group: New Task (DASHBOARD 01) -->
                <div class="group-box group-new-task"
                     :class="{'selected': selectedGroup === 'new-task'}"
                     @click="setSelectedGroup('new-task')">
                    <div class="group-title">New Tasks</div>
                    <div class="group-count">{{ $newTaskGroupCount }}</div>
                </div>

                <!-- Group: Pending Task (DASHBOARD 02) -->
                <div class="group-box group-pending-task"
                     :class="{'selected': selectedGroup === 'pending-task'}"
                     @click="setSelectedGroup('pending-task')">
                    <div class="group-title">Pending Tasks</div>
                    <div class="group-count">{{ $pendingTaskGroupCount }}</div>
                </div>

                <!-- Group: Completed Task (DASHBOARD 03) -->
                <div class="group-box group-completed"
                     :class="{'selected': selectedGroup === 'completed-task'}"
                     @click="setSelectedGroup('completed-task')">
                    <div class="group-title">Completed Tasks</div>
                    <div class="group-count">{{ $completedCount }}</div>
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
                        <div class="stat-label">All Tasks</div>
                    </div>
                    <div class="stat-count">{{ $allTaskCount }}</div>
                </div>
            </div>

            <!-- NEW TASK Categories (DASHBOARD 01) -->
            <div class="category-container" x-show="selectedGroup === 'new-task'">
                <div class="stat-box new-task"
                     :class="{'selected': selectedStat === 'new-task'}"
                     @click="setSelectedStat('new-task')">
                    <div class="stat-info">
                        <div class="stat-label">New Task</div>
                    </div>
                    <div class="stat-count">{{ $newCount }}</div>
                </div>

                <div class="stat-box rejected"
                     :class="{'selected': selectedStat === 'rejected'}"
                     @click="setSelectedStat('rejected')">
                    <div class="stat-info">
                        <div class="stat-label">Rejected</div>
                    </div>
                    <div class="stat-count">{{ $draftRejectedCount }}</div>
                </div>
            </div>

            <!-- PENDING TASK Categories (DASHBOARD 02) -->
            <div class="category-container" x-show="selectedGroup === 'pending-task'">
                <!-- <div class="stat-box pending-kick-off"
                     :class="{'selected': selectedStat === 'pending-kick-off'}"
                     @click="setSelectedStat('pending-kick-off')">
                    <div class="stat-info">
                        <div class="stat-label">Pending Kick Off</div>
                    </div>
                    <div class="stat-count">{{ $pendingKickOffCount }}</div>
                </div> -->

                <div class="stat-box pending-license"
                     :class="{'selected': selectedStat === 'pending-license'}"
                     @click="setSelectedStat('pending-license')">
                    <div class="stat-info">
                        <div class="stat-label">Pending License</div>
                    </div>
                    <div class="stat-count">{{ $pendingLicenseCount }}</div>
                </div>
            </div>

            <!-- COMPLETED TASK Categories (DASHBOARD 03) -->
            <div class="category-container" x-show="selectedGroup === 'completed-task'">
                <div class="stat-box completed-task"
                     :class="{'selected': selectedStat === 'completed-task'}"
                     @click="setSelectedStat('completed-task')">
                    <div class="stat-info">
                        <div class="stat-label">Completed Task</div>
                    </div>
                    <div class="stat-count">{{ $completedCount }}</div>
                </div>
            </div>
            <br>
            <!-- Content Area for Tables -->
            <div class="content-area">
                <!-- Display hint message when nothing is selected -->
                <div class="hint-message" x-show="selectedGroup === null || selectedStat === null" x-transition>
                    <h3 x-text="selectedGroup === null ? 'Select a dashboard to continue' : 'Select a category to view data'"></h3>
                    <p x-text="selectedGroup === null ? 'Click on any of the dashboard boxes to see categories' : 'Click on any of the category boxes to display the corresponding information'"></p>
                </div>

                <!-- All Items -->
                <div x-show="selectedStat === 'all-items'" x-transition>
                    <div>
                        <livewire:salesperson-dashboard.software-handover-new />
                    </div>
                    <div>
                        @livewire('software-handover-kick-off-reminder')
                    </div>
                    <div>
                        @livewire('software-handover-pending-license')
                    </div>
                    <div>
                        <livewire:salesperson-dashboard.software-handover-completed />
                    </div>
                    <div>
                        @livewire('software-handover-addon')
                    </div>
                </div>

                <!-- New Task (from DASHBOARD 01) -->
                <div x-show="selectedStat === 'new-task'" x-transition>
                    <livewire:salesperson-dashboard.software-handover-new />
                </div>

                <!-- Rejected (from DASHBOARD 01) -->
                <div x-show="selectedStat === 'rejected'" x-transition>
                    @livewire('software-handover-addon')
                </div>

                <!-- Pending Kick Off (from DASHBOARD 02) -->
                <div x-show="selectedStat === 'pending-kick-off'" x-transition>
                    @livewire('software-handover-kick-off-reminder')
                </div>

                <!-- Pending License (from DASHBOARD 02) -->
                <div x-show="selectedStat === 'pending-license'" x-transition>
                    @livewire('software-handover-pending-license')
                </div>

                <!-- Completed (from DASHBOARD 03) -->
                <div x-show="selectedStat === 'completed-task'" x-transition>
                    <livewire:salesperson-dashboard.software-handover-completed />
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // When the page loads, setup handlers for this component
    document.addEventListener('DOMContentLoaded', function() {
        // Function to reset the software component
        window.resetSoftwareHandover = function() {
            const container = document.getElementById('software-handover-container');
            if (container && container.__x) {
                container.__x.$data.selectedGroup = null;
                container.__x.$data.selectedStat = null;
                console.log('Software handover reset via global function');
            }
        };

        // Listen for our custom reset event
        window.addEventListener('reset-software-dashboard', function() {
            window.resetSoftwareHandover();
        });
    });
</script>
