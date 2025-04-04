@php
    $lead = $this->record;
    $utm = $lead->utmDetail;

    $utmDetails = [
        ['label' => 'UTM Campaign', 'value' => $utm->utm_campaign ?? '-'],
        ['label' => 'UTM Ad Group', 'value' => $utm->utm_adgroup ?? '-'],
        ['label' => 'UTM Creative', 'value' => $utm->utm_creative ?? '-'],
        ['label' => 'UTM Term', 'value' => $utm->utm_term ?? '-'],
        ['label' => 'UTM Matchtype', 'value' => $utm->utm_matchtype ?? '-'],
        ['label' => 'Device', 'value' => $utm->device ?? '-'],
        ['label' => 'Referrer Name', 'value' => $utm->referrername ?? '-'],
        ['label' => 'GCLID', 'value' => $utm->gclid ?? '-'],
        ['label' => 'Social Lead ID', 'value' => $utm->social_lead_id ?? '-'],
    ];

    // Chunk into rows of 4 for 4-column layout
    $rows = array_chunk($utmDetails, 4);
@endphp

<div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 24px;" class="grid grid-cols-4 gap-6">
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
