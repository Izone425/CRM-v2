{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/modals/invoices-list.blade.php --}}
<div class="space-y-4">
    <div class="text-sm text-gray-600 mb-4">
        <strong>Handover ID:</strong> {{ $handover->formatted_handover_id }}
    </div>

    <div class="space-y-3">
        @foreach($invoices as $invoiceNo)
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex-1">
                    <div class="font-medium text-green-600 font-mono">
                        {{ $invoiceNo }}
                    </div>
                    <div class="text-sm text-gray-500">
                        HRDF Invoice
                    </div>
                </div>
                <div class="ml-4">
                    <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">
                        Invoice
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4 pt-3 border-t border-gray-200">
        <div class="flex justify-between text-sm">
            <span class="font-medium">Total Invoices:</span>
            <span class="font-bold">{{ count($invoices) }}</span>
        </div>
    </div>
</div>
