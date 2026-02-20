# Change #029: Context-Aware Breadcrumbs and Back Navigation for ViewSalesInvoice

> **Date**: 2026-02-20
> **Type**: Fix/Change + UI
> **Status**: Completed

---

## Summary

**What**:
1. ViewSalesInvoice breadcrumbs now change based on navigation origin:
   - From Sales of Invoice page (`from=billing`): `Sales of Invoice > Sales Invoice`
   - From Company Details (`from=invoice`/`from=products`): `All Licenses > Company Details > Sales Invoice`
2. Back button navigates to the correct origin page based on `from` parameter
3. Invoice lookup now checks `hr_sales_invoices` table when hardcoded mock data doesn't match, fixing the "No license records found" error

**Why**: Provide correct navigation context when viewing an invoice from different entry points (Billing page vs Company Details).

**Breaking Change**: No

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `app/Filament/Pages/ViewSalesInvoice.php` | Modified | `getBreadcrumbs()` now returns different breadcrumbs for `from=billing` vs company detail origins |
| `app/Livewire/HrAdminDashboard/ViewSalesInvoice.php` | Modified | Added `HrSalesInvoice` import; `goBack()` handles `from=billing`; `loadInvoiceByInvoiceNo()` checks `hr_sales_invoices` table; new `buildInvoiceFromSalesRecord()` method |
