# Change #025: License Table Search by Email

> **Date**: 2026-02-20
> **Type**: Fix/Change
> **Status**: Completed

---

## Summary

**What**: Enhanced the Company Name column search in HrLicenseTable to also search by email address via the related SoftwareHandover -> Lead -> CompanyDetail relationship.

**Why**: Allow admins to find companies by email in addition to company name.

**Breaking Change**: No

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `app/Livewire/HrAdminDashboard/HrLicenseTable.php` | Modified | Changed `->searchable()` to custom query that searches `company_name` OR related `companyDetail.email` |
