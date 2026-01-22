<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Request;

class HrCompanyLicenseDetails extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static string $view = 'filament.pages.hr-company-license-details';
    protected static ?string $title = 'Company License Details';
    protected static ?string $slug = 'hr-company-license-details';
    protected static bool $shouldRegisterNavigation = false;

    public ?string $handoverId = null;
    public ?int $softwareHandoverId = null;
    public ?string $companyName = null;

    public function mount(): void
    {
        // Get parameters from query string
        $this->handoverId = Request::query('handoverId');
        $this->softwareHandoverId = Request::query('softwareHandoverId') ? (int) Request::query('softwareHandoverId') : null;

        // Get company name from HrLicense or SoftwareHandover
        if ($this->handoverId) {
            $hrLicense = \App\Models\HrLicense::where('handover_id', $this->handoverId)->first();
            $this->companyName = $hrLicense?->company_name;

            if (!$this->softwareHandoverId && $hrLicense) {
                $this->softwareHandoverId = $hrLicense->software_handover_id;
            }
        }

        if (!$this->companyName && $this->softwareHandoverId) {
            $softwareHandover = \App\Models\SoftwareHandover::find($this->softwareHandoverId);
            $this->companyName = $softwareHandover?->company_name;
        }
    }

    public function getTitle(): string
    {
        return 'Company License Details';
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/hr-license') => 'All Licenses',
            '#' => 'Company Details',
        ];
    }
}
