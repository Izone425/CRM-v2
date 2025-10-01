@php
    $lead = $this->record; // Accessing Filament's record

    $companyDetails = [
        // First row - 3 columns
        ['label' => 'Company Name', 'value' => $lead->companyDetail->company_name ?? '-'],
        ['label' => 'Company Address 1', 'value' => $lead->companyDetail->company_address1 ?? '-'],
        ['label' => 'New Reg No.', 'value' => $lead->companyDetail->reg_no_new ?? '-'],
        ['label' => 'Company Address 2', 'value' => $lead->companyDetail->company_address2 ?? '-'],
        ['label' => 'Industry', 'value' => $lead->companyDetail->industry ?? '-'],

        // Second row - 3 columns
        // Combined Postcode and State in one cell (side by side)
        [
            'label' => 'Location',
            'value' => [
                ['label' => 'Postcode', 'value' => $lead->companyDetail->postcode ?? '-'],
                ['label' => 'State', 'value' => $lead->companyDetail->state ?? '-']
            ],
            'combined' => true
        ],
    ];

    // Split into rows with a max of 3 items per row
    $rows = array_chunk($companyDetails, 3);
@endphp

<div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 24px;"
     class="grid grid-cols-2 gap-6">

    @foreach ($rows as $row)
        @foreach ($row as $item)
            <div style="--col-span-default: span 1 / span 1;" class="col-[--col-span-default]">
                <div data-field-wrapper="" class="fi-fo-field-wrp">
                    @if (isset($item['combined']) && $item['combined'])
                        {{-- Combined field for Postcode and State (side by side) --}}
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            @foreach ($item['value'] as $subItem)
                                <div class="grid gap-y-2">
                                    <div class="flex items-center justify-between gap-x-3">
                                        <div class="inline-flex items-center fi-fo-field-wrp-label gap-x-3">
                                            <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                                {{ $subItem['label'] }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="grid auto-cols-fr gap-y-2">
                                        <div class="text-sm leading-6 text-gray-900 fi-fo-placeholder dark:text-white">
                                            {{ $subItem['value'] }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        {{-- Regular single field --}}
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
                    @endif
                </div>
            </div>
        @endforeach
    @endforeach
</div>
