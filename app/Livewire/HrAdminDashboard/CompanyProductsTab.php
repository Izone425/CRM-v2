<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\HrLicense;
use App\Models\SoftwareHandover;
use Livewire\Component;

class CompanyProductsTab extends Component
{
    public ?int $softwareHandoverId = null;
    public array $companyData = [];
    public array $productData = [];
    public array $licenseRecords = [];
    public array $groupedLicenseRecords = [];

    // Edit modal properties
    public bool $showEditModal = false;
    public ?int $editingLicenseNo = null;
    public array $editForm = [
        'total_user' => '',
        'month' => '',
        'start_date' => '',
        'end_date' => '',
        'status' => 'active',
    ];
    public string $editingLicenseType = '';

    // Bulk edit modal properties
    public bool $showBulkEditModal = false;
    public array $bulkEditForm = [
        'total_user' => '',
        'start_date' => '',
        'end_date' => '',
        'status' => '',
    ];
    public array $bulkEditEnabled = [
        'total_user' => false,
        'start_date' => false,
        'end_date' => false,
        'status' => false,
    ];

    // Selection mode properties
    public bool $isSelectionMode = false;
    public array $selectedLicenseNos = [];

    public function mount(?int $softwareHandoverId = null, array $companyData = [])
    {
        $this->softwareHandoverId = $softwareHandoverId;
        $this->companyData = $companyData;
        $this->loadProductData();
        $this->loadLicenseRecords();
        $this->groupedLicenseRecords = $this->getGroupedLicenseRecords();
    }

    protected function loadProductData(): void
    {
        $hrLicense = $this->companyData['hr_license'] ?? null;
        $softwareHandover = $this->companyData['software_handover'] ?? null;

        // Get actual usage counts (from API later, use available data for now)
        $totalUsers = $hrLicense?->total_user ?? 0;

        // Build product data with active/inactive breakdown
        // Note: Actual active/inactive requires HR Backend API - using placeholders for now
        $this->productData = [
            'user_account' => [
                'total' => $totalUsers,
                'active' => $totalUsers,
                'inactive' => 0,
            ],
            'attendance_user' => [
                'total' => ($softwareHandover?->ta ?? false) ? $totalUsers : 0,
                'active' => ($softwareHandover?->ta ?? false) ? $totalUsers : 0,
                'inactive' => 0,
            ],
            'leave_user' => [
                'total' => ($softwareHandover?->tl ?? false) ? $totalUsers : 0,
                'active' => ($softwareHandover?->tl ?? false) ? $totalUsers : 0,
                'inactive' => 0,
            ],
            'claim_user' => [
                'total' => ($softwareHandover?->tc ?? false) ? $totalUsers : 0,
                'active' => ($softwareHandover?->tc ?? false) ? $totalUsers : 0,
                'inactive' => 0,
            ],
            'payroll_user' => [
                'total' => ($softwareHandover?->tp ?? false) ? $totalUsers : 0,
                'active' => ($softwareHandover?->tp ?? false) ? $totalUsers : 0,
                'inactive' => 0,
            ],
            'onboarding_offboarding' => [
                'total' => ($softwareHandover?->thire ?? false) ? $totalUsers : 0,
                'active' => ($softwareHandover?->thire ?? false) ? $totalUsers : 0,
                'inactive' => 0,
            ],
            'recruitment' => [
                'total' => ($softwareHandover?->thire ?? false) ? $totalUsers : 0,
                'active' => ($softwareHandover?->thire ?? false) ? $totalUsers : 0,
                'inactive' => 0,
            ],
            'appraisal' => [
                'total' => ($softwareHandover?->tapp ?? false) ? $totalUsers : 0,
                'active' => ($softwareHandover?->tapp ?? false) ? $totalUsers : 0,
                'inactive' => 0,
            ],
            'training' => [
                'total' => 0,
                'active' => 0,
                'inactive' => 0,
            ],
        ];
    }

