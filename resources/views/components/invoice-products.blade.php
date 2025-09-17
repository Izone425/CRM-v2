@if (empty($products))
    <div class="p-4 text-gray-500 bg-gray-50">No products found for this invoice</div>
@else
    <div class="border-t border-gray-200 bg-gray-50">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="px-6 py-2 text-xs font-medium tracking-wider text-left text-gray-600 uppercase">Product Name</th>
                        <th class="px-6 py-2 text-xs font-medium tracking-wider text-left text-gray-600 uppercase">Units</th>
                        <th class="px-6 py-2 text-xs font-medium tracking-wider text-left text-gray-600 uppercase">Amount</th>
                        <th class="px-6 py-2 text-xs font-medium tracking-wider text-left text-gray-600 uppercase">Start Date</th>
                        <th class="px-6 py-2 text-xs font-medium tracking-wider text-left text-gray-600 uppercase">Expiry Date</th>
                        <th class="px-6 py-2 text-xs font-medium tracking-wider text-left text-gray-600 uppercase">Created By</th>
                        <th class="px-6 py-2 text-xs font-medium tracking-wider text-left text-gray-600 uppercase">Payer</th>
                        <th class="px-6 py-2 text-xs font-medium tracking-wider text-left text-gray-600 uppercase">Created At</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    @foreach ($products as $product)
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="px-6 py-2 text-sm text-gray-900 whitespace-nowrap">{{ $product->f_name ?? 'N/A' }}</td>
                            <td class="px-6 py-2 text-sm text-gray-900 whitespace-nowrap">{{ $product->f_unit ?? 0 }}</td>
                            <td class="px-6 py-2 text-sm text-gray-900 whitespace-nowrap">{{ number_format($product->f_total_amount, 2) }} {{ $product->f_currency }}</td>
                            <td class="px-6 py-2 text-sm text-gray-500 whitespace-nowrap">{{ $product->f_start_date ? date('Y-m-d', strtotime($product->f_start_date)) : 'N/A' }}</td>
                            <td class="px-6 py-2 text-sm text-gray-500 whitespace-nowrap">{{ $product->f_expiry_date ? date('Y-m-d', strtotime($product->f_expiry_date)) : 'N/A' }}</td>
                            <td class="px-6 py-2 text-sm text-gray-500 whitespace-nowrap">{{ $product->Created ?? 'N/A' }}</td>
                            <td class="px-6 py-2 text-sm text-gray-500 whitespace-nowrap">{{ $product->payer ?? 'N/A' }}</td>
                            <td class="px-6 py-2 text-sm text-gray-500 whitespace-nowrap">{{ $product->f_created_time ? date('Y-m-d', strtotime($product->f_created_time)) : 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
