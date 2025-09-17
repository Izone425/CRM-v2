{{-- filepath: /var/www/html/timeteccrm/resources/views/components/debtor-invoices.blade.php --}}
@if (empty($invoices))
    <div class="p-4 text-gray-500">No invoices found for this debtor</div>
@else
    <div class="w-full overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 table-fixed">
            <thead>
                <tr class="bg-gray-50">
                    {{-- REQUEST 5: New sub headers with defined widths --}}
                    <th class="w-32 px-3 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Invoice Number</th>
                    <th class="w-24 px-3 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Invoice Date</th>
                    <th class="w-24 px-3 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Debtor Aging</th>
                    <th class="px-3 py-2 text-xs font-medium tracking-wider text-right text-gray-500 uppercase w-28">Invoice Amount</th>
                    <th class="px-3 py-2 text-xs font-medium tracking-wider text-right text-gray-500 uppercase w-28">Balance in RM</th>
                    <th class="w-24 px-3 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Payment Type</th>
                    <th class="w-20 px-3 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Invoice Type</th>
                    <th class="w-24 px-3 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">SalesPerson</th>
                    <th class="w-20 px-3 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Support</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($invoices as $invoice)
                    @php
                        // Calculate aging based on months difference
                        $due = \Carbon\Carbon::parse($invoice->aging_date);
                        $now = \Carbon\Carbon::now();

                        // Calculate debtor aging
                        if ($due->greaterThanOrEqualTo($now)) {
                            $agingText = 'Current';
                            $agingColor = 'text-green-600 bg-green-100';
                        } else {
                            $monthsDiff = $now->diffInMonths($due);

                            if ($monthsDiff == 0) {
                                $agingText = 'Current';
                                $agingColor = 'text-green-600 bg-green-100';
                            } elseif ($monthsDiff == 1) {
                                $agingText = '1 Month';
                                $agingColor = 'text-blue-600 bg-blue-100';
                            } elseif ($monthsDiff == 2) {
                                $agingText = '2 Months';
                                $agingColor = 'text-yellow-600 bg-yellow-100';
                            } elseif ($monthsDiff == 3) {
                                $agingText = '3 Months';
                                $agingColor = 'text-orange-600 bg-orange-100';
                            } elseif ($monthsDiff == 4) {
                                $agingText = '4 Months';
                                $agingColor = 'text-red-600 bg-red-100';
                            } else {
                                $agingText = '5+ Months';
                                $agingColor = 'text-red-800 bg-red-200';
                            }
                        }

                        // Calculate balance in RM
                        if ($invoice->currency_code === 'MYR') {
                            $balInRM = $invoice->outstanding;
                        } else {
                            $balInRM = $invoice->outstanding * $invoice->exchange_rate;
                        }

                        // Determine payment type
                        if ((float)$invoice->outstanding === 0.0) {
                            $paymentType = 'Full Payment';
                            $paymentColor = 'text-green-600 bg-green-100';
                        } elseif ((float)$invoice->outstanding === (float)$invoice->invoice_amount) {
                            $paymentType = 'UnPaid';
                            $paymentColor = 'text-red-600 bg-red-100';
                        } else {
                            $paymentType = 'Partial Payment';
                            $paymentColor = 'text-yellow-600 bg-yellow-100';
                        }

                        // Determine invoice type
                        if (strpos($invoice->invoice_number, 'EPIN') === 0) {
                            $invoiceType = 'Product';
                        } elseif (strpos($invoice->invoice_number, 'EHIN') === 0) {
                            $invoiceType = 'HRDF';
                        } else {
                            $invoiceType = 'Other';
                        }

                        // Row highlighting based on outstanding value
                        $rowBackground = ($invoice->outstanding > 0) ? 'bg-yellow-50' : '';
                    @endphp

                    <tr class="{{ $rowBackground }} hover:bg-gray-50">
                        {{-- Header 1: Invoice Number --}}
                        <td class="px-3 py-2 text-sm font-medium text-gray-900 truncate">
                            {{ $invoice->invoice_number }}
                        </td>

                        {{-- Header 2: Invoice Date --}}
                        <td class="px-3 py-2 text-sm text-gray-500 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}
                        </td>

                        {{-- Header 3: Debtor Aging --}}
                        <td class="px-3 py-2">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $agingColor }}">
                                {{ $agingText }}
                            </span>
                        </td>

                        {{-- Header 4: Invoice Amount --}}
                        <td class="px-3 py-2 text-sm text-right text-gray-900 whitespace-nowrap">
                            {{ $invoice->currency_code }} {{ number_format($invoice->invoice_amount, $filterDecimalPlaces ?? 2) }}
                        </td>

                        {{-- Header 5: Balance in RM --}}
                        <td class="px-3 py-2 text-sm font-medium text-right whitespace-nowrap {{ $invoice->outstanding > 0 ? 'text-red-600' : 'text-green-600' }}">
                            RM {{ number_format($balInRM, $filterDecimalPlaces ?? 2) }}
                        </td>

                        {{-- Header 6: Payment Type --}}
                        <td class="px-3 py-2">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $paymentColor }}">
                                {{ $paymentType }}
                            </span>
                        </td>

                        {{-- Header 7: Invoice Type --}}
                        <td class="px-3 py-2 text-sm text-gray-500 whitespace-nowrap">
                            {{ $invoiceType }}
                        </td>

                        {{-- Header 8: SalesPerson --}}
                        <td class="px-3 py-2 text-sm text-gray-500 truncate">
                            {{ $invoice->salesperson ?: 'N/A' }}
                        </td>

                        {{-- Header 9: Support --}}
                        <td class="px-3 py-2 text-sm text-gray-500 truncate">
                            {{ $invoice->support ?: 'N/A' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
