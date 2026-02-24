<div class="p-4 bg-white rounded-lg shadow-lg payment-received-compact" style="height: auto;">
    <style>
        .payment-received-compact .fi-ta-table {
            table-layout: fixed;
            width: 100%;
        }
        .payment-received-compact .fi-ta-cell,
        .payment-received-compact .fi-ta-header-cell {
            padding: 0.35rem 0.4rem !important;
            font-size: 0.75rem !important;
        }
        .payment-received-compact .fi-ta-header-cell-label {
            font-size: 0.7rem !important;
        }
        .payment-received-compact .fi-ta-text-item,
        .payment-received-compact .fi-ta-text-item span {
            font-size: 0.75rem !important;
        }
        .payment-received-compact .fi-badge {
            font-size: 0.65rem !important;
            padding: 0.15rem 0.4rem !important;
        }
        /* Column widths: Date(7%), Invoice(9%), DocNo(9%), Company(14%), Subscriber(11%), Method(8%), RefNo(8%), ACInvoice(16%), Currency(5%), Amount(7%) */
        .payment-received-compact .fi-ta-header-cell:nth-child(1),
        .payment-received-compact .fi-ta-cell:nth-child(1) { width: 7%; }
        .payment-received-compact .fi-ta-header-cell:nth-child(2),
        .payment-received-compact .fi-ta-cell:nth-child(2) { width: 9%; }
        .payment-received-compact .fi-ta-header-cell:nth-child(3),
        .payment-received-compact .fi-ta-cell:nth-child(3) { width: 9%; }
        .payment-received-compact .fi-ta-header-cell:nth-child(4),
        .payment-received-compact .fi-ta-cell:nth-child(4) { width: 14%; }
        .payment-received-compact .fi-ta-header-cell:nth-child(5),
        .payment-received-compact .fi-ta-cell:nth-child(5) { width: 11%; }
        .payment-received-compact .fi-ta-header-cell:nth-child(6),
        .payment-received-compact .fi-ta-cell:nth-child(6) { width: 8%; }
        .payment-received-compact .fi-ta-header-cell:nth-child(7),
        .payment-received-compact .fi-ta-cell:nth-child(7) { width: 8%; word-break: break-all; }
        .payment-received-compact .fi-ta-header-cell:nth-child(8),
        .payment-received-compact .fi-ta-cell:nth-child(8) { width: 16%; overflow: visible; }
        .payment-received-compact .fi-ta-header-cell:nth-child(9),
        .payment-received-compact .fi-ta-cell:nth-child(9) { width: 5%; }
        .payment-received-compact .fi-ta-header-cell:nth-child(10),
        .payment-received-compact .fi-ta-cell:nth-child(10) { width: 7%; text-align: right; }
    </style>
    <p class="mb-3 text-sm text-gray-500">Payment received from customers via admin payment entry, PayPal, or Razer payment links.</p>
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
