@if (empty($products))
    <div class="p-4 text-gray-500">No products found</div>
@else
    <div class="overflow-x-auto">
        <table class="w-full divide-y divide-gray-200">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-3 py-2">Product</th>
                    <th class="px-3 py-2">Invoice</th>
                    <th class="px-3 py-2">Units</th>
                    <th class="px-3 py-2">Amount</th>
                    <th class="px-3 py-2">Start Date</th>
                    <th class="px-3 py-2">Expiry Date</th>
                    <th class="px-3 py-2">Created By</th>
                    <th class="px-3 py-2">Payer</th>
                    <th class="px-3 py-2">Created At</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($products as $product)
                    <tr>
                        <td class="px-3 py-2">{{ $product->f_name ?? 'N/A' }}</td>
                        <td class="px-3 py-2">{{ $product->f_invoice_no ?? 'N/A' }}</td>
                        <td class="px-3 py-2">{{ $product->f_unit ?? 0 }}</td>
                        <td class="px-3 py-2">{{ number_format($product->f_total_amount, 2) }} {{ $product->f_currency }}</td>
                        <td class="px-3 py-2">{{ $product->f_start_date ? date('Y-m-d', strtotime($product->f_start_date)) : 'N/A' }}</td>
                        <td class="px-3 py-2">{{ $product->f_expiry_date ? date('Y-m-d', strtotime($product->f_expiry_date)) : 'N/A' }}</td>
                        <td class="px-3 py-2">{{ $product->Created ?? 'N/A' }}</td>
                        <td class="px-3 py-2">{{ $product->payer ?? 'N/A' }}</td>
                        <td class="px-3 py-2">{{ $product->f_created_time ? date('Y-m-d', strtotime($product->f_created_time)) : 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
