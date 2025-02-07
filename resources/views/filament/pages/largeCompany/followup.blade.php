<div class="p-4 bg-white rounded-lg shadow" wire:poll.5s>
    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <h3 class="text-lg font-bold">Follow Up (25 Above)</h3>
            @if ($this->getFollowUpBigCompanyLeads()->count() > 0)
                <div x-data="{ isOpen: false, leadId: null }">
                    <form wire:submit.prevent="resetBigCompanyDoneCall">
                        <x-filament::modal width="2xl">
                            <x-slot name="trigger">
                                <x-filament::button
                                    type="button"
                                    x-on:click="isOpen = true; leadId = {{ $lead->id }}"
                                    class="text-white rounded-lg hover:bg-blue-600"
                                    style="background-color: #ff0800; white-space: nowrap;">
                                    Reset Done Call
                                </x-filament::button>
                            </x-slot>

                            <x-slot name="heading">
                                <h3 class="text-lg font-bold">Reset All Done Call</h3>
                            </x-slot>

                            <x-slot name="description">
                                <p>Do you want to reset all done call? it will change back to Active Section in dashboard.</p>
                            </x-slot>

                            <div class="gap-3 mt-3 d-flex justify-content-center align-items-center" style="display: inline-flex; align-self: center; gap: 3rem;">
                                <button
                                    type="button"
                                    x-on:click="$wire.resetBigCompanyDoneCall; isOpen = false"
                                    style="background-color: #28a745; color: white; border-radius: 20px; padding: 10px 20px; border: none;">
                                    Confirm
                                </button>
                                <button
                                    type="button"
                                    x-on:click="isOpen = false"
                                    style="background-color: #6c757d; color: white; border-radius: 20px; padding: 10px 20px; border: none;">
                                    Cancel
                                </button>
                            </div>
                        </x-filament::modal>
                    </form>
                </div>
            @endif
        </div>
        <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getFollowUpBigCompanyLeads()->count() }})</span>
    </div>
    @if ($this->getFollowUpBigCompanyLeads()->count() > 0)
        <div class="mt-2 overflow-y-auto" style="max-height: 263px">
            <table class="w-full text-sm border-collapse table-fixed" style="width: 100%;">
                <colgroup>
                    <col style="width: 25%;">
                    <col style="width: 10%;">
                    <col style="width: 30%;">
                    <col style="width: 15%;">
                    <col style="width: 20%;">
                </colgroup>
                <thead class="sticky top-0 bg-gray-100" style="z-index: 1;">
                    <tr>
                        <th class="px-2 py-2 font-semibold text-center text-gray-700 border-b">Company<br> Name</th>
                        <th class="px-2 py-2 font-semibold text-center text-gray-700 border-b cursor-pointer"
                            wire:click="sortBy('company_size', 'followUpBigCompanyLeads')">
                            Company<br> Size
                            @if ($sortColumnFollowUpBigCompanyLeads === 'company_size')
                                <span>{{ $sortDirectionFollowUpBigCompanyLeads === 'asc' ? '▲' : '▼' }}</span>
                            @endif
                        </th>

                        <th class="px-2 py-2 font-semibold text-center text-gray-700 border-b cursor-pointer"
                            wire:click="sortBy('call_attempt', 'followUpBigCompanyLeads')">
                            Call<br> Attempt
                            @if ($sortColumnFollowUpBigCompanyLeads === 'call_attempt')
                                <span>{{ $sortDirectionFollowUpBigCompanyLeads === 'asc' ? '▲' : '▼' }}</span>
                            @endif
                        </th>

                        <th class="px-2 py-2 font-semibold text-center text-gray-700 border-b cursor-pointer"
                            wire:click="sortBy('pending_time', 'followUpBigCompanyLeads')">
                            Pending<br> Time
                            @if ($sortColumnFollowUpBigCompanyLeads === 'pending_time')
                                <span>{{ $sortDirectionFollowUpBigCompanyLeads === 'asc' ? '▲' : '▼' }}</span>
                            @endif
                        </th>
                        <th class="px-2 py-2 font-semibold text-center text-gray-700 border-b">Action</th>
                    </tr>
                </thead>
                @foreach ($this->getFollowUpBigCompanyLeads() as $lead)
                    <tr class="border-b" style="height:43px;">
                        <td class="px-1 py-1 font-medium">
                            <a href="{{ url('admin/leads/' . \App\Classes\Encryptor::encrypt($lead->id)) }}"
                            target="_blank"
                            class="inline-block"
                            style="color:#338cf0;">
                                {{ strtoupper($lead->companyDetail->company_name ?? 'N/A') }}
                            </a>
                        </td>
                        <td class="px-1 py-1">{{ $lead->getCompanySizeLabelAttribute() }}</td>
                        <td class="px-1 py-1 text-center">
                            {{ $lead->call_attempt !== null ? $lead->call_attempt : '0' }}
                        </td>
                        <td class="px-1 py-1 text-center" style="color:red">
                            {{ $lead->pending_time !== null ? $lead->pending_time . ' days' : 'N/A' }}
                        </td>
                        <td class="px-1 py-1 text-center">
                            <x-filament::button>
                                <a href="{{ url('admin/leads/' . \App\Classes\Encryptor::encrypt($lead->id)) }}"
                                target="_blank"
                                class="inline-block text-white bg-blue-500 rounded-lg hover:bg-blue-600">
                                    Lead Detail
                                </a>
                            </x-filament::button>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @else
        <!-- Placeholder when no overdue reminders are found -->
        <div class="flex flex-col items-center justify-center h-full">
            <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
            <p class="text-center text-gray-500">No data available.</p>
        </div>
    @endif
</div>
