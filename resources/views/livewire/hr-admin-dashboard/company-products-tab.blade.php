<div class="p-6">
    <style>
        .license-tooltip {
            position: relative;
            display: inline-block;
        }
        .license-tooltip .tooltip-content {
            visibility: hidden;
            position: absolute;
            z-index: 50;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background-color: #1f2937;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 14px;
            white-space: nowrap;
            margin-bottom: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .license-tooltip .tooltip-content::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #1f2937 transparent transparent transparent;
        }
        .license-tooltip:hover .tooltip-content {
            visibility: visible;
        }
        .tooltip-active {
            color: #22c55e;
            font-weight: 700;
        }
        .tooltip-inactive {
            color: #ef4444;
        }
    </style>

    <div class="mb-4">
        <h4 class="text-lg font-semibold text-gray-900 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
            </svg>
            Total License
        </h4>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full table-fixed divide-y divide-gray-200 border border-gray-200 rounded-lg">
            <thead class="bg-gray-50">
                <tr>
                    <th style="width: 11.11%;" class="px-2 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">User Account</th>
                    <th style="width: 11.11%;" class="px-2 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Attendance User</th>
                    <th style="width: 11.11%;" class="px-2 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Leave User</th>
                    <th style="width: 11.11%;" class="px-2 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Claim User</th>
                    <th style="width: 11.11%;" class="px-2 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Payroll User</th>
                    <th style="width: 11.11%;" class="px-2 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Onboarding & Offboarding</th>
                    <th style="width: 11.11%;" class="px-2 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Recruitment</th>
                    <th style="width: 11.11%;" class="px-2 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Appraisal</th>
                    <th style="width: 11.11%;" class="px-2 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Training</th>
                </tr>
            </thead>
            <tbody class="bg-white">
                <tr>
                    <td class="px-2 py-4 text-center">
                        <div class="license-tooltip">
                            <span class="text-2xl font-bold text-blue-600 cursor-help border-b border-dashed border-blue-400">{{ $productData['user_account']['total'] }}</span>
                            <div class="tooltip-content">
                                <div class="tooltip-active">Active: {{ $productData['user_account']['active'] }}</div>
                                <div class="tooltip-inactive">Inactive: {{ $productData['user_account']['inactive'] }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-2 py-4 text-center">
                        <div class="license-tooltip">
                            <span class="text-2xl font-bold text-blue-600 cursor-help border-b border-dashed border-blue-400">{{ $productData['attendance_user']['total'] }}</span>
                            <div class="tooltip-content">
                                <div class="tooltip-active">Active: {{ $productData['attendance_user']['active'] }}</div>
                                <div class="tooltip-inactive">Inactive: {{ $productData['attendance_user']['inactive'] }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-2 py-4 text-center">
                        <div class="license-tooltip">
                            <span class="text-2xl font-bold text-blue-600 cursor-help border-b border-dashed border-blue-400">{{ $productData['leave_user']['total'] }}</span>
                            <div class="tooltip-content">
                                <div class="tooltip-active">Active: {{ $productData['leave_user']['active'] }}</div>
                                <div class="tooltip-inactive">Inactive: {{ $productData['leave_user']['inactive'] }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-2 py-4 text-center">
                        <div class="license-tooltip">
                            <span class="text-2xl font-bold text-blue-600 cursor-help border-b border-dashed border-blue-400">{{ $productData['claim_user']['total'] }}</span>
                            <div class="tooltip-content">
                                <div class="tooltip-active">Active: {{ $productData['claim_user']['active'] }}</div>
                                <div class="tooltip-inactive">Inactive: {{ $productData['claim_user']['inactive'] }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-2 py-4 text-center">
                        <div class="license-tooltip">
                            <span class="text-2xl font-bold text-blue-600 cursor-help border-b border-dashed border-blue-400">{{ $productData['payroll_user']['total'] }}</span>
                            <div class="tooltip-content">
                                <div class="tooltip-active">Active: {{ $productData['payroll_user']['active'] }}</div>
                                <div class="tooltip-inactive">Inactive: {{ $productData['payroll_user']['inactive'] }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-2 py-4 text-center">
                        <div class="license-tooltip">
                            <span class="text-2xl font-bold text-blue-600 cursor-help border-b border-dashed border-blue-400">{{ $productData['onboarding_offboarding']['total'] }}</span>
                            <div class="tooltip-content">
                                <div class="tooltip-active">Active: {{ $productData['onboarding_offboarding']['active'] }}</div>
                                <div class="tooltip-inactive">Inactive: {{ $productData['onboarding_offboarding']['inactive'] }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-2 py-4 text-center">
                        <div class="license-tooltip">
                            <span class="text-2xl font-bold text-blue-600 cursor-help border-b border-dashed border-blue-400">{{ $productData['recruitment']['total'] }}</span>
                            <div class="tooltip-content">
                                <div class="tooltip-active">Active: {{ $productData['recruitment']['active'] }}</div>
                                <div class="tooltip-inactive">Inactive: {{ $productData['recruitment']['inactive'] }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-2 py-4 text-center">
                        <div class="license-tooltip">
                            <span class="text-2xl font-bold text-blue-600 cursor-help border-b border-dashed border-blue-400">{{ $productData['appraisal']['total'] }}</span>
                            <div class="tooltip-content">
                                <div class="tooltip-active">Active: {{ $productData['appraisal']['active'] }}</div>
                                <div class="tooltip-inactive">Inactive: {{ $productData['appraisal']['inactive'] }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-2 py-4 text-center">
                        <div class="license-tooltip">
                            <span class="text-2xl font-bold text-blue-600 cursor-help border-b border-dashed border-blue-400">{{ $productData['training']['total'] }}</span>
                            <div class="tooltip-content">
                                <div class="tooltip-active">Active: {{ $productData['training']['active'] }}</div>
                                <div class="tooltip-inactive">Inactive: {{ $productData['training']['inactive'] }}</div>
                            </div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- License Table (Grouped by Invoice) --}}
    <div class="flex items-center justify-between mt-6 mb-4">
        <h4 class="text-lg font-semibold text-gray-900">License</h4>
        <div class="flex items-center gap-2">
            @if($isSelectionMode)
                <button type="button" wire:click="exitSelectionMode"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-300 transition-colors">
                    Cancel
                </button>
                <button type="button" wire:click="openBulkEditModal"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-black bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    @if(count($selectedLicenseNos) === 0) disabled @endif>
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit Selected ({{ count($selectedLicenseNos) }})
                </button>
            @else
                <button type="button" wire:click="enterSelectionMode"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-black bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Bulk Edit License
                </button>
            @endif
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full table-fixed divide-y divide-gray-200 border border-gray-200 rounded-lg">
            <thead class="bg-gray-50">
                <tr>
                    @if($isSelectionMode)
                        <th style="width: 4%;" class="px-3 py-3 text-center">
                            <input type="checkbox"
                                wire:click="toggleSelectAll"
                                @checked(count($selectedLicenseNos) === count($licenseRecords) && count($licenseRecords) > 0)
                                class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer">
                        </th>
                    @endif
                    <th style="width: {{ $isSelectionMode ? '4%' : '5%' }};" class="px-3 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">No</th>
                    <th style="width: {{ $isSelectionMode ? '20%' : '22%' }};" class="px-3 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">License Type</th>
                    <th style="width: 9%;" class="px-3 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Total User</th>
                    <th style="width: 9%;" class="px-3 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Total Login</th>
                    <th style="width: 7%;" class="px-3 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Month</th>
                    <th style="width: 12%;" class="px-3 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Start Date</th>
                    <th style="width: 12%;" class="px-3 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">End Date</th>
                    <th style="width: 8%;" class="px-3 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Status</th>
                    <th style="width: {{ $isSelectionMode ? '15%' : '16%' }};" class="px-3 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white">
                @forelse($groupedLicenseRecords as $groupIndex => $group)
                    @if($group['type'] === 'TRIAL')
                        {{-- TRIAL: Direct rows without header --}}
                        @foreach($group['products'] as $product)
                            @php
                                $today = now()->startOfDay();
                                $startDate = \Carbon\Carbon::parse($product['start_date'])->startOfDay();
                                $endDate = \Carbon\Carbon::parse($product['end_date'])->endOfDay();
                                $isActive = $today->between($startDate, $endDate);
                            @endphp
                            <tr class="hover:bg-gray-50 border-b border-gray-100 {{ $isSelectionMode && in_array($product['no'], $selectedLicenseNos) ? 'bg-blue-50' : '' }}">
                                @if($isSelectionMode)
                                    <td class="px-3 py-3 text-center">
                                        <input type="checkbox"
                                            wire:click="toggleLicenseSelection({{ $product['no'] }})"
                                            @checked(in_array($product['no'], $selectedLicenseNos))
                                            class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer">
                                    </td>
                                @endif
                                <td class="px-3 py-3 text-sm text-gray-900">{{ $product['no'] }}</td>
                                <td class="px-3 py-3 text-sm text-gray-900">
                                    <span class="inline-flex px-2 py-0.5 mr-2 text-xs font-semibold rounded bg-amber-100 text-amber-800">TRIAL</span>
                                    {{ $product['license_type'] }}
                                </td>
                                <td class="px-3 py-3 text-sm text-center text-gray-900">{{ $product['total_user'] }}</td>
                                <td class="px-3 py-3 text-sm text-center text-gray-900">{{ $product['total_login'] }}</td>
                                <td class="px-3 py-3 text-sm text-center text-gray-900">{{ $product['month'] }}</td>
                                <td class="px-3 py-3 text-sm text-center text-gray-900">{{ $product['start_date'] }}</td>
                                <td class="px-3 py-3 text-sm text-center text-gray-900">{{ $product['end_date'] }}</td>
                                <td class="px-3 py-3 text-sm text-center">
                                    <span class="inline-flex items-center justify-center" title="{{ $isActive ? 'Active' : 'Inactive' }}">
                                        <span style="width: 12px; height: 12px; border-radius: 50%; display: inline-block; background-color: {{ $isActive ? '#22c55e' : '#ef4444' }};"></span>
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-sm text-center">
                                    @if($isSelectionMode)
                                        <span class="text-gray-400 cursor-not-allowed">Edit</span>
                                    @else
                                        <button type="button" wire:click="openEditModal({{ $product['no'] }})" class="text-blue-600 hover:text-blue-800 hover:underline">Edit</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @else
                        {{-- PAID: Collapsible group with header --}}
                        @php
                            // Calculate totals for this PAID group
                            $groupTotalUser = collect($group['products'])->sum('total_user');
                            $groupTotalLogin = collect($group['products'])->sum('total_login');
                            $groupStartDate = collect($group['products'])->min('start_date');
                            $groupEndDate = collect($group['products'])->max('end_date');
                            $today = now()->startOfDay();
                            $groupActiveCount = collect($group['products'])->filter(function($p) use ($today) {
                                $start = \Carbon\Carbon::parse($p['start_date'])->startOfDay();
                                $end = \Carbon\Carbon::parse($p['end_date'])->endOfDay();
                                return $today->between($start, $end);
                            })->count();
                            $groupInactiveCount = count($group['products']) - $groupActiveCount;
                            $groupNos = collect($group['products'])->pluck('no')->toArray();
                            $allGroupSelected = count(array_intersect($selectedLicenseNos, $groupNos)) === count($groupNos) && count($groupNos) > 0;
                        @endphp
                        <tr class="bg-gray-100 border-t border-gray-300 cursor-pointer hover:bg-gray-200"
                            x-data="{ expanded: true }"
                            @click="expanded = !expanded; $dispatch('toggle-paid-{{ $groupIndex }}', { expanded: expanded })">
                            @if($isSelectionMode)
                                <td class="px-3 py-2 text-center" @click.stop>
                                    <input type="checkbox"
                                        wire:click="toggleGroupSelection('{{ $group['invoice_no'] }}')"
                                        @checked($allGroupSelected)
                                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer">
                                </td>
                            @endif
                            <td class="px-3 py-2 text-xs">
                                <span class="inline-flex items-center">
                                    <svg class="w-4 h-4 mr-1 text-gray-500 transition-transform duration-200" :class="{ 'rotate-90': expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                    <span class="inline-flex px-2 py-0.5 font-semibold rounded bg-green-100 text-green-800">
                                        PAID
                                    </span>
                                </span>
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-600">
                                <a href="{{ route('pdf.license-proforma-invoice', ['softwareHandover' => $softwareHandoverId, 'invoiceNo' => $group['invoice_no']]) }}"
                                   target="_blank"
                                   @click.stop
                                   class="font-semibold text-blue-600 underline hover:text-blue-800 cursor-pointer">
                                    {{ $group['invoice_no'] }}
                                </a>
                                <span class="ml-2 text-gray-400">({{ count($group['products']) }} items)</span>
                            </td>
                            <td class="px-3 py-2 text-xs text-center font-semibold text-gray-700">{{ $groupTotalUser }}</td>
                            <td class="px-3 py-2 text-xs text-center font-semibold text-gray-700">{{ $groupTotalLogin }}</td>
                            <td class="px-3 py-2 text-xs text-center text-gray-500">-</td>
                            <td class="px-3 py-2 text-xs text-center font-medium text-gray-600">{{ $groupStartDate }}</td>
                            <td class="px-3 py-2 text-xs text-center font-medium text-gray-600">{{ $groupEndDate }}</td>
                            <td class="px-3 py-2 text-xs text-center">
                                @php
                                    $groupIsActive = $groupActiveCount >= $groupInactiveCount;
                                @endphp
                                <span class="inline-flex items-center justify-center" title="{{ $groupActiveCount }} Active, {{ $groupInactiveCount }} Inactive">
                                    <span style="width: 12px; height: 12px; border-radius: 50%; display: inline-block; background-color: {{ $groupIsActive ? '#22c55e' : '#ef4444' }};"></span>
                                </span>
                            </td>
                            <td class="px-3 py-2 text-xs text-center"></td>
                        </tr>

                        {{-- PAID Product Detail Rows --}}
                        @foreach($group['products'] as $product)
                            @php
                                $today = now()->startOfDay();
                                $startDate = \Carbon\Carbon::parse($product['start_date'])->startOfDay();
                                $endDate = \Carbon\Carbon::parse($product['end_date'])->endOfDay();
                                $isActive = $today->between($startDate, $endDate);
                            @endphp
                            <tr class="hover:bg-gray-50 border-b border-gray-100 {{ $isSelectionMode && in_array($product['no'], $selectedLicenseNos) ? 'bg-blue-50' : '' }}"
                                x-data="{ show: true }"
                                x-show="show"
                                @toggle-paid-{{ $groupIndex }}.window="show = $event.detail.expanded"
                                x-transition>
                                @if($isSelectionMode)
                                    <td class="px-3 py-3 text-center">
                                        <input type="checkbox"
                                            wire:click="toggleLicenseSelection({{ $product['no'] }})"
                                            @checked(in_array($product['no'], $selectedLicenseNos))
                                            class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer">
                                    </td>
                                @endif
                                <td class="px-3 py-3 {{ $isSelectionMode ? '' : 'pl-6' }} text-sm text-gray-900">{{ $product['no'] }}</td>
                                <td class="px-3 py-3 text-sm text-gray-900">{{ $product['license_type'] }}</td>
                                <td class="px-3 py-3 text-sm text-center text-gray-900">{{ $product['total_user'] }}</td>
                                <td class="px-3 py-3 text-sm text-center text-gray-900">{{ $product['total_login'] }}</td>
                                <td class="px-3 py-3 text-sm text-center text-gray-900">{{ $product['month'] }}</td>
                                <td class="px-3 py-3 text-sm text-center text-gray-900">{{ $product['start_date'] }}</td>
                                <td class="px-3 py-3 text-sm text-center text-gray-900">{{ $product['end_date'] }}</td>
                                <td class="px-3 py-3 text-sm text-center">
                                    <span class="inline-flex items-center justify-center" title="{{ $isActive ? 'Active' : 'Inactive' }}">
                                        <span style="width: 12px; height: 12px; border-radius: 50%; display: inline-block; background-color: {{ $isActive ? '#22c55e' : '#ef4444' }};"></span>
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-sm text-center">
                                    @if($isSelectionMode)
                                        <span class="text-gray-400 cursor-not-allowed">Edit</span>
                                    @else
                                        <button type="button" wire:click="openEditModal({{ $product['no'] }})" @click.stop class="text-blue-600 hover:text-blue-800 hover:underline">Edit</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endif
                @empty
                    <tr>
                        <td colspan="{{ $isSelectionMode ? '10' : '9' }}" class="px-3 py-8 text-center text-gray-500">
                            No license records found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Edit License Modal --}}
    @if($showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                {{-- Background overlay --}}
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeEditModal"></div>

                {{-- Modal panel --}}
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit="saveLicense">
                        {{-- Modal Header --}}
                        <div class="bg-gray-50 px-4 py-4 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900" id="modal-title">
                                    Edit License
                                </h3>
                                <button type="button" wire:click="closeEditModal" class="text-gray-400 hover:text-gray-500">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">{{ $editingLicenseType }}</p>
                        </div>

                        {{-- Modal Body --}}
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                            <div class="space-y-4">
                                {{-- Total User --}}
                                <div>
                                    <label for="edit_total_user" class="block text-sm font-medium text-gray-700">Total User</label>
                                    <input type="number" id="edit_total_user" wire:model="editForm.total_user"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        min="1" required>
                                    @error('editForm.total_user') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                {{-- Billing Cycle in Month --}}
                                <div>
                                    <label for="edit_month" class="block text-sm font-medium text-gray-700">Billing Cycle in Month</label>
                                    <input type="number" id="edit_month" wire:model="editForm.month"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        min="1" max="36" required>
                                    @error('editForm.month') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                {{-- Start Date --}}
                                <div>
                                    <label for="edit_start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                                    <input type="date" id="edit_start_date" wire:model="editForm.start_date"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        required>
                                    @error('editForm.start_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                {{-- End Date --}}
                                <div>
                                    <label for="edit_end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                                    <input type="date" id="edit_end_date" wire:model="editForm.end_date"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        required>
                                    @error('editForm.end_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                {{-- Status --}}
                                <div>
                                    <label for="edit_status" class="block text-sm font-medium text-gray-700">Status</label>
                                    <select id="edit_status" wire:model="editForm.status"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                    @error('editForm.status') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Modal Footer --}}
                        <div class="bg-gray-50 px-4 py-4 sm:px-6 flex flex-row justify-end gap-3 border-t border-gray-200">
                            <button type="button" wire:click="closeEditModal"
                                class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-6 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Cancel
                            </button>
                            <button type="submit"
                                class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-6 py-2 bg-blue-600 text-sm font-medium text-black hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Bulk Edit License Modal --}}
    @if($showBulkEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="bulk-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                {{-- Background overlay --}}
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeBulkEditModal"></div>

                {{-- Modal panel --}}
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit="saveBulkEdit">
                        {{-- Modal Header --}}
                        <div class="bg-gray-50 px-4 py-4 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900" id="bulk-modal-title">
                                        Bulk Edit License
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">Update multiple licenses at once. Check the fields you want to modify.</p>
                                </div>
                                <button type="button" wire:click="closeBulkEditModal" class="text-gray-400 hover:text-gray-500">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Modal Body --}}
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                            <div class="space-y-4">
                                {{-- Info Banner --}}
                                <div class="flex items-start p-3 bg-blue-50 border border-blue-200 rounded-md">
                                    <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div class="text-sm text-blue-700 flex-1">
                                        <p class="font-medium">Editing {{ count($selectedLicenseNos) }} selected license(s):</p>
                                        <div class="mt-2 max-h-32 overflow-y-auto">
                                            <table class="w-full text-xs">
                                                <thead>
                                                    <tr class="text-left text-blue-800">
                                                        <th class="pb-1 pr-3">License</th>
                                                        <th class="pb-1 pr-3 whitespace-nowrap" style="width: 80px;">Start Date</th>
                                                        <th class="pb-1 whitespace-nowrap" style="width: 80px;">End Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($this->getSelectedLicenseDetails() as $license)
                                                        <tr>
                                                            <td class="py-0.5 pr-3">{{ $license['name'] }}</td>
                                                            <td class="py-0.5 pr-3 whitespace-nowrap">{{ $license['start_date'] }}</td>
                                                            <td class="py-0.5 whitespace-nowrap">{{ $license['end_date'] }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        <p class="mt-2 text-xs text-blue-600">Only checked fields below will be modified.</p>
                                    </div>
                                </div>

                                {{-- Total User --}}
                                <div class="flex items-start gap-4">
                                    <div class="flex items-center h-9 mt-6">
                                        <input type="checkbox" id="bulk_enable_total_user" wire:model.live="bulkEditEnabled.total_user"
                                            class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    </div>
                                    <div class="flex-1">
                                        <label for="bulk_total_user" class="block text-sm font-medium text-gray-700">Total User</label>
                                        <input type="number" id="bulk_total_user" wire:model="bulkEditForm.total_user"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
                                            min="1" placeholder="Enter total users"
                                            @if(!$bulkEditEnabled['total_user']) disabled @endif>
                                        @error('bulkEditForm.total_user') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- Start Date --}}
                                <div class="flex items-start gap-4">
                                    <div class="flex items-center h-9 mt-6">
                                        <input type="checkbox" id="bulk_enable_start_date" wire:model.live="bulkEditEnabled.start_date"
                                            class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    </div>
                                    <div class="flex-1">
                                        <label for="bulk_start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                                        <input type="date" id="bulk_start_date" wire:model="bulkEditForm.start_date"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
                                            @if(!$bulkEditEnabled['start_date']) disabled @endif>
                                        @error('bulkEditForm.start_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- End Date --}}
                                <div class="flex items-start gap-4">
                                    <div class="flex items-center h-9 mt-6">
                                        <input type="checkbox" id="bulk_enable_end_date" wire:model.live="bulkEditEnabled.end_date"
                                            class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    </div>
                                    <div class="flex-1">
                                        <label for="bulk_end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                                        <input type="date" id="bulk_end_date" wire:model="bulkEditForm.end_date"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
                                            @if(!$bulkEditEnabled['end_date']) disabled @endif>
                                        @error('bulkEditForm.end_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- Status --}}
                                <div class="flex items-start gap-4">
                                    <div class="flex items-center h-9 mt-6">
                                        <input type="checkbox" id="bulk_enable_status" wire:model.live="bulkEditEnabled.status"
                                            class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    </div>
                                    <div class="flex-1">
                                        <label for="bulk_status" class="block text-sm font-medium text-gray-700">Status</label>
                                        <select id="bulk_status" wire:model="bulkEditForm.status"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
                                            @if(!$bulkEditEnabled['status']) disabled @endif>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                        @error('bulkEditForm.status') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Modal Footer --}}
                        <div class="bg-gray-50 px-4 py-4 sm:px-6 flex flex-row justify-end gap-3 border-t border-gray-200">
                            <button type="button" wire:click="closeBulkEditModal"
                                class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-6 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Cancel
                            </button>
                            <button type="submit"
                                class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-6 py-2 bg-blue-600 text-sm font-medium text-black hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Submit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Proforma Invoice Modal --}}
    @if($showPiModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="pi-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                {{-- Background overlay --}}
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closePiModal"></div>

                {{-- Modal panel --}}
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    {{-- Modal Header --}}
                    <div class="bg-gradient-to-r from-cyan-600 to-blue-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <img src="{{ asset('img/logo-ttc.png') }}" alt="TimeTec" class="h-10 w-auto">
                                <div>
                                    <h3 class="text-xl font-bold text-white" id="pi-modal-title">
                                        PROFORMA INVOICE
                                    </h3>
                                </div>
                            </div>
                            <button type="button" wire:click="closePiModal" class="text-white hover:text-cyan-200">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Modal Body --}}
                    <div class="bg-white px-6 py-6 max-h-[75vh] overflow-y-auto">
                        {{-- Loading State --}}
                        @if($piLoading)
                            <div class="flex items-center justify-center py-12">
                                <div class="text-center">
                                    <svg class="animate-spin h-10 w-10 text-cyan-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <p class="text-gray-500">Loading proforma invoice...</p>
                                </div>
                            </div>
                        @elseif($piError)
                            {{-- Error State --}}
                            <div class="text-center py-8">
                                <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                </div>
                                <h4 class="text-lg font-medium text-gray-800 mb-2">Error Loading Invoice</h4>
                                <p class="text-gray-500 text-sm">{{ $piError }}</p>
                            </div>
                        @elseif(!empty($apiPiData))
                            {{-- API-based PI Display (TimeTec Backend Format) --}}
                            @php
                                $pi = $apiPiData;
                                $billTo = $pi['bill_to'] ?? [];
                                $items = $pi['items'] ?? [];
                                $currency = $pi['currency'] ?? 'MYR';
                                $subtotal = $pi['subtotal'] ?? 0;
                                $discount = $pi['discount'] ?? 0;
                                $sst = $pi['sst'] ?? 0;
                                $sstRate = $pi['sst_rate'] ?? 8;
                                $totalAmount = $pi['total_amount'] ?? $pi['amount_due'] ?? 0;
                                $status = $pi['status'] ?? 'Pending';
                            @endphp

                            {{-- Bill To & Invoice Info Section --}}
                            <div class="grid grid-cols-2 gap-8 mb-6">
                                {{-- Bill To --}}
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Bill To:</h4>
                                    <p class="font-semibold text-gray-900">{{ $billTo['company_name'] ?? ($companyData['company_name'] ?? '-') }}</p>
                                    <p class="text-sm text-gray-600">{{ $billTo['email'] ?? '-' }}</p>
                                    <p class="text-sm text-gray-600">{{ $billTo['registration_no'] ?? '-' }}</p>
                                    <p class="text-sm text-gray-600">{{ $billTo['address'] ?? '-' }}</p>
                                </div>

                                {{-- Invoice Info --}}
                                <div class="border border-cyan-200 rounded-lg p-4 bg-cyan-50">
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">P. Invoice No:</span>
                                            <span class="font-semibold text-cyan-700">{{ $selectedInvoiceNo }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Date:</span>
                                            <span class="font-medium">{{ $pi['date'] ?? $pi['invoice_date'] ?? '-' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Status:</span>
                                            <span class="font-semibold {{ $status === 'PAID' ? 'text-green-600' : ($status === 'Cancel' ? 'text-red-600' : 'text-yellow-600') }}">
                                                {{ $status }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">TRX Rate (RM):</span>
                                            <span class="font-medium">{{ $pi['trx_rate'] ?? '1' }}</span>
                                        </div>
                                    </div>
                                    <div class="mt-4 pt-3 border-t border-cyan-300">
                                        <div class="flex justify-between items-center">
                                            <span class="font-semibold text-cyan-800">Amount Due:</span>
                                            <span class="text-lg font-bold text-cyan-700">{{ $currency }} {{ number_format($totalAmount, 2) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Items Table --}}
                            <div class="border border-gray-200 rounded-lg overflow-hidden mb-6">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase w-12">No.</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Description</th>
                                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase w-16">Qty</th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase w-20">Price</th>
                                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase w-24">Billing Cycle</th>
                                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase w-20">Discount</th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase w-24">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @forelse($items as $index => $item)
                                            <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                                                <td class="px-4 py-3 text-sm text-gray-700">{{ $index + 1 }}.</td>
                                                <td class="px-4 py-3 text-sm text-gray-900">
                                                    {{ $item['description'] ?? '-' }}
                                                    @if(!empty($item['period']))
                                                        <br><span class="text-xs text-gray-500">[{{ $item['period'] }}]</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-sm text-center text-gray-700">{{ $item['qty'] ?? $item['quantity'] ?? 0 }}</td>
                                                <td class="px-4 py-3 text-sm text-right text-gray-700">{{ number_format($item['price'] ?? $item['unit_price'] ?? 0, 2) }}</td>
                                                <td class="px-4 py-3 text-sm text-center text-gray-700">{{ $item['billing_cycle'] ?? $item['month'] ?? '-' }}</td>
                                                <td class="px-4 py-3 text-sm text-center text-gray-700">{{ $item['discount'] ?? '0%' }}</td>
                                                <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">{{ number_format($item['amount'] ?? 0, 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="px-4 py-6 text-center text-gray-500 text-sm">No items found</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {{-- Totals Section --}}
                            <div class="flex justify-end mb-6">
                                <div class="w-72 border border-gray-200 rounded-lg overflow-hidden">
                                    <table class="w-full">
                                        <tbody class="divide-y divide-gray-200">
                                            <tr class="bg-gray-50">
                                                <td class="px-4 py-2 text-sm text-gray-600 text-right">Discount:</td>
                                                <td class="px-4 py-2 text-sm font-medium text-right w-28">-{{ number_format($discount, 2) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="px-4 py-2 text-sm text-gray-600 text-right">Subtotal:</td>
                                                <td class="px-4 py-2 text-sm font-medium text-right">{{ number_format($subtotal, 2) }}</td>
                                            </tr>
                                            <tr class="bg-gray-50">
                                                <td class="px-4 py-2 text-sm text-gray-600 text-right">SST ({{ $sstRate }}%):</td>
                                                <td class="px-4 py-2 text-sm font-medium text-right">{{ number_format($sst, 2) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="px-4 py-2 text-sm text-gray-600 text-right">Total sales incl SST:</td>
                                                <td class="px-4 py-2 text-sm font-medium text-right">{{ number_format($subtotal + $sst, 2) }}</td>
                                            </tr>
                                            <tr class="bg-cyan-50">
                                                <td class="px-4 py-3 text-sm font-semibold text-cyan-800 text-right">Amount Due ({{ $currency }}):</td>
                                                <td class="px-4 py-3 text-base font-bold text-cyan-700 text-right">{{ number_format($totalAmount, 2) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- Terms & Conditions --}}
                            <div class="border-t border-gray-200 pt-4">
                                <h5 class="text-sm font-semibold text-gray-700 mb-2">Terms & Conditions:</h5>
                                <ol class="text-xs text-gray-500 space-y-1 list-decimal list-inside">
                                    <li>Please keep this Invoice for your future reference and correspondence with TimeTec Cloud Sdn Bhd (832542-W)</li>
                                    <li>All purchases with TimeTec Cloud Sdn Bhd are bound by the Terms & Conditions.</li>
                                    <li>Questions about your invoice, email us at info@timeteccloud.com.</li>
                                    <li>Bank Account Details (for TT payment): TimeTec Cloud Sdn Bhd (832542-W), United Overseas Bank (M) Bhd, Puchong branch</li>
                                </ol>
                            </div>

                            {{-- View Full Page Button --}}
                            @if($softwareHandoverId)
                                <div class="mt-6 pt-4 border-t border-gray-200 flex justify-center">
                                    <a href="{{ route('pdf.license-proforma-invoice', ['softwareHandover' => $softwareHandoverId, 'invoiceNo' => $selectedInvoiceNo]) }}"
                                       target="_blank"
                                       class="inline-flex items-center px-6 py-2.5 text-sm font-medium text-white bg-cyan-600 rounded-lg hover:bg-cyan-700 transition-colors shadow-sm">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                        View Full Page
                                    </a>
                                </div>
                            @endif
                        @elseif(count($piData) > 0)
                            {{-- Local Quotation-based PI Display (Fallback) --}}
                            @foreach($piData as $index => $pi)
                                <div class="mb-6 {{ $index > 0 ? 'pt-6 border-t border-gray-200' : '' }}">
                                    {{-- PI Header --}}
                                    <div class="flex items-center justify-between mb-4">
                                        <div>
                                            <h4 class="text-md font-semibold text-gray-900">{{ $pi['pi_reference_no'] }}</h4>
                                            <p class="text-sm text-gray-500">{{ $pi['company_name'] }}</p>
                                        </div>
                                        <a href="{{ url('/generate-proforma-invoice-pdf/' . $pi['id']) }}"
                                           target="_blank"
                                           class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-cyan-600 bg-cyan-50 rounded-md hover:bg-cyan-100 transition-colors">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            View PDF
                                        </a>
                                    </div>

                                    {{-- PI Info Grid --}}
                                    <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
                                        <div>
                                            <span class="text-gray-500">Date:</span>
                                            <span class="ml-2 font-medium text-gray-900">{{ $pi['quotation_date'] }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Currency:</span>
                                            <span class="ml-2 font-medium text-gray-900">{{ $pi['currency'] }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Salesperson:</span>
                                            <span class="ml-2 font-medium text-gray-900">{{ $pi['salesperson'] }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Total:</span>
                                            <span class="ml-2 font-medium text-green-600">{{ $pi['currency'] }} {{ number_format($pi['total_amount'], 2) }}</span>
                                        </div>
                                    </div>

                                    {{-- PI Items Table --}}
                                    @if(count($pi['items']) > 0)
                                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Qty</th>
                                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-200">
                                                    @foreach($pi['items'] as $item)
                                                        <tr>
                                                            <td class="px-4 py-2 text-sm text-gray-900">{{ $item['description'] }}</td>
                                                            <td class="px-4 py-2 text-sm text-center text-gray-900">{{ $item['quantity'] }}</td>
                                                            <td class="px-4 py-2 text-sm text-right text-gray-900">{{ number_format($item['unit_price'], 2) }}</td>
                                                            <td class="px-4 py-2 text-sm text-right text-gray-900">{{ number_format($item['amount'], 2) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="bg-gray-50">
                                                    <tr>
                                                        <td colspan="3" class="px-4 py-2 text-sm font-semibold text-gray-900 text-right">Total:</td>
                                                        <td class="px-4 py-2 text-sm font-semibold text-green-600 text-right">{{ $pi['currency'] }} {{ number_format($pi['total_amount'], 2) }}</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    @else
                                        <p class="text-sm text-gray-500 italic">No items found in this PI.</p>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            {{-- No Data Found --}}
                            <div class="text-center py-8">
                                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h4 class="text-lg font-medium text-gray-800 mb-2">No Proforma Invoice Found</h4>
                                <p class="text-gray-500 text-sm">The proforma invoice for this transaction is not available.</p>
                            </div>
                        @endif
                    </div>

                    {{-- Modal Footer --}}
                    <div class="bg-gray-50 px-6 py-4 flex justify-between items-center border-t border-gray-200">
                        <div class="text-xs text-gray-500">
                            Invoice No: {{ $selectedInvoiceNo }}
                        </div>
                        <button type="button" wire:click="closePiModal"
                            class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-6 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
