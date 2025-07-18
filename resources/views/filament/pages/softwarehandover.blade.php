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

    /* GROUP COLORS */
    .group-all-items { border-top-color: #6b7280; }
    .group-new-task { border-top-color: #2563eb; }
    .group-pending-kick-off { border-top-color: #f59e0b; }
    .group-pending-license { border-top-color: #8b5cf6; }
    .group-completed { border-top-color: #10b981; }
    .group-rejected { border-top-color: #ef4444; }

    .group-all-items .group-count { color: #6b7280; }
    .group-new-task .group-count { color: #2563eb; }
    .group-pending-kick-off .group-count { color: #f59e0b; }
    .group-pending-license .group-count { color: #8b5cf6; }
    .group-completed .group-count { color: #10b981; }
    .group-rejected .group-count { color: #ef4444; }

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
    }

    @media (max-width: 768px) {
        .group-container {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 640px) {
        .group-container {
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

    // Define queries for Pending Kick Off
    $pendingKickOffCount = app(\App\Livewire\SoftwareHandoverKickOffReminder::class)
        ->getNewSoftwareHandovers()
        ->count();

    // Define queries for Pending License
    $pendingLicenseCount = app(\App\Livewire\SoftwareHandoverPendingLicense::class)
        ->getNewSoftwareHandovers()
        ->count();

    // Define queries for Completed and Draft/Rejected
    $completedCount = app(\App\Livewire\SalespersonDashboard\SoftwareHandoverCompleted::class)
        ->getNewSoftwareHandovers()
        ->count();

    $draftRejectedCount = app(\App\Livewire\SoftwareHandoverAddon::class)
        ->getNewSoftwareHandovers()
        ->count();

    // Calculate combined pending count
    $allTaskCount = $newCount + $pendingKickOffCount + $pendingLicenseCount + $completedCount + $draftRejectedCount;
@endphp

<div id="software-handover-container" class="hardware-handover-container"
     x-data="{
         selectedSection: null,

         setSelectedSection(value) {
             console.log('Setting software section to:', value);
             if (this.selectedSection === value) {
                 this.selectedSection = null;
             } else {
                 this.selectedSection = value;
             }
         },

         init() {
             console.log('Software handover Alpine component initialized');
             this.selectedSection = null;
         }
     }"
     x-init="init()">

    <!-- New container structure -->
    <div class="dashboard-layout" wire:poll.300s>
        <!-- Left sidebar with groups -->
        <div class="group-column">
            <div class="group-container">
                <!-- Group: All Task -->
                <div class="group-box group-all-items"
                     :class="{'selected': selectedSection === 'all-tasks'}">
                    <div class="group-title">All Tasks</div>
                    <div class="group-count">{{ $allTaskCount }}</div>
                </div>

                <!-- Group: New Task -->
                <div class="group-box group-new-task"
                     :class="{'selected': selectedSection === 'new'}"
                     @click="setSelectedSection('new')">
                    <div class="group-title">New Task</div>
                    <div class="group-count">{{ $newCount }}</div>
                </div>

                <!-- Group: Pending Kick Off -->
                <div class="group-box group-pending-kick-off"
                     :class="{'selected': selectedSection === 'pending-kick-off'}"
                     @click="setSelectedSection('pending-kick-off')">
                    <div class="group-title">Pending Kick Off</div>
                    <div class="group-count">{{ $pendingKickOffCount }}</div>
                </div>

                <!-- Group: Pending License -->
                <div class="group-box group-pending-license"
                     :class="{'selected': selectedSection === 'pending-license'}"
                     @click="setSelectedSection('pending-license')">
                    <div class="group-title">Pending License</div>
                    <div class="group-count">{{ $pendingLicenseCount }}</div>
                </div>

                <!-- Group: Completed -->
                <div class="group-box group-completed"
                     :class="{'selected': selectedSection === 'completed'}"
                     @click="setSelectedSection('completed')">
                    <div class="group-title">Completed</div>
                    <div class="group-count">{{ $completedCount }}</div>
                </div>

                <!-- Group: Rejected -->
                <div class="group-box group-rejected"
                     :class="{'selected': selectedSection === 'draft-rejected'}"
                     @click="setSelectedSection('draft-rejected')">
                    <div class="group-title">Rejected</div>
                    <div class="group-count">{{ $draftRejectedCount }}</div>
                </div>
            </div>
        </div>

        <!-- Right content area -->
        <div class="content-column">
            <!-- Content Area for Tables -->
            <div class="content-area">
                <!-- Display hint message when nothing is selected -->
                <div class="hint-message" x-show="selectedSection === null" x-transition>
                    <h3>Select a group to view data</h3>
                    <p>Click on any of the group boxes to display the corresponding information</p>
                </div>

                <!-- All Tasks -->
                <div x-show="selectedSection === 'all-tasks'" x-transition :key="'all-tasks'">
                    <div x-show="selectedSection === 'all-tasks' || selectedSection === null">
                        <livewire:salesperson-dashboard.software-handover-new />
                    </div>
                    <div x-show="selectedSection === 'all-tasks' || selectedSection === null">
                        @livewire('software-handover-kick-off-reminder')
                    </div>
                    <div x-show="selectedSection === 'all-tasks' || selectedSection === null">
                        @livewire('software-handover-pending-license')
                    </div>
                    <div x-show="selectedSection === 'all-tasks' || selectedSection === null">
                        <livewire:salesperson-dashboard.software-handover-completed />
                    </div>
                    <div x-show="selectedSection === 'all-tasks' || selectedSection === null">
                        @livewire('software-handover-addon')
                    </div>
                </div>

                <!-- New Task -->
                <div x-show="selectedSection === 'new'" x-transition :key="'new'">
                    <livewire:salesperson-dashboard.software-handover-new />
                </div>

                <!-- Pending Kick Off -->
                <div x-show="selectedSection === 'pending-kick-off'" x-transition :key="'kick-off'">
                    @livewire('software-handover-kick-off-reminder')
                </div>

                <!-- Pending License -->
                <div x-show="selectedSection === 'pending-license'" x-transition :key="'license'">
                    @livewire('software-handover-pending-license')
                </div>

                <!-- Completed -->
                <div x-show="selectedSection === 'completed'" x-transition :key="'completed'">
                    <livewire:salesperson-dashboard.software-handover-completed />
                </div>

                <!-- Draft/Rejected -->
                <div x-show="selectedSection === 'draft-rejected'" x-transition :key="'rejected'">
                    @livewire('software-handover-addon')
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
                container.__x.$data.selectedSection = null;
                console.log('Software handover reset via global function');
            }
        };

        // Listen for our custom reset event
        window.addEventListener('reset-software-dashboard', function() {
            window.resetSoftwareHandover();
        });
    });
</script>
