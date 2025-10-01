@php
    $lead = $this->record; // Accessing Filament's record

    $leadDetails = [
        ['label' => 'Lead Owner', 'value' => $lead->lead_owner ?? 'No Lead Owner'],
        ['label' => 'Salesperson', 'value' => $lead->salesperson
            ? optional(\App\Models\User::find($lead->salesperson))->name ?? 'No Salesperson'
            : 'No Salesperson'],
    ];
@endphp

<div class="grid gap-2">
    @foreach ($leadDetails as $item)
        <div class="text-sm leading-6 text-gray-900 dark:text-white">
            <span class="font-medium">{{ $item['label'] }}:</span> {{ $item['value'] }}
        </div>
    @endforeach
</div>
