{{-- Raw Data Content (placeholder for future implementation) --}}
<div class="space-y-6">
    <div class="p-10 bg-white rounded-2xl shadow-xl border border-gray-200 text-center">
        <div class="flex flex-col items-center justify-center">
            <div class="flex items-center justify-center w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full mb-6">
                <i class="bi bi-table text-4xl text-gray-400"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">Raw Data View</h3>
            <p class="text-gray-500 mb-8 max-w-md">
                This section will display detailed raw data tables for TimeTec HR product metrics.
                Coming soon!
            </p>
            <div class="flex items-center gap-4">
                <button wire:click="toggleDashboard('Dashboard')"
                        class="px-6 py-3 text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-md hover:shadow-lg">
                    <i class="bi bi-speedometer2 mr-2"></i>
                    Back to Dashboard
                </button>
            </div>
        </div>
    </div>

    {{-- Example: You can add Livewire tables here in the future --}}
    {{-- @livewire('hr-raw-data-table') --}}
</div>
