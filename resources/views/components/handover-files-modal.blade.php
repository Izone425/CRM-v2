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
                                <i class="fas fa-file-alt"></i>
                                Files for {{ $selectedHandover->fb_id ?? '' }}
                            </h3>
                            <p class="handover-modal-subtitle">{{ $selectedHandover->subscriber_name ?? '' }}</p>
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
                            </div>

                            <div class="handover-info-box">
                                <h4 class="handover-info-title">
                                    <i class="fas fa-comment-dots"></i>
                                    Admin Reseller Remark
                                </h4>
                                <p class="handover-remark-text">
                                    {{ trim($selectedHandover->admin_reseller_remark ?? 'No remarks') }}
                                </p>
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

                            <!-- Pending TimeTec Invoice Files -->
                            @if(isset($handoverFiles['pending_timetec_invoice']) && count($handoverFiles['pending_timetec_invoice']) > 0)
                                <div class="handover-stage-section pending-timetec-invoice">
                                    <h4 class="handover-stage-title pending-timetec-invoice">
                                        <i class="fas fa-file-invoice"></i>
                                        Pending TimeTec Invoice Stage
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
                            <!-- Pending Reseller Invoice Files -->
                            @if(isset($handoverFiles['pending_reseller_invoice']) && count($handoverFiles['pending_reseller_invoice']) > 0)
                                <div class="handover-stage-section pending-reseller-invoice">
                                    <h4 class="handover-stage-title pending-reseller-invoice">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                        Pending Reseller Invoice Stage
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
                                </div>
                            @endif

                            <!-- Pending TimeTec License - Official Receipt Number -->
                            @if(isset($selectedHandover->official_receipt_number) && $selectedHandover->official_receipt_number)
                                <div class="handover-stage-section pending-timetec-license">
                                    <h4 class="handover-stage-title pending-timetec-license">
                                        <i class="fas fa-receipt"></i>
                                        Pending TimeTec License Stage
                                    </h4>
                                    <div class="handover-receipt-box">
                                        <p class="handover-receipt-label">Official Receipt Number</p>
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

                <!-- Footer -->
                <div class="handover-modal-footer">
                    <button wire:click="closeFilesModal" class="handover-modal-footer-btn">
                        <i class="fas fa-times"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
