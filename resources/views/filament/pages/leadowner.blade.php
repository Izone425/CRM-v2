<!-- Pending Section -->
<div class="p-4 bg-white rounded-lg shadow" wire:poll.5s>
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-bold">Pending</h3>
        <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getPendingLeadsQuery()->count() }})</span>
    </div>
    @if ($this->getPendingLeadsQuery()->count() > 0)
        <div class="mt-2 overflow-y-auto" style="max-height: 300px;">
            <table class="w-full text-sm border-collapse table-fixed" style="width: 100%;">
                <colgroup>
                    <col style="width: 30%;">
                    <col style="width: 20%;">
                    <col style="width: 30%;">
                    <col style="width: 20%;">
                </colgroup>
                <thead class="sticky top-0 bg-gray-100" style="z-index: 1;">
                    <tr>
                        <th class="px-2 py-2 font-semibold text-left text-gray-700 border-b">Company Name</th>
                        <th class="px-2 py-2 font-semibold text-left text-gray-700 border-b">Company Size</th>
                        <th class="px-2 py-2 font-semibold text-left text-gray-700 border-b">Lead Source</th>
                        <th class="px-2 py-2 font-semibold text-left text-gray-700 border-b">Actions</th>
                    </tr>
                </thead>
                @foreach ($this->getPendingLeadsQuery()->get() as $lead)
                    <tr class="border-b">
                        <td class="px-1 py-1 font-medium">
                            <a href="{{ url('admin/leads/' . \App\Classes\Encryptor::encrypt($lead->id)) }}"
                               target="_blank"
                               class="inline-block text-blue-500 hover:text-blue-600">
                                {{ $lead->companyDetail->company_name ?? 'N/A' }}
                            </a>
                        </td>
                        <td class="px-1 py-1">{{ $lead->getCompanySizeLabelAttribute() }}</td>
                        <td class="px-1 py-1">{{ $lead->leadSource->platform ?? $lead->lead_code }}</td>
                        <td class="px-1 py-1">
                            <div x-data="{ isOpen: false, leadId: null }">
                                <form wire:submit.prevent="assignLead">
                                    <x-filament::modal width="2xl">
                                        <!-- Modal Trigger -->
                                        <x-slot name="trigger">
                                            <x-filament::button
                                                type="button"
                                                x-on:click="isOpen = true; leadId = {{ $lead->id }}"
                                                class="text-white rounded-lg hover:bg-blue-600"
                                                style="background-color: #FFA500; white-space: nowrap;">
                                                Assign to Me
                                            </x-filament::button>
                                        </x-slot>

                                        <!-- Modal Header -->
                                        <x-slot name="heading">
                                            <h3 class="text-lg font-bold">Confirm Lead Assignment</h3>
                                        </x-slot>

                                        <x-slot name="description">
                                            <p>Do you want to assign this lead to yourself? Make sure to confirm assignment before contacting the lead to avoid duplicate efforts by other team members.</p>
                                        </x-slot>

                                        <!-- Modal Actions -->
                                        <div class="gap-3 mt-3 d-flex justify-content-center align-items-center" style="display: inline-flex; align-self: center; gap: 3rem;">
                                            <button
                                                type="button"
                                                x-on:click="$wire.assignLead(leadId); isOpen = false"
                                                style="background-color: #28a745; color: white; border-radius: 20px; padding: 10px 20px; border: none;"
                                            >
                                                Confirm
                                            </button>
                                            <button
                                                type="button"
                                                x-on:click="isOpen = false"
                                                style="background-color: #6c757d; color: white; border-radius: 20px; padding: 10px 20px; border: none;"
                                            >
                                                Cancel
                                            </button>
                                        </div>
                                    </x-filament::modal>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @else
        <div class="flex flex-col items-center justify-center h-full">
            <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
            <p class="text-center text-gray-500">No data available.</p>
        </div>
    @endif
</div>

