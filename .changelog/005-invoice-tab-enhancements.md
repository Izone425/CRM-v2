# Change #005: Invoice Tab - Dummy Records, Clickable Navigation & UI Cleanup

> **Date**: 2026-02-09
> **Type**: New Feature + UI
> **Status**: Completed

---

## Summary

**What**: Enhanced the Company Details > Invoice tab with 13 dummy invoice records, made Invoice No clickable to navigate to the invoice view page, removed the Action column, and fixed status color styling.

**Why**: The Invoice tab needed sample data to demonstrate layout, and users should be able to click an Invoice No to view the full invoice details without a separate Action button.

**Impact**: Invoice tab now shows 13 dummy records with proper styling. Clicking any Invoice No navigates to the ViewSalesInvoice page with all relevant data passed via query params.

**Breaking Change**: No

---

## What It Does (Plain English)

### The Flow

1. **When this happens**: Admin views the Invoice tab under Company License Details
2. **The system does this**: Loads real invoices from database, then appends 13 hardcoded dummy records
3. **Then**: Displays all invoices in a table with clickable Invoice No links
4. **When user clicks Invoice No**: Navigates to ViewSalesInvoice page, passing invoice data (total, currency, status, dates) via URL query params
5. **ViewSalesInvoice page**: Receives params through Filament Page wrapper -> Livewire component, builds invoice view from params

### Before vs After (Behavior)

| Before | After |
|--------|-------|
| Invoice tab showed only real database records (often empty) | 13 dummy records appended for demo purposes |
| Invoice No was plain text | Invoice No is blue, clickable, navigates to invoice view |
| Action column with "View" button | Action column removed entirely |
| Cancel status showed in yellow | Cancel status shows in red |
| ViewSalesInvoice only worked with quotation-based invoices | ViewSalesInvoice works with param-based dummy invoices too |

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `app/Livewire/HrAdminDashboard/CompanyInvoiceTab.php` | Modified | Added `appendDummyRecords()` and `viewInvoiceByNo()` methods |
| `resources/views/livewire/hr-admin-dashboard/company-invoice-tab.blade.php` | Modified | Clickable Invoice No, removed Action column, fixed status colors |
| `app/Filament/Pages/ViewSalesInvoice.php` | Modified | Added 5 extra query param properties (total, currency, status, invoiceDate, dueDate) |
| `resources/views/filament/pages/view-sales-invoice.blade.php` | Modified | Passes extra params to Livewire component |
| `app/Livewire/HrAdminDashboard/ViewSalesInvoice.php` | Modified | Added param properties, `buildInvoiceFromParams()`, fixed `goBack()` to Invoice tab |

---

## Code Changes (BEFORE -> AFTER)

---

### File: `app/Livewire/HrAdminDashboard/CompanyInvoiceTab.php`

**Change Type**: Modified

**What Changed**: Added dummy records and invoice-by-no navigation

#### NEW: `appendDummyRecords()` method
```php
protected function appendDummyRecords(): array
{
    return [
        ['invoice_no' => 'TT2408000355', 'invoice_date' => '2024-08-29', 'due_date' => '2024-09-05', 'description' => 'TimeTec License Purchase', 'total' => 110.00, 'currency' => 'USD', 'status' => 'Paid', 'quotation_id' => null],
        ['invoice_no' => 'TT2409000134', 'invoice_date' => '2024-09-13', 'due_date' => '2024-09-20', 'description' => 'TimeTec License Purchase', 'total' => 50.00, 'currency' => 'USD', 'status' => 'Cancel', 'quotation_id' => null],
        // ... 11 more records
        ['invoice_no' => 'TT2602000032', 'invoice_date' => '2026-02-03', 'due_date' => '2026-02-10', 'description' => 'TimeTec License Purchase', 'total' => 1.08, 'currency' => 'MYR', 'status' => 'Pending', 'quotation_id' => null],
    ];
}
```

#### NEW: `viewInvoiceByNo()` method
```php
public function viewInvoiceByNo(string $invoiceNo, float $total, string $currency, string $status, string $invoiceDate, ?string $dueDate = null): void
{
    $this->redirect(
        url('/admin/view-sales-invoice?' . http_build_query([
            'invoiceNo' => $invoiceNo,
            'softwareHandoverId' => $this->softwareHandoverId,
            'total' => $total,
            'currency' => $currency,
            'status' => $status,
            'invoiceDate' => $invoiceDate,
            'dueDate' => $dueDate,
        ])),
        navigate: false
    );
}
```

