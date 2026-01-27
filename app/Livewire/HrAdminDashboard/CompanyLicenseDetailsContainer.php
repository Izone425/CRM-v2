<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\HrLicense;
use App\Models\SoftwareHandover;
use App\Models\CompanyDetail;
use Livewire\Component;

class CompanyLicenseDetailsContainer extends Component
{
    public $activeTab = 'profile';
    public ?string $handoverId = null;
    public ?int $softwareHandoverId = null;
    public $companyData = [];

    protected $listeners = ['switchTab'];

    public function mount(?string $handoverId = null, ?int $softwareHandoverId = null)
    {
        $this->handoverId = $handoverId;
        $this->softwareHandoverId = $softwareHandoverId;
        $this->loadCompanyData();
    }

    protected function loadCompanyData(): void
    {
        $softwareHandover = null;
        $hrLicense = null;
        $companyDetail = null;

        // Load SoftwareHandover
        if ($this->softwareHandoverId) {
            $softwareHandover = SoftwareHandover::with(['lead.companyDetail'])->find($this->softwareHandoverId);
            $companyDetail = $softwareHandover?->lead?->companyDetail;
        }

        // Load HrLicense
        if ($this->handoverId) {
            $hrLicense = HrLicense::where('handover_id', $this->handoverId)->first();
        } elseif ($this->softwareHandoverId) {
            $hrLicense = HrLicense::where('software_handover_id', $this->softwareHandoverId)->first();
        }

        // Build company data context
        $this->companyData = [
            'software_handover' => $softwareHandover,
            'hr_license' => $hrLicense,
            'company_detail' => $companyDetail,
            'company_name' => $hrLicense?->company_name ?? $softwareHandover?->company_name ?? 'Unknown Company',
            'handover_id' => $this->handoverId ?? $hrLicense?->handover_id,
            'hr_account_id' => $softwareHandover?->hr_account_id,
            'hr_company_id' => $softwareHandover?->hr_company_id,
            'hr_user_id' => $softwareHandover?->hr_user_id,
            'license_category' => $hrLicense?->license_category ?? 'Subscriber',
        ];
    }

    public function switchToTab(string $tab): void
    {
        $validTabs = ['users', 'profile', 'products', 'customer', 'commission', 'invoice', 'account_setting'];
        if (in_array($tab, $validTabs)) {
            $this->activeTab = $tab;
        }
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.company-license-details-container');
    }
}
