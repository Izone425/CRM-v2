<x-filament-panels::page>
    @if($quotationId || $invoiceNo)
        <livewire:hr-admin-dashboard.view-sales-invoice
            :quotation-id="$quotationId"
            :software-handover-id="$softwareHandoverId"
            :invoice-no="$invoiceNo"
            :total="$total"
            :currency="$currency"
            :status="$status"
            :invoice-date="$invoiceDate"
            :due-date="$dueDate"
            :from="$from"
        />
    @else
        <div class="p-6 text-center bg-white rounded-lg shadow">
            <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">No Invoice Selected</h3>
            <p class="mt-2 text-sm text-gray-500">Please select an invoice to view details.</p>
            <a href="{{ url('/admin/hr-license') }}" class="inline-flex items-center mt-4 text-sm font-medium text-primary-600 hover:text-primary-700">
                Go to All Licenses
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        </div>
    @endif
</x-filament-panels::page>
