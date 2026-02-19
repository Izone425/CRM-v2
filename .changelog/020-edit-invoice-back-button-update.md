# Change #020: Edit Invoice: Update Button + Return URL Back Navigation

> **Date**: 2026-02-19
> **Type**: Fix/Change + UI
> **Status**: Completed

---

## Summary

**What**: When editing an invoice: (1) "Create Invoice" button changes to "Update Invoice", (2) Back button returns to the view-sales-invoice page via returnUrl parameter chain, (3) mode is set to 'edit' when prefillInvoiceNo is present, (4) removed duplicate "Edit Invoice >> TTC..." heading from blade form.

**Why**: Edit mode should show appropriate button text and navigate back to the source page.

**Breaking Change**: No

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `app/Livewire/HrAdminDashboard/ViewSalesInvoice.php` | Modified | `editInvoice()` builds returnUrl and includes it in redirect |
| `app/Filament/Pages/AddSalesInvoice.php` | Modified | Added `$returnUrl` property, reads from query params |
| `resources/views/filament/pages/add-sales-invoice.blade.php` | Modified | Passes `:return-url` prop to Livewire component |
| `app/Livewire/HrAdminDashboard/AddSalesInvoiceForm.php` | Modified | Added `$returnUrl` property, mount accepts it, `goBack()` prioritizes returnUrl, sets mode='edit' for prefillInvoiceNo |
| `resources/views/livewire/hr-admin-dashboard/add-sales-invoice-form.blade.php` | Modified | Removed duplicate heading, shows "Update Invoice" in edit mode, simplified Customer Info for edit mode |
