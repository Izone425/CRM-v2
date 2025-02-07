<div class="p-4 bg-white rounded-lg shadow" wire:poll.5s>
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-bold">Inactive (25 Above)</h3>
        <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getInactiveBigCompanyLeads()->count() }})</span>
    </div>
    @if ($this->getInactiveBigCompanyLeads()->count() > 0)
        <div class="mt-2 overflow-y-auto" style="max-height: 263px">
            <table class="w-full text-sm border-collapse table-fixed" style="width: 100%;">
                <colgroup>
                    <col style="width: 25%;">
                    <col style="width: 10%;">
                    <col style="width: 20%;">
                    <col style="width: 25%;">
                    <col style="width: 20%;">
                </colgroup>
                <thead class="sticky top-0 bg-gray-100" style="z-index: 1;">
                    <tr>
                        <th class="px-2 py-2 font-semibold text-center text-gray-700 border-b">Company<br> Name</th>
                        <th class="px-2 py-2 font-semibold text-center text-gray-700 border-b cursor-pointer"
                            wire:click="sortBy('company_size', 'inactiveBigCompanyLeads')">
                            Company<br> Size
                            @if ($sortColumnInactiveBigCompanyLeads === 'company_size')
                                <span>{{ $sortDirectionInactiveBigCompanyLeads === 'asc' ? '▲' : '▼' }}</span>
                            @endif
                        </th>

                        <th class="px-2 py-2 font-semibold text-center text-gray-700 border-b cursor-pointer"
                            wire:click="sortBy('lead_status', 'inactiveBigCompanyLeads')">
                            Status
                            @if ($sortColumnInactiveBigCompanyLeads === 'lead_status')
                                <span>{{ $sortDirectionInactiveBigCompanyLeads === 'asc' ? '▲' : '▼' }}</span>
                            @endif
                        </th>

                        <th class="px-2 py-2 font-semibold text-center text-gray-700 border-b cursor-pointer"
                            wire:click="sortBy('pending_days', 'inactiveBigCompanyLeads')">
                            Completion<br> Days
                            @if ($sortColumnInactiveBigCompanyLeads === 'pending_days')
                                <span>{{ $sortDirectionInactiveBigCompanyLeads === 'asc' ? '▲' : '▼' }}</span>
                            @endif
                        </th>
                        <th class="px-2 py-2 font-semibold text-center text-gray-700 border-b">Action</th>
                    </tr>
                </thead>
                @foreach ($this->getInactiveBigCompanyLeads() as $lead)
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
                        <td class="text-center">
                            {{ $lead->lead_status }}
                        </td>
                        <td class="px-1 py-1 text-center" style="color: red">
                            {{ $lead->updated_at && $lead->created_at ? $lead->created_at->diffInDays($lead->updated_at) . ' days' : 'N/A' }}
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

