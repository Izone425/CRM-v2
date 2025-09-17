{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/debtor-aging-process-data.blade.php --}}
<x-filament-panels::page>
    <style>
        .fi-ta-table {
            width: 100% !important;
        }
        .fi-ta-panel {
            width: 100% !important;
        }
        .fi-ta-content {
            overflow-x: auto;
        }
    </style>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight fi-header-heading text-gray-950 dark:text-white sm:text-3xl">
                Debtor Aging Process Data
            </h1>
        </div>

        <div class="text-right">
            <div class="text-base font-medium text-gray-900 dark:text-white">
                Total Outstanding Amount
            </div>
            <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                {{ $this->getTotalOutstandingAmount() }}
            </div>
        </div>
    </div>

    <div class="w-full">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
