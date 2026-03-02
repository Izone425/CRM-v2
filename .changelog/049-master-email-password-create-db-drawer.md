# 049 - Master Email & Password Fields in Create DB + Trial License Drawer

## Summary
Added two read-only fields to the "Create DB + Trial License" drawer: Master Email (auto-populated as `{handoverId}@timeteccloud.com`) and Password (auto-generated random 10-char string). When "Confirm Create DB" is clicked, the credentials are saved to a Customer record via `updateOrCreate`.

## Files Changed
- `resources/views/components/software-handover.blade.php` — Added PHP data prep for `$defaultMasterEmail` and `$defaultMasterPassword` (checks existing Customer first), Alpine.js properties, two read-only form fields, and included credentials in fetch JSON body
- `app/Http/Controllers/SoftwareHandoverExportController.php` — Updated `confirmCreateDb()` to create/update Customer record with master email, hashed password, and plain password; added `Customer` and `Hash` imports
