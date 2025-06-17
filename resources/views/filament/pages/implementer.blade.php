<style>
    /* General hint message styling */
    .hint-message {
        text-align: center;
        background-color: #f9fafb;
        border-radius: 0.5rem;
        border: 1px dashed #d1d5db;
        height: 530px;
    }

    .hint-message .icon-container {
        display: flex;
        justify-content: center;
        margin-bottom: 1rem;
    }

    .hint-message i {
        font-size: 3rem;
        color: #6b7280;
    }

    .hint-message h3 {
        font-size: 1.25rem;
        font-weight: 600;
        color: #374151;
        margin-top: 15rem;
        margin-bottom: 0.5rem;
    }

    .hint-message p {
        color: #6b7280;
        min-height: 340px;
    }

    /* Container styling */
    .hardware-handover-container {
        grid-column: 1 / -1; /* Span all columns in a grid */
        width: 100%;
    }

    .fi-ta-ctn .py-4 {
        padding-top: .5rem !important;
        padding-bottom: .5rem !important;
    }

    /* New dashboard layout structure */
    .dashboard-container {
        display: flex;
        gap: 20px;
        width: 100%;
    }

    .stats-sidebar {
        flex: 0.5 /* Change from 0 0 250px to 0.5 to make it grow */
        max-width: 50%; /* Cap at 50% of the container width */
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .content-area {
        flex: 2; /* Keep flex: 1 to match the sidebar */
        max-width: 85%; /* Cap at 85% of the container width */
        min-width: 0; /* Prevent overflow */
    }

    /* Adjust grid for wider sidebar */
    .dashboard-stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr); /* Keep 2-column layout */
        gap: 15px; /* Increase gap slightly for more spacing */
        margin-bottom: 20px;
    }

    /* Make the boxes a bit taller to fill the space better */
    .stat-box {
        background-color: white;
        width: 300px;
        height: 75px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        text-align: center;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border-top: 4px solid transparent; /* Changed to top border */
        min-height: 50px; /* Set minimum height */
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    /* Hover and selected states */
    .stat-box:hover {
        background-color: #f9fafb;
        transform: translateX(3px); /* Move slightly right instead of up */
    }

    .stat-box.selected {
        background-color: #f9fafb;
        transform: translateX(5px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.15);
    }

    .stat-count {
        font-size: 24px;
        font-weight: bold;
        margin: 5px 0;
    }

    .stat-label {
        color: #6b7280;
        font-size: 14px;
        font-weight: 500;
    }

    /* Color coding for different statuses - change from top border to left border */
    .new { border-left: 4px solid #2563eb; }
    .new .stat-count { color: #2563eb; }

    .pending-stock { border-left: 4px solid #f59e0b; }
    .pending-stock .stat-count { color: #f59e0b; }

    .pending-migration { border-left: 4px solid #8b5cf6; }
    .pending-migration .stat-count { color: #8b5cf6; }

    .completed { border-left: 4px solid #10b981; }
    .completed .stat-count { color: #10b981; }

    .all { border-left: 4px solid #6b7280; }
    .all .stat-count { color: #6b7280; }

    .draft-rejected { border-left: 4px solid #ef4444; }
    .draft-rejected .stat-count { color: #ef4444; }

    /* Add more visual distinction for selected state */
    .stat-box.selected.new { background-color: rgba(37, 99, 235, 0.05); border-left-width: 6px; }
    .stat-box.selected.pending-stock { background-color: rgba(245, 158, 11, 0.05); border-left-width: 6px; }
    .stat-box.selected.pending-migration { background-color: rgba(139, 92, 246, 0.05); border-left-width: 6px; }
    .stat-box.selected.completed { background-color: rgba(16, 185, 129, 0.05); border-left-width: 6px; }
    .stat-box.selected.all { background-color: rgba(107, 114, 128, 0.05); border-left-width: 6px; }
    .stat-box.selected.draft-rejected { background-color: rgba(239, 68, 68, 0.05); border-left-width: 6px; }

    /* Responsive adjustments */
    @media (max-width: 1200px) {
        .dashboard-container {
            flex-direction: column;
        }

        .stats-sidebar {
            flex: none;
            width: 100%;
        }

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            flex-direction: row;
        }

        .stat-box:hover, .stat-box.selected {
            transform: translateY(-3px); /* Change to vertical movement for grid layout */
        }

        /* Switch border back to top for responsive grid */
        .stat-box {
            border-left: none;
            border-top: 4px solid transparent;
        }

        .new { border-top: 4px solid #2563eb; border-left: none; }
        .pending-stock { border-top: 4px solid #f59e0b; border-left: none; }
        .pending-migration { border-top: 4px solid #8b5cf6; border-left: none; }
        .completed { border-top: 4px solid #10b981; border-left: none; }
        .all { border-top: 4px solid #6b7280; border-left: none; }
        .draft-rejected { border-top: 4px solid #ef4444; border-left: none; }

        .stat-box.selected.new { border-top-width: 6px; border-left: none; }
        .stat-box.selected.pending-stock { border-top-width: 6px; border-left: none; }
        .stat-box.selected.pending-migration { border-top-width: 6px; border-left: none; }
        .stat-box.selected.completed { border-top-width: 6px; border-left: none; }
        .stat-box.selected.all { border-top-width: 6px; border-left: none; }
        .stat-box.selected.draft-rejected { border-top-width: 6px; border-left: none; }
    }

    @media (max-width: 640px) {
        .dashboard-stats {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    /* Content area styling */
    .content-area {
        /* background: #ffffff; */
        border-radius: 8px;
        /* box-shadow: 0 1px 3px rgba(0,0,0,0.05); */
    }

    /* Table adjustments */
    .content-area .fi-ta {
        margin-top: 0;
    }

    /* Animation for tab switching */
    [x-transition] {
        transition: all 0.2s ease-out;
    }

    /* Remove excess padding in tables */
    .content-area .fi-ta-content {
        padding: 0.75rem !important;
    }
</style>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

@php
// Calculate counts directly in the blade template
use App\Models\SoftwareHandover;
use App\Models\User;

// Get the selected user from session or default to current user
$selectedUser = session('selectedUser') ?? auth()->user()->id;

// Build base queries with user filtering
$baseQuery = SoftwareHandover::query();

// Apply user filtering based on selected user
if ($selectedUser === 'all-implementer') {
    // Show handovers for all implementers - no additional filtering
} elseif (is_numeric($selectedUser)) {
    $user = User::find($selectedUser);

    if ($user && ($user->role_id === 4 || $user->role_id === 5)) {
        $baseQuery->where('implementer', $user->name);
    }
} else {
    $currentUser = auth()->user();

    if ($currentUser->role_id === 4 || $currentUser->role_id === 5) {
        $baseQuery->where('implementer', $currentUser->name);
    }
}

// Define queries for Pending Kick Off
$pendingMigrationQuery = clone $baseQuery;
$pendingMigrationCount = $pendingMigrationQuery
    ->whereIn('status', ['Completed'])
    ->where('data_migrated', false)
    ->where(function ($q) {
        // $q->where('id', '>=', 561);
    })->count();

// Define queries for Pending License
$pendingLicenseQuery = clone $baseQuery;
$pendingLicenseCount = $pendingLicenseQuery
    ->whereIn('status', ['Completed'])
    ->whereNull('license_certification_id')
    ->where(function ($q) {
        // $q->where('id', '>=', 561);
    })->count();

// Define queries for Completed and Draft/Rejected
$completedMigrationQuery = clone $baseQuery;
$completedMigrationCount = $completedMigrationQuery
    ->whereIn('status', ['Completed'])
    ->where('data_migrated', true)
    ->where(function ($q) {
        // $q->where('id', '>=', 561);
    })->count();

$completedLicenseQuery = clone $baseQuery;
$completedLicenseCount = $completedLicenseQuery
    ->whereIn('status', ['Completed'])
    ->whereNotNull('license_certification_id')
    ->where(function ($q) {
        // $q->where('id', '>=', 561);
    })->count();

// Calculate combined pending count
$pendingTaskCount = $pendingMigrationCount + $pendingLicenseCount;
$all = $pendingMigrationCount + $pendingLicenseCount + $completedMigrationCount + $completedLicenseCount;
@endphp

<div id="software-handover-container" class="hardware-handover-container"
     x-data="{
         selectedStat: null,

         setSelectedStat(value) {
             console.log('Setting software stat to:', value);
             if (this.selectedStat === value) {
                 this.selectedStat = null;
             } else {
                 this.selectedStat = value;
             }
         },

         init() {
             console.log('Software handover Alpine component initialized');
             this.selectedStat = null;
         }
     }"
     x-init="init()">

    <!-- New container structure -->
    <div class="dashboard-container">
        <!-- Left sidebar with stats -->
        <div class="stats-sidebar">
            <div class="stat-box all">
                <div class="stat-count">{{ $all }}</div>
                <div class="stat-label">All</div>
            </div>

            <div class="stat-box new">
                <div class="stat-count">{{ $pendingTaskCount }}</div>
                <div class="stat-label">Pending Task</div>
            </div>

            <div class="stat-box pending-stock"
                :class="{'selected': selectedStat === 'pending-kick-off'}"
                @click="setSelectedStat('pending-kick-off')"
                style="cursor: pointer;">
                <div class="stat-count">{{ $pendingMigrationCount }}</div>
                <div class="stat-label">Pending Data Migration</div>
            </div>

            <div class="stat-box pending-migration"
                :class="{'selected': selectedStat === 'pending-license'}"
                @click="setSelectedStat('pending-license')"
                style="cursor: pointer;">
                <div class="stat-count">{{ $pendingLicenseCount }}</div>
                <div class="stat-label">Pending License Certification</div>
            </div>

            <div class="stat-box completed"
                :class="{'selected': selectedStat === 'completed'}"
                @click="setSelectedStat('completed')"
                style="cursor: pointer;">
                <div class="stat-count">{{ $completedMigrationCount }}</div>
                <div class="stat-label">Completed Data Migration</div>
            </div>

            <div class="stat-box draft-rejected"
                :class="{'selected': selectedStat === 'draft-rejected'}"
                @click="setSelectedStat('draft-rejected')"
                style="cursor: pointer;">
                <div class="stat-count">{{ $completedLicenseCount }}</div>
                <div class="stat-label">Completed License Certification</div>
            </div>
        </div>

        <!-- Right content area -->
        <div class="content-area">
            <div class="hint-message" x-show="selectedStat === null" x-transition>
                <h3>Select a category to view data</h3>
                <p>Click on any of the stat boxes to display the corresponding information</p>
            </div>

            <div x-show="selectedStat === 'new' || selectedStat === 'pending-task'" x-transition :key="selectedStat + '-new'">
                <br>
                @livewire('software-handover-new')
            </div>

            <div x-show="selectedStat === 'pending-kick-off' || selectedStat === 'pending-task'" x-transition :key="selectedStat + '-kick-off'">
                <br>
                @livewire('implementer-migration')
            </div>

            <div x-show="selectedStat === 'pending-license' || selectedStat === 'pending-task'" x-transition :key="selectedStat + '-license'">
                <br>
                @livewire('implementer-license')
            </div>

            <div x-show="selectedStat === 'completed'" x-transition :key="selectedStat + '-completed'">
                <br>
                @livewire('implementer-migration-completed')
            </div>

            <div x-show="selectedStat === 'draft-rejected'" x-transition :key="selectedStat + '-rejected'">
                <br>
                @livewire('implementer-license-completed')
            </div>
        </div>
    </div>
</div>

<script>
    // When the page loads, setup handlers for this component
    document.addEventListener('DOMContentLoaded', function() {
        // Function to reset the software component
        window.resetSoftwareHandover = function() {
            const container = document.getElementById('software-handover-container');
            if (container && container.__x) {
                container.__x.$data.selectedStat = null;
                console.log('Software handover reset via global function');
            }
        };

        // Listen for our custom reset event
        window.addEventListener('reset-software-dashboard', function() {
            window.resetSoftwareHandover();
        });
    });
</script>
