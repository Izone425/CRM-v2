# Change #003: Build Company Customer Tab with Resellers & Subscribers Tables

> **Date**: 2026-01-29
> **Type**: New Feature
> **Status**: Completed

---

## Summary

**What**: Replaced the "Coming Soon" placeholder in the Company License Details > Customer tab with a fully functional page showing two data tables (Resellers and Customers/Subscribers) with search and filter capabilities.

**Why**: Admin users need to see which sub-resellers and subscriber customers belong to a Reseller/Distributor company.

**Impact**: Customer tab now displays live data from `hr_licenses` and `software_handovers` tables, with search by name, status filter, and date range filter.

**Breaking Change**: No

---

## What It Does (Plain English)

### The Flow

1. **When this happens**: Admin clicks the "Customer" tab on a Reseller/Distributor company's License Details page
2. **The system does this**: Queries `hr_licenses` table for all records linked to the same `reseller_id` (via `software_handovers`), excluding the current company
3. **Then**: Splits results by `license_category` - "Reseller" records go to the Resellers table, "Subscriber" records go to the Customers (Subscriber) table
4. **Finally**: Displays both tables with search/filter bar, active/inactive count badges, and clickable IDs that link to each company's details

### Before vs After (Behavior)

| Before | After |
|--------|-------|
| Customer tab showed "Coming Soon" placeholder with an icon | Customer tab shows search filters + two data tables (Resellers & Subscribers) |
| No data was loaded or displayed | Live data from `hr_licenses` + `software_handovers` tables |
| No search/filter capabilities | Search by name, filter by status, filter by date range |

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `app/Livewire/HrAdminDashboard/CompanyCustomerTab.php` | Modified (full rewrite) | Added query logic, search/filter properties, count computation |
| `resources/views/livewire/hr-admin-dashboard/company-customer-tab.blade.php` | Modified (full rewrite) | Replaced placeholder with filter bar + two data tables |

---

## Code Changes (BEFORE -> AFTER)

---

### File: `app/Livewire/HrAdminDashboard/CompanyCustomerTab.php`

**Change Type**: Modified (full rewrite)

**What Changed**: Replaced minimal placeholder component with full data-loading Livewire component

#### BEFORE (Old Code)
```php
<?php

namespace App\Livewire\HrAdminDashboard;

use Livewire\Component;

class CompanyCustomerTab extends Component
{
    public ?int $softwareHandoverId = null;
    public array $companyData = [];

    public function mount(?int $softwareHandoverId = null, array $companyData = [])
    {
        $this->softwareHandoverId = $softwareHandoverId;
        $this->companyData = $companyData;
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.company-customer-tab');
    }
}
```

#### AFTER (New Code)
```php
<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\HrLicense;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class CompanyCustomerTab extends Component
{
    public ?int $softwareHandoverId = null;
    public array $companyData = [];

    // NEW: Search/Filter properties
    public string $search = '';
    public string $statusFilter = 'all';
    public ?string $startDate = null;
    public ?string $endDate = null;

    // NEW: Data arrays
    public array $resellers = [];
    public array $subscribers = [];

    // NEW: Count properties (unfiltered totals for badges)
    public int $resellerActiveCount = 0;
    public int $resellerInactiveCount = 0;
    public int $subscriberActiveCount = 0;
    public int $subscriberInactiveCount = 0;

    public function mount(?int $softwareHandoverId = null, array $companyData = [])
    {
        $this->softwareHandoverId = $softwareHandoverId;
        $this->companyData = $companyData;
        $this->loadCustomers(); // NEW: Load data on mount
    }

    // NEW: Core data loading method
    public function loadCustomers(): void
    {
        // Gets reseller_id from current company's SoftwareHandover
        // Queries HrLicense WHERE softwareHandover.reseller_id = X
        //   AND softwareHandover.id != current (excludes self)
        // Applies search/status/date filters
        // Splits by license_category: 'Reseller' -> resellers, 'Subscriber' -> subscribers
        // Maps to display arrays with: id, software_handover_id, name, joined_date, status
    }

    // NEW: Separate unfiltered count query for badges
    protected function computeCounts(int $resellerId, int $currentSwId): void { /* ... */ }

    // NEW: Action methods
    public function searchCustomers(): void { $this->loadCustomers(); }
    public function resetFilters(): void { /* clears filters and reloads */ }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.company-customer-tab');
    }
}
```

