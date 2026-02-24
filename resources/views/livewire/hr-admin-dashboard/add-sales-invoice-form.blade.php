<div>
    <form wire:submit="submitInvoice">
        {{-- Validation Errors Summary --}}
        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center mb-2">
                    <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm font-medium text-red-800">Please fix the following errors:</span>
                </div>
                <ul class="list-disc list-inside text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ======================================== --}}
        {{-- CUSTOMER INFORMATION SECTION --}}
        {{-- ======================================== --}}
        <div class="bg-white rounded-lg shadow mb-16">
            <div class="bg-gray-100 px-6 py-3 rounded-t-lg border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-800">Customer Information</h3>
            </div>
            <div class="px-6 py-5">
                @if($mode === 'edit')
                    {{-- Edit mode: simplified layout --}}
                    <div style="display: grid; grid-template-columns: {{ $isUnderDealer ? '1fr 1fr 1fr 1fr' : '1fr 1fr 1fr' }}; gap: 20px;">
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
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Sales Type <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="salesType"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="NEW SALES">NEW SALES</option>
                                <option value="ADD ON NEW SALES">ADD ON NEW SALES</option>
                                <option value="RENEWAL SALES">RENEWAL SALES</option>
                                <option value="ADD ON RENEWAL SALES">ADD ON RENEWAL SALES</option>
                            </select>
                            @error('salesType') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        @if($isUnderDealer)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Pay By <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="payBy"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    <option value="Subscriber">Subscriber</option>
                                    <option value="Reseller">Reseller</option>
                                </select>
                            </div>
                        @endif
                    </div>
                @else
                    {{-- Create mode: full layout --}}
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        {{-- Row 1: Customer, Invoice Date & Pay By (if under dealer) --}}
                        <div style="{{ $isUnderDealer ? 'grid-column: 1 / -1; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;' : 'display: contents;' }}">
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
                            @if($isUnderDealer)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Pay By <span class="text-red-500">*</span>
                                    </label>
                                    <select wire:model="payBy"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        <option value="Subscriber">Subscriber</option>
                                        <option value="Reseller">Reseller</option>
                                    </select>
                                </div>
                            @endif
                        </div>

                        {{-- Row 2: Invoice Title, Sales Type & Invoice Type --}}
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
                                Sales Type <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="salesType"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="NEW SALES">NEW SALES</option>
                                <option value="ADD ON NEW SALES">ADD ON NEW SALES</option>
                                <option value="RENEWAL SALES">RENEWAL SALES</option>
                                <option value="ADD ON RENEWAL SALES">ADD ON RENEWAL SALES</option>
                            </select>
                            @error('salesType') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        {{-- Row 3: Company Address & Mobile Phone --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Company Address
                            </label>
                            <input type="text" wire:model="companyAddress"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm"
                                placeholder="Company address" />
                            @error('companyAddress') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Mobile Phone
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
                @endif
            </div>
        </div>

        {{-- Section Spacer --}}
        <div class="h-4"></div>

        {{-- ======================================== --}}
        {{-- BULK CONFIGURATION SECTION --}}
        {{-- ======================================== --}}
        <div x-data="{ bulkOpen: false }" class="bg-white rounded-lg shadow mb-6">
            <button type="button" @click="bulkOpen = !bulkOpen"
                class="w-full bg-gray-100 px-6 py-3 rounded-t-lg border-b border-gray-200 flex items-center justify-between cursor-pointer hover:bg-gray-200 transition-colors">
                <h3 class="text-base font-semibold text-gray-800">Bulk Configuration</h3>
                <svg :class="bulkOpen ? 'rotate-180' : ''" class="w-5 h-5 text-gray-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div x-show="bulkOpen" x-collapse class="px-6 py-5">
                {{-- Product Checkboxes --}}
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Products</label>
                    <div class="flex flex-wrap gap-4">
                        @foreach($availableProducts as $pIndex => $product)
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" value="{{ $pIndex }}" wire:model="bulkProducts"
                                    class="form-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                                <span class="ml-2 text-sm text-gray-700">{{ $product['name'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Bulk Fields --}}
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr 1fr; gap: 16px;" class="mb-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Units per Item</label>
                        <input type="number" wire:model="bulkUnits" min="0"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm"
                            placeholder="e.g. 30" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit Price</label>
                        <input type="number" step="0.01" wire:model="bulkUnitPrice" min="0"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm"
                            placeholder="e.g. 5.00" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">License Start Date</label>
                        <input type="date" wire:model="bulkStartDate"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Billing Cycle</label>
                        <select wire:model="bulkBillingCycle"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="1">1 Month</option>
                            <option value="3">3 Months</option>
                            <option value="6">6 Months</option>
                            <option value="12">12 Months</option>
                            <option value="24">24 Months</option>
                            <option value="36">36 Months</option>
                            <option value="48">48 Months</option>
                            <option value="60">60 Months</option>
                            @if($activeLicenseEndDate)
                                <option value="consolidate">Consolidate ({{ $this->bulkConsolidateMonths }}M)</option>
                            @endif
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Years of Subscription</label>
                        <select wire:model="bulkYears"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="1">1 Year</option>
                            <option value="2">2 Years</option>
                            <option value="3">3 Years</option>
                            <option value="4">4 Years</option>
                            <option value="5">5 Years</option>
                        </select>
                    </div>
                </div>

                {{-- Apply Button --}}
                <div class="flex justify-end">
                    <button type="button" wire:click="applyBulkConfig"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-wait"
                        class="inline-flex items-center px-5 py-2 text-sm font-medium text-black bg-blue-600 border border-blue-600 rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Apply Bulk Configuration
                    </button>
                </div>
            </div>
        </div>

        {{-- Section Spacer --}}
        <div class="h-4"></div>

        {{-- ======================================== --}}
        {{-- ORDER SECTION --}}
        {{-- ======================================== --}}
        <div class="bg-white rounded-lg shadow mb-12">
            <div class="bg-gray-100 px-6 py-3 rounded-t-lg border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-800">Order</h3>
            </div>
            <div class="px-6 py-5">
                <div class="overflow-x-auto">
                    <table class="w-full table-fixed border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th style="width: 19%;" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Name</th>
                                <th style="width: 8%;" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Unit(s)</th>
                                <th style="width: 10%;" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center justify-center gap-1">
                                        <span>Unit Price</span>
                                        <select wire:model="currency" class="text-xs border border-gray-300 rounded py-0.5 pl-2 pr-6 bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 appearance-none bg-no-repeat" style="font-size: 11px; background-image: url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 fill=%27none%27 viewBox=%270 0 20 20%27%3E%3Cpath stroke=%27%236b7280%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27 stroke-width=%271.5%27 d=%27M6 8l4 4 4-4%27/%3E%3C/svg%3E'); background-position: right 0.25rem center; background-size: 1.25em 1.25em;">
                                            <option value="MYR">MYR</option>
                                            <option value="USD">USD</option>
                                            <option value="SGD">SGD</option>
                                            <option value="EUR">EUR</option>
                                            <option value="GBP">GBP</option>
                                        </select>
                                    </div>
                                </th>
                                <th style="width: 14%;" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">License Start Date</th>
                                <th style="width: 14%;" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">License End Date</th>
                                <th style="width: 14%;" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Billing Cycle</th>
                                <th style="width: 11%;" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Discount</th>
                                <th style="width: 10%;" class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Price</th>
                                <th style="width: 3%;" class="px-1 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @foreach($orderItems as $index => $item)
                                <tr class="hover:bg-gray-50">
                                    {{-- Item Name --}}
                                    <td class="px-3 py-2">
                                        @if($index < 5)
                                            <span class="text-sm text-gray-900">{{ $item['item_name'] }}</span>
                                        @else
                                            <select wire:model.live="orderItems.{{ $index }}.item_name"
                                                wire:change="updateItemProduct({{ $index }}, $event.target.value)"
                                                class="w-full px-2 py-1 text-sm border border-gray-200 rounded-md bg-white hover:border-gray-300 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors">
                                                <option value="">-- Select Product --</option>
                                                @foreach($availableProducts as $product)
                                                    <option value="{{ $product['name'] }}">{{ $product['name'] }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </td>

                                    {{-- Units --}}
                                    <td class="px-3 py-2">
                                        <input type="number"
                                            wire:model.live.debounce.500ms="orderItems.{{ $index }}.units"
                                            class="w-full px-2 py-1 text-center text-sm border border-gray-200 rounded-md bg-white hover:border-gray-300 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
                                            min="0" />
                                    </td>

                                    {{-- Unit Price --}}
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.01"
                                            wire:model.live.debounce.500ms="orderItems.{{ $index }}.unit_price"
                                            class="w-full px-2 py-1 text-center text-sm border border-gray-200 rounded-md bg-white hover:border-gray-300 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
                                            min="0" />
                                    </td>

                                    {{-- License Start Date --}}
                                    <td class="px-3 py-2">
                                        <input type="date"
                                            wire:model.live="orderItems.{{ $index }}.license_start_date"
                                            class="w-full px-2 py-1 text-center text-sm border border-gray-200 rounded-md bg-white hover:border-gray-300 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors" />
                                    </td>

                                    {{-- License End Date --}}
                                    <td class="px-3 py-2">
                                        <input type="date"
                                            wire:model="orderItems.{{ $index }}.license_end_date"
                                            class="w-full px-2 py-1 text-center text-sm border border-gray-200 rounded-md bg-gray-50 text-gray-500 cursor-not-allowed" readonly />
                                    </td>

                                    {{-- Billing Cycle --}}
                                    <td class="px-3 py-2">
                                        <select wire:model.live="orderItems.{{ $index }}.billing_cycle"
                                            class="w-full px-2 py-1 text-center text-sm border border-gray-200 rounded-md bg-white hover:border-gray-300 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors cursor-pointer">
                                            <option value="1">1 Month</option>
                                            <option value="3">3 Months</option>
                                            <option value="6">6 Months</option>
                                            <option value="12">12 Months</option>
                                            <option value="24">24 Months</option>
                                            <option value="36">36 Months</option>
                                            <option value="48">48 Months</option>
                                            <option value="60">60 Months</option>
                                            @if($activeLicenseEndDate)
                                                <option value="consolidate">Consolidate ({{ $item['consolidate_months'] ?? 0 }}M)</option>
                                            @endif
                                        </select>
                                    </td>

                                    {{-- Discount --}}
                                    <td class="px-3 py-2">
                                        <div class="flex items-center">
                                            <input type="number" step="0.01"
                                                wire:model.blur="orderItems.{{ $index }}.discount"
                                                class="w-full px-2 py-1 text-center text-sm border border-gray-200 rounded-l-md bg-white hover:border-gray-300 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
                                                min="0" max="100"
                                                placeholder="0.00" />
                                            <span class="px-2 py-1 bg-gray-100 border border-l-0 border-gray-200 rounded-r-md text-sm text-gray-500">%</span>
                                        </div>
                                    </td>

                                    {{-- Total Price --}}
                                    <td class="px-3 py-2 text-right">
                                        <span class="text-sm font-medium text-gray-900">
                                            {{ number_format($item['total_price'], 2) }}
                                        </span>
                                    </td>

                                    {{-- Action --}}
                                    <td class="px-1 py-2 text-center">
                                        <button type="button" wire:click="removeItemRow({{ $index }})"
                                            class="w-6 h-6 inline-flex items-center justify-center rounded text-red-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                            title="Delete row">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 flex justify-end">
                    <button type="button" wire:click="addItemRow"
                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Item
                    </button>
                </div>
            </div>
        </div>

        {{-- ======================================== --}}
        {{-- TOTALS SECTION --}}
        {{-- ======================================== --}}
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-5">
                <div class="flex justify-end">
                    <div style="width: 600px;">
                        <table class="w-full">
                            {{-- Discount --}}
                            <tr>
                                <td class="py-2 text-sm text-gray-600">
                                    DISCOUNT
                                    <div class="inline-flex items-center ml-2">
                                        <input type="number" step="0.01"
                                            wire:model.blur="discountPercent"
                                            class="w-16 px-2 py-1 text-center text-sm border border-gray-200 rounded-l-md bg-white hover:border-gray-300 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
                                            min="0" max="100"
                                            placeholder="0.00" />
                                        <span class="px-2 py-1 bg-gray-100 border border-l-0 border-gray-200 rounded-r-md text-sm text-gray-500">%</span>
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
                                            class="w-16 px-2 py-1 text-center text-sm border border-gray-200 rounded-l-md bg-white hover:border-gray-300 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
                                            min="0" max="100" />
                                        <span class="px-2 py-1 bg-gray-100 border border-l-0 border-gray-200 rounded-r-md text-sm text-gray-500">%</span>
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

        {{-- Section Spacer --}}
        <div class="h-4"></div>

        {{-- ======================================== --}}
        {{-- ACTION BUTTONS --}}
        {{-- ======================================== --}}
        <div class="flex justify-end gap-3">
            <button type="button" wire:click="goBack"
                class="inline-flex items-center px-6 py-2.5 text-sm font-medium text-gray-700 bg-sky-100 border border-sky-200 rounded-md shadow-sm hover:bg-red-500 hover:text-white hover:border-red-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                Back
            </button>
            <button type="submit"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-50 cursor-wait"
                class="inline-flex items-center px-6 py-2.5 text-sm font-medium text-gray-700 bg-sky-100 border border-sky-200 rounded-md shadow-sm hover:bg-green-500 hover:text-white hover:border-green-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                <span wire:loading.remove wire:target="submitInvoice">{{ $mode === 'edit' ? 'Update Invoice' : 'Create Invoice' }}</span>
                <span wire:loading wire:target="submitInvoice" class="inline-flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ $mode === 'edit' ? 'Updating...' : 'Creating...' }}
                </span>
            </button>
        </div>
    </form>
</div>
