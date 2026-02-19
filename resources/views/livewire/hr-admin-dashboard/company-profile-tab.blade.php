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
                        <span class="text-sm text-gray-600">PIC Name</span>
                        <p class="text-sm font-medium text-gray-900 mt-1">{{ $billingPicName ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">Phone</span>
                        <p class="text-sm font-medium text-gray-900 mt-1">{{ $billingPhone ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">Email</span>
                        <p class="text-sm font-medium text-gray-900 mt-1">{{ $billingEmail ?? '-' }}</p>
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
                        <label class="block text-sm text-gray-600 mb-1">PIC Name</label>
                        <input type="text" wire:model="billingPicName" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Phone</label>
                        <input type="text" wire:model="billingPhone" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Email</label>
                        <input type="email" wire:model="billingEmail" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
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

    </div>

</div>
