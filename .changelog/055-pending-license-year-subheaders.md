# 055 - Create Pending License: Software Filter, Simplified Descriptions & Year Sub-headers

## Summary
Overhauled the "Create Pending License" drawer:
- **Filter**: Only software products shown (was excluding ONBOARD only)
- **Descriptions**: Simplified from raw HTML to clean format (e.g., "Timetec-Attendance") using `$getShortName()`
- **All years**: Shows all subscription years (not just Year 1 like Trial)
- **Year sub-headers**: Items grouped by year with "Year 1", "Year 2", "Year 3" headers (only if multi-year)
- **Billing cycle**: Auto-calculates default from number of distinct years (e.g., 3 years = 36 months)

## Plain English Explanation
Previously the Pending License drawer showed a flat list of all items with raw HTML descriptions and truncated product codes. Now:
1. Each item includes a `year` field from the QuotationDetail
2. Items are grouped by year with styled sub-headers (gray rounded bar)
3. Sub-headers only appear when there are multiple years (single year = clean table)
4. Billing cycle defaults to `distinct_years * 12` instead of hardcoded 12
5. Product codes and descriptions use the same clean format as "Create DB + Trial License"

## Files Changed

### `resources/views/components/software-handover.blade.php`

#### BEFORE - Pending rows data preparation
```php
$pendingInitialRows[] = [
    'product_code' => $item->product->code ?? '',
    'description' => $item->product->description ?? $item->description ?? '',
    'qty' => $item->quantity ?? 0,
];
```

#### AFTER - Pending rows with year field + software filter
```php
if (!$item->product || !str_starts_with($item->product->solution ?? '', 'software')) continue;
$pendingInitialRows[] = [
    'product_code' => $item->product->code ?? '',
    'description' => $getShortName($item->product->description ?? $item->description ?? ''),
    'qty' => $item->quantity ?? 0,
    'year' => $item->year ?? 'Year 1',
];
```

#### NEW - Auto-calculate billing cycle
```php
$distinctYears = count(array_unique(array_column($pendingInitialRows, 'year')));
$defaultPendingBillingCycle = max($distinctYears, 1) * 12;
```

#### BEFORE - Alpine.js billing cycle default
```php
plBillingCycle: {{ $savedPendingBillingCycle ?? 12 }},
```

#### AFTER - Alpine.js billing cycle default
```php
plBillingCycle: {{ $savedPendingBillingCycle ?? $defaultPendingBillingCycle ?? 12 }},
```

#### BEFORE - `plUpdateDescription` (index-based)
```js
plUpdateDescription(index) {
    const p = this.plProducts.find(p => p.code === this.plRows[index].product_code);
    this.plRows[index].description = p ? p.description : '-';
},
```

#### AFTER - `plUpdateDescription` (row-based) + `plYears` getter
```js
plUpdateDescription(row) {
    const p = this.plProducts.find(p => p.code === row.product_code);
    row.description = p ? p.short_name : '-';
},
get plYears() {
    const years = [...new Set(this.plRows.map(r => r.year))];
    years.sort();
    return years;
},
```

#### BEFORE - Flat items table
```html
<template x-for="(row, index) in plRows" :key="index">
    <tr>...</tr>
</template>
```

#### AFTER - Year-grouped items with sub-headers
```html
<template x-for="(year, yIndex) in plYears" :key="year">
    <div style="margin-bottom: 12px;">
        <template x-if="plYears.length > 1">
            <div style="font-weight:700; background:#e5e7eb; border-radius:4px; padding:6px 8px;"
                 x-text="year"></div>
        </template>
        <table>
            <thead>...</thead>
            <tbody>
                <template x-for="(row, index) in plRows.filter(r => r.year === year)"
                          :key="row.product_code + '-' + year + '-' + index">
                    <tr>...</tr>
                </template>
            </tbody>
        </table>
    </div>
</template>
```
