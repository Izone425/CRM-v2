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

<style>
    .einvoice-export-container {
        text-align: center;
        margin-top: 1.5rem;
        display: flex;
        justify-content: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .einvoice-export-btn, .sw-export-btn {
        display: inline-flex;
        align-items: center;
        color: #16a34a;
        background-color: white;
        text-decoration: none;
        font-weight: 500;
        padding: 0.75rem 1.5rem;
        border: 2px solid #16a34a;
        border-radius: 0.375rem;
        transition: all 0.2s;
        min-width: 200px;
        justify-content: center;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .einvoice-export-btn:hover, .sw-export-btn:hover {
        background-color: #16a34a;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
        text-decoration: none;
    }

    .einvoice-export-icon, .sw-export-icon {
        width: 1.25rem;
        height: 1.25rem;
        margin-right: 0.5rem;
        flex-shrink: 0;
    }
</style>

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

<div class="einvoice-export-container">
    <a href="{{ route('software-handover.export-customer', ['lead' => \App\Classes\Encryptor::encrypt($lead->id)]) }}"
        target="_blank"
        class="sw-export-btn">
        <!-- Download Icon -->
        <svg xmlns="http://www.w3.org/2000/svg" class="sw-export-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        Export AutoCount Debtor
    </a>

    <a href="{{ route('einvoice.export', [
            'lead' => \App\Classes\Encryptor::encrypt($lead->id),
            'subsidiaryId' => null
        ]) }}"
       target="_blank"
       class="einvoice-export-btn">
        <svg class="einvoice-export-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        Export AutoCount E-Invoice
    </a>
</div>
