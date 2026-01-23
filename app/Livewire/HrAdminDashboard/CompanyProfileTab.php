<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\SoftwareHandover;
use App\Models\CompanyDetail;
use App\Models\LicenseCertificate;
use Carbon\Carbon;
use Livewire\Component;

class CompanyProfileTab extends Component
{
    public ?int $softwareHandoverId = null;
    public array $companyData = [];
    public array $profileData = [];
    public string $selectedBranch = 'Timetec Cloud Sdn Bhd';

    public function mount(?int $softwareHandoverId = null, array $companyData = [])
    {
        $this->softwareHandoverId = $softwareHandoverId;
        $this->companyData = $companyData;
        $this->loadProfileData();
    }

    protected function loadProfileData(): void
    {
        $softwareHandover = $this->companyData['software_handover'] ?? null;
        $companyDetail = $this->companyData['company_detail'] ?? null;

        // Load License Certificate if available
        $licenseCertificate = null;
        if ($softwareHandover && $softwareHandover->license_certification_id) {
            $licenseCertificate = LicenseCertificate::find($softwareHandover->license_certification_id);
        }

        $this->profileData = [
            'account_info' => [
                'branch' => $softwareHandover?->company_name ?? $this->companyData['company_name'] ?? '-',
                'register_date' => $softwareHandover?->completed_at ? Carbon::parse($softwareHandover->completed_at)->format('Y-m-d H:i:s') : '-',
                'last_login_date' => '-', // From HR Backend API
            ],
            'backend_info' => [
                'company_id' => $this->companyData['hr_company_id'] ?? '-',
                'user_id' => $this->companyData['hr_user_id'] ?? '-',
                'webster_ip' => '-', // From HR Backend API
            ],
            'billing_info' => [
                'company_name' => $companyDetail?->company_name ?? $this->companyData['company_name'] ?? '-',
                'address' => $this->formatAddress($companyDetail),
                'email' => $companyDetail?->email ?? '-',
            ],
            'contact_person' => [
                'name' => $companyDetail?->name ?? '-',
                'email' => $companyDetail?->email ?? '-',
                'phone' => $companyDetail?->contact_no ?? '-',
                'position' => $companyDetail?->position ?? '-',
                'title' => '-',
                'nationality' => '-',
                'gender' => '-',
            ],
        ];
    }

    protected function formatAddress(?CompanyDetail $companyDetail): string
    {
        if (!$companyDetail) {
            return '-';
        }

        $parts = array_filter([
            $companyDetail->company_address1,
            $companyDetail->company_address2,
            $companyDetail->postcode,
            $companyDetail->state,
        ]);

        return implode(', ', $parts) ?: '-';
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.company-profile-tab');
    }
}
