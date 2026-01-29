<div>
    <form wire:submit="createInvoice">
        {{-- ======================================== --}}
        {{-- CUSTOMER INFORMATION SECTION --}}
        {{-- ======================================== --}}
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="bg-gray-100 px-6 py-3 rounded-t-lg border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-800">Customer Information</h3>
            </div>
            <div class="px-6 py-5">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    {{-- Row 1: Customer & Invoice Date --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Customer <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="selectedCustomer"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="">Select Customer</option>
                            @foreach($customerOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('selectedCustomer') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Invoice Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" wire:model="invoiceDate"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm" />
                        @error('invoiceDate') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- Row 2: Invoice Title & Invoice Type --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Invoice Title <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="invoiceTitle"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm"
                            placeholder="TimeTec License Purchase" />
                        @error('invoiceTitle') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Invoice Type <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center gap-6 mt-2">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="radio" wire:model="invoiceType" value="normal"
                                    class="form-radio h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500" />
                                <span class="ml-2 text-sm text-gray-700">Normal</span>
                            </label>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="radio" wire:model="invoiceType" value="free_device_campaign"
                                    class="form-radio h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500" />
                                <span class="ml-2 text-sm text-gray-700">Free Device Campaign</span>
                            </label>
                        </div>
                        @error('invoiceType') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- Row 3: Company Address & Mobile Phone --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Company Address <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="companyAddress"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm"
                            placeholder="Company address" />
                        @error('companyAddress') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Mobile Phone <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="mobilePhone"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm"
                            placeholder="e.g. 01843521123" />
                        @error('mobilePhone') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- Row 4: Billing Information (full width) --}}
                    <div style="grid-column: 1 / -1;">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Billing Information <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="billingInformation"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="">Select Billing Information</option>
                            @foreach($billingOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('billingInformation') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- ======================================== --}}
        {{-- ORDER SECTION --}}
        {{-- ======================================== --}}
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="bg-gray-100 px-6 py-3 rounded-t-lg border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-800">Order</h3>
            </div>
            <div class="px-6 py-5">
                <div class="overflow-x-auto">
                    <table class="w-full table-fixed border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th style="width: 24%;" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Name</th>
                                <th style="width: 8%;" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Unit(s)</th>
                                <th style="width: 14%;" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Unit Price
                                    <select wire:model="orderItems.0.currency" class="ml-1 text-xs border-gray-300 rounded py-0 px-1 inline-block" style="font-size: 10px;">
                                        <option value="MYR">MYR</option>
                                        <option value="USD">USD</option>
                                    </select>
                                </th>
                                <th style="width: 14%;" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">License Start Date</th>
                                <th style="width: 14%;" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">License End Date</th>
                                <th style="width: 12%;" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Billing Cycle</th>
                                <th style="width: 7%;" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Discount</th>
                                <th style="width: 10%;" class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Price</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @foreach($orderItems as $index => $item)
                                <tr class="hover:bg-gray-50">
                                    {{-- Item Name --}}
                                    <td class="px-3 py-2">
                                        <span class="text-sm text-gray-900">{{ $item['item_name'] }}</span>
                                    </td>

                                    {{-- Units --}}
                                    <td class="px-3 py-2">
                                        <input type="number"
                                            wire:model.live.debounce.500ms="orderItems.{{ $index }}.units"
                                            class="w-full px-2 py-1 text-center text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                            min="0" />
                                    </td>

                                    {{-- Unit Price --}}
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.01"
                                            wire:model.live.debounce.500ms="orderItems.{{ $index }}.unit_price"
                                            class="w-full px-2 py-1 text-center text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                            min="0" />
                                    </td>

                                    {{-- License Start Date --}}
                                    <td class="px-3 py-2">
                                        <input type="date"
                                            wire:model="orderItems.{{ $index }}.license_start_date"
                                            class="w-full px-2 py-1 text-center text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
                                    </td>

                                    {{-- License End Date --}}
                                    <td class="px-3 py-2">
                                        <input type="date"
                                            wire:model="orderItems.{{ $index }}.license_end_date"
                                            class="w-full px-2 py-1 text-center text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
                                    </td>

                                    {{-- Billing Cycle --}}
                                    <td class="px-3 py-2">
                                        <select wire:model.live="orderItems.{{ $index }}.billing_cycle"
                                            class="w-full px-2 py-1 text-center text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                            <option value="1">1 Month</option>
                                            <option value="3">3 Months</option>
                                            <option value="6">6 Months</option>
                                            <option value="12">12 Months</option>
                                        </select>
                                    </td>

                                    {{-- Discount --}}
                                    <td class="px-3 py-2">
                                        <div class="flex items-center">
                                            <input type="number" step="0.01"
                                                wire:model.live.debounce.500ms="orderItems.{{ $index }}.discount"
                                                class="w-full px-2 py-1 text-center text-sm border border-gray-300 rounded-l focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                                min="0" max="100" />
                                            <span class="px-2 py-1 bg-gray-100 border border-l-0 border-gray-300 rounded-r text-sm text-gray-500">%</span>
                                        </div>
                                    </td>

                                    {{-- Total Price --}}
                                    <td class="px-3 py-2 text-right">
                                        <span class="text-sm font-medium text-gray-900">
                                            {{ number_format($item['total_price'], 2) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ======================================== --}}
        {{-- TOTALS SECTION --}}
        {{-- ======================================== --}}
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-5">
                <div class="flex justify-end">
                    <div style="width: 400px;">
                        <table class="w-full">
                            {{-- Discount --}}
                            <tr>
                                <td class="py-2 text-sm text-gray-600">
                                    DISCOUNT
                                    <div class="inline-flex items-center ml-2">
                                        <input type="number" step="0.01"
                                            wire:model.live.debounce.500ms="discountPercent"
                                            class="w-16 px-2 py-1 text-center text-sm border border-gray-300 rounded-l focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            min="0" max="100" />
                                        <span class="px-2 py-1 bg-gray-100 border border-l-0 border-gray-300 rounded-r text-sm text-gray-500">%</span>
                                    </div>
                                </td>
                                <td class="py-2 text-right text-sm text-gray-900 font-medium">
                                    {{ number_format($this->discountAmount, 2) }}
                                </td>
                            </tr>

                            {{-- Sub Total --}}
                            <tr class="border-t border-gray-100">
                                <td class="py-2 text-sm text-gray-600">Sub Total</td>
                                <td class="py-2 text-right text-sm text-gray-900 font-medium">
                                    {{ number_format($this->subtotalAfterDiscount, 2) }}
                                </td>
                            </tr>

                            {{-- Tax --}}
                            <tr class="border-t border-gray-100">
                                <td class="py-2 text-sm text-gray-600">
                                    TAX
                                    <div class="inline-flex items-center ml-2">
                                        <input type="number" step="0.01"
                                            wire:model.live.debounce.500ms="taxPercent"
                                            class="w-16 px-2 py-1 text-center text-sm border border-gray-300 rounded-l focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            min="0" max="100" />
                                        <span class="px-2 py-1 bg-gray-100 border border-l-0 border-gray-300 rounded-r text-sm text-gray-500">%</span>
                                    </div>
                                </td>
                                <td class="py-2 text-right text-sm text-gray-900 font-medium">
                                    {{ number_format($this->taxAmount, 2) }}
                                </td>
                            </tr>

                            {{-- Total Sales Incl Tax --}}
                            <tr class="border-t border-gray-200">
                                <td class="py-2 text-sm font-semibold text-gray-700">Total SALES INCL TAX</td>
                                <td class="py-2 text-right text-sm font-semibold text-gray-900">
                                    {{ number_format($this->totalInclTax, 2) }}
                                </td>
                            </tr>

                            {{-- Grand Total --}}
                            <tr class="border-t-2 border-gray-300">
                                <td class="py-3 text-base font-bold text-gray-900">GRAND TOTAL</td>
                                <td class="py-3 text-right text-base font-bold text-gray-900">
                                    {{ number_format($this->grandTotal, 2) }}
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ======================================== --}}
        {{-- ACTION BUTTONS --}}
        {{-- ======================================== --}}
        <div class="flex justify-end gap-3">
            <button type="button" wire:click="goBack"
                class="inline-flex items-center px-6 py-2.5 text-sm font-medium text-black bg-blue-600 border border-solid rounded-md shadow-sm hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                Back
            </button>
            <button type="submit"
                class="inline-flex items-center px-6 py-2.5 text-sm font-medium text-black bg-blue-600 border border-solid rounded-md shadow-sm hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                Create Invoice
            </button>
        </div>
    </form>
</div>
