{{-- filepath: /var/www/html/timeteccrm/resources/views/components/hrdf-handover.blade.php --}}
@php
    $record = $extraAttributes['record'] ?? null;

    if (!$record) {
        return;
    }

    // Format the handover ID
    $handoverId = 'HRDF_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);

    // Get files
    $jd14Files = [];
    $invoiceFiles = [];
    $grantFiles = [];

    if ($record->jd14_form_files) {
        $jd14Files = is_string($record->jd14_form_files)
            ? json_decode($record->jd14_form_files, true)
            : $record->jd14_form_files;
    }

    if ($record->autocount_invoice_file) {
        $invoiceFiles = is_string($record->autocount_invoice_file)
            ? json_decode($record->autocount_invoice_file, true)
            : $record->autocount_invoice_file;
    }

    if ($record->hrdf_grant_approval_file) {
        $grantFiles = is_string($record->hrdf_grant_approval_file)
            ? json_decode($record->hrdf_grant_approval_file, true)
            : $record->hrdf_grant_approval_file;
    }

    // Get company and creator details
    $companyDetail = $record->lead->companyDetail ?? null;
    $creator = $record->creator ?? null;

    // Combine invoice and grant files
    $combinedFiles = array_merge($invoiceFiles ?: [], $grantFiles ?: []);
@endphp