    protected function loadLicenseRecords(): void
    {
        // Mock data matching "Koperasi Perbadanan Putrajaya Berhad" screenshot
        $this->licenseRecords = [
            [
                'no' => 1,
                'type' => 'TRIAL',
                'invoice_no' => '',
                'license_type' => 'TimeTec Profile',
                'unit' => 3,
                'user_limit' => 10,
                'total_user' => 28,
                'total_login' => 0,
                'total_terminal' => 0,
                'month' => 1,
                'start_date' => '2025-01-24',
                'end_date' => '2027-01-23',
                'status' => 'active',
                'renewed' => '-',
            ],
            [
                'no' => 2,
                'type' => 'TRIAL',
                'invoice_no' => '',
                'license_type' => 'TimeTec TA',
                'unit' => 3,
                'user_limit' => 10,
                'total_user' => 28,
                'total_login' => 30,
                'total_terminal' => 150,
                'month' => 1,
                'start_date' => '2024-12-23',
                'end_date' => '2025-01-23',
                'status' => 'expired',
                'renewed' => '-',
            ],
            [
                'no' => 3,
                'type' => 'TRIAL',
                'invoice_no' => '',
                'license_type' => 'TimeTec Leave',
                'unit' => 3,
                'user_limit' => 10,
                'total_user' => 28,
                'total_login' => 0,
                'total_terminal' => 0,
                'month' => 1,
                'start_date' => '2024-12-23',
                'end_date' => '2025-01-23',
                'status' => 'expired',
                'renewed' => '-',
            ],
            [
                'no' => 4,
                'type' => 'TRIAL',
                'invoice_no' => '',
                'license_type' => 'TimeTec Claim',
                'unit' => 3,
                'user_limit' => 10,
                'total_user' => 28,
                'total_login' => 0,
                'total_terminal' => 0,
                'month' => 1,
                'start_date' => '2024-12-23',
                'end_date' => '2025-01-23',
                'status' => 'expired',
                'renewed' => '-',
            ],
            [
                'no' => 5,
                'type' => 'PAID',
                'invoice_no' => 'TT2412000246',
                'license_type' => 'TimeTec TA',
                'unit' => 28,
                'user_limit' => 1,
                'total_user' => 28,
                'total_login' => 28,
                'total_terminal' => 140,
                'month' => 12,
                'start_date' => '2025-01-24',
                'end_date' => '2026-01-23',
                'status' => 'active',
                'renewed' => '-',
            ],
            [
                'no' => 6,
                'type' => 'PAID',
                'invoice_no' => 'TT2412000246',
                'license_type' => 'TimeTec Leave',
                'unit' => 28,
                'user_limit' => 1,
                'total_user' => 28,
                'total_login' => 0,
                'total_terminal' => 0,
                'month' => 12,
                'start_date' => '2025-01-24',
                'end_date' => '2026-01-23',
                'status' => 'active',
                'renewed' => '-',
            ],
            [
                'no' => 7,
                'type' => 'PAID',
                'invoice_no' => 'TT2412000246',
                'license_type' => 'TimeTec Claim',
                'unit' => 28,
                'user_limit' => 1,
                'total_user' => 28,
                'total_login' => 0,
                'total_terminal' => 0,
                'month' => 12,
                'start_date' => '2025-01-24',
                'end_date' => '2026-01-23',
                'status' => 'active',
                'renewed' => '-',
            ],
            [
                'no' => 8,
                'type' => 'PAID',
                'invoice_no' => 'TT2412000246',
                'license_type' => 'TimeTec Payroll',
                'unit' => 28,
                'user_limit' => 1,
                'total_user' => 28,
                'total_login' => 0,
                'total_terminal' => 0,
                'month' => 12,
                'start_date' => '2025-01-24',
                'end_date' => '2026-01-23',
                'status' => 'active',
                'renewed' => '-',
            ],
            [
                'no' => 9,
                'type' => 'PAID',
                'invoice_no' => 'TT2601000335',
                'license_type' => 'TimeTec TA',
                'unit' => 28,
                'user_limit' => 1,
                'total_user' => 28,
                'total_login' => 28,
                'total_terminal' => 140,
                'month' => 12,
                'start_date' => '2026-01-24',
                'end_date' => '2027-01-23',
                'status' => 'active',
                'renewed' => '-',
            ],
            [
                'no' => 10,
                'type' => 'PAID',
                'invoice_no' => 'TT2601000335',
                'license_type' => 'TimeTec Leave',
                'unit' => 28,
                'user_limit' => 1,
                'total_user' => 28,
                'total_login' => 0,
                'total_terminal' => 0,
                'month' => 12,
                'start_date' => '2026-01-24',
                'end_date' => '2027-01-23',
                'status' => 'active',
                'renewed' => '-',
            ],
            [
                'no' => 11,
                'type' => 'PAID',
                'invoice_no' => 'TT2601000335',
                'license_type' => 'TimeTec Claim',
                'unit' => 28,
                'user_limit' => 1,
                'total_user' => 28,
                'total_login' => 0,
                'total_terminal' => 0,
                'month' => 12,
                'start_date' => '2026-01-24',
                'end_date' => '2027-01-23',
                'status' => 'active',
                'renewed' => '-',
            ],
            [
                'no' => 12,
                'type' => 'PAID',
                'invoice_no' => 'TT2601000335',
                'license_type' => 'TimeTec Payroll',
                'unit' => 28,
                'user_limit' => 1,
                'total_user' => 28,
                'total_login' => 0,
                'total_terminal' => 0,
                'month' => 12,
                'start_date' => '2026-01-24',
                'end_date' => '2027-01-23',
                'status' => 'active',
                'renewed' => '-',
            ],
        ];
    }

