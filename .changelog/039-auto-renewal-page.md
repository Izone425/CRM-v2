# 039 - Auto Renewal Page

## Summary
Built the Billing > Auto Renewal page replacing the "coming soon" placeholder. Shows auto-renewal records with 7 columns (Invoice No, Company Name, Country, Next Billing Date, Created Time, Status, Action). The Action column features a toggle switch to enable/disable auto-renewal per record.

## Files Changed

### `database/migrations/2026_02_24_143338_create_hr_auto_renewals_table.php` (NEW)
- Creates `hr_auto_renewals` table with: invoice_no, company_name, country, next_billing_date, status, is_enabled (boolean toggle), software_handover_id, handover_id
- Indexes on invoice_no, next_billing_date, status

### `app/Models/HrAutoRenewal.php` (NEW)
- Eloquent model with fillable fields, casts for date and boolean

### `database/seeders/HrAutoRenewalSeeder.php` (NEW)
- Seeds 350 records with 40 different company/country combinations
- All status = PENDING, is_enabled = true
- next_billing_date spread across next 6 months
- Bulk insert in chunks of 100

### `app/Livewire/HrAdminDashboard/HrAutoRenewalTable.php` (NEW)
- Livewire table with 7 columns, Filament Table Builder
- Filters: Status, Country, Date Range (on next_billing_date)
- `toggleAutoRenewal()` method flips is_enabled boolean
- CSV export, default sort by next_billing_date ascending

### `resources/views/livewire/hr-admin-dashboard/hr-auto-renewal-table.blade.php` (NEW)
- Compact CSS with table-layout fixed and nth-child column widths
- Export CSV button, Total Records count, Page X of Y

### `resources/views/livewire/hr-admin-dashboard/partials/auto-renewal-toggle.blade.php` (NEW)
- iOS-style toggle switch: blue when enabled, gray when disabled
- Alpine.js x-data for local state, $wire.toggleAutoRenewal() for persistence
- All styles in x-bind:style to prevent Alpine.js style replacement issue

### `resources/views/filament/pages/hr-billing-auto-renewal.blade.php` (MODIFIED)
- Replaced "coming soon" with `@livewire('hr-admin-dashboard.hr-auto-renewal-table')`

### `app/Livewire/HrAdminDashboard/ViewSalesInvoice.php` (MODIFIED)
- Added `from=auto-renewal` case in goBack() → redirects to /admin/hr-billing-auto-renewal

## Migration
```bash
php artisan migrate --path=database/migrations/2026_02_24_143338_create_hr_auto_renewals_table.php
php artisan db:seed --class=HrAutoRenewalSeeder
```
