<div class="p-6 space-y-8">
    {{-- Company Profile Section --}}
    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
        <h4 class="mb-4 text-sm font-semibold text-gray-900 uppercase tracking-wider flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
            Company Profile
        </h4>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-xs font-medium text-left text-gray-500 uppercase">No</th>
                        <th class="px-4 py-2 text-xs font-medium text-left text-gray-500 uppercase">Company Name</th>
                        <th class="px-4 py-2 text-xs font-medium text-left text-gray-500 uppercase">Type</th>
                        <th class="px-4 py-2 text-xs font-medium text-left text-gray-500 uppercase">Sub Type</th>
                        <th class="px-4 py-2 text-xs font-medium text-left text-gray-500 uppercase">Role</th>
                        <th class="px-4 py-2 text-xs font-medium text-left text-gray-500 uppercase">Action</th>
                        <th class="px-4 py-2 text-xs font-medium text-left text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr>
                        <td class="px-4 py-3 text-sm">1</td>
                        <td class="px-4 py-3 text-sm font-medium">{{ $companyData['company_name'] ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-1 text-xs font-medium text-purple-800 bg-purple-100 rounded">SUBSCRIBER</span>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-1 text-xs font-medium text-amber-800 bg-amber-100 rounded">{{ $companyData['hr_license']['type'] ?? 'PAID' }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-1 text-xs font-medium text-green-800 bg-green-100 rounded">OWNER</span>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <button class="text-blue-600 hover:text-blue-800">Edit</button>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-1 text-xs font-medium text-green-800 bg-green-100 rounded">Active</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            <button class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700">
                + Add Company Profile
            </button>
        </div>
    </div>

    {{-- Trial Period Management --}}
    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
        <h4 class="mb-4 text-sm font-semibold text-gray-900 uppercase tracking-wider flex items-center">
            <svg class="w-5 h-5 mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Trial Period Management
        </h4>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <input type="date" wire:model="trialStartDate" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                <input type="date" wire:model="trialEndDate" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
        <div class="mt-4">
            <button wire:click="updateTrialPeriod" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700">
                Update Trial Period
            </button>
        </div>
    </div>

    {{-- Assign Customer to Dealer/Distributor --}}
    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
        <h4 class="mb-4 text-sm font-semibold text-gray-900 uppercase tracking-wider flex items-center">
            <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            Assign Customer to Dealer/Distributor
        </h4>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
            <input type="text" value="{{ $companyData['company_name'] ?? '-' }}" class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md" readonly>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Dealer/Distributor</label>
            <select wire:model="dealerId" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">Select Dealer/Distributor</option>
                @foreach($this->getDealerOptions() as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button wire:click="assignDealer" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700">
                Assign Dealer
            </button>
            <button wire:click="unlinkDealer" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded hover:bg-gray-300">
                Unlink Dealer
            </button>
        </div>
    </div>

    {{-- Billing Information --}}
    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
        <h4 class="mb-4 text-sm font-semibold text-gray-900 uppercase tracking-wider flex items-center">
            <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
            </svg>
            Billing Information
        </h4>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Billing Method</label>
            <select wire:model="billingMethod" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">Select Billing Method</option>
                <option value="direct">Direct Billing</option>
                <option value="reseller">Through Reseller</option>
            </select>
        </div>
        <div class="mt-4">
            <button wire:click="updateBilling" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700">
                Update Billing
            </button>
        </div>
    </div>

    {{-- Assign Customer to Referral --}}
    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
        <h4 class="mb-4 text-sm font-semibold text-gray-900 uppercase tracking-wider flex items-center">
            <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
            </svg>
            Assign Customer to Referral
        </h4>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
            <input type="text" value="{{ $companyData['company_name'] ?? '-' }}" class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md" readonly>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Referral</label>
            <select wire:model="referralId" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">Select Referral</option>
            </select>
        </div>
        <div class="mt-4">
            <button wire:click="assignReferral" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700">
                Assign Referral
            </button>
        </div>
    </div>
</div>
