# 049 - Quotation Items: Hide Subscription Period & Year Fields

## Summary
Hidden two fields from the quotation items UI to reduce clutter:
- **Subscription Period** (12 months) — hidden but still works internally with default value 12
- **Year** field — hidden since year badges in item headers already show this information

## Plain English Explanation
The Subscription Period and Year fields were visible inside each quotation item but were read-only and auto-calculated. Since subscription period is always 12 months for software and the year information is now shown as colored badges on the collapsed item headers, these fields are redundant in the expanded view. They are now hidden (`->hidden()`) but keep `->dehydrated(true)` so their values still submit with the form.

## Files Changed

### `app/Filament/Resources/QuotationResource.php`

#### BEFORE - Subscription Period field
```php
TextInput::make('subscription_period')
    ->label('Subscription Period')
    ->numeric()
    ->default(12)
    ->maxValue(12)
    ->minValue(1)
    ->suffix('months')
    ->live(onBlur: true)
    ->afterStateUpdated(...)
    ->visible(function(Forms\Get $get) { ... }),
```

#### AFTER - Subscription Period field
```php
TextInput::make('subscription_period')
    ->hidden()
    ->numeric()
    ->default(12)
    ->dehydrated(true)
    ->live(onBlur: true)
    ->afterStateUpdated(...),
```

#### BEFORE - Year field
```php
TextInput::make('year')
    ->label('Year')
    ->columnSpan(['md' => 1])
    ->readOnly()
    ->dehydrated(true)
    ->helperText('Auto-calculated based on duplicate products')
    ->visible(function(Forms\Get $get) { ... })
    ->afterStateHydrated(...),
```

#### AFTER - Year field
```php
TextInput::make('year')
    ->hidden()
    ->dehydrated(true)
    ->afterStateHydrated(...),
```
