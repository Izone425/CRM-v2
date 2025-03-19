@php
    $lead = $this->record; // Accessing Filament's record

    $personDetails = [
        ['label' => 'Name', 'value' => $lead->companyDetail->name ?? $lead->name ?? '-'],
        ['label' => 'Contact No.', 'value' => $lead->companyDetail->contact_no ?? $lead->phone ?? '-'],
        ['label' => 'Email Address', 'value' => $lead->companyDetail->email ?? $lead->email ?? '-'],
    ];
@endphp

<div style="display: grid; gap: 24px;" class="grid gap-6">

    @foreach ($personDetails as $item)
        <div style="--col-span-default: span 1 / span 1;" class="col-[--col-span-default]">
            <div data-field-wrapper="" class="fi-fo-field-wrp">
                <div class="grid gap-y-2">
                    <div class="flex items-center justify-between gap-x-3">
                        <label class="inline-flex items-center fi-fo-field-wrp-label gap-x-3">
                            <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                {{ $item['label'] }}
                            </span>
                        </label>
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
