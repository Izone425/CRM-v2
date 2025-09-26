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
            ['label' => 'Tax Identification Number', 'value' => $einvoice?->tin_no ?? '-'],
            ['label' => 'SST Registration No', 'value' => $einvoice?->sst_reg_no ?? '-'],
            ['label' => 'MSIC Code', 'value' => $einvoice?->msic_code ?? '-'],
            ['label' => 'Email Address', 'value' => $einvoice?->email_address ?? '-', 'note' => '(For receiving e-invoice from IRBM)'],
        ];

        // Split into rows with 3 items per row
        $rows = array_chunk($einvoiceDetails, 3);
    @endphp

    <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 24px;"
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
