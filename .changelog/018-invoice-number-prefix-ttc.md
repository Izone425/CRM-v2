# Change #018: Invoice Number Prefix TT -> TTC

> **Date**: 2026-02-19
> **Type**: Fix/Change
> **Status**: Completed

---

## Summary

**What**: Changed all dummy invoice number prefixes from "TT" to "TTC" across Invoice tab, Products tab, and View Sales Invoice.

**Why**: Invoice numbers should use the correct "TTC" prefix format.

**Breaking Change**: No

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `app/Livewire/HrAdminDashboard/CompanyInvoiceTab.php` | Modified | Changed 13 dummy invoice number prefixes from TT to TTC |
| `app/Livewire/HrAdminDashboard/CompanyProductsTab.php` | Modified | Changed 12 occurrences of TT2412000246 to TTC2412000246 |
| `app/Livewire/HrAdminDashboard/ViewSalesInvoice.php` | Modified | Changed 12 occurrences of TT invoice numbers to TTC |
