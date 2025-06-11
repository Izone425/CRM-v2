<style>
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

<div class="hardware-handover-container">
    <livewire:software-handover-stats />
    <br>
    <livewire:software-handover-new />
    <br>
    <livewire:software-handover-kick-off-reminder/>
    <br>
    <livewire:software-handover-pending-license />
    <br>
    <livewire:software-handover-completed />
    <br>
    <livewire:software-handover-addon />
</div>
