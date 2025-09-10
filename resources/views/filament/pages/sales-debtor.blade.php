<x-filament-panels::page>
    <!-- Dashboard Cards -->
    <div class="grid grid-cols-5 gap-4 mb-6 md:grid-cols-3 lg:grid-cols-5">
        <!-- Box 1: All Debtor -->
        <div class="overflow-hidden bg-white rounded-lg shadow">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 rounded-md bg-primary-100">
                        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="flex-1 w-0 ml-5">
                        <dt class="text-lg font-medium text-gray-900">All Debtor</dt>
                        <dd>
                            <div class="text-sm text-gray-500">Total Invoice: {{ $allDebtorStats['total_invoices'] }}</div>
                            <div class="text-lg font-medium text-gray-900">Total Amount:</div>
                            <div class="text-xl font-bold text-primary-600">{{ $allDebtorStats['formatted_amount'] }}</div>
                        </dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Box 2: HRDF Debtor -->
        <div class="overflow-hidden bg-white rounded-lg shadow">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 bg-blue-100 rounded-md">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div class="flex-1 w-0 ml-5">
                        <dt class="text-lg font-medium text-gray-900">HRDF Debtor</dt>
                        <dd>
                            <div class="text-sm text-gray-500">Total Invoice: {{ $hrdfDebtorStats['total_invoices'] }}</div>
                            <div class="text-lg font-medium text-gray-900">Total Amount:</div>
                            <div class="text-xl font-bold text-blue-600">{{ $hrdfDebtorStats['formatted_amount'] }}</div>
                        </dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Box 3: Product Debtor -->
        <div class="overflow-hidden bg-white rounded-lg shadow">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 bg-purple-100 rounded-md">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <div class="flex-1 w-0 ml-5">
                        <dt class="text-lg font-medium text-gray-900">Product Debtor</dt>
                        <dd>
                            <div class="text-sm text-gray-500">Total Invoice: {{ $productDebtorStats['total_invoices'] }}</div>
                            <div class="text-lg font-medium text-gray-900">Total Amount:</div>
                            <div class="text-xl font-bold text-purple-600">{{ $productDebtorStats['formatted_amount'] }}</div>
                        </dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Box 4: Unpaid Debtor -->
        <div class="overflow-hidden bg-white rounded-lg shadow">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 bg-red-100 rounded-md">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="flex-1 w-0 ml-5">
                        <dt class="text-lg font-medium text-gray-900">Unpaid Debtor</dt>
                        <dd>
                            <div class="text-sm text-gray-500">Total Invoice: {{ $unpaidDebtorStats['total_invoices'] }}</div>
                            <div class="text-lg font-medium text-gray-900">Total Amount:</div>
                            <div class="text-xl font-bold text-red-600">{{ $unpaidDebtorStats['formatted_amount'] }}</div>
                        </dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Box 5: Partial Payment Debtor -->
        <div class="overflow-hidden bg-white rounded-lg shadow">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 rounded-md bg-amber-100">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="flex-1 w-0 ml-5">
                        <dt class="text-lg font-medium text-gray-900">Partial Payment Debtor</dt>
                        <dd>
                            <div class="text-sm text-gray-500">Total Invoice: {{ $partialPaymentDebtorStats['total_invoices'] }}</div>
                            <div class="text-lg font-medium text-gray-900">Total Amount:</div>
                            <div class="text-xl font-bold text-amber-600">{{ $partialPaymentDebtorStats['formatted_amount'] }}</div>
                        </dd>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filament Table -->
    {{ $this->table }}
</x-filament-panels::page>
