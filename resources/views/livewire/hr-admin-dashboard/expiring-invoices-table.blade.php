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
    </style>
    <p class="mb-3 text-sm text-gray-500">Sales Invoice is created when customer purchase product from company.</p>
    <div class="flex items-center justify-between mb-4">
        <button
            wire:click="exportCsv"
            wire:loading.attr="disabled"
            class="px-4 py-2 text-sm font-medium text-white transition-colors bg-green-500 rounded-lg hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-50"
        >
            <span wire:loading.remove wire:target="exportCsv">Export CSV</span>
            <span wire:loading wire:target="exportCsv">Exporting...</span>
        </button>
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
