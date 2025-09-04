@if (empty($invoices))
    <div class="p-4 text-gray-500">No invoices found for this debtor</div>
@else
    <div class="overflow-x-auto">
        <table class="w-full divide-y divide-gray-200">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-3 py-2">Invoice Number</th>
                    <th class="px-3 py-2">Invoice Date</th>
                    <th class="px-3 py-2">Currency</th>
                    <th class="px-3 py-2">Invoice Amount</th>
                    <th class="px-3 py-2">Outstanding</th>
                    <th class="px-3 py-2">Bal in RM</th>
                    <th class="px-3 py-2">Aging</th>
                    <th class="px-3 py-2">SalesPerson</th>
                    <th class="px-3 py-2">Support</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoices as $invoice)
                    @php
                        // Calculate aging based on months difference
                        $due = \Carbon\Carbon::parse($invoice->aging_date);
                        $now = \Carbon\Carbon::now();

                        // Calculate months difference (0 = current month, 1 = next month, etc.)
                        $monthsDiff = $now->diffInMonths($due);

                        // For current month invoices
                        if ($due->greaterThanOrEqualTo($now)) {
                            $agingText = 'Current';
                            $agingColor = 'text-green-600';
                        }
                        // For overdue invoices, calculate months difference
                        else {
                            $monthsDiff = $now->diffInMonths($due);

                            if ($monthsDiff == 0) {
                                // Still in the same month (overdue but less than a month)
                                $agingText = 'Current';
                                $agingColor = '#10b981';
                            } elseif ($monthsDiff == 1) {
                                $agingText = '1 Month';
                                $agingColor = '#3b82f6';
                            } elseif ($monthsDiff == 2) {
                                $agingText = '2 Months';
                                $agingColor = '#eab308';
                            } elseif ($monthsDiff == 3) {
                                $agingText = '3 Months';
                                $agingColor = '#f97316';
                            } elseif ($monthsDiff == 4) {
                                $agingText = '4 Months';
                                $agingColor = '#ef4444';
                            } else {
                                $agingText = '5+ Months';
                                $agingColor = '#b91c1c';
                            }
                        }

                        // Calculate balance in RM
                        if ($invoice->currency_code === 'MYR') {
                            $balInRM = $invoice->outstanding;
                        } else {
                            $balInRM = $invoice->outstanding * $invoice->exchange_rate;
                        }

                        // Determine row highlighting based on outstanding value
                        $rowBackground = ($invoice->outstanding > 0) ? 'style="background-color: #ffed3a82;"' : '';
                    @endphp

                    <tr {!! $rowBackground !!}>
                        <td class="px-3 py-2">{{ $invoice->invoice_number }}</td>
                        <td class="px-3 py-2">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d') }}</td>
                        <td class="px-3 py-2">{{ $invoice->currency_code }}</td>
                        <td class="px-3 py-2">{{ number_format($invoice->invoice_amount, 2) }}</td>
                        <td class="px-3 py-2 font-medium {{ $invoice->outstanding > 0 ? 'text-red-600' : 'text-green-600' }}">{{ number_format($invoice->outstanding, 2) }}</td>
                        <td class="px-3 py-2">RM {{ number_format($balInRM, 2) }}</td>
                        <td class="px-3 py-2" style="color: {{ $agingColor }}">{{ $agingText }}</td>
                        <td class="px-3 py-2">{{ $invoice->salesperson }}</td>
                        <td class="px-3 py-2">{{ $invoice->support }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
