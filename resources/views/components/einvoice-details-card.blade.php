{{-- filepath: /var/www/html/timeteccrm/resources/views/components/einvoice-details-card.blade.php --}}
@php
    $record = $this->record;
    $eInvoiceDetail = $record->eInvoiceDetail;
    $companyDetail = $record->companyDetail;

    // Helper function to get value with fallback
    $getValue = function($eInvoiceValue, $companyValue = null) {
        if ($eInvoiceValue) {
            return $eInvoiceValue;
        }
        if ($companyValue) {
            return $companyValue;
        }
        return '-';
    };

    $eInvoiceDetails = [
        // Company Information - fallback to companyDetail
        ['label' => 'Company Name', 'value' => $getValue($eInvoiceDetail->company_name ?? null, $companyDetail->company_name ?? null)],
        ['label' => 'Business Register Number', 'value' => $getValue($eInvoiceDetail->business_register_number ?? null, $companyDetail->reg_no_new ?? null)],
        ['label' => 'Tax Identification Number', 'value' => $eInvoiceDetail->tax_identification_number ?? '-'],
        ['label' => 'Business Category', 'value' => $eInvoiceDetail && $eInvoiceDetail->business_category ? ucfirst(str_replace('_', ' ', $eInvoiceDetail->business_category)) : '-'],

        // Add separator marker
        ['separator' => true],

        // Address Information - fallback to companyDetail
        ['label' => 'Address 1', 'value' => $getValue($eInvoiceDetail->address_1 ?? null, $companyDetail->company_address1 ?? null)],
        ['label' => 'Address 2', 'value' => $getValue($eInvoiceDetail->address_2 ?? null, $companyDetail->company_address2 ?? null)],
        ['label' => 'City', 'value' => $getValue($eInvoiceDetail->city ?? null, $companyDetail->city ?? null)],
        ['label' => 'Postcode', 'value' => $getValue($eInvoiceDetail->postcode ?? null, $companyDetail->postcode ?? null)],
        ['label' => 'State', 'value' => $getValue($eInvoiceDetail->state ?? null, $companyDetail->state ?? null)],
['label' => 'Country', 'value' => $getValue($eInvoiceDetail->country ?? null, $record->country ?? null)],
        // Add separator marker
        ['separator' => true],

        // Business Configuration
        ['label' => 'Currency', 'value' => $eInvoiceDetail->currency ?? '-'],
        ['label' => 'Business Type', 'value' => $eInvoiceDetail && $eInvoiceDetail->business_type ? ucfirst(str_replace('_', ' ', $eInvoiceDetail->business_type)) : '-'],
        ['label' => 'MSIC Code', 'value' => $eInvoiceDetail->msic_code ?? '-'],
        ['label' => 'Billing Category', 'value' => $eInvoiceDetail && $eInvoiceDetail->billing_category ? ucfirst(str_replace('_', ' ', $eInvoiceDetail->billing_category)) : '-'],
    ];

    // Group items with separators handled
    $processedItems = [];
    $currentRow = [];

    foreach ($eInvoiceDetails as $item) {
        if (isset($item['separator'])) {
            // If we have items in current row, add them
            if (!empty($currentRow)) {
                $processedItems[] = ['type' => 'row', 'items' => $currentRow];
                $currentRow = [];
            }
            // Add separator
            $processedItems[] = ['type' => 'separator'];
        } else {
            $currentRow[] = $item;
            // If we have 2 items, create a row
            if (count($currentRow) == 2) {
                $processedItems[] = ['type' => 'row', 'items' => $currentRow];
                $currentRow = [];
            }
        }
    }

    // Add any remaining items
    if (!empty($currentRow)) {
        $processedItems[] = ['type' => 'row', 'items' => $currentRow];
    }
@endphp

<div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 24px;" class="grid grid-cols-2 gap-6">
    @foreach ($processedItems as $processedItem)
        @if ($processedItem['type'] === 'separator')
            <div style="grid-column: 1 / -1;">
                <hr class="border-gray-200 dark:border-gray-700">
            </div>
        @else
            @foreach ($processedItem['items'] as $item)
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
        @endif
    @endforeach
</div>
