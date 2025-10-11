<!-- filepath: /var/www/html/timeteccrm/resources/views/components/utm-details.blade.php -->
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
@endphp

<div style="display: grid; grid-template-columns: repeat(1, minmax(0, 1fr)); gap: 24px;" class="grid grid-cols-2 gap-6">
    @foreach ($utmDetails as $item)
        <div style="--col-span-default: span 1 / span 1;" class="col-[--col-span-default]">
            <div data-field-wrapper="" class="fi-fo-field-wrp">
                <div class="grid gap-y-2">
                    <div class="text-sm leading-6 text-gray-900 break-words whitespace-normal fi-fo-placeholder dark:text-white">
                        <span class="font-medium text-gray-950 dark:text-white">{{ $item['label'] }}:</span> {{ $item['value'] }}
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
