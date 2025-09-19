{{-- filepath: /var/www/html/timeteccrm/resources/views/components/company-invoices-myr.blade.php --}}
@php
    use App\Filament\Pages\RenewalDataMyr;

    // Get reseller information for the company
    $reseller = RenewalDataMyr::getResellerForCompany($companyId);
@endphp

@if (empty($invoices))
    <div class="p-4 text-gray-500">No invoices found</div>
@else
    {{-- Reseller Information --}}
    @if($resellerName)
        <div class="p-3 mb-4 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex items-center">
                <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                <span class="text-sm font-medium text-blue-800">Reseller:</span>
                <span class="ml-1 text-sm font-semibold text-blue-700">{{ $resellerName }}</span>
            </div>
        </div>
    @endif

    <div class="overflow-hidden bg-white border border-gray-200 rounded-lg">
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Invoice No</th>
                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Product Name</th>
                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Units</th>
                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Amount</th>
                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Start Date</th>
                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Expiry Date</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($invoices as $invoice)
                        @php
                            $products = \App\Filament\Pages\RenewalDataMyr::getProductsForInvoice($companyId, $invoice->f_invoice_no);
                        @endphp

                        @foreach ($products as $product)
                            @php
                                // Calculate the amount with reseller rate for each product
                                if ($reseller && $reseller->f_rate) {
                                    // With reseller: apply reseller rate + 8%
                                    $calculatedAmount = ($product->f_total_amount * 100) / ($reseller->f_rate + 8);
                                    // Subtract the reseller commission
                                    $finalAmount = $calculatedAmount - ($calculatedAmount * $reseller->f_rate / 100);
                                } else {
                                    // No reseller: only deduct 8%
                                    $finalAmount = ($product->f_total_amount * 100) / (100 + 8);
                                }
                            @endphp

                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $invoice->f_invoice_no ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $product->f_name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $product->f_unit }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ number_format($finalAmount, 2) }} {{ $product->f_currency }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ \Carbon\Carbon::parse($product->f_start_date)->format('Y-m-d') }}</td>
                                <td class="px-4 py-3 text-sm
                                    @if(\Carbon\Carbon::parse($product->f_expiry_date)->isToday())
                                        text-red-600
                                    @elseif(\Carbon\Carbon::parse($product->f_expiry_date)->diffInDays(\Carbon\Carbon::now()) <= 7)
                                        text-orange-600
                                    @elseif(\Carbon\Carbon::parse($product->f_expiry_date)->diffInDays(\Carbon\Carbon::now()) <= 30)
                                        text-blue-600
                                    @else
                                        text-gray-900
                                    @endif
                                ">{{ \Carbon\Carbon::parse($product->f_expiry_date)->format('Y-m-d') }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
