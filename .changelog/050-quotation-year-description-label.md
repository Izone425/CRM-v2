# 050 - Quotation Items: Bold Year Label in Description

## Summary
Prepend a bold `[YEAR-X SUBSCRIPTION]` label at the top of each quotation item's description for multi-year software products.

## Plain English Explanation
When a multi-year package is selected, each software product item's description now starts with a bold year label like **[YEAR-1 SUBSCRIPTION]** or **[YEAR-2 SUBSCRIPTION]**. This makes it immediately clear which subscription year the item belongs to when reading the description. The label is wrapped in HTML `<strong>` tags since the description uses a RichEditor.

## Files Changed

### `app/Filament/Resources/QuotationResource.php`

#### BEFORE - Item description in duplicatable products loop
```php
'description' => $product->description,
```

#### AFTER - Item description with year label
```php
'description' => ($isSoftware && $yearCount > 1)
    ? "<p><strong>[YEAR-{$year} SUBSCRIPTION]</strong></p>" . $product->description
    : $product->description,
```

#### BEFORE - Description auto-fill in recalculateAllRowsFromParent()
```php
if (blank($currentDescription) && $product) {
    $set("items.{$index}.description", $product?->description);
}
```

#### AFTER - Description auto-fill with year label
```php
if (blank($currentDescription) && $product) {
    $desc = $product->description;
    $yearNum = $itemYearNums[$index] ?? null;
    $yearCount = self::getPackageYearCount($get('package_group'));
    if ($yearNum && $yearCount > 1 && str_starts_with($product->solution, 'software')) {
        $desc = "<p><strong>[YEAR-{$yearNum} SUBSCRIPTION]</strong></p>" . $desc;
    }
    $set("items.{$index}.description", $desc);
}
```
