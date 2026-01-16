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

        .status-pending {
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            color: #9a3412;
            border: 1px solid #fdba74;
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

        .confirm-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .confirm-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .pdf-icon {
            width: 1rem;
            height: 1rem;
        }

        .confirm-icon {
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
            max-width: 500px;
            width: 90%;
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
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .modal-icon svg {
            width: 1.5rem;
            height: 1.5rem;
            color: #d97706;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
        }

        .modal-body {
            color: #6b7280;
            margin-bottom: 1.5rem;
            line-height: 1.6;
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
    </style>

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
                    <th>Status</th>
                    <th>Last Modified</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($handovers as $handover)
                    <tr>
                        <td class="fb-id">
                            <a href="javascript:void(0)"
                               wire:click="openFilesModal({{ $handover->id }})"
                               class="font-bold text-blue-600 cursor-pointer hover:text-blue-800 hover:underline">
                                {{ $handover->fb_id }}
                            </a>
                        </td>
                        <td class="subscriber-name">
                            {{ $handover->subscriber_name }}
                        </td>
                        <td>
                            <span class="status-badge status-pending">
                                Pending Confirmation
                            </span>
                        </td>
                        <td class="date-cell">
                            {{ $handover->updated_at->format('d/m/Y h:i A') }}
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <button
                                    wire:click="openConfirmModal({{ $handover->id }})"
                                    class="confirm-button">
                                    <svg class="confirm-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Proceed Confirmation
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-state">
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('components.handover-files-modal')

    <!-- Confirmation Modal -->
    @if($showConfirmModal)
        <div class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <h3 class="modal-title">Proceed with Confirmation?</h3>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to proceed with this confirmation?</p>
                    <p style="margin-top: 0.5rem;">This will send the request to TimeTec for invoicing.</p>
                </div>
                <div class="modal-actions">
                    <button wire:click="closeConfirmModal" class="modal-button-cancel">
                        Cancel
                    </button>
                    <button wire:click="proceedConfirmation" class="modal-button-confirm">
                        Confirm & Proceed
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Success Message -->
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
</div>
