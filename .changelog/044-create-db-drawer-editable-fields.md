# Change 044: Create DB + Trial License drawer — editable fields

## Summary
Make the Create DB + Trial License drawer fields editable before confirming: Product Code becomes a dropdown, Qty becomes an editable input, and Create DB Date becomes an editable date picker. Edited data is persisted to `type_1_pi_invoice_data` and `db_creation` on the SoftwareHandover record.

## Type
Fix/Change + UI

## Files Modified

### 1. `resources/views/components/software-handover.blade.php`

**BEFORE:**
- Static table with read-only text cells for Product Code, Description, Qty
- Read-only text input for Create DB Date
- Static Blade `@forelse` loop rendering PI items
- `x-data` only had `showDatabaseDrawer`, `confirming`, `confirmed`
- `fetch()` POST sent no body data

**AFTER:**
- Alpine.js-managed `rows` array initialized from PI items (or saved `type_1_pi_invoice_data`)
- Product Code → `<select>` dropdown populated from `Product` table (TIMETEC-* excluding ONBOARD)
- Description → auto-updates when product code changes via `updateDescription(index)`
- Qty → `<input type="number">` bound to `row.qty`
- Create DB Date → `<input type="date">` bound to `dbDate` (editable)
- All inputs disabled after confirmation (`x-bind:disabled="confirmed"`)
- `fetch()` POST now sends `{ items: rows, db_date: dbDate }` in JSON body
- `x-data` includes `rows`, `products`, `dbDate`, `updateDescription()` method

### 2. `app/Http/Controllers/SoftwareHandoverExportController.php`

**BEFORE:**
```php
public function confirmCreateDb($softwareHandoverId)
{
    $decryptedId = Encryptor::decrypt($softwareHandoverId);
    $handover = SoftwareHandover::findOrFail($decryptedId);
    $handover->update(['db_creation' => now()]);
    return response()->json(['success' => true, 'date' => now()->format('Y-m-d')]);
}
```

**AFTER:**
```php
public function confirmCreateDb(Request $request, $softwareHandoverId)
{
    $decryptedId = Encryptor::decrypt($softwareHandoverId);
    $handover = SoftwareHandover::findOrFail($decryptedId);
    $dbDate = $request->input('db_date', now()->format('Y-m-d'));
    $items = $request->input('items', []);
    $handover->update([
        'db_creation' => $dbDate,
        'type_1_pi_invoice_data' => $items,
    ]);
    return response()->json(['success' => true, 'date' => $dbDate]);
}
```

## Migration Steps
- No migration needed. Uses existing `type_1_pi_invoice_data` JSON column and `db_creation` date column on `software_handovers` table.

## Rollback Plan
- Revert blade changes to static table with read-only cells
- Revert controller to only save `db_creation => now()`
