@php
    $record = $record ?? null;

    if (!$record) {
        echo 'No record found.';
        return;
    }

    // Get lead and company details
    $lead = $record->lead ?? null;
    $companyDetail = $lead->companyDetail ?? null;
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
    <hr class="my-4 border-gray-300">

    <div class="einvoice-grid">
        <!-- Column 1 -->
        <div class="einvoice-info-item">
            <span class="einvoice-label">E-Invoice ID:</span>
            <span class="einvoice-value">{{ $record->project_code }}</span>
        </div>

        <!-- Column 2 -->
        <div class="einvoice-info-item">
            <span class="einvoice-label">Status:</span>
            <span class="einvoice-status
                @if($record->status === 'New') einvoice-status-new
                @elseif($record->status === 'Completed') einvoice-status-completed
                @elseif($record->status === 'Rejected') einvoice-status-rejected
                @endif">
                {{ $record->status }}
            </span>
        </div>
    </div>

    <div class="einvoice-grid">
        <!-- Column 1 -->
        <div class="einvoice-info-item">
            <span class="einvoice-label">SalesPerson:</span>
            <span class="einvoice-value">{{ $record->salesperson }}</span>
        </div>

        <!-- Column 2 -->
        <div class="einvoice-info-item">
            <span class="einvoice-label">Duration:</span>
            <span class="einvoice-value">
                @if($record->created_at && $record->completed_at && $record->status === 'Completed')
                    {{ $record->created_at->diffForHumans($record->completed_at, true) }}
                @else
                    {{ $record->created_at ? $record->created_at->diffForHumans() : 'N/A' }}
                @endif
            </span>
        </div>
    </div>

    <hr class="my-2 border-gray-300">

    <div class="einvoice-grid">
        <!-- Column 1 -->
        <div class="einvoice-info-item">
            <span class="einvoice-label">Created By:</span>
            <span class="einvoice-value">{{ $record->createdBy?->name ?? 'N/A' }}</span>
        </div>

        <!-- Column 2 -->
        <div class="einvoice-info-item">
            <span class="einvoice-label">Completed By:</span>
            <span class="einvoice-value">
                @if($record->status === 'Completed')
                    {{ $record->completedBy?->name ?? 'N/A' }}
                @else
                    -
                @endif
            </span>
        </div>
    </div>

    <div class="einvoice-grid">
        <!-- Column 1 -->
        <div class="einvoice-info-item">
            <span class="einvoice-label">Created At:</span>
            <span class="einvoice-value">{{ $record->created_at ? $record->created_at->format('d F Y, H:i') : 'N/A' }}</span>
        </div>

        <!-- Column 2 -->
        <div class="einvoice-info-item">
            <span class="einvoice-label">Completed At:</span>
            <span class="einvoice-value">
                @if($record->status === 'Completed')
                    {{ $record->completed_at ? $record->completed_at->format('d F Y, H:i') : 'N/A' }}
                @else
                    -
                @endif
            </span>
        </div>
    </div>

    <hr class="my-2 border-gray-300">

    <div class="einvoice-info-item">
        <span class="einvoice-label">Company Name:</span>
        <span class="einvoice-value">{{ $record->company_name }}</span>
    </div>

    <div class="einvoice-info-item">
        <span class="einvoice-label">Company Type:</span>
        <span class="einvoice-value">{{ ucfirst($record->company_type) }}</span>
    </div>

    <div class="einvoice-info-item">
        <span class="einvoice-label">Customer Type:</span>
        <span class="einvoice-value" style="{{ $record->customer_type === 'Existing Customer' ? 'color: #dc2626; font-weight: 700;' : '' }}">
            {{ $record->customer_type ?? 'N/A' }}
        </span>
    </div>

    @if($record->tin_number)
    <div class="einvoice-info-item">
        <span class="einvoice-label">TIN Number:</span>
        <span class="einvoice-value" style="color: #dc2626; font-weight: 700;">
            {{ $record->tin_number }}
        </span>
    </div>
    @endif

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