#### MODIFIED: `loadInvoicesFromLocalData()` - appends dummy records
```php
// Append dummy records
$allInvoices = array_merge($allInvoices, $this->appendDummyRecords());
```

---

### File: `resources/views/livewire/hr-admin-dashboard/company-invoice-tab.blade.php`

**Change Type**: Modified

**What Changed**: Removed Action column, made Invoice No clickable, fixed Cancel status color

#### BEFORE (Invoice No cell)
```blade
<td class="px-3 py-2.5 whitespace-nowrap text-sm text-gray-700 text-center">
    {{ $invoice['invoice_no'] ?? '-' }}
</td>
```

#### AFTER (Invoice No cell - clickable)
```blade
<td class="px-3 py-2.5 whitespace-nowrap text-center">
    @if(!empty($invoice['quotation_id']))
        <span wire:click="viewInvoice({{ $invoice['quotation_id'] }})"
            class="text-sm text-blue-600 font-medium cursor-pointer hover:underline">
            {{ $invoice['invoice_no'] ?? '-' }}
        </span>
    @else
        <span wire:click="viewInvoiceByNo('{{ $invoice['invoice_no'] ?? '' }}', {{ $invoice['total'] ?? 0 }}, '{{ $invoice['currency'] ?? 'MYR' }}', '{{ $invoice['status'] ?? 'Pending' }}', '{{ $invoice['invoice_date'] ?? '' }}', '{{ $invoice['due_date'] ?? '' }}')"
            class="text-sm text-blue-600 font-medium cursor-pointer hover:underline">
            {{ $invoice['invoice_no'] ?? '-' }}
        </span>
    @endif
</td>
```

#### REMOVED: Action column header and body cell
```blade
<!-- REMOVED from <thead> -->
<th scope="col" class="...">Action</th>

<!-- REMOVED from <tbody> -->
<td class="...">
    <button wire:click="viewInvoice(...)">View</button>
</td>
```

#### CHANGED: Status colors
```blade
<!-- Cancel/Cancelled now shows red instead of yellow -->
@elseif($status === 'cancel' || $status === 'cancelled' || $status === 'unpaid')
    <span class="text-sm font-semibold text-red-600">{{ ucfirst($invoice['status'] ?? 'Cancel') }}</span>
@elseif($status === 'pending')
    <span class="text-sm font-semibold text-red-600">Pending</span>
```

---

### File: `app/Filament/Pages/ViewSalesInvoice.php`

**Change Type**: Modified

**What Changed**: Added 5 extra public properties and query param extraction

#### BEFORE
```php
public ?int $quotationId = null;
public ?int $softwareHandoverId = null;
public ?string $invoiceNo = null;

public function mount(): void
{
    $this->quotationId = Request::query('quotationId') ? (int) Request::query('quotationId') : null;
    $this->softwareHandoverId = Request::query('softwareHandoverId') ? (int) Request::query('softwareHandoverId') : null;
    $this->invoiceNo = Request::query('invoiceNo') ? (string) Request::query('invoiceNo') : null;
}
```

#### AFTER
```php
public ?int $quotationId = null;
public ?int $softwareHandoverId = null;
public ?string $invoiceNo = null;
public ?float $total = null;          // NEW
public ?string $currency = null;       // NEW
public ?string $status = null;         // NEW
public ?string $invoiceDate = null;    // NEW
public ?string $dueDate = null;        // NEW

public function mount(): void
{
    $this->quotationId = Request::query('quotationId') ? (int) Request::query('quotationId') : null;
    $this->softwareHandoverId = Request::query('softwareHandoverId') ? (int) Request::query('softwareHandoverId') : null;
    $this->invoiceNo = Request::query('invoiceNo') ? (string) Request::query('invoiceNo') : null;
    $this->total = Request::query('total') ? (float) Request::query('total') : null;           // NEW
    $this->currency = Request::query('currency') ? (string) Request::query('currency') : null;  // NEW
    $this->status = Request::query('status') ? (string) Request::query('status') : null;        // NEW
    $this->invoiceDate = Request::query('invoiceDate') ? (string) Request::query('invoiceDate') : null; // NEW
    $this->dueDate = Request::query('dueDate') ? (string) Request::query('dueDate') : null;    // NEW
}
```

