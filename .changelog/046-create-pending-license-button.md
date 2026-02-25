# Change 046: Create Pending License button + drawer

## Summary
Add a new "Create Pending License" button in Software Handover Details, positioned between "Create DB + Trial License" and "Export AutoCount Debtor". Same concept — reads SW+HW Proforma Invoice items, shows editable table (Product Code dropdown, Description, Qty), Buffer Month (1-6), date picker, and trial license period summary. Data saved to `type_2_pi_invoice_data`.

## Type
New Feature + UI

## Files Modified

### 1. `resources/views/components/software-handover.blade.php`

**BEFORE:** Only 3 buttons: Create DB + Trial License, Export AutoCount Debtor, Export AutoCount Invoice.

**AFTER:** Added 4th button "Create Pending License" (green, key icon) between Create DB and Export Debtor, with:
- `@php` block: sets up `$confirmPendingUrl`, `$pendingInitialRows`, `$savedPendingBufferMonth`, `$existingPendingDate` from `type_2_pi_invoice_data`
- Alpine `x-data`: `showPendingDrawer`, `plConfirming`, `plConfirmed`, `plDate`, `plBufferMonth`, `plRows`, `plProducts`, computed `plStartDate`/`plEndDate`
- Right-side drawer (500px): PI reference, editable items table, Buffer Month dropdown, Pending License Date picker, trial period summary
- Footer: Cancel + "Confirm Pending License" button with fetch POST to `/software-handover/confirm-pending-license/{id}`

### 2. `app/Http/Controllers/SoftwareHandoverExportController.php`

**AFTER:** Added `confirmCreatePendingLicense()` method:
```php
public function confirmCreatePendingLicense(Request $request, $softwareHandoverId)
{
    $decryptedId = Encryptor::decrypt($softwareHandoverId);
    $handover = SoftwareHandover::findOrFail($decryptedId);
    $pendingDate = $request->input('pending_date', now()->format('Y-m-d'));
    $items = $request->input('items', []);
    $bufferMonth = $request->input('buffer_month', 1);
    $handover->update([
        'type_2_pi_invoice_data' => [
            'items' => $items,
            'buffer_month' => $bufferMonth,
            'pending_date' => $pendingDate,
        ],
    ]);
    return response()->json(['success' => true, 'date' => $pendingDate]);
}
```

### 3. `routes/web.php`

**AFTER:** Added POST route:
```php
Route::post('/software-handover/confirm-pending-license/{softwareHandover}', ...)
    ->name('software-handover.confirm-pending-license')
    ->middleware(['auth']);
```

## Migration Steps
- No migration needed. Uses existing `type_2_pi_invoice_data` JSON column on `software_handovers` table.

## Rollback Plan
- Remove the Create Pending License block from blade
- Remove `confirmCreatePendingLicense()` method from controller
- Remove the POST route from web.php
