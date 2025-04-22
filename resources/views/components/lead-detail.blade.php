@php
    $lead = $this->record; // Accessing Filament's record

    $leadDetails = [
        ['label' => 'Lead ID', 'value' => isset($lead->id) ? str_pad($lead->id, 5, '0', STR_PAD_LEFT) : '-'],
        ['label' => 'Lead Source', 'value' => $lead->lead_code ?? '-'],
        ['label' => 'Lead Created By', 'value' => optional(optional($lead->activityLogs()
            ->where('description', 'New lead created')
            ->where('subject_id', $lead->id ?? null)
            ->first())->causer)->name ?? '-'],
        ['label' => 'Lead Created On', 'value' => isset($lead->created_at) ? $lead->created_at->format('d M Y, H:i') : '-'],
        ['label' => 'Company Size', 'value' => $lead->getCompanySizeLabelAttribute() ?? '-'],
        ['label' => 'Headcount', 'value' => $lead->company_size ?? '-'],
        ['label' => 'Customer Type', 'value' => $lead->customer_type ?? '-'],
        ['label' => 'Region', 'value' => $lead->region ?? '-'],
    ];

    // Split into rows with a max of 2 items per row
    $rows = array_chunk($leadDetails, 2);
@endphp

<div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 24px;"
     class="grid grid-cols-2 gap-6">

    @foreach ($rows as $row)
        @foreach ($row as $item)
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
    @endforeach
</div>
