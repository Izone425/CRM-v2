{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/adminhrdfattlog.blade.php --}}
<style>
    /* Container styling */
    .hrdf-att-log-container {
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

    .group-desc {
        font-size: 12px;
        color: #6b7280;
    }

    .group-count {
        font-size: 24px;
        font-weight: bold;
    }

    /* HRDF ATT LOG ALL GROUP COLORS - Indigo Theme */
    .group-hrdf-att-log-all { border-top-color: #6366f1; }
    .group-hrdf-att-log-all .group-count { color: #6366f1; }
    .group-hrdf-att-log-all.selected { background-color: rgba(99, 102, 241, 0.05); }

    /* HRDF ATT LOG NEW GROUP COLORS - Blue Theme */
    .group-hrdf-att-log-new { border-top-color: #3b82f6; }
    .group-hrdf-att-log-new .group-count { color: #3b82f6; }
    .group-hrdf-att-log-new.selected { background-color: rgba(59, 130, 246, 0.05); }

    /* HRDF ATT LOG IN PROGRESS GROUP COLORS - Yellow Theme */
    .group-hrdf-att-log-progress { border-top-color: #f59e0b; }
    .group-hrdf-att-log-progress .group-count { color: #f59e0b; }
    .group-hrdf-att-log-progress.selected { background-color: rgba(245, 158, 11, 0.05); }

    /* HRDF ATT LOG COMPLETED GROUP COLORS - Green Theme */
    .group-hrdf-att-log-completed { border-top-color: #10b981; }
    .group-hrdf-att-log-completed .group-count { color: #10b981; }
    .group-hrdf-att-log-completed.selected { background-color: rgba(16, 185, 129, 0.05); }

    /* HRDF ATT LOG CANCELLED GROUP COLORS - Red Theme */
    .group-hrdf-att-log-cancelled { border-top-color: #ef4444; }
    .group-hrdf-att-log-cancelled .group-count { color: #ef4444; }
    .group-hrdf-att-log-cancelled.selected { background-color: rgba(239, 68, 68, 0.05); }

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
    @media (max-width: 1024px) {
        .dashboard-layout {
            grid-template-columns: 100%;
            grid-template-rows: auto auto;
        }

        .group-column {
            width: 100%;
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
    }

    @media (max-width: 768px) {
        .group-container {
            grid-template-columns: repeat(2, 1fr);
        }

        .group-box:hover {
            transform: none;
        }

        .group-box.selected {
            transform: none;
        }
    }

    @media (max-width: 640px) {
        .group-container {
            grid-template-columns: 1fr;
        }
    }
</style>

@php
    // HRDF Attendance Log Counts
    $hrdfAttLogAllCount = \App\Models\HrdfAttendanceLog::count();

    $hrdfAttLogNewCount = \App\Models\HrdfAttendanceLog::where('status', 'new')->count();

    $hrdfAttLogProgressCount = \App\Models\HrdfAttendanceLog::where('status', 'in_progress')->count();

    $hrdfAttLogCompletedCount = \App\Models\HrdfAttendanceLog::where('status', 'completed')->count();

    $hrdfAttLogCancelledCount = \App\Models\HrdfAttendanceLog::where('status', 'cancelled')->count();
@endphp

<div id="hrdf-att-log-container" class="hrdf-att-log-container"
    x-data="{
        selectedGroup: null,

        setSelectedGroup(value) {
            if (this.selectedGroup === value) {
                this.selectedGroup = null;
            } else {
                this.selectedGroup = value;
            }
        },

        init() {
            this.selectedGroup = null;
        }
    }"
    x-init="init()">

    <div class="dashboard-layout" wire:poll.300s>
        <!-- Left sidebar with main category groups -->
        <div class="group-column">
            <!-- 1. HRDF ATT LOG ALL (Indigo Theme) -->
            <div class="group-box group-hrdf-att-log-all"
                :class="{'selected': selectedGroup === 'hrdf-att-log-all'}"
                @click="setSelectedGroup('hrdf-att-log-all')">
                <div class="group-info">
                    <div class="group-title">All Logs</div>
                </div>
                <div class="group-count">{{ $hrdfAttLogAllCount }}</div>
            </div>

            <!-- 2. HRDF ATT LOG NEW (Blue Theme) -->
            <div class="group-box group-hrdf-att-log-new"
                :class="{'selected': selectedGroup === 'hrdf-att-log-new'}"
                @click="setSelectedGroup('hrdf-att-log-new')">
                <div class="group-info">
                    <div class="group-title">New Logs</div>
                </div>
                <div class="group-count">{{ $hrdfAttLogNewCount }}</div>
            </div>

            <!-- 4. HRDF ATT LOG COMPLETED (Green Theme) -->
            <div class="group-box group-hrdf-att-log-completed"
                :class="{'selected': selectedGroup === 'hrdf-att-log-completed'}"
                @click="setSelectedGroup('hrdf-att-log-completed')">
                <div class="group-info">
                    <div class="group-title">Completed</div>
                </div>
                <div class="group-count">{{ $hrdfAttLogCompletedCount }}</div>
            </div>
        </div>

        <!-- Right content column -->
        <div class="content-column">
            <!-- Content area for tables -->
            <div class="content-area">
                <!-- Display hint message when nothing is selected -->
                <div class="hint-message" x-show="selectedGroup === null" x-transition>
                    <h3>Select a category to continue</h3>
                    <p>Click on any of the category boxes to view HRDF attendance log data</p>
                </div>

                <!-- 1. HRDF ATT LOG ALL Table -->
                <div x-show="selectedGroup === 'hrdf-att-log-all'" x-transition>
                    <div class="p-4">
                        <livewire:admin-h-r-d-f-attendance-log.hrdf-att-log-all-table />
                    </div>
                </div>

                <!-- 2. HRDF ATT LOG NEW Table -->
                <div x-show="selectedGroup === 'hrdf-att-log-new'" x-transition>
                    <div class="p-4">
                        <livewire:admin-h-r-d-f-attendance-log.hrdf-att-log-new-table />
                    </div>
                </div>

                <!-- 4. HRDF ATT LOG COMPLETED Table -->
                <div x-show="selectedGroup === 'hrdf-att-log-completed'" x-transition>
                    <div class="p-4">
                        <livewire:admin-h-r-d-f-attendance-log.hrdf-att-log-completed-table />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to reset the admin HRDF Attendance Log component
        window.resetAdminHRDFAttLog = function() {
            const container = document.getElementById('hrdf-att-log-container');
            if (container && container.__x) {
                container.__x.$data.selectedGroup = null;
                console.log('Admin HRDF Attendance Log dashboard reset via global function');
            }
        };

        // Listen for our custom reset event
        window.addEventListener('reset-admin-hrdf-att-log-dashboard', function() {
            window.resetAdminHRDFAttLog();
        });
    });
</script>
