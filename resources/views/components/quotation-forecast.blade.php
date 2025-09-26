@php
    $lead = $this->record;
    // Only get quotations marked as final
    $finalQuotations = $lead->quotations()
        ->where('mark_as_final', 1)
        ->where('sales_type', 'NEW SALES')
        ->orderByDesc('quotation_date')
        ->get();

    // Calculate total deal amount based on final quotations
    $totalDealAmount = 0;
    foreach ($finalQuotations as $quotation) {
        // Get the total amount from quotation items (before tax)
        $totalDealAmount += $quotation->items->sum('total_before_tax');
    }

    if ($lead->deal_amount != $totalDealAmount && $totalDealAmount > 0) {
        // Using a database transaction to ensure data integrity
        \Illuminate\Support\Facades\DB::transaction(function() use ($lead, $totalDealAmount) {
            $lead->deal_amount = $totalDealAmount;
            $lead->updateQuietly(['deal_amount' => $totalDealAmount]);
        });
    }
@endphp

<div class="grid gap-6" wire:poll.300s>
    {{-- Row: Deal Amount + Quotations --}}
    <div style="display: grid; gap: 24px;" class="grid gap-6 md:grid-cols-3">
        {{-- Deal Amount --}}
        <div>
            <div class="text-sm font-medium text-gray-950 dark:text-white">Deal Amount</div>
            <div class="text-sm text-gray-900 dark:text-white">
                RM {{ number_format($totalDealAmount ?? 0, 2) }}
            </div>
        </div>

        {{-- Final Quotations --}}
        <div>
            <div class="text-sm font-medium text-gray-950 dark:text-white">Final Quotations</div>
            <div class="space-y-1 text-sm text-gray-900 dark:text-white">
                @forelse ($finalQuotations as $quotation)
                    <div>
                        <a href="{{ route('pdf.print-quotation-v2', ['quotation' => encrypt($quotation->id)]) }}"
                           target="_blank"
                           class="underline text-primary-600">
                            {{ $quotation->quotation_reference_no }}
                            <span class="font-medium text-gray-600">
                                (RM {{ number_format($quotation->items->sum('total_before_tax'), 2) }})
                            </span>
                        </a>
                    </div>
                @empty
                    <div>No Final Quotations</div>
                @endforelse
            </div>
        </div>

        {{-- Row: Status --}}
        <div>
            <div class="text-sm font-medium text-gray-950 dark:text-white">Status</div>
            <div class="text-sm text-gray-900 dark:text-white">
                {{ ($lead->stage ?? $lead->categories) ? ($lead->stage ?? $lead->categories) . ' : ' . $lead->lead_status : '-' }}
            </div>
        </div>
    </div>
</div>
