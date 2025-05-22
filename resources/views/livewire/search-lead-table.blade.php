<div>
    <div class="mb-4">
        <div class="flex items-center gap-4">
            <div class="relative grow">
                <label for="company-search" class="sr-only">Search Company Name</label>
                <input
                    type="text"
                    id="company-search"
                    wire:model="companySearchTerm"
                    placeholder="Search company name..."
                    class="w-full transition duration-75 border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500"
                    wire:keydown.enter="searchCompany"
                >
                {{-- <div wire:loading wire:target="searchCompany" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500">
                    <svg class="w-5 h-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div> --}}
            </div>
            <div class="flex gap-2 shrink-0">
                <button
                    type="button"
                    wire:click="searchCompany"
                    wire:loading.attr="disabled"
                    wire:target="searchCompany"
                    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white border border-transparent rounded-lg shadow-sm bg-primary-600 hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-70"
                >
                    <svg wire:loading.remove wire:target="searchCompany" class="w-5 h-5 mr-1 -ml-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <svg wire:loading wire:target="searchCompany" class="w-5 h-5 mr-1 -ml-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="searchCompany">Search</span>
                    <span wire:loading wire:target="searchCompany">Searching...</span>
                </button>
                @if($hasSearched)
                    <button
                        type="button"
                        wire:click="resetSearch"
                        wire:loading.attr="disabled"
                        wire:target="resetSearch"
                        class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 disabled:opacity-70"
                    >
                        <svg class="w-5 h-5 mr-1 -ml-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{ $this->table }}
</div>
