{{-- filepath: /var/www/html/timeteccrm/resources/views/components/e-invoice-details.blade.php --}}
<div
    x-data="{}"
    x-init="
        $wire.on('refresh-form', () => {
            setTimeout(() => { window.location.reload(); }, 300);
        });
    "
    wire:poll.10s="$refresh"
>
    @php
        $record = $getRecord();
        $einvoice = $record->eInvoiceDetail ?? null;

        // Safely create the einvoiceDetails array
        $einvoiceDetails = [
            ['label' => '1. PIC Email Address', 'value' => $einvoice?->pic_email ?? '-', 'note' => '(Contact for information)'],
            ['label' => '2. Tax Identification Number', 'value' => $einvoice?->tin_no ?? '-'],
            ['label' => '3. New Business Reg No', 'value' => $einvoice?->new_business_reg_no ?? '-'],
            ['label' => '4. Old Business Reg No', 'value' => $einvoice?->old_business_reg_no ?? '-'],
            ['label' => '5. Registration Name', 'value' => $einvoice?->registration_name ?? '-'],
            ['label' => '6. Identity Type', 'value' => $einvoice?->identity_type ?? '-'],
            ['label' => '7. Tax Classification', 'value' => $einvoice?->tax_classification ?? '-'],
            ['label' => '8. SST Registration No', 'value' => $einvoice?->sst_reg_no ?? '-'],
            ['label' => '9. MSIC Code', 'value' => $einvoice?->msic_code ?? '-'],
            ['label' => '10. MSIC Code 2', 'value' => $einvoice?->msic_code_2 ?? '-'],
            ['label' => '11. MSIC Code 3', 'value' => $einvoice?->msic_code_3 ?? '-'],
            ['label' => '12. Business Address', 'value' => $einvoice?->business_address ?? '-'],
            ['label' => '13. Postcode', 'value' => $einvoice?->postcode ?? '-'],
            ['label' => '14. Contact Number', 'value' => $einvoice?->contact_number ?? '-', 'note' => '(Finance/Account Department)'],
            ['label' => '15. Email Address', 'value' => $einvoice?->email_address ?? '-', 'note' => '(For receiving e-invoice from IRBM)'],
            ['label' => '16. City', 'value' => $einvoice?->city ?? '-'],
            ['label' => '17. Country', 'value' => ($einvoice && $einvoice->country === 'MYS') ? 'Malaysia (MYS)' : ($einvoice?->country ?? '-')],
            ['label' => '18. State', 'value' => $einvoice?->state ?? '-'],
        ];

        // Split into rows with 3 items per row
        $rows = array_chunk($einvoiceDetails, 3);
    @endphp

    <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 24px;"
         class="grid grid-cols-3 gap-6">

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
                                {{-- @if(isset($item['note']))
                                    <div class="text-xs text-gray-500">
                                        {{ $item['note'] }}
                                    </div>
                                @endif --}}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @endforeach
    </div>
</div>
