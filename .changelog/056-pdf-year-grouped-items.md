# 056 - Quotation & Proforma Invoice PDF: Year-Grouped Items with Ordinal Sub-headers

## Summary
Added year-based grouping to Quotation PDF and Proforma Invoice PDF. Multi-year subscription items now display under ordinal sub-headers ("1st Year Subscription", "2nd Year Subscription", etc.) with a blue highlight row. Also fixed the software solution check from exact match to `str_starts_with`.

## Plain English Explanation
Previously both PDFs rendered items in a flat list sorted by `sort_order`. Now items are grouped by their `year` field (from QuotationDetail). If any items have year values, blue sub-header rows appear before each year group. Items without a year value (hardware, services) render normally without grouping.

## Files Changed

### `resources/views/pdf/quotation-v2.blade.php`

#### BEFORE - Flat item loop
```php
$sortedItems = $quotation->items->sortBy('sort_order');
@foreach($sortedItems as $item)
```

#### AFTER - Year-grouped item loop
```php
$sortedItems = $quotation->items->sortBy('sort_order');
$groupedItems = $sortedItems->groupBy(fn($item) => $item->year ?? 'none');
$hasYearGroups = $groupedItems->keys()->filter(fn($k) => $k !== 'none')->isNotEmpty();

@foreach($groupedItems as $yearKey => $yearItems)
    @if($hasYearGroups && $yearKey !== 'none')
        @php
            $yearNum = (int) str_replace('Year ', '', $yearKey);
            $ordinal = match($yearNum) { 1 => '1st', 2 => '2nd', 3 => '3rd', default => $yearNum.'th' };
        @endphp
        <tr style="background: #e8f0fe;">
            <td colspan="7" style="font-weight:bold; color:#005baa;">
                {{ $ordinal }} Year Subscription
            </td>
        </tr>
    @endif
    @foreach($yearItems as $item)
        {{-- existing item row --}}
    @endforeach
@endforeach
```

#### BEFORE - Software solution check
```php
if ($item->product && $item->product->solution == 'software') {
```

#### AFTER - Software solution check
```php
if ($item->product && str_starts_with($item->product->solution, 'software')) {
```

### `resources/views/pdf/proforma-invoice-v2.blade.php`
Same changes as `quotation-v2.blade.php` (year grouping + software solution fix).
