# 048 - Quotation Items: Year Badges, Per-Year Unit Prices & Software Solution Fix

## Summary
Fixed multi-year package features in Create Quotation form:
- **Year badges** on collapsed item headers (e.g., "1st Year" blue pill, "2nd Year" purple pill) to differentiate duplicate product codes
- **Per-year unit prices** flow from parent fields (Unit Price Year 1/2/3/4/5) to corresponding quotation items
- **Root cause fix**: All `$product->solution === 'software'` checks were failing because actual DB values are `software_new_sales` and `software_renewal_sales`. Updated ~30 occurrences to use `str_starts_with($product->solution, 'software')`

## Plain English Explanation
When a user selects a multi-year package (e.g., "Package 3 - 2 Year Subscription"), the system duplicates software products for each year. Previously, the item headers all showed "Product Code: TCL_TA USER-NEW" with no way to tell which year each belongs to. Now each item header shows a colored year badge. Also, setting Unit Price Year 1 = 8 and Unit Price Year 2 = 4 now correctly flows those prices to the respective year's items. The root cause was that the code compared `$product->solution` against the literal string `'software'`, but the database stores `'software_new_sales'` and `'software_renewal_sales'`.

## Files Changed

### `app/Filament/Resources/QuotationResource.php`

#### BEFORE - Software solution checks (~30 occurrences)
```php
$product->solution === 'software'
$product->solution == 'software'
$product->solution !== 'software'
$product?->solution === 'software'
in_array($product->solution, ['software', 'hardware'])
```

#### AFTER - Software solution checks
```php
str_starts_with($product->solution, 'software')
str_starts_with($product->solution, 'software')
!str_starts_with($product->solution, 'software')
str_starts_with($product?->solution ?? '', 'software')
str_starts_with($product->solution, 'software') || str_starts_with($product->solution, 'hardware')
```

#### BEFORE - itemLabel (no year badge)
```php
->itemLabel(fn(?array $state) => $state ? 'Product Code: ' . Product::find($state['product_id'])?->code : null)
```

#### AFTER - itemLabel with colored year badges
```php
->itemLabel(
    function(?array $state, \Filament\Forms\Components\Repeater $component, string $uuid): \Illuminate\Support\HtmlString|string|null {
        // ... position-based year computation
        $badgeColors = [
            1 => 'background:linear-gradient(135deg,#3b82f6,#60a5fa)',  // Blue
            2 => 'background:linear-gradient(135deg,#8b5cf6,#a78bfa)',  // Purple
            3 => 'background:linear-gradient(135deg,#f59e0b,#fbbf24)',  // Amber
            4 => 'background:linear-gradient(135deg,#10b981,#34d399)',  // Green
            5 => 'background:linear-gradient(135deg,#ef4444,#f87171)',  // Red
        ];
        $label .= ' <span style="...">' . $ordinal . ' Year</span>';
        return new \Illuminate\Support\HtmlString($label);
    }
)
```

#### NEW - `computeItemYearNumbers()` helper method
```php
public static function computeItemYearNumbers(array $items, $products = null): array
{
    // Counts Nth occurrence of each software product to determine year number
    // Returns array mapping item index => year number
}
```

#### NEW - Per-year unit price fields (unit_price_year_1 through unit_price_year_5)
```php
TextInput::make('unit_price_year_1')
    ->label(fn($get) => $yearCount > 1 ? 'Unit Price (Year 1)' : 'Unit Price')
    ->afterStateUpdated(function($state, $get, $set) {
        // Sets unit_price on all Year 1 software items
    })
// ... same pattern for year 2-5
```
