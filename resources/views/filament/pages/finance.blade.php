<!-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/finance.blade.php -->
<style>
    /* Container styling */
    .finance-container {
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

    /* GROUP COLORS */
    .group-einvoice { border-top-color: #7c3aed; }
    .group-einvoice .group-count { color: #7c3aed; }

    .group-reseller { border-top-color: #f59e0b; }
    .group-reseller .group-count { color: #f59e0b; }

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
        grid-template-columns: repeat(3, 1fr);
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

    /* COLOR CODING FOR STAT BOXES */
    .new-task { border-left: 4px solid #2563eb; }
    .new-task .stat-count { color: #2563eb; }

    .rejected { border-left: 4px solid #ef4444; }
    .rejected .stat-count { color: #ef4444; }

    .completed { border-left: 4px solid #10b981; }
    .completed .stat-count { color: #10b981; }

    /* Selected states for categories */
    .stat-box.selected.new-task { background-color: rgba(37, 99, 235, 0.05); border-left-width: 6px; }
    .stat-box.selected.rejected { background-color: rgba(239, 68, 68, 0.05); border-left-width: 6px; }
    .stat-box.selected.completed { background-color: rgba(16, 185, 129, 0.05); border-left-width: 6px; }

    /* Animation for tab switching */
    [x-transition] {
        transition: all 0.2s ease-out;
    }

    /* Custom table styling for finance tables */
    .finance-table {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .finance-table table {
        width: 100%;
        border-collapse: collapse;
    }

    .finance-table th {
        background-color: #f8fafc;
        padding: 12px 16px;
        text-align: left;
        font-weight: 600;
        color: #374151;
        border-bottom: 1px solid #e5e7eb;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.05em;
    }

    .finance-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #f3f4f6;
        color: #6b7280;
    }

    .finance-table tr:hover {
        background-color: #f9fafb;
    }

    .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 500;
        text-transform: uppercase;
    }

    .status-new {
        background-color: #dbeafe;
        color: #1e40af;
    }

    .status-rejected {
        background-color: #fee2e2;
        color: #dc2626;
    }

    .status-completed {
        background-color: #d1fae5;
        color: #065f46;
    }

    /* Responsive adjustments */
    @media (max-width: 1200px) {
        .dashboard-layout {
            grid-template-columns: 100%;
            grid-template-rows: auto auto;
        }

        .group-container {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
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
        .category-container {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 640px) {
        .category-container {
            grid-template-columns: 1fr;
        }
    }
</style>

@php
    // Calculate E-Invoice counts
    use App\Models\EInvoiceHandover;
    use App\Models\ResellerHandover;

    // Get counts for different statuses
    $newCount = EInvoiceHandover::where('status', 'New')->count();
    $rejectedCount = EInvoiceHandover::where('status', 'Rejected')->count();
    $completedCount = EInvoiceHandover::where('status', 'Completed')->count();

    // Total count
    $totalEInvoiceCount = EInvoiceHandover::count();

    // Reseller Handover counts
    $resellerCompletedCount = ResellerHandover::where('status', 'completed')->count();
@endphp

<div id="finance-container" class="finance-container"
    x-data="{
        selectedGroup: null,
        selectedStat: null,

        setSelectedGroup(value) {
            if (this.selectedGroup === value) {
                this.selectedGroup = null;
                this.selectedStat = null;
            } else {
                this.selectedGroup = value;
                // Set default stat for E-Invoice group
                if (value === 'einvoice') {
                    this.selectedStat = 'new-task';
                }
                // Set default stat for Reseller Handover group
                if (value === 'reseller') {
                    this.selectedStat = 'reseller-completed';
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
            console.log('Finance dashboard Alpine component initialized');
        }
    }"
    x-init="init()">

    <!-- Dashboard layout -->
    <div class="dashboard-layout" wire:poll.300s>
        <!-- Left sidebar with groups -->
        <div class="group-column">
            <div class="group-container">
                <!-- Group: E-Invoice -->
                <div class="group-box group-einvoice"
                     :class="{'selected': selectedGroup === 'einvoice'}"
                     @click="setSelectedGroup('einvoice')">
                    <div class="group-title">E-Invoice Registration</div>
                    <div class="group-count">{{ $newCount }}</div>
                </div>

                <!-- Group: Reseller Handover -->
                <div class="group-box group-reseller"
                     :class="{'selected': selectedGroup === 'reseller'}"
                     @click="setSelectedGroup('reseller')">
                    <div class="group-title">Reseller Handover</div>
                    <div class="group-count">{{ $resellerCompletedCount }}</div>
                </div>
            </div>
        </div>

        <!-- Right content area -->
        <div class="content-column">
            <!-- E-Invoice Categories -->
            <div class="category-container" x-show="selectedGroup === 'einvoice'">
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
                    <div class="stat-count">{{ $rejectedCount }}</div>
                </div>

                <div class="stat-box completed"
                     :class="{'selected': selectedStat === 'completed'}"
                     @click="setSelectedStat('completed')">
                    <div class="stat-info">
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-count">{{ $completedCount }}</div>
                </div>
            </div>

            <!-- Reseller Handover Categories -->
            <div class="category-container" x-show="selectedGroup === 'reseller'">
                <div class="stat-box completed"
                     :class="{'selected': selectedStat === 'reseller-completed'}"
                     @click="setSelectedStat('reseller-completed')">
                    <div class="stat-info">
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-count">{{ $resellerCompletedCount }}</div>
                </div>
            </div>

            <br>
            <!-- Content Area for Tables -->
            <div class="content-area">
                <!-- Display hint message when nothing is selected -->
                <div class="hint-message" x-show="selectedGroup === null || selectedStat === null" x-transition>
                    <h3 x-text="selectedGroup === null ? 'Select a group to continue' : 'Select a category to view data'"></h3>
                    <p x-text="selectedGroup === null ? 'Click on E-Invoice or Reseller Handover to see categories' : 'Click on any category box to display the corresponding information'"></p>
                </div>

                <!-- E-Invoice Tables -->
                <!-- New Task Table -->
                <div x-show="selectedStat === 'new-task'" x-transition>
                    <livewire:finance-dashboard.e-invoice-handover-new />
                </div>

                <!-- Rejected Table -->
                <div x-show="selectedStat === 'rejected'" x-transition>
                    <livewire:finance-dashboard.e-invoice-handover-rejected />
                </div>

                <!-- Completed Table -->
                <div x-show="selectedStat === 'completed'" x-transition>
                    <livewire:finance-dashboard.e-invoice-handover-completed />
                </div>

                <!-- Reseller Handover Tables -->
                <!-- Reseller Completed Table -->
                <div x-show="selectedStat === 'reseller-completed'" x-transition>
                    <livewire:admin-reseller-handover-completed />
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Finance dashboard component setup
    document.addEventListener('DOMContentLoaded', function() {
        // Function to reset the finance component
        window.resetFinanceDashboard = function() {
            const container = document.getElementById('finance-container');
            if (container && container.__x) {
                container.__x.$data.selectedGroup = null;
                container.__x.$data.selectedStat = null;
                console.log('Finance dashboard reset via global function');
            }
        };

        // Listen for custom reset event
        window.addEventListener('reset-finance-dashboard', function() {
            window.resetFinanceDashboard();
        });
    });
</script>
