<x-filament::page>
    <style>
        .fi-ta-ctn .py-4 {
            padding-top: .5rem !important;
            padding-bottom: .5rem !important;
        }

        .search-container {
            margin-bottom: 1rem;
        }

        .search-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .search-inputs {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .input-group {
            position: relative;
            flex-grow: 1;
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .search-input {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            transition: all 0.15s ease-in-out;
            font-size: 0.875rem;
        }

        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .button-group {
            display: flex;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border: 1px solid transparent;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
            text-decoration: none;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-primary {
            background-color: #3b82f6;
            border-color: #3b82f6;
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            background-color: #2563eb;
            border-color: #2563eb;
        }

        .btn-primary:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }

        .btn-secondary {
            background-color: #6b7280;
            border-color: #6b7280;
            color: white;
        }

        .btn-secondary:hover:not(:disabled) {
            background-color: #4b5563;
            border-color: #4b5563;
        }

        .btn-gray {
            background-color: #f9fafb;
            border-color: #d1d5db;
            color: #374151;
        }

        .btn-gray:hover:not(:disabled) {
            background-color: #f3f4f6;
            border-color: #9ca3af;
        }

        .btn-icon {
            width: 1rem;
            height: 1rem;
            margin-right: 0.5rem;
        }

        .spin {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .helper-text {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .table-container {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }

        .table-header {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .results-count {
            font-weight: 600;
            color: #374151;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-head {
            background-color: #f9fafb;
        }

        .table-th {
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
            font-size: 0.75rem;
            text-transform: uppercase;
        }

        .table-th.sortable {
            cursor: pointer;
            user-select: none;
        }

        .table-th.sortable:hover {
            background-color: #f3f4f6;
        }

        .table-th.center {
            text-align: center;
        }

        .sort-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sort-header.center {
            justify-content: center;
        }

        .sort-icon {
            width: 1rem;
            height: 1rem;
            margin-left: 0.25rem;
        }

        .table-body {
            background-color: white;
        }

        .table-row {
            border-bottom: 1px solid #e5e7eb;
        }

        .table-row:hover {
            background-color: #f9fafb;
        }

        .table-td {
            padding: 0.75rem;
            color: #374151;
            font-size: 0.875rem;
        }

        .table-td.center {
            text-align: center;
        }

        .table-td.wrap {
            white-space: normal;
            word-wrap: break-word;
            max-width: 200px;
        }

        .table-td.bold {
            font-weight: 600;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem 0.75rem;
            border-radius: 25px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            width: 90%;
            height: 27px;
            text-align: center;
        }

        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            border-radius: 0.25rem;
            text-decoration: none;
            margin: 0.125rem;
        }

        .btn-action:hover {
            text-decoration: none;
        }

        .btn-action:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            align-items: center;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6b7280;
        }

        .empty-icon {
            width: 3rem;
            height: 3rem;
            margin: 0 auto 1rem;
            color: #d1d5db;
        }

        .empty-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .empty-description {
            font-size: 0.875rem;
        }

        .row-index {
            width: 50px;
            text-align: center;
        }

        /* Modal styles */
        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        .modal-content {
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 28rem;
            margin: 1rem;
        }

        .modal-header {
            padding: 1.5rem 1.5rem 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .modal-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #111827;
            margin: 0;
        }

        .modal-body {
            padding: 0;
        }

        .modal-footer {
            padding: 1rem 1.5rem 1.5rem 1.5rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
        }
    </style>

    <div>
        <div class="search-container">
            <div class="search-form">
                {{-- Search Input Row --}}
                <div class="search-inputs">
                    <div class="input-group">
                        <label for="company-search" class="sr-only">Search Company Name</label>
                        <input
                            type="text"
                            id="company-search"
                            wire:model.live="companySearchTerm"
                            placeholder="Search company name..."
                            class="search-input"
                            wire:keydown.enter="searchCompany"
                        >
                    </div>
                    <div class="input-group">
                        <label for="phone-search" class="sr-only">Search Phone Number</label>
                        <input
                            type="text"
                            id="phone-search"
                            wire:model.live="phoneSearchTerm"
                            placeholder="Search phone number..."
                            class="search-input"
                            wire:keydown.enter="searchCompany"
                        >
                    </div>
                    <div class="button-group">
                        <button
                            type="button"
                            wire:click="searchCompany"
                            wire:loading.attr="disabled"
                            wire:target="searchCompany"
                            class="btn btn-primary"
                        >
                            <svg wire:loading.remove wire:target="searchCompany" class="btn-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                            <svg wire:loading wire:target="searchCompany" class="btn-icon spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="searchCompany">Search</span>
                            <span wire:loading wire:target="searchCompany">Searching...</span>
                        </button>
                        @if($hasSearched)
                            <button
                                type="button"
                                wire:click="resetSearch"
                                wire:loading.attr="disabled"
                                wire:target="resetSearch"
                                class="btn btn-secondary"
                            >
                                <svg class="btn-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Clear
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Helper Text Row --}}
                <div class="helper-text">
                    <p>Search by company name and/or phone number. Phone search includes both lead phone and company contact numbers.</p>
                </div>
            </div>
        </div>

        {{-- Results Table --}}
        @if($hasSearched)
            @php $leads = $this->getLeads(); @endphp

            @if($leads->count() > 0)
                <div class="table-container">
                    <div class="table-header">
                        <h3 class="results-count">Found {{ $leads->count() }} result(s)</h3>
                    </div>

                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead class="table-head">
                                <tr>
                                    <th class="table-th row-index">ID</th>
                                    <th class="table-th">LEAD OWNER</th>
                                    <th class="table-th">SALESPERSON</th>
                                    <th class="table-th sortable" wire:click="sortBy('created_at')">
                                        <div class="sort-header">
                                            <span>CREATED ON</span>
                                            @if($sortField === 'created_at')
                                                <svg class="sort-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    @if($sortDirection === 'asc')
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                                    @else
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                                    @endif
                                                </svg>
                                            @endif
                                        </div>
                                    </th>
                                    <th class="table-th center">LEAD STATUS</th>
                                    <th class="table-th">COMPANY NAME</th>
                                    <th class="table-th">COMPANY SIZE</th>
                                    <th class="table-th">HEADCOUNT</th>
                                    <th class="table-th center">ACTION</th>
                                </tr>
                            </thead>
                            <tbody class="table-body">
                                @foreach($leads as $index => $lead)
                                    <tr class="table-row">
                                        <td class="table-td row-index">{{ $index + 1 }}</td>
                                        <td class="table-td">{{ $this->getLeadOwner($lead) }}</td>
                                        <td class="table-td">{{ $this->getSalesperson($lead) }}</td>
                                        <td class="table-td">
                                            {{ \Illuminate\Support\Carbon::parse($lead->created_at)->setTimezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A') }}
                                        </td>
                                        <td class="table-td center">
                                            @php
                                                $statusColor = $this->getLeadStatusColor($lead);
                                                $status = $this->getLeadStatus($lead);
                                                $textColor = in_array($status, ['Hot', 'Warm', 'Cold', 'RFQ-Transfer']) ? 'white' : 'black';
                                            @endphp
                                            <span class="status-badge" style="background-color: {{ $statusColor }}; color: {{ $textColor }};">
                                                {{ $status }}
                                            </span>
                                        </td>
                                        <td class="table-td bold">{{ $this->getCompanyName($lead) }}</td>
                                        <td class="table-td">{{ $lead->company_size_label ?? '-' }}</td>
                                        <td class="table-td">{{ $lead->company_size ?? '-' }}</td>
                                        <td class="table-td center">
                                            <div class="action-buttons">
                                                <button
                                                    wire:click="openTimeSinceModal({{ $lead->id }})"
                                                    class="btn btn-gray btn-action"
                                                    title="View Period"
                                                >
                                                    <svg class="btn-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    View Period
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="empty-state">
                    <svg class="empty-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <div class="empty-title">No Results Found</div>
                    <div class="empty-description">
                        No leads found matching your search criteria. Try adjusting your search terms.
                    </div>
                </div>
            @endif
        @endif

        {{-- Time Since Creation Modal --}}
        @if($showTimeSinceModal && $selectedLead)
            <div class="modal-overlay" wire:click="closeTimeSinceModal">
                <div class="modal-content" wire:click.stop>
                    <div class="modal-header">
                        <h3 class="modal-title">View Period</h3>
                    </div>
                    <div class="modal-body">
                        @php
                            $timeSinceData = $this->getTimeSinceCreationData($selectedLead);
                        @endphp
                        @include('filament.modals.time-since-creation', $timeSinceData)
                    </div>
                    <div class="modal-footer">
                        <button wire:click="closeTimeSinceModal" class="btn btn-secondary">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament::page>
