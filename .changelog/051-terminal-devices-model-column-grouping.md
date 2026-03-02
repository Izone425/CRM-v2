# 051 - Terminal Devices: Model Column & Company Name Grouping with Collapse/Expand

## Summary
Added a "Model" column (e.g. FaceId 5, FaceID 6) to the Terminal Devices table after Invoice No. Implemented company name grouping where the first row shows the clickable company name and subsequent rows show "-". Added collapse/expand toggle arrows per company group using Alpine.js.

## Files Changed
- `database/migrations/2026_03_02_120511_add_model_to_hr_terminal_devices_table.php` — New migration adding nullable `model` string column after `invoice_no`
- `app/Models/HrTerminalDevice.php` — Added `model` to `$fillable` array
- `app/Livewire/HrAdminDashboard/HrTerminalDeviceTable.php` — Added Model TextColumn; added `extraAttributes` with `data-company` and `data-company-first` data attributes; implemented inline deduplication via `static $last` tracking in `formatStateUsing`, `tooltip`, `color`, and `url` closures; default sort changed to `company_name asc`
- `resources/views/livewire/hr-admin-dashboard/hr-terminal-device-table.blade.php` — Added Alpine.js component with collapse/expand functionality: inserts ▼/▶ toggle arrows on first-row cells, hides/shows subsequent rows per company group, re-processes on Livewire morphs
- `database/seeders/HrTerminalDeviceSeeder.php` — Added random model names to seeded device data
