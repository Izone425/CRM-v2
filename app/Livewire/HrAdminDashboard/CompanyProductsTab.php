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
                'license_type' => 'TimeTec Profile (10 User License)',
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
                'license_type' => 'TimeTec TA (10 User License)',
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
                'license_type' => 'TimeTec Leave (10 User License)',
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
                'license_type' => 'TimeTec Claim (10 User License)',
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
                'license_type' => 'TimeTec TA (1 User License)',
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
                'license_type' => 'TimeTec Leave (1 User License)',
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
                'license_type' => 'TimeTec Claim (1 User License)',
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
                'license_type' => 'TimeTec Payroll (1 Payroll License)',
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
                'license_type' => 'TimeTec TA (1 User License)',
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
                'license_type' => 'TimeTec Leave (1 User License)',
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
                'license_type' => 'TimeTec Claim (1 User License)',
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
                'license_type' => 'TimeTec Payroll (1 Payroll License)',
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
            // Group by invoice_no for PAID, or by date range for TRIAL
            if ($record['invoice_no']) {
                $key = $record['invoice_no'];
            } else {
                // Group TRIAL licenses by their date range
                $key = 'TRIAL_' . $record['start_date'] . '_' . $record['end_date'];
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
                'unit' => $record['unit'],
                'user_limit' => $record['user_limit'],
                'total_user' => $record['total_user'],
                'total_login' => $record['total_login'],
                'total_terminal' => $record['total_terminal'],
            ];
        }

        return array_values($grouped);
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.company-products-tab');
    }
}
