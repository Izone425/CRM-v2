<div class="p-6">
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        {{-- Account Information --}}
        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div class="flex justify-between items-center mb-4">
                <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wider flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Account Information
                </h4>
                @if(!$editingAccountInfo)
                    <button
                        style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);"
                        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50"
                        type="button"
                        wire:loading.attr="disabled"
                        wire:click="editAccountInfo"
                    >
                        <svg wire:loading.remove.delay.default="1" wire:target="editAccountInfo" class="fi-btn-icon transition duration-75 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"></path>
                        </svg>
                        <svg fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="animate-spin fi-btn-icon transition duration-75 h-5 w-5 text-white" wire:loading.delay.default="" wire:target="editAccountInfo">
                            <path clip-rule="evenodd" d="M12 19C15.866 19 19 15.866 19 12C19 8.13401 15.866 5 12 5C8.13401 5 5 8.13401 5 12C5 15.866 8.13401 19 12 19ZM12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" fill-rule="evenodd" fill="currentColor" opacity="0.2"></path>
                            <path d="M2 12C2 6.47715 6.47715 2 12 2V5C8.13401 5 5 8.13401 5 12H2Z" fill="currentColor"></path>
                        </svg>
                        <span class="fi-btn-label">Edit</span>
                    </button>
                @endif
            </div>

            @if(!$editingAccountInfo)
                {{-- View Mode --}}
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Branch Info</span>
                        <span class="text-sm font-medium text-gray-900">{{ $selectedBranch }}</span>
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
            @else
                {{-- Edit Mode --}}
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Branch Info</span>
                        <select wire:model="selectedBranch" class="text-sm font-medium text-gray-900 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="Timetec Cloud Sdn Bhd">Timetec Cloud Sdn Bhd</option>
                            <option value="Timetec Penang Sdn Bhd">Timetec Penang Sdn Bhd</option>
                        </select>
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
                <div class="mt-4 flex gap-2">
                    <button
                        style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);"
                        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50"
                        type="button"
                        wire:loading.attr="disabled"
                        wire:click="saveAccountInfo"
                    >
                        <span class="fi-btn-label">Save Changes</span>
                    </button>
                    <button
                        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-white text-gray-950 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 ring-1 ring-gray-950/10 dark:ring-white/20"
                        type="button"
                        wire:click="cancelAccountInfo"
                    >
                        <span class="fi-btn-label">Cancel</span>
                    </button>
                </div>
            @endif
        </div>

        {{-- Backend Information (Read-only - no edit button) --}}
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
            <div class="flex justify-between items-center mb-4">
                <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wider flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    Billing Information
                </h4>
                @if(!$editingBillingInfo)
                    <button
                        style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);"
                        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50"
                        type="button"
                        wire:loading.attr="disabled"
                        wire:click="editBillingInfo"
                    >
                        <svg wire:loading.remove.delay.default="1" wire:target="editBillingInfo" class="fi-btn-icon transition duration-75 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"></path>
                        </svg>
                        <svg fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="animate-spin fi-btn-icon transition duration-75 h-5 w-5 text-white" wire:loading.delay.default="" wire:target="editBillingInfo">
                            <path clip-rule="evenodd" d="M12 19C15.866 19 19 15.866 19 12C19 8.13401 15.866 5 12 5C8.13401 5 5 8.13401 5 12C5 15.866 8.13401 19 12 19ZM12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" fill-rule="evenodd" fill="currentColor" opacity="0.2"></path>
                            <path d="M2 12C2 6.47715 6.47715 2 12 2V5C8.13401 5 5 8.13401 5 12H2Z" fill="currentColor"></path>
                        </svg>
                        <span class="fi-btn-label">Edit</span>
                    </button>
                @endif
            </div>

            @if(!$editingBillingInfo)
                {{-- View Mode --}}
                <div class="space-y-3">
                    <div>
                        <span class="text-sm text-gray-600">Company Name</span>
                        <p class="text-sm font-medium text-gray-900 mt-1">{{ $billingCompanyName ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">Address</span>
                        <p class="text-sm font-medium text-gray-900 mt-1">{{ $billingAddress ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">Billing Email</span>
                        <p class="text-sm font-medium text-gray-900 mt-1">{{ $billingEmail ?? '-' }}</p>
                    </div>
                    <div class="pt-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" class="form-checkbox text-blue-600 rounded" {{ $billingIsDefault ? 'checked' : '' }} disabled>
                            <span class="ml-2 text-sm text-gray-600">Set as Default</span>
                        </label>
                    </div>
                </div>
            @else
                {{-- Edit Mode --}}
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Company Name</label>
                        <input type="text" wire:model="billingCompanyName" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Address</label>
                        <textarea wire:model="billingAddress" rows="2" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Billing Email</label>
                        <input type="email" wire:model="billingEmail" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="pt-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" wire:model="billingIsDefault" class="form-checkbox text-blue-600 rounded">
                            <span class="ml-2 text-sm text-gray-600">Set as Default</span>
                        </label>
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    <button
                        style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);"
                        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50"
                        type="button"
                        wire:loading.attr="disabled"
                        wire:click="saveBillingInfo"
                    >
                        <span class="fi-btn-label">Save Changes</span>
                    </button>
                    <button
                        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-white text-gray-950 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 ring-1 ring-gray-950/10 dark:ring-white/20"
                        type="button"
                        wire:click="cancelBillingInfo"
                    >
                        <span class="fi-btn-label">Cancel</span>
                    </button>
                </div>
            @endif
        </div>

        {{-- Contact Person --}}
        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div class="flex justify-between items-center mb-4">
                <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wider flex items-center">
                    <svg class="w-5 h-5 mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    Contact Person
                </h4>
                @if(!$editingContactPerson)
                    <button
                        style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);"
                        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50"
                        type="button"
                        wire:loading.attr="disabled"
                        wire:click="editContactPerson"
                    >
                        <svg wire:loading.remove.delay.default="1" wire:target="editContactPerson" class="fi-btn-icon transition duration-75 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"></path>
                        </svg>
                        <svg fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="animate-spin fi-btn-icon transition duration-75 h-5 w-5 text-white" wire:loading.delay.default="" wire:target="editContactPerson">
                            <path clip-rule="evenodd" d="M12 19C15.866 19 19 15.866 19 12C19 8.13401 15.866 5 12 5C8.13401 5 5 8.13401 5 12C5 15.866 8.13401 19 12 19ZM12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" fill-rule="evenodd" fill="currentColor" opacity="0.2"></path>
                            <path d="M2 12C2 6.47715 6.47715 2 12 2V5C8.13401 5 5 8.13401 5 12H2Z" fill="currentColor"></path>
                        </svg>
                        <span class="fi-btn-label">Edit</span>
                    </button>
                @endif
            </div>

            @if(!$editingContactPerson)
                {{-- View Mode --}}
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Email/Username</span>
                        <span class="text-sm font-medium text-gray-900">{{ $contactEmail ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Name</span>
                        <span class="text-sm font-medium text-gray-900">{{ $contactName ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Title</span>
                        <span class="text-sm font-medium text-gray-900">{{ $contactTitle ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Mobile Phone</span>
                        <span class="text-sm font-medium text-gray-900">{{ $contactPhone ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Designation</span>
                        <span class="text-sm font-medium text-gray-900">{{ $contactPosition ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Gender</span>
                        <span class="text-sm font-medium text-gray-900">{{ $contactGender ?? '-' }}</span>
                    </div>
                </div>
            @else
                {{-- Edit Mode --}}
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Email/Username</label>
                        <input type="email" wire:model="contactEmail" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Name</label>
                        <input type="text" wire:model="contactName" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Title</label>
                        <input type="text" wire:model="contactTitle" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Mobile Phone</label>
                        <input type="text" wire:model="contactPhone" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Designation</label>
                        <input type="text" wire:model="contactPosition" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Gender</label>
                        <select wire:model="contactGender" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    <button
                        style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);"
                        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50"
                        type="button"
                        wire:loading.attr="disabled"
                        wire:click="saveContactPerson"
                    >
                        <span class="fi-btn-label">Save Changes</span>
                    </button>
                    <button
                        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-white text-gray-950 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 ring-1 ring-gray-950/10 dark:ring-white/20"
                        type="button"
                        wire:click="cancelContactPerson"
                    >
                        <span class="fi-btn-label">Cancel</span>
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Business Information Section --}}
    <div class="mt-6 p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
        <div class="flex justify-between items-center mb-4">
            <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wider flex items-center">
                <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                Business Information
            </h4>
            @if(!$editingBusinessInfo)
                <button
                    style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);"
                    class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50"
                    type="button"
                    wire:loading.attr="disabled"
                    wire:click="editBusinessInfo"
                >
                    <svg wire:loading.remove.delay.default="1" wire:target="editBusinessInfo" class="fi-btn-icon transition duration-75 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"></path>
                    </svg>
                    <svg fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="animate-spin fi-btn-icon transition duration-75 h-5 w-5 text-white" wire:loading.delay.default="" wire:target="editBusinessInfo">
                        <path clip-rule="evenodd" d="M12 19C15.866 19 19 15.866 19 12C19 8.13401 15.866 5 12 5C8.13401 5 5 8.13401 5 12C5 15.866 8.13401 19 12 19ZM12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" fill-rule="evenodd" fill="currentColor" opacity="0.2"></path>
                        <path d="M2 12C2 6.47715 6.47715 2 12 2V5C8.13401 5 5 8.13401 5 12H2Z" fill="currentColor"></path>
                    </svg>
                    <span class="fi-btn-label">Edit</span>
                </button>
            @endif
        </div>

        @if(!$editingBusinessInfo)
            {{-- View Mode --}}
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Business Type</span>
                        <span class="text-sm font-medium text-gray-900">{{ $businessType ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Company Name</span>
                        <span class="text-sm font-medium text-gray-900">{{ $companyName ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">Company Address</span>
                        <p class="text-sm font-medium text-gray-900 mt-1">{{ $companyAddress ?? '-' }}</p>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Area</span>
                        <span class="text-sm font-medium text-gray-900">{{ $area ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">State</span>
                        <span class="text-sm font-medium text-gray-900">{{ $state ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Telephone</span>
                        <span class="text-sm font-medium text-gray-900">{{ $telephone ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Email Address</span>
                        <span class="text-sm font-medium text-gray-900">{{ $emailAddress ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Primary Currency</span>
                        <span class="text-sm font-medium text-gray-900">{{ $primaryCurrency ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Preferred Timezone</span>
                        <span class="text-sm font-medium text-gray-900">{{ $preferredTimezone ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Number of Employee</span>
                        <span class="text-sm font-medium text-gray-900">{{ $numberOfEmployee ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">SST Exemption</span>
                        <span class="text-sm font-medium text-gray-900">{{ $sstExemption ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">SST Number</span>
                        <span class="text-sm font-medium text-gray-900">{{ $sstNumber ?? '-' }}</span>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Industry</span>
                        <span class="text-sm font-medium text-gray-900">{{ $industry ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Company Registration No</span>
                        <span class="text-sm font-medium text-gray-900">{{ $companyRegNo ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Postcode</span>
                        <span class="text-sm font-medium text-gray-900">{{ $postcode ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Country</span>
                        <span class="text-sm font-medium text-gray-900">{{ $country ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Fax</span>
                        <span class="text-sm font-medium text-gray-900">{{ $fax ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Business URL</span>
                        <span class="text-sm font-medium text-gray-900">{{ $businessUrl ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">How did you hear about us</span>
                        <span class="text-sm font-medium text-gray-900">{{ $howDidYouHear ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Preferred Language</span>
                        <span class="text-sm font-medium text-gray-900">{{ $preferredLanguage ?? '-' }}</span>
                    </div>
                </div>
            </div>
        @else
            {{-- Edit Mode --}}
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                {{-- Left Column --}}
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-blue-600 mb-1">Business Type:</label>
                        <select wire:model="businessType" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select Business Type</option>
                            <option value="local_business">Local Business</option>
                            <option value="foreign_business">Foreign Business</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"><span class="text-red-500">*</span> Company Name:</label>
                        <input type="text" wire:model="companyName" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Company Address:</label>
                        <textarea wire:model="companyAddress" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Area:</label>
                        <input type="text" wire:model="area" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="-">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">State:</label>
                        <input type="text" wire:model="state" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Telephone:</label>
                        <input type="text" wire:model="telephone" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address:</label>
                        <input type="email" wire:model="emailAddress" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Primary Currency:</label>
                        <input type="text" wire:model="primaryCurrency" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Preferred Timezone:</label>
                        <select wire:model="preferredTimezone" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="(GMT+08:00) Kuala Lumpur, Singapore">(GMT+08:00) Kuala Lumpur, Singapore</option>
                            <option value="(GMT+07:00) Bangkok, Hanoi, Jakarta">(GMT+07:00) Bangkok, Hanoi, Jakarta</option>
                            <option value="(GMT+09:00) Tokyo, Seoul">(GMT+09:00) Tokyo, Seoul</option>
                            <option value="(GMT+00:00) London, Dublin">(GMT+00:00) London, Dublin</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Number of Employee:</label>
                        <select wire:model="numberOfEmployee" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select</option>
                            <option value="1-24">1-24</option>
                            <option value="25-99">25-99</option>
                            <option value="100-500">100-500</option>
                            <option value="501 and Above">501 and Above</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">SST Exemption</label>
                        <select wire:model="sstExemption" class="w-24 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="No">No</option>
                            <option value="Yes">Yes</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">SST Number:</label>
                        <input type="text" wire:model="sstNumber" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="SST Number">
                    </div>
                </div>

                {{-- Right Column --}}
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Industry:</label>
                        <select wire:model="industry" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select Industry</option>
                            <option value="F&B">F&B</option>
                            <option value="Manufacturing">Manufacturing</option>
                            <option value="Retail">Retail</option>
                            <option value="Services">Services</option>
                            <option value="Technology">Technology</option>
                            <option value="Healthcare">Healthcare</option>
                            <option value="Education">Education</option>
                            <option value="Construction">Construction</option>
                            <option value="Finance">Finance</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Company Registration No:</label>
                        <input type="text" wire:model="companyRegNo" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Postcode:</label>
                        <input type="text" wire:model="postcode" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Country:</label>
                        <select wire:model="country" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="Malaysia">Malaysia</option>
                            <option value="Singapore">Singapore</option>
                            <option value="Indonesia">Indonesia</option>
                            <option value="Thailand">Thailand</option>
                            <option value="Philippines">Philippines</option>
                            <option value="Vietnam">Vietnam</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fax:</label>
                        <input type="text" wire:model="fax" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="-">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Business URL:</label>
                        <input type="text" wire:model="businessUrl" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">How did you hear about us:</label>
                        <select wire:model="howDidYouHear" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select</option>
                            <option value="Google">Google</option>
                            <option value="Facebook">Facebook</option>
                            <option value="LinkedIn">LinkedIn</option>
                            <option value="Referral">Referral</option>
                            <option value="Event">Event</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Preferred Language:</label>
                        <select wire:model="preferredLanguage" class="w-32 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="English">English</option>
                            <option value="Bahasa Malaysia">Bahasa Malaysia</option>
                            <option value="Chinese">Chinese</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="mt-4 flex gap-2">
                <button
                    style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);"
                    class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50"
                    type="button"
                    wire:loading.attr="disabled"
                    wire:click="saveBusinessInfo"
                >
                    <span class="fi-btn-label">Save Changes</span>
                </button>
                <button
                    class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-white text-gray-950 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 ring-1 ring-gray-950/10 dark:ring-white/20"
                    type="button"
                    wire:click="cancelBusinessInfo"
                >
                    <span class="fi-btn-label">Cancel</span>
                </button>
            </div>
        @endif
    </div>

    {{-- Payment Information Section --}}
    <div class="mt-6 p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
        <div class="flex justify-between items-center mb-4">
            <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wider flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
                Payment Information
            </h4>
            @if(!$editingPaymentInfo)
                <button
                    style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);"
                    class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50"
                    type="button"
                    wire:loading.attr="disabled"
                    wire:click="editPaymentInfo"
                >
                    <svg wire:loading.remove.delay.default="1" wire:target="editPaymentInfo" class="fi-btn-icon transition duration-75 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"></path>
                    </svg>
                    <svg fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="animate-spin fi-btn-icon transition duration-75 h-5 w-5 text-white" wire:loading.delay.default="" wire:target="editPaymentInfo">
                        <path clip-rule="evenodd" d="M12 19C15.866 19 19 15.866 19 12C19 8.13401 15.866 5 12 5C8.13401 5 5 8.13401 5 12C5 15.866 8.13401 19 12 19ZM12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" fill-rule="evenodd" fill="currentColor" opacity="0.2"></path>
                        <path d="M2 12C2 6.47715 6.47715 2 12 2V5C8.13401 5 5 8.13401 5 12H2Z" fill="currentColor"></path>
                    </svg>
                    <span class="fi-btn-label">Edit</span>
                </button>
            @endif
        </div>

        @if(!$editingPaymentInfo)
            {{-- View Mode --}}
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Company Bank Account</span>
                        <span class="text-sm font-medium text-gray-900">{{ $companyBankAccount ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Name on Bank Account</span>
                        <span class="text-sm font-medium text-gray-900">{{ $nameOnBankAccount ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">PayPal Email Address</span>
                        <p class="text-sm font-medium text-gray-900 mt-1">{{ $paypalEmail ?? '-' }}</p>
                        <p class="text-xs text-red-600">Commission or credit transfer will pay to dealer via PayPal.</p>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Bank Name</span>
                        <span class="text-sm font-medium text-gray-900">{{ $bankName ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">Customer Account Code</span>
                        <p class="text-sm font-medium text-gray-900 mt-1">{{ $customerAccountCode ?? '-' }}</p>
                        <p class="text-xs text-blue-600">This is the BizTrak Customer Code.</p>
                    </div>
                </div>
            </div>
        @else
            {{-- Edit Mode --}}
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                {{-- Left Column --}}
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Company Bank Account:</label>
                        <input type="text" wire:model="companyBankAccount" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name on Bank Account:</label>
                        <input type="text" wire:model="nameOnBankAccount" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">PayPal Email Address:</label>
                        <input type="email" wire:model="paypalEmail" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="-">
                        <p class="mt-1 text-xs text-red-600">Commission or credit transfer will pay to dealer via PayPal.</p>
                    </div>
                </div>

                {{-- Right Column --}}
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name:</label>
                        <input type="text" wire:model="bankName" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Customer Account Code:</label>
                        <input type="text" wire:model="customerAccountCode" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <p class="mt-1 text-xs text-blue-600">This is the BizTrak Customer Code.</p>
                    </div>
                </div>
            </div>
            <div class="mt-4 flex gap-2">
                <button
                    style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);"
                    class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50"
                    type="button"
                    wire:loading.attr="disabled"
                    wire:click="savePaymentInfo"
                >
                    <span class="fi-btn-label">Save Changes</span>
                </button>
                <button
                    class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-white text-gray-950 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 ring-1 ring-gray-950/10 dark:ring-white/20"
                    type="button"
                    wire:click="cancelPaymentInfo"
                >
                    <span class="fi-btn-label">Cancel</span>
                </button>
            </div>
        @endif
    </div>
</div>
