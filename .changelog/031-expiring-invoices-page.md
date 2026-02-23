# Change #031: Expiring Invoices Page with Filament Table Builder

> **Date**: 2026-02-23
> **Type**: New Feature + Database + UI
> **Status**: Completed

---

## Summary

**What**:
1. Built the Expiring Invoices billing sub-page showing invoice line items about to expire
2. Created denormalized `hr_sales_invoice_items` table (one row per product line item) to support Filament Table Builder
3. Filament Table Builder with filters: Currency, Sales Person, Product, and "Expiring In" dropdown (1/2/3 months, default 3)
4. Columns: Invoice No (clickable), Date, Company Name (clickable), Invoice Amount, Unit, Payer Name, Product Name, Start Date, Expiry Date, Created By
5. Export CSV button respects active filters
6. Compact table styling matching the Sales of Invoice page

**Why**: Provide a dedicated page to monitor invoices expiring soon, enabling proactive renewal follow-up. Uses the same Filament Table Builder design as the Sales of Invoice page for consistency.

**Breaking Change**: Yes (migration for `hr_sales_invoice_items` table)

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `database/migrations/2026_02_23_144300_create_hr_sales_invoice_items_table.php` | New | Migration creating denormalized `hr_sales_invoice_items` table with indexes on end_date, status, invoice_no |
| `app/Models/HrSalesInvoiceItem.php` | New | Eloquent model with fillable, date/decimal casts, belongsTo HrSalesInvoice |
| `app/Livewire/HrAdminDashboard/ExpiringInvoicesTable.php` | New | Filament Table Builder Livewire component with filters (Currency, Sales Person, Product, Expiring In), searchable/sortable columns, CSV export |
| `resources/views/livewire/hr-admin-dashboard/expiring-invoices-table.blade.php` | Rewritten | Compact Filament table layout with Export CSV button and total records count |
| `resources/views/filament/pages/hr-billing-expiring-invoices.blade.php` | Modified | Replaced "coming soon" placeholder with @livewire directive |
| `database/seeders/HrSalesInvoiceSeeder.php` | Modified | Added `createLineItemRecords()` to populate hr_sales_invoice_items alongside hr_sales_invoices |
