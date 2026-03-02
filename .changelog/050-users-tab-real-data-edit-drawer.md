# 050 - Users Tab: Real Customer Data, Password Column & Edit Password Drawer

## Summary
Replaced mock data in the Company License Details > Users tab with real Customer records queried by `sw_id`. Added a Password column showing the plain password. Added an Edit button that opens a slide-over drawer for changing a user's password (with eye icon toggle for visibility).

## Files Changed
- `app/Livewire/HrAdminDashboard/CompanyUsersTab.php` — Replaced `loadUsers()` mock data with `Customer::where('sw_id', ...)` query; added `openEditDrawer`, `closeEditDrawer`, `updatePassword` methods using `forceFill` + `Hash::make`; added Livewire properties for drawer state
- `resources/views/livewire/hr-admin-dashboard/company-users-tab.blade.php` — Added Password column header and data cell; added Edit button with `wire:click`; added slide-over drawer with fixed positioning (right side, z-index 9999), password fields with Alpine.js eye icon toggle, validation error display, and Filament notification on success
