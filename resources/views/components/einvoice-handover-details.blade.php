@php
    $record = $record ?? null;

    if (!$record) {
        echo 'No record found.';
        return;
    }

    // Get lead and reseller details
    $lead = $record->lead ?? null;
    $reseller = $lead?->resellerV2 ?? null;
@endphp

<style>
    .einvoice-container {
        padding: 1.5rem;
        border-radius: 0.5rem;
    }

    .einvoice-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }

    @media (max-width: 768px) {
        .einvoice-grid {
            grid-template-columns: 1fr;
        }
    }

    .einvoice-info-item {
        margin-bottom: 0.5rem;
    }

    .einvoice-label {
        font-weight: 600;
        color: #1f2937;
        margin-right: 0.5rem;
    }

    .einvoice-value {
        color: #374151;
    }

    .einvoice-export-container {
        text-align: center;
        margin-top: 1.5rem;
        display: flex;
        justify-content: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .einvoice-export-btn, .sw-export-btn {
        display: inline-flex;
        align-items: center;
        color: #16a34a;
        background-color: white;
        text-decoration: none;
        font-weight: 500;
        padding: 0.75rem 1.5rem;
        border: 2px solid #16a34a;
        border-radius: 0.375rem;
        transition: all 0.2s;
        min-width: 200px;
        justify-content: center;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .einvoice-export-btn:hover, .sw-export-btn:hover {
        background-color: #16a34a;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
        text-decoration: none;
    }

    .einvoice-export-icon, .sw-export-icon {
        width: 1.25rem;
        height: 1.25rem;
        margin-right: 0.5rem;
        flex-shrink: 0;
    }

    .einvoice-section-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #1f2937;
        border-bottom: 2px solid #e5e7eb;
        padding-bottom: 0.5rem;
        margin-bottom: 1rem;
    }

    .einvoice-status {
        padding: 0.25rem 0.75rem;
        border-radius: 0.75rem;
        font-size: 0.875rem;
        font-weight: 500;
        text-transform: uppercase;
    }

    .einvoice-status-new {
        background-color: #dbeafe;
        color: #1e40af;
    }

    .einvoice-status-completed {
        background-color: #d1fae5;
        color: #065f46;
    }

    .einvoice-status-rejected {
        background-color: #fee2e2;
        color: #dc2626;
    }
</style>

<div class="einvoice-container">
    <!-- Company Name - Full Width -->
    <div class="einvoice-info-item" style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
        <span class="einvoice-label">Company Name</span><br>
        <span class="einvoice-value" style="font-size: 1.1rem;">{{ $reseller?->company_name ?? 'N/A' }}</span>
    </div>

    <!-- Two Column Layout -->
    <div class="einvoice-grid">
        <!-- Left Column -->
        <div>
            <div class="einvoice-info-item">
                <span class="einvoice-label">Created</span><br>
                <span class="einvoice-value">{{ $reseller?->created_at ? $reseller->created_at->format('d M Y') : 'N/A' }}</span>
            </div>

            <div class="einvoice-info-item">
                <span class="einvoice-label">PIC Name</span><br>
                <span class="einvoice-value">{{ $reseller?->contact_person ?? 'N/A' }}</span>
            </div>

            <div class="einvoice-info-item">
                <span class="einvoice-label">PIC No Hp</span><br>
                <span class="einvoice-value">{{ $reseller?->phone ?? 'N/A' }}</span>
            </div>
        </div>

        <!-- Right Column -->
        <div>
            <div class="einvoice-info-item">
                <span class="einvoice-label">Bind Reseller ID</span><br>
                <span class="einvoice-value">{{ $reseller?->reseller_id ?? 'N/A' }}</span>
            </div>

            <div class="einvoice-info-item">
                <span class="einvoice-label">Login Email</span><br>
                <span class="einvoice-value">{{ $reseller?->email ?? 'N/A' }}</span>
            </div>

            <div class="einvoice-info-item">
                <span class="einvoice-label">Login Password</span><br>
                <span class="einvoice-value">{{ $reseller?->plain_password ?? 'N/A' }}</span>
            </div>
        </div>
    </div>

    <hr class="my-4 border-gray-300">

    <!-- Bottom Two Column Layout -->
    <div class="einvoice-grid">
        <!-- Left Column -->
        <div>
            <div class="einvoice-info-item">
                <span class="einvoice-label">Business Registration Number</span><br>
                <span class="einvoice-value">{{ $reseller?->ssm_number ?? 'N/A' }}</span>
            </div>

            <div class="einvoice-info-item">
                <span class="einvoice-label">Tax Identification Number</span><br>
                <span class="einvoice-value">{{ $reseller?->tax_identification_number ?? 'N/A' }}</span>
            </div>
        </div>

        <!-- Right Column -->
        <div>
            <div class="einvoice-info-item">
                <span class="einvoice-label">SST Category</span><br>
                <span class="einvoice-value">{{ $reseller?->sst_category ?? 'N/A' }}</span>
            </div>

            <div class="einvoice-info-item">
                <span class="einvoice-label">Commission Scheme (%)</span><br>
                <span class="einvoice-value">{{ $reseller?->commission_rate ? $reseller->commission_rate . '%' : 'N/A' }}</span>
            </div>
        </div>
    </div>

    <hr class="my-4 border-gray-300">

    <!-- Export Buttons -->
    <div class="einvoice-export-container">
        <a href="{{ route('software-handover.export-customer', ['lead' => \App\Classes\Encryptor::encrypt($record->lead_id)]) }}"
            target="_blank"
            class="sw-export-btn">
            <!-- Download Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" class="sw-export-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export AutoCount Debtor
        </a>

        <a href="{{ route('einvoice.export', [
                'lead' => \App\Classes\Encryptor::encrypt($record->lead_id),
                'subsidiaryId' => $record->subsidiary_id
            ]) }}"
           target="_blank"
           class="einvoice-export-btn">
            <svg class="einvoice-export-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Export AutoCount E-Invoice
        </a>
    </div>
</div>
