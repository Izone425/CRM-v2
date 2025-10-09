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
        padding: 1rem;
        background-color: #ffffff;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    }

    .hc-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    @media (min-width: 768px) {
        .hc-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    .hc-column {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .hc-column-right {
        display: flex;
        flex-direction: column;
    }

    .hc-info-item {
        margin-bottom: 0.5rem;
    }

    .hc-label {
        font-weight: 600;
        color: #1f2937;
    }

    .hc-value {
        margin-left: 0.5rem;
        color: #374151;
    }

    .hc-remark-container {
        margin-bottom: 1.5rem;
    }

    .hc-view-link {
        margin-left: 0.5rem;
        font-weight: 500;
        color: #2563eb;
        text-decoration: none;
        cursor: pointer;
    }

    .hc-view-link:hover {
        text-decoration: underline;
    }

    .hc-not-available {
        margin-left: 0.5rem;
        font-style: italic;
        color: #6b7280;
    }

    .hc-section {
        margin-bottom: 0.5rem;
    }

    .hc-section-title {
        font-size: 1.0rem;
        font-weight: 600;
        color: #1f2937;
        border-bottom: 2px solid #e5e7eb;
        padding-bottom: 0.5rem;
    }

    .hc-file-list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .hc-file-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 0;
    }

    .hc-file-label {
        font-weight: 500;
        color: #4b5563;
    }

    .hc-file-actions {
        display: flex;
        gap: 0.5rem;
    }

    .hc-btn {
        padding: 0.25rem 0.75rem;
        font-size: 0.75rem;
        color: white;
        text-decoration: none;
        border-radius: 0.25rem;
        border: none;
        cursor: pointer;
        transition: background-color 0.2s ease;
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
        font-weight: 600;
    }

    .hc-status-rejected {
        color: #dc2626;
        font-weight: 600;
    }

    .hc-status-draft {
        color: #d97706;
        font-weight: 600;
    }

    .hc-status-new {
        color: #4f46e5;
        font-weight: 600;
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

    /* Modal Styles */
    .hc-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 50;
        overflow: auto;
        padding: 1rem;
    }

    .hc-modal-content {
        position: relative;
        width: 100%;
        max-width: 42rem;
        padding: 1.5rem;
        margin: auto;
        background-color: white;
        border-radius: 0.5rem;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        margin-top: 5rem;
    }

    .hc-modal-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 1rem;
    }

    .hc-modal-title {
        font-size: 1.125rem;
        font-weight: 500;
        color: #111827;
    }

    .hc-modal-close {
        color: #9ca3af;
        background-color: transparent;
        border: none;
        border-radius: 0.375rem;
        padding: 0.375rem;
        margin-left: auto;
        display: inline-flex;
        align-items: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .hc-modal-close:hover {
        background-color: #f3f4f6;
        color: #111827;
    }

    .hc-modal-close svg {
        width: 1.25rem;
        height: 1.25rem;
    }

    .hc-modal-body {
        padding: 1rem;
        border-radius: 0.5rem;
        background-color: #f9fafb;
        margin-bottom: 1rem;
    }

    .hc-modal-text {
        color: #1f2937;
        white-space: pre-wrap;
        line-height: 1.6;
    }

    .hc-modal-footer {
        text-align: center;
    }

    .hc-modal-close-btn {
        padding: 0.5rem 1rem;
        color: white;
        background-color: #6b7280;
        border: none;
        border-radius: 0.25rem;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }

    .hc-modal-close-btn:hover {
        background-color: #4b5563;
    }

    /* Responsive adjustments */
    @media (max-width: 767px) {
        .hc-container {
            padding: 1rem;
        }

        .hc-modal-content {
            margin-top: 2rem;
            padding: 1rem;
        }

        .hc-file-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .hc-file-actions {
            width: 100%;
            justify-content: flex-start;
        }
    }

    /* Alpine.js transition styles */
    [x-cloak] { display: none !important; }
</style>

<div>
    <div class="hc-info-item">
        <span class="hc-label">Headcount Handover Details</span><br>
        <span class="hc-label">Company Name:</span>
        <span class="hc-value">{{ $companyDetail->company_name ?? 'N/A' }}</span>
    </div>
    <div class="hc-container" style="border: 0.1rem solid">
        <div class="hc-grid">
            <!-- Left Column -->
            <div class="hc-column">
                <!-- Basic Information -->
                <div class="hc-info-item">
                    <span class="hc-label">Created By:</span>
                    <span class="hc-value">{{ $creator->name ?? 'Unknown' }}</span>
                </div>

                <div class="hc-info-item">
                    <span class="hc-label">Created At:</span>
                    <span class="hc-value">{{ $record->submitted_at ? $record->submitted_at->format('d M Y') : 'N/A' }}</span>
                </div>

                <hr class="my-6 border-t border-gray-300">

                @if($record->status === 'Completed' && $completedBy)
                <div class="hc-info-item">
                    <span class="hc-label">Completed By:</span>
                    <span class="hc-value">{{ $completedBy->name }}</span>
                </div>

                <div class="hc-info-item">
                    <span class="hc-label">Completed At:</span>
                    <span class="hc-value">{{ $record->completed_at ? $record->completed_at->format('d M Y') : 'N/A' }}</span>
                </div>
                @endif

                <hr class="my-6 border-t border-gray-300">

                <!-- SalesPerson Remark with Modal -->
                <div class="hc-remark-container" x-data="{ remarkOpen: false }">
                    <span class="hc-label">SalesPerson Remark:</span>
                    @if($record->salesperson_remark)
                        <a href="#"
                        @click.prevent="remarkOpen = true"
                        class="hc-view-link">
                            View
                        </a>
                    @else
                        <span class="hc-not-available">Not Available</span>
                    @endif

                    <!-- SalesPerson Remark Modal -->
                    @if($record->salesperson_remark)
                    <div x-show="remarkOpen"
                        x-cloak
                        x-transition
                        @click.outside="remarkOpen = false"
                        class="hc-modal">
                        <div class="hc-modal-content" @click.away="remarkOpen = false">
                            <div class="hc-modal-header">
                                <h3 class="hc-modal-title">SalesPerson Remark</h3>
                                <button type="button" @click="remarkOpen = false" class="hc-modal-close">
                                    <svg fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="hc-modal-body">
                                <div class="hc-modal-text">{{ $record->salesperson_remark }}</div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Right Column -->
            <div class="hc-column-right">
                <!-- Product PI Section -->
                <div class="hc-section">
                    <div class="hc-info-item">
                        <span class="hc-label">Product PI:</span>
                        @if(count($productPIs) > 0)
                            <div class="hc-pi-list" style="display: inline; margin-left: 0.5rem;">
                                @foreach($productPIs as $index => $pi)
                                    <span style="display: inline;">
                                        @if($index > 0), @endif
                                        <a href="{{ url('proforma-invoice-v2/' . $pi->id) }}" target="_blank" class="hc-pi-link">
                                            {{ $pi->pi_reference_no }}
                                        </a>
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <span class="hc-not-available">No Product PI selected</span>
                        @endif
                    </div>
                </div>

                <!-- HRDF PI Section -->
                <div class="hc-section">
                    <div class="hc-info-item">
                        <span class="hc-label">HRDF PI:</span>
                        @if(count($hrdfPIs) > 0)
                            <div class="hc-pi-list" style="display: inline; margin-left: 0.5rem;">
                                @foreach($hrdfPIs as $index => $pi)
                                    <span class="hc-pi-item" style="display: inline;">
                                        @if($index > 0), @endif
                                        <a href="{{ url('proforma-invoice-v2/' . $pi->id) }}" target="_blank" class="hc-pi-link">
                                            {{ $pi->pi_reference_no }}
                                        </a>
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <span class="hc-not-available">No HRDF PI selected</span>
                        @endif
                    </div>
                </div>

                <!-- Payment Slip Section -->
                <div class="hc-section">
                    <h3 class="hc-section-title">Payment Slip</h3>
                    <div class="hc-file-list">
                        @if(is_array($paymentSlipFiles) && count($paymentSlipFiles) > 0)
                            @foreach($paymentSlipFiles as $index => $file)
                                <div class="hc-file-item">
                                    <span class="hc-file-label">File {{ $index + 1 }}:</span>
                                    <div class="hc-file-actions">
                                        <a href="{{ Storage::url($file) }}" target="_blank" class="hc-btn hc-btn-view">
                                            View
                                        </a>
                                        <a href="{{ Storage::url($file) }}" download class="hc-btn hc-btn-download">
                                            Download
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="hc-file-item">
                                <span class="hc-file-label">File 1:</span>
                                <span class="hc-not-available">Not Available</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Confirmation Order Section -->
                <div class="hc-section">
                    <h3 class="hc-section-title">Confirmation Order</h3>
                    <div class="hc-file-list">
                        @if(is_array($confirmationOrderFiles) && count($confirmationOrderFiles) > 0)
                            @foreach($confirmationOrderFiles as $index => $file)
                                <div class="hc-file-item">
                                    <span class="hc-file-label">File {{ $index + 1 }}:</span>
                                    <div class="hc-file-actions">
                                        <a href="{{ Storage::url($file) }}" target="_blank" class="hc-btn hc-btn-view">
                                            View
                                        </a>
                                        <a href="{{ Storage::url($file) }}" download class="hc-btn hc-btn-download">
                                            Download
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="hc-file-item">
                                <span class="hc-file-label">File 1:</span>
                                <span class="hc-not-available">Not Available</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Invoice Section -->
                <div class="hc-section">
                    <h3 class="hc-section-title">Invoice</h3>
                    <div class="hc-file-list">
                        @if($record->status === 'Completed')
                            @if(is_array($invoiceFiles) && count($invoiceFiles) > 0)
                                @foreach($invoiceFiles as $index => $file)
                                    @if($file)
                                    <div class="hc-file-item">
                                        <span class="hc-file-label">File {{ $index + 1 }}:</span>
                                        <div class="hc-file-actions">
                                            <a href="{{ Storage::url($file) }}" target="_blank" class="hc-btn hc-btn-view">
                                                View
                                            </a>
                                            <a href="{{ Storage::url($file) }}" download class="hc-btn hc-btn-download">
                                                Download
                                            </a>
                                        </div>
                                    </div>
                                    @endif
                                @endforeach
                            @else
                                <div class="hc-file-item">
                                    <span class="hc-file-label">File 1:</span>
                                    <span class="hc-not-available">Not Available</span>
                                </div>
                            @endif
                        @else
                            <div class="hc-file-item">
                                <span class="hc-file-label">File 1:</span>
                                <span class="hc-not-available">Available when completed</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
