<x-filament::page>
    <!-- Title & Filters in One Line -->
    <div class="flex flex-col items-center justify-between mb-6 md:flex-row">
        <!-- Title -->
        <h1 class="text-2xl font-bold tracking-tight fi-header-heading text-gray-950 dark:text-white sm:text-3xl">
            Sales Forecast
        </h1>

        <!-- Filters -->
        <div class="flex items-center gap-x-4">
            <!-- Salesperson Filter -->
            @if(auth()->user()->role_id != 2)
                <div>
                    <label for="salesperson-filter" class="sr-only">Select Salesperson</label>
                    <select id="salesperson-filter"
                            wire:model.live="selectedUser"
                            class="px-4 py-2 border-gray-300 rounded-md shadow-sm">
                        <option value="">All Salespersons</option>
                        @foreach (\App\Models\User::where('role_id', 2)->pluck('name', 'id') as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <!-- Month Filter -->
            <div>
                <label for="monthFilter" class="sr-only">Select Month</label>
                <input wire:model.live="selectedMonth"
                       type="month" id="monthFilter"
                       class="px-4 py-2 border-gray-300 rounded-md shadow-sm">
            </div>
        </div>
    </div>

    <!-- Main Content Layout -->
    <div class="flex w-full min-h-screen gap-4">
        <!-- Sales Forecast Tables (Expands Automatically) -->
        <div class="flex-grow">
            {{-- <livewire:invoice-table :selectedUser="$selectedUser" :selectedMonth="$selectedMonth"/>
            <br>
            <livewire:proforma-invoice-table :selectedUser="$selectedUser" :selectedMonth="$selectedMonth"/>
            <br> --}}
            <livewire:sales-forecast-table :selectedUser="$selectedUser" :selectedMonth="$selectedMonth"/>
        </div>

        <!-- Right-Side Card (Fixed width of 300px) -->
        {{-- <div class="w-[300px] rounded-lg" style="height: fit-content; background-color: #e6e6fa4d; border: dashed 2px #cdcbeb; border-radius: 10px;">
            <div class="p-4 rounded-lg shadow-md">
                <h2 class="mb-2 text-lg font-bold text-gray-800">Sales Summary</h2>

                <!-- Invoice -->
                <div class="p-3 mb-2 bg-gray-100 rounded-lg">
                    <p class="text-sm font-semibold text-gray-700">Invoice:</p>
                    <p class="text-lg font-bold text-red-900">
                        RM {{ number_format($invoiceTotal ?? 0, 2) }}
                    </p>
                </div>

                <!-- Proforma Invoice -->
                <div class="p-3 mb-2 bg-gray-100 rounded-lg">
                    <p class="text-sm font-semibold text-gray-700">Proforma Invoice:</p>
                    <p class="text-lg font-bold text-red-900">
                        RM {{ number_format($proformaInvoiceTotal ?? 0, 2) }}
                    </p>
                </div>

                <!-- Sales Forecast (Hot Deals for Selected Month) -->
                <div class="p-3 bg-red-100 rounded-lg">
                    <p class="text-sm font-semibold text-red-700">Sales Forecast (Hot Deals):</p>
                    <p class="text-lg font-bold text-red-900">
                        RM {{ number_format($hotDealsTotal ?? 0, 2) }}
                    </p>
                </div>

                <!-- Total Calculation (Invoice + Proforma Invoice + Sales Forecast) -->
                <div class="p-3 mt-2 border border-green-300 rounded-lg" style="height: fit-content; background-color: #e6e6fa4d; border: dashed 2px #cdcbeb; border-radius: 10px;">
                    <p class="text-sm font-semibold text-green-700">Total:</p>
                    <p class="text-lg font-bold text-green-900">
                        RM {{ number_format($invoiceTotal + $proformaInvoiceTotal + ($hotDealsTotal ?? 0), 2) }}
                    </p>
                </div>
            </div>
        </div> --}}
    </div>
</x-filament::page>
