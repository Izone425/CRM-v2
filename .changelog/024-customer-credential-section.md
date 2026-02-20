# Change #024: Customer Credential Section in Profile Tab

> **Date**: 2026-02-20
> **Type**: New Feature + UI
> **Status**: Completed

---

## Summary

**What**: Added a read-only "Customer Credential" section to the Profile tab showing Date & Time Creation, Sales Person, Master Email, Password, and Status. Data sourced from SoftwareHandover and Customer models.

**Why**: Provide quick access to customer credential information within the company profile view.

**Breaking Change**: No

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `app/Livewire/HrAdminDashboard/CompanyProfileTab.php` | Modified | Added Customer model import, 5 credential properties, `loadCustomerCredential()` method |
| `resources/views/livewire/hr-admin-dashboard/company-profile-tab.blade.php` | Modified | Added Customer Credential card with 5 read-only fields and status badge |
