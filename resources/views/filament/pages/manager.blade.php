{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/manager.blade.php --}}

<style>
    /* Container styling */
    .manager-container {
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

    .group-count {
        font-size: 24px;
        font-weight: bold;
    }

    /* GROUP COLORS */
    .group-lead-transfer { border-top-color: #2563eb; }
    .group-lead-transfer .group-count { color: #2563eb; }

    .group-bypass-duplicate { border-top-color: #f59e0b; }
    .group-bypass-duplicate .group-count { color: #f59e0b; }

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

    .fi-ta-ctn .py-4 {
        padding-top: .5rem !important;
        padding-bottom: .5rem !important;
    }
</style>

@php
    // Lead Owner Change Request Count
    $leadTransferCount = app(\App\Livewire\LeadOwnerChangeRequestTable::class)
        ->getTableQuery()
        ->count();

    // Bypass Duplicate Request Count
    $bypassDuplicateCount = app(\App\Livewire\ManagerDashboard\BypassDuplicatedLead::class)
        ->getTableQuery()
        ->count();
@endphp

<div id="manager-container" class="manager-container"
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
            <!-- Lead Transfer Requests -->
            <div class="group-box group-lead-transfer"
                :class="{'selected': selectedGroup === 'lead-transfer'}"
                @click="setSelectedGroup('lead-transfer')">
                <div class="group-info">
                    <div class="group-title">Lead Transfer Request</div>
                </div>
                <div class="group-count">{{ $leadTransferCount }}</div>
            </div>

            <!-- Bypass Duplicate Requests -->
            <div class="group-box group-bypass-duplicate"
                :class="{'selected': selectedGroup === 'bypass-duplicate'}"
                @click="setSelectedGroup('bypass-duplicate')">
                <div class="group-info">
                    <div class="group-title">Bypass Duplicate Request</div>
                </div>
                <div class="group-count">{{ $bypassDuplicateCount }}</div>
            </div>
        </div>

        <!-- Right content column -->
        <div class="content-column">
            <div class="content-area">
                <!-- Display hint message when nothing is selected -->
                <div class="hint-message" x-show="selectedGroup === null" x-transition>
                    <h3>Select a category to continue</h3>
                    <p>Click on any of the category boxes to see pending requests</p>
                </div>

                <!-- Lead Transfer Requests Table -->
                <div x-show="selectedGroup === 'lead-transfer'" x-transition>
                    <div class="p-4">
                        <livewire:lead-owner-change-request-table />
                    </div>
                </div>

                <!-- Bypass Duplicate Requests Table -->
                <div x-show="selectedGroup === 'bypass-duplicate'" x-transition>
                    <div class="p-4">
                        <livewire:manager-dashboard.bypass-duplicated-lead />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to reset the manager component
        window.resetManager = function() {
            const container = document.getElementById('manager-container');
            if (container && container.__x) {
                container.__x.$data.selectedGroup = null;
                console.log('Manager dashboard reset via global function');
            }
        };

        // Listen for our custom reset event
        window.addEventListener('reset-manager-dashboard', function() {
            window.resetManager();
        });
    });
</script>
