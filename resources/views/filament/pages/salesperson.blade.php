<livewire:demo-today-table />
<livewire:demo-tmr-table />
<livewire:pr-today-salesperson-table />
<livewire:pr-overdue-salesperson-table />
<livewire:debtor-follow-up-today-table />
<livewire:debtor-follow-up-overdue-table />

{{-- <div class="p-4 bg-white rounded-lg shadow" wire:poll.5s>
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-bold">Debtor Follow Up (Today)</h3>
        <span class="text-lg font-bold text-gray-500">(Count: 0)</span>
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
        <div class="flex flex-col items-center justify-center h-full">
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
        <div class="flex flex-col items-center justify-center h-full">
            <i class="mb-4 text-gray-500 fa fa-question-circle fa-3x"></i>
            <p class="text-center text-gray-500">No data available.</p>
        </div>
    @endif
</div> --}}
