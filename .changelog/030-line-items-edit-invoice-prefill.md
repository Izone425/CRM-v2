# Change #030: Line Items JSON Column and Edit Invoice Prefill

> **Date**: 2026-02-23
> **Type**: New Feature + Database
> **Status**: Completed

---

## Summary

**What**:
1. Added `line_items` JSON column to `hr_sales_invoices` table to store per-product breakdown (license_type, total_user, unit_price, month, start_date, end_date)
2. Edit Invoice form now prefills ALL line items from the stored JSON instead of a single row with the total amount
3. View Sales Invoice displays itemized products from `line_items` with correct quantities, prices, and periods
4. Edit Invoice button works for hr_sales_invoices records (new edit flow with returnUrl)
5. Seeder generates realistic line items for all invoices

**Why**: Previously, editing an invoice showed a single row with Units=1 and Price=total. Now each product line (e.g., TimeTec TA, Leave, Claim, Payroll) appears as its own row with correct units, unit price, and billing cycle.

**Breaking Change**: Yes (migration for `line_items` column)

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `database/migrations/2026_02_23_124737_add_line_items_to_hr_sales_invoices_table.php` | New | Migration adding `line_items` JSON column to hr_sales_invoices |
| `app/Models/HrSalesInvoice.php` | Modified | Added `line_items` to `$fillable` and `$casts` (as array) |
| `app/Livewire/HrAdminDashboard/AddSalesInvoiceForm.php` | Modified | Added `loadFromSalesInvoice()` method that maps line_items JSON to order item rows; `mount()` tries this before single-item fallback |
| `app/Livewire/HrAdminDashboard/ViewSalesInvoice.php` | Modified | `buildInvoiceFromSalesRecord()` now renders itemized products from line_items; new `editInvoice()` flow for hr_sales_invoices records with returnUrl |
| `database/seeders/HrSalesInvoiceSeeder.php` | Modified | Generates realistic line items (2-4 products) for all invoices; added `generateLineItems()`, `generateLineItemsForTotal()`, `calculateTotal()` helpers; ABC Technology invoice has explicit 4-product breakdown |
