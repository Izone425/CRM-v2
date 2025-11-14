{{-- filepath: resources/views/components/hardware-invoice-details.blade.php --}}
<div class="space-y-4">
    @if(empty($invoices))
        <div class="py-8 text-center text-gray-500">
            <p class="text-lg font-medium">No invoices available</p>
        </div>
    @else
        <div class="overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                            #
                        </th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                            Invoice Number
                        </th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                            Type
                        </th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                            Payment Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($invoices as $index => $invoice)
                        @php
                            $invoiceNo = $invoice['invoice_no'] ?? 'Unknown';
                            $invoiceType = 'Unknown';

                            if (stripos($invoiceNo, 'EPIN') !== false) {
                                $invoiceType = 'EPIN';
                            } elseif (stripos($invoiceNo, 'EHIN') !== false) {
                                $invoiceType = 'EHIN';
                            }

                            $paymentStatus = $invoice['payment_status'] ?? 'Unknown';
                            $statusColor = match($paymentStatus) {
                                'Full Payment' => 'text-green-600 bg-green-50',
                                'Partial Payment' => 'text-yellow-600 bg-yellow-50',
                                'UnPaid' => 'text-red-600 bg-red-50',
                                default => 'text-gray-600 bg-gray-50'
                            };
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap">
                                {{ $index + 1 }}
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">
                                {{ $invoiceNo }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $invoiceType === 'EPIN' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                    {{ $invoiceType }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                                    {{ $paymentStatus }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                @if(isset($invoice['invoice_file']) && $invoice['invoice_file'])
                                    <a href="{{ Storage::url($invoice['invoice_file']) }}"
                                       target="_blank"
                                       class="font-medium text-blue-600 hover:text-blue-800">
                                        View PDF
                                    </a>
                                @else
                                    <span class="text-gray-400">No file</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
