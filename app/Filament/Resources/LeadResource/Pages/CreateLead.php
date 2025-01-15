<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Classes\Encryptor;
use App\Filament\Resources\LeadResource;
use App\Models\ActivityLog;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Parfaitementweb\FilamentCountryField\Forms\Components\Country;

class CreateLead extends CreateRecord
{
    protected static string $resource = LeadResource::class;

    public function form(Form $form): Form
    {
        return parent::form($form)->schema($this->getFormSchema());
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.leads.view', [
            'record' => Encryptor::encrypt($this->record->id),
        ]);
    }

    protected function getCreatedNotificationMessage(): ?string
    {
        return 'The lead has been successfully created! 🎉';
    }

    protected function afterCreate(): void
    {
        // Fetch the latest activity log for the created lead
        $latestActivityLog = ActivityLog::where('subject_id', $this->record->id)
            ->orderByDesc('created_at')
            ->first();

        // Update the activity log description
        if ($latestActivityLog) {
            $latestActivityLog->update([
                'description' => 'New lead created',
                'causer_id' => 0, // Assuming 0 means the system created it
            ]);
        }
    }

    protected function getFormSchema(): array
    {
        return [
            // Define fields relevant for creating a lead
            TextInput::make('name')
                ->label('Lead Name')
                ->required(),
            TextInput::make('email')
                ->label('Work Email')
                ->email()
                ->required(),
            TextInput::make('phone')
                ->label('Phone Number')
                ->required(),
            TextInput::make('lead_code')
                ->label('Lead Source')
                ->default('CRM')
                ->readOnly(),
            TextInput::make('company_name')
                ->label('Company Name')
                ->required()
                ->dehydrateStateUsing(function ($state, $set, $get) {
                    $latestLeadId = \App\Models\Lead::max('id') ?? 0; // Get the latest lead ID or default to 0

                    // Step 2: Determine the next Lead ID
                    $nextLeadId = $latestLeadId + 1;
                    // Create a new CompanyDetail record and associate it with the Lead
                    $companyDetail = \App\Models\CompanyDetail::create([
                        'company_name' => $state, // The company name
                        'lead_id' => $nextLeadId      // Associate with the current Lead
                    ]);

                    // Store the new CompanyDetail ID in the `company_name` field of the Lead table
                    $set('company_name', $companyDetail->id);

                    return $companyDetail->id; // Optionally return the ID
                }),
            Country::make('country')
                ->label('Country')
                ->required(),
            Select::make('company_size')
                ->label('Company Size')
                ->options([
                    '1-24' => '1 - 24',
                    '25-99' => '25 - 99',
                    '100-500' => '100 - 500',
                    '501 and Above' => '501 and Above',
                ])
                ->required(),
            Select::make('products')
                ->label('Products')
                ->multiple()
                ->options([
                    'smart_parking' => 'Smart Parking Management (Cashless, LPR, Valet)',
                    'hr' => 'HR (Attendance, Leave, Claim, Payroll, Hire, Profile)',
                    'property_management' => 'Property Management (Neighbour, Accounting)',
                    'security_people_flow' => 'Security & People Flow (Visitor, Access, Patrol, IoT)',
                    'merchants' => 'i-Merchants (Near Field Commerce, Loyalty Program)',
                    'smart_city' => 'Smart City',
                ])
                ->required(), // Ensure it is stored properly in the database
        ];
    }
}
