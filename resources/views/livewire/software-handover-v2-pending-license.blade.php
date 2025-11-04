<div class="p-4 bg-white rounded-lg shadow-lg" style="height: auto;" x-data="{ showRefresh: false }" @mouseenter="showRefresh = true" @mouseleave="showRefresh = false">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <h3 class="text-lg font-bold">Pending License Activation</h3>
            <div x-data="{ lastRefresh: '{{ now()->format('Y-m-d H:i:s') }}' }" class="relative">
                <button
                    wire:click="refreshTable"
                    wire:loading.attr="disabled"
                    x-show="showRefresh"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform scale-90"
                    x-transition:enter-end="opacity-100 transform scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 transform scale-100"
                    x-transition:leave-end="opacity-0 transform scale-90"
                    class="flex items-center px-3 py-1 text-sm font-medium transition-colors bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 tooltip"
                    title="Last refreshed: {{ $lastRefreshTime }}"
                >
                    <span wire:loading.remove wire:target="refreshTable">
                        <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </span>
                    <span wire:loading wire:target="refreshTable">
                        <svg class="w-4 h-4 mr-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                </button>
            </div>
        </div>

        <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getTableRecords()->total() }})</span>
    </div>
    <br>
    {{ $this->table }}
    @if ($this->getTableRecords()->total() > 0 && $this->getTableRecords()->lastPage() > 1)
        <div class="mt-4 text-sm text-center text-gray-600">
            Page {{ $this->getTableRecords()->currentPage() }} of {{ $this->getTableRecords()->lastPage() }}
        </div>
    @endif
</div>
