<div>
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900">Follow Up Today USD (Non-Reseller)</h3>
        <div class="flex items-center space-x-2">
            <button wire:click="refreshTable" type="button"
                class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="-ml-0.5 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Refresh
            </button>
            @if($lastRefreshTime)
                <span class="text-xs text-gray-500">
                    Last refreshed: {{ $lastRefreshTime }}
                </span>
            @endif
        </div>
    </div>
    {{ $this->table }}
</div>