# Change #023: Profile Tab Simplification

> **Date**: 2026-02-19
> **Type**: Fix/Change + UI
> **Status**: Completed

---

## Summary

**What**: Major simplification of the Profile tab:
1. Removed "Backend User Id" and "Backend Webster IP" from Backend Information (only Backend Company Id remains)
2. Billing Information: replaced Address and Set as Default with PIC Name and Phone (now shows: Company Name, PIC Name, Phone, Email)
3. Removed entire sections: Contact Person, Business Information, Upline Information, Payment Information

Profile tab now only contains: Account Information, Backend Information, and Billing Information.

**Why**: Streamline the Profile tab to show only essential information.

**Breaking Change**: No

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `app/Livewire/HrAdminDashboard/CompanyProfileTab.php` | Modified | Replaced `$billingAddress`/`$billingIsDefault` with `$billingPicName`/`$billingPhone`; updated `loadBillingInfo()` and `saveBillingInfo()` to use `name`/`contact_no` from CompanyDetail |
| `resources/views/livewire/hr-admin-dashboard/company-profile-tab.blade.php` | Modified | Removed Backend User Id/Webster IP rows; updated Billing Info fields; removed Contact Person, Business Info, Upline Info, Payment Info sections (~550 lines removed) |
