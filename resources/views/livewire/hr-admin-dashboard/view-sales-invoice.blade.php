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
            <button wire:click="goBack"
                style="background-color: #2563eb; color: #fff;"
                class="inline-flex items-center mt-4 px-4 py-2 text-sm font-medium rounded-md hover:bg-blue-700">
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
                style="background-color: #06b6d4; color: #000;"
                class="px-4 py-2 text-sm font-medium rounded hover:bg-cyan-600 transition-colors">
                Back
            </button>
            <button onclick="window.print()"
                style="background-color: #06b6d4; color: #000;"
                class="px-4 py-2 text-sm font-medium rounded hover:bg-cyan-600 transition-colors">
                Print
            </button>
            @if(strtolower($invoice['status'] ?? '') === 'pending')
                <button wire:click="openPaymentModal"
                    style="background-color: #16a34a; color: #000;"
                    class="px-4 py-2 text-sm font-medium rounded hover:bg-green-700 transition-colors">
                    Add Payment
                </button>
                <button wire:click="editInvoice"
                    style="background-color: #2563eb; color: #000;"
                    class="px-4 py-2 text-sm font-medium rounded hover:bg-blue-700 transition-colors">
                    Edit Invoice
                </button>
                <button wire:click="openCancelModal"
                    style="background-color: #dc2626; color: #000;"
                    class="px-4 py-2 text-sm font-medium rounded hover:bg-red-700 transition-colors">
                    Cancel Invoice
                </button>
                <button wire:click="copyPaymentLink"
                    style="background-color: #4b5563; color: #000;"
                    class="px-4 py-2 text-sm font-medium rounded hover:bg-gray-700 transition-colors">
                    Copy Payment Link
                </button>
            @elseif(in_array(strtolower($invoice['status'] ?? ''), ['cancel', 'cancelled']))
                <button
                    style="background-color: #16a34a; color: #000;"
                    class="px-4 py-2 text-sm font-medium rounded hover:bg-green-700 transition-colors">
                    Reactive Invoice
                </button>
            @endif
        </div>
