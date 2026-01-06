@php
    $lead = $record ?? $this->record ?? null;

    // Default status is "Pending SalesPerson"
    $einvoiceStatus = $lead->einvoice_status ?? 'Pending SalesPerson';

    // Get progress percentage and next step based on status
    $statusDetails = match($einvoiceStatus) {
        'Pending SalesPerson' => [
            'progress' => '33%',
            'step' => 'SalesPerson needs to complete E-Invoice details and submit for registration',
            'badge_color' => 'background-color: #fed7aa; color: #ea580c; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500;'
        ],
        'Pending Finance' => [
            'progress' => '66%',
            'step' => 'Finance team needs to complete the E-Invoice registration process',
            'badge_color' => 'background-color: #dbeafe; color: #2563eb; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500;'
        ],
        'Complete Registration' => [
            'progress' => '100%',
            'step' => 'E-Invoice registration has been completed successfully',
            'badge_color' => 'background-color: #dcfce7; color: #16a34a; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500;'
        ],
        default => [
            'progress' => '0%',
            'step' => 'Unknown status',
            'badge_color' => 'background-color: #f3f4f6; color: #6b7280; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500;'
        ]
    };

    $statusItems = [
        ['label' => 'Current Status', 'value' => $einvoiceStatus],
    ];
@endphp

<div style="display: grid; grid-template-columns: repeat(1, minmax(0, 1fr)); gap: 24px;"
     class="grid grid-cols-4 gap-6">

    @foreach ($statusItems as $item)
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
                        @if($item['label'] === 'Current Status')
                            <div class="text-sm leading-6 text-gray-900 fi-fo-placeholder dark:text-white">
                                <span style="{{ $statusDetails['badge_color'] }}">{{ $item['value'] }}</span>
                            </div>
                        @elseif($item['label'] === 'Progress')
                            <div class="text-sm leading-6 text-gray-900 fi-fo-placeholder dark:text-white">
                                <div class="w-full h-2 mb-1 bg-gray-200 rounded-full">
                                    <div class="h-2 bg-blue-600 rounded-full" style="width: {{ $item['value'] }}"></div>
                                </div>
                                <span class="text-xs text-gray-500">{{ $item['value'] }} Complete</span>
                            </div>
                        @else
                            <div class="text-sm leading-6 text-gray-900 fi-fo-placeholder dark:text-white">
                                {{ $item['value'] }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach

</div>
