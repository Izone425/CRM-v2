# Change #027: Billing Navigation with 7 Sub-Items and Sales Invoice Page

> **Date**: 2026-02-20
> **Type**: New Feature + Database + UI
> **Status**: Completed

---

## Summary

**What**:
1. Added "Billing" as an expandable section in the sidebar with 7 sub-items: Sales Invoice, Expiring Invoices, Official Receipt, Commission, Payment, Auto Renewal, Credit Notes
2. Built the "Sales of Invoice" page (`/admin/hr-billing-sales-invoice`) with a full Filament v3 data table
3. Table features: searchable, sortable, status filter, status badges, clickable invoice no / company / reseller links, status-based action buttons (Add Payment / View Receipt), compact CSS for no horizontal scrolling
4. Created `hr_sales_invoices` table with migration and seeder

**Why**: Centralize billing management under a dedicated navigation section with the Sales Invoice page as the first functional sub-item.

**Breaking Change**: Yes (migration required)

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `app/Filament/Pages/HrBilling.php` | New | Billing landing page |
| `app/Filament/Pages/HrBillingSalesInvoice.php` | New | Sales of Invoice page |
| `app/Filament/Pages/HrBillingExpiringInvoices.php` | New | Expiring Invoices placeholder |
| `app/Filament/Pages/HrBillingOfficialReceipt.php` | New | Official Receipt placeholder |
| `app/Filament/Pages/HrBillingCommission.php` | New | Commission placeholder |
| `app/Filament/Pages/HrBillingPayment.php` | New | Payment placeholder |
| `app/Filament/Pages/HrBillingAutoRenewal.php` | New | Auto Renewal placeholder |
| `app/Filament/Pages/HrBillingCreditNotes.php` | New | Credit Notes placeholder |
| `app/Livewire/HrAdminDashboard/HrSalesInvoiceTable.php` | New | Livewire component with Filament table for sales invoices |
| `app/Models/HrSalesInvoice.php` | New | Eloquent model for hr_sales_invoices |
| `database/migrations/2026_02_20_155148_create_hr_sales_invoices_table.php` | New | Create hr_sales_invoices table |
| `database/migrations/2026_02_20_162813_add_reseller_columns_to_hr_sales_invoices_table.php` | New | Add reseller linkage columns |
| `database/seeders/HrSalesInvoiceSeeder.php` | New | Seed dummy sales invoice data (TT* + TTC* records) |
| `resources/views/filament/pages/hr-billing.blade.php` | New | Billing landing page view |
| `resources/views/filament/pages/hr-billing-sales-invoice.blade.php` | New | Sales Invoice page view |
| `resources/views/filament/pages/hr-billing-*.blade.php` | New | 5 placeholder billing sub-page views |
| `resources/views/livewire/hr-admin-dashboard/hr-sales-invoice-table.blade.php` | New | Sales Invoice table with compact CSS |
| `resources/views/layouts/custom-sidebar.blade.php` | Modified | Added Billing expandable section with 7 sub-items |
| `app/Providers/Filament/AdminPanelProvider.php` | Modified | Registered all 8 billing pages |
