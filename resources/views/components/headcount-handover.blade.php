{{-- filepath: /var/www/html/timeteccrm/resources/views/components/headcount-handover.blade.php --}}
@php
    $record = $extraAttributes['record'] ?? null;

    if (!$record) {
        return;
    }

    // Format the handover ID
    $handoverId = 'HC_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);

    // Get PI details
    $productPIs = [];
    $hrdfPIs = [];

    if ($record->proforma_invoice_product) {
        $productPiIds = is_string($record->proforma_invoice_product)
            ? json_decode($record->proforma_invoice_product, true)
            : $record->proforma_invoice_product;

        if (is_array($productPiIds)) {
            $productPIs = App\Models\Quotation::whereIn('id', $productPiIds)->get();
        }
    }

    if ($record->proforma_invoice_hrdf) {
        $hrdfPiIds = is_string($record->proforma_invoice_hrdf)
            ? json_decode($record->proforma_invoice_hrdf, true)
            : $record->proforma_invoice_hrdf;

        if (is_array($hrdfPiIds)) {
            $hrdfPIs = App\Models\Quotation::whereIn('id', $hrdfPiIds)->get();
        }
    }

    // Get files
    $paymentSlipFiles = [];
    $confirmationOrderFiles = [];
    $invoiceFiles = [];

    if ($record->payment_slip_file) {
        $paymentSlipFiles = is_string($record->payment_slip_file)
            ? json_decode($record->payment_slip_file, true)
            : $record->payment_slip_file;
    }

    if ($record->confirmation_order_file) {
        $confirmationOrderFiles = is_string($record->confirmation_order_file)
            ? json_decode($record->confirmation_order_file, true)
            : $record->confirmation_order_file;
    }

    // Get invoice files for completed handovers
    if ($record->invoice_file) {
        if (is_string($record->invoice_file)) {
            $decoded = json_decode($record->invoice_file, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $invoiceFiles = $decoded;
            } else {
                $invoiceFiles = [$record->invoice_file];
            }
        } elseif (is_array($record->invoice_file)) {
            $invoiceFiles = $record->invoice_file;
        } else {
            $invoiceFiles = [$record->invoice_file];
        }
    }

    // Get company and creator details
    $companyDetail = $record->lead->companyDetail ?? null;
    $creator = $record->creator ?? null;
    $completedBy = $record->completedBy ?? null;
@endphp

