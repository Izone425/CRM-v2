<div>
    <style>
        .title-section {
            padding: 0;
            margin-bottom: 1.5rem;
        }

        .title-section h2 {
            color: #111827;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }

        .title-section p {
            color: #6b7280;
            font-size: 0.875rem;
            margin: 0.25rem 0 0 0;
        }

        .search-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            pointer-events: none;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            background: white;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
        }

        .custom-table thead {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }

        .custom-table th {
            padding: 1rem 1.5rem;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #e2e8f0;
        }

        .custom-table th button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
            font-weight: 600;
            transition: color 0.2s;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .custom-table th button:hover {
            color: #667eea;
        }

        .custom-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s ease;
        }

        .custom-table tbody tr:hover {
            background: linear-gradient(90deg, #f8fafc 0%, #ffffff 100%);
        }

        .custom-table td {
            padding: 1rem 1.5rem;
            font-size: 0.875rem;
            color: #1f2937;
        }

        .fb-id {
            font-weight: 600;
            color: #667eea;
        }

        .subscriber-name {
            font-weight: 600;
            color: #111827;
        }

        .date-cell {
            color: #6b7280;
        }

        .status-badge {
            display: inline-flex;
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 20px;
            letter-spacing: 0.025em;
        }

        .status-pending-payment {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border: 1px solid #f87171;
        }

        .pdf-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .pdf-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .pdf-icon {
            width: 1rem;
            height: 1rem;
        }

        .empty-state {
            padding: 3rem 1.5rem;
            text-align: center;
            color: #9ca3af;
        }

        .sort-icon {
            width: 1rem;
            height: 1rem;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            animation: fadeIn 0.2s ease-out;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            animation: slideUp 0.3s ease-out;
        }

        .modal-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .modal-icon {
            width: 3rem;
            height: 3rem;
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .modal-icon svg {
            width: 1.5rem;
            height: 1.5rem;
            color: #059669;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
        }

        .modal-body {
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-label.required::after {
            content: ' *';
            color: #ef4444;
        }

        .file-upload-wrapper {
            position: relative;
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 1rem;
            background: linear-gradient(135deg, #f9fafb 0%, #ffffff 100%);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .file-upload-wrapper:hover {
            border-color: #667eea;
            background: linear-gradient(135deg, #f0f4ff 0%, #ffffff 100%);
        }

        .file-upload-wrapper.has-file {
            border-color: #10b981;
            background: linear-gradient(135deg, #d1fae5 0%, #ffffff 100%);
            border-style: solid;
        }

        .file-upload-content {
            text-align: center;
        }

        .file-upload-icon {
            width: 3rem;
            height: 3rem;
            margin: 0 auto 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .file-upload-wrapper.has-file .file-upload-icon {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .file-upload-icon svg {
            width: 1.5rem;
            height: 1.5rem;
            color: white;
        }

        .file-upload-text {
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .file-upload-hint {
            font-size: 0.75rem;
            color: #9ca3af;
        }

        .file-upload-input {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }

        .file-selected-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-top: 1rem;
            padding: 0.75rem;
            background: white;
            border-radius: 8px;
            border: 1px solid #d1fae5;
        }

        .file-selected-icon {
            width: 2.5rem;
            height: 2.5rem;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .file-selected-icon svg {
            width: 1.25rem;
            height: 1.25rem;
            color: white;
        }

        .file-selected-details {
            flex: 1;
        }

        .file-selected-name {
            font-size: 0.875rem;
            font-weight: 600;
            color: #059669;
            word-break: break-all;
        }

        .file-selected-size {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.125rem;
        }

        .file-delete-button {
            padding: 0.375rem;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .file-delete-button:hover {
            transform: scale(1.1);
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        }

        .file-delete-button svg {
            width: 1rem;
            height: 1rem;
            color: white;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .modal-button-cancel {
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            background: #f3f4f6;
            color: #374151;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .modal-button-cancel:hover {
            background: #e5e7eb;
        }

        .modal-button-confirm {
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .modal-button-confirm:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .modal-button-confirm:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-message {
            position: fixed;
            top: 1rem;
            right: 1rem;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            z-index: 10000;
            animation: slideInRight 0.3s ease-out;
        }

        .error-message {
            position: fixed;
            top: 1rem;
            right: 1rem;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
            z-index: 10000;
            animation: slideInRight 0.3s ease-out;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .error-text {
            color: #ef4444;
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
    </style>

    <!-- Title -->
    <div class="title-section">
        <h2>Pending Payment</h2>
        <p>View handovers that are pending payment</p>
    </div>

    <!-- Search Input -->
    <div class="search-wrapper">
        <div class="search-icon">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
        <input
            type="text"
            wire:model.live="search"
            placeholder="Search by FB ID or subscriber name..."
            class="search-input"
        >
    </div>

    <!-- Table -->
    <div class="table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>
                        <button wire:click="sortBy('id')">
                            FB ID
                            <svg class="sort-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($sortField === 'id')
                                    @if($sortDirection === 'desc')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    @endif
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                @endif
                            </svg>
                        </button>
                    </th>
                    <th>
                        <button wire:click="sortBy('subscriber_name')">
                            Subscriber Name
                            <svg class="sort-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($sortField === 'subscriber_name')
                                    @if($sortDirection === 'desc')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    @endif
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                @endif
                            </svg>
                        </button>
                    </th>
                    <th>
                        <button wire:click="sortBy('created_at')">
                            Created At
                            <svg class="sort-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($sortField === 'created_at')
                                    @if($sortDirection === 'desc')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    @endif
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                @endif
                            </svg>
                        </button>
                    </th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($handovers as $handover)
                    <tr>
                        <td class="fb-id">
                            <a wire:click="openFilesModal({{ $handover->id }})" style="color: #3b82f6; font-weight: 600; cursor: pointer; text-decoration: none;"
                               onmouseover="this.style.textDecoration='underline'"
                               onmouseout="this.style.textDecoration='none'">
                                {{ $handover->fb_id }}
                            </a>
                        </td>
                        <td class="subscriber-name">
                            {{ $handover->subscriber_name }}
                        </td>
                        <td class="date-cell">
                            {{ $handover->created_at->format('d M Y, H:i') }}
                        </td>
                        <td>
                            <span class="status-badge status-pending-payment">
                                Pending Payment
                            </span>
                        </td>
                        <td>
                            @if($handover->pdf_file)
                                <a href="{{ Storage::disk('public')->url($handover->pdf_file) }}"
                                   target="_blank"
                                   class="pdf-button">
                                    <svg class="pdf-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    View PDF
                                </a>
                            @endif
                            <button
                                wire:click="openCompleteModal({{ $handover->id }})"
                                class="pdf-button"
                                style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); margin-left: 0.5rem;">
                                <svg class="pdf-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Complete Task
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-state">
                            No pending payment handovers found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Complete Task Modal -->
    @if($showCompleteModal && $selectedHandover)
        <div class="modal-overlay" wire:click="closeCompleteModal">
            <div class="modal-content" wire:click.stop>
                <div class="modal-header">
                    <div class="modal-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="modal-title">Complete Task</h3>
                </div>

                <div class="modal-body">
                    <form wire:submit.prevent="completeTask">
                        <div class="form-group">
                            <label class="form-label required">Reseller Payment Slip</label>
                            <div class="file-upload-wrapper {{ $paymentSlip ? 'has-file' : '' }}">
                                <div class="file-upload-content">
                                    <div class="file-upload-icon">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            @if($paymentSlip)
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                            @endif
                                        </svg>
                                    </div>
                                    <p class="file-upload-text">
                                        @if($paymentSlip)
                                            {{ is_array($paymentSlip) ? count($paymentSlip) : 1 }} file(s) selected
                                        @else
                                            Click to upload or drag and drop
                                        @endif
                                    </p>
                                    <p class="file-upload-hint">PDF, JPG, JPEG, PNG (Max 10MB per file)</p>
                                </div>
                                <input
                                    type="file"
                                    wire:model="paymentSlip"
                                    multiple
                                    accept="application/pdf,image/*"
                                    class="file-upload-input"
                                />
                            </div>
                            @if($paymentSlip)
                                @foreach(is_array($paymentSlip) ? $paymentSlip : [$paymentSlip] as $index => $file)
                                    <div class="file-selected-info">
                                        <div class="file-selected-icon">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                        </div>
                                        <div class="file-selected-details">
                                            <p class="file-selected-name">{{ $file->getClientOriginalName() }}</p>
                                            <p class="file-selected-size">{{ number_format($file->getSize() / 1024, 2) }} KB</p>
                                        </div>
                                        <button
                                            type="button"
                                            wire:click="removePaymentSlipFile({{ $index }})"
                                            class="file-delete-button"
                                        >
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            @endif
                            @error('paymentSlip')
                                <p class="error-text">{{ $message }}</p>
                            @enderror
                        </div>
                    </form>
                </div>

                <div class="modal-actions">
                    <button wire:click="closeCompleteModal" class="modal-button-cancel">
                        Cancel
                    </button>
                    <button
                        wire:click="completeTask"
                        class="modal-button-confirm"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove>Complete Task</span>
                        <span wire:loading>Uploading...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Success/Error Messages -->
    @if (session()->has('message'))
        <div
            x-data="{ show: true }"
            x-show="show"
            x-init="setTimeout(() => show = false, 3000)"
            class="success-message">
            <i class="mr-2 fas fa-check-circle"></i>
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div
            x-data="{ show: true }"
            x-show="show"
            x-init="setTimeout(() => show = false, 3000)"
            class="error-message">
            <i class="mr-2 fas fa-exclamation-circle"></i>
            {{ session('error') }}
        </div>
    @endif

    @include('components.handover-files-modal')
</div>
