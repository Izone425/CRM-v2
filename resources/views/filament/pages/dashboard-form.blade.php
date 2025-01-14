<x-filament::page>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        @if (auth()->user()->role_id == 1)
            <!-- Pending Section -->
            <div class="p-4 bg-white rounded-lg shadow">
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
                                    <td class="px-1 py-1">{{ $lead->leadSource->platform ?? 'Unknown' }}</td>
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
                        {{-- <form wire:submit.prevent="livewireSubmitMethodHere">
                            <x-filament::modal>
                                <x-slot name="trigger">
                                    <button type="button" x-on:click="isOpen = true">Open</button>
                                </x-slot>

                                <x-slot name="header">
                                    Modal heading
                                </x-slot>

                                Form components here

                                <x-slot name="actions">
                                    <button type="submit">
                                         Submit form
                                    </button>
                                </x-slot>
                            </x-filament::modal>
                        </form> --}}
                    </div>
                @else
                    <!-- Placeholder when no pending leads are found -->
                    <div class="flex flex-col items-center justify-center mt-6">
                        <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
                        <p class="text-center text-gray-500">No data available.</p>
                    </div>
                @endif
            </div>

            <!-- My New Leads Section -->
            <div class="p-4 bg-white rounded-lg shadow">
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
                    <div class="flex flex-col items-center justify-center mt-6">
                        <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
                        <p class="text-center text-gray-500">No data available.</p>
                    </div>
                @endif
            </div>

            <!-- Prospect Reminder (Today) Section -->
            <div class="p-4 bg-white rounded-lg shadow">
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
                    <div class="flex flex-col items-center justify-center mt-6">
                        <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
                        <p class="text-center text-gray-500">No data available.</p>
                    </div>
                @endif
            </div>

            <!-- Prospect Reminder (Overdue) Section -->
            <div class="p-4 bg-white rounded-lg shadow">
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
                    <div class="flex flex-col items-center justify-center mt-6">
                        <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
                        <p class="text-center text-gray-500">No data available.</p>
                    </div>
                @endif
            </div>

        @elseif (auth()->user()->role_id == 2)
            <div class="p-4 bg-white rounded-lg shadow">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold">Demo (Today)</h3>
                    <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getTodayDemos()->count() }})</span>
                </div>
                @if ($this->getTodayDemos()->count() > 0)
                    <div class="mt-2 overflow-y-auto" style="max-height: 300px;">
                        <table class="w-full text-sm border-collapse table-fixed" style="width: 100%;">
                            <colgroup>
                                <col style="width: 20%;">
                                <col style="width: 20%;">
                                <col style="width: 20%;">
                                <col style="width: 25%;">
                                <col style="width: 15%;">
                            </colgroup>
                            @foreach ($this->getTodayDemos()->get() as $lead)
                                <tr class="border-b">
                                    <td class="px-1 py-1 font-medium">{{ $lead->companyDetail->company_name }}</td>
                                    <td class="px-1 py-1">{{ $lead->demoAppointment->first()?->type ?? 'N/A' }}</td>
                                    <td class="px-1 py-1">{{ $lead->demoAppointment->first()?->date ?? 'N/A' }}</td>
                                    <td class="px-1 py-1" style="color:red">
                                        {{ $lead->demoAppointment->first()?->start_time ? \Carbon\Carbon::parse($lead->demoAppointment->first()?->start_time)->format('h:i A') : 'N/A' }}
                                        -
                                        {{ $lead->demoAppointment->first()?->end_time ? \Carbon\Carbon::parse($lead->demoAppointment->first()?->end_time)->format('h:i A') : 'N/A' }}
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
                    <!-- Placeholder when no demos are scheduled -->
                    <div class="flex flex-col items-center justify-center mt-6">
                        <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
                        <p class="text-center text-gray-500">No data available.</p>
                    </div>
                @endif
            </div>

            <div class="p-4 bg-white rounded-lg shadow">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold">Demo (Tomorrow)</h3>
                    <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getTomorrowDemos()->count() }})</span>
                </div>
                @if ($this->getTomorrowDemos()->count() > 0)
                    <div class="mt-2 overflow-y-auto" style="max-height: 300px;">
                        <table class="w-full text-sm border-collapse table-fixed" style="width: 100%;">
                            <colgroup>
                                <col style="width: 20%;">
                                <col style="width: 20%;">
                                <col style="width: 20%;">
                                <col style="width: 25%;">
                                <col style="width: 15%;">
                            </colgroup>
                            @foreach ($this->getTomorrowDemos()->get() as $lead)
                                <tr class="border-b">
                                    <td class="px-1 py-1 font-medium">{{ $lead->companyDetail->company_name }}</td>
                                    <td class="px-1 py-1">{{ $lead->demoAppointment->first()?->type ?? 'N/A' }}</td>
                                    <td class="px-1 py-1">{{ $lead->demoAppointment->first()?->date ?? 'N/A' }}</td>
                                    <td class="px-1 py-1" style="color:red">
                                        {{ $lead->demoAppointment->first()?->start_time ? \Carbon\Carbon::parse($lead->demoAppointment->first()?->start_time)->format('h:i A') : 'N/A' }}
                                        -
                                        {{ $lead->demoAppointment->first()?->end_time ? \Carbon\Carbon::parse($lead->demoAppointment->first()?->end_time)->format('h:i A') : 'N/A' }}
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
                    <!-- Placeholder when no demos are scheduled -->
                    <div class="flex flex-col items-center justify-center mt-6">
                        <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
                        <p class="text-center text-gray-500">No demos are scheduled for tomorrow.</p>
                    </div>
                @endif
            </div>

            <!-- Prospect Reminder (Today) Section -->
            <div class="p-4 bg-white rounded-lg shadow">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold">Prospect Reminder (Today)</h3>
                    <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getTodayProspects()->count() }})</span>
                </div>
                @if ($this->getTodayProspects()->count() > 0)
                    <div class="mt-2 overflow-y-auto" style="max-height: 300px;">
                        <table class="w-full text-sm border-collapse table-fixed" style="width: 100%;">
                            <colgroup>
                                <col style="width: 20%;">
                                <col style="width: 30%;">
                                <col style="width: 25%;">
                                <col style="width: 15%;">
                            </colgroup>
                            @foreach ($this->getTodayProspects()->get() as $lead)
                                <tr class="border-b">
                                    <td class="px-1 py-1 font-medium">{{ $lead->companyDetail->company_name ?? 'N/A' }}</td>
                                    <td class="px-1 py-1 font-medium"
                                        style="
                                            color: {{
                                                str_contains($lead->activityLogs()->latest('created_at')->first()?->description ?? '', 'RFQ')
                                                    ? '#FFA500'
                                                    : (str_contains($lead->activityLogs()->latest('created_at')->first()?->description ?? '', '4th')
                                                        ? 'red'
                                                        : 'inherit')
                                            }};
                                            font-weight: {{
                                                str_contains($lead->activityLogs()->latest('created_at')->first()?->description ?? '', 'RFQ') ||
                                                str_contains($lead->activityLogs()->latest('created_at')->first()?->description ?? '', '4th')
                                                    ? 'bold'
                                                    : 'normal'
                                            }};
                                        ">
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
                    <!-- Placeholder when no prospects are found -->
                    <div class="flex flex-col items-center justify-center mt-6">
                        <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
                        <p class="text-center text-gray-500">No data available.</p>
                    </div>
                @endif
            </div>

            <!-- Prospect Reminder (Overdue) Section -->
            <div class="p-4 bg-white rounded-lg shadow">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold">Prospect Reminder (Overdue)</h3>
                    <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getOverdueProspects()->count() }})</span>
                </div>
                @if ($this->getOverdueProspects()->count() > 0)
                    <div class="mt-2 overflow-y-auto" style="max-height: 300px;">
                        <table class="w-full text-sm border-collapse table-fixed" style="width: 100%;">
                            <colgroup>
                                <col style="width: 15%;">
                                <col style="width: 35%;">
                                <col style="width: 20%;">
                                <col style="width: 15%;">
                                <col style="width: 15%;">
                            </colgroup>
                            @foreach ($this->getOverdueProspects()->get() as $lead)
                                <tr class="border-b">
                                    <td class="px-1 py-1 font-medium">{{ $lead->companyDetail->company_name ?? 'N/A' }}</td>
                                    <td class="px-1 py-1 font-medium"
                                        style="
                                            color: {{
                                                str_contains($lead->activityLogs()->latest('created_at')->first()?->description ?? '', 'RFQ')
                                                    ? '#FFA500'
                                                    : (str_contains($lead->activityLogs()->latest('created_at')->first()?->description ?? '', '4th')
                                                        ? 'red'
                                                        : 'inherit')
                                            }};
                                            font-weight: {{
                                                str_contains($lead->activityLogs()->latest('created_at')->first()?->description ?? '', 'RFQ') ||
                                                str_contains($lead->activityLogs()->latest('created_at')->first()?->description ?? '', '4th')
                                                    ? 'bold'
                                                    : 'normal'
                                            }};
                                        ">
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
                    <!-- Placeholder when no overdue prospects are found -->
                    <div class="flex flex-col items-center justify-center mt-6">
                        <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
                        <p class="text-center text-gray-500">No data available.</p>
                    </div>
                @endif
            </div>

            <div class="p-4 bg-white rounded-lg shadow">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold">Prospect Reminder (Overdue)</h3>
                    <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getOverdueProspects()->count() }})</span>
                </div>
                @if ($this->getOverdueDebtors()->count() > 0)
                    <div class="mt-2 overflow-y-auto" style="max-height: 300px;">
                        <table class="w-full text-sm border-collapse table-fixed" style="width: 100%;">
                            <colgroup>
                                <col style="width: 20%;">
                                <col style="width: 30%;">
                                <col style="width: 20%;">
                                <col style="width: 20%;">
                            </colgroup>
                            @foreach ($this->getOverdueDebtors()->get() as $lead)
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
                    <!-- Placeholder when no follow-ups are found -->
                    <div class="flex flex-col items-center justify-center mt-6">
                        <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
                        <p class="text-center text-gray-500">No data available.</p>
                    </div>
                @endif
            </div>

            <div class="p-4 bg-white rounded-lg shadow">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold">Prospect Reminder (Overdue)</h3>
                    <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getOverdueProspects()->count() }})</span>
                </div>
                @if ($this->getOverdueDebtors()->count() > 0)
                    <div class="mt-2 overflow-y-auto" style="max-height: 300px;">
                        <table class="w-full text-sm border-collapse table-fixed" style="width: 100%;">
                            <colgroup>
                                <col style="width: 10%;">
                                <col style="width: 35%;">
                                <col style="width: 20%;">
                                <col style="width: 15%;">
                                <col style="width: 20%;">
                            </colgroup>
                            @foreach ($this->getOverdueDebtors()->get() as $lead)
                                <tr class="border-b">
                                    <td class="px-1 py-1 font-medium">{{ $lead->companyDetail->company_name ?? 'N/A' }}</td>
                                    <td class="px-1 py-1 font-medium">
                                        {{ $lead->activityLogs()->latest('created_at')->first()?->description ?? 'N/A' }}
                                    </td>
                                    <td class="px-1 py-1">{{ $lead->remark ?? '-' }}</td>
                                    <td class="px-1 py-1 text-red-500" style="color: red">
                                        {{ $lead->updated_at ? $lead->updated_at->diffForHumans() : 'N/A' }}
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
                    <!-- Placeholder when no overdue debtor follow-ups are found -->
                    <div class="flex flex-col items-center justify-center mt-6">
                        <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
                        <p class="text-center text-gray-500">No data available.</p>
                    </div>
                @endif
            </div>
        @elseif (auth()->user()->role_id == 3)
            <div class="space-y-4">
                <!-- Dropdown for Selecting a User -->
                <div class="flex items-center space-x-8"> <!-- Use space-x-8 for horizontal spacing -->
                    <!-- Dropdown -->
                    <div>
                        <select wire:model.defer="selectedUser" id="userFilter" class="mt-1 border-gray-300 rounded-md shadow-sm">
                            <option value="">Select a User</option>
                            <optgroup label="Lead Owner">
                                @foreach ($users->where('role_id', 1) as $user)
                                    <option value="{{ $user->id }}">
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Salesperson">
                                @foreach ($users->where('role_id', 2) as $user)
                                    <option value="{{ $user->id }}">
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        </select>
                    </div>
                    &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp
                    <!-- Button -->
                    <div>
                        <button
                            wire:click="handleSelectedUser"
                            class="px-3 py-1 font-bold text-white transition duration-300 ease-in-out transform rounded-lg shadow-md hover:bg-blue-600 hover:scale-105"
                            style="background-color: #431fa1;"
                        >
                            Update Dashboard
                        </button>
                    </div>
                </div>
            </div>

            <br>
            {{-- <div class="mt-8"> --}}
                @if ($selectedUserRole == 1)
                    <!-- Pending Section -->
                    <div class="p-4 bg-white rounded-lg shadow">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold">Pending</h3>
                            <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getPendingLeadsQuery()->count() }})</span>
                        </div>
                        @if ($this->getPendingLeadsQuery()->count() > 0)
                            <div class="mt-2 overflow-y-auto" style="max-height: 300px;">
                                <table class="w-full text-sm border-collapse table-fixed" style="width: 100%;">
                                    <colgroup>
                                        <col style="width: 20%;">
                                        <col style="width: 15%;">
                                        <col style="width: 15%;">
                                        <col style="width: 20%;">
                                        <col style="width: 20%;">
                                    </colgroup>
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
                                            <td class="px-1 py-1">{{ $lead->leadSource->platform ?? 'Unknown' }}</td>
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
                                            <td class="px-1 py-1">
                                                <div x-data="{ isOpen: false, leadId: null }">
                                                    <form wire:submit.prevent="assignLeadToUser">
                                                        <x-filament::modal width="2xl">
                                                            <!-- Modal Trigger -->
                                                            <x-slot name="trigger">
                                                                <x-filament::button
                                                                    type="button"
                                                                    x-on:click="isOpen = true; leadId = {{ $lead->id }}"
                                                                    class="text-white rounded-lg hover:bg-green-600"
                                                                    style="background-color: #7a5aca; white-space: nowrap;">
                                                                    Assign to {{ $selectedUser ? \App\Models\User::find($selectedUser)->name : 'Selected User' }}
                                                                </x-filament::button>
                                                            </x-slot>

                                                            <!-- Modal Header -->
                                                            <x-slot name="heading">
                                                                <h3 class="text-lg font-bold">Confirm Lead Assignment to {{ $selectedUser ? \App\Models\User::find($selectedUser)->name : 'Selected User' }}</h3>
                                                            </x-slot>

                                                            <x-slot name="description">
                                                                <p>Do you want to assign this lead to {{ $selectedUser ? \App\Models\User::find($selectedUser)->name : 'Selected User' }}? Make sure to confirm assignment before contacting the lead to avoid duplicate efforts by other team members.</p>
                                                            </x-slot>

                                                            <!-- Modal Actions -->
                                                            <div class="gap-3 mt-3 d-flex justify-content-center align-items-center" style="display: inline-flex; align-self: center; gap: 3rem;">
                                                                <button
                                                                    type="button"
                                                                    x-on:click="$wire.assignLeadToUser(leadId); isOpen = false"
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
                            <!-- Placeholder when no pending leads are found -->
                            <div class="flex flex-col items-center justify-center mt-6">
                                <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
                                <p class="text-center text-gray-500">No data available.</p>
                            </div>
                        @endif
                    </div>

                    <!-- My New Leads Section -->
                    <div class="p-4 bg-white rounded-lg shadow">
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
                            <div class="flex flex-col items-center justify-center mt-6">
                                <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
                                <p class="text-center text-gray-500">No data available.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Prospect Reminder (Today) Section -->
                    <div class="p-4 bg-white rounded-lg shadow">
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
                            <div class="flex flex-col items-center justify-center mt-6">
                                <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
                                <p class="text-center text-gray-500">No data available.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Prospect Reminder (Overdue) Section -->
                    <div class="p-4 bg-white rounded-lg shadow">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold">Prospect Reminder (Overdue)</h3>
                            <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getProspectOverdueQuery()->count() }})</span>
                        </div>
                        @if ($this->getProspectOverdueQuery()->count() > 0)
                            <div class="mt-2 overflow-y-auto" style="max-height: 300px;">
                                <table class="w-full text-sm border-collapse table-fixed" style="width: 100%;">
                                    <colgroup>
                                        <col style="width: 10%;">
                                        <col style="width: 35%;">
                                        <col style="width: 20%;">
                                        <col style="width: 15%;">
                                        <col style="width: 20%;">
                                    </colgroup>
                                    @foreach ($this->getProspectOverdueQuery()->get() as $lead)
                                        <tr class="border-b">
                                            <td class="px-1 py-1 font-medium">{{ $lead->companyDetail->company_name ?? 'N/A' }}</td>
                                            <td class="px-1 py-1 font-medium"
                                                style="
                                                    color: {{
                                                        str_contains($lead->activityLogs()->latest('created_at')->first()?->description ?? '', 'RFQ')
                                                            ? '#FFA500'
                                                            : (str_contains($lead->activityLogs()->latest('created_at')->first()?->description ?? '', '4th')
                                                                ? 'red'
                                                                : 'inherit')
                                                    }};
                                                    font-weight: {{
                                                        str_contains($lead->activityLogs()->latest('created_at')->first()?->description ?? '', 'RFQ') ||
                                                        str_contains($lead->activityLogs()->latest('created_at')->first()?->description ?? '', '4th')
                                                            ? 'bold'
                                                            : 'normal'
                                                    }};
                                                ">
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
                            <div class="flex flex-col items-center justify-center mt-6">
                                <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
                                <p class="text-center text-gray-500">No data available.</p>
                            </div>
                        @endif
                    </div>

                @elseif ($selectedUserRole == 2)
                <div class="p-4 bg-white rounded-lg shadow">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold">Demo (Today)</h3>
                        <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getTodayDemos()->count() }})</span>
                    </div>
                    @if ($this->getTodayDemos()->count() > 0)
                        <div class="mt-2 overflow-y-auto" style="max-height: 300px;">
                            <table class="w-full text-sm border-collapse table-fixed" style="width: 100%;">
                                <colgroup>
                                    <col style="width: 20%;">
                                    <col style="width: 20%;">
                                    <col style="width: 20%;">
                                    <col style="width: 25%;">
                                    <col style="width: 15%;">
                                </colgroup>
                                @foreach ($this->getTodayDemos()->get() as $lead)
                                    <tr class="border-b">
                                        <td class="px-1 py-1 font-medium">{{ $lead->companyDetail->company_name }}</td>
                                        <td class="px-1 py-1">{{ $lead->demoAppointment->first()?->type ?? 'N/A' }}</td>
                                        <td class="px-1 py-1">{{ $lead->demoAppointment->first()?->date ?? 'N/A' }}</td>
                                        <td class="px-1 py-1" style="color:red">
                                            {{ $lead->demoAppointment->first()?->start_time ? \Carbon\Carbon::parse($lead->demoAppointment->first()?->start_time)->format('h:i A') : 'N/A' }}
                                            -
                                            {{ $lead->demoAppointment->first()?->end_time ? \Carbon\Carbon::parse($lead->demoAppointment->first()?->end_time)->format('h:i A') : 'N/A' }}
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
                        <!-- Placeholder when no demos are scheduled -->
                        <div class="flex flex-col items-center justify-center mt-6">
                            <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
                            <p class="text-center text-gray-500">No data available.</p>
                        </div>
                    @endif
                </div>

                <div class="p-4 bg-white rounded-lg shadow">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold">Demo (Tomorrow)</h3>
                        <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getTomorrowDemos()->count() }})</span>
                    </div>
                    @if ($this->getTomorrowDemos()->count() > 0)
                        <div class="mt-2 overflow-y-auto" style="max-height: 300px;">
                            <table class="w-full text-sm border-collapse table-fixed" style="width: 100%;">
                                <colgroup>
                                    <col style="width: 20%;">
                                    <col style="width: 20%;">
                                    <col style="width: 20%;">
                                    <col style="width: 25%;">
                                    <col style="width: 15%;">
                                </colgroup>
                                @foreach ($this->getTomorrowDemos()->get() as $lead)
                                    <tr class="border-b">
                                        <td class="px-1 py-1 font-medium">{{ $lead->companyDetail->company_name }}</td>
                                        <td class="px-1 py-1">{{ $lead->demoAppointment->first()?->type ?? 'N/A' }}</td>
                                        <td class="px-1 py-1">{{ $lead->demoAppointment->first()?->date ?? 'N/A' }}</td>
                                        <td class="px-1 py-1" style="color:red">
                                            {{ $lead->demoAppointment->first()?->start_time ? \Carbon\Carbon::parse($lead->demoAppointment->first()?->start_time)->format('h:i A') : 'N/A' }}
                                            -
                                            {{ $lead->demoAppointment->first()?->end_time ? \Carbon\Carbon::parse($lead->demoAppointment->first()?->end_time)->format('h:i A') : 'N/A' }}
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
                        <!-- Placeholder when no demos are scheduled -->
                        <div class="flex flex-col items-center justify-center mt-6">
                            <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
                            <p class="text-center text-gray-500">No data available.</p>
                        </div>
                    @endif
                </div>

                <!-- Prospect Reminder (Today) Section -->
                <div class="p-4 bg-white rounded-lg shadow">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold">Prospect Reminder (Today)</h3>
                        <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getTodayProspects()->count() }})</span>
                    </div>
                    @if ($this->getTodayProspects()->count() > 0)
                        <div class="mt-2 overflow-y-auto" style="max-height: 300px;">
                            <table class="w-full text-sm border-collapse table-fixed" style="width: 100%;">
                                <colgroup>
                                    <col style="width: 20%;">
                                    <col style="width: 30%;">
                                    <col style="width: 25%;">
                                    <col style="width: 15%;">
                                </colgroup>
                                @foreach ($this->getTodayProspects()->get() as $lead)
                                    <tr class="border-b">
                                        <td class="px-1 py-1 font-medium">{{ $lead->companyDetail->company_name ?? 'N/A' }}</td>
                                        <td class="px-1 py-1 font-medium"
                                            style="
                                                color: {{
                                                    str_contains($lead->activityLogs()->latest('created_at')->first()?->description ?? '', 'RFQ')
                                                        ? '#FFA500'
                                                        : (str_contains($lead->activityLogs()->latest('created_at')->first()?->description ?? '', '4th')
                                                            ? 'red'
                                                            : 'inherit')
                                                }};
                                                font-weight: {{
                                                    str_contains($lead->activityLogs()->latest('created_at')->first()?->description ?? '', 'RFQ') ||
                                                    str_contains($lead->activityLogs()->latest('created_at')->first()?->description ?? '', '4th')
                                                        ? 'bold'
                                                        : 'normal'
                                                }};
                                            ">
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
                        <!-- Placeholder when no prospects are found -->
                        <div class="flex flex-col items-center justify-center mt-6">
                            <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
                            <p class="text-center text-gray-500">No data available.</p>
                        </div>
                    @endif
                </div>

                <!-- Prospect Reminder (Overdue) Section -->
                <div class="p-4 bg-white rounded-lg shadow">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold">Prospect Reminder (Overdue)</h3>
                        <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getOverdueProspects()->count() }})</span>
                    </div>
                    @if ($this->getOverdueProspects()->count() > 0)
                        <div class="mt-2 overflow-y-auto" style="max-height: 300px;">
                            <table class="w-full text-sm border-collapse table-fixed" style="width: 100%;">
                                <colgroup>
                                    <col style="width: 15%;">
                                    <col style="width: 35%;">
                                    <col style="width: 20%;">
                                    <col style="width: 15%;">
                                    <col style="width: 15%;">
                                </colgroup>
                                @foreach ($this->getOverdueProspects()->get() as $lead)
                                    <tr class="border-b">
                                        <td class="px-1 py-1 font-medium">{{ $lead->companyDetail->company_name ?? 'N/A' }}</td>
                                        <td class="px-1 py-1 font-medium"
                                            style="
                                                color: {{
                                                    str_contains($lead->activityLogs()->latest('created_at')->first()?->description ?? '', 'RFQ')
                                                        ? '#FFA500'
                                                        : (str_contains($lead->activityLogs()->latest('created_at')->first()?->description ?? '', '4th')
                                                            ? 'red'
                                                            : 'inherit')
                                                }};
                                                font-weight: {{
                                                    str_contains($lead->activityLogs()->latest('created_at')->first()?->description ?? '', 'RFQ') ||
                                                    str_contains($lead->activityLogs()->latest('created_at')->first()?->description ?? '', '4th')
                                                        ? 'bold'
                                                        : 'normal'
                                                }};
                                            ">
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
                        <!-- Placeholder when no overdue prospects are found -->
                        <div class="flex flex-col items-center justify-center mt-6">
                            <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
                            <p class="text-center text-gray-500">No data available.</p>
                        </div>
                    @endif
                </div>

                <div class="p-4 bg-white rounded-lg shadow">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold">Debtor Follow Up (Today)</h3>
                        <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getOverdueProspects()->count() }})</span>
                    </div>
                    @if ($this->getTodayDebtors()->count() > 0)
                        <div class="mt-2 overflow-y-auto" style="max-height: 300px;">
                            <table class="w-full text-sm border-collapse table-fixed" style="width: 100%;">
                                <colgroup>
                                    <col style="width: 20%;">
                                    <col style="width: 30%;">
                                    <col style="width: 20%;">
                                    <col style="width: 20%;">
                                </colgroup>
                                @foreach ($this->getTodayDebtors()->get() as $lead)
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
                        <!-- Placeholder when no follow-ups are found -->
                        <div class="flex flex-col items-center justify-center mt-6">
                            <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
                            <p class="text-center text-gray-500">No data available.</p>
                        </div>
                    @endif
                </div>

                <div class="p-4 bg-white rounded-lg shadow">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold">Debtor Follow Up (Overdue)</h3>
                        <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getOverdueProspects()->count() }})</span>
                    </div>
                    @if ($this->getOverdueDebtors()->count() > 0)
                        <div class="mt-2 overflow-y-auto" style="max-height: 300px;">
                            <table class="w-full text-sm border-collapse table-fixed" style="width: 100%;">
                                <colgroup>
                                    <col style="width: 10%;">
                                    <col style="width: 35%;">
                                    <col style="width: 20%;">
                                    <col style="width: 15%;">
                                    <col style="width: 20%;">
                                </colgroup>
                                @foreach ($this->getOverdueDebtors()->get() as $lead)
                                    <tr class="border-b">
                                        <td class="px-1 py-1 font-medium">{{ $lead->companyDetail->company_name ?? 'N/A' }}</td>
                                        <td class="px-1 py-1 font-medium">
                                            {{ $lead->activityLogs()->latest('created_at')->first()?->description ?? 'N/A' }}
                                        </td>
                                        <td class="px-1 py-1">{{ $lead->remark ?? '-' }}</td>
                                        <td class="px-1 py-1 text-red-500" style="color: red">
                                            {{ $lead->updated_at ? $lead->updated_at->diffForHumans() : 'N/A' }}
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
                        <!-- Placeholder when no overdue debtor follow-ups are found -->
                        <div class="flex flex-col items-center justify-center mt-6">
                            <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
                            <p class="text-center text-gray-500">No data available.</p>
                        </div>
                    @endif
                </div>
                @else
                    <div class="p-4 bg-white rounded-lg shadow">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold">Demo (Today)</h3>
                            <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getTodayDemos()->count() }})</span>
                        </div>
                        @if ($this->getTodayDemos()->count() > 0)
                            <div class="mt-2 overflow-y-auto" style="max-height: 300px;">
                                <table class="w-full text-sm border-collapse table-fixed" style="width: 100%;">
                                    <colgroup>
                                        <col style="width: 20%;">
                                        <col style="width: 20%;">
                                        <col style="width: 20%;">
                                        <col style="width: 25%;">
                                        <col style="width: 15%;">
                                    </colgroup>
                                    @foreach ($this->getTodayDemos()->get() as $lead)
                                        <tr class="border-b">
                                            <td class="px-1 py-1 font-medium">{{ $lead->companyDetail->company_name }}</td>
                                            <td class="px-1 py-1">{{ $lead->demoAppointment->first()?->type ?? 'N/A' }}</td>
                                            <td class="px-1 py-1">{{ $lead->demoAppointment->first()?->date ?? 'N/A' }}</td>
                                            <td class="px-1 py-1" style="color:red">
                                                {{ $lead->demoAppointment->first()?->start_time ? \Carbon\Carbon::parse($lead->demoAppointment->first()?->start_time)->format('h:i A') : 'N/A' }}
                                                -
                                                {{ $lead->demoAppointment->first()?->end_time ? \Carbon\Carbon::parse($lead->demoAppointment->first()?->end_time)->format('h:i A') : 'N/A' }}
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
                            <!-- Placeholder when no demos are scheduled -->
                            <div class="flex flex-col items-center justify-center mt-6">
                                <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
                                <p class="text-center text-gray-500">No data available.</p>
                            </div>
                        @endif
                    </div>

                    <div class="p-4 bg-white rounded-lg shadow">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold">Demo (Tomorrow)</h3>
                            <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getTomorrowDemos()->count() }})</span>
                        </div>
                        @if ($this->getTomorrowDemos()->count() > 0)
                            <div class="mt-2 overflow-y-auto" style="max-height: 300px;">
                                <table class="w-full text-sm border-collapse table-fixed" style="width: 100%;">
                                    <colgroup>
                                        <col style="width: 20%;">
                                        <col style="width: 20%;">
                                        <col style="width: 20%;">
                                        <col style="width: 25%;">
                                        <col style="width: 15%;">
                                    </colgroup>
                                    @foreach ($this->getTomorrowDemos()->get() as $lead)
                                        <tr class="border-b">
                                            <td class="px-1 py-1 font-medium">{{ $lead->companyDetail->company_name }}</td>
                                            <td class="px-1 py-1">{{ $lead->demoAppointment->first()?->type ?? 'N/A' }}</td>
                                            <td class="px-1 py-1">{{ $lead->demoAppointment->first()?->date ?? 'N/A' }}</td>
                                            <td class="px-1 py-1" style="color:red">
                                                {{ $lead->demoAppointment->first()?->start_time ? \Carbon\Carbon::parse($lead->demoAppointment->first()?->start_time)->format('h:i A') : 'N/A' }}
                                                -
                                                {{ $lead->demoAppointment->first()?->end_time ? \Carbon\Carbon::parse($lead->demoAppointment->first()?->end_time)->format('h:i A') : 'N/A' }}
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
                            <!-- Placeholder when no demos are scheduled -->
                            <div class="flex flex-col items-center justify-center mt-6">
                                <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
                                <p class="text-center text-gray-500">No data available.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Prospect Reminder (Today) Section -->
                    <div class="p-4 bg-white rounded-lg shadow">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold">Prospect Reminder (Today)</h3>
                            <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getTodayProspects()->count() }})</span>
                        </div>
                        @if ($this->getTodayProspects()->count() > 0)
                            <div class="mt-2 overflow-y-auto" style="max-height: 300px;">
                                <table class="w-full text-sm border-collapse table-fixed" style="width: 100%;">
                                    <colgroup>
                                        <col style="width: 20%;">
                                        <col style="width: 30%;">
                                        <col style="width: 25%;">
                                        <col style="width: 15%;">
                                    </colgroup>
                                    @foreach ($this->getTodayProspects()->get() as $lead)
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
                            <!-- Placeholder when no prospects are found -->
                            <div class="flex flex-col items-center justify-center mt-6">
                                <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
                                <p class="text-center text-gray-500">No data available.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Prospect Reminder (Overdue) Section -->
                    <div class="p-4 bg-white rounded-lg shadow">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold">Prospect Reminder (Overdue)</h3>
                            <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getOverdueProspects()->count() }})</span>
                        </div>
                        @if ($this->getOverdueProspects()->count() > 0)
                            <div class="mt-2 overflow-y-auto" style="max-height: 300px;">
                                <table class="w-full text-sm border-collapse table-fixed" style="width: 100%;">
                                    <colgroup>
                                        <col style="width: 15%;">
                                        <col style="width: 35%;">
                                        <col style="width: 20%;">
                                        <col style="width: 15%;">
                                        <col style="width: 15%;">
                                    </colgroup>
                                    @foreach ($this->getOverdueProspects()->get() as $lead)
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
                            <!-- Placeholder when no overdue prospects are found -->
                            <div class="flex flex-col items-center justify-center mt-6">
                                <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
                                <p class="text-center text-gray-500">No data available.</p>
                            </div>
                        @endif
                    </div>

                    <div class="p-4 bg-white rounded-lg shadow">
                        <h3 class="text-lg font-bold">Debtor Follow Up (Today) <span class="text-gray-500" style="padding-left: 70%;">(Count: {{ $this->getOverdueProspects()->count() }})</span></h3>

                        @if ($this->getOverdueProspects()->count() > 0)
                            <div class="mt-2 overflow-y-auto" style="max-height: 300px;">
                                <table class="w-full text-sm border-collapse table-fixed" style="width: 100%;">
                                    <colgroup>
                                        <col style="width: 20%;">
                                        <col style="width: 30%;">
                                        <col style="width: 20%;">
                                        <col style="width: 20%;">
                                    </colgroup>
                                    @foreach ($this->getOverdueProspects()->get() as $lead)
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
                            <!-- Placeholder when no follow-ups are found -->
                            <div class="flex flex-col items-center justify-center mt-6">
                                <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
                                <p class="text-center text-gray-500">No data available.</p>
                            </div>
                        @endif
                    </div>

                    <div class="p-4 bg-white rounded-lg shadow">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold">Debtor Follow Up (Overdue)</h3>
                            <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getOverdueProspects()->count() }})</span>
                        </div>
                        @if ($this->getOverdueProspects()->count() > 0)
                            <div class="mt-2 overflow-y-auto" style="max-height: 300px;">
                                <table class="w-full text-sm border-collapse table-fixed" style="width: 100%;">
                                    <colgroup>
                                        <col style="width: 10%;">
                                        <col style="width: 35%;">
                                        <col style="width: 20%;">
                                        <col style="width: 15%;">
                                        <col style="width: 20%;">
                                    </colgroup>
                                    @foreach ($this->getOverdueProspects()->get() as $lead)
                                        <tr class="border-b">
                                            <td class="px-1 py-1 font-medium">{{ $lead->companyDetail->company_name ?? 'N/A' }}</td>
                                            <td class="px-1 py-1 font-medium">
                                                {{ $lead->activityLogs()->latest('created_at')->first()?->description ?? 'N/A' }}
                                            </td>
                                            <td class="px-1 py-1">{{ $lead->remark ?? '-' }}</td>
                                            <td class="px-1 py-1 text-red-500" style="color: red">
                                                {{ $lead->updated_at ? $lead->updated_at->diffForHumans() : 'N/A' }}
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
                            <!-- Placeholder when no overdue debtor follow-ups are found -->
                            <div class="flex flex-col items-center justify-center mt-6">
                                <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
                                <p class="text-center text-gray-500">No data available.</p>
                            </div>
                        @endif
                    </div>
                @endif
            {{-- </div> --}}
        @endif
    </div>
</x-filament::page>