#### Key Implementation Details
- **Data source**: `HrLicense` joined with `SoftwareHandover` via `whereHas('softwareHandover')`
- **Display mapping**: ID = `softwareHandover->hr_account_id`, Name = `hrLicense->company_name`, Joined Date = `softwareHandover->completed_at`, Status = `hrLicense->status`
- **Count query**: Uses `selectRaw` with `SUM(CASE WHEN...)` grouped by `license_category` for efficient counting
- **Error handling**: Try/catch with Log::error fallback

---

### File: `resources/views/livewire/hr-admin-dashboard/company-customer-tab.blade.php`

**Change Type**: Modified (full rewrite)

**What Changed**: Replaced "Coming Soon" placeholder with search/filter bar and two data tables

#### BEFORE (Old Code)
```blade
<div class="p-6">
    <div class="flex flex-col items-center justify-center py-16">
        <div class="w-20 h-20 rounded-full bg-blue-100 flex items-center justify-center mb-6">
            <svg class="w-10 h-10 text-blue-500" ...>...</svg>
        </div>
        <h3 class="text-xl font-semibold text-gray-800 mb-2">Customer Management</h3>
        <p class="text-gray-500 text-center max-w-md mb-4">
            Manage customers under this reseller account...
        </p>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-800">
            Coming Soon
        </span>
    </div>
</div>
```

#### AFTER (New Code)
```blade
<div class="p-6">
    {{-- Search/Filter Bar (HTML table layout for full-width stretch) --}}
    <table style="width: 100%; border-spacing: 8px; border-collapse: separate;" class="mb-8">
        <tr>
            <td style="width: 30%;">
                <input type="text" wire:model.defer="search" placeholder="Search by name..." ... />
            </td>
            <td style="width: 15%;">
                <select wire:model.defer="statusFilter" ...>
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </td>
            <td style="width: 15%;"><input type="date" wire:model.defer="startDate" ... /></td>
            <td style="width: 15%;"><input type="date" wire:model.defer="endDate" ... /></td>
            <td style="width: 10%;"><button wire:click="searchCustomers" ...>Search</button></td>
            <td style="width: 10%;"><button wire:click="resetFilters" ...>Reset</button></td>
        </tr>
    </table>

    {{-- Resellers Section --}}
    <div class="mb-8">
        <h3>Resellers</h3> Active: X | Inactive: Y
        <table> Reseller Id | Reseller Name | Joined Date | Status </table>
    </div>

    {{-- Customers (Subscriber) Section --}}
    <div>
        <h3>Customers (Subscriber)</h3> Active: X | Inactive: Y
        <table> Customer Id | Customer Name | Joined Date | Status </table>
    </div>
</div>
```

#### Key UI Features
- **Filter bar**: HTML table with percentage-based column widths for full-width stretch
- **Clickable IDs**: Links to `/admin/hr-company-license-details?softwareHandoverId={id}`
- **Status display**: Green text for Active, gray for other statuses
- **Empty states**: "No resellers found" / "No subscribers found"
- **Count badges**: Active count (green) and Inactive count (gray) next to section headers

---

## Testing Notes

### How to Test

1. Navigate to Company License Details for a **Distributor** company (e.g., GENX TECHNOLOGY)
2. Click the "Customer" tab
3. Verify "Resellers" section shows sub-dealers with Active/Inactive counts
4. Verify "Customers (Subscriber)" section shows subscribers with counts
5. Test search: type a company name, click "Search"
6. Test status filter: select "Active" from dropdown, click "Search"
7. Test date range: set start/end dates, click "Search"
8. Test reset: click "Reset" to clear all filters
9. Click a Reseller/Customer ID link - verify it navigates to that company's details
10. Navigate to a **Subscriber** company - verify Customer tab is NOT visible

---

## Completion Checklist

- [x] Code changes reviewed
- [x] BEFORE/AFTER code documented
- [x] Tested locally
- [x] Ready for developer handoff
