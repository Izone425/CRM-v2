# Change #004: Add Per-Item License Dates to Invoice Creation & Display

> **Date**: 2026-02-09
> **Type**: Database + Fix/Change
> **Status**: Completed

---

## Summary

**What**: Added `license_start_date` and `license_end_date` columns to the `quotation_details` table, saved them when creating invoices via the Add Sales Invoice form, and displayed the correct per-item date range on the ViewSalesInvoice page.

**Why**: Previously, invoice line items showed a computed fallback date range (today + subscription period). Each item needs its own specific license start/end dates as entered in the form.

**Impact**: Invoice items now display the exact license period entered during invoice creation instead of a generic calculated range.

**Breaking Change**: Yes (migration required)

---

## What It Does (Plain English)

### The Flow

1. **When this happens**: Admin creates a new sales invoice with items that have individual license start/end dates
2. **The system does this**: Saves `license_start_date` and `license_end_date` per item in `quotation_details`
3. **Then**: When viewing the invoice, it reads the stored dates and displays them as the "[dd/mm/yyyy - dd/mm/yyyy]" period
4. **Finally**: Each line item shows its actual license period, not a computed fallback

### Before vs After (Behavior)

| Before | After |
|--------|-------|
| `quotation_details` had no date columns for license periods | Added `license_start_date` and `license_end_date` nullable date columns |
| `createInvoice()` did not save per-item dates | `createInvoice()` saves `license_start_date` and `license_end_date` per item |
| ViewSalesInvoice computed a fallback period from today's date | ViewSalesInvoice reads stored dates and displays actual period |

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `database/migrations/2026_02_06_190318_add_license_dates_to_quotation_details_table.php` | New | Migration adding `license_start_date` and `license_end_date` to `quotation_details` |
| `app/Models/QuotationDetail.php` | Modified | Added `license_start_date`, `license_end_date` to `$fillable` |
| `app/Livewire/HrAdminDashboard/AddSalesInvoiceForm.php` | Modified | Saves per-item dates in `createInvoice()` |
| `app/Livewire/HrAdminDashboard/ViewSalesInvoice.php` | Modified | Reads stored dates in `loadInvoice()` to build period string |

---

## Code Changes (BEFORE -> AFTER)

---

### File: `database/migrations/2026_02_06_190318_add_license_dates_to_quotation_details_table.php`

**Change Type**: New file

```php
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_details', function (Blueprint $table) {
            $table->date('license_start_date')->nullable()->after('subscription_period');
            $table->date('license_end_date')->nullable()->after('license_start_date');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_details', function (Blueprint $table) {
            $table->dropColumn(['license_start_date', 'license_end_date']);
        });
    }
};
```

---

### File: `app/Models/QuotationDetail.php`

**Change Type**: Modified

**What Changed**: Added new date fields to `$fillable` array

#### BEFORE
```php
protected $fillable = [
    'quotation_id',
    'product_id',
    'description',
    'quantity',
    'subscription_period',
    'unit_price',
    'discount',
    'taxation',
    'total_before_tax',
    'total_after_tax',
    'sort_order',
    'tax_code',
    // ...
];
```

#### AFTER
```php
protected $fillable = [
    'quotation_id',
    'product_id',
    'description',
    'quantity',
    'subscription_period',
    'unit_price',
    'discount',
    'taxation',
    'total_before_tax',
    'total_after_tax',
    'sort_order',
    'license_start_date',   // NEW
    'license_end_date',     // NEW
    'tax_code',
    // ...
];
```

---

### File: `app/Livewire/HrAdminDashboard/AddSalesInvoiceForm.php`

**Change Type**: Modified

**What Changed**: `createInvoice()` now saves per-item license dates

#### BEFORE
```php
QuotationDetail::create([
    'quotation_id' => $quotation->id,
    'product_id' => $productId,
    'description' => $item['description'],
    'quantity' => $item['quantity'],
    'subscription_period' => $item['subscription_period'],
    'unit_price' => $item['unit_price'],
    'discount' => $item['discount'],
    // ... no dates
]);
```

#### AFTER
```php
QuotationDetail::create([
    'quotation_id' => $quotation->id,
    'product_id' => $productId,
    'description' => $item['description'],
    'quantity' => $item['quantity'],
    'subscription_period' => $item['subscription_period'],
    'unit_price' => $item['unit_price'],
    'discount' => $item['discount'],
    'license_start_date' => $item['license_start_date'] ?? null,  // NEW
    'license_end_date' => $item['license_end_date'] ?? null,      // NEW
    // ...
]);
```

---

### File: `app/Livewire/HrAdminDashboard/ViewSalesInvoice.php`

**Change Type**: Modified

**What Changed**: `loadInvoice()` now reads stored dates for period display

#### BEFORE
```php
foreach ($quotation->items as $item) {
    $itemData = [
        'description' => $item->description,
        // ... no period from stored dates
    ];
}
```

#### AFTER
```php
foreach ($quotation->items as $item) {
    $period = null;
    if ($item->license_start_date && $item->license_end_date) {
        $period = date('d/m/Y', strtotime($item->license_start_date))
            . ' - ' . date('d/m/Y', strtotime($item->license_end_date));
    }

    $itemData = [
        'description' => $item->description,
        'period' => $period, // NEW: uses stored dates
        // ...
    ];
}
```

---

## Migration Steps

1. Run `php artisan migrate` to add `license_start_date` and `license_end_date` columns to `quotation_details`
2. Existing records will have `NULL` for both columns (backward compatible)

## Rollback Plan

1. Run `php artisan migrate:rollback` to drop the two columns
2. Revert changes to `QuotationDetail.php`, `AddSalesInvoiceForm.php`, and `ViewSalesInvoice.php`

---

## Completion Checklist

- [x] Code changes reviewed
- [x] BEFORE/AFTER code documented
- [x] Migration created and run
- [x] Ready for developer handoff
