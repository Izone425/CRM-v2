<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\HrLicense;
use App\Models\SoftwareHandover;
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
            $this->appendDummyRecords();
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

        $this->appendDummyRecords();
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

    protected function appendDummyRecords(): void
    {
        // Fetch real SoftwareHandover records for clickable navigation
        $realSwRecords = SoftwareHandover::where('id', '!=', $this->softwareHandoverId ?? 0)
            ->select('id', 'hr_account_id')
            ->take(4)
            ->get();

        // ABC Technology is now a real DB record (loaded via loadCustomers), so only add other dummy records
        $dummyResellers = [
            [
                'id' => $realSwRecords[0]->hr_account_id ?? 'RS-002',
                'software_handover_id' => $realSwRecords[0]->id ?? null,
                'name' => 'XYZ Solutions Pte Ltd',
                'joined_date' => '20-06-2024',
                'status' => 'Inactive',
            ],
        ];

        $dummySubscribers = [
            [
                'id' => $realSwRecords[1]->hr_account_id ?? 'CU-001',
                'software_handover_id' => $realSwRecords[1]->id ?? null,
                'name' => 'Global Manufacturing Sdn Bhd',
                'joined_date' => '10-03-2025',
                'status' => 'Active',
            ],
            [
                'id' => $realSwRecords[2]->hr_account_id ?? 'CU-002',
                'software_handover_id' => $realSwRecords[2]->id ?? null,
                'name' => 'Metro Services Pte Ltd',
                'joined_date' => '05-08-2024',
                'status' => 'Active',
            ],
            [
                'id' => $realSwRecords[3]->hr_account_id ?? 'CU-003',
                'software_handover_id' => $realSwRecords[3]->id ?? null,
                'name' => 'Pinnacle Trading Co.',
                'joined_date' => '22-11-2024',
                'status' => 'Inactive',
            ],
        ];

        $this->resellers = array_merge($this->resellers, $dummyResellers);
        $this->subscribers = array_merge($this->subscribers, $dummySubscribers);

        // Update badge counts to include dummy records
        $this->resellerInactiveCount += 1;
        $this->subscriberActiveCount += 2;
        $this->subscriberInactiveCount += 1;
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
