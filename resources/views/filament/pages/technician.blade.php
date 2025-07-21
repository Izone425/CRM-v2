<!-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/technician.blade.php -->
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
    .group-new { border-top-color: #2563eb; }
    .group-accepted { border-top-color: #f59e0b; }
    .group-pending-confirmation { border-top-color: #8b5cf6; }
    .group-pending-onsite { border-top-color: #ec4899; }
    .group-completed { border-top-color: #10b981; }
    .group-inactive { border-top-color: #ef4444; }

    .group-new .group-count { color: #2563eb; }
    .group-accepted .group-count { color: #f59e0b; }
    .group-pending-confirmation .group-count { color: #8b5cf6; }
    .group-pending-onsite .group-count { color: #ec4899; }
    .group-completed .group-count { color: #10b981; }
    .group-inactive .group-count { color: #ef4444; }

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
    use App\Models\AdminRepair;

    // Define queries for New
    $newCount = app(\App\Livewire\TechnicianNew::class)
        ->getTableQuery()
        ->count();

    // Define queries for Pending Kick Off
    $repairAccepted = app(\App\Livewire\TechnicianAccepted::class)
        ->getTableQuery()
        ->count();

    $repairPendingConfirmation = app(\App\Livewire\TechnicianPendingConfirmation::class)
        ->getTableQuery()
        ->count();

    $repairPendingOnsiteRepair = app(\App\Livewire\TechnicianPendingOnsiteRepair::class)
        ->getTableQuery()
        ->count();

    // Define queries for Completed and Draft/Rejected
    $completedCount = app(\App\Livewire\AdminRepairCompletedTechnician::class)
        ->getTableQuery()
        ->count();

    $inactiveCount = app(\App\Livewire\AdminRepairInactive::class)
        ->getTableQuery()
        ->count();

    // Calculate all tasks count
    $allTaskCount = $newCount + $repairAccepted + $repairPendingConfirmation + $repairPendingOnsiteRepair + $completedCount + $inactiveCount;
@endphp

<div id="technician-container" class="hardware-handover-container"
     x-data="{
         selectedSection: null,

         setSelectedSection(value) {
             console.log('Setting technician section to:', value);
             if (this.selectedSection === value) {
                 this.selectedSection = null;
             } else {
                 this.selectedSection = value;
             }
         },

         init() {
             console.log('Technician dashboard Alpine component initialized');
             this.selectedSection = null;
         }
     }"
     x-init="init()">

    <!-- New container structure -->
    <div class="dashboard-layout" wire:poll.300s>
        <!-- Left sidebar with groups -->
        <div class="group-column">
            <div class="group-container">
                <!-- Group: New Task -->
                <div class="group-box group-new"
                     :class="{'selected': selectedSection === 'new'}"
                     @click="setSelectedSection('new')">
                    <div class="group-title">New Task</div>
                    <div class="group-count">{{ $newCount }}</div>
                </div>

                <!-- Group: Accepted Task -->
                <div class="group-box group-accepted"
                     :class="{'selected': selectedSection === 'accepted'}"
                     @click="setSelectedSection('accepted')">
                    <div class="group-title">Accepted Task</div>
                    <div class="group-count">{{ $repairAccepted }}</div>
                </div>

                <!-- Group: Pending Confirmation -->
                <div class="group-box group-pending-confirmation"
                     :class="{'selected': selectedSection === 'pending_confirmation'}"
                     @click="setSelectedSection('pending_confirmation')">
                    <div class="group-title">Pending Confirmation</div>
                    <div class="group-count">{{ $repairPendingConfirmation }}</div>
                </div>

                <!-- Group: Pending Onsite -->
                <div class="group-box group-pending-onsite"
                     :class="{'selected': selectedSection === 'pending_onsite_repair'}"
                     @click="setSelectedSection('pending_onsite_repair')">
                    <div class="group-title">Pending Onsite</div>
                    <div class="group-count">{{ $repairPendingOnsiteRepair }}</div>
                </div>

                <!-- Group: Completed -->
                <div class="group-box group-completed"
                     :class="{'selected': selectedSection === 'completed'}"
                     @click="setSelectedSection('completed')">
                    <div class="group-title">Completed</div>
                    <div class="group-count">{{ $completedCount }}</div>
                </div>

                <!-- Group: Inactive -->
                <div class="group-box group-inactive"
                     :class="{'selected': selectedSection === 'inactive'}"
                     @click="setSelectedSection('inactive')">
                    <div class="group-title">Inactive</div>
                    <div class="group-count">{{ $inactiveCount }}</div>
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

                <!-- New Task -->
                <div x-show="selectedSection === 'new'" x-transition :key="'new'">
                    @livewire('technician-new')
                </div>

                <!-- Accepted Task -->
                <div x-show="selectedSection === 'accepted'" x-transition :key="'accepted'">
                    @livewire('technician-accepted')
                </div>

                <!-- Pending Confirmation -->
                <div x-show="selectedSection === 'pending_confirmation'" x-transition :key="'pending-confirmation'">
                    @livewire('technician-pending-confirmation')
                </div>

                <!-- Pending Onsite Repair -->
                <div x-show="selectedSection === 'pending_onsite_repair'" x-transition :key="'pending-onsite'">
                    @livewire('technician-pending-onsite-repair')
                </div>

                <!-- Completed -->
                <div x-show="selectedSection === 'completed'" x-transition :key="'completed'">
                    @livewire('admin-repair-completed-technician')
                </div>

                <!-- Inactive -->
                <div x-show="selectedSection === 'inactive'" x-transition :key="'inactive'">
                    @livewire('admin-repair-inactive')
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // When the page loads, setup handlers for this component
    document.addEventListener('DOMContentLoaded', function() {
        // Function to reset the technician component
        window.resetTechnicianDashboard = function() {
            const container = document.getElementById('technician-container');
            if (container && container.__x) {
                container.__x.$data.selectedSection = null;
                console.log('Technician dashboard reset via global function');
            }
        };

        // Listen for our custom reset event
        window.addEventListener('reset-technician-dashboard', function() {
            window.resetTechnicianDashboard();
        });
    });
</script>
