# 037 - Payment Page (List of Payment Received)

## Summary
Built the Billing > Payment page replacing the "coming soon" placeholder. Shows all paid invoices with 10 columns matching the old system's "List of Payment Received" page. For PayPal/Razer payment methods, an inline editable "AutoCount Invoice No." field with an "Update" button allows admin to enter the accounting system invoice number.

## Files Changed

### `database/migrations/2026_02_24_131317_add_payment_fields_to_hr_official_receipts_table.php` (NEW)
- Adds `subscriber_name` (string, nullable) after company_name
- Adds `ref_no` (string, nullable) after payment_method
- Adds `autocount_invoice_no` (string(20), nullable) after ref_no

### `app/Models/HrOfficialReceipt.php` (MODIFIED)
- Added `subscriber_name`, `ref_no`, `autocount_invoice_no` to $fillable

### `database/seeders/HrOfficialReceiptSeeder.php` (MODIFIED)
- Payment method distribution: ~60% Bank Transfer, ~15% PayPal, ~10% Razer, ~10% Point, ~5% other
- Populates ref_no for PayPal (hash-based) and Razer (ORD_ format)
- Populates autocount_invoice_no with EPIN/ERIN/EHIN prefixes
- Populates subscriber_name from dummy company names

### `app/Livewire/HrAdminDashboard/HrPaymentReceivedTable.php` (NEW)
- Livewire table component with 10 columns: Date, Invoice, Doc No, Company Name, Subscriber Name, Payment Method, Ref No., AutoCount Invoice No., Currency, Amount
- Filters: Payment Method, Currency, Date Range
- `updateAutocountInvoice()` method for inline edit
- CSV export with all columns

### `resources/views/livewire/hr-admin-dashboard/hr-payment-received-table.blade.php` (NEW)
- Compact CSS with table-layout fixed and nth-child column widths
- Export CSV button, Total Records count, Page X of Y

### `resources/views/livewire/hr-admin-dashboard/partials/autocount-invoice-cell.blade.php` (NEW)
- ViewColumn partial: input field + "Update" button for PayPal/Razer rows
- Plain text display for other payment methods
- Alpine.js x-data for local state, $wire for Livewire calls

### `resources/views/filament/pages/hr-billing-payment.blade.php` (MODIFIED)
- Replaced "coming soon" with `@livewire('hr-admin-dashboard.hr-payment-received-table')`

### `app/Livewire/HrAdminDashboard/ViewSalesInvoice.php` (MODIFIED)
- Added `from=payment` case in goBack() → redirects to /admin/hr-billing-payment

### `app/Livewire/HrAdminDashboard/ViewOfficialReceipt.php` (MODIFIED)
- Updated goBack() to check `from=payment` → redirects to /admin/hr-billing-payment

## Migration
```bash
php artisan migrate --path=database/migrations/2026_02_24_131317_add_payment_fields_to_hr_official_receipts_table.php
php artisan db:seed --class=HrOfficialReceiptSeeder
```
