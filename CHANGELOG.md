# Changelog

> **Project**: TimeTec CRM PDT - Admin Portal V2
> **Maintained by**: Admin Portal Team
> **Last Updated**: 2026-02-09

This changelog tracks all code changes, new features, and modifications. Each entry links to a detailed file showing **BEFORE and AFTER code** for developer handoff.

---

## How to Read This

- Each entry links to a detailed file with **pseudo code** (plain English) and **code diffs** (BEFORE/AFTER)
- New Feature | Fix/Change | Database | Config | UI
- Breaking change (requires migration/careful review)

---

## Changes

### [Unreleased]

| # | Type | Change | Breaking | Details | Status |
|---|------|--------|----------|---------|--------|
| 001 | New Feature + Database | Add Commission Rate field for Reseller/Distributor companies | Yes (migration) | [View Details](.changelog/001-add-commission-rate.md) | Completed |
| 002 | Fix/Change | Show Customer & Commission tabs for Distributor companies | No | [View Details](.changelog/002-distributor-tab-visibility.md) | Completed |
| 003 | New Feature | Build Company Customer Tab with Resellers & Subscribers tables | No | [View Details](.changelog/003-company-customer-tab.md) | Completed |
| 004 | Database + Fix/Change | Add per-item license dates to invoice creation and display | Yes (migration) | [View Details](.changelog/004-license-dates-per-item.md) | Completed |
| 005 | New Feature + UI | Invoice tab dummy records, clickable Invoice No navigation, remove Action column | No | [View Details](.changelog/005-invoice-tab-enhancements.md) | Completed |
| 006 | New Feature + UI | Status-based action buttons on ViewSalesInvoice page | No | [View Details](.changelog/006-invoice-status-action-buttons.md) | Completed |

---

## Status Legend

- Not Started - Developer hasn't begun
- In Progress - Developer is working on it
- Completed - Code deployed and tested
- Needs Review - Requires vibe coder review

---

## Quick Reference

| Change # | One-Line Summary | Key Files | Has Breaking Changes |
|----------|------------------|-----------|---------------------|
| 001 | Added Commission Rate dropdown to Payment Information for Reseller/Distributor companies | `ResellerV2Commission.php`, `CompanyProfileTab.php`, migration | Yes (new table) |
| 002 | Distributor companies now see Customer & Commission tabs (previously only Resellers) | `company-license-details-container.blade.php` | No |
| 003 | Built Customer Tab showing sub-resellers and subscribers under a Reseller/Distributor | `CompanyCustomerTab.php`, `company-customer-tab.blade.php` | No |
| 004 | Added per-item license start/end dates to invoice creation and display | `QuotationDetail.php`, `AddSalesInvoiceForm.php`, `ViewSalesInvoice.php`, migration | Yes (new columns) |
| 005 | Invoice tab: 13 dummy records, clickable Invoice No, removed Action column, fixed status colors | `CompanyInvoiceTab.php`, `company-invoice-tab.blade.php`, `ViewSalesInvoice.php` (Filament + Livewire) | No |
| 006 | Status-based action buttons: Pending gets 4 buttons, Cancelled gets Reactive Invoice | `view-sales-invoice.blade.php` | No |

---

## For Developers

Each change file contains:
1. **Summary** - What and why
2. **Plain English explanation** - Logic flow for context
3. **BEFORE/AFTER code** - Exact code changes with line references
4. **Migration steps** - What to run/configure
5. **Rollback plan** - How to revert if needed