<!-- My New Leads Section -->
<div class="p-4 bg-white rounded-lg shadow" wire:poll.5s>
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-bold">My New Leads</h3>
        <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getNewLeadsQuery()->count() }})</span>
    </div>
    @if ($this->getNewLeadsQuery()->count() > 0)
        <div class="mt-2 overflow-y-auto" style="max-height: 300px;">
            <table class="w-full text-sm border-collapse table-fixed" style="width: 100%;">
                <colgroup>
                    <col style="width: 40%;">
                    <col style="width: 30%;">
                    <col style="width: 30%;">
                </colgroup>
                <thead class="sticky top-0 bg-gray-100" style="z-index: 1;">
                    <tr>
                        <th class="px-2 py-2 font-semibold text-left text-gray-700 border-b">Company Name</th>
                        <th class="px-2 py-2 font-semibold text-left text-gray-700 border-b">Company Size</th>
                        <th class="px-2 py-2 font-semibold text-left text-gray-700 border-b">Pending Time</th>
                    </tr>
                </thead>
                @foreach ($this->getNewLeadsQuery()->get() as $lead)
                    <tr class="border-b">
                        <td class="px-1 py-1 font-medium">
                            <a href="{{ url('admin/leads/' . \App\Classes\Encryptor::encrypt($lead->id)) }}"
                               target="_blank"
                               class="inline-block text-blue-500 hover:text-blue-600">
                                {{ $lead->companyDetail->company_name ?? 'N/A' }}
                            </a>
                        </td>
                        <td class="px-1 py-1">{{ $lead->getCompanySizeLabelAttribute() }}</td>
                        <td class="px-1 py-1 text-red-500" style="color: red">{{ $lead->updated_at ? $lead->updated_at->diffForHumans() : 'N/A' }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @else
        <!-- Placeholder when no new leads are found -->
        <div class="flex flex-col items-center justify-center h-full">
            <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
            <p class="text-center text-gray-500">No data available.</p>
        </div>
    @endif
</div>

<!-- Prospect Reminder (Today) Section -->
<div class="p-4 bg-white rounded-lg shadow" wire:poll.5s>
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-bold">Prospect Reminder (Today)</h3>
        <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getProspectTodayQuery()->count() }})</span>
    </div>
    @if ($this->getProspectTodayQuery()->count() > 0)

        <div class="mt-2 overflow-y-auto" style="max-height: 300px;">
            <table class="w-full text-sm border-collapse table-fixed" style="width: 100%;">
                <colgroup>
                    <col style="width: 20%;">
                    <col style="width: 30%;">
                    <col style="width: 30%;">
                    <col style="width: 20%;">
                </colgroup>
                <thead class="sticky top-0 bg-gray-100" style="z-index: 1;">
                    <tr>
                        <th class="px-2 py-2 font-semibold text-left text-gray-700 border-b">Company Name</th>
                        <th class="px-2 py-2 font-semibold text-left text-gray-700 border-b">Description</th>
                        <th class="px-2 py-2 font-semibold text-left text-gray-700 border-b">Remarks</th>
                        <th class="px-2 py-2 font-semibold text-left text-gray-700 border-b">Actions</th>
                    </tr>
                </thead>
                @foreach ($this->getProspectTodayQuery()->get() as $lead)
                    <tr class="border-b">
                        <td class="px-1 py-1 font-medium">{{ $lead->companyDetail->company_name ?? 'N/A' }}</td>
                        <td class="px-1 py-1 font-medium">
                            {{ $lead->activityLogs()->latest('created_at')->first()?->description ?? 'N/A' }}
                        </td>
                        <td class="px-1 py-1">{{ $lead->remark ?? '-' }}</td>
                        <td class="px-1 py-1">
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
        <!-- Placeholder when no reminders are found -->
        <div class="flex flex-col items-center justify-center h-full">
            <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
            <p class="text-center text-gray-500">No data available.</p>
        </div>
    @endif
</div>

