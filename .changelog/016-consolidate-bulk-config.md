# Change #016: Consolidate Billing in Bulk Config + Column Width Fix

> **Date**: 2026-02-19
> **Type**: Fix/Change + UI
> **Status**: Completed

---

## Summary

**What**: Added Consolidate option to the Bulk Configuration dropdown, widened Billing Cycle column (12%->14%), shortened label to "Consolidate (XXM)", changed bulk config to 5-column single-line layout, and added `bulkConsolidateMonths()` computed property.

**Why**: Consolidate option was cut off in the dropdown and missing from Bulk Configuration.

**Breaking Change**: No

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `app/Livewire/HrAdminDashboard/AddSalesInvoiceForm.php` | Modified | Added `bulkConsolidateMonths()` computed property, updated `applyBulkConfig()` to handle consolidate |
| `resources/views/livewire/hr-admin-dashboard/add-sales-invoice-form.blade.php` | Modified | Widened Billing Cycle column, shortened consolidate label, added consolidate to bulk config dropdown, 5-column bulk config layout |
