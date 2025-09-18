<!-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/admin-renewal-raw-data.blade.php -->
<x-filament-panels::page>
    <style>
        /* Force sticky headers for Filament tables */
        .fi-ta-table {
            position: relative;
        }

        .fi-ta-table thead {
            position: sticky !important;
            top: 0 !important;
            z-index: 20 !important;
        }

        .fi-ta-table thead th {
            position: sticky !important;
            top: 0 !important;
            z-index: 20 !important;
            background-color: rgb(250, 250, 250) !important;
            border-bottom: 2px solid #e5e7eb !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
        }

        /* Dark mode support */
        .dark .fi-ta-table thead th {
            background-color: rgb(17 24 39) !important;
            border-bottom: 2px solid rgb(55 65 81) !important;
            color: rgb(229 231 235) !important;
        }

        /* Table container with fixed height */
        .fi-ta-content {
            max-height: calc(100vh - 250px) !important;
            overflow: auto !important;
        }

        /* Ensure proper scrolling */
        .fi-ta-ctn {
            overflow: visible !important;
        }

        /* Fix for filter dropdowns to appear above sticky headers */
        [x-data*="dropdown"], .fi-dropdown-panel {
            z-index: 30 !important;
        }
    </style>

    {{ $this->table }}
</x-filament-panels::page>
