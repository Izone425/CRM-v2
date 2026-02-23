# Change #033: Expiring Invoices Payer Name Clickable Link

> **Date**: 2026-02-23
> **Type**: Fix/Change + UI
> **Status**: Completed

---

## Summary

**What**:
1. Payer Name column on Expiring Invoices table is now clickable (blue/primary color)
2. Clicking navigates to Company Details page (`hr-company-license-details`)

**Why**: Payer Name should be a quick link to the company details page, consistent with how Company Name works on other tables.

**Breaking Change**: No

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `app/Livewire/HrAdminDashboard/ExpiringInvoicesTable.php` | Modified | Added `->color('primary')` and `->url()` to the `payer_name` virtual column, linking to Company Details |
