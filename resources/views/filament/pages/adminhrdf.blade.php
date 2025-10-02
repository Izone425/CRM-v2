{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/adminhrdf.blade.php --}}
<style>
    /* Container styling */
    .hrdf-container {
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
        padding: 20px 0px 20px 0px;
        cursor: pointer;
        transition: all 0.2s;
        border-top: 4px solid transparent;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
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
    }

    .group-desc {
        font-size: 12px;
        color: #6b7280;
    }

    .group-count {
        font-size: 24px;
        font-weight: bold;
    }

    /* HRDF ALL GROUP COLORS - Purple Theme */
    .group-hrdf-all { border-top-color: #8b5cf6; }
    .group-hrdf-all .group-count { color: #8b5cf6; }
    .group-hrdf-all.selected { background-color: rgba(139, 92, 246, 0.05); }

    /* HRDF NEW GROUP COLORS - Blue Theme */
    .group-hrdf-new { border-top-color: #3b82f6; }
    .group-hrdf-new .group-count { color: #3b82f6; }
    .group-hrdf-new.selected { background-color: rgba(59, 130, 246, 0.05); }

    /* HRDF COMPLETED GROUP COLORS - Green Theme */
    .group-hrdf-completed { border-top-color: #10b981; }
    .group-hrdf-completed .group-count { color: #10b981; }
    .group-hrdf-completed.selected { background-color: rgba(16, 185, 129, 0.05); }

    /* HRDF REJECTED GROUP COLORS - Red Theme */
    .group-hrdf-rejected { border-top-color: #ef4444; }
    .group-hrdf-rejected .group-count { color: #ef4444; }
    .group-hrdf-rejected.selected { background-color: rgba(239, 68, 68, 0.05); }

    /* Category column styling */
    .category-column {
        padding-right: 10px;
    }

    /* Category container */
    .category-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 10px;
        border-right: 1px solid #e5e7eb;
        padding-right: 10px;
        max-height: 75vh;
        overflow-y: auto;
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

        .category-container {
            grid-template-columns: repeat(4, 1fr);
            border-right: none;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 15px;
            margin-bottom: 15px;
            max-height: none;
        }
    }

    @media (max-width: 768px) {
        .group-container,
        .category-container {
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
        .group-container,
        .category-container {
            grid-template-columns: 1fr;
        }
    }
</style>

@php
    // HRDF Handover Counts
    $hrdfAllCount = app(\App\Livewire\AdminHRDFDashboard\HrdfAllTable::class)
        ->getNewHrdfHandovers()
        ->count();

    $hrdfNewCount = app(\App\Livewire\AdminHRDFDashboard\HrdfNewTable::class)
        ->getNewHrdfHandovers()
        ->count();

    $hrdfCompletedCount = app(\App\Livewire\AdminHRDFDashboard\HrdfCompletedTable::class)
        ->getNewHrdfHandovers() // This should be getCompletedHrdfHandovers in the actual implementation
        ->count();

    $hrdfRejectedCount = app(\App\Livewire\AdminHRDFDashboard\HrdfRejectedTable::class)
        ->getNewHrdfHandovers() // This should be getRejectedHrdfHandovers in the actual implementation
        ->count();
@endphp

<div id="hrdf-container" class="hrdf-container"
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
            <!-- 1. HRDF ALL (Purple Theme) -->
            <div class="group-box group-hrdf-all"
                :class="{'selected': selectedGroup === 'hrdf-all'}"
                @click="setSelectedGroup('hrdf-all')">
                <div class="group-info">
                    <div class="group-title">HRDF All</div>
                </div>
                <div class="group-count">{{ $hrdfAllCount }}</div>
            </div>

            <!-- 2. HRDF NEW (Blue Theme) -->
            <div class="group-box group-hrdf-new"
                :class="{'selected': selectedGroup === 'hrdf-new'}"
                @click="setSelectedGroup('hrdf-new')">
                <div class="group-info">
                    <div class="group-title">HRDF New</div>
                </div>
                <div class="group-count">{{ $hrdfNewCount }}</div>
            </div>

            <!-- 3. HRDF COMPLETED (Green Theme) -->
            <div class="group-box group-hrdf-completed"
                :class="{'selected': selectedGroup === 'hrdf-completed'}"
                @click="setSelectedGroup('hrdf-completed')">
                <div class="group-info">
                    <div class="group-title">HRDF Completed</div>
                </div>
                <div class="group-count">{{ $hrdfCompletedCount }}</div>
            </div>

            <!-- 4. HRDF REJECTED (Red Theme) -->
            <div class="group-box group-hrdf-rejected"
                :class="{'selected': selectedGroup === 'hrdf-rejected'}"
                @click="setSelectedGroup('hrdf-rejected')">
                <div class="group-info">
                    <div class="group-title">HRDF Rejected</div>
                </div>
                <div class="group-count">{{ $hrdfRejectedCount }}</div>
            </div>
        </div>

        <!-- Right content column -->
        <div class="content-column">
            <!-- Content area for tables -->
            <div class="content-area">
                <!-- Display hint message when nothing is selected -->
                <div class="hint-message" x-show="selectedGroup === null" x-transition>
                    <h3>Select a category to continue</h3>
                    <p>Click on any of the category boxes to view HRDF handover data</p>
                </div>

                <!-- 1. HRDF ALL Table -->
                <div x-show="selectedGroup === 'hrdf-all'" x-transition>
                    <div class="p-4">
                        <livewire:admin-h-r-d-f-dashboard.hrdf-all-table />
                    </div>
                </div>

                <!-- 2. HRDF NEW Table -->
                <div x-show="selectedGroup === 'hrdf-new'" x-transition>
                    <div class="p-4">
                        <livewire:admin-h-r-d-f-dashboard.hrdf-new-table />
                    </div>
                </div>

                <!-- 3. HRDF COMPLETED Table -->
                <div x-show="selectedGroup === 'hrdf-completed'" x-transition>
                    <div class="p-4">
                        <livewire:admin-h-r-d-f-dashboard.hrdf-completed-table />
                    </div>
                </div>

                <!-- 4. HRDF REJECTED Table -->
                <div x-show="selectedGroup === 'hrdf-rejected'" x-transition>
                    <div class="p-4">
                        <livewire:admin-h-r-d-f-dashboard.hrdf-rejected-table />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to reset the admin HRDF component
        window.resetAdminHRDF = function() {
            const container = document.getElementById('hrdf-container');
            if (container && container.__x) {
                container.__x.$data.selectedGroup = null;
                console.log('Admin HRDF dashboard reset via global function');
            }
        };

        // Listen for our custom reset event
        window.addEventListener('reset-admin-hrdf-dashboard', function() {
            window.resetAdminHRDF();
        });
    });
</script>
