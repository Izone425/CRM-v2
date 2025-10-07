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
@endphp

<style>
    .hrdf-container {
        padding: 1.5rem;
        background-color: #ffffff;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    }

    .hrdf-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    @media (min-width: 768px) {
        .hrdf-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    .hrdf-column {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .hrdf-column-right {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .hrdf-info-item {
        margin-bottom: 0.5rem;
    }

    .hrdf-label {
        font-weight: 600;
        color: #1f2937;
    }

    .hrdf-value {
        margin-left: 0.5rem;
        color: #374151;
    }

    .hrdf-remark-container {
        margin-bottom: 1.5rem;
    }

    .hrdf-view-link {
        margin-left: 0.5rem;
        font-weight: 500;
        color: #2563eb;
        text-decoration: none;
        cursor: pointer;
    }

    .hrdf-view-link:hover {
        text-decoration: underline;
    }

    .hrdf-not-available {
        margin-left: 0.5rem;
        font-style: italic;
        color: #6b7280;
    }

    .hrdf-section {
        margin-bottom: 0.5rem;
    }

    .hrdf-section-title {
        font-size: 1rem;
        font-weight: 600;
        color: #1f2937;
        border-bottom: 2px solid #e5e7eb;
        padding-bottom: 0.5rem;
    }

    .hrdf-file-list {
        display: flex;
        flex-direction: column;
    }

    .hrdf-file-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 0;
    }

    .hrdf-file-label {
        font-weight: 500;
        color: #4b5563;
    }

    .hrdf-file-actions {
        display: flex;
        gap: 0.5rem;
    }

    .hrdf-btn {
        padding: 0.25rem 0.75rem;
        font-size: 0.75rem;
        color: white;
        text-decoration: none;
        border-radius: 0.25rem;
        border: none;
        cursor: pointer;
        transition: background-color 0.2s ease;
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

    /* Modal Styles */
    .hrdf-modal {
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

    .hrdf-modal-content {
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

    .hrdf-modal-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 1rem;
    }

    .hrdf-modal-title {
        font-size: 1.125rem;
        font-weight: 500;
        color: #111827;
    }

    .hrdf-modal-close {
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

    .hrdf-modal-close:hover {
        background-color: #f3f4f6;
        color: #111827;
    }

    .hrdf-modal-close svg {
        width: 1.25rem;
        height: 1.25rem;
    }

    .hrdf-modal-body {
        padding: 1rem;
        border-radius: 0.5rem;
        background-color: #f9fafb;
        margin-bottom: 1rem;
    }

    .hrdf-modal-text {
        color: #1f2937;
        white-space: pre-wrap;
        line-height: 1.6;
    }

    .hrdf-modal-footer {
        text-align: center;
    }

    .hrdf-modal-close-btn {
        padding: 0.5rem 1rem;
        color: white;
        background-color: #6b7280;
        border: none;
        border-radius: 0.25rem;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }

    .hrdf-modal-close-btn:hover {
        background-color: #4b5563;
    }

    /* Responsive adjustments */
    @media (max-width: 767px) {
        .hrdf-container {
            padding: 1rem;
        }

        .hrdf-modal-content {
            margin-top: 2rem;
            padding: 1rem;
        }

        .hrdf-file-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .hrdf-file-actions {
            width: 100%;
            justify-content: flex-start;
        }
    }

    /* Alpine.js transition styles */
    [x-cloak] { display: none !important; }
</style>

<div class="hrdf-container">
    <div class="hrdf-grid">
        <!-- Left Column -->
        <div class="hrdf-column">
            <!-- Basic Information -->
            <div class="hrdf-info-item">
                <span class="hrdf-label">Company Name:</span>
                <span class="hrdf-value">{{ $companyDetail->company_name ?? 'N/A' }}</span>
            </div>

            <div class="hrdf-info-item">
                <span class="hrdf-label">HRDF ID:</span>
                <span class="hrdf-value">{{ $handoverId }}</span>
            </div>

            <div class="hrdf-info-item">
                <span class="hrdf-label">HRDF Grant ID:</span>
                <span class="hrdf-value">{{ $record->hrdf_grant_id ?? 'N/A' }}</span>
            </div>

            <div class="hrdf-info-item">
                <span class="hrdf-label">AutoCount Invoice No:</span>
                <span class="hrdf-value">{{ $record->autocount_invoice_number ?? 'N/A' }}</span>
            </div>

            <!-- SalesPerson Remark with Modal -->
            <div class="hrdf-remark-container" x-data="{ remarkOpen: false }">
                <span class="hrdf-label">SalesPerson Remark:</span>
                @if($record->salesperson_remark)
                    <a href="#"
                       @click.prevent="remarkOpen = true"
                       class="hrdf-view-link">
                        View
                    </a>
                @else
                    <span class="hrdf-not-available">Not Available</span>
                @endif

                <!-- SalesPerson Remark Modal -->
                @if($record->salesperson_remark)
                <div x-show="remarkOpen"
                     x-cloak
                     x-transition
                     @click.outside="remarkOpen = false"
                     class="hrdf-modal">
                    <div class="hrdf-modal-content" @click.away="remarkOpen = false">
                        <div class="hrdf-modal-header">
                            <h3 class="hrdf-modal-title">SalesPerson Remark</h3>
                            <button type="button" @click="remarkOpen = false" class="hrdf-modal-close">
                                <svg fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="hrdf-modal-body">
                            <div class="hrdf-modal-text">{{ $record->salesperson_remark }}</div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Right Column -->
        <div class="hrdf-column-right">
            <!-- JD 14 Form Section -->
            <div class="hrdf-section">
                <h3 class="hrdf-section-title">JD 14 Form + 3 Days Attendance Logs</h3>
                <div class="hrdf-file-list">
                    @for($i = 1; $i <= 4; $i++)
                        <div class="hrdf-file-item">
                            <span class="hrdf-file-label">File {{ $i }}:</span>
                            @if(isset($jd14Files[$i-1]))
                                <div class="hrdf-file-actions">
                                    <a href="{{ Storage::url($jd14Files[$i-1]) }}" target="_blank" class="hrdf-btn hrdf-btn-view">
                                        View
                                    </a>
                                    <a href="{{ Storage::url($jd14Files[$i-1]) }}" download class="hrdf-btn hrdf-btn-download">
                                        Download
                                    </a>
                                </div>
                            @else
                                <span class="hrdf-not-available">Not Available</span>
                            @endif
                        </div>
                    @endfor
                </div>
            </div>

            <!-- AutoCount Invoice Section -->
            <div class="hrdf-section">
                <h3 class="hrdf-section-title">AutoCount Invoice</h3>
                <div class="hrdf-file-list">
                    <div class="hrdf-file-item">
                        <span class="hrdf-file-label">File 1:</span>
                        @if(isset($invoiceFiles[0]))
                            <div class="hrdf-file-actions">
                                <a href="{{ Storage::url($invoiceFiles[0]) }}" target="_blank" class="hrdf-btn hrdf-btn-view">
                                    View
                                </a>
                                <a href="{{ Storage::url($invoiceFiles[0]) }}" download class="hrdf-btn hrdf-btn-download">
                                    Download
                                </a>
                            </div>
                        @else
                            <span class="hrdf-not-available">Not Available</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- HRDF Grant Approval Letter Section -->
            <div class="hrdf-section">
                <h3 class="hrdf-section-title">HRDF Grant Approval Letter</h3>
                <div class="hrdf-file-list">
                    <div class="hrdf-file-item">
                        <span class="hrdf-file-label">File 1:</span>
                        @if(isset($grantFiles[0]))
                            <div class="hrdf-file-actions">
                                <a href="{{ Storage::url($grantFiles[0]) }}" target="_blank" class="hrdf-btn hrdf-btn-view">
                                    View
                                </a>
                                <a href="{{ Storage::url($grantFiles[0]) }}" download class="hrdf-btn hrdf-btn-download">
                                    Download
                                </a>
                            </div>
                        @else
                            <span class="hrdf-not-available">Not Available</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
