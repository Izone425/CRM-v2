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
    $completedBy = $record->completedBy ?? null;
    $rejectedBy = $record->rejectedBy ?? null;
@endphp

<style>
    .detail-container {
        background: #f8fafc;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .detail-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px 24px;
        text-align: center;
    }

    .detail-header h2 {
        font-size: 24px;
        font-weight: 700;
        margin: 0;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .detail-header .subtitle {
        font-size: 14px;
        opacity: 0.9;
        margin-top: 4px;
    }

    .detail-body {
        padding: 0;
        background: white;
    }

    .section {
        border-bottom: 1px solid #e5e7eb;
        padding: 24px;
    }

    .section:last-child {
        border-bottom: none;
    }

    .section-title {
        font-size: 18px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .section-icon {
        width: 20px;
        height: 20px;
        color: #6b7280;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 16px;
    }

    .info-item {
        background: #f9fafb;
        border-radius: 8px;
        padding: 16px;
        border-left: 4px solid #3b82f6;
    }

    .info-label {
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .info-value {
        font-size: 14px;
        color: #111827;
        font-weight: 500;
        word-break: break-word;
    }

    .file-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .file-item {
        display: flex;
        align-items: center;
        gap: 12px;
        background: #f9fafb;
        border-radius: 8px;
        padding: 12px;
        border: 1px solid #e5e7eb;
    }

    .file-icon {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 12px;
        font-weight: 600;
    }

    .file-icon.jd14 {
        background: #10b981;
    }

    .file-icon.invoice {
        background: #f59e0b;
    }

    .file-icon.grant {
        background: #8b5cf6;
    }

    .file-info {
        flex: 1;
    }

    .file-name {
        font-size: 14px;
        font-weight: 500;
        color: #111827;
        margin-bottom: 2px;
    }

    .file-size {
        font-size: 12px;
        color: #6b7280;
    }

    .file-actions {
        display: flex;
        gap: 8px;
    }

    .file-action-btn {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s;
    }

    .btn-view {
        background: #3b82f6;
        color: white;
    }

    .btn-view:hover {
        background: #2563eb;
        color: white;
    }

    .btn-download {
        background: #10b981;
        color: white;
    }

    .btn-download:hover {
        background: #059669;
        color: white;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-draft {
        background: #fef3c7;
        color: #92400e;
    }

    .status-new {
        background: #dbeafe;
        color: #1e40af;
    }

    .status-completed {
        background: #d1fae5;
        color: #065f46;
    }

    .status-rejected {
        background: #fee2e2;
        color: #991b1b;
    }

    .no-files {
        text-align: center;
        padding: 24px;
        color: #6b7280;
        font-style: italic;
    }

    .remark-box {
        background: #fffbeb;
        border: 1px solid #fbbf24;
        border-radius: 8px;
        padding: 16px;
        margin-top: 8px;
    }

    .remark-text {
        color: #92400e;
        font-size: 14px;
        line-height: 1.5;
        margin: 0;
        white-space: pre-wrap;
    }

    .empty-state {
        text-align: center;
        color: #6b7280;
        font-style: italic;
        padding: 12px;
        background: #f9fafb;
        border-radius: 6px;
        border: 1px dashed #d1d5db;
    }
</style>

<div class="detail-container">
    <!-- Header -->
    <div class="detail-header">
        <h2>{{ $handoverId }}</h2>
        <div class="subtitle">HRDF Handover Details</div>
    </div>

    <div class="detail-body">
        <!-- Basic Information -->
        <div class="section">
            <div class="section-title">
                <svg class="section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Basic Information
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">HRDF ID</div>
                    <div class="info-value">{{ $handoverId }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">HRDF Grant ID</div>
                    <div class="info-value">{{ $record->hrdf_grant_id ?? 'N/A' }}</div>
                </div>
                @if($record->hrdf_claim_id)
                <div class="info-item">
                    <div class="info-label">HRDF Claim ID</div>
                    <div class="info-value">{{ $record->hrdf_claim_id }}</div>
                </div>
                @endif
                <div class="info-item">
                    <div class="info-label">Company Name</div>
                    <div class="info-value">{{ $companyDetail->company_name ?? 'N/A' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Date Submitted</div>
                    <div class="info-value">{{ $record->submitted_at ? $record->submitted_at->format('d M Y, H:i A') : 'N/A' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div class="info-value">
                        <span class="status-badge status-{{ strtolower($record->status) }}">
                            {{ $record->status }}
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Created By</div>
                    <div class="info-value">{{ $creator->name ?? 'Unknown' }}</div>
                </div>
                @if($record->status === 'Completed' && $completedBy)
                <div class="info-item">
                    <div class="info-label">Completed By</div>
                    <div class="info-value">{{ $completedBy->name }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Completed At</div>
                    <div class="info-value">{{ $record->completed_at ? $record->completed_at->format('d M Y, H:i A') : 'N/A' }}</div>
                </div>
                @endif
                @if($record->status === 'Rejected' && $rejectedBy)
                <div class="info-item">
                    <div class="info-label">Rejected By</div>
                    <div class="info-value">{{ $rejectedBy->name }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Rejected At</div>
                    <div class="info-value">{{ $record->rejected_at ? $record->rejected_at->format('d M Y, H:i A') : 'N/A' }}</div>
                </div>
                @endif
            </div>
        </div>

        <!-- File Uploads -->
        <div class="section">
            <div class="section-title">
                <svg class="section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Uploaded Documents
            </div>

            <!-- JD14 Form Files -->
            <div class="info-item">
                <div class="info-label">JD14 Form Files</div>
                <div class="info-value">
                    @if(is_array($jd14Files) && count($jd14Files) > 0)
                        <div class="file-list">
                            @foreach($jd14Files as $file)
                                <div class="file-item">
                                    <div class="file-icon jd14">JD14</div>
                                    <div class="file-info">
                                        <div class="file-name">{{ basename($file) }}</div>
                                        <div class="file-size">JD14 Form Document</div>
                                    </div>
                                    <div class="file-actions">
                                        <a href="{{ Storage::url($file) }}" target="_blank" class="file-action-btn btn-view">View</a>
                                        <a href="{{ Storage::url($file) }}" download class="file-action-btn btn-download">Download</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="no-files">No JD14 form files uploaded</div>
                    @endif
                </div>
            </div>

            <!-- AutoCount Invoice Files -->
            <div class="info-item">
                <div class="info-label">AutoCount Invoice Files</div>
                <div class="info-value">
                    @if(is_array($invoiceFiles) && count($invoiceFiles) > 0)
                        <div class="file-list">
                            @foreach($invoiceFiles as $file)
                                <div class="file-item">
                                    <div class="file-icon invoice">INV</div>
                                    <div class="file-info">
                                        <div class="file-name">{{ basename($file) }}</div>
                                        <div class="file-size">AutoCount Invoice Document</div>
                                    </div>
                                    <div class="file-actions">
                                        <a href="{{ Storage::url($file) }}" target="_blank" class="file-action-btn btn-view">View</a>
                                        <a href="{{ Storage::url($file) }}" download class="file-action-btn btn-download">Download</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="no-files">No AutoCount invoice files uploaded</div>
                    @endif
                </div>
            </div>

            <!-- HRDF Grant Approval Files -->
            <div class="info-item">
                <div class="info-label">HRDF Grant Approval Files</div>
                <div class="info-value">
                    @if(is_array($grantFiles) && count($grantFiles) > 0)
                        <div class="file-list">
                            @foreach($grantFiles as $file)
                                <div class="file-item">
                                    <div class="file-icon grant">GRANT</div>
                                    <div class="file-info">
                                        <div class="file-name">{{ basename($file) }}</div>
                                        <div class="file-size">HRDF Grant Approval Document</div>
                                    </div>
                                    <div class="file-actions">
                                        <a href="{{ Storage::url($file) }}" target="_blank" class="file-action-btn btn-view">View</a>
                                        <a href="{{ Storage::url($file) }}" download class="file-action-btn btn-download">Download</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="no-files">No HRDF grant approval files uploaded</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Remarks -->
        <div class="section">
            <div class="section-title">
                <svg class="section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                </svg>
                Remarks
            </div>

            @if($record->salesperson_remark)
                <div class="remark-box">
                    <div class="info-label">Salesperson Remark</div>
                    <p class="remark-text">{{ $record->salesperson_remark }}</p>
                </div>
            @else
                <div class="empty-state">No remarks provided</div>
            @endif

            @if($record->status === 'Rejected' && $record->reject_reason)
                <div class="remark-box" style="background: #fef2f2; border-color: #f87171;">
                    <div class="info-label" style="color: #991b1b;">Rejection Reason</div>
                    <p class="remark-text" style="color: #991b1b;">{{ $record->reject_reason }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
