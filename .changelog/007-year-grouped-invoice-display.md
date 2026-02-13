# Change #007: Year-Grouped Item Display on ViewSalesInvoice and PDF Proforma Invoice

> **Date**: 2026-02-13
> **Type**: New Feature + UI
> **Status**: Completed

---

## Summary

**What**: Items on the ViewSalesInvoice page and the PDF proforma invoice are now grouped by year with year header rows (e.g., "Year 2025", "Year 2026", "Year 2027") and per-year item numbering that restarts at 1 for each year group.

**Why**: Multi-year subscriptions produce many rows. Grouping by year makes it easier for admins and customers to see the breakdown per subscription period.

**Impact**: UI change on ViewSalesInvoice page and PDF output. Mock data expanded from 8 records to 12 records (3 years x 4 products) all under one invoice number.

**Breaking Change**: No

---

## What It Does (Plain English)

### The Flow

1. **When this happens**: Admin views a sales invoice (ViewSalesInvoice page) or generates a PDF proforma invoice
2. **The system groups**: All line items by their `year` field (derived from `license_start_date`)
3. **For each year group**: A blue header row "Year XXXX" is rendered, followed by the items
4. **Numbering**: Restarts at 1 for each year group (not continuous across years)

### Before vs After (Behavior)

| Before | After |
|--------|-------|
| Items listed flat with continuous numbering | Items grouped by year with header rows |
| Single counter across all items | Per-year counter restarting at 1 |
| 8 mock records (2 different invoices) | 12 mock records (3 years x 4 products, same invoice) |
| PDF showed flat list | PDF shows year-grouped sections |

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `app/Livewire/HrAdminDashboard/ViewSalesInvoice.php` | Modified | Added `year` field to items array, expanded mock data to 12 records (3 years x 4 products) |
| `resources/views/livewire/hr-admin-dashboard/view-sales-invoice.blade.php` | Modified | Replaced flat `@forelse` with `collect()->groupBy('year')` and year header rows with per-year counter |
| `app/Http/Controllers/LicenseProformaInvoiceController.php` | Modified | Added `year` field to PDF item data |
| `resources/views/pdf/license-proforma-invoice.blade.php` | Modified | Added year-grouped rendering in PDF template |
| `app/Livewire/HrAdminDashboard/CompanyProductsTab.php` | Modified | Added `year` field, expanded mock data to 3 years, updated data source priority |
| `resources/views/livewire/hr-admin-dashboard/company-products-tab.blade.php` | Modified | Added year-grouped rendering in Products tab |

---

## Code Changes (BEFORE -> AFTER)

---

### File: `resources/views/livewire/hr-admin-dashboard/view-sales-invoice.blade.php`

**Change Type**: Modified

**What Changed**: Replaced flat item loop with year-grouped rendering

#### BEFORE (Items table body)
```blade
<tbody>
    @forelse($items as $index => $item)
        <tr class="border-b border-gray-200">
            <td class="border border-gray-200 px-3 py-2 text-center text-blue-600">{{ $index + 1 }}.</td>
            <td class="border border-gray-200 px-3 py-2">
                <span class="text-blue-600">TimeTec Suite- {{ $item['description'] }}</span>
                ...
            </td>
            ...
        </tr>
    @empty
        <tr><td colspan="7">No items found</td></tr>
    @endforelse
</tbody>
```

#### AFTER (Items table body)
```blade
<tbody>
    @php
        $groupedByYear = collect($items)->groupBy(function($item) {
            if (isset($item['year'])) return $item['year'];
            if (!empty($item['period'])) return substr($item['period'], 6, 4);
            return 'Other';
        });
    @endphp
    @forelse($groupedByYear as $yearLabel => $yearItems)
        {{-- Year Header Row --}}
        <tr style="background-color: #eff6ff;">
            <td colspan="7" class="px-4 py-2 text-sm font-semibold text-blue-800 border border-gray-200">
                Year {{ $yearLabel }}
            </td>
        </tr>
        @php $yearCounter = 0; @endphp
        @foreach($yearItems as $item)
            @php $yearCounter++; @endphp
            <tr class="border-b border-gray-200">
                <td class="border border-gray-200 px-3 py-2 text-center text-blue-600">{{ $yearCounter }}.</td>
                ...
            </tr>
        @endforeach
    @empty
        <tr><td colspan="7">No items found</td></tr>
    @endforelse
</tbody>
```

---

### File: `app/Livewire/HrAdminDashboard/ViewSalesInvoice.php`

**Change Type**: Modified

**What Changed**: Added `year` field to items array in `loadInvoiceByInvoiceNo()`, expanded mock data to 12 records

#### BEFORE (items array in loadInvoiceByInvoiceNo)
```php
$this->items[] = [
    'description' => $description,
    'period' => $period,
    ...
];
```

#### AFTER
```php
$this->items[] = [
    'year' => (int) date('Y', strtotime($startDate)),
    'description' => $description,
    'period' => $period,
    ...
];
```

---

## Testing Notes

### How to Test

1. Navigate to Products tab, click an invoice link (e.g., TT2412000246)
2. Verify items are grouped under "Year 2025", "Year 2026", "Year 2027" headers
3. Verify numbering restarts at 1 for each year group (1-4 per year for 4 products)
4. Generate PDF proforma invoice and verify same year grouping appears

---

## Completion Checklist

- [x] Code changes reviewed
- [x] BEFORE/AFTER code documented
- [x] Tested locally
- [x] Ready for developer handoff
