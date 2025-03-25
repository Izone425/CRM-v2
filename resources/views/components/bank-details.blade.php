@php
    $bank = $this->record?->bankDetail;

    $bankDetails = [
        ['label' => 'FULL NAME', 'value' => $bank?->full_name ?? '-'],
        ['label' => 'BANK NAME', 'value' => $bank?->bank_name ?? '-'],
        ['label' => 'CONTACT NO.', 'value' => $bank?->contact_no ?? '-'],
        ['label' => 'REFERRAL PAYMENT STATUS', 'value' => $bank?->referral_payment_status ?? '-'],
        ['label' => 'IC NO.', 'value' => $bank?->ic ?? '-'],
        ['label' => 'BANK ACCOUNT NO.', 'value' => $bank?->bank_account_no ?? '-'],
        ['label' => 'EMAIL ADDRESS', 'value' => $bank?->email ?? '-'],
        ['label' => 'REMARK', 'value' => $bank?->remark ?? '-'],
        ['label' => 'TIN NO.', 'value' => $bank?->tin ?? '-'],
    ];

    $rows = array_chunk($bankDetails, 4); // 4 columns per row
@endphp

<div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 24px;"
     class="grid grid-cols-2 gap-6">
    @foreach ($rows as $row)
        @foreach ($row as $item)
            <div>
                <div class="fi-fo-field-wrp">
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
    @endforeach
</div>
