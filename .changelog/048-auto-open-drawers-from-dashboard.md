# 048 - Auto-Open Drawers from Dashboard Actions & Hide Handover Details

## Summary
When "Create DB+Trial License" or "Create Pending License" is clicked from the dashboard Actions dropdown, the corresponding drawer now opens immediately without showing the Software Handover Details modal behind it. The "View" action remains unchanged.

## Files Changed
- `app/Livewire/SalespersonDashboard/SoftwareHandoverNew.php` — Added `autoOpen` parameter (`db_trial` / `pending`) to `extraAttributes` for create actions
- `resources/views/components/software-handover.blade.php` — Added `@if(!$autoOpen)` conditional blocks to skip header, main content, and export buttons when `autoOpen` is set; Alpine.js initial state reads `autoOpen` to auto-open the correct drawer
