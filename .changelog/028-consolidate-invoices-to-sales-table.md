# Change #028: Consolidate All Invoices into hr_sales_invoices Table

> **Date**: 2026-02-20
> **Type**: Fix/Change
> **Status**: Completed

---

## Summary

**What**:
1. Moved the 13 TTC* hardcoded dummy records from `CompanyInvoiceTab::appendDummyRecords()` into the `HrSalesInvoiceSeeder`
2. Removed the `appendDummyRecords()` method and its call from `CompanyInvoiceTab.php`
3. Invoice data in Company Details > Proforma Invoice tab now comes entirely from `hr_sales_invoices` via the `getHrSalesInvoices()` method

**Why**: Make `hr_sales_invoices` the single source of truth for all sales invoices, appearing in both the Sales of Invoice page and the Company License Details > Proforma Invoice tab.

**Breaking Change**: No

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `database/seeders/HrSalesInvoiceSeeder.php` | Modified | Added 13 TTC* records distributed across existing license companies |
| `app/Livewire/HrAdminDashboard/CompanyInvoiceTab.php` | Modified | Removed `appendDummyRecords()` method and its call in `loadInvoicesFromLocalData()` |
