<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\HrLicense;
use App\Models\SoftwareHandover;
use Livewire\Component;

class CompanyProductsTab extends Component
{
    public ?int $softwareHandoverId = null;
    public array $companyData = [];
    public array $licenseData = [];
    public array $summaryData = [];

    public function mount(?int $softwareHandoverId = null, array $companyData = [])
    {
        $this->softwareHandoverId = $softwareHandoverId;
        $this->companyData = $companyData;
        $this->loadProductData();
    }

    protected function loadProductData(): void
    {
        $softwareHandover = $this->companyData['software_handover'] ?? null;
        $hrLicense = $this->companyData['hr_license'] ?? null;

        // Get module flags from SoftwareHandover
        $modules = [
            'ta' => $softwareHandover?->ta ?? false,
            'tl' => $softwareHandover?->tl ?? false,
            'tc' => $softwareHandover?->tc ?? false,
            'tp' => $softwareHandover?->tp ?? false,
            'tapp' => $softwareHandover?->tapp ?? false,
            'thire' => $softwareHandover?->thire ?? false,
            'tacc' => $softwareHandover?->tacc ?? false,
            'tpbi' => $softwareHandover?->tpbi ?? false,
        ];

        // Build license data structure
        $this->licenseData = [
            'User Account' => $hrLicense?->user_limit ?? 0,
            'Login Account' => $hrLicense?->total_login ?? 0,
            'Patrol User' => 0,
            'Patrol Checkpoint' => 0,
            'Live Location Tracking' => 0,
            'Leave' => $modules['tl'] ? ($hrLicense?->user_limit ?? 0) : 0,
            'Access User' => 0,
            'Access Door' => 0,
            'Profile' => 1000,
            'Payroll' => $modules['tp'] ? ($hrLicense?->user_limit ?? 0) : 0,
            'Appraisal' => 0,
            'Hire User' => $modules['thire'] ? ($hrLicense?->user_limit ?? 0) : 0,
            'Hire JobPost' => 0,
            'Terminal' => $hrLicense?->unit ?? 0,
            'Claim' => $modules['tc'] ? ($hrLicense?->user_limit ?? 0) : 0,
        ];

        // Build summary data
        $this->summaryData = [
            'total_users' => $hrLicense?->total_user ?? 0,
            'admin' => [
                'ta_active' => $modules['ta'] ? 1 : 0,
                'ta_inactive' => 0,
                'patrol_active' => 0,
                'patrol_inactive' => 0,
                'leave_active' => $modules['tl'] ? 1 : 0,
                'leave_inactive' => 0,
            ],
            'operator' => [
                'ta' => 0,
                'patrol' => 0,
                'leave' => 0,
            ],
            'user' => [
                'total' => $hrLicense?->total_user ?? 0,
            ],
            'device' => [
                'web_login' => $hrLicense?->total_login ?? 0,
                'mobile_login' => 0,
            ],
        ];
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.company-products-tab');
    }
}
