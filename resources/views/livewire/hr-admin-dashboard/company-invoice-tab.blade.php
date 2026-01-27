<div class="p-6">
    {{-- Header Section --}}
    <div class="mb-4 p-4 bg-cyan-50 border border-cyan-200 rounded-lg">
        <p class="text-sm text-cyan-800">
            <span class="font-medium">Note:</span> Invoices shown here are only those billed under the name of the distributor/dealer. It excludes invoices issued directly to the subscriber.
        </p>
    </div>

    {{-- Local Data Banner --}}
    @if($isLocalData && !$hasError)
        <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <p class="text-sm text-yellow-800">
                <span class="font-medium">Note:</span> Showing local invoice records. Backend sync data is not available for this company.
            </p>
        </div>
    @endif

    {{-- Search and Controls Section --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
        {{-- Search Box --}}
        <div class="flex items-center gap-2">
            <div class="relative">
                <input
                    type="text"
                    wire:model.defer="search"
                    wire:keydown.enter="searchInvoices"
                    placeholder="Search"
                    class="w-48 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                >
            </div>
            <button
                wire:click="searchInvoices"
                wire:loading.attr="disabled"
                class="px-4 py-2 bg-cyan-500 text-white rounded-lg hover:bg-cyan-600 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 text-sm font-medium transition-colors disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="searchInvoices">Search</span>
                <span wire:loading wire:target="searchInvoices">
                    <svg class="animate-spin h-4 w-4 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </span>
            </button>
        </div>

        {{-- Pagination Controls & Total Records --}}
        <div class="flex items-center gap-4">
            {{-- Pagination Navigation --}}
            <div class="flex items-center gap-1 border border-gray-300 rounded-lg overflow-hidden">
                <button
                    wire:click="previousPage"
                    @if($currentPage <= 1) disabled @endif
                    class="px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed border-r border-gray-300"
                >
                    &lt;
                </button>
                <span class="px-3 py-1.5 text-sm text-gray-700 bg-gray-50">{{ $currentPage }}</span>
                <button
                    wire:click="nextPage"
                    @if($currentPage >= $this->totalPages()) disabled @endif
                    class="px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed border-l border-gray-300"
                >
                    &gt;
                </button>
            </div>

            {{-- Total Records Count --}}
            <div class="text-sm text-gray-600">
                Total of record (s): <span class="font-semibold">{{ $totalRecords }}</span>
            </div>
        </div>
    </div>

    {{-- Loading State --}}
    @if($isLoading)
        <div class="flex items-center justify-center py-12">
            <div class="text-center">
                <svg class="animate-spin h-10 w-10 text-cyan-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-gray-500">Loading invoices...</p>
            </div>
        </div>
    @elseif($hasError)
        {{-- Error State --}}
        <div class="flex items-center justify-center py-12">
            <div class="text-center max-w-md">
                <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h4 class="text-lg font-semibold text-gray-800 mb-2">Unable to Load Invoices</h4>
                <p class="text-gray-500 mb-4">{{ $errorMessage }}</p>
                <button
                    wire:click="refreshInvoices"
                    class="px-4 py-2 bg-cyan-500 text-white rounded-lg hover:bg-cyan-600 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 text-sm font-medium"
                >
                    Try Again
                </button>
            </div>
        </div>
    @else
        {{-- Invoice Table --}}
        <div class="overflow-x-auto border border-gray-200 rounded-lg">
            <table class="w-full divide-y divide-gray-200 table-fixed">
                <thead class="bg-gray-500">
                    <tr>
                        <th scope="col" class="w-[15%] px-3 py-2.5 text-center text-xs font-medium text-black tracking-wider">
                            Invoice No
                        </th>
                        <th scope="col" class="w-[12%] px-3 py-2.5 text-center text-xs font-medium text-black tracking-wider">
                            Invoice Date
                        </th>
                        <th scope="col" class="w-[12%] px-3 py-2.5 text-center text-xs font-medium text-black tracking-wider">
                            Due Date
                        </th>
                        <th scope="col" class="w-[31%] px-3 py-2.5 text-center text-xs font-medium text-black tracking-wider">
                            Description
                        </th>
                        <th scope="col" class="w-[15%] px-3 py-2.5 text-center text-xs font-medium text-black tracking-wider">
                            Total
                        </th>
                        <th scope="col" class="w-[15%] px-3 py-2.5 text-center text-xs font-medium text-black tracking-wider">
                            Status
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($invoices as $index => $invoice)
                        <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }} hover:bg-gray-100">
                            <td class="px-3 py-2.5 whitespace-nowrap text-center">
                                <span class="text-sm text-gray-900 font-medium">
                                    {{ $invoice['invoice_no'] ?? '-' }}
                                </span>
                            </td>
                            <td class="px-3 py-2.5 whitespace-nowrap text-sm text-gray-700 text-center">
                                {{ isset($invoice['invoice_date']) ? \Carbon\Carbon::parse($invoice['invoice_date'])->format('Y-m-d') : '-' }}
                            </td>
                            <td class="px-3 py-2.5 whitespace-nowrap text-sm text-gray-700 text-center">
                                {{ isset($invoice['due_date']) && $invoice['due_date'] ? \Carbon\Carbon::parse($invoice['due_date'])->format('Y-m-d') : '-' }}
                            </td>
                            <td class="px-3 py-2.5 text-sm text-gray-700 text-center truncate" title="{{ $invoice['description'] ?? 'TimeTec License' }}">
                                {{ $invoice['description'] ?? 'TimeTec License' }}
                            </td>
                            <td class="px-3 py-2.5 whitespace-nowrap text-sm text-blue-600 text-center font-semibold">
                                {{ number_format($invoice['total'] ?? 0, 2) }} {{ $invoice['currency'] ?? 'MYR' }}
                            </td>
                            <td class="px-3 py-2.5 whitespace-nowrap text-center">
                                @php
                                    $status = strtolower($invoice['status'] ?? 'pending');
                                @endphp
                                @if($status === 'paid')
                                    <span class="text-sm font-semibold text-green-600">Paid</span>
                                @elseif($status === 'unpaid')
                                    <span class="text-sm font-semibold text-red-600">Unpaid</span>
                                @else
                                    <span class="text-sm font-semibold text-yellow-600">{{ ucfirst($invoice['status'] ?? 'Pending') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-8 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-10 h-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="text-gray-500 text-sm">No invoices found</p>
                                    @if(!empty($search))
                                        <p class="text-gray-400 text-xs mt-1">Try adjusting your search criteria</p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
