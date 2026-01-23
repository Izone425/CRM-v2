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
    <h4 class="text-lg font-semibold text-gray-900 mt-6 mb-4">License</h4>
    <div class="overflow-x-auto">
        <table class="w-full table-fixed divide-y divide-gray-200 border border-gray-200 rounded-lg">
            <thead class="bg-gray-50">
                <tr>
                    <th style="width: 5%;" class="px-3 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">No</th>
                    <th style="width: 35%;" class="px-3 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">License Type</th>
                    <th style="width: 8%;" class="px-3 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Unit</th>
                    <th style="width: 10%;" class="px-3 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">User Limit</th>
                    <th style="width: 10%;" class="px-3 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Total User</th>
                    <th style="width: 10%;" class="px-3 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Total Login</th>
                    <th style="width: 12%;" class="px-3 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Total Terminal</th>
                    <th style="width: 10%;" class="px-3 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white">
                @forelse($groupedLicenseRecords as $group)
                    {{-- Group Header Row --}}
                    <tr class="bg-gray-100 border-t border-gray-300">
                        <td class="px-3 py-2 text-xs">
                            <span class="inline-flex px-2 py-0.5 font-semibold rounded {{ $group['type'] === 'PAID' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $group['type'] }}
                            </span>
                        </td>
                        <td colspan="6" class="px-3 py-2 text-xs text-gray-500">
                            <span class="font-medium text-gray-700">{{ $group['invoice_no'] ?: '-' }}</span>
                            <span class="ml-3">{{ $group['month'] }} Month</span>
                            <span class="ml-3">{{ $group['start_date'] }} → {{ $group['end_date'] }}</span>
                        </td>
                        <td class="px-3 py-2 text-xs text-right text-gray-500">
                            Renewed: {{ $group['renewed'] }}
                        </td>
                    </tr>

                    {{-- Product Detail Rows --}}
                    @foreach($group['products'] as $product)
                        <tr class="hover:bg-gray-50 border-b border-gray-100">
                            <td class="px-3 py-3 pl-6 text-sm text-gray-900">{{ $product['no'] }}</td>
                            <td class="px-3 py-3 text-sm text-gray-900">{{ $product['license_type'] }}</td>
                            <td class="px-3 py-3 text-sm text-center text-gray-900">{{ $product['unit'] }}</td>
                            <td class="px-3 py-3 text-sm text-center text-blue-600 font-medium">{{ $product['user_limit'] }}</td>
                            <td class="px-3 py-3 text-sm text-center text-gray-900">{{ $product['total_user'] }}</td>
                            <td class="px-3 py-3 text-sm text-center text-gray-900">{{ $product['total_login'] }}</td>
                            <td class="px-3 py-3 text-sm text-center text-gray-900">{{ $product['total_terminal'] }}</td>
                            <td class="px-3 py-3 text-sm text-center">
                                <a href="#" class="text-blue-600 hover:text-blue-800 hover:underline">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="8" class="px-3 py-8 text-center text-gray-500">
                            No license records found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
