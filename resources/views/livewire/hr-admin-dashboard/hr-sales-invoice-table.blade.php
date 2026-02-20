<div class="p-4 bg-white rounded-lg shadow-lg sales-invoice-compact" style="height: auto;">
    <style>
        .sales-invoice-compact .fi-ta-table {
            table-layout: fixed;
            width: 100%;
        }
        .sales-invoice-compact .fi-ta-cell,
        .sales-invoice-compact .fi-ta-header-cell {
            padding: 0.35rem 0.4rem !important;
            font-size: 0.75rem !important;
        }
        .sales-invoice-compact .fi-ta-header-cell-label {
            font-size: 0.7rem !important;
        }
        .sales-invoice-compact .fi-ta-text-item,
        .sales-invoice-compact .fi-ta-text-item span {
            font-size: 0.75rem !important;
        }
        .sales-invoice-compact .fi-badge {
            font-size: 0.65rem !important;
            padding: 0.15rem 0.4rem !important;
        }
        .sales-invoice-compact .fi-ta-actions {
            gap: 0.25rem !important;
        }
        .sales-invoice-compact .fi-ta-actions .fi-btn {
            font-size: 0.7rem !important;
            padding: 0.2rem 0.5rem !important;
            white-space: normal !important;
            text-align: center !important;
            line-height: 1.2 !important;
        }
        .sales-invoice-compact .fi-ta-actions .fi-btn .fi-btn-icon {
            display: none !important;
        }
    </style>
    <div class="flex items-center justify-end mb-4">
        <span class="text-sm font-medium text-gray-600">
            Total Records: <span class="font-bold text-gray-900">{{ number_format($this->getTableRecords()->total()) }}</span>
        </span>
    </div>
    {{ $this->table }}
    @if ($this->getTableRecords()->total() > 0 && $this->getTableRecords()->lastPage() > 1)
        <div class="mt-4 text-sm text-center text-gray-600">
            Page {{ $this->getTableRecords()->currentPage() }} of {{ $this->getTableRecords()->lastPage() }}
        </div>
    @endif
</div>
