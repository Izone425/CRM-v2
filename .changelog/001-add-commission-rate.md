# Change #001: Add Commission Rate to Payment Information

> **Date**: 2026-01-29
> **Type**: New Feature + Database
> **Status**: Completed

---

## Summary

**What**: Added a "Commission Rate" dropdown field (0-100) to the Payment Information section in Company License Details > Profile tab. Only visible for Reseller and Distributor companies.

**Why**: Resellers/Distributors need a per-company commission rate setting stored separately from global defaults.

**Impact**: Company Profile Tab for Reseller/Distributor companies now shows and saves a commission rate.

**Breaking Change**: Yes (requires database migration for new table)

---

## What It Does (Plain English)

### The Flow

1. **When this happens**: Admin navigates to Company License Details > Profile for a Reseller/Distributor company
2. **The system does this**: Loads the commission rate from the `reseller_v2_commissions` table (linked via ResellerV2)
3. **Then**: Displays it in the Payment Information section (view mode shows "X%", edit mode shows a dropdown 0-100)
4. **Finally**: When saved, uses `updateOrCreate` to store the commission rate in the new table

### Before vs After (Behavior)

| Before | After |
|--------|-------|
| No commission rate field in Payment Information | Commission Rate dropdown appears for Reseller/Distributor companies |
| Commission rates only existed as global defaults in PaymentSetting | Per-company commission rate stored in dedicated table |

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `database/migrations/2026_01_29_000000_create_reseller_v2_commissions_table.php` | New | Migration for `reseller_v2_commissions` table |
| `app/Models/ResellerV2Commission.php` | New | Eloquent model for the new table |
| `app/Models/ResellerV2.php` | Modified | Added `commission()` hasOne relationship |
| `app/Livewire/HrAdminDashboard/CompanyLicenseDetailsContainer.php` | Modified | Added ResellerV2 lookup with eager-loaded commission |
| `app/Livewire/HrAdminDashboard/CompanyProfileTab.php` | Modified | Added commission rate load/save logic |
| `resources/views/livewire/hr-admin-dashboard/company-profile-tab.blade.php` | Modified | Added commission rate UI in view/edit modes |

---

## Code Changes (BEFORE -> AFTER)

---

### File: `database/migrations/2026_01_29_000000_create_reseller_v2_commissions_table.php`

**Change Type**: New

**What Changed**: Created migration for `reseller_v2_commissions` table

#### BEFORE (Old Code)
```
N/A - New file
```

#### AFTER (New Code)
```php
Schema::create('reseller_v2_commissions', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('reseller_v2_id')->unique();
    $table->unsignedTinyInteger('commission_rate')->default(0);
    $table->timestamps();

    $table->foreign('reseller_v2_id')
          ->references('id')
          ->on('reseller_v2')
          ->onDelete('cascade');
});
```

---

### File: `app/Models/ResellerV2Commission.php`

**Change Type**: New

**What Changed**: Eloquent model with `belongsTo(ResellerV2::class)` relationship

#### BEFORE (Old Code)
```
N/A - New file
```

#### AFTER (New Code)
```php
class ResellerV2Commission extends Model
{
    protected $table = 'reseller_v2_commissions';
    protected $fillable = ['reseller_v2_id', 'commission_rate'];
    protected $casts = ['commission_rate' => 'integer'];

    public function resellerV2(): BelongsTo
    {
        return $this->belongsTo(ResellerV2::class, 'reseller_v2_id');
    }
}
```

---

### File: `app/Models/ResellerV2.php`

**Change Type**: Modified

**What Changed**: Added `commission()` hasOne relationship

#### BEFORE (Old Code)
```php
public function reseller()
{
    return $this->belongsTo(Reseller::class, 'reseller_id');
}
// No commission relationship existed
```

#### AFTER (New Code)
```php
public function reseller()
{
    return $this->belongsTo(Reseller::class, 'reseller_id');
}

// NEW: Commission relationship
public function commission()
{
    return $this->hasOne(ResellerV2Commission::class, 'reseller_v2_id');
}
```

---

### File: `app/Livewire/HrAdminDashboard/CompanyLicenseDetailsContainer.php`

