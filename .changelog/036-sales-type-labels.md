# 036 - Sales Type Labels on Product Tab and Add/Edit Invoice

## Summary
Added Sales Type labels (NEW SALES, ADD ON NEW SALES, RENEWAL SALES, ADD ON RENEWAL SALES) to PAID invoice headers on the Product tab. Added Sales Type dropdown selector to both Add Invoice (create) and Edit Invoice forms. Expanded the quotations table enum to support 4 sales type values.

## Files Changed

### `database/migrations/2026_02_24_091851_expand_sales_type_enum_on_quotations_table.php` (NEW)
- Alters `quotations.sales_type` enum from 2 values (NEW SALES, RENEWAL SALES) to 4 values (adds ADD ON NEW SALES, ADD ON RENEWAL SALES)

### `app/Livewire/HrAdminDashboard/AddSalesInvoiceForm.php` (MODIFIED)
- Added `public string $salesType = 'NEW SALES'` property
- `loadExistingInvoice()`: loads `$this->salesType` from quotation
- `createInvoice()`: uses `$this->salesType` instead of hardcoded 'NEW SALES'
- `updateInvoice()`: saves `sales_type` to quotation
- Both create/update validation: added salesType rule

### `resources/views/livewire/hr-admin-dashboard/add-sales-invoice-form.blade.php` (MODIFIED)
- Edit mode: Added Sales Type dropdown, expanded grid to 4 columns when isUnderDealer
- Create mode Row 2: Replaced Invoice Type radio with Sales Type dropdown (4 options)

### `app/Livewire/HrAdminDashboard/CompanyProductsTab.php` (MODIFIED)
- Added `'sales_type' => 'NEW SALES'` to all PAID dummy records
- Added `'sales_type'` to group structure in `getGroupedLicenseRecordsFrom()`

### `resources/views/livewire/hr-admin-dashboard/company-products-tab.blade.php` (MODIFIED)
- Added color-coded sales type badge after `(X items)` on PAID Tier 1 header
- NEW SALES: blue, ADD ON NEW SALES: indigo, RENEWAL SALES: emerald, ADD ON RENEWAL SALES: teal

## Migration
```bash
php artisan migrate --path=database/migrations/2026_02_24_091851_expand_sales_type_enum_on_quotations_table.php
```
