# 051 - Quotation Items: Rename Quantity Label

## Summary
Changed the quantity field label from "Quantity" back to "Quantity/Headcount" as per user preference.

## Files Changed

### `app/Filament/Resources/QuotationResource.php`

#### BEFORE
```php
TextInput::make('quantity')
    ->label('Quantity')
```

#### AFTER
```php
TextInput::make('quantity')
    ->label('Quantity/Headcount')
```
