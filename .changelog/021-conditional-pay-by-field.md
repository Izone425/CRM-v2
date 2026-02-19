# Change #021: Conditional "Pay By" Field for Accounts Under Reseller/Distributor

> **Date**: 2026-02-19
> **Type**: New Feature + UI
> **Status**: Completed

---

## Summary

**What**: Added a "Pay By" dropdown (options: Subscriber, Reseller) to the Add Sales Invoice form. This field only appears when the account is under a Reseller or Distributor (determined by `SoftwareHandover.reseller_id` being set). In create mode, the first row becomes 3-column when Pay By is visible. In edit mode, the simplified layout also conditionally includes Pay By.

**Why**: When an account has a parent dealer, invoices need to specify who is paying - the subscriber or the reseller.

**Breaking Change**: No

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `app/Livewire/HrAdminDashboard/AddSalesInvoiceForm.php` | Modified | Added `$payBy` (default 'Subscriber') and `$isUnderDealer` (bool) properties; `loadCompanyData()` sets isUnderDealer from `$sw->reseller_id` |
| `resources/views/livewire/hr-admin-dashboard/add-sales-invoice-form.blade.php` | Modified | Create mode: conditional 3-column first row with Pay By when `$isUnderDealer`; Edit mode: conditional 3-column vs 2-column layout |
