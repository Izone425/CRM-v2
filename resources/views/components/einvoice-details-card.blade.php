<!-- filepath: /var/www/html/timeteccrm/resources/views/components/einvoice-details-card.blade.php -->
@php
    $record = $this->record;
    $eInvoiceDetail = $record->eInvoiceDetail;
@endphp

@if($eInvoiceDetail)
    @php
        $eInvoiceDetails = [
            // Company Information
            ['label' => 'Company Name', 'value' => $eInvoiceDetail->company_name ?? '-'],
            ['label' => 'Business Register Number', 'value' => $eInvoiceDetail->business_register_number ?? '-'],
            ['label' => 'Tax Identification Number', 'value' => $eInvoiceDetail->tax_identification_number ?? '-'],
            ['label' => 'Business Category', 'value' => $eInvoiceDetail->business_category ? ucfirst(str_replace('_', ' ', $eInvoiceDetail->business_category)) : '-'],

            // Address Information
            ['label' => 'Address 1', 'value' => $eInvoiceDetail->address_1 ?? '-'],
            ['label' => 'Address 2', 'value' => $eInvoiceDetail->address_2 ?? '-'],
            ['label' => 'City', 'value' => $eInvoiceDetail->city ?? '-'],
            ['label' => 'Postcode', 'value' => $eInvoiceDetail->postcode ?? '-'],
            ['label' => 'State', 'value' => $eInvoiceDetail->state ?? '-'],
            ['label' => 'Country', 'value' => $eInvoiceDetail->country ?? '-'],

            // Business Configuration
            ['label' => 'Currency', 'value' => $eInvoiceDetail->currency ?? '-'],
            ['label' => 'Business Type', 'value' => $eInvoiceDetail->business_type ? ucfirst(str_replace('_', ' ', $eInvoiceDetail->business_type)) : '-'],
            ['label' => 'MSIC Code', 'value' => $eInvoiceDetail->msic_code ?? '-'],
            ['label' => 'Billing Category', 'value' => $eInvoiceDetail->billing_category ? ucfirst(str_replace('_', ' ', $eInvoiceDetail->billing_category)) : '-'],
        ];

        // Chunk into rows of 2 for 2-column layout
        $rows = array_chunk($eInvoiceDetails, 2);
    @endphp

    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 24px;" class="grid grid-cols-2 gap-6">
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
                                <div class="text-sm leading-6 text-gray-900 break-words whitespace-normal fi-fo-placeholder dark:text-white">
                                    {{ $item['value'] }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @endforeach
    </div>
@else
    <div class="py-12 text-center">
        <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <h3 class="mb-2 text-lg font-medium text-gray-900">No E-Invoice Details</h3>
        <p class="text-sm text-gray-500">Click "Edit E-Invoice Details" to add e-invoice information for this lead.</p>
    </div>
@endif
