# 052 - Quotation: Restore Subscription Period & Year Fields

## Summary
Restored the previously hidden "Subscription Period" and "Year" fields in the Create Quotation form, making them visible for software products. Subscription Period is now editable (1-12 months) with validation notifications. Year field is read-only and auto-calculated. Also removed the `[YEAR-X SUBSCRIPTION]` prefix from product descriptions (now handled by year badges from #048).

## Plain English Explanation
The Subscription Period and Year fields were hidden (`->hidden()`) in the Quotation form but needed to be visible for software products. Subscription Period now shows with min/max validation (1-12 months) and warning notifications. Year is auto-calculated based on duplicate product position and displayed read-only. The `[YEAR-X SUBSCRIPTION]` prefix that was being prepended to descriptions is removed since year badges (#048) already provide that visual distinction.

## Files Changed

### `app/Filament/Resources/QuotationResource.php`

#### BEFORE - Subscription Period (hidden)
```php
TextInput::make('subscription_period')
    ->hidden()
    ->numeric()
    ->default(12)
    ->dehydrated(true)
```

#### AFTER - Subscription Period (visible for software products)
```php
TextInput::make('subscription_period')
    ->label('Subscription Period')
    ->numeric()
    ->default(12)
    ->maxValue(12)
    ->minValue(1)
    ->suffix('months')
    ->dehydrated(true)
    ->visible(function(Forms\Get $get) {
        $productId = $get('product_id');
        if ($productId != null) {
            $product = Product::find($productId);
            if ($product && $get('../../quotation_type') == 'product' && str_starts_with($product->solution, 'software')) {
                return true;
            }
        }
        return false;
    })
```

#### BEFORE - Year (hidden)
```php
TextInput::make('year')
    ->hidden()
    ->dehydrated(true)
```

#### AFTER - Year (visible, read-only for software products)
```php
TextInput::make('year')
    ->label('Year')
    ->readOnly()
    ->dehydrated(true)
    ->helperText('Auto-calculated based on duplicate products')
    ->visible(function(Forms\Get $get) { /* software product check */ })
```

#### BEFORE - Description with YEAR prefix
```php
'description' => ($isSoftware && $yearCount > 1)
    ? "<p><strong>[YEAR-{$year} SUBSCRIPTION]</strong></p>" . $product->description
    : $product->description,
```

#### AFTER - Description without YEAR prefix
```php
'description' => $product->description,
```
