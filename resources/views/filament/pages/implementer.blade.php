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
        margin-bottom: 2px;
    }

    .group-desc {
        font-size: 12px;
        color: #6b7280;
    }

    .group-count {
        font-size: 20px;
        font-weight: bold;
    }

    /* GROUP COLORS */
    .group-project-status { border-top-color: #2563eb; }
    .group-project-status .group-count { color: #2563eb; }

    .group-license { border-top-color: #8b5cf6; }
    .group-license .group-count { color: #8b5cf6; }

    .group-migration { border-top-color: #10b981; }
    .group-migration .group-count { color: #10b981; }

    .group-follow-up { border-top-color: #f59e0b; }
    .group-follow-up .group-count { color: #f59e0b; }

    .group-ticketing { border-top-color: #ec4899; }
    .group-ticketing .group-count { color: #ec4899; }

    .group-new-request { border-top-color: #06b6d4; }
    .group-new-request .group-count { color: #06b6d4; }

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

    /* STAT BOX COLORS - PROJECT STATUS */
    .status-all { border-left: 4px solid #6b7280; }
    .status-all .stat-count { color: #6b7280; }

    .status-open { border-left: 4px solid #2563eb; }
    .status-open .stat-count { color: #2563eb; }

    .status-closed { border-left: 4px solid #10b981; }
    .status-closed .stat-count { color: #10b981; }

    .status-delay { border-left: 4px solid #f59e0b; }
    .status-delay .stat-count { color: #f59e0b; }

    .status-inactive { border-left: 4px solid #ef4444; }
    .status-inactive .stat-count { color: #ef4444; }

    /* STAT BOX COLORS - LICENSE CERTIFICATION */
    .license-pending { border-left: 4px solid #8b5cf6; }
    .license-pending .stat-count { color: #8b5cf6; }

    .license-completed { border-left: 4px solid #a855f7; }
    .license-completed .stat-count { color: #a855f7; }

    /* STAT BOX COLORS - DATA MIGRATION */
    .migration-pending { border-left: 4px solid #10b981; }
    .migration-pending .stat-count { color: #10b981; }

    .migration-completed { border-left: 4px solid #34d399; }
    .migration-completed .stat-count { color: #34d399; }

    /* STAT BOX COLORS - PROJECT FOLLOW UP */
    .follow-up-today { border-left: 4px solid #f59e0b; }
    .follow-up-today .stat-count { color: #f59e0b; }

    .follow-up-overdue { border-left: 4px solid #f97316; }
    .follow-up-overdue .stat-count { color: #f97316; }

    /* STAT BOX COLORS - TICKETING SYSTEM */
    .ticketing-today { border-left: 4px solid #ec4899; }
    .ticketing-today .stat-count { color: #ec4899; }

    .ticketing-overdue { border-left: 4px solid #d946ef; }
    .ticketing-overdue .stat-count { color: #d946ef; }

    /* STAT BOX COLORS - NEW REQUEST */
    .customization-pending { border-left: 4px solid #06b6d4; }
    .customization-pending .stat-count { color: #06b6d4; }

    .customization-completed { border-left: 4px solid #0ea5e9; }
    .customization-completed .stat-count { color: #0ea5e9; }

    .enhancement-pending { border-left: 4px solid #0284c7; }
    .enhancement-pending .stat-count { color: #0284c7; }

    .enhancement-completed { border-left: 4px solid #0369a1; }
    .enhancement-completed .stat-count { color: #0369a1; }

    /* Selected states for categories */
    .stat-box.selected.status-all { background-color: rgba(107, 114, 128, 0.05); border-left-width: 6px; }
    .stat-box.selected.status-open { background-color: rgba(37, 99, 235, 0.05); border-left-width: 6px; }
    .stat-box.selected.status-closed { background-color: rgba(16, 185, 129, 0.05); border-left-width: 6px; }
    .stat-box.selected.status-delay { background-color: rgba(245, 158, 11, 0.05); border-left-width: 6px; }
    .stat-box.selected.status-inactive { background-color: rgba(239, 68, 68, 0.05); border-left-width: 6px; }

    .stat-box.selected.license-pending { background-color: rgba(139, 92, 246, 0.05); border-left-width: 6px; }
    .stat-box.selected.license-completed { background-color: rgba(168, 85, 247, 0.05); border-left-width: 6px; }

    .stat-box.selected.migration-pending { background-color: rgba(16, 185, 129, 0.05); border-left-width: 6px; }
    .stat-box.selected.migration-completed { background-color: rgba(52, 211, 153, 0.05); border-left-width: 6px; }

    .stat-box.selected.follow-up-today { background-color: rgba(245, 158, 11, 0.05); border-left-width: 6px; }
    .stat-box.selected.follow-up-overdue { background-color: rgba(249, 115, 22, 0.05); border-left-width: 6px; }

    .stat-box.selected.ticketing-today { background-color: rgba(236, 72, 153, 0.05); border-left-width: 6px; }
    .stat-box.selected.ticketing-overdue { background-color: rgba(217, 70, 239, 0.05); border-left-width: 6px; }

    .stat-box.selected.customization-pending { background-color: rgba(6, 182, 212, 0.05); border-left-width: 6px; }
    .stat-box.selected.customization-completed { background-color: rgba(14, 165, 233, 0.05); border-left-width: 6px; }
    .stat-box.selected.enhancement-pending { background-color: rgba(2, 132, 199, 0.05); border-left-width: 6px; }
    .stat-box.selected.enhancement-completed { background-color: rgba(3, 105, 161, 0.05); border-left-width: 6px; }

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
    // Project Status Counts
    $allProjects = app(\App\Livewire\ImplementerDashboard\ImplementerProjectAll::class)
        ->getAllSoftwareHandover()
        ->count();

    $openProjects = app(\App\Livewire\ImplementerDashboard\ImplementerProjectOpen::class)
        ->getAllSoftwareHandover()
        ->count();

    $closedProjects = app(\App\Livewire\ImplementerDashboard\ImplementerProjectClosed::class)
        ->getAllSoftwareHandover()
        ->count();

    $delayProjects = app(\App\Livewire\ImplementerDashboard\ImplementerProjectDelay::class)
        ->getAllSoftwareHandover()
        ->count();

    $inactiveProjects = app(\App\Livewire\ImplementerDashboard\ImplementerProjectInactive::class)
        ->getAllSoftwareHandover()
        ->count();

    // License Certification Counts
    $pendingLicenseCount = app(\App\Livewire\ImplementerDashboard\ImplementerLicense::class)
        ->getOverdueSoftwareHandovers()
        ->count();

    $completedLicenseCount = app(\App\Livewire\ImplementerDashboard\ImplementerLicenseCompleted::class)
        ->getOverdueHardwareHandovers()
        ->count();

    // Data Migration Counts
    $pendingMigrationCount = app(\App\Livewire\ImplementerDashboard\ImplementerMigration::class)
        ->getOverdueHardwareHandovers()
        ->count();

    $completedMigrationCount = app(\App\Livewire\ImplementerDashboard\ImplementerMigrationCompleted::class)
        ->getOverdueHardwareHandovers()
        ->count();

    // Project Follow Up Counts
    $followUpToday = app(\App\Livewire\ImplementerDashboard\ImplementerFollowUpToday::class)
        ->getOverdueHardwareHandovers()
        ->count();

    $followUpOverdue = app(\App\Livewire\ImplementerDashboard\ImplementerFollowUpOverdue::class)
        ->getOverdueHardwareHandovers()
        ->count();

    // Ticketing System Counts
    $internalTicketsToday = 0; // Replace with actual count
    $internalTicketsOverdue = 0; // Replace with actual count
    $externalTicketsToday = 0; // Replace with actual count
    $externalTicketsOverdue = 0;

    // New Request Counts
    $customizationPending = 0; // Example count
    $customizationCompleted = 0;
    $enhancementPending = 0;
    $enhancementCompleted = 0;

    // Calculate totals for main categories
    $projectStatusTotal = $allProjects;
    $licenseTotal = $pendingLicenseCount + $completedLicenseCount;
    $migrationTotal = $pendingMigrationCount + $completedMigrationCount;
    $followUpTotal = $followUpToday + $followUpOverdue;
    $ticketingTotal = $internalTicketsToday + $internalTicketsOverdue + $externalTicketsToday + $externalTicketsOverdue;
    $requestTotal = $customizationPending + $customizationCompleted + $enhancementPending + $enhancementCompleted;
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

    <div class="dashboard-layout" wire:poll.300s>
        <!-- Left sidebar with main category groups -->
        <div class="group-column">
            <!-- NO1 - PROJECT STATUS -->
            <div class="group-box group-project-status"
                :class="{'selected': selectedGroup === 'project-status'}"
                @click="setSelectedGroup('project-status')">
                <div class="group-info">
                    <div class="group-title">Project Status</div>
                </div>
                <div class="group-count">{{ $projectStatusTotal }}</div>
            </div>

            <!-- NO2 - LICENSE CERTIFICATION -->
            <div class="group-box group-license"
                :class="{'selected': selectedGroup === 'license'}"
                @click="setSelectedGroup('license')">
                <div class="group-info">
                    <div class="group-title">License Certificate</div>
                </div>
                <div class="group-count">{{ $licenseTotal }}</div>
            </div>

            <!-- NO3 - DATA MIGRATION -->
            <div class="group-box group-migration"
                :class="{'selected': selectedGroup === 'migration'}"
                @click="setSelectedGroup('migration')">
                <div class="group-info">
                    <div class="group-title">Data Migration</div>
                </div>
                <div class="group-count">{{ $migrationTotal }}</div>
            </div>

            <!-- NO4 - PROJECT FOLLOW UP -->
            <div class="group-box group-follow-up"
                :class="{'selected': selectedGroup === 'follow-up'}"
                @click="setSelectedGroup('follow-up')">
                <div class="group-info">
                    <div class="group-title">Project Follow Up</div>
                </div>
                <div class="group-count">{{ $followUpTotal }}</div>
            </div>

            <!-- NO5 - TICKETING SYSTEM -->
            <div class="group-box group-ticketing"
                :class="{'selected': selectedGroup === 'ticketing'}"
                @click="setSelectedGroup('ticketing')">
                <div class="group-info">
                    <div class="group-title">Ticketing System</div>
                </div>
                <div class="group-count">{{ $ticketingTotal }}</div>
            </div>

            <!-- NO6 - NEW REQUEST -->
            <div class="group-box group-new-request"
                :class="{'selected': selectedGroup === 'new-request'}"
                @click="setSelectedGroup('new-request')">
                <div class="group-info">
                    <div class="group-title">New Request</div>
                </div>
                <div class="group-count">{{ $requestTotal }}</div>
            </div>
        </div>

        <!-- Right content column -->
        <div class="content-column">
            <!-- PROJECT STATUS Sub-tabs -->
            <div class="category-container" x-show="selectedGroup === 'project-status'" x-transition>
                <div class="stat-box status-all"
                    :class="{'selected': selectedStat === 'status-all'}"
                    @click="setSelectedStat('status-all')">
                    <div class="stat-info">
                        <div class="stat-label">All Projects</div>
                    </div>
                    <div class="stat-count">{{ $allProjects }}</div>
                </div>

                <div class="stat-box status-closed"
                    :class="{'selected': selectedStat === 'status-closed'}"
                    @click="setSelectedStat('status-closed')">
                    <div class="stat-info">
                        <div class="stat-label">Closed</div>
                    </div>
                    <div class="stat-count">{{ $closedProjects }}</div>
                </div>

                <div class="stat-box status-open"
                    :class="{'selected': selectedStat === 'status-open'}"
                    @click="setSelectedStat('status-open')">
                    <div class="stat-info">
                        <div class="stat-label">Open</div>
                    </div>
                    <div class="stat-count">{{ $openProjects }}</div>
                </div>

                <div class="stat-box status-delay"
                    :class="{'selected': selectedStat === 'status-delay'}"
                    @click="setSelectedStat('status-delay')">
                    <div class="stat-info">
                        <div class="stat-label">Delay</div>
                    </div>
                    <div class="stat-count">{{ $delayProjects }}</div>
                </div>

                <div class="stat-box status-inactive"
                    :class="{'selected': selectedStat === 'status-inactive'}"
                    @click="setSelectedStat('status-inactive')">
                    <div class="stat-info">
                        <div class="stat-label">Inactive</div>
                    </div>
                    <div class="stat-count">{{ $inactiveProjects }}</div>
                </div>
            </div>

            <!-- LICENSE CERTIFICATION Sub-tabs -->
            <div class="category-container" x-show="selectedGroup === 'license'" x-transition>
                <div class="stat-box license-pending"
                    :class="{'selected': selectedStat === 'license-pending'}"
                    @click="setSelectedStat('license-pending')">
                    <div class="stat-info">
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-count">{{ $pendingLicenseCount }}</div>
                </div>

                <div class="stat-box license-completed"
                    :class="{'selected': selectedStat === 'license-completed'}"
                    @click="setSelectedStat('license-completed')">
                    <div class="stat-info">
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-count">{{ $completedLicenseCount }}</div>
                </div>
            </div>

            <!-- DATA MIGRATION Sub-tabs -->
            <div class="category-container" x-show="selectedGroup === 'migration'" x-transition>
                <div class="stat-box migration-pending"
                    :class="{'selected': selectedStat === 'migration-pending'}"
                    @click="setSelectedStat('migration-pending')">
                    <div class="stat-info">
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-count">{{ $pendingMigrationCount }}</div>
                </div>

                <div class="stat-box migration-completed"
                    :class="{'selected': selectedStat === 'migration-completed'}"
                    @click="setSelectedStat('migration-completed')">
                    <div class="stat-info">
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-count">{{ $completedMigrationCount }}</div>
                </div>
            </div>

            <!-- PROJECT FOLLOW UP Sub-tabs -->
            <div class="category-container" x-show="selectedGroup === 'follow-up'" x-transition>
                <div class="stat-box follow-up-today"
                    :class="{'selected': selectedStat === 'follow-up-today'}"
                    @click="setSelectedStat('follow-up-today')">
                    <div class="stat-info">
                        <div class="stat-label">Today</div>
                    </div>
                    <div class="stat-count">{{ $followUpToday }}</div>
                </div>

                <div class="stat-box follow-up-overdue"
                    :class="{'selected': selectedStat === 'follow-up-overdue'}"
                    @click="setSelectedStat('follow-up-overdue')">
                    <div class="stat-info">
                        <div class="stat-label">Overdue</div>
                    </div>
                    <div class="stat-count">{{ $followUpOverdue }}</div>
                </div>
            </div>

            <!-- TICKETING SYSTEM Sub-tabs -->
            <div class="category-container" x-show="selectedGroup === 'ticketing'" x-transition>
                <div class="stat-box ticketing-today"
                    :class="{'selected': selectedStat === 'internal-today'}"
                    @click="setSelectedStat('internal-today')">
                    <div class="stat-info">
                        <div class="stat-label">Internal Today</div>
                    </div>
                    <div class="stat-count">{{ $internalTicketsToday ?? 0 }}</div>
                </div>

                <div class="stat-box ticketing-overdue"
                    :class="{'selected': selectedStat === 'internal-overdue'}"
                    @click="setSelectedStat('internal-overdue')">
                    <div class="stat-info">
                        <div class="stat-label">Internal Overdue</div>
                    </div>
                    <div class="stat-count">{{ $internalTicketsOverdue ?? 0 }}</div>
                </div>

                <div class="stat-box ticketing-today"
                    :class="{'selected': selectedStat === 'external-today'}"
                    @click="setSelectedStat('external-today')">
                    <div class="stat-info">
                        <div class="stat-label">External Today</div>
                    </div>
                    <div class="stat-count">{{ $externalTicketsToday ?? 0 }}</div>
                </div>

                <div class="stat-box ticketing-overdue"
                    :class="{'selected': selectedStat === 'external-overdue'}"
                    @click="setSelectedStat('external-overdue')">
                    <div class="stat-info">
                        <div class="stat-label">External Overdue</div>
                    </div>
                    <div class="stat-count">{{ $externalTicketsOverdue ?? 0 }}</div>
                </div>
            </div>

            <!-- NEW REQUEST Sub-tabs -->
            <div class="category-container" x-show="selectedGroup === 'new-request'" x-transition>
                <div class="stat-box customization-pending"
                    :class="{'selected': selectedStat === 'customization-pending'}"
                    @click="setSelectedStat('customization-pending')">
                    <div class="stat-info">
                        <div class="stat-label">Customization | Pending</div>
                    </div>
                    <div class="stat-count">{{ $customizationPending }}</div>
                </div>

                <div class="stat-box customization-completed"
                    :class="{'selected': selectedStat === 'customization-completed'}"
                    @click="setSelectedStat('customization-completed')">
                    <div class="stat-info">
                        <div class="stat-label">Customization | Completed</div>
                    </div>
                    <div class="stat-count">{{ $customizationCompleted }}</div>
                </div>

                <div class="stat-box enhancement-pending"
                    :class="{'selected': selectedStat === 'enhancement-pending'}"
                    @click="setSelectedStat('enhancement-pending')">
                    <div class="stat-info">
                        <div class="stat-label">Enhancement | Pending</div>
                    </div>
                    <div class="stat-count">{{ $enhancementPending }}</div>
                </div>

                <div class="stat-box enhancement-completed"
                    :class="{'selected': selectedStat === 'enhancement-completed'}"
                    @click="setSelectedStat('enhancement-completed')">
                    <div class="stat-info">
                        <div class="stat-label">Enhancement | Completed</div>
                    </div>
                    <div class="stat-count">{{ $enhancementCompleted }}</div>
                </div>
            </div>

            <!-- Content area for tables -->
            <div class="content-area">
                <!-- Display hint message when nothing is selected -->
                <div class="hint-message" x-show="selectedGroup === null || selectedStat === null" x-transition>
                    <h3 x-text="selectedGroup === null ? 'Select a category to continue' : 'Select a subcategory to view data'"></h3>
                    <p x-text="selectedGroup === null ? 'Click on any of the category boxes to see options' : 'Click on any of the subcategory boxes to display the corresponding information'"></p>
                </div>

                <!-- Content panels for each table -->
                <!-- PROJECT STATUS Tables -->
                <div x-show="selectedStat === 'status-all'" x-transition>
                    <div class="p-4">
                        <livewire:implementer-dashboard.implementer-project-all />
                    </div>
                </div>
                <div x-show="selectedStat === 'status-open'" x-transition>
                    <div class="p-4">
                        <livewire:implementer-dashboard.implementer-project-open />
                    </div>
                </div>
                <div x-show="selectedStat === 'status-closed'" x-transition>
                    <div class="p-4">
                        <livewire:implementer-dashboard.implementer-project-closed />
                    </div>
                </div>
                <div x-show="selectedStat === 'status-delay'" x-transition>
                    <div class="p-4">
                        <livewire:implementer-dashboard.implementer-project-delay />
                    </div>
                </div>
                <div x-show="selectedStat === 'status-inactive'" x-transition>
                    <div class="p-4">
                        <livewire:implementer-dashboard.implementer-project-inactive />
                    </div>
                </div>

                <!-- LICENSE CERTIFICATION Tables -->
                <div x-show="selectedStat === 'license-pending'" x-transition>
                    <div class="p-4">
                        <livewire:implementer-dashboard.implementer-license />
                    </div>
                </div>
                <div x-show="selectedStat === 'license-completed'" x-transition>
                    <div class="p-4">
                        <livewire:implementer-dashboard.implementer-license-completed />
                    </div>
                </div>

                <!-- DATA MIGRATION Tables -->
                <div x-show="selectedStat === 'migration-pending'" x-transition>
                    <div class="p-4">
                        <livewire:implementer-dashboard.implementer-migration />
                    </div>
                </div>
                <div x-show="selectedStat === 'migration-completed'" x-transition>
                    <div class="p-4">
                        <livewire:implementer-dashboard.implementer-migration-completed />
                    </div>
                </div>

                <!-- PROJECT FOLLOW UP Tables -->
                <div x-show="selectedStat === 'follow-up-today'" x-transition>
                    <div class="p-4">
                        <livewire:implementer-dashboard.implementer-follow-up-today />
                    </div>
                </div>
                <div x-show="selectedStat === 'follow-up-overdue'" x-transition>
                    <div class="p-4">
                        <livewire:implementer-dashboard.implementer-follow-up-overdue />
                    </div>
                </div>

                <!-- TICKETING SYSTEM Tables -->
                <div x-show="selectedStat === 'internal-today'" x-transition>
                    <div class="p-4">
                        {{-- <livewire:implementer-dashboard.internal-tickets-today /> --}}
                    </div>
                </div>
                <div x-show="selectedStat === 'internal-overdue'" x-transition>
                    <div class="p-4">
                        {{-- <livewire:implementer-dashboard.internal-tickets-overdue /> --}}
                    </div>
                </div>
                <div x-show="selectedStat === 'external-today'" x-transition>
                    <div class="p-4">
                        {{-- <livewire:implementer-dashboard.external-tickets-today /> --}}
                    </div>
                </div>
                <div x-show="selectedStat === 'external-overdue'" x-transition>
                    <div class="p-4">
                        {{-- <livewire:implementer-dashboard.external-tickets-overdue /> --}}
                    </div>
                </div>

                <!-- NEW REQUEST Tables -->
                <div x-show="selectedStat === 'customization-pending'" x-transition>
                    <div class="p-4">
                        {{-- <livewire:customization-pending-table /> --}}
                    </div>
                </div>
                <div x-show="selectedStat === 'customization-completed'" x-transition>
                    <div class="p-4">
                        {{-- <livewire:customization-completed-table /> --}}
                    </div>
                </div>
                <div x-show="selectedStat === 'enhancement-pending'" x-transition>
                    <div class="p-4">
                        {{-- <livewire:enhancement-pending-table /> --}}
                    </div>
                </div>
                <div x-show="selectedStat === 'enhancement-completed'" x-transition>
                    <div class="p-4">
                        {{-- <livewire:enhancement-completed-table /> --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to reset the implementer component
        window.resetImplementer = function() {
            const container = document.getElementById('implementer-container');
            if (container && container.__x) {
                container.__x.$data.selectedGroup = null;
                container.__x.$data.selectedStat = null;
                console.log('Implementer dashboard reset via global function');
            }
        };

        // Listen for our custom reset event
        window.addEventListener('reset-implementer-dashboard', function() {
            window.resetImplementer();
        });
    });
</script>
