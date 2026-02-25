# Change 045: Create DB + Trial License drawer — Buffer Month dropdown

## Summary
Add a "Buffer Month" dropdown to the Create DB + Trial License drawer. Default is 1 Month, options range from 1 to 6 Months. The value is persisted alongside items in `type_1_pi_invoice_data`.

## Type
Fix/Change + UI

## Files Modified

### 1. `resources/views/components/software-handover.blade.php`

**BEFORE:**
- No buffer month field in drawer
- `type_1_pi_invoice_data` loaded as flat items array
- Alpine x-data had no `bufferMonth` state

**AFTER:**
- Added `bufferMonth` to Alpine x-data (default 1, loaded from saved data)
- Added "Buffer Month" `<select>` dropdown below "Create DB Date" with options 1-6 Months
- Dropdown disabled after confirmation (`x-bind:disabled="confirmed"`)
- `fetch()` POST body now includes `buffer_month: bufferMonth`
- PHP init block handles new data structure: `{ items: [...], buffer_month: N }` with backward compat for old flat array format

### 2. `app/Http/Controllers/SoftwareHandoverExportController.php`

**BEFORE:**
```php
$handover->update([
    'db_creation' => $dbDate,
    'type_1_pi_invoice_data' => $items,
]);
```

**AFTER:**
```php
$bufferMonth = $request->input('buffer_month', 1);
$handover->update([
    'db_creation' => $dbDate,
    'type_1_pi_invoice_data' => [
        'items' => $items,
        'buffer_month' => $bufferMonth,
    ],
]);
```

## Migration Steps
- No migration needed. Uses existing `type_1_pi_invoice_data` JSON column.

## Rollback Plan
- Remove buffer month dropdown from blade
- Revert controller to save flat items array
- Revert PHP init block to flat array loading
