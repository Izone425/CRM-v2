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

        .company-name {
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

        .status-green {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            color: #166534;
            border: 1px solid #86efac;
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

        .tabs-container {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .tab-button {
            padding: 0.75rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            border: none;
            background: transparent;
            cursor: pointer;
            transition: all 0.2s;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            color: #6b7280;
        }

        .tab-button:hover {
            color: #667eea;
        }

        .tab-button.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .tab-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.5rem;
            height: 1.5rem;
            padding: 0 0.5rem;
            margin-left: 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 12px;
            background: #e5e7eb;
            color: #6b7280;
        }

        .tab-button.active .tab-count {
            background: #667eea;
            color: white;
        }

        .pagination-wrapper {
            padding: 1.5rem;
            background: white;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: center;
        }

        .pagination-wrapper nav {
            display: flex;
            gap: 0.5rem;
        }

        .pagination-wrapper a,
        .pagination-wrapper span {
            padding: 0.5rem 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .pagination-wrapper a {
            color: #667eea;
            cursor: pointer;
        }

        .pagination-wrapper a:hover {
            background: #f0f4ff;
            border-color: #667eea;
        }

        .pagination-wrapper span.current {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
    </style>
    <!-- Tabs -->
    <div class="tabs-container">
        <button
            wire:click="switchTab('active')"
            class="tab-button {{ $activeTab === 'active' ? 'active' : '' }}">
            Active Customers
            <span class="tab-count">{{ $activeCount ?? 0 }}</span>
        </button>
        <button
            wire:click="switchTab('inactive')"
            class="tab-button {{ $activeTab === 'inactive' ? 'active' : '' }}">
            InActive Customers
            <span class="tab-count">{{ $inactiveCount ?? 0 }}</span>
        </button>
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
            placeholder="Search by company name"
            class="search-input"
        >
    </div>

    <!-- Table -->
    <div class="table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Company Name</th>
                    <th>
                        <button wire:click="sortByDate">
                            Registration Date
                            <svg class="sort-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($sortDirection === 'desc')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                @endif
                            </svg>
                        </button>
                    </th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="company-name">
                            {{ strtoupper($customer->f_company_name) }}
                        </td>
                        <td class="date-cell">
                            {{ date('Y-m-d', strtotime($customer->f_reg_date)) }}
                        </td>
                        <td>
                            <span class="status-badge {{ $activeTab === 'active' ? 'status-green' : 'status-red' }}">
                                {{ $activeTab === 'active' ? 'Active' : 'InActive' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-state">
                            No {{ $activeTab }} customers found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
