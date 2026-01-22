<div class="p-6">
    {{-- Active License Section --}}
    <div class="mb-8">
        <h4 class="mb-4 text-lg font-semibold text-gray-900 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
            </svg>
            Active License
        </h4>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
            @foreach($licenseData as $module => $count)
                <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm">
                    <div class="text-2xl font-bold text-blue-600">{{ $count }}</div>
                    <div class="text-sm text-gray-600">{{ $module }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Summary Section --}}
    <div class="p-6 bg-gray-50 rounded-lg border border-gray-200">
        <h4 class="mb-4 text-lg font-semibold text-gray-900 flex items-center">
            <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            Summary
        </h4>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            {{-- Total Users --}}
            <div class="p-4 bg-white rounded-lg border border-gray-200">
                <div class="text-sm font-medium text-gray-500 uppercase">Total Users</div>
                <div class="mt-2 text-3xl font-bold text-gray-900">{{ $summaryData['total_users'] }}</div>
            </div>

            {{-- Admin Breakdown --}}
            <div class="p-4 bg-white rounded-lg border border-gray-200">
                <div class="text-sm font-medium text-gray-500 uppercase mb-3">Admin</div>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">TA Active</span>
                        <span class="font-medium">{{ $summaryData['admin']['ta_active'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">TA Inactive</span>
                        <span class="font-medium">{{ $summaryData['admin']['ta_inactive'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Leave Active</span>
                        <span class="font-medium">{{ $summaryData['admin']['leave_active'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Leave Inactive</span>
                        <span class="font-medium">{{ $summaryData['admin']['leave_inactive'] }}</span>
                    </div>
                </div>
            </div>

            {{-- Device/Login Breakdown --}}
            <div class="p-4 bg-white rounded-lg border border-gray-200">
                <div class="text-sm font-medium text-gray-500 uppercase mb-3">Device & Login</div>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Web Login</span>
                        <span class="font-medium">{{ $summaryData['device']['web_login'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Mobile Login</span>
                        <span class="font-medium">{{ $summaryData['device']['mobile_login'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
