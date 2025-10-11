<!-- filepath: /var/www/html/timeteccrm/resources/views/components/finance-details.blade.php -->
@php
    $lead = $this->record; // Accessing Filament's record

    $financeDetails = [
        ['label' => 'Name', 'value' => $lead->eInvoiceDetail->finance_person_name ?? '-'],
        ['label' => 'HP Number', 'value' => $lead->eInvoiceDetail->finance_person_contact ?? '-'],
        ['label' => 'Email Address', 'value' => $lead->eInvoiceDetail->finance_person_email ?? '-'],
        ['label' => 'Position', 'value' => $lead->eInvoiceDetail->finance_person_position ?? '-'],
    ];
@endphp

<div style="display: grid; grid-template-columns: repeat(1, minmax(0, 1fr)); gap: 24px;"
     class="grid grid-cols-4 gap-6">

    @foreach ($financeDetails as $item)
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

</div>
