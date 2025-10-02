{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/adminheadcount.blade.php --}}
<style>
    /* Container styling */
    .headcount-container {
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

    /* HEADCOUNT ALL GROUP COLORS - Purple Theme */
    .group-headcount-all { border-top-color: #8b5cf6; }
    .group-headcount-all .group-count { color: #8b5cf6; }
    .group-headcount-all.selected { background-color: rgba(139, 92, 246, 0.05); }

    /* HEADCOUNT NEW GROUP COLORS - Blue Theme */
    .group-headcount-new { border-top-color: #3b82f6; }
    .group-headcount-new .group-count { color: #3b82f6; }
    .group-headcount-new.selected { background-color: rgba(59, 130, 246, 0.05); }

    /* HEADCOUNT COMPLETED GROUP COLORS - Green Theme */
    .group-headcount-completed { border-top-color: #10b981; }
    .group-headcount-completed .group-count { color: #10b981; }
    .group-headcount-completed.selected { background-color: rgba(16, 185, 129, 0.05); }

    /* HEADCOUNT REJECTED GROUP COLORS - Red Theme */
    .group-headcount-rejected { border-top-color: #ef4444; }
    .group-headcount-rejected .group-count { color: #ef4444; }
    .group-headcount-rejected.selected { background-color: rgba(239, 68, 68, 0.05); }

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
    // Headcount Handover Counts
    $headcountAllCount = app(\App\Livewire\AdminHeadcountDashboard\HeadcountAllTable::class)
        ->getNewHeadcountHandovers()
        ->count();

    $headcountNewCount = app(\App\Livewire\AdminHeadcountDashboard\HeadcountNewTable::class)
        ->getNewHeadcountHandovers()
        ->count();

    $headcountCompletedCount = app(\App\Livewire\AdminHeadcountDashboard\HeadcountCompletedTable::class)
        ->getCompletedHeadcountHandovers()
        ->count();

    $headcountRejectedCount = app(\App\Livewire\AdminHeadcountDashboard\HeadcountRejectedTable::class)
        ->getRejectedHeadcountHandovers()
        ->count();
@endphp

<div id="headcount-container" class="headcount-container"
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
            <!-- 1. HEADCOUNT ALL (Purple Theme) -->
            <div class="group-box group-headcount-all"
                :class="{'selected': selectedGroup === 'headcount-all'}"
                @click="setSelectedGroup('headcount-all')">
                <div class="group-info">
                    <div class="group-title">Headcount All</div>
                </div>
                <div class="group-count">{{ $headcountAllCount }}</div>
            </div>

            <!-- 2. HEADCOUNT NEW (Blue Theme) -->
            <div class="group-box group-headcount-new"
                :class="{'selected': selectedGroup === 'headcount-new'}"
                @click="setSelectedGroup('headcount-new')">
                <div class="group-info">
                    <div class="group-title">Headcount New</div>
                </div>
                <div class="group-count">{{ $headcountNewCount }}</div>
            </div>

            <!-- 3. HEADCOUNT COMPLETED (Green Theme) -->
            <div class="group-box group-headcount-completed"
                :class="{'selected': selectedGroup === 'headcount-completed'}"
                @click="setSelectedGroup('headcount-completed')">
                <div class="group-info">
                    <div class="group-title">Headcount Completed</div>
                </div>
                <div class="group-count">{{ $headcountCompletedCount }}</div>
            </div>

            <!-- 4. HEADCOUNT REJECTED (Red Theme) -->
            <div class="group-box group-headcount-rejected"
                :class="{'selected': selectedGroup === 'headcount-rejected'}"
                @click="setSelectedGroup('headcount-rejected')">
                <div class="group-info">
                    <div class="group-title">Headcount Rejected</div>
                </div>
                <div class="group-count">{{ $headcountRejectedCount }}</div>
            </div>
        </div>

        <!-- Right content column -->
        <div class="content-column">
            <!-- Content area for tables -->
            <div class="content-area">
                <!-- Display hint message when nothing is selected -->
                <div class="hint-message" x-show="selectedGroup === null" x-transition>
                    <h3>Select a category to continue</h3>
                    <p>Click on any of the category boxes to view headcount handover data</p>
                </div>

                <!-- 1. HEADCOUNT ALL Table -->
                <div x-show="selectedGroup === 'headcount-all'" x-transition>
                    <div class="p-4">
                        <livewire:admin-headcount-dashboard.headcount-all-table />
                    </div>
                </div>

                <!-- 2. HEADCOUNT NEW Table -->
                <div x-show="selectedGroup === 'headcount-new'" x-transition>
                    <div class="p-4">
                        <livewire:admin-headcount-dashboard.headcount-new-table />
                    </div>
                </div>

                <!-- 3. HEADCOUNT COMPLETED Table -->
                <div x-show="selectedGroup === 'headcount-completed'" x-transition>
                    <div class="p-4">
                        <livewire:admin-headcount-dashboard.headcount-completed-table />
                    </div>
                </div>

                <!-- 4. HEADCOUNT REJECTED Table -->
                <div x-show="selectedGroup === 'headcount-rejected'" x-transition>
                    <div class="p-4">
                        <livewire:admin-headcount-dashboard.headcount-rejected-table />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to reset the admin headcount component
        window.resetAdminHeadcount = function() {
            const container = document.getElementById('headcount-container');
            if (container && container.__x) {
                container.__x.$data.selectedGroup = null;
                console.log('Admin Headcount dashboard reset via global function');
            }
        };

        // Listen for our custom reset event
        window.addEventListener('reset-admin-headcount-dashboard', function() {
            window.resetAdminHeadcount();
        });
    });
</script>
