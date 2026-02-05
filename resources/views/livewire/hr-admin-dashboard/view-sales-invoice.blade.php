<div>
    {{-- Loading State --}}
    @if($isLoading)
        <div class="flex items-center justify-center py-12">
            <svg class="w-8 h-8 text-blue-500 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="ml-2 text-gray-600">Loading invoice...</span>
        </div>
    @elseif($hasError)
        {{-- Error State --}}
        <div class="p-6 text-center bg-white rounded-lg shadow">
            <svg class="w-12 h-12 mx-auto text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">Error Loading Invoice</h3>
            <p class="mt-2 text-sm text-gray-500">{{ $errorMessage }}</p>
            <button wire:click="goBack" class="inline-flex items-center mt-4 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                Go Back
            </button>
        </div>
    @else
        {{-- Page Header --}}
        <div class="mb-4">
            <h1 class="text-xl font-semibold text-gray-800">
                Invoice <span class="text-gray-400">&raquo;</span> {{ $invoice['reference_no'] ?? 'N/A' }}
            </h1>
        </div>

        {{-- Action Buttons --}}
        <div class="flex gap-2 mb-6">
            <button wire:click="goBack"
                class="px-4 py-2 text-sm font-medium text-black bg-cyan-500 rounded hover:bg-cyan-600 transition-colors">
                Back
            </button>
            <button onclick="window.print()"
                class="px-4 py-2 text-sm font-medium text-black bg-cyan-500 rounded hover:bg-cyan-600 transition-colors">
                Print
            </button>
        </div>

        {{-- Invoice Document --}}
        <div class="flex justify-center px-4">
            <div class="bg-white shadow-lg border border-gray-200 rounded-lg max-w-4xl w-full" id="invoice-document">
                {{-- Invoice Content --}}
                <div class="px-6 py-8">
                    {{-- Company Header --}}
                    <div class="flex justify-between items-start mb-6">
                        {{-- Logo (cropped to hide www.timeteccloud.com) --}}
                        <div class="overflow-hidden" style="height: 50px;">
                            <img src="{{ asset('img/logo-ttc.png') }}" alt="TimeTec" style="height: 65px;">
                        </div>
                        {{-- Company Details --}}
                        <div class="text-right text-xs text-gray-600">
                            <p>CP No. B16-1809-32000587</p>
                            <p class="font-semibold">TimeTec Cloud Sdn Bhd (832542-W)</p>
                            <p>NO. 1 & 2, 18TH FLOOR, TOWER 5 @ PFCC,</p>
                            <p>JALAN PUTERI 1/2, BANDAR PUTERI,</p>
                            <p>47100, Puchong,</p>
                            <p>SELANGOR, MALAYSIA.</p>
                            <p>Tel: 603 80709933</p>
                        </div>
                    </div>
                    <div class="h-4"></div>
                    {{-- Invoice Title --}}
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-normal text-blue-700">PROFORMA INVOICE</h2>
                    </div>
                    <div class="h-4"></div>
                    {{-- Bill To & Invoice Details --}}
                    <div class="flex justify-between mb-6">
                        {{-- Bill To --}}
                        <div>
                            <p class="font-bold text-gray-800 mb-1">Bill To:</p>
                            <p class="text-sm text-gray-700">{{ $invoice['customer'] ?? '-' }}</p>
                            @if(!empty($invoice['email']))
                                <p class="text-sm text-gray-600">{{ $invoice['email'] }}</p>
                            @endif
                            <p class="text-sm text-gray-700 uppercase">{{ $invoice['customer'] ?? '-' }}</p>
                            <p class="text-sm text-gray-600">Malaysia</p>
                        </div>
                        {{-- Invoice Details Box --}}
                        <div class="border border-gray-300 p-4 min-w-64">
                            <table class="text-sm w-full">
                                <tr>
                                    <td class="text-blue-600 pr-2">P. Invoice No:</td>
                                    <td class="font-medium">{{ $invoice['reference_no'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-gray-600 pr-2">Date:</td>
                                    <td>{{ $invoice['date'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-gray-600 pr-2">Status:</td>
                                    <td>
                                        @if(strtolower($invoice['status'] ?? '') === 'paid' || strtolower($invoice['status'] ?? '') === 'completed')
                                            <span class="text-green-600 font-semibold">PAID</span>
                                        @else
                                            <span class="text-red-600 font-semibold">UNPAID</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-gray-600 pr-2">TRX Rate (RM):</td>
                                    <td>{{ $invoice['trx_rate'] ?? '4.1765' }}</td>
                                </tr>
                                <tr class="border-t border-gray-200">
                                    <td class="text-gray-800 font-bold pt-2">Amount Due:</td>
                                    <td class="font-bold pt-2">{{ $invoice['currency'] ?? 'MYR' }} {{ number_format($invoice['grand_total'] ?? 0, 2) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                <div class="h-4"></div>
                    {{-- Items Table --}}
                    <div class="mb-6">
                        <table class="w-full text-sm border-collapse">
                            <thead>
                                <tr style="background-color: #1d4ed8; color: #ffffff;">
                                    <th class="px-3 py-2 text-left w-12" style="border: 1px solid #1d4ed8;">No.</th>
                                    <th class="px-3 py-2 text-left" style="border: 1px solid #1d4ed8;">Description</th>
                                    <th class="px-3 py-2 text-center w-16" style="border: 1px solid #1d4ed8;">Qty</th>
                                    <th class="px-3 py-2 text-right w-20" style="border: 1px solid #1d4ed8;">Price</th>
                                    <th class="px-3 py-2 text-center w-24" style="border: 1px solid #1d4ed8;">Billing Cycle</th>
                                    <th class="px-3 py-2 text-center w-20" style="border: 1px solid #1d4ed8;">Discount</th>
                                    <th class="px-3 py-2 text-right w-24" style="border: 1px solid #1d4ed8;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $index => $item)
                                    <tr class="border-b border-gray-200">
                                        <td class="border border-gray-200 px-3 py-2 text-center text-blue-600">{{ $index + 1 }}.</td>
                                        <td class="border border-gray-200 px-3 py-2">
                                            <span class="text-blue-600">TimeTec Suite - {{ $item['description'] }}</span>
                                            <br>
                                            <span class="text-gray-500 text-xs">[{{ $item['period'] ?? (date('d/m/Y') . ' - ' . date('d/m/Y', strtotime('+' . ($item['subscription_period'] ?? 1) . ' months'))) }}]</span>
                                        </td>
                                        <td class="border border-gray-200 px-3 py-2 text-center text-blue-600">{{ $item['quantity'] }}</td>
                                        <td class="border border-gray-200 px-3 py-2 text-right text-blue-600">{{ number_format($item['unit_price'], 2) }}</td>
                                        <td class="border border-gray-200 px-3 py-2 text-center text-blue-600">{{ $item['subscription_period'] }}</td>
                                        <td class="border border-gray-200 px-3 py-2 text-center text-blue-600">{{ number_format($item['discount'] ?? 0, 0) }}%</td>
                                        <td class="border border-gray-200 px-3 py-2 text-right text-blue-600">{{ number_format($item['total_before_tax'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="border border-gray-200 px-3 py-6 text-center text-gray-500">No items found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Totals Section --}}
                    <div class="flex justify-end mb-8">
                        <div class="w-72">
                            <table class="w-full text-sm text-blue-600">
                                <tr>
                                    <td class="py-1 text-right pr-4">Subtotal:</td>
                                    <td class="py-1 text-right">{{ number_format($invoice['subtotal'] ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="py-1 text-right pr-4">Discount:</td>
                                    <td class="py-1 text-right">-{{ number_format($invoice['discount'] ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="py-1 text-right pr-4">Taxable Amount:</td>
                                    <td class="py-1 text-right">{{ number_format($invoice['taxable_amount'] ?? $invoice['subtotal'] ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="py-1 text-right pr-4">SST ({{ $invoice['tax_rate'] ?? 0 }}%):</td>
                                    <td class="py-1 text-right border-b border-gray-300">{{ number_format($invoice['tax_amount'] ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="py-1 text-right pr-4 font-medium">Total:</td>
                                    <td class="py-1 text-right font-medium">{{ number_format($invoice['grand_total'] ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="py-1 text-right pr-4 font-bold">Amount Due ({{ $invoice['currency'] ?? 'USD' }}):</td>
                                    <td class="py-1 text-right font-bold">{{ number_format($invoice['grand_total'] ?? 0, 2) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                <div class="h-4"></div>
                    {{-- Terms & Conditions --}}
                    <div class="border-t border-gray-200 pt-4">
                        <p class="font-bold text-gray-800 text-sm mb-2">Terms & Conditions:</p>
                        <ol class="text-xs text-gray-600 list-decimal list-inside space-y-1">
                            <li>Please keep this invoice for your future reference and correspondence with <span class="text-blue-600 underline">TimeTec Cloud Sdn Bhd (832542-W)</span>.</li>
                            <li>All purchases with TimeTec Cloud Sdn Bhd are bound by the <span class="text-blue-600 underline">Terms & Conditions</span>.</li>
                            <li>Questions about your invoice, email us at <span class="text-blue-600">info@timeteccloud.com</span>.</li>
                            <li>Bank Account Details (for TT payment):<br>
                                <div class="ml-4 mt-1 space-y-0.5">
                                    <p>Beneficiary's Name: <span class="font-semibold">TimeTec Cloud Sdn Bhd (832542-W)</span></p>
                                    <p>Banker:             <span class="font-semibold">PUBLIC BANK BERHAD</span></p>
                                    <p>Account No.:        <span class="font-semibold">3593 6726 19</span></p>
                                    <p>Swift Code:         <span class="font-semibold">PBBEMYKL</span></p>
                                </div>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Print Styles --}}
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            #invoice-document, #invoice-document * {
                visibility: visible;
            }
            #invoice-document {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                box-shadow: none !important;
            }
        }
    </style>
</div>
