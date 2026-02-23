# Change #034: Official Receipt List Page

> **Date**: 2026-02-23
> **Type**: New Feature + Database + UI
> **Status**: Completed

---

## Summary

**What**:
1. New `hr_official_receipts` database table to store official receipt records
2. New `HrOfficialReceipt` model with fillable fields and casts
3. New `HrOfficialReceiptTable` Livewire component using Filament Table Builder
4. Compact table design matching Sales of Invoice page style
5. Filters: Status, Currency, Date Range (DatePicker from/to)
6. Columns: O/R No (clickable), Date, Company Name (clickable to Company Details), Description, Currency, Amount, Status (badge), Created By
7. CSV export with filtered data
8. Seeder generates receipts from all PAID invoices in `hr_sales_invoices`

**Why**: Official Receipt page needed under Billing navigation to show receipts generated when payment is received for invoices.

**Breaking Change**: Yes (migration required)

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `database/migrations/2026_02_23_162320_create_hr_official_receipts_table.php` | New | Creates `hr_official_receipts` table with indexes on or_no, receipt_date, invoice_no |
| `app/Models/HrOfficialReceipt.php` | New | Eloquent model with fillable fields and date/decimal casts |
| `app/Livewire/HrAdminDashboard/HrOfficialReceiptTable.php` | New | Filament Table Builder component with filters, columns, and CSV export |
| `resources/views/livewire/hr-admin-dashboard/hr-official-receipt-table.blade.php` | New | Compact table blade template with Export CSV button and record count |
| `resources/views/filament/pages/hr-billing-official-receipt.blade.php` | Modified | Updated from placeholder to load Livewire table component |
| `database/seeders/HrOfficialReceiptSeeder.php` | New | Seeds receipts from PAID invoices with OR number format: OR + YYMM + 6-digit counter |
| `app/Filament/Pages/ViewSalesInvoice.php` | Modified | Added breadcrumb handling for `from=official-receipt` |
| `app/Livewire/HrAdminDashboard/ViewSalesInvoice.php` | Modified | Added `goBack()` case for `from=official-receipt` |
