# Change #010: Order Table Improvements - Delete Icon and Clean Product Names

> **Date**: 2026-02-13
> **Type**: Fix/Change + UI
> **Status**: Completed

---

## Summary

**What**: Two improvements to the Order table on the Add Sales Invoice page: (1) Replaced the minus character ("-") delete button with a proper trash/bin icon, and made it available for ALL rows (previously only rows 6+). (2) Removed bracket text from product names (e.g., "TimeTec Attendance (1 User License)" -> "TimeTec Attendance").

**Why**: The minus button was small and unclear. Users need to be able to delete any row, not just additional ones. Product names with "(1 User License)" were unnecessarily long in the table.

**Impact**: UI improvement. Minimum 1 row is always preserved (cannot delete the last row).

**Breaking Change**: No

---

## What It Does (Plain English)

### Delete Icon Changes

1. **Before**: Only rows with index >= 5 (6th row onward) had a small minus ("-") button
2. **After**: All rows have a trash icon button, any row can be deleted as long as at least 1 row remains

### Product Name Changes

1. **Before**: Products shown as "TimeTec Attendance (1 User License)", "TimeTec Payroll (1 Payroll License)"
2. **After**: Products shown as "TimeTec Attendance", "TimeTec Payroll" (clean names)

### Before vs After (Behavior)

| Before | After |
|--------|-------|
| Minus character ("-") button | Trash/bin SVG icon |
| Delete only available for rows 6+ | Delete available for all rows |
| Minimum 5 rows enforced | Minimum 1 row enforced |
| "TimeTec Attendance (1 User License)" | "TimeTec Attendance" |
| "TimeTec Payroll (1 Payroll License)" | "TimeTec Payroll" |

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `app/Livewire/HrAdminDashboard/AddSalesInvoiceForm.php` | Modified | Changed `removeItemRow()` guard from `> 5` to `> 1`; cleaned product names in `initializeOrderItems()` |
| `resources/views/livewire/hr-admin-dashboard/add-sales-invoice-form.blade.php` | Modified | Replaced minus button with trash icon SVG; removed `@if($index >= 5)` condition |

---

## Code Changes (BEFORE -> AFTER)

---

### File: `app/Livewire/HrAdminDashboard/AddSalesInvoiceForm.php`

**Change Type**: Modified

**What Changed**: Updated `removeItemRow()` guard and cleaned product names

#### BEFORE (removeItemRow)
```php
public function removeItemRow(int $index): void
{
    if (count($this->orderItems) > 5) {
        array_splice($this->orderItems, $index, 1);
        $this->orderItems = array_values($this->orderItems);
        $this->recalculateItemTotals();
    }
}
```

#### AFTER (removeItemRow)
```php
public function removeItemRow(int $index): void
{
    if (count($this->orderItems) > 1) {
        array_splice($this->orderItems, $index, 1);
        $this->orderItems = array_values($this->orderItems);
        $this->recalculateItemTotals();
    }
}
```

#### BEFORE (initializeOrderItems - product names)
```php
$products = [
    ['name' => 'TimeTec Attendance (1 User License)', 'unit_price' => 5.00],
    ['name' => 'TimeTec Leave (1 User License)', 'unit_price' => 5.00],
    ['name' => 'TimeTec Claim (1 User License)', 'unit_price' => 5.00],
    ['name' => 'TimeTec Payroll (1 Payroll License)', 'unit_price' => 5.00],
    ['name' => 'TimeTec Appraisal (1 User License)', 'unit_price' => 5.00],
];
```

#### AFTER (initializeOrderItems - product names)
```php
$products = [
    ['name' => 'TimeTec Attendance', 'unit_price' => 5.00],
    ['name' => 'TimeTec Leave', 'unit_price' => 5.00],
    ['name' => 'TimeTec Claim', 'unit_price' => 5.00],
    ['name' => 'TimeTec Payroll', 'unit_price' => 5.00],
    ['name' => 'TimeTec Appraisal', 'unit_price' => 5.00],
];
```

---

### File: `resources/views/livewire/hr-admin-dashboard/add-sales-invoice-form.blade.php`

**Change Type**: Modified

**What Changed**: Replaced minus button with trash icon, removed index >= 5 condition

#### BEFORE (Action column)
```blade
{{-- Action --}}
<td class="px-1 py-2 text-center">
    @if($index >= 5)
        <button type="button" wire:click="removeItemRow({{ $index }})"
            class="w-6 h-6 rounded-full bg-red-100 text-red-600 hover:bg-red-200 text-sm font-bold leading-none"
            title="Remove row">
            &minus;
        </button>
    @endif
</td>
```

#### AFTER (Action column)
```blade
{{-- Action --}}
<td class="px-1 py-2 text-center">
    <button type="button" wire:click="removeItemRow({{ $index }})"
        class="w-6 h-6 inline-flex items-center justify-center rounded text-red-400 hover:text-red-600 hover:bg-red-50 transition-colors"
        title="Delete row">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
        </svg>
    </button>
</td>
```

---

## Testing Notes

### How to Test

1. Navigate to Add Sales Invoice page
2. Verify all 5 default rows show trash icon in the last column
3. Click trash icon on any row - verify it gets deleted
4. Verify at least 1 row always remains (cannot delete the last row)
5. Click "+ Add Item" to add rows, verify new rows also have trash icon
6. Verify product names show "TimeTec Attendance" (no bracket text)

---

## Completion Checklist

- [x] Code changes reviewed
- [x] BEFORE/AFTER code documented
- [x] Tested locally
- [x] Ready for developer handoff
