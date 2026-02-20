# Changelog

> **Project**: TimeTec CRM PDT - Admin Portal V2
> **Maintained by**: Admin Portal Team
> **Last Updated**: 2026-02-20

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
| 007 | New Feature + UI | Year-grouped item display on ViewSalesInvoice and PDF proforma invoice | No | [View Details](.changelog/007-year-grouped-invoice-display.md) | Completed |
| 008 | Fix/Change + UI | Context-aware Back button returns to originating tab (Products or Invoice) | No | [View Details](.changelog/008-context-aware-back-button.md) | Completed |
| 009 | New Feature + UI | Bulk Configuration section for Add Sales Invoice (products x years auto-populate) | No | [View Details](.changelog/009-bulk-configuration-add-invoice.md) | Completed |
| 010 | Fix/Change + UI | Order table: trash delete icon for all rows, clean product names | No | [View Details](.changelog/010-order-table-improvements.md) | Completed |
| 011 | Fix/Change + UI | Remove PayPal & Razer buttons from admin Sales Invoice page | No | [View Details](.changelog/011-remove-paypal-razer-buttons.md) | Completed |
| 012 | Fix/Change + UI | Stair-step indentation for Product tab license tiers (Year, Product) | No | [View Details](.changelog/012-product-tier-indentation.md) | Completed |
| 013 | New Feature + UI | License filter bar on Product tab (date, type, status, product) | No | [View Details](.changelog/013-license-filter-bar.md) | Completed |
| 014 | Fix/Change + UI | Hide Commission tab from navigation | No | [View Details](.changelog/014-hide-commission-tab.md) | Completed |
| 015 | New Feature + UI | Add 60 Months and Consolidate billing cycle options with dynamic month calculation | No | [View Details](.changelog/015-billing-cycle-60months-consolidate.md) | Completed |
| 016 | Fix/Change + UI | Consolidate billing in Bulk Config + column width fix + label shortening | No | [View Details](.changelog/016-consolidate-bulk-config.md) | Completed |
| 017 | Fix/Change + UI | Rename Invoice tab to "Proforma Invoice" | No | [View Details](.changelog/017-rename-invoice-tab-proforma.md) | Completed |
| 018 | Fix/Change | Invoice number prefix TT -> TTC across all dummy records | No | [View Details](.changelog/018-invoice-number-prefix-ttc.md) | Completed |
| 019 | Fix/Change + UI | Simplify Official Receipt modal to 4 fields (Company, Total, License No, Autocount Invoice) | No | [View Details](.changelog/019-simplify-official-receipt-modal.md) | Completed |
| 020 | Fix/Change + UI | Edit Invoice: "Update Invoice" button + returnUrl Back navigation | No | [View Details](.changelog/020-edit-invoice-back-button-update.md) | Completed |
| 021 | New Feature + UI | Conditional "Pay By" field (Subscriber/Reseller) for accounts under a dealer | No | [View Details](.changelog/021-conditional-pay-by-field.md) | Completed |
| 022 | Fix/Change + UI | Account Setting: remove Trial Period, rename Assign to Reseller | No | [View Details](.changelog/022-account-setting-cleanup.md) | Completed |
| 023 | Fix/Change + UI | Profile tab: simplify Billing Info, remove 4 sections, remove Backend fields | No | [View Details](.changelog/023-profile-tab-simplification.md) | Completed |
| 024 | New Feature + UI | Customer Credential section in Profile tab (read-only) | No | [View Details](.changelog/024-customer-credential-section.md) | Completed |
| 025 | Fix/Change | License table search by email via CompanyDetail relationship | No | [View Details](.changelog/025-license-search-by-email.md) | Completed |
| 026 | New Feature + Database + UI | Devices sub-navigation and Terminal Devices page | Yes (migration) | [View Details](.changelog/026-devices-sub-navigation.md) | Completed |
| 027 | New Feature + Database + UI | Billing navigation with 7 sub-items and Sales Invoice page | Yes (migration) | [View Details](.changelog/027-billing-navigation-sales-invoice.md) | Completed |
| 028 | Fix/Change | Consolidate all invoices into hr_sales_invoices table (single source of truth) | No | [View Details](.changelog/028-consolidate-invoices-to-sales-table.md) | Completed |
| 029 | Fix/Change + UI | Context-aware breadcrumbs and back navigation for ViewSalesInvoice | No | [View Details](.changelog/029-context-aware-breadcrumbs-invoice.md) | Completed |

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
| 007 | Year-grouped items on ViewSalesInvoice and PDF with per-year numbering | `ViewSalesInvoice.php`, `view-sales-invoice.blade.php`, `LicenseProformaInvoiceController.php`, `license-proforma-invoice.blade.php` | No |
| 008 | Back button returns to Products or Invoice tab based on navigation origin | `ViewSalesInvoice.php` (Filament + Livewire), `CompanyInvoiceTab.php`, `company-products-tab.blade.php` | No |
| 009 | Bulk Configuration: select products, set params, auto-generate order rows | `AddSalesInvoiceForm.php`, `add-sales-invoice-form.blade.php` | No |
| 010 | Trash icon for all rows, removed bracket text from product names | `AddSalesInvoiceForm.php`, `add-sales-invoice-form.blade.php` | No |
| 011 | Removed PayPal/Razer buttons from admin invoice page (payment via external link) | `view-sales-invoice.blade.php` | No |
| 012 | Added stair-step indentation (pl-6, pl-12) to Year and Product tier rows | `company-products-tab.blade.php` | No |
| 013 | License filter bar: date range, type, status, product filters with search/reset | `CompanyProductsTab.php`, `company-products-tab.blade.php` | No |
| 014 | Hidden Commission tab from company navigation | `company-license-details-container.blade.php` | No |
| 015 | 60 Months + Consolidate billing cycle with dynamic month calc and URL param passing | `AddSalesInvoiceForm.php`, `CompanyProductsTab.php`, `AddSalesInvoice.php`, blade templates | No |
| 016 | Consolidate in Bulk Config, column width fix, label shortening to (XXM) | `AddSalesInvoiceForm.php`, `add-sales-invoice-form.blade.php` | No |
| 017 | Renamed Invoice tab to "Proforma Invoice" | `company-license-details-container.blade.php` | No |
| 018 | All dummy invoice number prefixes changed from TT to TTC | `CompanyInvoiceTab.php`, `CompanyProductsTab.php`, `ViewSalesInvoice.php` | No |
| 019 | Official Receipt modal: only Company, Total, License Number, Autocount Invoice | `ViewSalesInvoice.php`, `view-sales-invoice.blade.php` | No |
| 020 | Edit Invoice: Update button + returnUrl chain for Back navigation | `AddSalesInvoice.php`, `AddSalesInvoiceForm.php`, `ViewSalesInvoice.php`, blade templates | No |
| 021 | Pay By field appears when account is under Reseller/Distributor | `AddSalesInvoiceForm.php`, `add-sales-invoice-form.blade.php` | No |
| 022 | Removed Trial Period Management, renamed Assign section to "Assign to Reseller" | `company-account-setting-tab.blade.php` | No |
| 023 | Profile simplified: only Account Info, Backend Info, Billing Info remain | `CompanyProfileTab.php`, `company-profile-tab.blade.php` | No |
| 024 | Customer Credential section in Profile tab | `CompanyProfileTab.php`, `company-profile-tab.blade.php` | No |
| 025 | License table search by email | `HrLicenseTable.php` | No |
| 026 | Devices sub-nav + Terminal Devices page | `HrDevices.php`, `HrTerminalDeviceTable.php`, `HrTerminalDevice.php`, migrations, sidebar | Yes (migration) |
| 027 | Billing nav (7 sub-items) + Sales Invoice page | `HrBilling*.php`, `HrSalesInvoiceTable.php`, `HrSalesInvoice.php`, migrations, sidebar | Yes (migration) |
| 028 | Consolidate invoices into hr_sales_invoices | `HrSalesInvoiceSeeder.php`, `CompanyInvoiceTab.php` | No |
| 029 | Context-aware breadcrumbs for ViewSalesInvoice | `ViewSalesInvoice.php` (Filament + Livewire) | No |

---

## For Developers

Each change file contains:
1. **Summary** - What and why
2. **Plain English explanation** - Logic flow for context
3. **BEFORE/AFTER code** - Exact code changes with line references
4. **Migration steps** - What to run/configure
5. **Rollback plan** - How to revert if needed
