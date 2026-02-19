# Change #014: Hide Commission Tab

> **Date**: 2026-02-19
> **Type**: Fix/Change + UI
> **Status**: Completed

---

## Summary

**What**: Hidden the Commission tab from the company license details navigation bar.

**Why**: Commission tab is not needed in the current phase.

**Impact**: UI only. Tab button removed from view; underlying component is untouched.

**Breaking Change**: No

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `resources/views/livewire/hr-admin-dashboard/company-license-details-container.blade.php` | Modified | Replaced Commission tab button with a comment placeholder |

---

## Code Changes (BEFORE -> AFTER)

#### BEFORE
```blade
<button wire:click="switchToTab('commission')"
    class="company-tab-button {{ $activeTab === 'commission' ? 'active' : 'inactive' }}">
    <svg ...>...</svg>
    Commission
</button>
```

#### AFTER
```blade
{{-- Commission tab hidden --}}
```

---

## Completion Checklist

- [x] Code changes reviewed
- [x] BEFORE/AFTER code documented
- [x] Tested locally
- [x] Ready for developer handoff
