<div>
    {{-- Loading State --}}
    @if($isLoading)
        <div class="flex items-center justify-center py-12">
            <svg class="w-8 h-8 text-blue-500 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="ml-2 text-gray-600">Loading receipt...</span>
        </div>
    @elseif($hasError)
        {{-- Error State --}}
        <div class="p-6 text-center bg-white rounded-lg shadow">
            <svg class="w-12 h-12 mx-auto text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">Error Loading Receipt</h3>
            <p class="mt-2 text-sm text-gray-500">{{ $errorMessage }}</p>
            <button wire:click="goBack"
                style="background-color: #2563eb; color: #fff;"
                class="inline-flex items-center px-4 py-2 mt-4 text-sm font-medium rounded-md hover:bg-blue-700">
                Go Back
            </button>
        </div>
    @else
        {{-- Page Header --}}
        <div class="mb-4">
            <h1 class="text-xl font-semibold text-gray-800">
                Official Receipt <span class="text-gray-400">&raquo;</span> {{ $receipt['or_no'] ?? 'N/A' }}
            </h1>
        </div>

        {{-- Action Buttons --}}
        <div class="flex gap-2 mb-6">
            <button wire:click="goBack"
                style="background-color: #06b6d4; color: #000;"
                class="px-4 py-2 text-sm font-medium rounded hover:bg-cyan-600 transition-colors">
                Back
            </button>
            <button onclick="window.print()"
                style="background-color: #06b6d4; color: #000;"
                class="px-4 py-2 text-sm font-medium rounded hover:bg-cyan-600 transition-colors">
                Print
            </button>
        </div>
        <div class="h-4"></div>

        {{-- Receipt Document --}}
        <div class="flex justify-center px-4">
            <div class="w-full max-w-4xl bg-white border border-gray-200 rounded-lg shadow-lg" id="receipt-document">
                <div class="px-6 py-8">
                    {{-- Company Header --}}
                    <div class="flex items-start justify-between mb-6">
                        {{-- Logo --}}
                        <div class="overflow-hidden" style="height: 50px;">
                            <img src="{{ asset('img/logo-ttc.png') }}" alt="TimeTec" style="height: 65px;">
                        </div>
                        {{-- Company Details --}}
                        <div class="text-xs text-right text-gray-600">
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

                    {{-- Receipt Title --}}
                    <div class="mb-8 text-center">
                        <div class="border-t border-gray-300"></div>
                        <h2 class="py-3 text-2xl font-normal text-blue-700">OFFICIAL RECEIPT</h2>
                        <div class="border-t border-gray-300"></div>
                    </div>

                    <div class="h-4"></div>

                    {{-- Received From & Doc Info --}}
                    <div class="flex justify-between mb-6">
                        {{-- Received From --}}
                        <div>
                            <p class="mb-1 font-bold text-gray-800">Received From:</p>
                            <p class="text-sm text-gray-700">{{ $receipt['company_name'] ?? '-' }}</p>
                            <p class="text-sm text-gray-600">Malaysia</p>
                        </div>
                        {{-- Doc Info Box --}}
                        <div class="p-4 border border-gray-300 min-w-64">
                            <table class="w-full text-sm">
                                <tr>
                                    <td class="pr-2 text-blue-600">Doc No:</td>
                                    <td class="font-medium">{{ $receipt['or_no'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="pr-2 text-gray-600">Date:</td>
                                    <td>{{ $receipt['receipt_date'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="pr-2 text-gray-600">Status:</td>
                                    <td>
                                        @if(strtoupper($receipt['status'] ?? '') === 'PAID')
                                            <span class="font-semibold text-green-600">PAID</span>
                                        @else
                                            <span class="font-semibold text-red-600">{{ strtoupper($receipt['status'] ?? 'UNPAID') }}</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="pr-2 text-gray-600">Received In:</td>
                                    <td class="font-medium">{{ $receipt['payment_method'] ?? 'BANK TRANSFER' }}</td>
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
                                    <th class="px-3 py-2 text-right w-32" style="border: 1px solid #1d4ed8;">Total ({{ $receipt['currency'] ?? 'MYR' }})</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b border-gray-200">
                                    <td class="px-3 py-2 text-center text-blue-600 border border-gray-200">1.</td>
                                    <td class="px-3 py-2 border border-gray-200">
                                        <span class="text-blue-600">TimeTec Suite - Payment for Invoice {{ $receipt['invoice_no'] ?? '-' }}</span>
                                    </td>
                                    <td class="px-3 py-2 text-right text-blue-600 border border-gray-200">{{ number_format($receipt['amount'] ?? 0, 2) }}</td>
                                </tr>

                                {{-- Invoice Sub-table --}}
                                @if(!empty($invoice))
                                <tr>
                                    <td class="border border-gray-200"></td>
                                    <td class="px-3 py-2 border border-gray-200" colspan="2">
                                        <table class="w-full text-xs border-collapse">
                                            <thead>
                                                <tr style="background-color: #dbeafe;">
                                                    <th class="px-2 py-1 text-left border border-gray-200">Invoice No</th>
                                                    <th class="px-2 py-1 text-left border border-gray-200">Date</th>
                                                    <th class="px-2 py-1 text-right border border-gray-200">Amount ({{ $receipt['currency'] ?? 'MYR' }})</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="px-2 py-1 text-blue-600 border border-gray-200">{{ $invoice['invoice_no'] ?? '-' }}</td>
                                                    <td class="px-2 py-1 border border-gray-200">{{ $invoice['invoice_date'] ?? '-' }}</td>
                                                    <td class="px-2 py-1 text-right border border-gray-200">{{ number_format($invoice['invoice_amount'] ?? 0, 2) }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    {{-- Totals Section --}}
                    <div class="flex justify-end mb-8">
                        <div class="w-72">
                            <table class="w-full text-sm text-blue-600">
                                <tr>
                                    <td class="py-1 pr-4 text-right">Subtotal:</td>
                                    <td class="py-1 text-right">{{ number_format($receipt['amount'] ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="py-1 pr-4 text-right">Taxable Amount:</td>
                                    <td class="py-1 text-right">{{ number_format($receipt['amount'] ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="py-1 pr-4 text-right font-medium">Total (Inclusive Tax):</td>
                                    <td class="py-1 text-right font-medium border-t border-b border-gray-300">{{ $receipt['currency'] ?? 'MYR' }} {{ number_format($receipt['amount'] ?? 0, 2) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="h-4"></div>

                    {{-- Terms & Conditions --}}
                    <div class="pt-4 border-t border-gray-200">
                        <p class="mb-2 text-sm font-bold text-gray-800">Terms & Conditions:</p>
                        <ol class="space-y-1 text-xs text-gray-600 list-decimal list-inside">
                            <li>Please keep this receipt for your future reference and correspondence with <span class="text-blue-600 underline">TimeTec Cloud Sdn Bhd (832542-W)</span>.</li>
                            <li>All purchases with TimeTec Cloud Sdn Bhd are bound by the <span class="text-blue-600 underline">Terms & Conditions</span>.</li>
                            <li>Questions about your receipt, email us at <span class="text-blue-600">info@timeteccloud.com</span>.</li>
                            <li>Bank Account Details (for TT payment):<br>
                                <div class="mt-1 ml-4 space-y-0.5">
                                    <p>Banker:             <span class="font-semibold">United Overseas Bank (M) Bhd, Puchong branch</span></p>
                                    <p>Beneficiary's Name: <span class="font-semibold">TimeTec Cloud Sdn Bhd (832542-W)</span></p>
                                    <p>Account No.:        <span class="font-semibold">2253 0814 40</span></p>
                                    <p>Swift Code:         <span class="font-semibold">UOVBMYKL</span></p>
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
            #receipt-document, #receipt-document * {
                visibility: visible;
            }
            #receipt-document {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                box-shadow: none !important;
            }
        }
    </style>
</div>
