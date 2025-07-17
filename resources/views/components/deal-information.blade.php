@php
    $lead = $this->record;
    // Filter quotations to only include those marked as final
    $quotations = $lead->quotations()
        ->where('mark_as_final', true)
        ->orderByDesc('quotation_date')
        ->get();
@endphp

<div class="grid gap-6">
    {{-- Row: Deal Amount + Quotations --}}
    <div style="display: grid; gap: 24px;" class="grid gap-6 md:grid-cols-2">
        {{-- Deal Amount --}}
        <div>
            <div class="text-sm font-medium text-gray-950 dark:text-white">Deal Amount</div>
            <div class="text-sm text-gray-900 dark:text-white">
                RM {{ number_format($lead->deal_amount ?? 0, 2) }}
            </div>
        </div>

        {{-- Quotations (Final Only) --}}
        <div>
            <div class="text-sm font-medium text-gray-950 dark:text-white">Final Quotations</div>
            <div class="space-y-1 text-sm text-gray-900 dark:text-white">
                @forelse ($quotations as $quotation)
                    <div>
                        <a href="{{ route('pdf.print-quotation-v2', $quotation) }}"
                           target="_blank"
                           class="underline text-primary-600">
                            {{ $quotation->quotation_reference_no }}
                        </a>
                    </div>
                @empty
                    <div>No Final Quotations</div>
                @endforelse
            </div>
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