<style>
    .hc-container {
        padding: 1.5rem;
        background-color: white;
        border-radius: 0.5rem;
    }

    .hc-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .hc-grid-2 {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .hc-field {
        margin-bottom: 0.5rem;
    }

    .hc-label {
        font-weight: 600;
        color: #374151;
    }

    .hc-value {
        color: #111827;
    }

    .hc-section {
        margin-bottom: 1.5rem;
    }

    .hc-file-section {
        margin-bottom: 1rem;
    }

    .hc-file-label {
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
        display: block;
    }

    .hc-file-list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .hc-file-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem;
        border-radius: 0.25rem;
    }

    .hc-file-name {
        flex: 1;
        font-size: 0.875rem;
        color: #111827;
    }

    .hc-file-actions {
        display: flex;
        gap: 0.5rem;
    }

    .hc-btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        text-align: center;
        color: white;
        text-decoration: none;
        border-radius: 0.25rem;
        border: none;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .hc-btn-view {
        background-color: #2563eb;
    }

    .hc-btn-view:hover {
        background-color: #1d4ed8;
    }

    .hc-btn-download {
        background-color: #16a34a;
    }

    .hc-btn-download:hover {
        background-color: #15803d;
    }

    .hc-status-completed {
        color: #059669;
    }

    .hc-status-rejected {
        color: #dc2626;
    }

    .hc-status-draft {
        color: #d97706;
    }

    .hc-status-new {
        color: #4f46e5;
    }

    .hc-no-files {
        color: #6b7280;
        font-style: italic;
        font-size: 0.875rem;
    }

    .hc-pi-list {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .hc-pi-item {
        font-size: 0.875rem;
    }

    .hc-pi-link {
        color: #2563EB;
        text-decoration: none;
        font-weight: 500;
    }

    .hc-pi-link:hover {
        text-decoration: underline;
    }

    .hc-remark {
        color: #374151;
        padding: 1rem;
        background: #f9fafb;
        border-radius: 0.25rem;
        border: 1px solid #d1d5db;
        white-space: pre-wrap;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .hc-grid-3 {
            grid-template-columns: repeat(2, 1fr);
        }

        .hc-grid-2 {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .hc-grid-3 {
            grid-template-columns: 1fr;
        }

        .hc-container {
            padding: 1rem;
        }
    }
</style>

<div class="hc-container">
    <!-- Basic Information - 3 columns -->
    <div class="hc-grid-3">
        <div>
            <p class="hc-field">
                <span class="hc-label">Company Name:</span><br>
                <span class="hc-value">{{ $companyDetail->company_name ?? 'N/A' }}</span>
            </p>
        </div>
        <div>
            <p class="hc-field">
                <span class="hc-label">Created By:</span><br>
                <span class="hc-value">{{ $creator->name ?? 'Unknown' }}</span>
            </p>
        </div>
        <div>
            <p class="hc-field">
                <span class="hc-label">Status:</span><br>
                @if($record->status == 'Completed')
                    <span class="hc-status-completed">{{ $record->status }}</span>
                @elseif($record->status == 'Rejected')
                    <span class="hc-status-rejected">{{ $record->status }}</span>
                @elseif($record->status == 'Draft')
                    <span class="hc-status-draft">{{ $record->status }}</span>
                @elseif($record->status == 'New')
                    <span class="hc-status-new">{{ $record->status }}</span>
                @else
                    <span class="hc-value">{{ $record->status ?? '-' }}</span>
                @endif
            </p>
        </div>
    </div>

    <div class="hc-grid-3">
        <div>
            <p class="hc-field">
                <span class="hc-label">Date Submitted:</span><br>
                <span class="hc-value">{{ $record->submitted_at ? $record->submitted_at->format('d M Y') : 'N/A' }}</span>
            </p>
        </div>
        @if($record->status === 'Completed' && $completedBy)
        <div>
            <p class="hc-field">
                <span class="hc-label">Completed By:</span><br>
                <span class="hc-value">{{ $completedBy->name }}</span>
            </p>
        </div>
        <div>
            <p class="hc-field">
                <span class="hc-label">Completed At:</span><br>
                <span class="hc-value">{{ $record->completed_at ? $record->completed_at->format('d M Y') : 'N/A' }}</span>
            </p>
        </div>
        @endif
    </div>

    <!-- Product PI & HRDF PI - 2 columns -->
    <div class="hc-grid-2">
        <div>
            <p class="hc-field">
                <span class="hc-label">Product PI:</span><br>
                @if(count($productPIs) > 0)
                    <div class="hc-pi-list">
                        @foreach($productPIs as $pi)
                            <div class="hc-pi-item">
                                <a href="{{ url('proforma-invoice-v2/' . $pi->id) }}" target="_blank" class="hc-pi-link">
                                    {{ $pi->pi_reference_no }}
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <span class="hc-no-files">No Product PI selected</span>
                @endif
            </p>
        </div>
        <div>
            <p class="hc-field">
                <span class="hc-label">HRDF PI:</span><br>
                @if(count($hrdfPIs) > 0)
                    <div class="hc-pi-list">
                        @foreach($hrdfPIs as $pi)
                            <div class="hc-pi-item">
                                <a href="{{ url('proforma-invoice-v2/' . $pi->id) }}" target="_blank" class="hc-pi-link">
                                    {{ $pi->pi_reference_no }}
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <span class="hc-no-files">No HRDF PI selected</span>
                @endif
            </p>
        </div>
    </div>

    <div class="hc-grid-3">
        <!-- Payment Slip -->
        <div class="hc-file-section">
            <span class="hc-file-label">Payment Slip:</span>
            @if(is_array($paymentSlipFiles) && count($paymentSlipFiles) > 0)
                <div class="hc-file-list">
                    @foreach($paymentSlipFiles as $file)
                        <div class="hc-file-item">
                            <div class="hc-file-actions">
                                <a href="{{ Storage::url($file) }}" target="_blank" class="hc-btn hc-btn-view">View</a>
                                <a href="{{ Storage::url($file) }}" download class="hc-btn hc-btn-download">Download</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="hc-no-files">No payment slip files uploaded</div>
            @endif
        </div>

        <!-- Confirmation Order -->
        <div class="hc-file-section">
            <span class="hc-file-label">Confirmation Order:</span>
            @if(is_array($confirmationOrderFiles) && count($confirmationOrderFiles) > 0)
                <div class="hc-file-list">
                    @foreach($confirmationOrderFiles as $file)
                        <div class="hc-file-item">
                            <div class="hc-file-actions">
                                <a href="{{ Storage::url($file) }}" target="_blank" class="hc-btn hc-btn-view">View</a>
                                <a href="{{ Storage::url($file) }}" download class="hc-btn hc-btn-download">Download</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="hc-no-files">No confirmation order files uploaded</div>
            @endif
        </div>

        <!-- Invoice (for completed handovers only) -->
        <div class="hc-file-section">
            <span class="hc-file-label">Invoice:</span>
            @if($record->status === 'Completed')
                @if(is_array($invoiceFiles) && count($invoiceFiles) > 0)
                    <div class="hc-file-list">
                        @foreach($invoiceFiles as $file)
                            @if($file)
                            <div class="hc-file-item">
                                <div class="hc-file-actions">
                                    <a href="{{ Storage::url($file) }}" target="_blank" class="hc-btn hc-btn-view">View</a>
                                    <a href="{{ Storage::url($file) }}" download class="hc-btn hc-btn-download">Download</a>
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="hc-no-files">No invoice files uploaded</div>
                @endif
            @else
                <div class="hc-no-files">Available when completed</div>
            @endif
        </div>
    </div>

    <!-- Salesperson Remark -->
    <div class="hc-section">
        <span class="hc-file-label">Salesperson Remark:</span>
        @if($record->salesperson_remark)
            <div class="hc-remark">{{ $record->salesperson_remark }}</div>
        @else
            <div class="hc-no-files">No remark provided</div>
        @endif
    </div>
</div>
