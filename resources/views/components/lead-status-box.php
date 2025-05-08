{{-- filepath: /var/www/html/timeteccrm/resources/views/components/lead-status-box.blade.php --}}
@php
    $lead = $getRecord();

    // Get lead status and color
    $leadStatus = $lead->lead_status ?? 'Unknown';
    $statusColor = match ($leadStatus) {
        'Hot' => 'text-rose-500',
        'Warm' => 'text-amber-500',
        'Cold' => 'text-blue-500',
        'Closed' => 'text-emerald-500',
        default => 'text-gray-500',
    };

    // Format last updated date
    $lastUpdated = $lead->updated_at ? $lead->updated_at->format('d M Y') : 'N/A';

    // Format deal amount
    $dealAmount = $lead->deal_amount ? number_format($lead->deal_amount, 2) : '0.00';
@endphp

<div class="p-4 mb-4 bg-white border border-gray-200 rounded-lg shadow-sm">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <div class="text-sm font-medium text-gray-500">Status</div>
            <div class="text-2xl font-bold {{ $statusColor }}">{{ $leadStatus }}</div>
            <div class="text-xs text-gray-400">Updated on {{ $lastUpdated }}</div>
        </div>

        <div>
            <div class="text-sm font-medium text-gray-500">Deal Amount</div>
            <div class="text-2xl font-bold">MYR {{ $dealAmount }}</div>
            <div class="text-xs text-gray-400">Click edit to update</div>
        </div>
    </div>

    <a
        href="#"
        class="block w-full px-4 py-2 mt-4 font-medium text-center text-white transition rounded bg-primary-600 hover:bg-primary-700"
        x-data
        x-on:click.prevent="$dispatch('open-modal', { id: 'edit-deal-amount-{{ $lead->id }}' })"
    >
        Edit
    </a>

    <x-filament::modal id="edit-deal-amount-{{ $lead->id }}" heading="Edit Deal Amount">
        <form wire:submit.prevent="updateDealAmount">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Deal Amount</label>
                    <input
                        type="number"
                        wire:model="dealAmount"
                        value="{{ $lead->deal_amount }}"
                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    />
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="px-4 py-2 text-white rounded-md bg-primary-600 hover:bg-primary-700"
                    >
                        Save Changes
                    </button>
                </div>
            </div>
        </form>
    </x-filament::modal>
</div>
