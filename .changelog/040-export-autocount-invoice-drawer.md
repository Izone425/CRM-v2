# 040 - Export AutoCount Invoice Drawer with Pre-fill Fields

## Summary
Changed the "Export AutoCount Invoice" button in Software Handover Details from an immediate download to a right-side drawer that prompts the user to enter DocNo, DebtorCode, and UDF_IV_LicenseNumber before generating the Excel file. Previously these 3 fields were yellow-highlighted in the exported Excel for manual entry; now they are pre-filled from the drawer inputs.

## Files Changed

### `resources/views/components/software-handover.blade.php` (MODIFIED)
- Replaced `<a href>` link (lines 797–805) with Alpine.js `x-data` button + drawer
- Button opens a right-side drawer overlay (400px wide, z-index 9999)
- Drawer has 3 input fields: DocNo (pre-filled with EPIN/EHIN/EGIN based on training_type), DebtorCode, UDF_IV_LicenseNumber
- "Generate Excel" button builds URL with query params and opens in new tab
- "Cancel" button and backdrop click close the drawer
- DocNo prefix determined from `$record->training_type` using same logic as controller

### `app/Http/Controllers/InvoiceDataExportController.php` (MODIFIED)
- Added `use Illuminate\Http\Request` import
- `exportInvoiceData()` now accepts `Request $request` parameter
- DocNo: reads from `$request->query('doc_no')`, falls back to training_type prefix
- DebtorCode: reads from `$request->query('debtor_code')`, falls back to empty string
- UDF_IV_LicenseNumber: reads from `$request->query('license_number')`, falls back to empty string
- Yellow highlighting now conditional: only applied to cells that were NOT pre-filled from drawer

## Migration
No migration needed. No database changes.
