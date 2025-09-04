<x-filament-panels::page>
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight fi-header-heading text-gray-950 dark:text-white sm:text-3xl">
                Debtor Aging Raw Data
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

    {{ $this->table }}
</x-filament-panels::page>
