# 054 - Company License Details: Real Trial Data from PI Invoice Data

## Summary
Replaced hardcoded mock data in the Company License Details > Products tab with real trial license data derived from `type_1_pi_invoice_data` (saved by "Confirm Create DB"). Also fixed Total License counts to derive from actual license records instead of boolean flags.

## Plain English Explanation
Previously, `loadLicenseRecords()` had 15 hardcoded fake records (e.g., "Trial231224001"). Now it reads from `SoftwareHandover->type_1_pi_invoice_data` to build TRIAL records with correct product names, quantities, dates, and invoice number (format: `TRYYMMDD + licenseSetId`, e.g., "TR260227306"). The `loadProductData()` method was also fixed - it used to check boolean flags (`ta`, `tl`, `tc`, `tp`) which were all false, causing all zeros. Now it derives user counts from the license records themselves.

## Files Changed

### `app/Livewire/HrAdminDashboard/CompanyProductsTab.php`

#### Added import
```php
use Carbon\Carbon;
```

#### Fixed mount order
```php
// BEFORE
$this->loadProductData();
$this->loadLicenseRecords();

// AFTER (license records must load first so productData can derive from them)
$this->loadLicenseRecords();
$this->loadProductData();
```

#### BEFORE - `loadLicenseRecords()` (~260 lines of hardcoded mock data)
```php
protected function loadLicenseRecords(): void
{
    $this->licenseRecords = [
        ['no' => 1, 'type' => 'TRIAL', 'invoice_no' => 'Trial231224001', ...],
        // ... 15 hardcoded fake records
    ];
}
```

#### AFTER - `loadLicenseRecords()` (real data from PI invoice)
```php
protected function loadLicenseRecords(): void
{
    $softwareHandover = $this->companyData['software_handover'] ?? null;
    $this->licenseRecords = [];
    if (!$softwareHandover) return;

    if ($softwareHandover->crm_buffer_license_id && $softwareHandover->type_1_pi_invoice_data) {
        $piData = is_string($softwareHandover->type_1_pi_invoice_data)
            ? json_decode($softwareHandover->type_1_pi_invoice_data, true)
            : $softwareHandover->type_1_pi_invoice_data;
        $items = $piData['items'] ?? [];
        $bufferMonth = (int) ($piData['buffer_month'] ?? 1);
        // Build trial invoice number: TRYYMMDD + licenseSetId
        $trialInvoiceNo = 'TR' . Carbon::parse($softwareHandover->db_creation)->format('ymd')
            . $softwareHandover->crm_buffer_license_id;
        foreach ($items as $item) {
            // Map product code to license name, build record with real data
        }
    }
}
```

#### BEFORE - `loadProductData()` (boolean flags)
```php
'attendance_user' => [
    'total' => ($softwareHandover?->ta ?? false) ? $totalUsers : 0,
    ...
],
```

#### AFTER - `loadProductData()` (derives from license records)
```php
foreach ($this->licenseRecords as $record) {
    if (($record['status'] ?? '') === 'expired') continue;
    $type = strtolower($record['license_type'] ?? '');
    $users = (int) ($record['total_user'] ?? 0);
    if (str_contains($type, 'attendance')) $counts['attendance_user'] += $users;
    if (str_contains($type, 'leave')) $counts['leave_user'] += $users;
    // etc.
}
```
