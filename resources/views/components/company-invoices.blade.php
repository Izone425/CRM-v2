@if (empty($invoices))
    <div class="p-4 text-gray-500">No invoices found</div>
@else
    <div class="overflow-hidden bg-white border border-gray-200 rounded-lg" x-data="{ openInvoices: {} }">
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200">
                {{-- <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Invoice No</th>
                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Products</th>
                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Total Amount</th>
                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Currency</th>
                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Total Units</th>
                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Earliest Expiry</th>
                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Latest Expiry</th>
                    </tr>
                </thead> --}}
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($invoices as $invoice)
                        @php
                            $invoiceKey = "invoice_{$companyId}_{$loop->index}";
                        @endphp
                        <tr class="transition-colors duration-150 cursor-pointer hover:bg-gray-50"
                            @click="openInvoices['{{ $invoiceKey }}'] = !openInvoices['{{ $invoiceKey }}']">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="text-sm font-medium text-gray-900">INV NO: {{ $invoice->f_invoice_no ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                                {{ $invoice->invoice_product_count }} products
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 whitespace-nowrap">
                                {{ number_format($invoice->invoice_total_amount, 2) }} {{ $invoice->f_currency }}
                            </td>
                        </tr>

                        <!-- Products row (hidden by default) -->
                        <tr x-show="openInvoices['{{ $invoiceKey }}']"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0">
                            <td colspan="7" class="px-0 py-0">
                                @php
                                    $products = \App\Filament\Pages\RenewalData::getProductsForInvoice($companyId, $invoice->f_invoice_no);
                                @endphp

                                @include('components.invoice-products', ['products' => $products])
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