<!-- Prospect Reminder (Overdue) Section -->
<div class="p-4 bg-white rounded-lg shadow" wire:poll.5s>
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-bold">Prospect Reminder (Overdue)</h3>
        <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getProspectOverdueQuery()->count() }})</span>
    </div>
    @if ($this->getProspectOverdueQuery()->count() > 0)
        <div class="mt-2 overflow-y-auto" style="max-height: 300px;">
            <table class="w-full text-sm border-collapse table-fixed" style="width: 100%;">
                <colgroup>
                    <col style="width: 20%;">
                    <col style="width: 30%;">
                    <col style="width: 15%;">
                    <col style="width: 15%;">
                    <col style="width: 20%;">
                </colgroup>
                <thead class="sticky top-0 bg-gray-100" style="z-index: 1;">
                    <tr>
                        <th class="px-2 py-2 font-semibold text-left text-gray-700 border-b">Company Name</th>
                        <th class="px-2 py-2 font-semibold text-left text-gray-700 border-b">Description</th>
                        <th class="px-2 py-2 font-semibold text-left text-gray-700 border-b">Remarks</th>
                        <th class="px-2 py-2 font-semibold text-left text-gray-700 border-b">Pending Time</th>
                        <th class="px-2 py-2 font-semibold text-left text-gray-700 border-b">Actions</th>
                    </tr>
                </thead>
                @foreach ($this->getProspectOverdueQuery()->get() as $lead)
                    <tr class="border-b">
                        <td class="px-1 py-1 font-medium">{{ $lead->companyDetail->company_name ?? 'N/A' }}</td>
                        <td class="px-1 py-1 font-medium">
                            {{ $lead->activityLogs()->latest('created_at')->first()?->description ?? 'N/A' }}
                        </td>
                        <td class="px-1 py-1">{{ $lead->remark ?? '-' }}</td>
                        <td class="px-1 py-1 text-red-500" style="color: red">
                            {{ $lead->follow_up_date ? $lead->follow_up_date->diffForHumans() : 'N/A' }}
                        </td>
                        <td class="px-1 py-1">
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

<!-- No Response -->
<div class="p-4 bg-white rounded-lg shadow" wire:poll.5s>
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-bold">No Response Lead</h3>
        <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getNoResponseLeadsQuery()->count() }})</span>
    </div>
    @if ($this->getNoResponseLeadsQuery()->count() > 0)
        <div class="mt-2 overflow-y-auto" style="max-height: 300px;">
            <table class="w-full text-sm border-collapse table-fixed" style="width: 100%;">
                <colgroup>
                    <col style="width: 20%;">
                    <col style="width: 30%;">
                    <col style="width: 15%;">
                    <col style="width: 15%;">
                    <col style="width: 20%;">
                </colgroup>
                <thead class="sticky top-0 bg-gray-100" style="z-index: 1;">
                    <tr>
                        <th class="px-2 py-2 font-semibold text-left text-gray-700 border-b">Company Name</th>
                        <th class="px-2 py-2 font-semibold text-left text-gray-700 border-b">Description</th>
                        <th class="px-2 py-2 font-semibold text-left text-gray-700 border-b">Remarks</th>
                        <th class="px-2 py-2 font-semibold text-left text-gray-700 border-b">Actions</th>
                    </tr>
                </thead>
                @foreach ($this->getNoResponseLeadsQuery()->get() as $lead)
                    <tr class="border-b">
                        <td class="px-1 py-1 font-medium">{{ $lead->companyDetail->company_name ?? 'N/A' }}</td>
                        <td class="px-1 py-1 font-medium">
                            {{ $lead->activityLogs()->latest('created_at')->first()?->description ?? 'N/A' }}
                        </td>
                        <td class="px-1 py-1">{{ $lead->remark ?? '-' }}</td>
                        <td class="px-1 py-1 text-red-500" style="color: red">
                            {{ $lead->follow_up_date ? $lead->follow_up_date->diffForHumans() : 'N/A' }}
                        </td>
                        <td class="px-1 py-1">
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
