# Change #019: Simplify Official Receipt Modal

> **Date**: 2026-02-19
> **Type**: Fix/Change + UI
> **Status**: Completed

---

## Summary

**What**: Simplified the Add Official Receipt modal to only 4 fields: Company, Total Amount, License Number, and Autocount Invoice (13 char max, uppercase). Removed bill_title, payment_method, ref_no, remark fields. Removed cyan description text from modal header.

**Why**: Streamline the receipt form to only essential fields.

**Breaking Change**: No

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `app/Livewire/HrAdminDashboard/ViewSalesInvoice.php` | Modified | Removed bill_title, payment_method, ref_no, remark from paymentForm; added license_number, autocount_invoice |
| `resources/views/livewire/hr-admin-dashboard/view-sales-invoice.blade.php` | Modified | Replaced modal form fields; Autocount Invoice input with maxlength=13 and forced uppercase; removed cyan description text |
