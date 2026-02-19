# Change #012: Stair-Step Indentation for Product Tab License Tiers

> **Date**: 2026-02-19
> **Type**: Fix/Change + UI
> **Status**: Completed

---

## Summary

**What**: Added visible stair-step indentation to the 3-tier license table (Invoice → Year → Product) on the Product tab.

**Why**: Year and product rows had no visual indentation on the name column, making the hierarchy hard to read.

**Impact**: UI improvement only. Tier 2 (Year) indented with `pl-6`, Tier 3 (Product) indented with `pl-12`.

**Breaking Change**: No

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `resources/views/livewire/hr-admin-dashboard/company-products-tab.blade.php` | Modified | Added `pl-6` to year name cell, `pl-12` to product name cell |

---

## Code Changes (BEFORE -> AFTER)

### Tier 2 — Year name cell

#### BEFORE
```blade
<td class="px-3 py-2 text-xs text-gray-600">
```

#### AFTER
```blade
<td class="px-3 py-2 text-xs text-gray-600 pl-6">
```

### Tier 3 — Product name cell

#### BEFORE
```blade
<td class="px-3 py-3 text-sm text-gray-900">{{ $product['license_type'] }}</td>
```

#### AFTER
```blade
<td class="px-3 py-3 text-sm text-gray-900 pl-12">{{ $product['license_type'] }}</td>
```

---

## Completion Checklist

- [x] Code changes reviewed
- [x] BEFORE/AFTER code documented
- [x] Tested locally
- [x] Ready for developer handoff