**Change Type**: Modified

**What Changed**: Added ResellerV2 lookup with eager-loaded commission in `loadCompanyData()`

#### BEFORE (Old Code)
```php
// No ResellerV2 lookup existed
$this->companyData = [
    // ... existing fields
];
```

#### AFTER (New Code)
```php
// NEW: Lookup ResellerV2 via the shared reseller_id
$resellerV2 = null;
if ($softwareHandover && $softwareHandover->reseller_id) {
    $resellerV2 = ResellerV2::with('commission')
        ->where('reseller_id', $softwareHandover->reseller_id)
        ->first();
}

$this->companyData = [
    // ... existing fields
    'reseller_v2' => $resellerV2, // NEW
];
```

---

### File: `app/Livewire/HrAdminDashboard/CompanyProfileTab.php`

**Change Type**: Modified

**What Changed**: Added `$commissionRate` property, `loadCommissionRate()`, `saveCommissionRate()`, and `isResellerOrDistributor()` methods

#### BEFORE (Old Code)
```php
// No commission rate property or methods
```

#### AFTER (New Code)
```php
public ?int $commissionRate = null; // NEW property

// NEW: Check if company is Reseller or Distributor
public function isResellerOrDistributor(): bool
{
    $licenseCategory = $this->companyData['license_category'] ?? 'Subscriber';
    return in_array($licenseCategory, ['Reseller', 'Distributor']);
}

// NEW: Load commission rate (called from loadPaymentInfo)
protected function loadCommissionRate(): void { /* ... */ }

// NEW: Save commission rate (called from savePaymentInfo)
protected function saveCommissionRate(): void { /* ... */ }
```

---

### File: `resources/views/livewire/hr-admin-dashboard/company-profile-tab.blade.php`

**Change Type**: Modified

**What Changed**: Added Commission Rate display in view mode and dropdown in edit mode (Payment Information section)

#### BEFORE (Old Code)
```blade
{{-- No commission rate field in Payment Information --}}
```

#### AFTER (New Code)
```blade
{{-- View mode: after PayPal Email --}}
@if($this->isResellerOrDistributor())
    <div>
        <span class="text-sm text-gray-600">Commission Rate</span>
        <p class="text-sm font-medium text-gray-900 mt-1">{{ $commissionRate !== null ? $commissionRate . '%' : '-' }}</p>
        <p class="text-xs text-gray-500">This is the Commission Rate for this dealer. Eg: 30, 40, 50.</p>
    </div>
@endif

{{-- Edit mode: dropdown 0-100 --}}
@if($this->isResellerOrDistributor())
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            <span class="text-red-500">*</span> Commission Rate:
        </label>
        <select wire:model="commissionRate" class="w-full px-3 py-2 border border-gray-300 rounded-md ...">
            <option value="">Select Commission Rate</option>
            @for($i = 0; $i <= 100; $i++)
                <option value="{{ $i }}">{{ $i }}</option>
            @endfor
        </select>
    </div>
@endif
```

---

## Dependencies & Migration Steps

### Migration Steps (in order)

1. Run database migration: `php artisan migrate --path=database/migrations/2026_01_29_000000_create_reseller_v2_commissions_table.php`

### Rollback Plan

1. Run: `php artisan migrate:rollback --path=database/migrations/2026_01_29_000000_create_reseller_v2_commissions_table.php`
2. Remove commission-related code from CompanyProfileTab.php
3. Remove `ResellerV2Commission.php` model
4. Remove `commission()` relationship from `ResellerV2.php`

---

## Testing Notes

### How to Test

1. Navigate to Company License Details for a **Reseller** company
2. Go to Profile tab > Payment Information section
3. Click Edit - verify Commission Rate dropdown appears (0-100)
4. Select a value (e.g., 30), click Save
5. Verify the value persists after page reload
6. Navigate to a **Subscriber** company - verify the field is NOT shown

---

## Completion Checklist

- [x] Code changes reviewed
- [x] BEFORE/AFTER code documented
- [x] Database migration tested (completed in 68ms)
- [x] Tested locally
- [x] Ready for developer handoff
