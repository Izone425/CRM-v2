<x-filament::page>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        @if (auth()->user()->role_id == 1)
            @include('filament.pages.leadowner')

        @elseif (auth()->user()->role_id == 2)
            @include('filament.pages.salesperson')

        @elseif (auth()->user()->role_id == 3)
            <div class="space-y-4">
                <!-- Dropdown for Selecting a User -->
                <div class="flex items-center space-x-8"> <!-- Use space-x-8 for horizontal spacing -->
                    <!-- Dropdown -->
                    <div>
                        <select
                            wire:model.live="selectedUser"
                            id="userFilter"
                            class="mt-1 border-gray-300 rounded-md shadow-sm"
                        >
                            <option value="">Your Own Dashboard</option>
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

                    @if (!$selectedUser) <!-- Show toggle only when 'Your Own Dashboard' is selected -->
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <div style="display: flex; background: #f0f0f0; border-radius: 25px; padding: 3px;">
                            <!-- Lead Owner Button -->
                            <button
                                wire:click="toggleDashboard('LeadOwner')"
                                style="
                                    padding: 10px 15px;
                                    font-size: 14px;
                                    font-weight: bold;
                                    border: none;
                                    border-radius: 20px;
                                    background: {{ $currentDashboard === 'LeadOwner' ? '#431fa1' : 'transparent' }};
                                    color: {{ $currentDashboard === 'LeadOwner' ? '#ffffff' : '#555' }};
                                    cursor: pointer;
                                "
                            >
                                Lead Owner
                            </button>

                            <!-- Salesperson Button -->
                            <button
                                wire:click="toggleDashboard('Salesperson')"
                                style="
                                    padding: 10px 15px;
                                    font-size: 14px;
                                    font-weight: bold;
                                    border: none;
                                    border-radius: 20px;
                                    background: {{ $currentDashboard === 'Salesperson' ? '#431fa1' : 'transparent' }};
                                    color: {{ $currentDashboard === 'Salesperson' ? '#ffffff' : '#555' }};
                                    cursor: pointer;
                                "
                            >
                                Salesperson
                            </button>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <br>
            {{-- <div class="mt-8"> --}}
                @if ($selectedUserRole == 1)
                    <!-- Pending Section -->
                    <div class="p-4 bg-white rounded-lg shadow" >
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold">New Leads</h3>
                            <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getPendingLeadsQuery()->count() }})</span>
                        </div>
                        @if ($this->getPendingLeadsQuery()->count() > 0)
                            <div class="mt-2 overflow-y-auto" style="max-height: 263px;">
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
                                            <th class="px-2 py-2 font-semibold text-center text-gray-700 border-b">
                                                Company<br> Name
                                            </th>

                                            <th class="px-2 py-2 font-semibold text-center text-gray-700 border-b cursor-pointer"
                                                wire:click="sortBy('company_size', 'newLeads')">
                                                Company<br> Size
                                                @if ($sortColumnNewLeads === 'company_size')
                                                    <span>{{ $sortDirectionNewLeads === 'asc' ? '▲' : '▼' }}</span>
                                                @endif
                                            </th>

                                            <th class="px-2 py-2 font-semibold text-center text-gray-700 border-b cursor-pointer"
                                                wire:click="sortBy('created_at', 'newLeads')">
                                                Created<br> Time
                                                @if ($sortColumnNewLeads === 'created_at')
                                                    <span>{{ $sortDirectionNewLeads === 'asc' ? '▲' : '▼' }}</span>
                                                @endif
                                            </th>

                                            <th class="px-2 py-2 font-semibold text-center text-gray-700 border-b">
                                                Details
                                            </th>

                                            <th class="px-2 py-2 font-semibold text-center text-gray-700 border-b">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    @foreach ($this->getPendingLeadsQuery()->get() as $lead)
                                        <tr class="border-b">
                                            <td class="px-1 py-1 font-medium">
                                                <a href="{{ url('admin/leads/' . \App\Classes\Encryptor::encrypt($lead->id)) }}"
                                                   target="_blank"
                                                   class="inline-block"
                                                   style="color:#338cf0;">
                                                   {{ strtoupper(\Illuminate\Support\Str::limit($lead->companyDetail->company_name ?? 'N/A', 15, '...')) }}
                                                </a>
                                            </td>
                                            <td class="px-1 py-1">{{ $lead->getCompanySizeLabelAttribute() }}</td>
                                            <td class="px-1 py-1 text-center">{{ \Carbon\Carbon::parse($lead->created_at)->format('d M Y g:iA') }}</td>
                                            <td class="px-1 py-1 text-center">
                                                <div x-data="{ isOpen: false, leadId: null }">
                                                    <x-filament::modal width="2xl">
                                                        <x-slot name="trigger">
                                                            <span
                                                                style= "color:#338cf0;"
                                                                x-on:click="isOpen = true; leadId = {{ $lead->id }}">
                                                                View
                                                            </span>
                                                        </x-slot>

                                                        <x-slot name="heading">
                                                            <h3 class="text-lg font-bold text-left">View Lead Details
                                                        </x-slot>

                                                        <x-slot name="description">
                                                            <div class="text-left">
                                                                <p>COMPANY NAME: {{ $lead->companyDetail->company_name ?? 'N/A' }}</p>
                                                                <p>PIC NAME: {{ $lead->companyDetail->name ?? $lead->companyDetail->company_name }}</p>
                                                                <p>PIC CONTACT NO: {{ $lead->companyDetail->contact_no ?? $lead->phone }}</p>
                                                                <p>PIC EMAIL ADDRESS: {{ $lead->companyDetail->email ?? $lead->email }}</p>
                                                                <p>LEADS CREATED:
                                                                    {{ \Carbon\Carbon::parse($lead->created_at)->format('d M Y g:ia') }}
                                                                </p>
                                                            </div>
                                                        </x-slot>

                                                        <div class="gap-3 mt-3 d-flex justify-content-center align-items-center" style="display: inline-flex; align-self: center; gap: 3rem;">
                                                            <button
                                                                type="button"
                                                                x-on:click="isOpen = false"
                                                                style="background-color: #6c757d; color: white; border-radius: 20px; padding: 10px 20px; border: none;">
                                                                Close
                                                            </button>
                                                        </div>
                                                    </x-filament::modal>
                                                </div>
                                            </td>
                                            <td class="px-1 py-1 text-center">
                                                <div style="display: inline-flex; gap: 10px;">
                                                    <div x-data="{ isOpen: false, leadId: null }">
                                                        <form wire:submit.prevent="assignLead">
                                                            <x-filament::modal width="2xl">
                                                                <x-slot name="trigger">
                                                                    <x-filament::button
                                                                        type="button"
                                                                        x-on:click="isOpen = true; leadId = {{ $lead->id }}"
                                                                        class="text-white rounded-lg hover:bg-blue-600"
                                                                        style="background-color: #FFA500; white-space: nowrap;">
                                                                        Assign to Me
                                                                    </x-filament::button>
                                                                </x-slot>

                                                                <x-slot name="heading">
                                                                    <h3 class="text-lg font-bold">Confirm Lead Assignment</h3>
                                                                </x-slot>

                                                                <x-slot name="description">
                                                                    <p>Do you want to assign this lead to yourself? Make sure to confirm assignment before contacting the lead to avoid duplicate efforts by other team members.</p>
                                                                </x-slot>

                                                                <div class="gap-3 mt-3 d-flex justify-content-center align-items-center" style="display: inline-flex; align-self: center; gap: 3rem;">
                                                                    <button
                                                                        type="button"
                                                                        x-on:click="$wire.assignLead(leadId); isOpen = false"
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
                                                                        Assign to {{ $selectedUser ? \Illuminate\Support\Str::limit(\App\Models\User::find($selectedUser)->name, 6, '...') : 'Selected User' }}
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

                    <!-- My Pending Tasks Section -->
                    <div class="p-4 bg-white rounded-lg shadow" wire:poll.5s>
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold">My Pending Tasks</h3>
                            <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getNewLeadsQuery()->count() }})</span>
                        </div>
                        @if ($this->getNewLeadsQuery()->count() > 0)
                            <div class="mt-2 overflow-y-auto" style="max-height: 263px;">
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
                                                wire:click="sortBy('company_size', 'pendingTasks')">
                                                Company <br>Size
                                                @if ($sortColumnPendingTasks === 'company_size')
                                                    <span>{{ $sortDirectionPendingTasks === 'asc' ? '▲' : '▼' }}</span>
                                                @endif
                                            </th>

                                            <th class="px-2 py-2 font-semibold text-center text-gray-700 border-b cursor-pointer"
                                                wire:click="sortBy('created_at', 'pendingTasks')">
                                                Created <br>Time
                                                @if ($sortColumnPendingTasks === 'created_at')
                                                    <span>{{ $sortDirectionPendingTasks === 'asc' ? '▲' : '▼' }}</span>
                                                @endif
                                            </th>
                                            <th class="px-2 py-2 font-semibold text-center text-gray-700 border-b cursor-pointer"
                                                wire:click="sortBy('pending_days', 'pendingTasks')">
                                                Pending<br> Days
                                                @if ($sortColumnPendingTasks === 'pending_days')
                                                    <span>{{ $sortDirectionPendingTasks === 'asc' ? '▲' : '▼' }}</span>
                                                @endif
                                            </th>
                                            <th class="px-2 py-2 font-semibold text-center text-gray-700 border-b">Action</th>
                                        </tr>
                                    </thead>
                                    @foreach ($this->getNewLeadsQuery()->get() as $lead)
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
                                            <td class="px-1 py-1 text-center">{{ \Carbon\Carbon::parse($lead->created_at)->format('d M Y g:iA') }}</td>
                                            <td class="px-1 py-1 text-center"
                                                style="color: {{ ($lead->created_at->diffInDays(now()) == 0) ? 'black' : 'red' }}">

                                                @if ($lead->updated_at && $lead->created_at)
                                                    {{ $lead->created_at->diffInDays(now()) }} days
                                                @else
                                                    N/A
                                                @endif
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
                                            <th class="px-2 py-2 font-semibold text-center text-gray-700 border-b">
                                                Company<br> Name
                                            </th>
                                            <th class="px-2 py-2 font-semibold text-center text-gray-700 border-b cursor-pointer"
                                                wire:click="sortBy('company_size', 'prospectToday')">
                                                Company<br> Size
                                                @if ($sortColumnProspect === 'company_size')
                                                    <span>{{ $sortDirectionProspect === 'asc' ? '▲' : '▼' }}</span>
                                                @endif
                                            </th>

                                            <th class="px-2 py-2 font-semibold text-center text-gray-700 border-b cursor-pointer"
                                                wire:click="sortBy('created_at', 'prospectToday')">
                                                Created<br> Time
                                                @if ($sortColumnProspect === 'created_at')
                                                    <span>{{ $sortDirectionProspect === 'asc' ? '▲' : '▼' }}</span>
                                                @endif
                                            </th>
                                            <th class="px-2 py-2 font-semibold text-center text-gray-700 border-b">Pending<br> Days</th>
                                            <th class="px-2 py-2 font-semibold text-center text-gray-700 border-b">Actions</th>
                                        </tr>
                                    </thead>
                                    @foreach ($this->getProspectTodayQuery()->get() as $lead)
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
                                            <td class="px-1 py-1 text-center">{{ \Carbon\Carbon::parse($lead->created_at)->format('d M Y g:iA') }}</td>
                                            <td class="px-1 py-1 text-center">0 Days</td>
                                            <td class="px-1 py-1 text-center">
                                                <x-filament::button
                                                    style="background-color: #FFA500; white-space: nowrap;"
                                                    class="text-white hover:bg-orange-600">
                                                    <a href="{{ url('admin/leads/' . \App\Classes\Encryptor::encrypt($lead->id)) }}"
                                                       target="_blank"
                                                       class="text-white">
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
                                                wire:click="sortBy('company_size', 'prospectOverdue')">
                                                Company<br> Size
                                                @if ($sortColumnProspectOverdue === 'company_size')
                                                    <span>{{ $sortDirectionProspectOverdue === 'asc' ? '▲' : '▼' }}</span>
                                                @endif
                                            </th>

                                            <th class="px-2 py-2 font-semibold text-center text-gray-700 border-b cursor-pointer"
                                                wire:click="sortBy('created_at', 'prospectOverdue')">
                                                Created<br> Time
                                                @if ($sortColumnProspectOverdue === 'created_at')
                                                    <span>{{ $sortDirectionProspectOverdue === 'asc' ? '▲' : '▼' }}</span>
                                                @endif
                                            </th>
                                            <th class="px-2 py-2 font-semibold text-center text-gray-700 border-b cursor-pointer"
                                            wire:click="sortBy('pending_days_prospect_overdue', 'prospectOverdue')">
                                                Pending<br> Days
                                                @if ($sortColumnProspectOverdue === 'pending_days_prospect_overdue')
                                                    <span>{{ $sortDirectionProspectOverdue === 'asc' ? '▲' : '▼' }}</span>
                                                @endif
                                            </th>
                                            <th class="px-2 py-2 font-semibold text-center text-gray-700 border-b">Action</th>
                                        </tr>
                                    </thead>
                                    @foreach ($this->getProspectOverdueQuery()->get() as $lead)
                                        <tr class="border-b" style="height:43px;">
                                            <td class="px-1 py-1 font-medium">
                                                <a href="{{ url('admin/leads/' . \App\Classes\Encryptor::encrypt($lead->id)) }}"
                                                   target="_blank"
                                                   class="inline-block"
                                                   style="color:#338cf0;">

                                                   {{ strtoupper(\Illuminate\Support\Str::limit($lead->companyDetail->company_name ?? 'N/A', 15, '...')) }}

                                                </a>
                                            </td>
                                            <td class="px-1 py-1">{{ $lead->getCompanySizeLabelAttribute() }}</td>
                                            <td class="px-1 py-1 text-center">{{ \Carbon\Carbon::parse($lead->created_at)->format('d M Y g:iA') }}</td>
                                            <td class="px-1 py-1 text-center" style="color: red">
                                                {{ $lead->pending_days_prospect_overdue !== null ? $lead->pending_days_prospect_overdue . ' days' : 'N/A' }}
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
                @elseif ($selectedUserRole == 2)
                <div class="p-4 bg-white rounded-lg shadow" wire:poll.5s>
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
                <div class="p-4 bg-white rounded-lg shadow" wire:poll.5s>
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
                <div class="p-4 bg-white rounded-lg shadow" wire:poll.5s>
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

                <div class="p-4 bg-white rounded-lg shadow" wire:poll.5s>
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold">Debtor Follow Up (Today)</h3>
                        <span class="text-lg font-bold text-gray-500">(Count: 0)</span>
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

                <div class="p-4 bg-white rounded-lg shadow" wire:poll.5s>
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold">Debtor Follow Up (Overdue)</h3>
                        <span class="text-lg font-bold text-gray-500">(Count: 0)</span>
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
                    @if ($currentDashboard === 'LeadOwner')
                        @include('filament.pages.leadowner')
                    @elseif ($currentDashboard === 'Salesperson')
                        @include('filament.pages.salesperson')
                    @endif
                @endif
            {{-- </div> --}}
        @endif
    </div>
</x-filament::page>
