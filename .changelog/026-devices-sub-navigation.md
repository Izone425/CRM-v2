# Change #026: Devices Sub-Navigation and Terminal Devices Page

> **Date**: 2026-02-20
> **Type**: New Feature + Database + UI
> **Status**: Completed

---

## Summary

**What**:
1. Added "Devices" as a sub-item under License in the sidebar navigation
2. Created HrDevices Filament page with Terminal Device table (Filament v3)
3. Created `hr_terminal_devices` table with migration and seeder
4. License sidebar item now expandable with "All Licenses" and "Devices" sub-items

**Why**: Provide a dedicated page for managing terminal devices linked to company licenses.

**Breaking Change**: Yes (migration required)

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `app/Filament/Pages/HrDevices.php` | New | Filament page for Terminal Devices |
| `app/Livewire/HrAdminDashboard/HrTerminalDeviceTable.php` | New | Livewire component with Filament table for terminal devices |
| `app/Models/HrTerminalDevice.php` | New | Eloquent model for hr_terminal_devices |
| `database/migrations/2026_02_20_125402_create_hr_terminal_devices_table.php` | New | Create hr_terminal_devices table |
| `database/migrations/2026_02_20_151637_add_handover_columns_to_hr_terminal_devices_table.php` | New | Add handover columns |
| `database/seeders/HrTerminalDeviceSeeder.php` | New | Seed dummy terminal device data |
| `resources/views/filament/pages/hr-devices.blade.php` | New | Blade view for devices page |
| `resources/views/livewire/hr-admin-dashboard/hr-terminal-device-table.blade.php` | New | Blade view for terminal device table |
| `resources/views/layouts/custom-sidebar.blade.php` | Modified | License item now expandable with sub-items (All Licenses, Devices) |
| `app/Providers/Filament/AdminPanelProvider.php` | Modified | Registered HrDevices page |
