# Change #008: Context-Aware Back Button on ViewSalesInvoice

> **Date**: 2026-02-13
> **Type**: Fix/Change + UI
> **Status**: Completed

---

## Summary

**What**: The "Back" button on the ViewSalesInvoice page now returns the user to the correct tab they came from. If they opened the invoice from the Products tab, Back goes to Products tab. If from Invoice tab, Back goes to Invoice tab.

**Why**: Previously the Back button always returned to the same tab regardless of where the user navigated from, causing confusion and extra clicks to find their place.

**Impact**: Navigation improvement across 5 files. A `from` query parameter is passed through the full chain: URL -> Filament Page -> Blade -> Livewire component -> `goBack()` method.

**Breaking Change**: No

---

## What It Does (Plain English)

### The Flow

1. **When user clicks an invoice link from Products tab**: URL includes `&from=products`
2. **When user clicks an invoice link from Invoice tab**: URL includes `&from=invoice`
3. **Filament Page reads** the `from` query parameter and passes it to the Livewire component
4. **ViewSalesInvoice component stores** `$this->from` and uses it in `goBack()`:
   - `from=products` -> redirects to Company Details with `&tab=products`
   - `from=invoice` (or default) -> redirects to Company Details with `&tab=invoice`
5. **Breadcrumbs** also use the `from` value to link back to the correct tab

### Before vs After (Behavior)

| Before | After |
|--------|-------|
| Back button always went to default tab (profile) | Back button returns to the originating tab |
| No `from` parameter in URLs | URLs include `&from=products` or `&from=invoice` |
| Breadcrumbs always linked to default tab | Breadcrumbs link to correct tab based on origin |

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `resources/views/livewire/hr-admin-dashboard/company-products-tab.blade.php` | Modified | Added `&from=products` to invoice link URLs |
| `app/Livewire/HrAdminDashboard/CompanyInvoiceTab.php` | Modified | Added `'from' => 'invoice'` to both `viewInvoice()` and `viewInvoiceByNo()` methods |
| `app/Filament/Pages/ViewSalesInvoice.php` | Modified | Added `$from` property, reads from query string, uses in breadcrumbs |
| `resources/views/filament/pages/view-sales-invoice.blade.php` | Modified | Passes `:from="$from"` to Livewire component |
| `app/Livewire/HrAdminDashboard/ViewSalesInvoice.php` | Modified | Added `$from` property, uses in `goBack()` to determine redirect tab |

---

## Code Changes (BEFORE -> AFTER)

---

### File: `app/Livewire/HrAdminDashboard/ViewSalesInvoice.php`

**Change Type**: Modified

**What Changed**: Added `$from` property and context-aware `goBack()` logic

#### BEFORE (goBack method)
```php
public function goBack(): void
{
    if ($this->softwareHandoverId) {
        $this->redirect(
            url('/admin/hr-company-license-details?softwareHandoverId=' . $this->softwareHandoverId . '&tab=invoice'),
            navigate: false
        );
    } else {
        $this->redirect(url('/admin/hr-license'), navigate: false);
    }
}
```

#### AFTER (goBack method)
```php
public ?string $from = null;

public function goBack(): void
{
    if ($this->softwareHandoverId) {
        $tab = $this->from === 'products' ? 'products' : 'invoice';
        $this->redirect(
            url('/admin/hr-company-license-details?softwareHandoverId=' . $this->softwareHandoverId . '&tab=' . $tab),
            navigate: false
        );
    } else {
        $this->redirect(url('/admin/hr-license'), navigate: false);
    }
}
```

---

### File: `app/Filament/Pages/ViewSalesInvoice.php`

**Change Type**: Modified

**What Changed**: Added `$from` property, reads from query, uses in breadcrumbs

#### BEFORE
```php
public function mount(): void
{
    // ... existing params ...
}

public function getBreadcrumbs(): array
{
    $breadcrumbs = [
        url('/admin/hr-license') => 'All Licenses',
    ];
    if ($this->softwareHandoverId) {
        $breadcrumbs[url('/admin/hr-company-license-details?softwareHandoverId=' . $this->softwareHandoverId)] = 'Company Details';
    }
    $breadcrumbs['#'] = 'Sales Invoice';
    return $breadcrumbs;
}
```

#### AFTER
```php
public ?string $from = null;

public function mount(): void
{
    // ... existing params ...
    $this->from = Request::query('from') ? (string) Request::query('from') : null;
}

public function getBreadcrumbs(): array
{
    $breadcrumbs = [
        url('/admin/hr-license') => 'All Licenses',
    ];
    if ($this->softwareHandoverId) {
        $tab = $this->from === 'products' ? 'products' : 'invoice';
        $breadcrumbs[url('/admin/hr-company-license-details?softwareHandoverId=' . $this->softwareHandoverId . '&tab=' . $tab)] = 'Company Details';
    }
    $breadcrumbs['#'] = 'Sales Invoice';
    return $breadcrumbs;
}
```

---

### File: `resources/views/livewire/hr-admin-dashboard/company-products-tab.blade.php`

**Change Type**: Modified

**What Changed**: Added `&from=products` to invoice link URL

#### BEFORE
```blade
<a href="{{ url('/admin/view-sales-invoice?invoiceNo=' . $group['invoice_no'] . '&softwareHandoverId=' . $softwareHandoverId) }}"
```

#### AFTER
```blade
<a href="{{ url('/admin/view-sales-invoice?invoiceNo=' . $group['invoice_no'] . '&softwareHandoverId=' . $softwareHandoverId . '&from=products') }}"
```

---

### File: `app/Livewire/HrAdminDashboard/CompanyInvoiceTab.php`

**Change Type**: Modified

**What Changed**: Added `from=invoice` to both navigation methods

#### BEFORE (viewInvoiceByNo)
```php
$params = [
    'invoiceNo' => $invoiceNo,
    'softwareHandoverId' => $this->softwareHandoverId,
    ...
];
```

#### AFTER
```php
$params = [
    'invoiceNo' => $invoiceNo,
    'softwareHandoverId' => $this->softwareHandoverId,
    'from' => 'invoice',
    ...
];
```

---

## Important Note

The valid tab names in `CompanyLicenseDetailsContainer.php` are: `['users', 'profile', 'products', 'customer', 'commission', 'invoice', 'account_setting']`. The tab value must be `products` (plural), not `product`. Using an invalid tab name causes it to fall back to the default `profile` tab.

---

## Testing Notes

### How to Test

1. Go to Company Details -> Products tab -> click an invoice link
2. On the ViewSalesInvoice page, click "Back"
3. Verify it returns to the **Products** tab (not Profile or Invoice)
4. Go to Company Details -> Invoice tab -> click an invoice number
5. On the ViewSalesInvoice page, click "Back"
6. Verify it returns to the **Invoice** tab
7. Check breadcrumbs also link to the correct tab

---

## Completion Checklist

- [x] Code changes reviewed
- [x] BEFORE/AFTER code documented
- [x] Tested locally
- [x] Ready for developer handoff