<style>
    .hrdf-container {
        padding: 1.5rem;
        background-color: white;
        border-radius: 0.5rem;
    }

    .hrdf-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .hrdf-grid-4 {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
    }

    .hrdf-field {
        margin-bottom: 0.5rem;
    }

    .hrdf-label {
        font-weight: 600;
        color: #374151;
    }

    .hrdf-value {
        color: #111827;
    }

    .hrdf-section-title {
        font-size: 1.125rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: #111827;
    }

    .hrdf-section {
        margin-bottom: 1.5rem;
    }

    .hrdf-file-card {
        padding: 1rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
    }

    .hrdf-file-title {
        font-weight: 500;
        margin-bottom: 0.75rem;
        color: #374151;
    }

    .hrdf-button-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .hrdf-btn {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        text-align: center;
        color: white;
        text-decoration: none;
        border-radius: 0.25rem;
        border: none;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .hrdf-btn-view {
        background-color: #2563eb;
    }

    .hrdf-btn-view:hover {
        background-color: #1d4ed8;
    }

    .hrdf-btn-download {
        background-color: #16a34a;
    }

    .hrdf-btn-download:hover {
        background-color: #15803d;
    }

    .hrdf-status-completed {
        color: #059669;
    }

    .hrdf-status-rejected {
        color: #dc2626;
    }

    .hrdf-status-draft {
        color: #d97706;
    }

    .hrdf-status-new {
        color: #4f46e5;
    }

    .hrdf-no-files {
        color: #6b7280;
    }

    .hrdf-remark {
        color: #374151;
    }

    /* Responsive adjustments */
    @media (max-width: 1024px) {
        .hrdf-grid-4 {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 768px) {
        .hrdf-grid-3 {
            grid-template-columns: repeat(2, 1fr);
        }

        .hrdf-grid-4 {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 640px) {
        .hrdf-grid-3 {
            grid-template-columns: 1fr;
        }

        .hrdf-grid-4 {
            grid-template-columns: 1fr;
        }

        .hrdf-container {
            padding: 1rem;
        }
    }
</style>

<div class="hrdf-container">
    <!-- Basic Information - 3 columns -->
    <div class="hrdf-grid-3">
        <div>
            <p class="hrdf-field">
                <span class="hrdf-label">Company Name:</span><br>
                <span class="hrdf-value">{{ $companyDetail->company_name ?? 'N/A' }}</span>
            </p>
        </div>
        <div>
            <p class="hrdf-field">
                <span class="hrdf-label">Created By:</span><br>
                <span class="hrdf-value">{{ $creator->name ?? 'Unknown' }}</span>
            </p>
        </div>
        <div>
            <p class="hrdf-field">
                <span class="hrdf-label">Date Submitted:</span><br>
                <span class="hrdf-value">{{ $record->submitted_at ? $record->submitted_at->format('d M Y') : 'N/A' }}</span>
            </p>
        </div>
    </div>

    <div class="hrdf-grid-4">
        <div>
            <p class="hrdf-field">
                <span class="hrdf-label">HRDF Grant ID:</span><br>
                <span class="hrdf-value">{{ $record->hrdf_grant_id ?? 'N/A' }}</span>
            </p>
        </div>
        <div>
            <p class="hrdf-field">
                <span class="hrdf-label">AutoCount Inv No:</span><br>
                <span class="hrdf-value">{{ $record->autocount_invoice_number ?? 'N/A' }}</span>
            </p>
        </div>
        <div>
            <p class="hrdf-field">
                <span class="hrdf-label">HRDF ID:</span><br>
                <span class="hrdf-value">{{ $handoverId }}</span>
            </p>
        </div>
        <div>
            <p class="hrdf-field">
                <span class="hrdf-label">Status:</span><br>
                @if($record->status == 'Completed')
                    <span class="hrdf-status-completed">{{ $record->status }}</span>
                @elseif($record->status == 'Rejected')
                    <span class="hrdf-status-rejected">{{ $record->status }}</span>
                @elseif($record->status == 'Draft')
                    <span class="hrdf-status-draft">{{ $record->status }}</span>
                @elseif($record->status == 'New')
                    <span class="hrdf-status-new">{{ $record->status }}</span>
                @else
                    <span class="hrdf-value">{{ $record->status ?? '-' }}</span>
                @endif
            </p>
        </div>
    </div>

    <!-- JD14 Form + 3 Days Attendance Logs - 4 columns -->
    <div class="hrdf-section">
        <h3 class="hrdf-section-title">JD 14 FORM + 3 DAYS ATTENDANCE LOGS</h3>
        @if(is_array($jd14Files) && count($jd14Files) > 0)
            <div class="hrdf-grid-4">
                @foreach($jd14Files as $index => $file)
                    <div class="hrdf-file-card">
                        <p class="hrdf-file-title">File {{ $index + 1 }}</p>
                        <div class="hrdf-button-group">
                            <a href="{{ Storage::url($file) }}" target="_blank" class="hrdf-btn hrdf-btn-view">
                                View
                            </a>
                            <a href="{{ Storage::url($file) }}" download class="hrdf-btn hrdf-btn-download">
                                Download
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="hrdf-no-files">No files available</p>
        @endif
    </div>

    <!-- AutoCount Invoice | HRDF Grant Approval Letter -->
    <div class="hrdf-section">
        <h3 class="hrdf-section-title">AUTOCOUNT INVOICE | HRDF GRANT APPROVAL LETTER</h3>
        @if(count($combinedFiles) > 0)
            <div class="hrdf-grid-4">
                @foreach($combinedFiles as $index => $file)
                    <div class="hrdf-file-card">
                        <p class="hrdf-file-title">File {{ $index + 1 }}</p>
                        <div class="hrdf-button-group">
                            <a href="{{ Storage::url($file) }}" target="_blank" class="hrdf-btn hrdf-btn-view">
                                View
                            </a>
                            <a href="{{ Storage::url($file) }}" download class="hrdf-btn hrdf-btn-download">
                                Download
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="hrdf-no-files">No files available</p>
        @endif
    </div>

    <!-- Salesperson Remark -->
    <div class="hrdf-section">
        <h3 class="hrdf-section-title">SALESPERSON REMARK</h3>
        @if($record->salesperson_remark)
            <p class="hrdf-remark">{{ $record->salesperson_remark }}</p>
        @else
            <p class="hrdf-no-files">No remark provided</p>
        @endif
    </div>
</div>
