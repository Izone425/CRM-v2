# 041 - Create Database Button with PI Items Drawer

## Summary
Added a new green "Create Database" button to the Software Handover Details popup, placed before "Export AutoCount Debtor". When clicked, it opens a right-side drawer displaying a read-only table of items from the SW+HW Proforma Invoice (Type 1). Also added eager-loading (`items.product`) to all 4 Quotation queries to prevent N+1 queries.

## Files Changed

### `resources/views/components/software-handover.blade.php` (MODIFIED)
- Added `->with(['items.product'])` to all 4 Quotation queries in the `@php` block (productPIs, softwareHardwarePIs, nonHrdfPIs, hrdfPIs)
- Added `$type1PIs` computed variable that selects the correct PI collection based on `training_type`
- Added "Create Database" button (green #16a34a, database SVG icon) as first child of `.sw-export-container`
- Added right-side drawer (500px wide) with:
  - Read-only table per PI: Product Code, Description, Qty, Unit Price, Total
  - PI reference number as section heading
  - "No data available" fallback when PI is empty
  - "Close" button in footer
- Uses proven Alpine.js pattern: `x-show` + `x-cloak` + `.stop.prevent` to prevent Filament modal interference

## Button order in export container:
1. **Create Database** (green filled) — NEW
2. **Export AutoCount Debtor** (green outline, existing)
3. **Export AutoCount Invoice** (blue, existing)

## Migration
No migration needed. No database changes.
