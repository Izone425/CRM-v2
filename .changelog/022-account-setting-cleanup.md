# Change #022: Account Setting: Remove Trial Period + Rename Assign Section

> **Date**: 2026-02-19
> **Type**: Fix/Change + UI
> **Status**: Completed

---

## Summary

**What**: (1) Removed the "Trial Period Management" section (Start Date, End Date, Update button) from Account Setting tab. (2) Renamed "Assign Customer to Dealer/Distributor" to "Assign to Reseller".

**Why**: Trial Period section is no longer needed. Naming simplification for the assign section.

**Breaking Change**: No

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `resources/views/livewire/hr-admin-dashboard/company-account-setting-tab.blade.php` | Modified | Removed Trial Period Management section; renamed assign section title |
