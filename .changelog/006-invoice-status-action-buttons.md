# Change #006: Status-Based Action Buttons on ViewSalesInvoice Page

> **Date**: 2026-02-09
> **Type**: New Feature + UI
> **Status**: Completed

---

## Summary

**What**: Added conditional action buttons on the ViewSalesInvoice page that change based on invoice status: Pending invoices show "Add Payment", "Edit Invoice", "Cancel Invoice", and "Copy Payment Link"; Cancelled invoices show "Reactive Invoice"; Paid invoices show only "Back" and "Print".

**Why**: Different invoice statuses require different actions. Admins need payment and management options for pending invoices, reactivation for cancelled ones, and minimal controls for paid ones.

**Impact**: UI-only change. Buttons are displayed conditionally but do not yet have backend logic wired up.

**Breaking Change**: No

---

## What It Does (Plain English)

### The Flow

1. **When this happens**: Admin views a sales invoice page
2. **The system checks**: The invoice's `status` field
3. **If Pending**: Shows Back, Print, Add Payment, Edit Invoice, Cancel Invoice, Copy Payment Link buttons
4. **If Cancel/Cancelled**: Shows Back, Print, Reactive Invoice buttons
5. **If Paid (or any other status)**: Shows only Back and Print buttons

### Before vs After (Behavior)

| Before | After |
|--------|-------|
| All invoices showed only "Back" and "Print" buttons | Pending invoices show 6 buttons (Back, Print, Add Payment, Edit Invoice, Cancel Invoice, Copy Payment Link) |
| No reactivation option for cancelled invoices | Cancelled invoices show "Reactive Invoice" button |
| No visual distinction by status | Buttons color-coded: green (Add Payment, Reactive), blue (Edit), red (Cancel), gray (Copy Link) |

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `resources/views/livewire/hr-admin-dashboard/view-sales-invoice.blade.php` | Modified | Added conditional button blocks for pending and cancelled statuses |

---

## Code Changes (BEFORE -> AFTER)

---

### File: `resources/views/livewire/hr-admin-dashboard/view-sales-invoice.blade.php`

**Change Type**: Modified

**What Changed**: Added `@if`/`@elseif` blocks after the Print button to conditionally render status-specific buttons

#### BEFORE (Action Buttons section)
```blade
{{-- Action Buttons --}}
<div class="flex gap-2 mb-6">
    <button wire:click="goBack" class="px-4 py-2 text-sm font-medium text-black bg-cyan-500 rounded hover:bg-cyan-600 transition-colors">
        Back
    </button>
    <button onclick="window.print()" class="px-4 py-2 text-sm font-medium text-black bg-cyan-500 rounded hover:bg-cyan-600 transition-colors">
        Print
    </button>
</div>
```

#### AFTER (Action Buttons section)
```blade
{{-- Action Buttons --}}
<div class="flex gap-2 mb-6">
    <button wire:click="goBack"
        class="px-4 py-2 text-sm font-medium text-black bg-cyan-500 rounded hover:bg-cyan-600 transition-colors">
        Back
    </button>
    <button onclick="window.print()"
        class="px-4 py-2 text-sm font-medium text-black bg-cyan-500 rounded hover:bg-cyan-600 transition-colors">
        Print
    </button>

    {{-- NEW: Status-based buttons --}}
    @if(strtolower($invoice['status'] ?? '') === 'pending')
        <button class="px-4 py-2 text-sm font-medium text-black bg-green-600 rounded hover:bg-green-700 transition-colors">
            Add Payment
        </button>
        <button class="px-4 py-2 text-sm font-medium text-black bg-blue-600 rounded hover:bg-blue-700 transition-colors">
            Edit Invoice
        </button>
        <button class="px-4 py-2 text-sm font-medium text-black bg-red-600 rounded hover:bg-red-700 transition-colors">
            Cancel Invoice
        </button>
        <button class="px-4 py-2 text-sm font-medium text-black bg-gray-600 rounded hover:bg-gray-700 transition-colors">
            Copy Payment Link
        </button>
    @elseif(in_array(strtolower($invoice['status'] ?? ''), ['cancel', 'cancelled']))
        <button class="px-4 py-2 text-sm font-medium text-black bg-green-600 rounded hover:bg-green-700 transition-colors">
            Reactive Invoice
        </button>
    @endif
</div>
```

---

## Button Summary by Status

| Status | Buttons Shown | Button Colors |
|--------|---------------|---------------|
| Pending | Back, Print, Add Payment, Edit Invoice, Cancel Invoice, Copy Payment Link | Cyan, Cyan, Green, Blue, Red, Gray |
| Cancel/Cancelled | Back, Print, Reactive Invoice | Cyan, Cyan, Green |
| Paid / Other | Back, Print | Cyan, Cyan |

---

## Testing Notes

### How to Test

1. Navigate to Invoice tab, click a **Pending** invoice (e.g., TT2602000032)
2. Verify all 6 buttons appear: Back, Print, Add Payment, Edit Invoice, Cancel Invoice, Copy Payment Link
3. Navigate to Invoice tab, click a **Cancel** invoice (e.g., TT2409000134)
4. Verify 3 buttons appear: Back, Print, Reactive Invoice
5. Navigate to Invoice tab, click a **Paid** invoice (e.g., TT2408000355)
6. Verify only 2 buttons appear: Back, Print
7. Note: Action buttons are UI placeholders only - clicking them does not trigger backend actions yet

---

## Completion Checklist

- [x] Code changes reviewed
- [x] BEFORE/AFTER code documented
- [x] Tested locally
- [x] Ready for developer handoff
