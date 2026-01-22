<div class="p-6">
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        {{-- Account Information --}}
        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
            <h4 class="mb-4 text-sm font-semibold text-gray-900 uppercase tracking-wider flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                Account Information
            </h4>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Branch Info</span>
                    <span class="text-sm font-medium text-gray-900">{{ $profileData['account_info']['branch'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Register Date</span>
                    <span class="text-sm font-medium text-gray-900">{{ $profileData['account_info']['register_date'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Last Login Date</span>
                    <span class="text-sm font-medium text-gray-900">{{ $profileData['account_info']['last_login_date'] }}</span>
                </div>
            </div>
        </div>

        {{-- Backend Information --}}
        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
            <h4 class="mb-4 text-sm font-semibold text-gray-900 uppercase tracking-wider flex items-center">
                <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                </svg>
                Backend Information
            </h4>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Backend Company Id</span>
                    <span class="text-sm font-medium text-gray-900">{{ $profileData['backend_info']['company_id'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Backend User Id</span>
                    <span class="text-sm font-medium text-gray-900">{{ $profileData['backend_info']['user_id'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Backend Webster IP</span>
                    <span class="text-sm font-medium text-gray-900">{{ $profileData['backend_info']['webster_ip'] }}</span>
                </div>
            </div>
        </div>

        {{-- Billing Information --}}
        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
            <h4 class="mb-4 text-sm font-semibold text-gray-900 uppercase tracking-wider flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
                Billing Information
            </h4>
            <div class="space-y-3">
                <div>
                    <span class="text-sm text-gray-600">Company Name</span>
                    <p class="text-sm font-medium text-gray-900 mt-1">{{ $profileData['billing_info']['company_name'] }}</p>
                </div>
                <div>
                    <span class="text-sm text-gray-600">Address</span>
                    <p class="text-sm font-medium text-gray-900 mt-1">{{ $profileData['billing_info']['address'] }}</p>
                </div>
                <div>
                    <span class="text-sm text-gray-600">Billing Email</span>
                    <p class="text-sm font-medium text-gray-900 mt-1">{{ $profileData['billing_info']['email'] }}</p>
                </div>
                <div class="pt-2">
                    <label class="inline-flex items-center">
                        <input type="checkbox" class="form-checkbox text-blue-600 rounded" checked>
                        <span class="ml-2 text-sm text-gray-600">Set as Default</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Contact Person --}}
        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
            <h4 class="mb-4 text-sm font-semibold text-gray-900 uppercase tracking-wider flex items-center">
                <svg class="w-5 h-5 mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                Contact Person
            </h4>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Email/Username</span>
                    <span class="text-sm font-medium text-gray-900">{{ $profileData['contact_person']['email'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Name</span>
                    <span class="text-sm font-medium text-gray-900">{{ $profileData['contact_person']['name'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Title</span>
                    <span class="text-sm font-medium text-gray-900">{{ $profileData['contact_person']['title'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Mobile Phone</span>
                    <span class="text-sm font-medium text-gray-900">{{ $profileData['contact_person']['phone'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Designation</span>
                    <span class="text-sm font-medium text-gray-900">{{ $profileData['contact_person']['position'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Gender</span>
                    <span class="text-sm font-medium text-gray-900">{{ $profileData['contact_person']['gender'] }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
