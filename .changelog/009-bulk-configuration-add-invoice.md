# Change #009: Bulk Configuration Section for Add Sales Invoice

> **Date**: 2026-02-13
> **Type**: New Feature + UI
> **Status**: Completed

---

## Summary

**What**: Added a collapsible "Bulk Configuration" section above the Order table on the Add Sales Invoice page. Users can select multiple products, set units, price, start date, billing cycle, and years of subscription, then click "Apply Bulk Configuration" to auto-populate the Order table with all generated rows (products x years).

**Why**: When creating multi-year, multi-product invoices, manually adding 12+ rows (e.g., 4 products x 3 years) is tedious and error-prone. Bulk configuration lets users set parameters once and generate all rows automatically.

**Impact**: New UI section and backend method. Does not affect existing manual row-by-row workflow which still works alongside bulk config.

**Breaking Change**: No

---

## What It Does (Plain English)

### The Flow

1. **Admin expands** the "Bulk Configuration" collapsible section
2. **Selects products** via checkboxes (e.g., Attendance, Leave, Claim, Payroll)
3. **Sets parameters**: Units per item (30), Unit Price (5.00), Start Date (01/01/2026), Billing Cycle (12 Months), Years of Subscription (3)
4. **Clicks "Apply Bulk Configuration"**
5. **System generates** 12 rows (4 products x 3 years):
   - Year 1: 4 products with dates 01/01/2026 - 31/12/2026
   - Year 2: 4 products with dates 01/01/2027 - 31/12/2027
   - Year 3: 4 products with dates 01/01/2028 - 31/12/2028
6. **Order table** is replaced with the generated rows
7. **User can still** manually edit individual rows, add more rows, or delete rows after bulk apply

### Before vs After (Behavior)

| Before | After |
|--------|-------|
| Only manual row-by-row entry | Bulk configuration section available above Order table |
| No way to auto-generate multi-year rows | Select products + params, click Apply to generate all rows |
| Fixed 5 default product rows always present | Bulk apply replaces all rows with generated ones |

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `app/Livewire/HrAdminDashboard/AddSalesInvoiceForm.php` | Modified | Added 6 bulk config properties and `applyBulkConfig()` method |
| `resources/views/livewire/hr-admin-dashboard/add-sales-invoice-form.blade.php` | Modified | Added collapsible Bulk Configuration UI section above Order table |

---

## Code Changes (BEFORE -> AFTER)

---

### File: `app/Livewire/HrAdminDashboard/AddSalesInvoiceForm.php`

**Change Type**: Modified

**What Changed**: Added bulk configuration properties and `applyBulkConfig()` method

#### BEFORE (properties section)
```php
// Currency
public string $currency = 'MYR';

public function mount(
```

#### AFTER (properties section)
```php
// Currency
public string $currency = 'MYR';

// Bulk Configuration
public array $bulkProducts = [];
public int $bulkUnits = 0;
public float $bulkUnitPrice = 5.00;
public string $bulkStartDate = '';
public string $bulkBillingCycle = '12';
public int $bulkYears = 1;

public function mount(
```

#### NEW METHOD: `applyBulkConfig()`
```php
public function applyBulkConfig(): void
{
    if (empty($this->bulkProducts) || empty($this->bulkStartDate) || $this->bulkUnits <= 0) {
        return;
    }

    $newItems = [];
    $billingCycleMonths = (int) $this->bulkBillingCycle;

    for ($year = 0; $year < $this->bulkYears; $year++) {
        $yearStartDate = Carbon::parse($this->bulkStartDate)->addYears($year);
        $yearEndDate = $yearStartDate->copy()->addMonths($billingCycleMonths)->subDay();

        foreach ($this->bulkProducts as $productIndex) {
            $product = $this->availableProducts[$productIndex] ?? null;
            if (!$product) {
                continue;
            }

            $subtotal = $this->bulkUnits * $this->bulkUnitPrice * $billingCycleMonths;

            $newItems[] = [
                'item_name' => $product['name'],
                'units' => $this->bulkUnits,
                'unit_price' => $this->bulkUnitPrice,
                'currency' => $this->currency,
                'license_start_date' => $yearStartDate->format('Y-m-d'),
                'license_end_date' => $yearEndDate->format('Y-m-d'),
                'billing_cycle' => (string) $billingCycleMonths,
                'discount' => 0,
                'total_price' => round($subtotal, 2),
            ];
        }
    }

    $this->orderItems = $newItems;
    $this->recalculateItemTotals();
}
```

---

### File: `resources/views/livewire/hr-admin-dashboard/add-sales-invoice-form.blade.php`

