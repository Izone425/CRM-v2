# 053 - Create DB+Trial License: Software Filter, Simplified Descriptions & CRM API Integration

## Summary
Overhauled the "Create DB + Trial License" drawer in Software Handover:
- **Filter**: Only software products shown (was excluding ONBOARD only)
- **Descriptions**: Simplified from raw HTML to clean format (e.g., "Timetec-Attendance")
- **Year filter**: Only Year 1 items shown (for trial license)
- **CRM API**: "Confirm Create DB" now auto-creates CRM account, creates buffer/trial licenses via API with seat limits, and creates local `hr_licenses` record
- **Error handling**: Shows alert on API failure instead of silently failing

## Plain English Explanation
Previously, "Confirm Create DB" only saved the date and items locally. Now it performs a full end-to-end flow:
1. Auto-creates a CRM account (if not yet created) using customer credentials from the Customer model
2. Calls the CRM API `addBufferLicense` with applications (Attendance, Leave, Claim, Payroll), seat limits per module, and trial dates
3. Stores the returned `licenseSetId` on the SoftwareHandover record
4. Creates a local `HrLicense` record so the trial appears in "All Licenses" page
5. Shows error alerts if any step fails

The product dropdown now uses `solution LIKE 'software%'` filter and a `$getShortName()` helper to extract clean names from HTML descriptions.

## Files Changed

### `resources/views/components/software-handover.blade.php`

#### NEW - `$getShortName()` helper (~line 802)
```php
$getShortName = function($description) {
    if (preg_match('/<strong>(.*?)<\/strong>/i', $description ?? '', $matches)) {
        $name = strip_tags($matches[1]);
    } else {
        $name = strip_tags($description ?? '');
    }
    $name = trim(preg_replace('/^TIMETEC\s*-\s*/i', '', trim($name)));
    $name = trim(preg_replace('/^TIME\s+/i', '', $name));
    return 'Timetec-' . ucwords(strtolower($name));
};
```

#### BEFORE - Product query
```php
$dbProducts = \App\Models\Product::where('code', 'LIKE', 'TIMETEC-%')
    ->where('code', 'NOT LIKE', '%ONBOARD%')
    ->orderBy('code')
    ->get(['id', 'code', 'description']);
```

#### AFTER - Product query (software filter + short_name)
```php
$dbProducts = \App\Models\Product::where('solution', 'LIKE', 'software%')
    ->orderBy('code')
    ->get(['id', 'code', 'description'])
    ->map(function($p) use ($getShortName) {
        $p->short_name = $getShortName($p->description);
        return $p;
    });
```

#### BEFORE - Item filter
```php
if ($item->product && str_contains(strtoupper($item->product->code ?? ''), 'ONBOARD')) continue;
```

#### AFTER - Item filter (software + Year 1 only)
```php
if (!$item->product || !str_starts_with($item->product->solution ?? '', 'software')) continue;
if ($item->year && $item->year !== 'Year 1') continue;
```

#### BEFORE - Confirm button (simple save)
```php
.then(r => r.json())
.then(() => { confirmed = true; confirming = false; })
.catch(() => { confirming = false; });
```

#### AFTER - Confirm button (with error handling)
```php
.then(r => r.json().then(data => ({ ok: r.ok, data })))
.then(({ ok, data }) => {
    if (ok && data.success) {
        confirmed = true; confirming = false;
    } else {
        confirming = false;
        alert(data.error || 'Failed to create trial licenses.');
    }
})
.catch(() => { confirming = false; alert('Network error.'); });
```

### `app/Http/Controllers/SoftwareHandoverExportController.php`

#### NEW - CRM API integration in `confirmCreateDb()` (~line 534-650)
```php
// Auto-create CRM account if needed
if (!$handover->hr_account_id || !$handover->hr_company_id) {
    $this->createCRMAccountForHandover($handover);
}

// Map product codes to API app names
$productCodeToApp = [
    'TCL_TA' => 'Attendance', 'TCL_LEAVE' => 'Leave',
    'TCL_CLAIM' => 'Claim', 'TCL_PAYROLL' => 'Payroll',
];

// Create buffer license via CRM API (single call, all apps)
$result = $crmService->addBufferLicense($accountId, $companyId, [
    'applications' => $appList,
    'startDate' => $startDate, 'endDate' => $endDate,
    'seatLimits' => $seatLimits,
    'notes' => 'Trial license created from CRM Software Handover',
]);

// Store licenseSetId and create local HrLicense record
$handover->update(['crm_buffer_license_id' => $result['data']['licenseSetId']]);
\App\Models\HrLicense::updateOrCreate(['handover_id' => $handoverId], [...]);
```

#### NEW - `createCRMAccountForHandover()` helper (~line 649-746)
```php
protected function createCRMAccountForHandover(SoftwareHandover $handover): void
{
    // Gets customer credentials, country data, phone number
    // Calls CRM API createAccount()
    // Saves hr_account_id, hr_company_id, hr_user_id
}
```

### `app/Services/CRMApiService.php`

#### BEFORE - `addBufferLicense()` (pass-through)
```php
public function addBufferLicense(int $accountId, int $companyId, array $licenseData): array
{
    $endpoint = "/api/crm/account/{$accountId}/company/{$companyId}/licenses/buffer";
    return $this->makeRequest('POST', $endpoint, $licenseData);
}
```

#### AFTER - `addBufferLicense()` (structured payload with applications + seatLimits)
```php
public function addBufferLicense(int $accountId, int $companyId, array $licenseData): array
{
    $endpoint = "/api/crm/account/{$accountId}/company/{$companyId}/licenses/buffer";
    $payload = [
        'startDate' => $licenseData['startDate'],
        'endDate' => $licenseData['endDate'],
        'notes' => $licenseData['notes'] ?? null,
    ];
    if (isset($licenseData['applications'])) $payload['applications'] = $licenseData['applications'];
    if (isset($licenseData['seatLimits'])) $payload['seatLimits'] = $licenseData['seatLimits'];
    return $this->makeRequest('POST', $endpoint, $payload);
}
```
