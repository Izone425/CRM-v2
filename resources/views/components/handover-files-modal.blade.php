<!-- Files Modal -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="{{ asset('css/handover-files-modal.css') }}">

@if($showFilesModal && $selectedHandover)
    <div class="handover-modal-overlay" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="handover-modal-container">
            <!-- Background overlay -->
            <div class="handover-modal-background" wire:click="closeFilesModal" aria-hidden="true"></div>

            <!-- Modal panel -->
            <div class="handover-modal-panel">
                <!-- Header -->
                <div class="handover-modal-header">
                    <div class="handover-modal-header-content">
                        <div>
                            <h3 class="handover-modal-title">
                                {{ $selectedHandover->fb_id ?? '' }}
                            </h3>

                            <h3 class="handover-modal-title">{{ $selectedHandover->reseller_company_name ?? '' }}</h3>

                            <h3 class="handover-modal-title">{{ $selectedHandover->subscriber_name ?? '' }}</h3>
                        </div>
                        <button wire:click="closeFilesModal" class="handover-modal-close-btn">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="handover-modal-body">
                    <div class="handover-modal-grid">
                        <!-- Left Column: Categorized Uploaded Files -->
                        <div class="handover-modal-column">
                            <div class="handover-info-box">
                                <h4 class="handover-info-title">
                                    Reseller Remark:
                                    <span
                                        wire:click="$set('showRemarkModal', true)"
                                        style="color: #3b82f6; cursor: pointer; text-decoration: underline; margin-left: 0.25rem;"
                                        onmouseover="this.style.color='#2563eb'"
                                        onmouseout="this.style.color='#3b82f6'">
                                        View
                                    </span>
                                </h4>
                            </div>

                            <div class="handover-info-box">
                                <h4 class="handover-info-title">
                                    RFQ – Request For Quotation
                                </h4>
                                <div style="margin-top: 0.75rem; line-height: 1.8;">
                                    <p style="font-size: 0.875rem; color: #1f2937;">
                                        <span style="display: inline-block; width: 100px;">Attendance</span>: <span style="font-weight: 600;">{{ $selectedHandover->attendance_qty ?? 0 }}</span>
                                    </p>
                                    <p style="font-size: 0.875rem; color: #1f2937;">
                                        <span style="display: inline-block; width: 100px;">Leave</span>: <span style="font-weight: 600;">{{ $selectedHandover->leave_qty ?? 0 }}</span>
                                    </p>
                                    <p style="font-size: 0.875rem; color: #1f2937;">
                                        <span style="display: inline-block; width: 100px;">Claim</span>: <span style="font-weight: 600;">{{ $selectedHandover->claim_qty ?? 0 }}</span>
                                    </p>
                                    <p style="font-size: 0.875rem; color: #1f2937;">
                                        <span style="display: inline-block; width: 100px;">Payroll</span>: <span style="font-weight: 600;">{{ $selectedHandover->payroll_qty ?? 0 }}</span>
                                    </p>
                                </div>
                            </div>

                            <div class="handover-info-box">
                                <h4 class="handover-info-title">
                                    <i class="fas fa-file-invoice"></i>
                                    TimeTec Proforma Invoice
                                </h4>
                                @if(isset($selectedHandover->timetec_proforma_invoice) && $selectedHandover->timetec_proforma_invoice && isset($selectedHandover->invoice_url) && $selectedHandover->invoice_url)
                                    <a href="{{ $selectedHandover->invoice_url }}" target="_blank" class="handover-invoice-link">
                                        {{ $selectedHandover->timetec_proforma_invoice }}
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                @elseif(isset($selectedHandover->timetec_proforma_invoice) && $selectedHandover->timetec_proforma_invoice)
                                    <p class="handover-invoice-text">
                                        {{ $selectedHandover->timetec_proforma_invoice }}
                                    </p>
                                @else
                                    <p class="handover-info-na">N/A</p>
                                @endif
                                <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #e5e7eb;">
                                    <p style="font-size: 0.75rem; color: #6b7280; margin-bottom: 0.25rem;">Submission Date/Time</p>
                                    <p style="font-size: 0.875rem; color: #1f2937; font-weight: 500;">
                                        {{ $selectedHandover->ttpi_submitted_at ? $selectedHandover->ttpi_submitted_at->format('d M Y, h:i A') : 'N/A' }}
                                    </p>
                                </div>
                            </div>

                            <div class="handover-info-box">
                                <h4 class="handover-info-title">
                                    Admin Reseller Remark:
                                    <span
                                        wire:click="$set('showAdminRemarkModal', true)"
                                        style="color: #3b82f6; cursor: pointer; text-decoration: underline; margin-left: 0.25rem;"
                                        onmouseover="this.style.color='#2563eb'"
                                        onmouseout="this.style.color='#3b82f6'">
                                        View
                                    </span>
                                </h4>
                            </div>

                            <!-- Pending Confirmation Files -->
                            @if(isset($handoverFiles['pending_confirmation']) && count($handoverFiles['pending_confirmation']) > 0)
                                <div class="handover-stage-section pending-confirmation">
                                    <h4 class="handover-stage-title pending-confirmation">
                                        <i class="fas fa-file-upload"></i>
                                        Pending Confirmation Stage
                                    </h4>
                                    <div class="handover-files-list">
                                        @foreach($handoverFiles['pending_confirmation'] as $file)
                                            <div class="handover-file-item pending-confirmation">
                                                <div class="handover-file-info">
                                                    <div class="handover-file-icon pending-confirmation">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </div>
                                                    <div>
                                                        <p class="handover-file-name">{{ $file['name'] }}</p>
                                                    </div>
                                                </div>
                                                <a href="{{ $file['url'] }}" target="_blank" class="handover-file-link pending-confirmation">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if(
                                (!isset($handoverFiles['pending_confirmation']) || count($handoverFiles['pending_confirmation']) == 0) &&
                                (!isset($handoverFiles['pending_timetec_invoice']) || count($handoverFiles['pending_timetec_invoice']) == 0)
                            )
                                <div class="handover-empty-state">
                                    <i class="fas fa-folder-open"></i>
                                    <p>No files available</p>
                                </div>
                            @endif
                        </div>

                        <!-- Right Column: Pending Reseller Invoice & TimeTec License -->
                        <div class="handover-modal-column">
                            <!-- Pending TimeTec Invoice Files -->
                            @if(isset($handoverFiles['pending_timetec_invoice']) && count($handoverFiles['pending_timetec_invoice']) > 0)
                                <div class="handover-stage-section pending-timetec-invoice">
                                    <h4 class="handover-stage-title pending-timetec-invoice">
                                        File From TimeTec
                                    </h4>
                                    <div class="handover-files-list">
                                        @foreach($handoverFiles['pending_timetec_invoice'] as $file)
                                            <div class="handover-file-item pending-timetec-invoice">
                                                <div class="handover-file-info">
                                                    <div class="handover-file-icon pending-timetec-invoice">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </div>
                                                    <div>
                                                        <p class="handover-file-name">{{ $file['name'] }}</p>
                                                    </div>
                                                </div>
                                                <a href="{{ $file['url'] }}" target="_blank" class="handover-file-link pending-timetec-invoice">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                                        <p style="font-size: 0.75rem; color: #6b7280; margin-bottom: 0.25rem;">Submission Date/Time</p>
                                        <p style="font-size: 0.875rem; color: #1f2937; font-weight: 500;">
                                            {{ $selectedHandover->aci_submitted_at ? $selectedHandover->aci_submitted_at->format('d M Y, h:i A') : 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                            @endif

                            <!-- Pending Reseller Invoice Files -->
                            @if(isset($handoverFiles['pending_reseller_invoice']) && count($handoverFiles['pending_reseller_invoice']) > 0)
                                <div class="handover-stage-section pending-reseller-invoice">
                                    <h4 class="handover-stage-title pending-reseller-invoice">
                                        File From Reseller
                                    </h4>
                                    <div class="handover-files-list">
                                        @foreach($handoverFiles['pending_reseller_invoice'] as $file)
                                            <div class="handover-file-item pending-reseller-invoice">
                                                <div class="handover-file-info">
                                                    <div class="handover-file-icon pending-reseller-invoice">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </div>
                                                    <div>
                                                        <p class="handover-file-name">{{ $file['name'] }}</p>
                                                    </div>
                                                </div>
                                                <a href="{{ $file['url'] }}" target="_blank" class="handover-file-link pending-reseller-invoice">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                                        <p style="font-size: 0.75rem; color: #6b7280; margin-bottom: 0.25rem;">Submission Date/Time</p>
                                        <p style="font-size: 0.875rem; color: #1f2937; font-weight: 500;">
                                            {{ $selectedHandover->rni_submitted_at ? $selectedHandover->rni_submitted_at->format('d M Y, h:i A') : 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                            @endif

                            <!-- Pending TimeTec License - Official Receipt Number -->
                            @if(isset($selectedHandover->official_receipt_number) && $selectedHandover->official_receipt_number)
                                <div class="handover-stage-section pending-timetec-license">
                                    <h4 class="handover-stage-title pending-timetec-license">
                                        <i class="fas fa-receipt"></i>
                                        Official Receipt Number
                                    </h4>
                                    <div class="handover-receipt-box">
                                        <p class="handover-receipt-number">{{ $selectedHandover->official_receipt_number }}</p>
                                    </div>
                                </div>
                            @endif

                            <!-- Completed Stage Files -->
                            @if(isset($handoverFiles['completed']) && count($handoverFiles['completed']) > 0)
                                <div class="handover-stage-section completed">
                                    <h4 class="handover-stage-title completed">
                                        <i class="fas fa-check-circle"></i>
                                        Completed Stage
                                    </h4>
                                    <div class="handover-files-list">
                                        @foreach($handoverFiles['completed'] as $file)
                                            <div class="handover-file-item completed">
                                                <div class="handover-file-info">
                                                    <div class="handover-file-icon completed">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </div>
                                                    <div>
                                                        <p class="handover-file-name">{{ $file['name'] }}</p>
                                                    </div>
                                                </div>
                                                <a href="{{ $file['url'] }}" target="_blank" class="handover-file-link completed">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if(
                                (!isset($handoverFiles['pending_reseller_invoice']) || count($handoverFiles['pending_reseller_invoice']) == 0) &&
                                (!isset($selectedHandover->official_receipt_number) || !$selectedHandover->official_receipt_number) &&
                                (!isset($handoverFiles['completed']) || count($handoverFiles['completed']) == 0)
                            )
                                <div class="handover-empty-state">
                                    <i class="fas fa-folder-open"></i>
                                    <p>No files available</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Remark Modal -->
    @if(isset($showRemarkModal) && $showRemarkModal)
        <div class="handover-modal-overlay" style="z-index: 10000;">
            <div class="handover-modal-container">
                <div class="handover-modal-background" wire:click="$set('showRemarkModal', false)"></div>
                <div class="handover-modal-panel" style="max-width: 600px;">
                    <div class="handover-modal-header">
                        <div class="handover-modal-header-content">
                            <h3 class="handover-modal-title">
                                <i class="fas fa-comment"></i> Reseller Remark
                            </h3>
                            <button wire:click="$set('showRemarkModal', false)" class="handover-modal-close-btn">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="handover-modal-body">
                        <div style="background: #f9fafb; padding: 1rem; border-radius: 8px; border-left: 4px solid #3b82f6;">
                            <p style="white-space: pre-wrap; word-wrap: break-word; color: #1f2937; line-height: 1.6;">{{ $selectedHandover->reseller_remark ?? 'No remarks' }}</p>
                        </div>
                    </div>
                    <div class="handover-modal-footer">
                        <button wire:click="$set('showRemarkModal', false)" class="handover-modal-footer-btn">
                            <i class="fas fa-times"></i>Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Admin Remark Modal -->
    @if(isset($showAdminRemarkModal) && $showAdminRemarkModal)
        <div class="handover-modal-overlay" style="z-index: 10000;">
            <div class="handover-modal-container">
                <div class="handover-modal-background" wire:click="$set('showAdminRemarkModal', false)"></div>
                <div class="handover-modal-panel" style="max-width: 600px;">
                    <div class="handover-modal-header">
                        <div class="handover-modal-header-content">
                            <h3 class="handover-modal-title">
                                <i class="fas fa-comment-dots"></i> Admin Reseller Remark
                            </h3>
                            <button wire:click="$set('showAdminRemarkModal', false)" class="handover-modal-close-btn">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="handover-modal-body">
                        <div style="background: #f9fafb; padding: 1rem; border-radius: 8px; border-left: 4px solid #10b981;">
                            <p style="white-space: pre-wrap; word-wrap: break-word; color: #1f2937; line-height: 1.6;">{{ $selectedHandover->admin_reseller_remark ?? 'No remarks' }}</p>
                        </div>
                    </div>
                    <div class="handover-modal-footer">
                        <button wire:click="$set('showAdminRemarkModal', false)" class="handover-modal-footer-btn">
                            <i class="fas fa-times"></i>Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endif
