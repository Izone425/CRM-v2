<div class="p-6">

    {{-- Search/Filter Bar --}}
    <table style="width: 100%; border-spacing: 8px; border-collapse: separate;" class="mb-8">
        <tr>
            <td style="width: 30%;">
                <input type="text"
                       wire:model.defer="search"
                       wire:keydown.enter="searchCustomers"
                       placeholder="Search by name..."
                       style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; outline: none;" />
            </td>
            <td style="width: 15%;">
                <select wire:model.defer="statusFilter"
                        style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; outline: none; background: white;">
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </td>
            <td style="width: 15%;">
                <input type="date"
                       wire:model.defer="startDate"
                       style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; outline: none;" />
            </td>
            <td style="width: 15%;">
                <input type="date"
                       wire:model.defer="endDate"
                       style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; outline: none;" />
            </td>
            <td style="width: 10%;">
                <button wire:click="searchCustomers"
                        wire:loading.attr="disabled"
                        style="width: 100%; padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 6px; font-size: 14px; font-weight: 500; cursor: pointer; white-space: nowrap;">
                    Search
                </button>
            </td>
            <td style="width: 10%;">
                <button wire:click="resetFilters"
                        style="width: 100%; padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 6px; font-size: 14px; font-weight: 500; cursor: pointer; white-space: nowrap;">
                    Reset
                </button>
            </td>
        </tr>
    </table>

    {{-- Resellers Section --}}
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-3">
            <h3 class="text-lg font-bold text-gray-800">Resellers</h3>
            <span class="text-sm text-green-600 font-medium">Active: {{ $resellerActiveCount }}</span>
            <span class="text-sm text-gray-500">| Inactive: {{ $resellerInactiveCount }}</span>
        </div>
<div class="h-4"></div>
        <div class="overflow-x-auto border border-gray-200 rounded-lg">
            <table class="w-full divide-y divide-gray-200 table-fixed">
                <thead class="bg-gray-100">
                    <tr>
                        <th style="width: 15%;" class="px-4 py-2.5 text-left text-xs font-semibold text-gray-700 tracking-wider">
                            Reseller Id
                        </th>
                        <th style="width: 40%;" class="px-4 py-2.5 text-left text-xs font-semibold text-gray-700 tracking-wider">
                            Reseller Name
                        </th>
                        <th style="width: 25%;" class="px-4 py-2.5 text-center text-xs font-semibold text-gray-700 tracking-wider">
                            Joined Date
                        </th>
                        <th style="width: 20%;" class="px-4 py-2.5 text-center text-xs font-semibold text-gray-700 tracking-wider">
                            Status
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($resellers as $index => $reseller)
                        <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }} hover:bg-gray-100">
                            <td class="px-4 py-2.5 whitespace-nowrap">
                                @if($reseller['software_handover_id'])
                                    <a href="{{ url('/admin/hr-company-license-details?' . http_build_query(['softwareHandoverId' => $reseller['software_handover_id']])) }}"
                                       class="text-sm text-blue-600 hover:text-blue-800 hover:underline font-medium">
                                        {{ $reseller['id'] }}
                                    </a>
                                @else
                                    <span class="text-sm text-gray-900">{{ $reseller['id'] }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-sm text-gray-900">
                                {{ $reseller['name'] }}
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-700 text-center">
                                {{ $reseller['joined_date'] }}
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap text-center">
                                @if(strtolower($reseller['status']) === 'active')
                                    <span class="text-sm font-medium text-green-600">Active</span>
                                @else
                                    <span class="text-sm font-medium text-gray-500">{{ $reseller['status'] }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center">
                                <p class="text-gray-500 text-sm">No resellers found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
<div class="h-4"></div>
    {{-- Customers (Subscriber) Section --}}
    <div>
        <div class="flex items-center gap-3 mb-3">
            <h3 class="text-lg font-bold text-gray-800">Customers (Subscriber)</h3>
            <span class="text-sm text-green-600 font-medium">Active: {{ $subscriberActiveCount }}</span>
            <span class="text-sm text-gray-500">| Inactive: {{ $subscriberInactiveCount }}</span>
        </div>
<div class="h-4"></div>
        <div class="overflow-x-auto border border-gray-200 rounded-lg">
            <table class="w-full divide-y divide-gray-200 table-fixed">
                <thead class="bg-gray-100">
                    <tr>
                        <th style="width: 15%;" class="px-4 py-2.5 text-left text-xs font-semibold text-gray-700 tracking-wider">
                            Customer Id
                        </th>
                        <th style="width: 40%;" class="px-4 py-2.5 text-left text-xs font-semibold text-gray-700 tracking-wider">
                            Customer Name
                        </th>
                        <th style="width: 25%;" class="px-4 py-2.5 text-center text-xs font-semibold text-gray-700 tracking-wider">
                            Joined Date
                        </th>
                        <th style="width: 20%;" class="px-4 py-2.5 text-center text-xs font-semibold text-gray-700 tracking-wider">
                            Status
                        </th>
                    </tr>
                </thead>
                
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($subscribers as $index => $subscriber)
                        <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }} hover:bg-gray-100">
                            <td class="px-4 py-2.5 whitespace-nowrap">
                                @if($subscriber['software_handover_id'])
                                    <a href="{{ url('/admin/hr-company-license-details?' . http_build_query(['softwareHandoverId' => $subscriber['software_handover_id']])) }}"
                                       class="text-sm text-blue-600 hover:text-blue-800 hover:underline font-medium">
                                        {{ $subscriber['id'] }}
                                    </a>
                                @else
                                    <span class="text-sm text-gray-900">{{ $subscriber['id'] }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-sm text-gray-900">
                                {{ $subscriber['name'] }}
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-700 text-center">
                                {{ $subscriber['joined_date'] }}
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap text-center">
                                @if(strtolower($subscriber['status']) === 'active')
                                    <span class="text-sm font-medium text-green-600">Active</span>
                                @else
                                    <span class="text-sm font-medium text-gray-500">{{ $subscriber['status'] }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center">
                                <p class="text-gray-500 text-sm">No subscribers found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
