# Change #011: Remove PayPal & Razer Buttons from Sales Invoice Page

> **Date**: 2026-02-19
> **Type**: Fix/Change + UI
> **Status**: Completed

---

## Summary

**What**: Removed the PayPal and Razer payment buttons from the admin ViewSalesInvoice page.

**Why**: Payment is handled externally via the "Copy Payment Link" feature (links to timeteccloud.com). These buttons were non-functional placeholders on the admin page.

**Impact**: UI cleanup. No functional change — payment flow remains on the external domain.

**Breaking Change**: No

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `resources/views/livewire/hr-admin-dashboard/view-sales-invoice.blade.php` | Modified | Removed the PayPal/Razer button block (lines 254-264) |

---

## Code Changes (BEFORE -> AFTER)

### File: `resources/views/livewire/hr-admin-dashboard/view-sales-invoice.blade.php`

#### BEFORE
```blade
{{-- Payment Buttons --}}
<div class="mt-8 flex justify-center gap-6 pb-4">
    <button type="button" style="background-color: #93c5fd; ...">Paypal</button>
    <button type="button" style="background-color: #93c5fd; ...">Razer</button>
</div>
```

#### AFTER
*(Removed entirely)*

---

## Completion Checklist

- [x] Code changes reviewed
- [x] BEFORE/AFTER code documented
- [x] Tested locally
- [x] Ready for developer handoff
