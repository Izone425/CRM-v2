{{-- filepath: /var/www/html/timeteccrm/resources/views/components/view-bypass-duplicate-details.blade.php --}}
<style>
.bypass-details-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.section-card {
    padding: 1rem;
    border-radius: 0.5rem;
}

.reason-section {
    background-color: #f9fafb;
}

.current-lead-section {
    background-color: #eff6ff;
}

.duplicate-leads-section {
    background-color: #fef2f2;
}

.green-section {
    background-color: #f0fdf4;
}

.yellow-section {
    background-color: #fffbeb;
}

.timestamp-section {
    background-color: #f9fafb;
    padding: 0.75rem;
    font-size: 0.875rem;
    color: #4b5563;
}

.section-title {
    margin-bottom: 0.75rem;
    font-size: 1.125rem;
    font-weight: 600;
}

.reason-title {
    color: #111827;
}

.current-lead-title {
    color: #1e3a8a;
}

.duplicate-title {
    color: #7f1d1d;
}

.green-title {
    color: #14532d;
}

.yellow-title {
    color: #92400e;
}

.reason-text {
    color: #374151;
    white-space: pre-wrap;
}

.green-text {
    color: #166534;
}

.yellow-text {
    color: #d97706;
}

.table-container {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    font-size: 0.875rem;
    background-color: white;
    border: 1px solid;
    border-radius: 0.375rem;
}

.current-lead-table {
    border-color: #bfdbfe;
}

.duplicate-table {
    border-color: #fecaca;
}

.current-lead-row {
    border-bottom: 1px solid #dbeafe;
}

.current-lead-row:last-child {
    border-bottom: none;
}

.current-lead-label {
    padding: 0.5rem 0.75rem;
    font-weight: 500;
    color: #1e40af;
    background-color: #eff6ff;
}

.current-lead-value {
    padding: 0.5rem 0.75rem;
    color: #1d4ed8;
}

.table-header {
    background-color: #fecaca;
}

.table-header th {
    padding: 0.5rem 0.75rem;
    text-align: left;
    font-weight: 500;
    color: #7f1d1d;
    border-right: 1px solid #fecaca;
}

.table-header th:last-child {
    border-right: none;
}

.table-row {
    border-bottom: 1px solid #fecaca;
}

.table-row:nth-child(even) {
    background-color: #fef7f7;
}

.table-row:nth-child(odd) {
    background-color: white;
}

.table-cell {
    padding: 0.5rem 0.75rem;
    color: #7f1d1d;
    border-right: 1px solid #fecaca;
}

.table-cell:last-child {
    border-right: none;
}

.truncate-text {
    max-width: 12rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.match-type-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 500;
    color: #7f1d1d;
    background-color: #fecaca;
    border-radius: 9999px;
}

.na-text {
    color: #6b7280;
}
</style>

<div class="bypass-details-container">
    <!-- Reason Section -->
    <div class="section-card reason-section">
        <h3 class="section-title reason-title">Request Reason</h3>
        <p class="reason-text">{{ $reason }}</p>
    </div>

    @if($duplicateInfo && isset($duplicateInfo['current_lead']))
        <!-- Current Lead Information -->
        <div class="section-card current-lead-section">
            <h3 class="section-title current-lead-title">Current Lead Information</h3>
            <div class="table-container">
                <table class="data-table current-lead-table">
                    <tbody>
                        <tr class="current-lead-row">
                            <td class="current-lead-label">Lead ID</td>
                            <td class="current-lead-value">{{ $duplicateInfo['current_lead']['lead_id'] }}</td>
                        </tr>
                        <tr class="current-lead-row">
                            <td class="current-lead-label">Company Name</td>
                            <td class="current-lead-value">{{ $duplicateInfo['current_lead']['company_name'] ?? 'N/A' }}</td>
                        </tr>
                        <tr class="current-lead-row">
                            <td class="current-lead-label">Lead Code</td>
                            <td class="current-lead-value">{{ $duplicateInfo['current_lead']['lead_code'] ?? 'N/A' }}</td>
                        </tr>
                        <tr class="current-lead-row">
                            <td class="current-lead-label">Email</td>
                            <td class="current-lead-value">{{ $duplicateInfo['current_lead']['email'] ?? 'N/A' }}</td>
                        </tr>
                        <tr class="current-lead-row">
                            <td class="current-lead-label">Phone</td>
                            <td class="current-lead-value">{{ $duplicateInfo['current_lead']['phone'] ?? 'N/A' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        @if(isset($duplicateInfo['duplicates_found']) && count($duplicateInfo['duplicates_found']) > 0)
            <!-- Duplicate Leads Section -->
            <div class="section-card duplicate-leads-section">
                <h3 class="section-title duplicate-title">
                    Duplicate Leads Found ({{ $duplicateInfo['total_duplicates'] ?? count($duplicateInfo['duplicates_found']) }})
                </h3>

                <div class="table-container">
                    <table class="data-table duplicate-table">
                        <thead class="table-header">
                            <tr>
                                <th>Lead ID</th>
                                <th>Company Name</th>
                                <th>Lead Code</th>
                                <th>Lead Owner</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Category</th>
                                <th>Created Date</th>
                                <th>Match Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($duplicateInfo['duplicates_found'] as $index => $duplicate)
                                <tr class="table-row">
                                    <td class="table-cell">{{ $duplicate['lead_id'] }}</td>
                                    <td class="table-cell">
                                        <div class="truncate-text" title="{{ $duplicate['company_name'] ?? 'N/A' }}">
                                            {{ $duplicate['company_name'] ?? 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="table-cell">{{ $duplicate['lead_code'] ?? 'N/A' }}</td>
                                    <td class="table-cell">{{ $duplicate['lead_owner'] ?? 'N/A' }}</td>
                                    <td class="table-cell">
                                        <div class="truncate-text" title="{{ $duplicate['email'] ?? 'N/A' }}">
                                            {{ $duplicate['email'] ?? 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="table-cell">{{ $duplicate['phone'] ?? 'N/A' }}</td>
                                    <td class="table-cell">{{ $duplicate['categories'] ?? 'N/A' }}</td>
                                    <td class="table-cell">
                                        @if(isset($duplicate['created_at']))
                                            {{ \Carbon\Carbon::parse($duplicate['created_at'])->format('d M Y') }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td class="table-cell">
                                        @if(isset($duplicate['match_type']))
                                            <span class="match-type-badge">
                                                {{ $duplicate['match_type'] }}
                                            </span>
                                        @else
                                            <span class="na-text">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="section-card green-section">
                <h3 class="section-title green-title">Duplicate Status</h3>
                <p class="green-text">No duplicate leads found during the check.</p>
            </div>
        @endif

        @if(isset($duplicateInfo['checked_at']))
            <div class="timestamp-section">
                <strong>Duplicate Check Performed:</strong> {{ \Carbon\Carbon::parse($duplicateInfo['checked_at'])->format('d M Y, h:i A') }}
            </div>
        @endif
    @else
        <!-- Fallback for old requests without duplicate_info -->
        <div class="section-card yellow-section">
            <h3 class="section-title yellow-title">Duplicate Information</h3>
            <p class="yellow-text">Duplicate information not available for this request. This may be an older request created before duplicate tracking was implemented.</p>
        </div>
    @endif
</div>
