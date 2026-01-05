<!-- Admin Renewal Non-Reseller Dashboard -->
<style>
    /* Container styling */
    .implementer-container {
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
        max-height: 95px;
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
        width: 100%;
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
        text-align: left;
    }

    .group-count {
        font-size: 24px;
        font-weight: bold;
        text-align: center;
        margin-top: 5px;
    }

    /* MYR GROUP COLORS - Orange Theme */
    .group-follow-up-myr { border-top-color: #f59e0b; }
    .group-follow-up-myr .group-count { color: #f59e0b; }
    .group-follow-up-myr.selected { background-color: rgba(245, 158, 11, 0.05); }

    /* MYR V2 GROUP COLORS - Amber Theme */
    .group-follow-up-myr-v2 { border-top-color: #d97706; }
    .group-follow-up-myr-v2 .group-count { color: #d97706; }
    .group-follow-up-myr-v2.selected { background-color: rgba(217, 119, 6, 0.05); }

    /* USD GROUP COLORS - Blue Theme */
    .group-follow-up-usd { border-top-color: #3b82f6; }
    .group-follow-up-usd .group-count { color: #3b82f6; }
    .group-follow-up-usd.selected { background-color: rgba(59, 130, 246, 0.05); }

    /* USD V2 GROUP COLORS - Indigo Theme */
    .group-follow-up-usd-v2 { border-top-color: #6366f1; }
    .group-follow-up-usd-v2 .group-count { color: #6366f1; }
    .group-follow-up-usd-v2.selected { background-color: rgba(99, 102, 241, 0.05); }

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

    /* MYR FOLLOW UP COLORS - Orange Theme */
    .follow-up-today-myr { border-left: 4px solid #f59e0b; }
    .follow-up-today-myr .stat-count { color: #f59e0b; }
    .stat-box.selected.follow-up-today-myr { background-color: rgba(245, 158, 11, 0.05); border-left-width: 6px; }

    .follow-up-overdue-myr { border-left: 4px solid #f97316; }
    .follow-up-overdue-myr .stat-count { color: #f97316; }
    .stat-box.selected.follow-up-overdue-myr { background-color: rgba(249, 115, 22, 0.05); border-left-width: 6px; }

    .follow-up-future-myr { border-left: 4px solid #ea580c; }
    .follow-up-future-myr .stat-count { color: #ea580c; }
    .stat-box.selected.follow-up-future-myr { background-color: rgba(234, 88, 12, 0.05); border-left-width: 6px; }

    /* MYR V2 FOLLOW UP COLORS - Amber Theme */
    .follow-up-today-myr-v2 { border-left: 4px solid #d97706; }
    .follow-up-today-myr-v2 .stat-count { color: #d97706; }
    .stat-box.selected.follow-up-today-myr-v2 { background-color: rgba(217, 119, 6, 0.05); border-left-width: 6px; }

    .follow-up-overdue-myr-v2 { border-left: 4px solid #b45309; }
    .follow-up-overdue-myr-v2 .stat-count { color: #b45309; }
    .stat-box.selected.follow-up-overdue-myr-v2 { background-color: rgba(180, 83, 9, 0.05); border-left-width: 6px; }

    .follow-up-future-myr-v2 { border-left: 4px solid #92400e; }
    .follow-up-future-myr-v2 .stat-count { color: #92400e; }
    .stat-box.selected.follow-up-future-myr-v2 { background-color: rgba(146, 64, 14, 0.05); border-left-width: 6px; }

    /* USD FOLLOW UP COLORS - Blue Theme */
    .follow-up-today-usd { border-left: 4px solid #3b82f6; }
    .follow-up-today-usd .stat-count { color: #3b82f6; }
    .stat-box.selected.follow-up-today-usd { background-color: rgba(59, 130, 246, 0.05); border-left-width: 6px; }

    .follow-up-overdue-usd { border-left: 4px solid #2563eb; }
    .follow-up-overdue-usd .stat-count { color: #2563eb; }
    .stat-box.selected.follow-up-overdue-usd { background-color: rgba(37, 99, 235, 0.05); border-left-width: 6px; }

    .follow-up-future-usd { border-left: 4px solid #1d4ed8; }
    .follow-up-future-usd .stat-count { color: #1d4ed8; }
    .stat-box.selected.follow-up-future-usd { background-color: rgba(29, 78, 216, 0.05); border-left-width: 6px; }

    /* USD V2 FOLLOW UP COLORS - Indigo Theme */
    .follow-up-today-usd-v2 { border-left: 4px solid #6366f1; }
    .follow-up-today-usd-v2 .stat-count { color: #6366f1; }
    .stat-box.selected.follow-up-today-usd-v2 { background-color: rgba(99, 102, 241, 0.05); border-left-width: 6px; }

    .follow-up-overdue-usd-v2 { border-left: 4px solid #4f46e5; }
    .follow-up-overdue-usd-v2 .stat-count { color: #4f46e5; }
    .stat-box.selected.follow-up-overdue-usd-v2 { background-color: rgba(79, 70, 229, 0.05); border-left-width: 6px; }

    .follow-up-future-usd-v2 { border-left: 4px solid #4338ca; }
    .follow-up-future-usd-v2 .stat-count { color: #4338ca; }
    .stat-box.selected.follow-up-future-usd-v2 { background-color: rgba(67, 56, 202, 0.05); border-left-width: 6px; }

        /* All MYR FOLLOW UP COLORS - Orange Theme (Darker) */
    .follow-up-all-myr { border-left: 4px solid #dc2626; }
    .follow-up-all-myr .stat-count { color: #dc2626; }
    .stat-box.selected.follow-up-all-myr { background-color: rgba(220, 38, 38, 0.05); border-left-width: 6px; }

    /* All MYR V2 FOLLOW UP COLORS - Amber Theme (Darker) */
    .follow-up-all-myr-v2 { border-left: 4px solid #7c2d12; }
    .follow-up-all-myr-v2 .stat-count { color: #7c2d12; }
    .stat-box.selected.follow-up-all-myr-v2 { background-color: rgba(124, 45, 18, 0.05); border-left-width: 6px; }

    /* All USD FOLLOW UP COLORS - Blue Theme (Darker) */
    .follow-up-all-usd { border-left: 4px solid #1e40af; }
    .follow-up-all-usd .stat-count { color: #1e40af; }
    .stat-box.selected.follow-up-all-usd { background-color: rgba(30, 64, 175, 0.05); border-left-width: 6px; }

    /* All USD V2 FOLLOW UP COLORS - Indigo Theme (Darker) */
    .follow-up-all-usd-v2 { border-left: 4px solid #312e81; }
    .follow-up-all-usd-v2 .stat-count { color: #312e81; }
    .stat-box.selected.follow-up-all-usd-v2 { background-color: rgba(49, 46, 129, 0.05); border-left-width: 6px; }

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
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            border-right: none;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .category-container {
            grid-template-columns: repeat(3, 1fr);
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

        .stat-box:hover,
        .group-box:hover {
            transform: none;
        }

        .stat-box.selected,
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
    // Admin Renewal Follow Up Counts MYR (Non-Reseller)
    $followUpTodayMYR = app(\App\Livewire\AdminRenewalDashboard\ArFollowUpTodayMyrNonReseller::class)
        ->getTodayRenewals()
        ->count();

    $followUpOverdueMYR = app(\App\Livewire\AdminRenewalDashboard\ArFollowUpOverdueMyrNonReseller::class)
        ->getOverdueRenewals()
        ->count();

    $followUpFutureMYR = app(\App\Livewire\AdminRenewalDashboard\ArFollowUpUpcomingMyrNonReseller::class)
        ->getIncomingRenewals()
        ->count();

    $followUpAllMYR = app(\App\Livewire\AdminRenewalDashboard\ArFollowUpAllMyrNonReseller::class)
        ->getOverdueRenewals()
        ->count();

    // Admin Renewal Follow Up Counts USD (Non-Reseller)
    $followUpTodayUSD = app(\App\Livewire\AdminRenewalDashboard\ArFollowUpTodayUsdNonReseller::class)
        ->getTodayRenewals()
        ->count();

    $followUpOverdueUSD = app(\App\Livewire\AdminRenewalDashboard\ArFollowUpOverdueUsdNonReseller::class)
        ->getOverdueRenewals()
        ->count();

    $followUpFutureUSD = app(\App\Livewire\AdminRenewalDashboard\ArFollowUpUpcomingUsdNonReseller::class)
        ->getIncomingRenewals()
        ->count();

    $followUpAllUSD = app(\App\Livewire\AdminRenewalDashboard\ArFollowUpAllUsdNonReseller::class)
        ->getOverdueRenewals()
        ->count();

    // Admin Renewal Follow Up Counts MYR V2 (Non-Reseller - Pending Payment)
    $followUpTodayMYRv2 = app(\App\Livewire\AdminRenewalDashboard\ArFollowUpTodayMyrV2NonReseller::class)
        ->getTodayRenewals()
        ->count();

    $followUpOverdueMYRv2 = app(\App\Livewire\AdminRenewalDashboard\ArFollowUpOverdueMyrV2NonReseller::class)
        ->getOverdueRenewals()
        ->count();

    $followUpFutureMYRv2 = app(\App\Livewire\AdminRenewalDashboard\ArFollowUpUpcomingMyrV2NonReseller::class)
        ->getIncomingRenewals()
        ->count();

    $followUpAllMYRv2 = app(\App\Livewire\AdminRenewalDashboard\ArFollowUpAllMyrV2NonReseller::class)
        ->getOverdueRenewals()
        ->count();

    // Admin Renewal Follow Up Counts USD V2 (Non-Reseller - Pending Payment)
    $followUpTodayUSDv2 = app(\App\Livewire\AdminRenewalDashboard\ArFollowUpTodayUsdV2NonReseller::class)
        ->getTodayRenewals()
        ->count();

    $followUpOverdueUSDv2 = app(\App\Livewire\AdminRenewalDashboard\ArFollowUpOverdueUsdV2NonReseller::class)
        ->getOverdueRenewals()
        ->count();

    $followUpFutureUSDv2 = app(\App\Livewire\AdminRenewalDashboard\ArFollowUpUpcomingUsdV2NonReseller::class)
        ->getIncomingRenewals()
        ->count();

    $followUpAllUSDv2 = app(\App\Livewire\AdminRenewalDashboard\ArFollowUpAllUsdV2NonReseller::class)
        ->getOverdueRenewals()
        ->count();

    // Calculate totals for both currencies
    $followUpTotalMYR = $followUpTodayMYR + $followUpOverdueMYR;
    $followUpTotalUSD = $followUpTodayUSD + $followUpOverdueUSD;
    $followUpTotalMYRv2 = $followUpTodayMYRv2 + $followUpOverdueMYRv2;
    $followUpTotalUSDv2 = $followUpTodayUSDv2 + $followUpOverdueUSDv2;
@endphp

<div id="implementer-container" class="implementer-container"
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

    <!-- Page Header -->
    <div class="mb-6" style= "padding: 30px 0px 30px 0px;">
        <h1 class="text-2xl font-bold text-gray-900">Renewal - End User</h1>
    </div>

    <div class="dashboard-layout" wire:poll.300s>
        <!-- Left sidebar with main category groups -->
        <div class="group-column">
            <!-- 1. ADMIN RENEWAL FOLLOW UP MYR (Orange Theme) -->
            <div class="group-box group-follow-up-myr"
                :class="{'selected': selectedGroup === 'follow-up-myr'}"
                @click="setSelectedGroup('follow-up-myr')">
                <div class="group-info">
                    <div class="group-title">Pending Confirmation <br>End User (MYR) </div>
                    <div class="group-count">{{ $followUpTotalMYR }}</div>
                </div>
            </div>

            <!-- 2. ADMIN RENEWAL FOLLOW UP MYR V2 (Amber Theme) -->
            <div class="group-box group-follow-up-myr-v2"
                :class="{'selected': selectedGroup === 'follow-up-myr-v2'}"
                @click="setSelectedGroup('follow-up-myr-v2')">
                <div class="group-info">
                    <div class="group-title">Pending Payment <br>End User (MYR) </div>
                    <div class="group-count">{{ $followUpTotalMYRv2 }}</div>
                </div>
            </div>

            <!-- 3. ADMIN RENEWAL FOLLOW UP USD (Blue Theme) -->
            <div class="group-box group-follow-up-usd"
                :class="{'selected': selectedGroup === 'follow-up-usd'}"
                @click="setSelectedGroup('follow-up-usd')">
                <div class="group-info">
                    <div class="group-title">Pending Confirmation <br>End User (USD) </div>
                    <div class="group-count">{{ $followUpTotalUSD }}</div>
                </div>
            </div>

            <!-- 4. ADMIN RENEWAL FOLLOW UP USD V2 (Indigo Theme) -->
            <div class="group-box group-follow-up-usd-v2"
                :class="{'selected': selectedGroup === 'follow-up-usd-v2'}"
                @click="setSelectedGroup('follow-up-usd-v2')">
                <div class="group-info">
                    <div class="group-title">Pending Payment <br>End User (USD)</div>
                    <div class="group-count">{{ $followUpTotalUSDv2 }}</div>
                </div>
            </div>
        </div>

        <!-- Right content column -->
        <div class="content-column">
            <!-- 1. MYR ADMIN RENEWAL FOLLOW UP Sub-tabs (Orange Theme) -->
            <div class="category-container" x-show="selectedGroup === 'follow-up-myr'" x-transition>
                <div class="stat-box follow-up-today-myr"
                    :class="{'selected': selectedStat === 'follow-up-today-myr'}"
                    @click="setSelectedStat('follow-up-today-myr')">
                    <div class="stat-info">
                        <div class="stat-label">Today</div>
                    </div>
                    <div class="stat-count">{{ $followUpTodayMYR }}</div>
                </div>

                <div class="stat-box follow-up-overdue-myr"
                    :class="{'selected': selectedStat === 'follow-up-overdue-myr'}"
                    @click="setSelectedStat('follow-up-overdue-myr')">
                    <div class="stat-info">
                        <div class="stat-label">Overdue</div>
                    </div>
                    <div class="stat-count">{{ $followUpOverdueMYR }}</div>
                </div>

                <div class="stat-box follow-up-future-myr"
                    :class="{'selected': selectedStat === 'follow-up-future-myr'}"
                    @click="setSelectedStat('follow-up-future-myr')">
                    <div class="stat-info">
                        <div class="stat-label">Next Follow Up</div>
                    </div>
                    <div class="stat-count">{{ $followUpFutureMYR }}</div>
                </div>

                <div class="stat-box follow-up-all-myr"
                    :class="{'selected': selectedStat === 'follow-up-all-myr'}"
                    @click="setSelectedStat('follow-up-all-myr')">
                    <div class="stat-info">
                        <div class="stat-label">All Follow Ups</div>
                    </div>
                    <div class="stat-count">{{ $followUpAllMYR }}</div>
                </div>
            </div>

            <!-- 2. MYR V2 ADMIN RENEWAL FOLLOW UP Sub-tabs (Amber Theme) -->
            <div class="category-container" x-show="selectedGroup === 'follow-up-myr-v2'" x-transition>
                <div class="stat-box follow-up-today-myr-v2"
                    :class="{'selected': selectedStat === 'follow-up-today-myr-v2'}"
                    @click="setSelectedStat('follow-up-today-myr-v2')">
                    <div class="stat-info">
                        <div class="stat-label">Today</div>
                    </div>
                    <div class="stat-count">{{ $followUpTodayMYRv2 }}</div>
                </div>

                <div class="stat-box follow-up-overdue-myr-v2"
                    :class="{'selected': selectedStat === 'follow-up-overdue-myr-v2'}"
                    @click="setSelectedStat('follow-up-overdue-myr-v2')">
                    <div class="stat-info">
                        <div class="stat-label">Overdue</div>
                    </div>
                    <div class="stat-count">{{ $followUpOverdueMYRv2 }}</div>
                </div>

                <div class="stat-box follow-up-future-myr-v2"
                    :class="{'selected': selectedStat === 'follow-up-future-myr-v2'}"
                    @click="setSelectedStat('follow-up-future-myr-v2')">
                    <div class="stat-info">
                        <div class="stat-label">Next Follow Up</div>
                    </div>
                    <div class="stat-count">{{ $followUpFutureMYRv2 }}</div>
                </div>

                <div class="stat-box follow-up-all-myr-v2"
                    :class="{'selected': selectedStat === 'follow-up-all-myr-v2'}"
                    @click="setSelectedStat('follow-up-all-myr-v2')">
                    <div class="stat-info">
                        <div class="stat-label">All Follow Ups</div>
                    </div>
                    <div class="stat-count">{{ $followUpAllMYRv2 }}</div>
                </div>
            </div>

            <!-- 3. USD ADMIN RENEWAL FOLLOW UP Sub-tabs (Blue Theme) -->
            <div class="category-container" x-show="selectedGroup === 'follow-up-usd'" x-transition>
                <div class="stat-box follow-up-today-usd"
                    :class="{'selected': selectedStat === 'follow-up-today-usd'}"
                    @click="setSelectedStat('follow-up-today-usd')">
                    <div class="stat-info">
                        <div class="stat-label">Today</div>
                    </div>
                    <div class="stat-count">{{ $followUpTodayUSD }}</div>
                </div>

                <div class="stat-box follow-up-overdue-usd"
                    :class="{'selected': selectedStat === 'follow-up-overdue-usd'}"
                    @click="setSelectedStat('follow-up-overdue-usd')">
                    <div class="stat-info">
                        <div class="stat-label">Overdue</div>
                    </div>
                    <div class="stat-count">{{ $followUpOverdueUSD }}</div>
                </div>

                <div class="stat-box follow-up-future-usd"
                    :class="{'selected': selectedStat === 'follow-up-future-usd'}"
                    @click="setSelectedStat('follow-up-future-usd')">
                    <div class="stat-info">
                        <div class="stat-label">Next Follow Up</div>
                    </div>
                    <div class="stat-count">{{ $followUpFutureUSD }}</div>
                </div>

                <div class="stat-box follow-up-all-usd"
                    :class="{'selected': selectedStat === 'follow-up-all-usd'}"
                    @click="setSelectedStat('follow-up-all-usd')">
                    <div class="stat-info">
                        <div class="stat-label">All Follow Ups</div>
                    </div>
                    <div class="stat-count">{{ $followUpAllUSD }}</div>
                </div>
            </div>

            <!-- 4. USD V2 ADMIN RENEWAL FOLLOW UP Sub-tabs (Indigo Theme) -->
            <div class="category-container" x-show="selectedGroup === 'follow-up-usd-v2'" x-transition>
                <div class="stat-box follow-up-today-usd-v2"
                    :class="{'selected': selectedStat === 'follow-up-today-usd-v2'}"
                    @click="setSelectedStat('follow-up-today-usd-v2')">
                    <div class="stat-info">
                        <div class="stat-label">Today</div>
                    </div>
                    <div class="stat-count">{{ $followUpTodayUSDv2 }}</div>
                </div>

                <div class="stat-box follow-up-overdue-usd-v2"
                    :class="{'selected': selectedStat === 'follow-up-overdue-usd-v2'}"
                    @click="setSelectedStat('follow-up-overdue-usd-v2')">
                    <div class="stat-info">
                        <div class="stat-label">Overdue</div>
                    </div>
                    <div class="stat-count">{{ $followUpOverdueUSDv2 }}</div>
                </div>

                <div class="stat-box follow-up-future-usd-v2"
                    :class="{'selected': selectedStat === 'follow-up-future-usd-v2'}"
                    @click="setSelectedStat('follow-up-future-usd-v2')">
                    <div class="stat-info">
                        <div class="stat-label">Next Follow Up</div>
                    </div>
                    <div class="stat-count">{{ $followUpFutureUSDv2 }}</div>
                </div>

                <div class="stat-box follow-up-all-usd-v2"
                    :class="{'selected': selectedStat === 'follow-up-all-usd-v2'}"
                    @click="setSelectedStat('follow-up-all-usd-v2')">
                    <div class="stat-info">
                        <div class="stat-label">All Follow Ups</div>
                    </div>
                    <div class="stat-count">{{ $followUpAllUSDv2 }}</div>
                </div>
            </div>

            <!-- Content area for tables -->
            <div class="content-area">
                <!-- Display hint message when nothing is selected -->
                <div class="hint-message" x-show="selectedGroup === null || selectedStat === null" x-transition>
                    <h3 x-text="selectedGroup === null ? 'Select a category to continue' : 'Select a subcategory to view data'"></h3>
                    <p x-text="selectedGroup === null ? 'Click on any of the category boxes to see options' : 'Click on any of the subcategory boxes to display the corresponding information'"></p>
                </div>

                <!-- 1. MYR ADMIN RENEWAL FOLLOW UP Tables (Non-Reseller) -->
                <div x-show="selectedStat === 'follow-up-today-myr'" x-transition>
                    <div class="p-4">
                        <livewire:admin-renewal-dashboard.ar-follow-up-today-myr-non-reseller />
                    </div>
                </div>
                <div x-show="selectedStat === 'follow-up-overdue-myr'" x-transition>
                    <div class="p-4">
                        <livewire:admin-renewal-dashboard.ar-follow-up-overdue-myr-non-reseller />
                    </div>
                </div>
                <div x-show="selectedStat === 'follow-up-future-myr'" x-transition>
                    <div class="p-4">
                        <livewire:admin-renewal-dashboard.ar-follow-up-upcoming-myr-non-reseller />
                    </div>
                </div>
                <div x-show="selectedStat === 'follow-up-all-myr'" x-transition>
                    <div class="p-4">
                        <livewire:admin-renewal-dashboard.ar-follow-up-all-myr-non-reseller />
                    </div>
                </div>

                <!-- 2. MYR V2 ADMIN RENEWAL FOLLOW UP Tables (Non-Reseller - Pending Payment) -->
                <div x-show="selectedStat === 'follow-up-today-myr-v2'" x-transition>
                    <div class="p-4">
                        <livewire:admin-renewal-dashboard.ar-follow-up-today-myr-v2-non-reseller />
                    </div>
                </div>
                <div x-show="selectedStat === 'follow-up-overdue-myr-v2'" x-transition>
                    <div class="p-4">
                        <livewire:admin-renewal-dashboard.ar-follow-up-overdue-myr-v2-non-reseller />
                    </div>
                </div>
                <div x-show="selectedStat === 'follow-up-future-myr-v2'" x-transition>
                    <div class="p-4">
                        <livewire:admin-renewal-dashboard.ar-follow-up-upcoming-myr-v2-non-reseller />
                    </div>
                </div>
                <div x-show="selectedStat === 'follow-up-all-myr-v2'" x-transition>
                    <div class="p-4">
                        <livewire:admin-renewal-dashboard.ar-follow-up-all-myr-v2-non-reseller />
                    </div>
                </div>

                <!-- 3. USD ADMIN RENEWAL FOLLOW UP Tables (Non-Reseller) -->
                <div x-show="selectedStat === 'follow-up-today-usd'" x-transition>
                    <div class="p-4">
                        <livewire:admin-renewal-dashboard.ar-follow-up-today-usd-non-reseller />
                    </div>
                </div>
                <div x-show="selectedStat === 'follow-up-overdue-usd'" x-transition>
                    <div class="p-4">
                        <livewire:admin-renewal-dashboard.ar-follow-up-overdue-usd-non-reseller />
                    </div>
                </div>
                <div x-show="selectedStat === 'follow-up-future-usd'" x-transition>
                    <div class="p-4">
                        <livewire:admin-renewal-dashboard.ar-follow-up-upcoming-usd-non-reseller />
                    </div>
                </div>
                <div x-show="selectedStat === 'follow-up-all-usd'" x-transition>
                    <div class="p-4">
                        <livewire:admin-renewal-dashboard.ar-follow-up-all-usd-non-reseller />
                    </div>
                </div>

                <!-- 4. USD V2 ADMIN RENEWAL FOLLOW UP Tables (Non-Reseller - Pending Payment) -->
                <div x-show="selectedStat === 'follow-up-today-usd-v2'" x-transition>
                    <div class="p-4">
                        <livewire:admin-renewal-dashboard.ar-follow-up-today-usd-v2-non-reseller />
                    </div>
                </div>
                <div x-show="selectedStat === 'follow-up-overdue-usd-v2'" x-transition>
                    <div class="p-4">
                        <livewire:admin-renewal-dashboard.ar-follow-up-overdue-usd-v2-non-reseller />
                    </div>
                </div>
                <div x-show="selectedStat === 'follow-up-future-usd-v2'" x-transition>
                    <div class="p-4">
                        <livewire:admin-renewal-dashboard.ar-follow-up-upcoming-usd-v2-non-reseller />
                    </div>
                </div>
                <div x-show="selectedStat === 'follow-up-all-usd-v2'" x-transition>
                    <div class="p-4">
                        <livewire:admin-renewal-dashboard.ar-follow-up-all-usd-v2-non-reseller />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to reset the admin renewal non-reseller component
        window.resetAdminRenewalNonReseller = function() {
            const container = document.getElementById('implementer-container');
            if (container && container.__x) {
                container.__x.$data.selectedGroup = null;
                container.__x.$data.selectedStat = null;
                console.log('Admin renewal non-reseller dashboard reset via global function');
            }
        };

        // Listen for our custom reset event
        window.addEventListener('reset-admin-renewal-non-reseller-dashboard', function() {
            window.resetAdminRenewalNonReseller();
        });
    });
</script>
