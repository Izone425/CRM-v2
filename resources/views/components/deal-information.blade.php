@php
    $lead = $this->record;
    // Filter quotations to only include those marked as final
    $quotations = $lead->quotations()
        ->where('mark_as_final', true)
        ->orderByDesc('quotation_date')
        ->get();

    // Separate quotations by sales type
    $newSalesQuotations = $quotations->where('sales_type', 'NEW SALES');
    $renewalQuotations = $quotations->where('sales_type', 'RENEWAL SALES');

    $hasNewSalesQuotations = $newSalesQuotations->count() > 0;
    $hasRenewalQuotations = $renewalQuotations->count() > 0;

    // Calculate NEW SALES deal amount
    $newSalesDealAmount = 0;
    if ($hasNewSalesQuotations) {
        foreach ($newSalesQuotations as $quotation) {
            $newSalesDealAmount += $quotation->items->sum('total_before_tax');
        }
    }

    // Calculate RENEWAL SALES deal amount
    $renewalDealAmount = 0;
    if ($hasRenewalQuotations) {
        foreach ($renewalQuotations as $quotation) {
            $renewalDealAmount += $quotation->items->sum('total_before_tax');
        }
    }
@endphp

<div class="grid gap-6">
    {{-- Row: Deal Amount + Quotations --}}
    {{-- <div class="text-sm leading-6 text-gray-900 dark:text-white">
        <span class="font-medium">Deal Amt:</span>
        @if ($hasNewSalesQuotations)
            RM {{ number_format($newSalesDealAmount, 2) }}
            @foreach ($newSalesQuotations as $quotation)
                <a href="{{ route('pdf.print-quotation-v2', ['quotation' => encrypt($quotation->id)]) }}"
                   target="_blank"
                   class="underline text-primary-600">
                    <span class="ml-1 text-xs text-gray-500">-</span>
                    {{ $quotation->quotation_reference_no }}
                </a>
            @endforeach
        @else
            <span class="text-xs text-gray-500">No NEW SALES quotations</span>
        @endif
    </div> --}}

    {{-- @if ($hasRenewalQuotations)
        <div>
            <div class="text-sm font-medium text-gray-950 dark:text-white">Renewal Deal Amount</div>
            <div class="text-sm text-gray-900 dark:text-white">
                RM {{ number_format($renewalDealAmount, 2) }}
                @foreach ($renewalQuotations as $quotation)
                    <a href="{{ route('pdf.print-quotation-v2', ['quotation' => encrypt($quotation->id)]) }}"
                       target="_blank"
                       class="underline text-primary-600">
                        <span class="ml-1 text-xs text-gray-500">
                            -
                        </span>
                        {{ $quotation->quotation_reference_no }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif --}}

    {{-- Row: Status --}}
    <div>
        <div class="text-sm leading-6 text-gray-900 dark:text-white">
            <span class="font-medium">Status:</span> {{ ($lead->stage ?? $lead->categories) ? ($lead->stage ?? $lead->categories) . ' - ' . $lead->lead_status : '-' }}
        </div>
    </div>
</div>
