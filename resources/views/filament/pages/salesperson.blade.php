<style>
    /* Container styling */
    .lead-owner-container {
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
    .group-demo { border-top-color: #2563eb; }
    .group-pr { border-top-color: #8b5cf6; }
    .group-software { border-top-color: #10b981; }
    .group-hardware { border-top-color: #f59e0b; }
    .group-no-respond { border-top-color: #ec4899; }
    .group-others { border-top-color: #06b6d4; }

    .group-demo .group-count { color: #2563eb; }
    .group-pr .group-count { color: #8b5cf6; }
    .group-software .group-count { color: #10b981; }
    .group-hardware .group-count { color: #f59e0b; }
    .group-no-respond .group-count { color: #ec4899; }
    .group-others .group-count { color: #06b6d4; }

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

    .demo-today { border-left: 4px solid #2563eb; }
    .demo-today .stat-count { color: #2563eb; }

    .demo-tmr { border-left: 4px solid #3b82f6; }
    .demo-tmr .stat-count { color: #3b82f6; }

    .pr-today { border-left: 4px solid #8b5cf6; }
    .pr-today .stat-count { color: #8b5cf6; }

    .pr-overdue { border-left: 4px solid #ef4444; }
    .pr-overdue .stat-count { color: #ef4444; }

    .software-handover-pending { border-left: 4px solid #10b981; }
    .software-handover-pending .stat-count { color: #10b981; }

    .software-handover-completed { border-left: 4px solid #34d399; }
    .software-handover-completed .stat-count { color: #34d399; }

    .hardware-handover-pending { border-left: 4px solid #f59e0b; }
    .hardware-handover-pending .stat-count { color: #f59e0b; }

    .hardware-handover-completed { border-left: 4px solid #fbbf24; }
    .hardware-handover-completed .stat-count { color: #fbbf24; }

    .transfer-lead { border-left: 4px solid #ec4899; }
    .transfer-lead .stat-count { color: #ec4899; }

    .follow-up-lead { border-left: 4px solid #d946ef; }
    .follow-up-lead .stat-count { color: #d946ef; }

    .debtor-follow-up-today {
        border-left: 4px solid #06b6d4;
    }
    .debtor-follow-up-today .stat-count {
        color: #06b6d4;
    }

    /* Debtor Follow Up - OVERDUE */
    .debtor-follow-up-overdue {
        border-left: 4px solid #0284c7;
    }
    .debtor-follow-up-overdue .stat-count {
        color: #0284c7;
    }

    /* HRDF Follow Up - TODAY */
    .hrdf-follow-up-today {
        border-left: 4px solid #0ea5e9;
    }
    .hrdf-follow-up-today .stat-count {
        color: #0ea5e9;
    }

    /* HRDF Follow Up - OVERDUE */
    .hrdf-follow-up-overdue {
        border-left: 4px solid #0369a1;
    }
    .hrdf-follow-up-overdue .stat-count {
        color: #0369a1;
    }

    /* Selected states for new categories */
    .stat-box.selected.demo-today { background-color: rgba(37, 99, 235, 0.05); border-left-width: 6px; }
    .stat-box.selected.demo-tmr { background-color: rgba(59, 130, 246, 0.05); border-left-width: 6px; }
    .stat-box.selected.pr-today { background-color: rgba(139, 92, 246, 0.05); border-left-width: 6px; }
    .stat-box.selected.pr-overdue { background-color: rgba(239, 68, 68, 0.05); border-left-width: 6px; }
    .stat-box.selected.software-handover-pending { background-color: rgba(16, 185, 129, 0.05); border-left-width: 6px; }
    .stat-box.selected.software-handover-completed { background-color: rgba(52, 211, 153, 0.05); border-left-width: 6px; }
    .stat-box.selected.hardware-handover-pending { background-color: rgba(245, 158, 11, 0.05); border-left-width: 6px; }
    .stat-box.selected.hardware-handover-completed { background-color: rgba(251, 191, 36, 0.05); border-left-width: 6px; }
    .stat-box.selected.transfer-lead { background-color: rgba(236, 72, 153, 0.05); border-left-width: 6px; }
    .stat-box.selected.follow-up-lead { background-color: rgba(217, 70, 239, 0.05); border-left-width: 6px; }
    .stat-box.selected.debtor-follow-up-today { background-color: rgba(6, 182, 212, 0.05); border-left-width: 6px; }
    .stat-box.selected.debtor-follow-up-overdue { background-color: rgba(6, 182, 212, 0.05); border-left-width: 6px; }
    .stat-box.selected.hrdf-follow-up-today { background-color: rgba(14, 165, 233, 0.05); border-left-width: 6px; }
    .stat-box.selected.hrdf-follow-up-overdue { background-color: rgba(14, 165, 233, 0.05); border-left-width: 6px; }

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


@php
    // New Leads count
    $user = Auth::user();

    $demoTodayCount = app(\App\Livewire\SalespersonDashboard\DemoTodayTable::class)
        ->getTodayDemos()
        ->count();

    $demoTomorrowCount = app(\App\Livewire\SalespersonDashboard\DemoTmrTable::class)
        ->getTomorrowDemos()
        ->count();

    $prospectTodayCount = app(\App\Livewire\SalespersonDashboard\PrTodaySalespersonTable::class)
        ->getTodayProspects()
        ->count();

    $prospectOverdueCount = app(\App\Livewire\SalespersonDashboard\PrOverdueSalespersonTable::class)
        ->getOverdueProspects()
        ->count();

    $softwareHandoverNew = app(\App\Livewire\SalespersonDashboard\SoftwareHandoverNew::class)
        ->getNewSoftwareHandovers()
        ->count();

    $softwareHandoverCompleted = app(\App\Livewire\SalespersonDashboard\SoftwareHandoverCompleted::class)
        ->getNewSoftwareHandovers()
        ->count();

    $hardwareHandoverNew = app(\App\Livewire\SalespersonDashboard\HardwareHandoverNew::class)
        ->getNewHardwareHandovers()
        ->count();

    $hardwareHandoverCompleted = app(\App\Livewire\SalespersonDashboard\HardwareHandoverCompleted::class)
        ->getOverdueHardwareHandovers()
        ->count();

    $followUpLead = app(\App\Livewire\SalespersonDashboard\FollowUpLead::class)
        ->getFollowUpLead()
        ->count();

    $transferLead = app(\App\Livewire\SalespersonDashboard\TransferLead::class)
        ->getTransferLead()
        ->count();

    $debtorFollowUpToday = app(\App\Livewire\SalespersonDashboard\DebtorFollowUpTodayTable::class)
        ->getTodayProspects()
        ->count();

    $debtorFollowUpOverdue = app(\App\Livewire\SalespersonDashboard\DebtorFollowUpOverdueTable::class)
        ->getOverdueProspects()
        ->count();

    $hrdfFollowUpToday = app(\App\Livewire\SalespersonDashboard\HrdfFollowUpTodayTable::class)
        ->getTodayProspects()
        ->count();

    $hrdfFollowUpOverdue = app(\App\Livewire\SalespersonDashboard\HrdfFollowUpOverdueTable::class)
        ->getTodayProspects()
        ->count();
@endphp

<div id="lead-owner-container" class="lead-owner-container"
    x-data="{
        selectedGroup: 'demo-session',
        selectedStat: 'demo-today',

        setSelectedGroup(value) {
            if (this.selectedGroup === value) {
                this.selectedGroup = null;
                this.selectedStat = null;
            } else {
                this.selectedGroup = value;

                // Set default stat for each group
                if (value === 'demo-session') {
                    this.selectedStat = 'demo-today';
                } else if (value === 'prospect-reminder') {
                    this.selectedStat = 'pr-today';
                } else if (value === 'software-handover') {
                    this.selectedStat = 'software-handover-pending';
                } else if (value === 'hardware-handover') {
                    this.selectedStat = 'hardware-handover-pending';
                } else if (value === 'no-respond-leads') {
                    this.selectedStat = 'transfer-lead';
                } else if (value === 'others') {
                    this.selectedStat = 'debtor-follow-up-today';
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

                <!-- Group: Demo Session -->
                <div class="group-box group-demo"
                        :class="{'selected': selectedGroup === 'demo-session'}"
                        @click="setSelectedGroup('demo-session')">
                    <div class="group-title">Demo Session</div>
                    <div class="group-count">{{ $demoTodayCount + $demoTomorrowCount }}</div>
                </div>

                <!-- Group: Prospect Reminder -->
                <div class="group-box group-pr"
                        :class="{'selected': selectedGroup === 'prospect-reminder'}"
                        @click="setSelectedGroup('prospect-reminder')">
                    <div class="group-title">Prospect Reminder</div>
                    <div class="group-count">{{ $prospectTodayCount + $prospectOverdueCount }}</div>
                </div>

                <!-- Group: Software Handover -->
                <div class="group-box group-software"
                        :class="{'selected': selectedGroup === 'software-handover'}"
                        @click="setSelectedGroup('software-handover')">
                    <div class="group-title">Software Handover</div>
                    <div class="group-count">{{ $softwareHandoverNew + $softwareHandoverCompleted }}</div>
                </div>

                <!-- Group: Hardware Handover -->
                <div class="group-box group-hardware"
                        :class="{'selected': selectedGroup === 'hardware-handover'}"
                        @click="setSelectedGroup('hardware-handover')">
                    <div class="group-title">Hardware Handover</div>
                    <div class="group-count">{{ $hardwareHandoverNew + $hardwareHandoverCompleted }}</div>
                </div>

                <!-- Group: No Respond Leads -->
                <div class="group-box group-no-respond"
                        :class="{'selected': selectedGroup === 'no-respond-leads'}"
                        @click="setSelectedGroup('no-respond-leads')">
                    <div class="group-title">No Respond Leads</div>
                    <div class="group-count">{{ $followUpLead + $transferLead }}</div>
                </div>

                <!-- Group: Others -->
                <div class="group-box group-others"
                        :class="{'selected': selectedGroup === 'others'}"
                        @click="setSelectedGroup('others')">
                    <div class="group-title">Others</div>
                    {{-- <div class="group-count">{{ $hrdfFollowUpToday + $hrdfFollowUpOverdue }}</div> --}}
                    <div class="group-count">0</div>
                </div>
            </div>
        </div>

        <div class="content-column">
            <div class="category-container" x-show="selectedGroup === 'demo-session'">
                <div class="stat-box demo-today"
                        :class="{'selected': selectedStat === 'demo-today'}"
                        @click="setSelectedStat('demo-today')">
                    <div class="stat-info">
                        <div class="stat-label">Demo Today</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $demoTodayCount }}</div>
                    </div>
                </div>
                <div class="stat-box demo-tmr"
                        :class="{'selected': selectedStat === 'demo-tmr'}"
                        @click="setSelectedStat('demo-tmr')">
                    <div class="stat-info">
                        <div class="stat-label">Demo Tomorrow</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $demoTomorrowCount }}</div>
                    </div>
                </div>
            </div>

            <!-- PROSPECT REMINDER -->
            <div class="category-container" x-show="selectedGroup === 'prospect-reminder'">
                <div class="stat-box pr-today"
                        :class="{'selected': selectedStat === 'pr-today'}"
                        @click="setSelectedStat('pr-today')">
                    <div class="stat-info">
                        <div class="stat-label">Today</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $prospectTodayCount }}</div>
                    </div>
                </div>
                <div class="stat-box pr-overdue"
                        :class="{'selected': selectedStat === 'pr-overdue'}"
                        @click="setSelectedStat('pr-overdue')">
                    <div class="stat-info">
                        <div class="stat-label">Overdue</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $prospectOverdueCount }}</div>
                    </div>
                </div>
            </div>

            <!-- SOFTWARE HANDOVER -->
            <div class="category-container" x-show="selectedGroup === 'software-handover'">
                <div class="stat-box software-handover-pending"
                        :class="{'selected': selectedStat === 'software-handover-pending'}"
                        @click="setSelectedStat('software-handover-pending')">
                    <div class="stat-info">
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $softwareHandoverNew }}</div>
                    </div>
                </div>
                <div class="stat-box software-handover-completed"
                        :class="{'selected': selectedStat === 'software-handover-completed'}"
                        @click="setSelectedStat('software-handover-completed')">
                    <div class="stat-info">
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $softwareHandoverCompleted }}</div>
                    </div>
                </div>
            </div>

            <!-- HARDWARE HANDOVER -->
            <div class="category-container" x-show="selectedGroup === 'hardware-handover'">
                <div class="stat-box hardware-handover-pending"
                        :class="{'selected': selectedStat === 'hardware-handover-pending'}"
                        @click="setSelectedStat('hardware-handover-pending')">
                    <div class="stat-info">
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $hardwareHandoverNew }}</div>
                    </div>
                </div>
                <div class="stat-box hardware-handover-completed"
                        :class="{'selected': selectedStat === 'hardware-handover-completed'}"
                        @click="setSelectedStat('hardware-handover-completed')">
                    <div class="stat-info">
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $hardwareHandoverCompleted }}</div>
                    </div>
                </div>
            </div>

            <!-- NO RESPOND LEADS -->
            <div class="category-container" x-show="selectedGroup === 'no-respond-leads'">
                <div class="stat-box transfer-lead"
                        :class="{'selected': selectedStat === 'transfer-lead'}"
                        @click="setSelectedStat('transfer-lead')">
                    <div class="stat-info">
                        <div class="stat-label">Transfer Leads (37 Days)</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $transferLead }}</div>
                    </div>
                </div>
                <div class="stat-box follow-up-lead"
                        :class="{'selected': selectedStat === 'follow-up-lead'}"
                        @click="setSelectedStat('follow-up-lead')">
                    <div class="stat-info">
                        <div class="stat-label">Follow Up - Leads (97 Days)</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $followUpLead }}</div>
                    </div>
                </div>
            </div>

            <!-- OTHERS -->
            <div class="category-container" x-show="selectedGroup === 'others'">
                <div class="stat-box debtor-follow-up-today"
                        :class="{'selected': selectedStat === 'debtor-follow-up-today'}"
                        @click="setSelectedStat('debtor-follow-up-today')">
                    <div class="stat-info">
                        <div class="stat-label">Debtor Follow Up (Today)</div>
                    </div>
                    <div class="stat-count">
                        {{-- <div class="stat-count">{{ $debtorFollowUpToday }}</div> --}}
                        <div class="stat-count">0</div>
                    </div>
                </div>
                <div class="stat-box debtor-follow-up-overdue"
                        :class="{'selected': selectedStat === 'debtor-follow-up-overdue'}"
                        @click="setSelectedStat('debtor-follow-up-overdue')">
                    <div class="stat-info">
                        <div class="stat-label">Debtor Follow Up (Overdue)</div>
                    </div>
                    <div class="stat-count">
                        {{-- <div class="stat-count">{{ $debtorFollowUpOverdue }}</div> --}}
                        <div class="stat-count">0</div>
                    </div>
                </div>
                <div class="stat-box hrdf-follow-up-today"
                        :class="{'selected': selectedStat === 'hrdf-follow-up-today'}"
                        @click="setSelectedStat('hrdf-follow-up-today')">
                    <div class="stat-info">
                        <div class="stat-label">HRDF Follow Up (Today)</div>
                    </div>
                    <div class="stat-count">
                        {{-- <div class="stat-count">{{ $hrdfFollowUpToday }}</div> --}}
                        <div class="stat-count">0</div>
                    </div>
                </div>
                <div class="stat-box hrdf-follow-up-overdue"
                        :class="{'selected': selectedStat === 'hrdf-follow-up-overdue'}"
                        @click="setSelectedStat('hrdf-follow-up-overdue')">
                    <div class="stat-info">
                        <div class="stat-label">HRDF Follow Up (Overdue)</div>
                    </div>
                    <div class="stat-count">
                        {{-- <div class="stat-count">{{ $hrdfFollowUpOverdue }}</div> --}}
                        <div class="stat-count">0</div>
                    </div>
                </div>
            </div>

            <br>
            <div class="content-area">
                <!-- Display hint message when nothing is selected -->
                <div class="hint-message" x-show="selectedGroup === null || selectedStat === null" x-transition>
                    <h3 x-text="selectedGroup === null ? 'Select a group to continue' : 'Select a category to view leads'"></h3>
                    <p x-text="selectedGroup === null ? 'Click on any of the group boxes to see categories' : 'Click on any of the category boxes to display the corresponding information'"></p>
                </div>

                <!-- Content panels for each table (keep the same as your original) -->
                <div x-show="selectedStat === 'demo-today'" x-transition>
                    <livewire:salesperson-dashboard.demo-today-table />
                </div>
                <div x-show="selectedStat === 'demo-tmr'" x-transition>
                    <livewire:salesperson-dashboard.demo-tmr-table />
                </div>

                <!-- Prospect Reminder -->
                <div x-show="selectedStat === 'pr-today'" x-transition>
                    <livewire:salesperson-dashboard.pr-today-salesperson-table />
                </div>
                <div x-show="selectedStat === 'pr-overdue'" x-transition>
                    <livewire:salesperson-dashboard.pr-overdue-salesperson-table />
                </div>

                <!-- Software Handover -->
                <div x-show="selectedStat === 'software-handover-pending'" x-transition>
                    <livewire:salesperson-dashboard.software-handover-new />
                </div>
                <div x-show="selectedStat === 'software-handover-completed'" x-transition>
                    <livewire:salesperson-dashboard.software-handover-completed />
                </div>

                <!-- Hardware Handover -->
                <div x-show="selectedStat === 'hardware-handover-pending'" x-transition>
                    <livewire:salesperson-dashboard.hardware-handover-new />
                </div>
                <div x-show="selectedStat === 'hardware-handover-completed'" x-transition>
                    <livewire:salesperson-dashboard.hardware-handover-completed />
                </div>

                <!-- No Respond Leads -->
                <div x-show="selectedStat === 'transfer-lead'" x-transition>
                    <livewire:salesperson-dashboard.transfer-lead />
                </div>
                <div x-show="selectedStat === 'follow-up-lead'" x-transition>
                    <livewire:salesperson-dashboard.follow-up-lead />
                </div>

                <!-- Others -->
                <div x-show="selectedStat === 'debtor-follow-up-today'" x-transition>
                    <livewire:salesperson-dashboard.debtor-follow-up-today-table />
                </div>
                <div x-show="selectedStat === 'debtor-follow-up-overdue'" x-transition>
                    <livewire:salesperson-dashboard.debtor-follow-up-overdue-table />
                </div>
                <div x-show="selectedStat === 'hrdf-follow-up-today'" x-transition>
                    <livewire:salesperson-dashboard.hrdf-follow-up-today-table />
                </div>
                <div x-show="selectedStat === 'hrdf-follow-up-overdue'" x-transition>
                    <livewire:salesperson-dashboard.hrdf-follow-up-overdue-table />
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to reset the lead owner component
        window.resetLeadOwner = function() {
            const container = document.getElementById('lead-owner-container');
            if (container && container.__x) {
                container.__x.$data.selectedGroup = null;
                container.__x.$data.selectedStat = null;
                console.log('Lead owner reset via global function');
            }
        };

        // Listen for our custom reset event
        window.addEventListener('reset-lead-dashboard', function() {
            window.resetLeadOwner();
        });
    });
</script>
