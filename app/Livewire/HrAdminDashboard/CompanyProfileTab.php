<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\SoftwareHandover;
use App\Models\CompanyDetail;
use App\Models\BankDetail;
use App\Models\LicenseCertificate;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Livewire\Component;

class CompanyProfileTab extends Component
{
    public ?int $softwareHandoverId = null;
    public array $companyData = [];
    public array $profileData = [];
    public string $selectedBranch = 'Timetec Cloud Sdn Bhd';

    // Edit Mode Toggles
    public bool $editingAccountInfo = false;
    public bool $editingBillingInfo = false;
    public bool $editingContactPerson = false;
    public bool $editingBusinessInfo = false;
    public bool $editingPaymentInfo = false;

    // Billing Information Properties
    public ?string $billingCompanyName = null;
    public ?string $billingAddress = null;
    public ?string $billingEmail = null;
    public bool $billingIsDefault = true;

    // Contact Person Properties
    public ?string $contactName = null;
    public ?string $contactEmail = null;
    public ?string $contactPhone = null;
    public ?string $contactPosition = null;
    public ?string $contactTitle = null;
    public ?string $contactGender = null;

    // Business Information Properties
    public ?string $businessType = null;
    public ?string $industry = null;
    public ?string $companyName = null;
    public ?string $companyRegNo = null;
    public ?string $companyAddress = null;
    public ?string $area = null;
    public ?string $postcode = null;
    public ?string $state = null;
    public ?string $country = 'Malaysia';
    public ?string $telephone = null;
    public ?string $fax = null;
    public ?string $emailAddress = null;
    public ?string $businessUrl = null;
    public ?string $primaryCurrency = 'MYR';
    public ?string $howDidYouHear = null;
    public ?string $preferredTimezone = '(GMT+08:00) Kuala Lumpur, Singapore';
    public ?string $preferredLanguage = 'English';
    public ?string $numberOfEmployee = null;
    public ?string $sstExemption = 'No';
    public ?string $sstNumber = null;

    // Payment Information Properties
    public ?string $companyBankAccount = null;
    public ?string $bankName = null;
    public ?string $nameOnBankAccount = null;
    public ?string $customerAccountCode = null;
    public ?string $paypalEmail = null;

