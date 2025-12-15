{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/admingeneral.blade.php --}}

<style>
    /* Container styling */
    .general-container {
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

    /* TICKETS GROUP COLORS - Green Theme */
    .group-tickets-new { border-top-color: #10b981; }
    .group-tickets-new .group-count { color: #10b981; }
    .group-tickets-new.selected { background-color: rgba(16, 185, 129, 0.05); }

    .group-tickets-completed { border-top-color: #059669; }
    .group-tickets-completed .group-count { color: #059669; }
    .group-tickets-completed.selected { background-color: rgba(5, 150, 105, 0.05); }

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
        padding: 12px 15px;
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

    /* TICKETS COLORS - Green Theme */
    .tickets-new { border-left: 4px solid #10b981; }
    .tickets-new .stat-count { color: #10b981; }
    .stat-box.selected.tickets-new { background-color: rgba(16, 185, 129, 0.05); border-left-width: 6px; }

    .tickets-completed { border-left: 4px solid #059669; }
    .tickets-completed .stat-count { color: #059669; }
    .stat-box.selected.tickets-completed { background-color: rgba(5, 150, 105, 0.05); border-left-width: 6px; }

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
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            border-right: none;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .category-container {
            grid-template-columns: repeat(2, 1fr);
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
            grid-template-columns: 1fr;
        }

        .stat-box:hover,
        .group-box:hover {
            transform: none;
        }

        .stat-box.selected,
        .group-box.selected {
            transform: none;
        }
    }
</style>

@php
    // Get ticket counts
    $newTicketsCount = \App\Models\InternalTicket::where('status', 'new')->count();
    $completedTicketsCount = \App\Models\InternalTicket::where('status', 'completed')->count();
@endphp

<div id="general-container" class="general-container"
    x-data="{
        selectedGroup: null,
        selectedStat: null,

        setSelectedGroup(value) {
            if (this.selectedGroup === value) {
                this.selectedGroup = null;
                this.selectedStat = null;
            } else {
                this.selectedGroup = value;
                this.selectedStat = null;
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
            this.selectedGroup = null;
            this.selectedStat = null;
        }
    }"
    x-init="init()">

    <div class="dashboard-layout" wire:poll.300s>
        <!-- Left sidebar with main category groups -->
        <div class="group-column">
            <!-- 1. NEW TICKETS (Green Theme) -->
            <div class="group-box group-tickets-new"
                :class="{'selected': selectedGroup === 'tickets-new'}"
                @click="setSelectedGroup('tickets-new')">
                <div class="group-info">
                    <div class="group-title">New Tickets</div>
                </div>
                <div class="group-count">{{ $newTicketsCount }}</div>
            </div>

            <!-- 2. COMPLETED TICKETS (Darker Green Theme) -->
            <div class="group-box group-tickets-completed"
                :class="{'selected': selectedGroup === 'tickets-completed'}"
                @click="setSelectedGroup('tickets-completed')">
                <div class="group-info">
                    <div class="group-title">Completed Tickets</div>
                </div>
                <div class="group-count">{{ $completedTicketsCount }}</div>
            </div>
        </div>

        <!-- Right content column -->
        <div class="content-column">
            <!-- 1. NEW TICKETS Sub-tabs -->
            <div class="category-container" x-show="selectedGroup === 'tickets-new'" x-transition>
                <div class="stat-box tickets-new"
                    :class="{'selected': selectedStat === 'tickets-new-table'}"
                    @click="setSelectedStat('tickets-new-table')">
                    <div class="stat-info">
                        <div class="stat-label">View New Tickets</div>
                    </div>
                    <div class="stat-count">{{ $newTicketsCount }}</div>
                </div>
            </div>

            <!-- 2. COMPLETED TICKETS Sub-tabs -->
            <div class="category-container" x-show="selectedGroup === 'tickets-completed'" x-transition>
                <div class="stat-box tickets-completed"
                    :class="{'selected': selectedStat === 'tickets-completed-table'}"
                    @click="setSelectedStat('tickets-completed-table')">
                    <div class="stat-info">
                        <div class="stat-label">View Completed Tickets</div>
                    </div>
                    <div class="stat-count">{{ $completedTicketsCount }}</div>
                </div>
            </div>

            <!-- Content area for tables -->
            <div class="content-area">
                <!-- Display hint message when nothing is selected -->
                <div class="hint-message" x-show="selectedGroup === null || selectedStat === null" x-transition>
                    <h3 x-text="selectedGroup === null ? 'Select a category to continue' : 'Select a subcategory to view data'"></h3>
                    <p x-text="selectedGroup === null ? 'Click on any of the category boxes to see options' : 'Click on any of the subcategory boxes to display the corresponding information'"></p>
                </div>

                <!-- 1. NEW TICKETS Table -->
                <div x-show="selectedStat === 'tickets-new-table'" x-transition>
                    <div class="p-4">
                        @livewire('internal-ticket-new')
                    </div>
                </div>

                <!-- 2. COMPLETED TICKETS Table -->
                <div x-show="selectedStat === 'tickets-completed-table'" x-transition>
                    <div class="p-4">
                        @livewire('internal-ticket-completed')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to reset the admin general component
        window.resetAdminGeneral = function() {
            const container = document.getElementById('general-container');
            if (container && container.__x) {
                container.__x.$data.selectedGroup = null;
                container.__x.$data.selectedStat = null;
                console.log('Admin general dashboard reset via global function');
            }
        };

        // Listen for our custom reset event
        window.addEventListener('reset-admin-general-dashboard', function() {
            window.resetAdminGeneral();
        });
    });
</script>
