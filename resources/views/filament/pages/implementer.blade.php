<style>
    .hint-message {
        text-align: center;
        padding: 2rem;
        background-color: #f9fafb;
        border-radius: 0.5rem;
        border: 1px dashed #d1d5db;
        margin: 1rem 0;
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
        margin-bottom: 0.5rem;
    }

    .hint-message p {
        color: #6b7280;
    }

    .hardware-handover-container {
        grid-column: 1 / -1; /* Span all columns in a grid */
        width: 100%;
    }
    .fi-ta-ctn .py-4 {
        padding-top: .5rem !important;
        padding-bottom: .5rem !important;
    }

    .dashboard-stats {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 15px;
        margin-bottom: 20px;
    }

    .stat-box {
        background-color: white;
        border-radius: 8px;
        padding: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        text-align: center;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .stat-box:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .stat-count {
        font-size: 28px;
        font-weight: bold;
        margin: 5px 0;
    }

    .stat-label {
        color: #6b7280;
        font-size: 14px;
    }

    /* Color coding for different statuses */
    .new { border-top: 4px solid #2563eb; }
    .new .stat-count { color: #2563eb; }

    .pending-stock { border-top: 4px solid #f59e0b; }
    .pending-stock .stat-count { color: #f59e0b; }

    .pending-migration { border-top: 4px solid #8b5cf6; }
    .pending-migration .stat-count { color: #8b5cf6; }

    .completed { border-top: 4px solid #10b981; }
    .completed .stat-count { color: #10b981; }

    .all { border-top: 4px solid #6b7280; }
    .all .stat-count { color: #6b7280; }

    .draft-rejected { border-top: 4px solid #ef4444; }
    .draft-rejected .stat-count { color: #ef4444; }

    /* Responsive adjustments */
    @media (max-width: 1200px) {
        .dashboard-stats {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 640px) {
        .dashboard-stats {
            grid-template-columns: repeat(2, 1fr);
        }
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
    <!-- Move stats directly here instead of using a separate component -->
    <div class="dashboard-stats">
        <div class="stat-box all"
            :class="{'selected': selectedStat === 'pending-task'}">
            {{-- @click="setSelectedStat('pending-task')" --}}
        <div class="stat-count">{{ $all }}</div>
        <div class="stat-label">All</div>
        </div>

        <div class="stat-box new"
            :class="{'selected': selectedStat === 'new'}">
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

    <div class="hint-message" x-show="selectedStat === null" x-transition>
        <div class="icon-container" wire:poll.10s>
            <i class="bi bi-hand-index"></i>
    </div>
        <h3>Select a category to view data</h3>
        <p>Click on any of the stat boxes above to display the corresponding information</p>
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