    public function mount(?int $softwareHandoverId = null, array $companyData = [])
    {
        $this->softwareHandoverId = $softwareHandoverId;
        $this->companyData = $companyData;
        $this->loadProfileData();
        $this->loadBillingInfo();
        $this->loadContactPerson();
        $this->loadBusinessInfo();
        $this->loadPaymentInfo();
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

    protected function loadBillingInfo(): void
    {
        $companyDetail = $this->companyData['company_detail'] ?? null;

        $this->billingCompanyName = $companyDetail?->company_name ?? $this->companyData['company_name'] ?? null;
        $this->billingAddress = $this->formatAddress($companyDetail);
        $this->billingEmail = $companyDetail?->email ?? null;
    }

    protected function loadContactPerson(): void
    {
        $companyDetail = $this->companyData['company_detail'] ?? null;

        $this->contactName = $companyDetail?->name ?? null;
        $this->contactEmail = $companyDetail?->email ?? null;
        $this->contactPhone = $companyDetail?->contact_no ?? null;
        $this->contactPosition = $companyDetail?->position ?? null;
    }

    // Account Info Edit Methods
    public function editAccountInfo(): void
    {
        $this->editingAccountInfo = true;
    }

    public function cancelAccountInfo(): void
    {
        $this->editingAccountInfo = false;
    }

    public function saveAccountInfo(): void
    {
        // Branch selection doesn't need to save to database currently
        $this->editingAccountInfo = false;

        Notification::make()
            ->title('Account Information saved successfully')
            ->success()
            ->send();
    }

    // Billing Info Edit Methods
    public function editBillingInfo(): void
    {
        $this->editingBillingInfo = true;
    }

    public function cancelBillingInfo(): void
    {
        $this->editingBillingInfo = false;
        $this->loadBillingInfo();
    }

    public function saveBillingInfo(): void
    {
        $companyDetail = $this->companyData['company_detail'] ?? null;

        if ($companyDetail) {
            $companyDetail->update([
                'company_name' => $this->billingCompanyName,
                'email' => $this->billingEmail,
            ]);
        }

        $this->editingBillingInfo = false;

        Notification::make()
            ->title('Billing Information saved successfully')
            ->success()
            ->send();
    }

    // Contact Person Edit Methods
    public function editContactPerson(): void
    {
        $this->editingContactPerson = true;
    }

    public function cancelContactPerson(): void
    {
        $this->editingContactPerson = false;
        $this->loadContactPerson();
    }

    public function saveContactPerson(): void
    {
        $companyDetail = $this->companyData['company_detail'] ?? null;

        if ($companyDetail) {
            $companyDetail->update([
                'name' => $this->contactName,
                'email' => $this->contactEmail,
                'contact_no' => $this->contactPhone,
                'position' => $this->contactPosition,
            ]);
        }

        $this->editingContactPerson = false;

        Notification::make()
            ->title('Contact Person saved successfully')
            ->success()
            ->send();
    }

    // Business Info Edit Methods
    public function editBusinessInfo(): void
    {
        $this->editingBusinessInfo = true;
    }

    public function cancelBusinessInfo(): void
    {
        $this->editingBusinessInfo = false;
        $this->loadBusinessInfo();
    }

    // Payment Info Edit Methods
    public function editPaymentInfo(): void
    {
        $this->editingPaymentInfo = true;
    }

    public function cancelPaymentInfo(): void
    {
        $this->editingPaymentInfo = false;
        $this->loadPaymentInfo();
    }

    protected function loadBusinessInfo(): void
    {
        $companyDetail = $this->companyData['company_detail'] ?? null;
        $subsidiary = $this->companyData['subsidiary'] ?? null;
        $lead = $this->companyData['lead'] ?? null;
        $softwareHandover = $this->companyData['software_handover'] ?? null;

        // Load from Subsidiary first, then fallback to CompanyDetail
        $this->businessType = $subsidiary?->business_type ?? null;
        $this->industry = $companyDetail?->industry ?? $subsidiary?->industry ?? null;
        $this->companyName = $companyDetail?->company_name ?? $this->companyData['company_name'] ?? null;
        $this->companyRegNo = $companyDetail?->reg_no_new ?? $subsidiary?->business_register_number ?? null;

        // Address - combine address1 and address2
        $address1 = $companyDetail?->company_address1 ?? $subsidiary?->company_address1 ?? '';
        $address2 = $companyDetail?->company_address2 ?? $subsidiary?->company_address2 ?? '';
        $this->companyAddress = trim($address1 . ($address2 ? ', ' . $address2 : '')) ?: null;

        $this->postcode = $companyDetail?->postcode ?? $subsidiary?->postcode ?? null;
        $this->state = $companyDetail?->state ?? $subsidiary?->state ?? null;
        $this->country = $subsidiary?->country ?? $lead?->country ?? 'Malaysia';
        $this->telephone = $lead?->phone ?? $companyDetail?->contact_no ?? $subsidiary?->contact_number ?? null;
        $this->emailAddress = $companyDetail?->email ?? $lead?->email ?? $subsidiary?->email ?? null;
        $this->businessUrl = $companyDetail?->website_url ?? null;
        $this->primaryCurrency = $subsidiary?->currency ?? 'MYR';
        $this->numberOfEmployee = $softwareHandover?->headcount ?? $lead?->company_size ?? null;
        $this->sstNumber = $subsidiary?->tax_identification_number ?? null;
    }

    protected function loadPaymentInfo(): void
    {
        $bankDetail = $this->companyData['bank_detail'] ?? null;
        $softwareHandover = $this->companyData['software_handover'] ?? null;

        $this->companyBankAccount = $bankDetail?->bank_account_no ?? null;
        $this->bankName = $bankDetail?->bank_name ?? null;
        $this->nameOnBankAccount = $bankDetail?->beneficiary_name ?? null;
        $this->customerAccountCode = $softwareHandover?->autocount_debtor_code ?? null;
    }

    public function saveBusinessInfo(): void
    {
        $companyDetail = $this->companyData['company_detail'] ?? null;
        $lead = $this->companyData['lead'] ?? null;

        // Update CompanyDetail if exists
        if ($companyDetail) {
            // Split address back if needed
            $addressParts = explode(', ', $this->companyAddress ?? '', 2);
            $companyDetail->update([
                'company_name' => $this->companyName,
                'industry' => $this->industry,
                'company_address1' => $addressParts[0] ?? null,
                'company_address2' => $addressParts[1] ?? null,
                'postcode' => $this->postcode,
                'state' => $this->state,
                'reg_no_new' => $this->companyRegNo,
                'email' => $this->emailAddress,
                'contact_no' => $this->telephone,
                'website_url' => $this->businessUrl,
            ]);
        }

        // Update Lead if exists
        if ($lead) {
            $lead->update([
                'phone' => $this->telephone,
                'email' => $this->emailAddress,
                'country' => $this->country,
                'company_size' => $this->numberOfEmployee,
            ]);
        }

        $this->editingBusinessInfo = false;

        Notification::make()
            ->title('Business Information saved successfully')
            ->success()
            ->send();
    }

    public function savePaymentInfo(): void
    {
        $bankDetail = $this->companyData['bank_detail'] ?? null;
        $softwareHandover = $this->companyData['software_handover'] ?? null;
        $lead = $this->companyData['lead'] ?? null;

        // Update or create BankDetail
        if ($lead) {
            BankDetail::updateOrCreate(
                ['lead_id' => $lead->id],
                [
                    'bank_account_no' => $this->companyBankAccount,
                    'bank_name' => $this->bankName,
                    'beneficiary_name' => $this->nameOnBankAccount,
                ]
            );
        }

        // Update SoftwareHandover if exists
        if ($softwareHandover) {
            $softwareHandover->update([
                'autocount_debtor_code' => $this->customerAccountCode,
            ]);
        }

        $this->editingPaymentInfo = false;

        Notification::make()
            ->title('Payment Information saved successfully')
            ->success()
            ->send();
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.company-profile-tab');
    }
}
