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

    .stat-box.selected {
        transform: translateY(-3px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        position: relative;
    }

    .stat-box.selected::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 50%;
        transform: translateX(-50%);
        width: 0;
        height: 0;
        border-left: 8px solid transparent;
        border-right: 8px solid transparent;
        border-top: 8px solid white;
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
use App\Models\HardwareHandover;

// Define queries for different statuses
$newCount = HardwareHandover::where('status', 'New')->count();
$pendingStockCount = HardwareHandover::where('status', 'Pending Stock')->count();
$pendingMigrationCount = HardwareHandover::where('status', 'Pending Migration')->count();
$completedCount = HardwareHandover::where('status', 'Completed')->count();
$draftRejectedCount = HardwareHandover::whereIn('status', ['Draft', 'Rejected'])->count();

// Calculate combined pending count
$pendingTaskCount = $newCount + $pendingStockCount + $pendingMigrationCount;
@endphp

<div id="hardware-handover-container" class="hardware-handover-container"
     x-data="{
         selectedStat: null,

         setSelectedStat(value) {
             console.log('Setting hardware stat to:', value);
             if (this.selectedStat === value) {
                 this.selectedStat = null;
             } else {
                 this.selectedStat = value;
             }
         },

         init() {
             console.log('Hardware handover Alpine component initialized');
             this.selectedStat = null;
         }
     }"
     x-init="init()">
    <div class="dashboard-stats">
        <div class="stat-box all"
             :class="{'selected': selectedStat === 'all'}">
             {{-- @click="setSelectedStat('all')" --}}
            <div class="stat-count">{{ HardwareHandover::count() }}</div>
            <div class="stat-label">All</div>
        </div>

        <div class="stat-box new"
             :class="{'selected': selectedStat === 'new'}"
             @click="setSelectedStat('new')"
             style="cursor: pointer;">
            <div class="stat-count">{{ $newCount }}</div>
            <div class="stat-label">New</div>
        </div>

        <div class="stat-box pending-stock"
             :class="{'selected': selectedStat === 'pending-stock'}"
             @click="setSelectedStat('pending-stock')"
             style="cursor: pointer;">
            <div class="stat-count">{{ $pendingStockCount }}</div>
            <div class="stat-label">Pending Stock</div>
        </div>

        <div class="stat-box pending-migration"
             :class="{'selected': selectedStat === 'pending-migration'}"
             @click="setSelectedStat('pending-migration')"
             style="cursor: pointer;">
            <div class="stat-count">{{ $pendingMigrationCount }}</div>
            <div class="stat-label">Pending Migration</div>
        </div>

        <div class="stat-box completed"
             :class="{'selected': selectedStat === 'completed'}"
             @click="setSelectedStat('completed')"
             style="cursor: pointer;">
            <div class="stat-count">{{ $completedCount }}</div>
            <div class="stat-label">Completed</div>
        </div>

        <div class="stat-box draft-rejected"
             :class="{'selected': selectedStat === 'draft-rejected'}"
             @click="setSelectedStat('draft-rejected')"
             style="cursor: pointer;">
            <div class="stat-count">{{ $draftRejectedCount }}</div>
            <div class="stat-label">Draft / Rejected</div>
        </div>
    </div>

    <div class="hint-message" x-show="selectedStat === null" x-transition>
        <div class="icon-container" wire:poll.10s>
            <i class="bi bi-hand-index"></i>
        </div>
        <h3>Select a category to view data</h3>
        <p>Click on any of the stat boxes above to display the corresponding information</p>
    </div>

    <div x-show="selectedStat === 'new' || selectedStat === 'all'" x-transition :key="selectedStat + '-new'">
        <br>
        @livewire('hardware-handover-new')
    </div>

    <div x-show="selectedStat === 'pending-stock' || selectedStat === 'all'" x-transition :key="selectedStat + '-stock'">
        <br>
        @livewire('hardware-handover-pending-stock')
    </div>

    <div x-show="selectedStat === 'pending-migration' || selectedStat === 'all'" x-transition :key="selectedStat + '-migration'">
        <br>
        @livewire('hardware-handover-pending-migration')
    </div>

    <div x-show="selectedStat === 'completed' || selectedStat === 'all'" x-transition :key="selectedStat + '-completed'">
        <br>
        @livewire('hardware-handover-completed')
    </div>

    <div x-show="selectedStat === 'draft-rejected' || selectedStat === 'all'" x-transition :key="selectedStat + '-rejected'">
        <br>
        @livewire('hardware-handover-addon')
    </div>
</div>

<script>
    // When the page loads, setup handlers for this component
    document.addEventListener('DOMContentLoaded', function() {
        // Function to reset the hardware component
        window.resetHardwareHandover = function() {
            const container = document.getElementById('hardware-handover-container');
            if (container && container.__x) {
                container.__x.$data.selectedStat = null;
                console.log('Hardware handover reset via global function');
            }
        };

        // Listen for our custom reset event
        window.addEventListener('reset-hardware-dashboard', function() {
            window.resetHardwareHandover();
        });
    });
</script>
