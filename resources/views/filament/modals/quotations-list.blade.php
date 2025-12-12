{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/modals/quotations-list.blade.php --}}
<div class="space-y-4">
    <div class="mb-4 text-sm text-gray-600">
        <strong>Handover ID:</strong> {{ $handover->formatted_handover_id }}
    </div>

    <div class="space-y-3">
        @foreach($quotations as $quotation)
            <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50">
                <div class="flex-1">
                    <div class="font-medium text-blue-600">
                        <a
                            href="{{ url('proforma-invoice-v2/' . $quotation->id) }}"
                            target="_blank"
                            class="sw-view-link hover:text-blue-800 hover:underline"
                        >
                            {{ $quotation->pi_reference_no }}
                            <svg class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                        </a>
                    </div>
                    {{-- <div class="text-sm text-gray-500">
                        Total: RM {{ number_format($quotation->total_before_tax, 2) }}
                    </div> --}}
                </div>
                <div class="ml-4">
                    <span class="px-2 py-1 text-xs text-blue-800 bg-blue-100 rounded-full">
                        Quotation
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="pt-3 mt-4 border-t border-gray-200">
        <div class="flex justify-between text-sm">
            <span class="font-medium">Total Quotations:</span>
            <span class="font-bold">{{ count($quotations) }}</span>
        </div>
        {{-- <div class="flex justify-between mt-1 text-sm">
            <span class="font-medium">Grand Total:</span>
            <span class="font-bold">RM {{ number_format($quotations->sum('total_before_tax'), 2) }}</span>
        </div> --}}
    </div>
</div>
