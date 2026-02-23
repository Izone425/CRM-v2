# Change #032: Expiring Invoices Back Navigation

> **Date**: 2026-02-23
> **Type**: Fix/Change + UI
> **Status**: Completed

---

## Summary

**What**:
1. Invoice links from Expiring Invoices page now pass `from=expiring-invoices` instead of `from=billing`
2. ViewSalesInvoice breadcrumbs show `Expiring Invoices > Sales Invoice` when navigated from Expiring Invoices
3. Back button navigates to `/admin/hr-billing-expiring-invoices` when `from=expiring-invoices`

**Why**: Previously, clicking an invoice from the Expiring Invoices page then pressing Back would navigate to the Sales of Invoice page instead of back to Expiring Invoices.

**Breaking Change**: No

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `app/Livewire/HrAdminDashboard/ExpiringInvoicesTable.php` | Modified | Changed `from` parameter in invoice URL from `billing` to `expiring-invoices` |
| `app/Filament/Pages/ViewSalesInvoice.php` | Modified | Added breadcrumb handling for `from=expiring-invoices` → `Expiring Invoices > Sales Invoice` |
| `app/Livewire/HrAdminDashboard/ViewSalesInvoice.php` | Modified | Added `goBack()` case for `from=expiring-invoices` → redirects to `/admin/hr-billing-expiring-invoices` |
