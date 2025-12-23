{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/search-license.blade.php --}}
<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Forms Container with Tabs -->
        {{ $this->form }}

        <!-- Action Buttons -->
        <div class="flex flex-wrap gap-3 mt-4">
            @if($hasSearched && !empty($searchResults))
                <x-filament::button
                    color="gray"
                    wire:click="clearSearch"
                    icon="heroicon-o-x-mark"
                    size="sm"
                >
                    Clear Search Results
                </x-filament::button>
            @endif

            @if($hasProjectSearched && !empty($projectResults))
                <x-filament::button
                    color="gray"
                    wire:click="clearProjectSearch"
                    icon="heroicon-o-x-mark"
                    size="sm"
                >
                    Clear Project Results
                </x-filament::button>
            @endif

            @if($hasLeadSearched && !empty($leadResults))
                <x-filament::button
                    color="gray"
                    wire:click="clearLeadSearch"
                    icon="heroicon-o-x-mark"
                    size="sm"
                >
                    Clear Lead Results
                </x-filament::button>
            @endif

            @if($hasCalculated)
                <x-filament::button
                    color="gray"
                    wire:click="clearCalculator"
                    icon="heroicon-o-x-mark"
                    size="sm"
                >
                    Clear Calculator
                </x-filament::button>
            @endif
        </div>

        <!-- Calculator Result Display -->
        @if($hasCalculated && $calculatorResult)
            <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 mb-4 bg-blue-100 rounded-full">
                        <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <h3 class="mb-2 text-2xl font-bold text-gray-900">{{ $calculatorResult }}</h3>
                    <p class="text-gray-600">From today to the selected expiry date</p>
                </div>
            </div>
        @endif

        <!-- Search Results -->
        @if($hasSearched)
            @if(!empty($searchResults))
                <div class="space-y-6">
                    @foreach($searchResults as $result)
                        <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm">
                            <h3 class="mb-4 text-lg font-semibold text-gray-900">
                                {{ $result['f_company_name'] }}
                                <span class="text-sm font-normal text-gray-500">(ID: {{ $result['f_company_id'] }})</span>
                            </h3>

                            <div class="license-content">
                                {!! $result['license_html'] !!}
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-12 text-center bg-white border border-gray-200 rounded-lg shadow-sm">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-gray-100 rounded-lg">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 20.4a7.962 7.962 0 01-8-7.934c0-4.411 3.589-8 8-8s8 3.589 8 8a7.962 7.962 0 01-2 5.291z"></path>
                        </svg>
                    </div>
                    <h3 class="mb-2 text-lg font-medium text-gray-900">No Results Found</h3>
                    <p class="text-gray-500">No license data found for the searched company name.</p>
                </div>
            @endif
        @endif

        <!-- Project Search Results -->
        @if($hasProjectSearched)
            @if(!empty($projectResults))
                <div class="space-y-6">
                    @foreach($projectResults as $result)
                        <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm">
                            <h3 class="mb-4 text-lg font-semibold text-gray-900">
                                {{ $result['project']->company_name }}
                                <span class="text-sm font-normal text-gray-500">(Project ID: {{ $result['project']->formatted_handover_id }})</span>
                            </h3>

                            <div class="project-content">
                                {!! $result['project_html'] !!}
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-12 text-center bg-white border border-gray-200 rounded-lg shadow-sm">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-gray-100 rounded-lg">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </div>
                    <h3 class="mb-2 text-lg font-medium text-gray-900">No Projects Found</h3>
                    <p class="text-gray-500">No software handover projects found for the searched company name.</p>
                </div>
            @endif
        @endif

        <!-- Lead Search Results -->
        @if($hasLeadSearched)
            @if(!empty($leadResults))
                <div class="space-y-6">
                    <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900">
                            Lead Search Results
                            <span class="text-sm font-normal text-gray-500">({{ count($leadResults) }} lead(s) found)</span>
                        </h3>

                        <div class="lead-content">
                            {!! $this->generateLeadHtml($leadResults) !!}
                        </div>
                    </div>
                </div>
            @else
                <div class="p-12 text-center bg-white border border-gray-200 rounded-lg shadow-sm">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-gray-100 rounded-lg">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2h8z"></path>
                        </svg>
                    </div>
                    <h3 class="mb-2 text-lg font-medium text-gray-900">No Leads Found</h3>
                    <p class="text-gray-500">No leads found for the searched company name.</p>
                </div>
            @endif
        @endif
    </div>

    <!-- Add Bootstrap Icons for the module icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</x-filament-panels::page>