**Change Type**: Modified

**What Changed**: Added collapsible Bulk Configuration card section between Customer Information and Order sections

#### NEW SECTION (inserted above Order section)
```blade
{{-- BULK CONFIGURATION SECTION --}}
<div x-data="{ bulkOpen: false }" class="bg-white rounded-lg shadow mb-6">
    <button type="button" @click="bulkOpen = !bulkOpen"
        class="w-full bg-gray-100 px-6 py-3 rounded-t-lg border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-base font-semibold text-gray-800">Bulk Configuration</h3>
        <svg :class="bulkOpen ? 'rotate-180' : ''" class="w-5 h-5 text-gray-500 transition-transform">...</svg>
    </button>
    <div x-show="bulkOpen" x-collapse class="px-6 py-5">
        {{-- Product checkboxes from $availableProducts --}}
        {{-- Units per Item, Unit Price, License Start Date inputs --}}
        {{-- Billing Cycle dropdown (1-48 months), Years of Subscription dropdown (1-5) --}}
        {{-- Apply Bulk Configuration button -> wire:click="applyBulkConfig" --}}
    </div>
</div>
```

---

## UI Layout

```
+-- Bulk Configuration (collapsible) ----------------------------+
|                                                                 |
|  Select Products:                                               |
|  [x] TimeTec Attendance  [x] TimeTec Leave                     |
|  [x] TimeTec Claim       [x] TimeTec Payroll                   |
|  [ ] TimeTec Appraisal                                         |
|                                                                 |
|  Units per Item: [30]    Unit Price: [5.00]                     |
|  Start Date: [2026-01-01]   Billing Cycle: [12 Months]         |
|  Years of Subscription: [3]                                     |
|                                                                 |
|                             [Apply Bulk Configuration]          |
+-----------------------------------------------------------------+
```

---

## Generated Output Example

For 4 products, 30 units, $5.00, start 01/01/2026, 12-month cycle, 3 years:

| # | Item | Units | Price | Start Date | End Date | Cycle | Total |
|---|------|-------|-------|------------|----------|-------|-------|
| 1 | TimeTec Attendance | 30 | 5.00 | 01/01/2026 | 31/12/2026 | 12 | 1,800.00 |
| 2 | TimeTec Leave | 30 | 5.00 | 01/01/2026 | 31/12/2026 | 12 | 1,800.00 |
| 3 | TimeTec Claim | 30 | 5.00 | 01/01/2026 | 31/12/2026 | 12 | 1,800.00 |
| 4 | TimeTec Payroll | 30 | 5.00 | 01/01/2026 | 31/12/2026 | 12 | 1,800.00 |
| 5 | TimeTec Attendance | 30 | 5.00 | 01/01/2027 | 31/12/2027 | 12 | 1,800.00 |
| 6 | TimeTec Leave | 30 | 5.00 | 01/01/2027 | 31/12/2027 | 12 | 1,800.00 |
| 7 | TimeTec Claim | 30 | 5.00 | 01/01/2027 | 31/12/2027 | 12 | 1,800.00 |
| 8 | TimeTec Payroll | 30 | 5.00 | 01/01/2027 | 31/12/2027 | 12 | 1,800.00 |
| 9 | TimeTec Attendance | 30 | 5.00 | 01/01/2028 | 31/12/2028 | 12 | 1,800.00 |
| 10 | TimeTec Leave | 30 | 5.00 | 01/01/2028 | 31/12/2028 | 12 | 1,800.00 |
| 11 | TimeTec Claim | 30 | 5.00 | 01/01/2028 | 31/12/2028 | 12 | 1,800.00 |
| 12 | TimeTec Payroll | 30 | 5.00 | 01/01/2028 | 31/12/2028 | 12 | 1,800.00 |

**Grand Total**: 12 x 1,800.00 = 21,600.00

---

## Testing Notes

### How to Test

1. Navigate to Add Sales Invoice page
2. Click "Bulk Configuration" header to expand the section
3. Check 4 products: Attendance, Leave, Claim, Payroll
4. Set Units: 30, Unit Price: 5, Start Date: 01/01/2026, Billing Cycle: 12 Months, Years: 3
5. Click "Apply Bulk Configuration"
6. Verify Order table shows 12 rows with correct dates per year
7. Verify each row total = 30 x 5 x 12 = 1,800.00
8. Verify user can still manually edit individual rows after bulk apply
9. Verify "+ Add Item" button still works to add extra rows

---

## Completion Checklist

- [x] Code changes reviewed
- [x] BEFORE/AFTER code documented
- [x] Tested locally
- [x] Ready for developer handoff
