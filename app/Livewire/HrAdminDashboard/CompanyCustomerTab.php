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

    // Search/Filter properties
    public string $search = '';
    public string $statusFilter = 'all';
    public ?string $startDate = null;
    public ?string $endDate = null;

    // Data arrays
    public array $resellers = [];
    public array $subscribers = [];

    // Count properties (unfiltered totals for badges)
    public int $resellerActiveCount = 0;
    public int $resellerInactiveCount = 0;
    public int $subscriberActiveCount = 0;
    public int $subscriberInactiveCount = 0;

    public function mount(?int $softwareHandoverId = null, array $companyData = [])
    {
        $this->softwareHandoverId = $softwareHandoverId;
        $this->companyData = $companyData;
        $this->loadCustomers();
    }

    public function loadCustomers(): void
    {
        $softwareHandover = $this->companyData['software_handover'] ?? null;

        if (!$softwareHandover || !$softwareHandover->reseller_id) {
            $this->resellers = [];
            $this->subscribers = [];
            return;
        }

        $resellerId = $softwareHandover->reseller_id;
        $currentSwId = $softwareHandover->id;

        try {
            // Build query for all HrLicense records under this reseller (excluding current company)
            $query = HrLicense::whereHas('softwareHandover', function ($q) use ($resellerId, $currentSwId) {
                $q->where('reseller_id', $resellerId)
                  ->where('id', '!=', $currentSwId);
            })
            ->with('softwareHandover:id,hr_account_id,completed_at,status');

            // Apply search filter
            if (!empty($this->search)) {
                $query->where('company_name', 'like', '%' . $this->search . '%');
            }

            // Apply status filter
            if ($this->statusFilter === 'active') {
                $query->whereRaw('LOWER(status) = ?', ['active']);
            } elseif ($this->statusFilter === 'inactive') {
                $query->whereRaw('LOWER(status) != ?', ['active']);
            }

            // Apply date range filter (on softwareHandover.completed_at)
            if ($this->startDate) {
                $query->whereHas('softwareHandover', function ($q) {
                    $q->whereDate('completed_at', '>=', $this->startDate);
                });
            }
            if ($this->endDate) {
                $query->whereHas('softwareHandover', function ($q) {
                    $q->whereDate('completed_at', '<=', $this->endDate);
                });
            }

            $allLicenses = $query->get();

            // Split by license_category
            $resellerRecords = $allLicenses->where('license_category', 'Reseller');
            $subscriberRecords = $allLicenses->where('license_category', 'Subscriber');

            // Map to display arrays
            $this->resellers = $resellerRecords->map(function ($license) {
                return [
                    'id' => $license->softwareHandover?->hr_account_id ?? '-',
                    'software_handover_id' => $license->software_handover_id,
                    'name' => $license->company_name ?? '-',
                    'joined_date' => $license->softwareHandover?->completed_at
                        ? Carbon::parse($license->softwareHandover->completed_at)->format('d-m-Y')
                        : '-',
                    'status' => $license->status ?? 'Inactive',
                ];
            })->values()->toArray();

            $this->subscribers = $subscriberRecords->map(function ($license) {
                return [
                    'id' => $license->softwareHandover?->hr_account_id ?? '-',
                    'software_handover_id' => $license->software_handover_id,
                    'name' => $license->company_name ?? '-',
                    'joined_date' => $license->softwareHandover?->completed_at
                        ? Carbon::parse($license->softwareHandover->completed_at)->format('d-m-Y')
                        : '-',
                    'status' => $license->status ?? 'Inactive',
                ];
            })->values()->toArray();

            // Compute unfiltered counts for badges
            $this->computeCounts($resellerId, $currentSwId);

        } catch (\Exception $e) {
            Log::error('CompanyCustomerTab: Failed to load customers', [
                'software_handover_id' => $this->softwareHandoverId,
                'error' => $e->getMessage(),
            ]);
            $this->resellers = [];
            $this->subscribers = [];
        }
    }

    protected function computeCounts(int $resellerId, int $currentSwId): void
    {
        $counts = HrLicense::whereHas('softwareHandover', function ($q) use ($resellerId, $currentSwId) {
            $q->where('reseller_id', $resellerId)
              ->where('id', '!=', $currentSwId);
        })
        ->selectRaw("
            license_category,
            SUM(CASE WHEN LOWER(status) = 'active' THEN 1 ELSE 0 END) as active_count,
            SUM(CASE WHEN LOWER(status) != 'active' THEN 1 ELSE 0 END) as inactive_count
        ")
        ->groupBy('license_category')
        ->get()
        ->keyBy('license_category');

        $resellerCounts = $counts->get('Reseller');
        $subscriberCounts = $counts->get('Subscriber');

        $this->resellerActiveCount = (int) ($resellerCounts->active_count ?? 0);
        $this->resellerInactiveCount = (int) ($resellerCounts->inactive_count ?? 0);
        $this->subscriberActiveCount = (int) ($subscriberCounts->active_count ?? 0);
        $this->subscriberInactiveCount = (int) ($subscriberCounts->inactive_count ?? 0);
    }

    public function searchCustomers(): void
    {
        $this->loadCustomers();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->startDate = null;
        $this->endDate = null;
        $this->loadCustomers();
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.company-customer-tab');
    }
}
