@php
    $lead = $this->record; // Accessing Filament's record

    $dealInformation = [
        ['label' => 'Deal Amount', 'value' => $lead ? 'RM ' . number_format($lead->deal_amount ?? 0, 2) : 'RM 0.00'],
        ['label' => 'Status', 'value' => ($lead->stage ?? $lead->categories)
            ? ($lead->stage ?? $lead->categories) . ' : ' . $lead->lead_status
            : '-'],
    ];
@endphp

<div style="display: grid; gap: 24px;" class="grid gap-6">

    @foreach ($dealInformation as $item)
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
