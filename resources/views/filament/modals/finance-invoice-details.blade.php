    <style>
    .finance-invoice-modal {
        padding: 1rem;
    }

    .finance-invoice-modal .top-section {
        margin-bottom: 1.5rem;
    }

    .finance-invoice-modal .field-row {
        padding-bottom: 0.75rem;
        margin-bottom: 0.75rem;
        /* border-bottom: 1px solid #d1d5db; */
    }

    .finance-invoice-modal .field-label {
        font-weight: 600;
        color: #111827;
    }

    .finance-invoice-modal .field-value {
        color: #111827;
    }

    .finance-invoice-modal .two-columns {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }

    .finance-invoice-modal .column-content {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .finance-invoice-modal .field-inline {
        line-height: 1.5;
    }

    .finance-invoice-modal a {
        color: #2563eb;
        text-decoration: underline;
    }

    .finance-invoice-modal a:hover {
        color: #1d4ed8;
    }

    .dark .finance-invoice-modal .field-label,
    .dark .finance-invoice-modal .field-value {
        color: #f9fafb;
    }

    .dark .finance-invoice-modal .field-row {
        border-bottom-color: #4b5563;
    }
</style>

<div class="finance-invoice-modal">
    <!-- Top Section - Company and Subscriber Names -->
    <div class="top-section">
        <div class="field-row">
            <span class="field-label">Company Name: </span>
            <span class="field-value">{{ $record->reseller_name ?? 'N/A' }}</span>
        </div>

        <div class="field-row" style= "border-bottom: 1px solid #d1d5db;">
            <span class="field-label">Subscriber Name: </span>
            <span class="field-value">{{ $record->subscriber_name ?? 'N/A' }}</span>
        </div>
    </div>

    <!-- Two Columns Section -->
    <div class="two-columns">
        <!-- Left Column -->
        <div class="column-content">
            <div class="field-inline">
                <span class="field-label">Invoice: </span>
                @if($record->resellerHandover && $record->resellerHandover->timetec_proforma_invoice)
                    <a href="{{ $record->resellerHandover->invoice_url }}" target="_blank">CLICKABLE</a>
                @else
                    <span class="field-value">N/A</span>
                @endif
            </div>

            <div class="field-inline">
                <span class="field-label">AutoCount Invoice: </span>
                <span class="field-value">{{ $record->autocount_invoice_number ?? 'N/A' }}</span>
            </div>

            <div class="field-inline">
                <span class="field-label">Sample Reseller Invoice: </span>
                @php
                    $resellerInvoice = \App\Models\FinanceInvoice::where('reseller_handover_id', $record->reseller_handover_id)
                        ->where('portal_type', 'reseller')
                        ->first();
                @endphp
                @if($resellerInvoice)
                    <a href="{{ route('pdf.print-finance-invoice', $resellerInvoice) }}" target="_blank">{{ $resellerInvoice->fc_number }}</a>
                @else
                    <span class="field-value">N/A</span>
                @endif
            </div>
        </div>

        <!-- Right Column -->
        <div class="column-content">
            <div class="field-inline">
                <span class="field-label">Invoice Date: </span>
                <span class="field-value">{{ $record->created_at ? $record->created_at->format('d M Y, H:i') : 'N/A' }}</span>
            </div>

            <div class="field-inline">
                <span class="field-label">Currency: </span>
                <span class="field-value">MYR</span>
            </div>

            <div class="field-inline">
                <span class="field-label">Amount: </span>
                <span class="field-value" style="font-weight: 600;">RM {{ number_format($record->reseller_commission_amount ?? 0, 2) }}</span>
            </div>
        </div>
    </div>
</div>
