<!-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/device-purchase-information.blade.php -->
<x-filament-panels::page>
    <style>
        /* Modern card styling */
        .month-section {
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .month-header {
            background: linear-gradient(90deg, #2563eb, #3b82f6);
            color: white;
            font-weight: 600;
            padding: 1rem 1.25rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s ease;
        }

        .month-header:hover {
            background: linear-gradient(90deg, #1d4ed8, #2563eb);
        }

        .month-details {
            padding: 0;
            background-color: #fff;
            border-bottom-left-radius: 0.5rem;
            border-bottom-right-radius: 0.5rem;
        }

        /* Table styling */
        .month-table-container {
            overflow-x: auto;
            padding: 0.5rem;
        }

        .month-table {
            width: 100%;
            min-width: 1200px;
            border-collapse: collapse;
            border: none;
        }

        .month-table th {
            background-color: #f8fafc;
            color: #334155;
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.875rem;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }

        .month-table td {
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.875rem;
            vertical-align: middle;
        }

        .month-table tbody tr:hover {
            background-color: #f1f5f9;
        }

        /* Special rows */
        .totals-row {
            background-color: #eff6ff;
            font-weight: 600;
        }

        .totals-row td {
            border-top: 2px solid #bfdbfe;
            border-bottom: 2px solid #bfdbfe;
        }

        /* Enhanced Modal styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(2px);
            transition: all 0.3s ease;
        }

        .modal-content {
            background-color: white;
            border-radius: 0.75rem;
            max-width: 850px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.1);
            transform: translateY(0);
            transition: transform 0.3s ease;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #f3f4f6;
            background: linear-gradient(to right, #f9fafb, #f3f4f6);
        }

        .modal-body {
            padding: 1.75rem;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            padding: 1.25rem 1.5rem;
            border-top: 1px solid #f3f4f6;
            background: linear-gradient(to right, #f9fafb, #f3f4f6);
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modal-title-icon {
            width: 1.75rem;
            height: 1.75rem;
            padding: 0.375rem;
            background-color: #e0f2fe;
            color: #0284c7;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            color: #374151;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1rem;
            line-height: 1.5;
            transition: all 0.2s ease;
            background-color: #fff;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
        }

        .form-input:hover {
            border-color: #93c5fd;
        }

        /* Section dividers in modal */
        .form-section {
            border-bottom: 1px solid #f3f4f6;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
        }

        .form-section-title {
            font-weight: 500;
            color: #4b5563;
            margin-bottom: 1rem;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .form-section-icon {
            width: 1.25rem;
            height: 1.25rem;
        }

        /* Form grid */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.25rem;
        }

        @media (min-width: 768px) {
            .form-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        /* Enhanced buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
            gap: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background-color: #2563eb;
            color: white;
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-secondary {
            background-color: #9ca3af;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #6b7280;
            transform: translateY(-1px);
        }

        /* Close button */
        .modal-close-btn {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            background-color: #f3f4f6;
            transition: all 0.2s ease;
        }

        .modal-close-btn:hover {
            background-color: #e5e7eb;
            color: #374151;
        }

        /* Rest of your existing styles */
        .btn-danger {
            background-color: #dc2626;
            color: white;
        }

        .btn-danger:hover {
            background-color: #b91c1c;
        }

        .btn-success {
            background-color: #10b981;
            color: white;
        }

        .btn-success:hover {
            background-color: #059669;
        }

        .edit-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            color: #3b82f6;
            background-color: #dbeafe;
            transition: all 0.2s ease;
        }

        .edit-btn:hover {
            background-color: #bfdbfe;
            transform: scale(1.05);
        }

        .delete-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            color: #ef4444;
            background-color: #fee2e2;
            transition: all 0.2s ease;
        }

        .delete-btn:hover {
            background-color: #fecaca;
            transform: scale(1.05);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .expand-icon {
            width: 1.5rem;
            height: 1.5rem;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            padding: 0.25rem;
            transition: transform 0.2s ease;
        }

        /* Stats box */
        .month-stats {
            display: flex;
            margin-left: 1rem;
            gap: 1rem;
        }

        .stat-box {
            padding: 0.25rem 0.75rem;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 1rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .stat-icon {
            width: 1.25rem;
            height: 1.25rem;
        }

        /* Cell styling */
        .cell-feature {
            max-width: 250px;
        }

        .cell-number {
            text-align: right;
        }

        .cell-model {
            font-weight: 500;
        }
    </style>

    <!-- Modal for editing or creating items -->
    @if($isModalOpen)
    <div class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">
                    @if($editingModel)
                        <div class="modal-title-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                            </svg>
                        </div>
                        Edit Model: <span class="text-blue-600">{{ $editingModel }}</span>
                    @else
                        <div class="modal-title-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </div>
                        Add New Model
                    @endif
                </h2>
                <button type="button" class="modal-close-btn" wire:click="closeModal">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <form>
                    <!-- Model Information Section -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="form-section-icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                            </svg>
                            Basic Information
                        </div>
                        <div class="form-grid">
                            @if(!$editingModel)
                            <div class="form-group full-width">
                                <label class="form-label" for="model">Model Name*</label>
                                <input type="text" id="model" class="form-input" wire:model.defer="editingData.model" placeholder="Enter model name">
                            </div>
                            @endif

                            <div class="form-group">
                                <label class="form-label" for="qty">Quantity</label>
                                <input type="number" id="qty" class="form-input" wire:model.defer="editingData.qty">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="languages">Languages</label>
                                <input type="text" id="languages" class="form-input" wire:model.defer="editingData.languages" placeholder="e.g. English, Spanish">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="features">Features</label>
                                <input type="text" id="features" class="form-input" wire:model.defer="editingData.features" placeholder="Special features">
                            </div>
                        </div>
                    </div>

                    <!-- Power Plug Section -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="form-section-icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                            </svg>
                            Power Plug Configuration
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="england">England Plug</label>
                                <input type="number" id="england" class="form-input" wire:model.defer="editingData.england">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="america">America Plug</label>
                                <input type="number" id="america" class="form-input" wire:model.defer="editingData.america">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="europe">Europe Plug</label>
                                <input type="number" id="europe" class="form-input" wire:model.defer="editingData.europe">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="australia">Australia Plug</label>
                                <input type="number" id="australia" class="form-input" wire:model.defer="editingData.australia">
                            </div>
                        </div>
                    </div>

                    <!-- Serial Numbers & Orders Section -->
                    <div class="form-section" style="border-bottom: none; margin-bottom: 0;">
                        <div class="form-section-title">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="form-section-icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z" />
                            </svg>
                            Serial Numbers & Order Details
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="sn_no_from">SN No. From</label>
                                <input type="text" id="sn_no_from" class="form-input" wire:model.defer="editingData.sn_no_from" placeholder="Starting serial number">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="sn_no_to">SN No. To</label>
                                <input type="text" id="sn_no_to" class="form-input" wire:model.defer="editingData.sn_no_to" placeholder="Ending serial number">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="po_no">PO No.</label>
                                <input type="text" id="po_no" class="form-input" wire:model.defer="editingData.po_no" placeholder="Purchase order number">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="order_no">Order No.</label>
                                <input type="text" id="order_no" class="form-input" wire:model.defer="editingData.order_no" placeholder="Order reference number">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="balance_not_order">Balance Not Order</label>
                                <input type="number" id="balance_not_order" class="form-input" wire:model.defer="editingData.balance_not_order">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="rfid_card_foc">RFID/MF Card (F.O.C)</label>
                                <input type="number" id="rfid_card_foc" class="form-input" wire:model.defer="editingData.rfid_card_foc">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" wire:click="closeModal">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Cancel
                </button>
                <button type="button" class="btn btn-primary" wire:click="saveModalData">
                    @if($editingModel)
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                        </svg>
                        Update
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Create
                    @endif
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Rest of the content remains unchanged -->
    <div>
        <!-- Loop through months -->
        @foreach($months as $monthNum => $month)
            <div class="month-section">
                <!-- Month header (always visible) -->
                <div class="month-header" wire:click="toggleMonth({{ $monthNum }})">
                    <div class="flex items-center">
                        <span class="text-lg">RAW MATERIAL PLANNING {{ $month['name'] }} {{ $selectedYear }}</span>
                        <div class="month-stats">
                            <div class="stat-box">
                                <svg class="stat-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                </svg>
                                <span>{{ $month['totals']['qty'] }} Units</span>
                            </div>
                            <div class="stat-box">
                                <svg class="stat-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                </svg>
                                <span>{{ $month['totals']['rfid_card_foc'] }} RFID Cards</span>
                            </div>
                        </div>
                    </div>
                    <div class="expand-icon" style="{{ in_array($monthNum, $expandedMonths) ? 'transform: rotate(180deg)' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>

                <!-- Month details (expandable) -->
                @if(in_array($monthNum, $expandedMonths))
                <div class="month-details">
                    <div class="flex items-center justify-between px-4 py-3 bg-white">
                        <div class="text-sm text-gray-500">
                            {{ count($purchaseData[$monthNum]) }} models found
                        </div>
                        <button type="button" class="btn btn-success" wire:click="openCreateModal({{ $monthNum }})">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-1">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Add New Model
                        </button>
                    </div>
                    <div class="month-table-container">
                        <table class="month-table">
                            <thead>
                                <tr>
                                    <th style="width: 15%;">Model</th>
                                    <th style="width: 7%;">Qty</th>
                                    <th style="width: 10%;">Languages</th>
                                    <th style="width: 6%;">England</th>
                                    <th style="width: 6%;">America</th>
                                    <th style="width: 6%;">Europe</th>
                                    <th style="width: 6%;">Australia</th>
                                    <th style="width: 9%;">SN No. From/To</th>
                                    <th style="width: 7%;">PO No.</th>
                                    <th style="width: 7%;">Order No.</th>
                                    <th style="width: 6%;">Balance</th>
                                    <th style="width: 6%;">RFID Card</th>
                                    <th style="width: 15%;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Display existing models -->
                                @foreach($purchaseData[$monthNum] as $model => $data)
                                <tr>
                                    <td class="cell-model">{{ $model }}</td>
                                    <td class="cell-number">{{ $data['qty'] }}</td>
                                    <td>{{ $data['languages'] }}</td>
                                    <td class="cell-number">{{ $data['england'] }}</td>
                                    <td class="cell-number">{{ $data['america'] }}</td>
                                    <td class="cell-number">{{ $data['europe'] }}</td>
                                    <td class="cell-number">{{ $data['australia'] }}</td>
                                    <td>{{ $data['sn_no_from'] }} - {{ $data['sn_no_to'] }}</td>
                                    <td>{{ $data['po_no'] }}</td>
                                    <td>{{ $data['order_no'] }}</td>
                                    <td class="cell-number">{{ $data['balance_not_order'] }}</td>
                                    <td class="cell-number">{{ $data['rfid_card_foc'] }}</td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="edit-btn" wire:click="openEditModal({{ $monthNum }}, '{{ $model }}')">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                                </svg>
                                            </button>
                                            <button type="button" class="delete-btn" wire:click="deleteModel({{ $monthNum }}, '{{ $model }}')">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach

                                <!-- Totals row -->
                                <tr class="totals-row">
                                    <td>Total</td>
                                    <td class="cell-number">{{ $month['totals']['qty'] }}</td>
                                    <td colspan="9"></td>
                                    <td class="cell-number">{{ $month['totals']['rfid_card_foc'] }}</td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