<div class="h-4"></div>
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
                                @php
                                    $groupedByYear = collect($items)->groupBy(function($item) {
                                        if (isset($item['year'])) return $item['year'];
                                        if (!empty($item['period'])) return substr($item['period'], 6, 4);
                                        return 'Other';
                                    });
                                    $itemCounter = 0;
                                @endphp
                                @forelse($groupedByYear as $yearLabel => $yearItems)
                                    {{-- Year Header Row --}}
                                    <tr style="background-color: #eff6ff;">
                                        <td colspan="7" class="px-4 py-2 text-sm font-semibold text-blue-800 border border-gray-200">
                                            Year {{ $yearLabel }}
                                        </td>
                                    </tr>
                                    @php $yearCounter = 0; @endphp
                                    @foreach($yearItems as $item)
                                        @php $yearCounter++; @endphp
                                        <tr class="border-b border-gray-200">
                                            <td class="border border-gray-200 px-3 py-2 text-center text-blue-600">{{ $yearCounter }}.</td>
                                            <td class="border border-gray-200 px-3 py-2">
                                                <span class="text-blue-600">TimeTec Suite- {{ $item['description'] }}</span>
                                                <br>
                                                <span class="text-gray-500 text-xs">[{{ $item['period'] ?? (date('d/m/Y') . ' - ' . date('d/m/Y', strtotime('+' . ($item['subscription_period'] ?? 1) . ' months'))) }}]</span>
                                            </td>
                                            <td class="border border-gray-200 px-3 py-2 text-center text-blue-600">{{ $item['quantity'] }}</td>
                                            <td class="border border-gray-200 px-3 py-2 text-right text-blue-600">{{ number_format($item['unit_price'], 2) }}</td>
                                            <td class="border border-gray-200 px-3 py-2 text-center text-blue-600">{{ $item['subscription_period'] }}</td>
                                            <td class="border border-gray-200 px-3 py-2 text-center text-blue-600">{{ number_format($item['discount'] ?? 0, 0) }}%</td>
                                            <td class="border border-gray-200 px-3 py-2 text-right text-blue-600">{{ number_format($item['total_before_tax'], 2) }}</td>
                                        </tr>
                                    @endforeach
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

    {{-- Success Notification --}}
    @if(session()->has('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
            class="fixed top-4 right-4 z-[60] bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Add Official Receipt Modal --}}
    @if($showPaymentModal)
        {{-- Lock body scroll when modal is open --}}
        <style>body { overflow: hidden !important; }</style>

        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="payment-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 py-6">
                {{-- Background overlay --}}
                <div class="fixed inset-0 transition-opacity" style="background-color: rgba(0, 0, 0, 0.5);" wire:click="closePaymentModal"></div>

                {{-- Modal panel --}}
                <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-auto" style="z-index: 51;">
                    <form wire:submit="submitPayment">
                        {{-- Modal Header --}}
                        <div class="px-6 pt-6 pb-4">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900" id="payment-modal-title">
                                        Add Official Receipt
                                    </h3>
                                    <div class="mt-1 h-0.5 w-full" style="background-color: #06b6d4;"></div>
                                </div>
                                <button type="button" wire:click="closePaymentModal" class="ml-4 p-1 rounded-full hover:bg-gray-100 transition-colors" style="background-color: transparent;">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Modal Body --}}
                        <div class="px-6 py-4">
                            <table class="w-full" style="border-collapse: separate; border-spacing: 0 16px;">
                                {{-- Company --}}
                                <tr>
                                    <td class="pr-4 align-top" style="width: 140px; padding-top: 10px;">
                                        <label class="text-sm font-medium text-gray-700 whitespace-nowrap">
                                            Company <span class="text-red-500">*</span>:
                                        </label>
                                    </td>
                                    <td>
                                        <select disabled
                                            style="background-color: #f9fafb; border: 1px solid #d1d5db; color: #374151; padding: 8px 12px; border-radius: 6px; width: 100%; font-size: 14px;">
                                            <option>{{ $paymentForm['company'] }}</option>
                                        </select>
                                    </td>
                                </tr>

                                {{-- Total Amount --}}
                                <tr>
                                    <td class="pr-4 align-top" style="padding-top: 10px;">
                                        <label class="text-sm font-medium text-gray-700 whitespace-nowrap">
                                            Total Amount <span class="text-red-500">*</span>:
                                        </label>
                                    </td>
                                    <td>
                                        <div class="flex gap-2">
                                            <select wire:model="paymentForm.currency"
                                                style="border: 1px solid #d1d5db; padding: 8px 8px; border-radius: 6px; font-size: 14px; width: 80px; background-color: #fff;">
                                                <option value="MYR">MYR</option>
                                                <option value="USD">USD</option>
                                            </select>
                                            <input type="number" wire:model="paymentForm.amount" step="0.01" min="0"
                                                style="border: 1px solid #d1d5db; padding: 8px 12px; border-radius: 6px; font-size: 14px; flex: 1; background-color: #fff;">
                                        </div>
                                        @error('paymentForm.amount')
                                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                        @enderror
                                    </td>
                                </tr>

                                {{-- License Number --}}
                                <tr>
                                    <td class="pr-4 align-top" style="padding-top: 10px;">
                                        <label class="text-sm font-medium text-gray-700 whitespace-nowrap">
                                            License Number <span class="text-red-500">*</span>:
                                        </label>
                                    </td>
                                    <td>
                                        <input type="text" wire:model="paymentForm.license_number"
                                            style="border: 1px solid #d1d5db; padding: 8px 12px; border-radius: 6px; font-size: 14px; width: 100%; background-color: #fff;">
                                        @error('paymentForm.license_number')
                                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                        @enderror
                                    </td>
                                </tr>

                                {{-- Autocount Invoice --}}
                                <tr>
                                    <td class="pr-4 align-top" style="padding-top: 10px;">
                                        <label class="text-sm font-medium text-gray-700 whitespace-nowrap">
                                            Autocount Invoice <span class="text-red-500">*</span>:
                                        </label>
                                    </td>
                                    <td>
                                        <input type="text" wire:model="paymentForm.autocount_invoice"
                                            maxlength="13"
                                            x-on:input="$el.value = $el.value.toUpperCase()"
                                            style="border: 1px solid #d1d5db; padding: 8px 12px; border-radius: 6px; font-size: 14px; width: 100%; background-color: #fff; text-transform: uppercase;">
                                        <p class="mt-1 text-xs text-gray-400">Max 13 characters, uppercase</p>
                                        @error('paymentForm.autocount_invoice')
                                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                        @enderror
                                    </td>
                                </tr>
                            </table>
                        </div>

                        {{-- Modal Footer --}}
                        <div class="px-6 py-5 flex gap-3">
                            <button type="submit"
                                style="background-color: #38bdf8; color: #fff; padding: 8px 28px; border-radius: 6px; font-size: 14px; font-weight: 500;">
                                Continue
                            </button>
                            <button type="button" wire:click="closePaymentModal"
                                style="background-color: #38bdf8; color: #fff; padding: 8px 28px; border-radius: 6px; font-size: 14px; font-weight: 500;">
                                Back
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Cancel Invoice Modal --}}
    @if($showCancelModal)
        {{-- Lock body scroll when modal is open --}}
        <style>body { overflow: hidden !important; }</style>

        <div class="fixed inset-0 z-50 flex items-center justify-center" aria-labelledby="cancel-modal-title" role="dialog" aria-modal="true">
            {{-- Background overlay --}}
            <div class="fixed inset-0 transition-opacity" style="background-color: rgba(0, 0, 0, 0.5);" wire:click="closeCancelModal"></div>

            {{-- Modal panel --}}
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-lg mx-auto" style="z-index: 51;">
                <form wire:submit="submitCancelInvoice">
                    {{-- Modal Header --}}
                    <div class="px-4 pt-4 pb-2">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <h3 class="text-lg font-bold text-gray-900" id="cancel-modal-title">Cancel Invoice</h3>
                                </div>
                                <p class="mt-1 text-sm" style="color: #dc2626;">Please fill up the reason to cancel this invoice. Thank you.</p>
                            </div>
                            <button type="button" wire:click="closeCancelModal" class="ml-4 p-1 rounded-full hover:bg-gray-100 transition-colors" style="background-color: transparent;">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Modal Body --}}
                    <div class="px-4 py-3">
                        <table class="w-full" style="border-collapse: separate; border-spacing: 0 10px;">
                            {{-- Doc No (readonly) --}}
                            <tr>
                                <td class="pr-3 align-middle" style="width: 100px;">
                                    <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Doc No:</label>
                                </td>
                                <td>
                                    <input type="text" value="{{ $cancelForm['doc_no'] }}" readonly
                                        style="background-color: #f3f4f6; border: 1px solid #d1d5db; color: #374151; padding: 6px 10px; border-radius: 6px; width: 100%; font-size: 13px; cursor: not-allowed;">
                                </td>
                            </tr>

                            {{-- Status --}}
                            <tr>
                                <td class="pr-3 align-middle">
                                    <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Status <span class="text-red-500">*</span>:</label>
                                </td>
                                <td>
                                    <select wire:model="cancelForm.status"
                                        style="border: 1px solid #d1d5db; padding: 6px 10px; border-radius: 6px; font-size: 13px; width: 100%; background-color: #fff;">
                                        <option value="cancelled">Cancelled</option>
                                        <option value="pending">Pending</option>
                                    </select>
                                    @error('cancelForm.status')
                                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>

                            {{-- Remark --}}
                            <tr>
                                <td class="pr-3 align-top" style="padding-top: 8px;">
                                    <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Remark <span class="text-red-500">*</span>:</label>
                                </td>
                                <td>
                                    <textarea wire:model="cancelForm.remark" rows="3"
                                        placeholder="Enter reason for cancellation..."
                                        style="border: 1px solid #d1d5db; padding: 6px 10px; border-radius: 6px; font-size: 13px; width: 100%; resize: vertical; background-color: #fff;"></textarea>
                                    @error('cancelForm.remark')
                                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>
                        </table>

                        {{-- Submit Button --}}
                        <div class="mt-2 flex justify-end">
                            <button type="submit"
                                style="background-color: #38bdf8; color: #fff; padding: 6px 24px; border-radius: 6px; font-size: 13px; font-weight: 500;">
                                Submit
                            </button>
                        </div>
                    </div>

                    {{-- Remarks History Section --}}
                    <div class="px-4 py-3 border-t border-gray-200">
                        <h4 class="text-sm font-semibold text-gray-700 mb-1">Remarks</h4>
                        @if(count($cancelRemarks) > 0)
                            <div class="space-y-1">
                                @foreach($cancelRemarks as $remarkEntry)
                                    <div class="text-sm text-gray-600 border-b border-gray-100 pb-1">
                                        <span class="font-medium">{{ $remarkEntry['user'] ?? 'System' }}</span>
                                        <span class="text-gray-400 text-xs ml-2">{{ $remarkEntry['date'] ?? '' }}</span>
                                        <p class="mt-0.5">{{ $remarkEntry['remark'] ?? '' }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-400 italic">No record yet.</p>
                        @endif
                    </div>
                </form>
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

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('payment-link-copied', (event) => {
                if (event.error) {
                    alert(event.error);
                    return;
                }
                navigator.clipboard.writeText(event.url).then(() => {
                    alert('Payment Link was Copied: ' + event.url);
                }).catch(() => {
                    alert('Payment Link was Copied: ' + event.url);
                });
            });
        });
    </script>
</div>
