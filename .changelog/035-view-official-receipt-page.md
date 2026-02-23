# Change #035: View Official Receipt Page

> **Date**: 2026-02-23
> **Type**: New Feature + Database + UI
> **Status**: Completed

---

## Summary

**What**:
1. New `ViewOfficialReceipt` Filament page at `/admin/view-official-receipt?orNo=...`
2. New `ViewOfficialReceipt` Livewire component that loads receipt and linked invoice data
3. Receipt document layout with TimeTec branding:
   - Company header (logo + address)
   - "OFFICIAL RECEIPT" centered title with horizontal rules
   - "Received From" section (left) + Doc Info box (right: Doc No, Date, Status, Received In)
   - Items table (No., Description, Total) with invoice sub-table
   - Totals section (Subtotal, Taxable Amount, Total Inclusive Tax)
   - Terms & Conditions with UOB bank details
4. Back button navigates to Official Receipt list
5. Print button prints only the receipt document
6. Added `payment_method` column to `hr_official_receipts` table
7. O/R No in receipt list now links to this view page instead of ViewSalesInvoice

**Why**: Clicking an O/R No from the Official Receipt list should show a dedicated receipt document view, not the sales invoice view.

**Breaking Change**: Yes (migration required for payment_method column)

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `app/Filament/Pages/ViewOfficialReceipt.php` | New | Filament page with breadcrumbs: Official Receipt > {OR No} |
| `resources/views/filament/pages/view-official-receipt.blade.php` | New | Filament page view passing params to Livewire component |
| `app/Livewire/HrAdminDashboard/ViewOfficialReceipt.php` | New | Livewire component loading receipt + linked invoice data |
| `resources/views/livewire/hr-admin-dashboard/view-official-receipt.blade.php` | New | Receipt document template with print styles |
| `database/migrations/2026_02_23_164603_add_payment_method_to_hr_official_receipts_table.php` | New | Adds `payment_method` nullable string column |
| `app/Models/HrOfficialReceipt.php` | Modified | Added `payment_method` to `$fillable` |
| `database/seeders/HrOfficialReceiptSeeder.php` | Modified | Populates `payment_method` mapped from invoice (BANK TRANSFER, CREDIT CARD, CHEQUE) |
| `app/Livewire/HrAdminDashboard/HrOfficialReceiptTable.php` | Modified | O/R No URL changed from `view-sales-invoice` to `view-official-receipt` |
| `app/Providers/Filament/AdminPanelProvider.php` | Modified | Registered `ViewOfficialReceipt::class` in pages array |