    protected function getGroupedLicenseRecords(): array
    {
        $grouped = [];

        foreach ($this->licenseRecords as $record) {
            // Group by invoice_no for PAID only; TRIAL licenses are individual (no grouping)
            if ($record['invoice_no']) {
                $key = $record['invoice_no'];
            } else {
                // Each TRIAL license gets its own row (no grouping)
                $key = 'TRIAL_' . $record['no'];
            }

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'invoice_no' => $record['invoice_no'],
                    'type' => $record['type'],
                    'month' => $record['month'],
                    'start_date' => $record['start_date'],
                    'end_date' => $record['end_date'],
                    'status' => $record['status'],
                    'renewed' => $record['renewed'],
                    'products' => [],
                ];
            }

            $grouped[$key]['products'][] = [
                'no' => $record['no'],
                'license_type' => $record['license_type'],
                'total_user' => $record['total_user'],
                'total_login' => $record['total_login'],
                'month' => $record['month'],
                'start_date' => $record['start_date'],
                'end_date' => $record['end_date'],
            ];
        }

        return array_values($grouped);
    }

    public function openEditModal(int $licenseNo): void
    {
        // Find the license record by 'no'
        $record = collect($this->licenseRecords)->firstWhere('no', $licenseNo);

        if ($record) {
            $this->editingLicenseNo = $licenseNo;
            $this->editingLicenseType = $record['license_type'];
            $this->editForm = [
                'total_user' => $record['total_user'],
                'month' => $record['month'],
                'start_date' => $record['start_date'],
                'end_date' => $record['end_date'],
                'status' => $this->calculateStatus($record['start_date'], $record['end_date']),
            ];
            $this->showEditModal = true;
        }
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingLicenseNo = null;
        $this->editingLicenseType = '';
        $this->editForm = [
            'total_user' => '',
            'month' => '',
            'start_date' => '',
            'end_date' => '',
            'status' => 'active',
        ];
    }

    public function saveLicense(): void
    {
        // Validate the form
        $this->validate([
            'editForm.total_user' => 'required|integer|min:1',
            'editForm.month' => 'required|integer|min:1|max:36',
            'editForm.start_date' => 'required|date',
            'editForm.end_date' => 'required|date|after_or_equal:editForm.start_date',
            'editForm.status' => 'required|in:active,inactive',
        ]);

        // Find and update the license record
        foreach ($this->licenseRecords as $index => $record) {
            if ($record['no'] === $this->editingLicenseNo) {
                $this->licenseRecords[$index]['total_user'] = (int) $this->editForm['total_user'];
                $this->licenseRecords[$index]['month'] = (int) $this->editForm['month'];
                $this->licenseRecords[$index]['start_date'] = $this->editForm['start_date'];
                $this->licenseRecords[$index]['end_date'] = $this->editForm['end_date'];
                $this->licenseRecords[$index]['status'] = $this->editForm['status'];
                break;
            }
        }

        // Refresh grouped records
        $this->groupedLicenseRecords = $this->getGroupedLicenseRecords();

        // Close the modal
        $this->closeEditModal();

        // Dispatch success notification
        $this->dispatch('notify', type: 'success', message: 'License updated successfully.');
    }

    protected function calculateStatus(string $startDate, string $endDate): string
    {
        $today = now()->startOfDay();
        $start = \Carbon\Carbon::parse($startDate)->startOfDay();
        $end = \Carbon\Carbon::parse($endDate)->endOfDay();

        return $today->between($start, $end) ? 'active' : 'inactive';
    }

    // Selection mode methods
    public function enterSelectionMode(): void
    {
        $this->isSelectionMode = true;
        $this->selectedLicenseNos = [];
    }

    public function exitSelectionMode(): void
    {
        $this->isSelectionMode = false;
        $this->selectedLicenseNos = [];
    }

    public function toggleLicenseSelection(int $licenseNo): void
    {
        if (in_array($licenseNo, $this->selectedLicenseNos)) {
            $this->selectedLicenseNos = array_values(array_diff($this->selectedLicenseNos, [$licenseNo]));
        } else {
            $this->selectedLicenseNos[] = $licenseNo;
        }
    }

    public function toggleSelectAll(): void
    {
        $allNos = array_column($this->licenseRecords, 'no');
        if (count($this->selectedLicenseNos) === count($allNos)) {
            $this->selectedLicenseNos = [];
        } else {
            $this->selectedLicenseNos = $allNos;
        }
    }

    public function toggleGroupSelection(string $invoiceNo): void
    {
        $groupNos = collect($this->licenseRecords)
            ->where('invoice_no', $invoiceNo)
            ->pluck('no')
            ->toArray();

        $allSelected = count(array_intersect($this->selectedLicenseNos, $groupNos)) === count($groupNos);

        if ($allSelected) {
            $this->selectedLicenseNos = array_values(array_diff($this->selectedLicenseNos, $groupNos));
        } else {
            $this->selectedLicenseNos = array_values(array_unique(array_merge($this->selectedLicenseNos, $groupNos)));
        }
    }

    public function getSelectedLicenseNames(): array
    {
        return collect($this->licenseRecords)
            ->whereIn('no', $this->selectedLicenseNos)
            ->pluck('license_type')
            ->toArray();
    }

    public function openBulkEditModal(): void
    {
        // Validate selection
        if (empty($this->selectedLicenseNos)) {
            $this->dispatch('notify', type: 'error', message: 'Please select at least one license to edit.');
            return;
        }

        // Reset form and checkboxes
        $this->bulkEditForm = [
            'total_user' => '',
            'start_date' => '',
            'end_date' => '',
            'status' => 'active',
        ];
        $this->bulkEditEnabled = [
            'total_user' => false,
            'start_date' => false,
            'end_date' => false,
            'status' => false,
        ];
        $this->showBulkEditModal = true;
    }

    public function closeBulkEditModal(): void
    {
        $this->showBulkEditModal = false;
        $this->bulkEditForm = [
            'total_user' => '',
            'start_date' => '',
            'end_date' => '',
            'status' => 'active',
        ];
        $this->bulkEditEnabled = [
            'total_user' => false,
            'start_date' => false,
            'end_date' => false,
            'status' => false,
        ];
    }

    public function saveBulkEdit(): void
    {
        // Check if at least one field is enabled
        $hasEnabledField = in_array(true, $this->bulkEditEnabled, true);
        if (!$hasEnabledField) {
            $this->dispatch('notify', type: 'error', message: 'Please select at least one field to update.');
            return;
        }

        // Build validation rules only for enabled fields
        $rules = [];
        if ($this->bulkEditEnabled['total_user']) {
            $rules['bulkEditForm.total_user'] = 'required|integer|min:1';
        }
        if ($this->bulkEditEnabled['start_date']) {
            $rules['bulkEditForm.start_date'] = 'required|date';
        }
        if ($this->bulkEditEnabled['end_date']) {
            $rules['bulkEditForm.end_date'] = 'required|date';
            if ($this->bulkEditEnabled['start_date']) {
                $rules['bulkEditForm.end_date'] .= '|after_or_equal:bulkEditForm.start_date';
            }
        }
        if ($this->bulkEditEnabled['status']) {
            $rules['bulkEditForm.status'] = 'required|in:active,inactive';
        }

        if (!empty($rules)) {
            $this->validate($rules);
        }

        // Update only selected license records with enabled fields
        $updatedCount = 0;
        foreach ($this->licenseRecords as $index => $record) {
            // Skip if this license is not selected
            if (!in_array($record['no'], $this->selectedLicenseNos)) {
                continue;
            }

            if ($this->bulkEditEnabled['total_user']) {
                $this->licenseRecords[$index]['total_user'] = (int) $this->bulkEditForm['total_user'];
            }
            if ($this->bulkEditEnabled['start_date']) {
                $this->licenseRecords[$index]['start_date'] = $this->bulkEditForm['start_date'];
            }
            if ($this->bulkEditEnabled['end_date']) {
                $this->licenseRecords[$index]['end_date'] = $this->bulkEditForm['end_date'];
            }
            if ($this->bulkEditEnabled['status']) {
                $this->licenseRecords[$index]['status'] = $this->bulkEditForm['status'];
            }
            $updatedCount++;
        }

        // Refresh grouped records
        $this->groupedLicenseRecords = $this->getGroupedLicenseRecords();

        // Close the modal and exit selection mode
        $this->closeBulkEditModal();
        $this->exitSelectionMode();

        // Dispatch success notification
        $this->dispatch('notify', type: 'success', message: "Successfully updated {$updatedCount} license(s).");
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.company-products-tab');
    }
}
