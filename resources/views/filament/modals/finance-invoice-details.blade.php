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

@php
    // Fetch invoice details from crm_invoice_details table
    $invoiceDetail = null;
    if ($record->tt_invoice) {
        $invoiceDetail = \App\Models\CrmInvoiceDetail::where('f_invoice_no', $record->tt_invoice)->first();
    }
@endphp

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
                <span class="field-label">TT Invoice: </span>
                @if($invoiceDetail)
                    @php
                        $aesKey = 'Epicamera@99';
                        try {
                            $encrypted = openssl_encrypt($invoiceDetail->f_id, "AES-128-ECB", $aesKey);
                            $encryptedBase64 = base64_encode($encrypted);
                            $invoiceUrl = 'https://www.timeteccloud.com/paypal_reseller_invoice?iIn=' . $encryptedBase64;
                        } catch (\Exception $e) {
                            $invoiceUrl = null;
                        }
                    @endphp
                    @if($invoiceUrl)
                        <a href="{{ $invoiceUrl }}" target="_blank">{{ $invoiceDetail->f_invoice_no }}</a>
                    @else
                        <span class="field-value">{{ $invoiceDetail->f_invoice_no }}</span>
                    @endif
                @else
                    <span class="field-value">N/A</span>
                @endif
            </div>

            <div class="field-inline">
                <span class="field-label">AutoCount Invoice: </span>
                <span class="field-value">{{ $invoiceDetail->f_auto_count_inv ?? $record->autocount_invoice ?? 'N/A' }}</span>
            </div>

            <div class="field-inline">
                <span class="field-label">Sample Reseller Invoice: </span>
                @if($record->financeInvoice)
                    <a href="{{ route('pdf.print-finance-invoice', $record->financeInvoice) }}" target="_blank">{{ $record->financeInvoice->fc_number }}</a>
                @else
                    <span class="field-value">N/A</span>
                @endif
            </div>
        </div>

        <!-- Right Column -->
        <div class="column-content">
            <div class="field-inline">
                <span class="field-label">Invoice Date: </span>
                <span class="field-value">{{ $invoiceDetail && $invoiceDetail->f_created_time ? \Carbon\Carbon::parse($invoiceDetail->f_created_time)->format('d M Y, H:i') : ($record->created_at ? $record->created_at->format('d M Y, H:i') : 'N/A') }}</span>
            </div>

            <div class="field-inline">
                <span class="field-label">Currency: </span>
                <span class="field-value">{{ $invoiceDetail->f_currency ?? 'MYR' }}</span>
            </div>

            <div class="field-inline">
                <span class="field-label">Total Amount: </span>
                <span class="field-value" style="font-weight: 600;">{{ $invoiceDetail && $invoiceDetail->f_currency ? $invoiceDetail->f_currency : '' }} {{ $invoiceDetail ? number_format($invoiceDetail->f_total_amount ?? 0, 2) : '0.00' }}</span>
            </div>
        </div>
    </div>
</div>