---

### File: `resources/views/filament/pages/view-sales-invoice.blade.php`

**Change Type**: Modified

**What Changed**: Passes extra params to Livewire component

#### BEFORE
```blade
<livewire:hr-admin-dashboard.view-sales-invoice
    :quotation-id="$quotationId"
    :software-handover-id="$softwareHandoverId"
    :invoice-no="$invoiceNo"
/>
```

#### AFTER
```blade
<livewire:hr-admin-dashboard.view-sales-invoice
    :quotation-id="$quotationId"
    :software-handover-id="$softwareHandoverId"
    :invoice-no="$invoiceNo"
    :total="$total"
    :currency="$currency"
    :status="$status"
    :invoice-date="$invoiceDate"
    :due-date="$dueDate"
/>
```

---

### File: `app/Livewire/HrAdminDashboard/ViewSalesInvoice.php`

**Change Type**: Modified

**What Changed**: Added param properties, `buildInvoiceFromParams()`, fixed `goBack()` tab

#### NEW: Extra param properties
```php
public ?float $paramTotal = null;
public ?string $paramCurrency = null;
public ?string $paramStatus = null;
public ?string $paramInvoiceDate = null;
public ?string $paramDueDate = null;
```

#### MODIFIED: `mount()` accepts and stores extra params
```php
public function mount(
    ?int $quotationId = null,
    ?int $softwareHandoverId = null,
    ?string $invoiceNo = null,
    ?float $total = null,        // NEW
    ?string $currency = null,    // NEW
    ?string $status = null,      // NEW
    ?string $invoiceDate = null, // NEW
    ?string $dueDate = null,     // NEW
): void {
    // ...
    $this->paramTotal = $total;
    $this->paramCurrency = $currency;
    $this->paramStatus = $status;
    $this->paramInvoiceDate = $invoiceDate;
    $this->paramDueDate = $dueDate;
}
```

#### NEW: `buildInvoiceFromParams()` method
```php
protected function buildInvoiceFromParams(string $companyName, string $companyAddress, string $email): void
{
    $total = $this->paramTotal ?? 0;
    $currency = $this->paramCurrency ?? 'MYR';
    $status = strtolower($this->paramStatus ?? 'pending');
    $invoiceDate = $this->paramInvoiceDate ?? date('Y-m-d');

    $this->items = [[ /* single line item built from params */ ]];
    $this->invoice = [
        'reference_no' => $this->invoiceNo,
        'status' => $status === 'paid' ? 'paid' : ($status === 'cancel' ? 'cancelled' : 'pending'),
        // ... totals, customer info, etc.
    ];
    $this->isLoading = false;
}
```

#### MODIFIED: `loadInvoiceByInvoiceNo()` fallback
```php
if (empty($licenseRecords)) {
    if ($this->paramTotal !== null) {
        $this->buildInvoiceFromParams($companyName, $companyAddress, $email);
        return;
    }
    // ... error handling
}
```

#### MODIFIED: `goBack()` returns to Invoice tab
```php
// BEFORE
url('/admin/hr-company-license-details?softwareHandoverId=' . $this->softwareHandoverId . '&tab=products')

// AFTER
url('/admin/hr-company-license-details?softwareHandoverId=' . $this->softwareHandoverId . '&tab=invoice')
```

---

## Testing Notes

### How to Test

1. Navigate to Company License Details > **Invoice** tab
2. Verify 13 dummy invoice records appear in the table
3. Verify "Cancel" status shows in **red** text
4. Verify "Paid" status shows in **green** text
5. Verify "Pending" status shows in **red** text
6. Click any Invoice No - verify it navigates to ViewSalesInvoice page
7. Verify the invoice view shows correct data (total, currency, status, dates)
8. Click "Back" button - verify it returns to Invoice tab (not Products tab)
9. Test with invoices TT2412000246 or TT2601000335 - these have mock license records and show detailed line items

---

## Completion Checklist

- [x] Code changes reviewed
- [x] BEFORE/AFTER code documented
- [x] Tested locally
- [x] Ready for developer handoff
