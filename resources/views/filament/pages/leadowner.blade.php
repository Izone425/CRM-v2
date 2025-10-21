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
        width: 230px;
    }

    .group-box {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 20px 15px;
        cursor: pointer;
        transition: all 0.2s;
        border-top: 4px solid transparent;  /* Changed from border-left to border-top */
        display: flex;
        flex-direction: column;  /* Changed from horizontal to vertical layout */
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
        transform: translateY(-3px);  /* Changed from translateX to translateY */
        background-color: #f9fafb;
    }

    .group-box.selected {
        background-color: #f9fafb;
        transform: translateY(-5px);  /* Changed from translateX to translateY */
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .group-title {
        font-size: 15px;
        font-weight: 600;
        margin-bottom: 8px;  /* Added margin bottom for spacing */
    }

    .group-count {
        font-size: 24px;  /* Increased font size for count */
        font-weight: bold;
    }

    /* Update color coding for different groups to use top border */
    .group-all { border-top-color: #64748b; border-left: none; }
    .group-new { border-top-color: #2563eb; border-left: none; }
    .group-active { border-top-color: #10b981; border-left: none; }
    .group-inactive { border-top-color: #f43f5e; border-left: none; }
    .group-salesperson { border-top-color: #8b5cf6; border-left: none; }

    /* Update group container to use grid layout for cards */
    .group-container {
        display: flex;
        flex-direction: column;
        align-items: flex-end; /* Align items to the right */
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

    /* Color coding for different groups */
    /* All Leads styles */
    .group-all { border-left-color: #64748b; }
    .group-all .group-count { color: #64748b; }

    .group-new { border-left-color: #2563eb; }
    .group-new .group-count { color: #2563eb; }

    .group-active { border-left-color: #10b981; }
    .group-active .group-count { color: #10b981; }

    .group-inactive { border-left-color: #f43f5e; }
    .group-inactive .group-count { color: #f43f5e; }

    .group-salesperson { border-left-color: #8b5cf6; }
    .group-salesperson .group-count { color: #8b5cf6; }

    /* All leads categories */
    .all-leads { border-left: 4px solid #64748b; }
    .all-leads .stat-count { color: #64748b; }

    /* Color coding for different stat boxes */
    .new-leads { border-left: 4px solid #2563eb; }
    .new-leads .stat-count { color: #2563eb; }

    .pending-leads { border-left: 4px solid #f59e0b; }
    .pending-leads .stat-count { color: #f59e0b; }

    .reminder-today { border-left: 4px solid #8b5cf6; }
    .reminder-today .stat-count { color: #8b5cf6; }

    .reminder-overdue { border-left: 4px solid #ef4444; }
    .reminder-overdue .stat-count { color: #ef4444; }

    .active-small { border-left: 4px solid #10b981; }
    .active-small .stat-count { color: #10b981; }

    .active-big { border-left: 4px solid #0ea5e9; }
    .active-big .stat-count { color: #0ea5e9; }

    .inactive-small1 { border-left: 4px solid #14b8a6; }
    .inactive-small1 .stat-count { color: #14b8a6; }

    .inactive-small2 { border-left: 4px solid #a855f7; }
    .inactive-small2 .stat-count { color: #a855f7; }

    .inactive-small { border-left: 4px solid #d946ef; }
    .inactive-small .stat-count { color: #d946ef; }

    .inactive-big1 { border-left: 4px solid #ec4899; }
    .inactive-big1 .stat-count { color: #ec4899; }

    .inactive-big2 { border-left: 4px solid #f43f5e; }
    .inactive-big2 .stat-count { color: #f43f5e; }

    .inactive-big { border-left: 4px solid #fb7185; }
    .inactive-big .stat-count { color: #fb7185; }

    .call-attempt-small { border-left: 4px solid #06b6d4; }
    .call-attempt-small .stat-count { color: #06b6d4; }

    .call-attempt-big { border-left: 4px solid #6366f1; }
    .call-attempt-big .stat-count { color: #6366f1; }

    .salesperson-small { border-left: 4px solid #22c55e; }
    .salesperson-small .stat-count { color: #22c55e; }

    .salesperson-big { border-left: 4px solid #84cc16; }
    .salesperson-big .stat-count { color: #84cc16; }

    /* Selected state styling */
    .stat-box.selected.all-leads { background-color: rgba(100, 116, 139, 0.05); border-left-width: 6px; }
    .stat-box.selected.new-leads { background-color: rgba(37, 99, 235, 0.05); border-left-width: 6px; }
    .stat-box.selected.pending-leads { background-color: rgba(245, 158, 11, 0.05); border-left-width: 6px; }
    .stat-box.selected.reminder-today { background-color: rgba(139, 92, 246, 0.05); border-left-width: 6px; }
    .stat-box.selected.reminder-overdue { background-color: rgba(244, 67, 54, 0.05); border-left-width: 6px; }
    .stat-box.selected.active-small { background-color: rgba(16, 185, 129, 0.05); border-left-width: 6px; }
    .stat-box.selected.active-big { background-color: rgba(14, 165, 233, 0.05); border-left-width: 6px; }
    .stat-box.selected.call-attempt-small { background-color: rgba(6, 182, 212, 0.05); border-left-width: 6px; }
    .stat-box.selected.call-attempt-big { background-color: rgba(99, 102, 241, 0.05); border-left-width: 6px; }
    .stat-box.selected.inactive-small1 { background-color: rgba(20, 184, 166, 0.05); border-left-width: 6px; }
    .stat-box.selected.inactive-big1 { background-color: rgba(236, 72, 153, 0.05); border-left-width: 6px; }
    .stat-box.selected.inactive-small2 { background-color: rgba(168, 85, 247, 0.05); border-left-width: 6px; }
    .stat-box.selected.inactive-big2 { background-color: rgba(244, 63, 94, 0.05); border-left-width: 6px; }
    .stat-box.selected.inactive-small { background-color: rgba(217, 70, 239, 0.05); border-left-width: 6px; }
    .stat-box.selected.inactive-big { background-color: rgba(251, 113, 133, 0.05); border-left-width: 6px; }
    .stat-box.selected.salesperson-small { background-color: rgba(34, 197, 94, 0.05); border-left-width: 6px; }
    .stat-box.selected.salesperson-big { background-color: rgba(132, 204, 22, 0.05); border-left-width: 6px; }

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

    $allLeadsCount = DB::table('leads')->count();

    $newLeadsCount = app(\App\Livewire\LeadownerDashboard\NewLeadTable::class)
        ->getPendingLeadsQuery()
        ->count();

    $apolloNewLeadsCount = app(\App\Livewire\LeadownerDashboard\ApolloNewLeadTable::class)
        ->getPendingLeadsQuery()
        ->count();

    $apolloPendingLeadsCount = app(\App\Livewire\LeadownerDashboard\ApolloPendingLeadTable::class)
        ->getNewLeadsQuery()
        ->count();

    $apolloReminderTodayCount = app(\App\Livewire\LeadownerDashboard\ApolloProspectReminderTodayTable::class)
        ->getProspectTodayQuery()
        ->count();

    $apolloReminderOverdueCount = app(\App\Livewire\LeadownerDashboard\ApolloProspectReminderOverdueTable::class)
        ->getProspectOverdueQuery()
        ->count();

    $pendingLeadsCount = app(\App\Livewire\LeadownerDashboard\PendingLeadTable::class)
        ->getNewLeadsQuery()
        ->count();

    $reminderTodayCount = app(\App\Livewire\LeadownerDashboard\ProspectReminderTodayTable::class)
        ->getProspectTodayQuery()
        ->count();

    $reminderOverdueCount = app(\App\Livewire\LeadownerDashboard\ProspectReminderOverdueTable::class)
        ->getProspectOverdueQuery()
        ->count();

    $activeSmallCompCount = app(\App\Livewire\LeadownerDashboard\ActiveSmallCompTable::class)
        ->getActiveSmallCompanyLeads()
        ->count();

    $activeBigCompCount = app(\App\Livewire\LeadownerDashboard\ActiveBigCompTable::class)
        ->getActiveBigCompanyLeads()
        ->count();

    $callAttemptSmallCount = app(\App\Livewire\LeadownerDashboard\CallAttemptSmallCompTable::class)
        ->getFollowUpSmallCompanyLeads()
        ->count();

    $callAttemptBigCount = app(\App\Livewire\LeadownerDashboard\CallAttemptBigCompTable::class)
        ->getFollowUpBigCompanyLeads()
        ->count();

    $salespersonSmallCount = app(\App\Livewire\LeadownerDashboard\SalespersonSmallCompTable::class)
        ->getActiveSmallCompanyLeadsWithSalesperson()
        ->count();

    $salespersonBigCount = app(\App\Livewire\LeadownerDashboard\SalespersonBigCompTable::class)
        ->getActiveBigCompanyLeadsWithSalesperson()
        ->count();

    $inactiveSmall1Count = app(\App\Livewire\LeadownerDashboard\InactiveSmallCompTable1::class)
        ->getInactiveSmallCompanyLeads()
        ->count();

    $inactiveBig1Count = app(\App\Livewire\LeadownerDashboard\InactiveBigCompTable1::class)
        ->getInactiveBigCompanyLeads()
        ->count();

    $inactiveSmall2Count = app(\App\Livewire\LeadownerDashboard\InactiveSmallCompTable2::class)
        ->getInactiveSmallCompanyLeads()
        ->count();

    $inactiveBig2Count = app(\App\Livewire\LeadownerDashboard\InactiveBigCompTable2::class)
        ->getInactiveSmallCompanyLeads()
        ->count();

    $inactiveSmallCount = app(\App\Livewire\LeadownerDashboard\InactiveSmallCompTable::class)
        ->getInactiveSmallCompanyLeads()
        ->count();

    $inactiveBigCount = app(\App\Livewire\LeadownerDashboard\InactiveBigCompTable::class)
        ->getInactiveBigCompanyLeads()
        ->count();
    @endphp

    <div id="lead-owner-container" class="lead-owner-container"
        x-data="{
            selectedGroup: 'new',
            selectedStat: 'new-leads',

            setSelectedGroup(value) {
                if (this.selectedGroup === value) {
                    this.selectedGroup = null;
                    this.selectedStat = null;
                } else {
                    this.selectedGroup = value;

                    // Set default category based on selected group
                    if (value === 'new') {
                        this.selectedStat = 'new-leads';
                    } else if (value === 'apollo-new') {
                        this.selectedStat = 'apollo-new-leads';
                    } else if (value === 'active') {
                        this.selectedStat = 'active-small';
                    } else if (value === 'inactive') {
                        this.selectedStat = 'inactive-small1';
                    } else if (value === 'salesperson') {
                        this.selectedStat = 'salesperson-small';
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
                    <div class="group-box group-all"
                         :class="{'selected': selectedGroup === 'all'}">
                        <div class="group-title">All Leads</div>
                        <div class="group-count">{{ $allLeadsCount }}</div>
                    </div>
                    <!-- Group 1: New Leads -->
                    <div class="group-box group-new"
                         :class="{'selected': selectedGroup === 'new'}"
                         @click="setSelectedGroup('new')">
                        <div class="group-title">New Leads</div>
                        <div class="group-count">{{ $newLeadsCount + $pendingLeadsCount + $reminderTodayCount + $reminderOverdueCount }}</div>
                    </div>

                    <div class="group-box group-new"
                         :class="{'selected': selectedGroup === 'apollo-new'}"
                         @click="setSelectedGroup('apollo-new')">
                        <div class="group-title">Apollo New Leads</div>
                        <div class="group-count">{{ $apolloNewLeadsCount + $apolloPendingLeadsCount + $apolloReminderTodayCount + $apolloReminderOverdueCount}}</div>
                    </div>

                    <!-- Group 2: Active Leads -->
                    <div class="group-box group-active"
                         :class="{'selected': selectedGroup === 'active'}"
                         @click="setSelectedGroup('active')">
                        <div class="group-title">Active Leads</div>
                        <div class="group-count">{{ $activeSmallCompCount + $activeBigCompCount + $callAttemptSmallCount + $callAttemptBigCount }}</div>
                    </div>

                    <!-- Group 3: Inactive Leads -->
                    <div class="group-box group-inactive"
                         :class="{'selected': selectedGroup === 'inactive'}"
                         @click="setSelectedGroup('inactive')">
                        <div class="group-title">InActive Leads</div>
                        <div class="group-count">{{ $inactiveSmall1Count + $inactiveBig1Count + $inactiveSmall2Count + $inactiveBig2Count }}</div>
                    </div>

                    <!-- Group 4: Salesperson -->
                    <div class="group-box group-salesperson"
                         :class="{'selected': selectedGroup === 'salesperson'}"
                         @click="setSelectedGroup('salesperson')">
                        <div class="group-title">SalesPerson</div>
                        <div class="group-count">{{ $salespersonSmallCount + $salespersonBigCount + $inactiveSmallCount + $inactiveBigCount }}</div>
                    </div>
                </div>
            </div>

            <div class="content-column">
                <!-- New Leads categories - VERTICAL -->
                <div class="category-container" x-show="selectedGroup === 'new'">
                    <div class="stat-box new-leads"
                            :class="{'selected': selectedStat === 'new-leads'}"
                            @click="setSelectedStat('new-leads')">
                        <div class="stat-info">
                            <div class="stat-label">New Leads</div>
                        </div>
                        <div class="stat-count">
                            <div class="stat-count">{{ $newLeadsCount }}</div>
                        </div>
                    </div>

                    <div class="stat-box pending-leads"
                            :class="{'selected': selectedStat === 'pending-leads'}"
                            @click="setSelectedStat('pending-leads')">
                        <div class="stat-info">
                            <div class="stat-label">My Pending Tasks</div>
                        </div>
                        <div class="stat-count">
                            <div class="stat-count">{{ $pendingLeadsCount }}</div>
                        </div>
                    </div>

                    <div class="stat-box reminder-today"
                         :class="{'selected': selectedStat === 'reminder-today'}"
                         @click="setSelectedStat('reminder-today')">
                        <div class="stat-info">
                            <div class="stat-label">Reminder (Today)</div>
                        </div>
                        <div class="stat-count">{{ $reminderTodayCount }}</div>
                    </div>

                    <div class="stat-box reminder-overdue"
                         :class="{'selected': selectedStat === 'reminder-overdue'}"
                         @click="setSelectedStat('reminder-overdue')">
                        <div class="stat-info">
                            <div class="stat-label">Reminder (Overdue)</div>
                        </div>
                        <div class="stat-count">{{ $reminderOverdueCount }}</div>
                    </div>
                </div>

                <div class="category-container" x-show="selectedGroup === 'apollo-new'">
                    <div class="stat-box new-leads"
                            :class="{'selected': selectedStat === 'apollo-new-leads'}"
                            @click="setSelectedStat('apollo-new-leads')">
                        <div class="stat-info">
                            <div class="stat-label">Apollo New Leads</div>
                        </div>
                        <div class="stat-count">
                            <div class="stat-count">{{ $apolloNewLeadsCount }}</div>
                        </div>
                    </div>

                    <div class="stat-box pending-leads"
                            :class="{'selected': selectedStat === 'apollo-pending-leads'}"
                            @click="setSelectedStat('apollo-pending-leads')">
                        <div class="stat-info">
                            <div class="stat-label">Apollo Pending Leads</div>
                        </div>
                        <div class="stat-count">{{ $apolloPendingLeadsCount }}</div>
                    </div>

                    <div class="stat-box reminder-today"
                        :class="{'selected': selectedStat === 'apollo-reminder-today'}"
                        @click="setSelectedStat('apollo-reminder-today')">
                        <div class="stat-info">
                            <div class="stat-label">Apollo Reminder (Today)</div>
                        </div>
                        <div class="stat-count">{{ $apolloReminderTodayCount }}</div>
                    </div>

                    <div class="stat-box reminder-overdue"
                        :class="{'selected': selectedStat === 'apollo-reminder-overdue'}"
                        @click="setSelectedStat('apollo-reminder-overdue')">
                        <div class="stat-info">
                            <div class="stat-label">Apollo Reminder (Overdue)</div>
                        </div>
                        <div class="stat-count">{{ $apolloReminderOverdueCount }}</div>
                    </div>
                </div>

                <!-- Active Leads categories - VERTICAL -->
                <div class="category-container" x-show="selectedGroup === 'active'">
                    <div class="stat-box active-small"
                         :class="{'selected': selectedStat === 'active-small'}"
                         @click="setSelectedStat('active-small')">
                        <div class="stat-info">
                            <div class="stat-label">Active | Small Company</div>
                        </div>
                        <div class="stat-count">{{ $activeSmallCompCount }}</div>
                    </div>

                    <div class="stat-box active-big"
                         :class="{'selected': selectedStat === 'active-big'}"
                         @click="setSelectedStat('active-big')">
                        <div class="stat-info">
                            <div class="stat-label">Active | Big Company</div>
                        </div>
                        <div class="stat-count">{{ $activeBigCompCount }}</div>
                    </div>

                    <div class="stat-box call-attempt-small"
                         :class="{'selected': selectedStat === 'call-attempt-small'}"
                         @click="setSelectedStat('call-attempt-small')">
                        <div class="stat-info">
                            <div class="stat-label">Call Attempt | Small Company</div>
                        </div>
                        <div class="stat-count">{{ $callAttemptSmallCount }}</div>
                    </div>

                    <div class="stat-box call-attempt-big"
                         :class="{'selected': selectedStat === 'call-attempt-big'}"
                         @click="setSelectedStat('call-attempt-big')">
                        <div class="stat-info">
                            <div class="stat-label">Call Attempt | Big Company</div>
                        </div>
                        <div class="stat-count">{{ $callAttemptBigCount }}</div>
                    </div>
                </div>

                <!-- Inactive Leads categories - VERTICAL -->
                <div class="category-container" x-show="selectedGroup === 'inactive'">
                    <div class="stat-box inactive-small1"
                         :class="{'selected': selectedStat === 'inactive-small1'}"
                         @click="setSelectedStat('inactive-small1')">
                        <div class="stat-info">
                            <div class="stat-label">InActive | Small Company</div>
                            <div class="stat-label">Follow Up 1</div>
                        </div>
                        <div class="stat-count">{{ $inactiveSmall1Count }}</div>
                    </div>

                    <div class="stat-box inactive-big1"
                         :class="{'selected': selectedStat === 'inactive-big1'}"
                         @click="setSelectedStat('inactive-big1')">
                        <div class="stat-info">
                            <div class="stat-label">InActive | Big Company</div>
                            <div class="stat-label">Follow Up 1</div>
                        </div>
                        <div class="stat-count">{{ $inactiveBig1Count }}</div>
                    </div>

                    <div class="stat-box inactive-small2"
                         :class="{'selected': selectedStat === 'inactive-small2'}"
                         @click="setSelectedStat('inactive-small2')">
                        <div class="stat-info">
                            <div class="stat-label">InActive | Small Company</div>
                            <div class="stat-label">Follow Up 2</div>
                        </div>
                        <div class="stat-count">{{ $inactiveSmall2Count }}</div>
                    </div>

                    <div class="stat-box inactive-big2"
                         :class="{'selected': selectedStat === 'inactive-big2'}"
                         @click="setSelectedStat('inactive-big2')">
                        <div class="stat-info">
                            <div class="stat-label">InActive | Big Company</div>
                            <div class="stat-label">Follow Up 2</div>
                        </div>
                        <div class="stat-count">{{ $inactiveBig2Count }}</div>
                    </div>
                </div>

                <!-- Salesperson categories - VERTICAL -->
                <div class="category-container" x-show="selectedGroup === 'salesperson'">
                    <div class="stat-box salesperson-small"
                         :class="{'selected': selectedStat === 'salesperson-small'}"
                         @click="setSelectedStat('salesperson-small')">
                        <div class="stat-info">
                            <div class="stat-label">SalesPerson | Active</div>
                            <div class="stat-label">Small Company</div>
                        </div>
                        <div class="stat-count">{{ $salespersonSmallCount }}</div>
                    </div>

                    <div class="stat-box salesperson-big"
                         :class="{'selected': selectedStat === 'salesperson-big'}"
                         @click="setSelectedStat('salesperson-big')">
                        <div class="stat-info">
                            <div class="stat-label">SalesPerson | Active</div>
                            <div class="stat-label">Big Company</div>
                        </div>
                        <div class="stat-count">{{ $salespersonBigCount }}</div>
                    </div>

                    <div class="stat-box inactive-small"
                            :class="{'selected': selectedStat === 'inactive-small'}"
                            @click="setSelectedStat('inactive-small')">
                        <div class="stat-info">
                            <div class="stat-label">SalesPerson | InActive</div>
                            <div class="stat-label">Small Company</div>
                        </div>
                        <div class="stat-count">{{ $inactiveSmallCount }}</div>
                    </div>

                    <div class="stat-box inactive-big"
                            :class="{'selected': selectedStat === 'inactive-big'}"
                            @click="setSelectedStat('inactive-big')">
                        <div class="stat-info">
                            <div class="stat-label">SalesPerson | InActive</div>
                            <div class="stat-label">Big Company</div>
                        </div>
                        <div class="stat-count">{{ $inactiveBigCount }}</div>
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
                    <div x-show="selectedStat === 'new-leads'" x-transition :key="selectedStat + '-new-leads'">
                        <livewire:leadowner-dashboard.new-lead-table />
                    </div>

                    <div x-show="selectedStat === 'apollo-new-leads'" x-transition :key="selectedStat + '-apollo-new-leads'">
                        <livewire:leadowner-dashboard.apollo-new-lead-table />
                    </div>

                    <div x-show="selectedStat === 'apollo-new-leads'" x-transition :key="selectedStat + '-apollo-new-leads'">
                        <livewire:leadowner-dashboard.apollo-new-lead-table />
                    </div>

                    <div x-show="selectedStat === 'apollo-pending-leads'" x-transition :key="selectedStat + '-apollo-pending-leads'">
                        <livewire:leadowner-dashboard.apollo-pending-lead-table />
                    </div>

                    <div x-show="selectedStat === 'apollo-reminder-today'" x-transition :key="selectedStat + '-apollo-reminder-today'">
                        <livewire:leadowner-dashboard.apollo-prospect-reminder-today-table />
                    </div>

                    <div x-show="selectedStat === 'apollo-reminder-overdue'" x-transition :key="selectedStat + '-apollo-reminder-overdue'">
                        <livewire:leadowner-dashboard.apollo-prospect-reminder-overdue-table />
                    </div>

                    <div x-show="selectedStat === 'pending-leads'" x-transition :key="selectedStat + '-pending-leads'">
                        <livewire:leadowner-dashboard.pending-lead-table />
                    </div>

                    <!-- Include all your other table panels here... -->
                    <div x-show="selectedStat === 'reminder-today'" x-transition :key="selectedStat + '-reminder-today'">
                        <livewire:leadowner-dashboard.prospect-reminder-today-table />
                    </div>

                    <div x-show="selectedStat === 'reminder-overdue'" x-transition :key="selectedStat + '-reminder-overdue'">
                        <livewire:leadowner-dashboard.prospect-reminder-overdue-table />
                    </div>

                    <div x-show="selectedStat === 'active-small'" x-transition :key="selectedStat + '-active-small'">
                        <livewire:leadowner-dashboard.active-small-comp-table />
                    </div>

                    <div x-show="selectedStat === 'active-big'" x-transition :key="selectedStat + '-active-big'">
                        <livewire:leadowner-dashboard.active-big-comp-table />
                    </div>

                    <div x-show="selectedStat === 'call-attempt-small'" x-transition :key="selectedStat + '-call-attempt-small'">
                        <livewire:leadowner-dashboard.call-attempt-small-comp-table />
                    </div>

                    <div x-show="selectedStat === 'call-attempt-big'" x-transition :key="selectedStat + '-call-attempt-big'">
                        <livewire:leadowner-dashboard.call-attempt-big-comp-table />
                    </div>

                    <div x-show="selectedStat === 'salesperson-small'" x-transition :key="selectedStat + '-salesperson-small'">
                        <livewire:leadowner-dashboard.salesperson-small-comp-table />
                    </div>

                    <div x-show="selectedStat === 'salesperson-big'" x-transition :key="selectedStat + '-salesperson-big'">
                        <livewire:leadowner-dashboard.salesperson-big-comp-table />
                    </div>

                    <div x-show="selectedStat === 'inactive-small1'" x-transition :key="selectedStat + '-inactive-small1'">
                        <livewire:leadowner-dashboard.inactive-small-comp-table1 />
                    </div>

                    <div x-show="selectedStat === 'inactive-big1'" x-transition :key="selectedStat + '-inactive-big1'">
                        <livewire:leadowner-dashboard.inactive-big-comp-table1 />
                    </div>

                    <div x-show="selectedStat === 'inactive-small2'" x-transition :key="selectedStat + '-inactive-small2'">
                        <livewire:leadowner-dashboard.inactive-small-comp-table2 />
                    </div>

                    <div x-show="selectedStat === 'inactive-big2'" x-transition :key="selectedStat + '-inactive-big2'">
                        <livewire:leadowner-dashboard.inactive-big-comp-table2 />
                    </div>

                    <div x-show="selectedStat === 'inactive-small'" x-transition :key="selectedStat + '-inactive-small'">
                        <livewire:leadowner-dashboard.inactive-small-comp-table />
                    </div>

                    <div x-show="selectedStat === 'inactive-big'" x-transition :key="selectedStat + '-inactive-big'">
                        <livewire:leadowner-dashboard.inactive-big-comp-table />
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
