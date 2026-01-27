<?php

namespace App\Livewire\HrAdminDashboard;

use Livewire\Component;

class CompanyCommissionTab extends Component
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
        return view('livewire.hr-admin-dashboard.company-commission-tab');
    }
}
