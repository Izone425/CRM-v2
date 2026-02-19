# Change #015: Add 60 Months and Consolidate Billing Cycle Options

> **Date**: 2026-02-19
> **Type**: New Feature + UI
> **Status**: Completed

---

## Summary

**What**: Added two new options to the Billing Cycle dropdown on Add Sales Invoice: "60 Months" and "Consolidate". Consolidate calculates months from the item's start date to the active PAID license's end date, using the rounding rule: <15 remaining days = 0 extra months, >=15 days = +1 month. The end date is set to match the active license's end date.

**Why**: Users need a 60-month billing option and a way to align new licenses with existing active license periods (consolidation).

**Impact**: New billing cycle options. Consolidate dynamically shows calculated month count in the dropdown label.

**Breaking Change**: No

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `app/Livewire/HrAdminDashboard/CompanyProductsTab.php` | Modified | Added `maxPaidEndDate` property computed from PAID license records |
| `resources/views/livewire/hr-admin-dashboard/company-products-tab.blade.php` | Modified | Passes `activeLicenseEndDate` in Add License URL |
| `app/Filament/Pages/AddSalesInvoice.php` | Modified | Added `activeLicenseEndDate` property, reads from URL query |
| `resources/views/filament/pages/add-sales-invoice.blade.php` | Modified | Passes `:active-license-end-date` to Livewire component |
| `app/Livewire/HrAdminDashboard/AddSalesInvoiceForm.php` | Modified | Added `activeLicenseEndDate` property, `calculateConsolidateMonths()` helper, updated `recalculateItemTotals()` and `recalculateEndDates()` to handle consolidate |
| `resources/views/livewire/hr-admin-dashboard/add-sales-invoice-form.blade.php` | Modified | Added "60 Months" and "Consolidate (X Months)" options to both order table and bulk config dropdowns |

---

## Code Changes (BEFORE -> AFTER)

### Key new method: `calculateConsolidateMonths()`

```php
protected function calculateConsolidateMonths(string $startDate): int
{
    // Counts full months from start to active license end date
    // Remaining days >= 15 = +1 month, < 15 = +0
}
```

### Billing Cycle Dropdown

#### BEFORE
```blade
<option value="48">48 Months</option>
```

#### AFTER
```blade
<option value="48">48 Months</option>
<option value="60">60 Months</option>
@if($activeLicenseEndDate)
    <option value="consolidate">Consolidate ({{ $item['consolidate_months'] ?? 0 }} Months)</option>
@endif
```

### Data Flow
Products Tab → URL `&activeLicenseEndDate=2028-01-23` → Filament Page → Livewire Form → calculates months dynamically

---

## Completion Checklist

- [x] Code changes reviewed
- [x] BEFORE/AFTER code documented
- [x] Tested locally
- [x] Ready for developer handoff
