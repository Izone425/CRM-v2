# 052 - Customer Credential Page & Sidebar Rename to Admin Portal v2

## Summary
Added a new Customer Credential page under Admin Portal v2. Renamed sidebar label from "Timetec Hr-Admin Portal" to "Admin Portal v2". Added Customer Credential submenu link under Devices.

## Files Changed
- `app/Filament/Pages/HrCustomerCredential.php` — New Filament page for Customer Credential
- `app/Livewire/HrAdminDashboard/HrCustomerCredentialTable.php` — New Livewire table component
- `resources/views/filament/pages/hr-customer-credential.blade.php` — New blade view
- `resources/views/livewire/hr-admin-dashboard/hr-customer-credential-table.blade.php` — New blade view
- `app/Providers/Filament/AdminPanelProvider.php` — Registered HrCustomerCredential page
- `resources/views/layouts/custom-sidebar.blade.php` — Renamed "Timetec Hr-Admin Portal" to "Admin Portal v2"; added Customer Credential submenu link
