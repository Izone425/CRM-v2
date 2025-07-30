@php
    $referral = $this->record?->referralDetail;

    $closedByUser = \App\Models\User::find($this->record->closed_by);

    $referralDetails = [
        ['label' => 'COMPANY', 'value' => $referral?->company ?? '-'],
        ['label' => 'NAME', 'value' => $referral?->name ?? '-'],
        ['label' => 'CLOSED BY', 'value' => $closedByUser ? $closedByUser->name : '-'],
        ['label' => 'EMAIL ADDRESS', 'value' => $referral?->email ?? '-'],
        ['label' => 'CONTACT NO.', 'value' => $referral?->contact_no ?? '-'],
        ['label' => 'REMARK', 'value' => $referral?->remark ?? '-'],
    ];

    $rows = array_chunk($referralDetails, 2);
@endphp

<div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 24px;"
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
