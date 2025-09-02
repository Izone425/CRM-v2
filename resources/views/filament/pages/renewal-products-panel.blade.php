<div class="p-2 space-y-4">
    @if(empty($products))
        <div class="text-gray-500">No products found</div>
    @else
        @foreach($products as $product)
            <div class="p-3 border rounded bg-gray-50">
                <div class="text-lg font-medium">{{ $product->f_name }}</div>
                <div class="grid grid-cols-3 gap-4 mt-2">
                    <div>
                        <div class="text-sm text-gray-600">Invoice:</div>
                        <div>{{ $product->f_invoice_no ?? 'N/A' }}</div>
                        <div class="mt-1 text-sm text-gray-600">Units:</div>
                        <div>{{ $product->f_unit ?? '0' }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600">Amount:</div>
                        <div>{{ number_format($product->f_total_amount, 2) }} {{ $product->f_currency }}</div>
                        <div class="mt-1 text-sm text-gray-600">Created By:</div>
                        <div>{{ $product->Created ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600">Start Date:</div>
                        <div>{{ isset($product->f_start_date) ? date('Y-m-d', strtotime($product->f_start_date)) : 'N/A' }}</div>
                        <div class="mt-1 text-sm text-gray-600">Expiry Date:</div>
                        <div>{{ isset($product->f_expiry_date) ? date('Y-m-d', strtotime($product->f_expiry_date)) : 'N/A' }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</div>
