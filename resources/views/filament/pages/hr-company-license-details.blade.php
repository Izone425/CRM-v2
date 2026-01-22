<x-filament-panels::page>
    @if($handoverId || $softwareHandoverId)
        <livewire:hr-admin-dashboard.company-license-details-container
            :handover-id="$handoverId"
            :software-handover-id="$softwareHandoverId"
        />
    @else
        <div class="p-6 text-center bg-white rounded-lg shadow">
            <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">No Company Selected</h3>
            <p class="mt-2 text-sm text-gray-500">Please select a company from the All Licenses page to view details.</p>
            <a href="{{ url('/admin/hr-license') }}" class="inline-flex items-center mt-4 text-sm font-medium text-primary-600 hover:text-primary-700">
                Go to All Licenses
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        </div>
    @endif
</x-filament-panels::page>
