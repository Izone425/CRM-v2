@php
    $lead = $this->record; // Accessing Filament's record

    $progressDetails = [
        ['label' => 'Total Days from Lead Created', 'value' => $lead->created_at ? now()->diffInDays($lead->created_at) . ' days' : '-'],
        ['label' => 'Total Days from New Demo', 'value' => $lead->calculateDaysFromNewDemo() . ' days'],
        ['label' => 'Salesperson Assigned Date', 'value' => $lead->salesperson_assigned_date
            ? \Carbon\Carbon::parse($lead->salesperson_assigned_date)->format('d M Y')
            : '-'],
        ['label' => 'Call Attempt', 'value' => $lead->call_attempt . ' times'],
        ['label' => 'Closing Date', 'value' => $lead->closing_date ? \Carbon\Carbon::parse($lead->closing_date)->format('d M Y') : '-'],
    ];
@endphp

<div style="display: grid; gap: 24px;" class="grid gap-6">

    @foreach ($progressDetails as $item)
        <div style="--col-span-default: span 1 / span 1;" class="col-[--col-span-default]">
            <div data-field-wrapper="" class="fi-fo-field-wrp">
                <div class="grid gap-y-2">
                    <div class="flex items-center justify-between gap-x-3">
                        <div class="inline-flex items-center fi-fo-field-wrp-label gap-x-3">
                            <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                {{ $item['label'] }}
                            </span>
                        </div>
                    </div>
                    <div class="grid auto-cols-fr gap-y-2">
                        <div class="text-sm leading-6 text-gray-900 fi-fo-placeholder dark:text-white">
                            {{ $item['value'] }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

</div>
