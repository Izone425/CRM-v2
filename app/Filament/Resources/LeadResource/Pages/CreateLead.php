<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Classes\Encryptor;
use App\Filament\Resources\LeadResource;
use App\Mail\NewLeadNotification;
use App\Models\ActivityLog;
use App\Models\LeadSource;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Ysfkaya\FilamentPhoneInput\Infolists\PhoneEntry;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\Tables\PhoneColumn;
use App\Models\Lead;

class CreateLead extends CreateRecord
{
    protected static string $resource = LeadResource::class;
    protected ?string $companyName = null;
    protected ?string $emailAddress = null;
    protected ?string $phoneNumber = null;
    protected bool $hasDuplicates = false;
    protected string $duplicateIds = '';

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
        return 'New lead created successfully';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Store values for duplicate checking
        $this->companyName = $data['company_name'] ?? null;
        $this->emailAddress = $data['email'] ?? null;
        $this->phoneNumber = $data['phone'] ?? null;

        // Check for duplicates
        $this->checkForDuplicates();

        return $data;
    }

    protected function checkForDuplicates(): void
    {
        // First get the actual company name from the CompanyDetail relation
        $companyNameToCheck = null;
        if ($this->companyName) {
            // Since company_name contains the CompanyDetail ID at this point, we need to get the actual name
            $companyDetail = \App\Models\CompanyDetail::find($this->companyName);
            if ($companyDetail) {
                $companyNameToCheck = $companyDetail->company_name;
            }
        }

        $duplicateLeads = Lead::query()
            ->where(function ($query) use ($companyNameToCheck) {
                if ($companyNameToCheck) {
                    // Get base company name without SDN BHD suffix
                    $baseCompanyName = preg_replace('/ SDN\.? BHD\.?$/i', '', $companyNameToCheck);

                    // Search for any company that starts with the base name (ignoring suffix)
                    $query->whereHas('companyDetail', function ($q) use ($baseCompanyName) {
                        $q->where('company_name', 'LIKE', $baseCompanyName . '%');
                    });
                }

                if ($this->emailAddress) {
                    $query->orWhere('email', $this->emailAddress);
                }

                if ($this->phoneNumber) {
                    $query->orWhere('phone', $this->phoneNumber);
                }
            })
            ->get(['id']);

        $this->hasDuplicates = $duplicateLeads->isNotEmpty();

        if ($this->hasDuplicates) {
            $this->duplicateIds = $duplicateLeads->map(fn ($lead) => "LEAD ID " . str_pad($lead->id, 5, '0', STR_PAD_LEFT))
                ->implode("\n\n");

            // Show notification about duplicates
            Notification::make()
                ->title('Duplicate Lead Warning')
                ->warning()
                ->body("This lead may be a duplicate based on company name, email, or phone.\n\nDuplicate IDs:\n" . $this->duplicateIds)
                ->persistent()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('proceed')
                        ->label('Proceed Anyway')
                        ->close(),
                ])
                ->send();
        }
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
                'causer_id' => auth()->user()->id, // Assuming 0 means the system created it
            ]);
        }

        if (auth()->user()->role_id === 1) {
            sleep(1);
            $this->record->update([
                'lead_owner' => auth()->user()->name,
                'stage' => 'Transfer',
                'lead_status' => 'New',
                'categories' => 'Active',
                'pickup_date' => now(),
            ]);
            $latestActivityLog = ActivityLog::where('subject_id', $this->record->id)
                ->orderByDesc('id')
                ->first();

            $latestActivityLog->update([
                'subject_id' => $this->record->id,
                'description' => 'Lead assigned to Lead Owner: ' . auth()->user()->name,
                'causer_id' => auth()->user()->id,
            ]);
        } elseif (auth()->user()->role_id === 2) { // Corrected syntax
            sleep(1);
            $this->record->update([
                'salesperson' => auth()->user()->id,
                'salesperson_assigned_date' => now(),
                'stage' => 'Transfer',
                'lead_status' => 'RFQ-Transfer',
                'categories' => 'Active',
            ]);

            $latestActivityLog = ActivityLog::where('subject_id', $this->record->id)
            ->orderByDesc('id')
            ->first();

            $latestActivityLog->update([
                'subject_id' => $this->record->id,
                'description' => 'Lead assigned to Salesperson: ' . auth()->user()->name,
                'causer_id' => auth()->user()->id,
            ]);
        }

        // If this was a duplicate lead, log it
        if ($this->hasDuplicates) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($this->record)
                ->log('Created duplicate lead. Duplicate IDs: ' . $this->duplicateIds);
        }

        try {
            $lead = $this->record;
            $viewName = 'emails.new_lead';

            // Set fixed recipient
            $recipients = collect([
                (object)[
                    'email' => 'faiz@timeteccloud.com',
                    'name' => 'Faiz'
                ]
            ]);

            foreach ($recipients as $recipient) {
                $emailContent = [
                    'leadOwnerName' => $recipient->name ?? 'Unknown Person',
                    'lead' => [
                        'lead_code' => 'CRM',
                        'creator' => $lead->salesperson ? User::find($lead->salesperson)?->name : $lead->lead_owner,
                        'lastName' => $lead->name ?? 'N/A',
                        'company' => $lead->companyDetail->company_name ?? 'N/A',
                        'companySize' => $lead->company_size ?? 'N/A',
                        'phone' => $lead->phone ?? 'N/A',
                        'email' => $lead->email ?? 'N/A',
                        'country' => $lead->country ?? 'N/A',
                        'products' => $lead->products ?? 'N/A',
                    ],
                    'remark' => $lead->remark ?? 'No remarks provided',
                    'formatted_products' => is_array($lead->formatted_products)
                        ? implode(', ', $lead->formatted_products)
                        : ($lead->formatted_products ?? 'N/A'),
                ];

                Mail::to($recipient->email)
                    ->send(new \App\Mail\NewLeadNotification($emailContent, $viewName));
            }
        } catch (\Exception $e) {
            Log::error("New Lead Email Error: {$e->getMessage()}");
        }
    }

    protected function getFormSchema(): array
    {
        return [
            // Define fields relevant for creating a lead
            TextInput::make('company_name')
                ->label('Company Name')
                ->required()
                ->reactive()
                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
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
            TextInput::make('name')
                ->label('Name')
                ->required()
                ->reactive()
                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),
            TextInput::make('email')
                ->label('Work Email Address')
                ->email()
                ->required(),
            PhoneInput::make('phone')
                ->label('Phone Number')
                ->required()
                ->dehydrateStateUsing(function ($state) {
                    // Remove the "+" symbol from the phone number
                    return ltrim($state, '+');
                }),
            Select::make('company_size')
                ->label('Company Size')
                ->options([
                    '1-24' => '1 - 24',
                    '25-99' => '25 - 99',
                    '100-500' => '100 - 500',
                    '501 and Above' => '501 and Above',
                ])
                ->required(),
            Select::make('country')
                ->label('Country')
                ->searchable()
                ->required()
                ->default('MYS')
                ->options(function () {
                    $filePath = storage_path('app/public/json/CountryCodes.json');

                    if (file_exists($filePath)) {
                        $countriesContent = file_get_contents($filePath);
                        $countries = json_decode($countriesContent, true);

                        // Map 3-letter country codes to full country names
                        return collect($countries)->mapWithKeys(function ($country) {
                            return [$country['Code'] => ucfirst(strtolower($country['Country']))];
                        })->toArray();
                    }

                    return [];
                })
                ->dehydrateStateUsing(function ($state) {
                    // Convert the selected code to the full country name
                    $filePath = storage_path('app/public/json/CountryCodes.json');

                    if (file_exists($filePath)) {
                        $countriesContent = file_get_contents($filePath);
                        $countries = json_decode($countriesContent, true);

                        foreach ($countries as $country) {
                            if ($country['Code'] === $state) {
                                return ucfirst(strtolower($country['Country'])); // Store the full country name
                            }
                        }
                    }

                    return $state; // Fallback to the original state if mapping fails
                }),

            // Select::make('lead_code')
            //     ->label('Lead Source')
            //     ->default('CRM')
            //     ->options(fn () => LeadSource::pluck('salesperson')->toArray()) // Fetch existing lead sources
            //     ->searchable()
            //     ->createOptionForm([
            //         TextInput::make('lead_code')
            //             ->label('Lead Code')
            //             ->required()
            //             ->unique(\App\Models\LeadSource::class, 'lead_code')
            //             ->rules(['required', 'string', 'max:255', 'unique:lead_sources,lead_code']),

            //         TextInput::make('salesperson')
            //             ->label('Salesperson')
            //             ->required(),

            //         TextInput::make('platform')
            //         ->label('Platform')
            //         ->required(),
            //     ])
            //     ->createOptionUsing(function (array $data) {
            //         // Validate again before saving
            //         $validatedData = validator($data, [
            //             'code' => 'required|string|max:255|unique:lead_sources,code',
            //             'name' => 'required|string|max:255',
            //         ])->validate();

            //         // Create new lead source if validation passes
            //         $leadSource = \App\Models\LeadSource::create($validatedData);
            //         return $leadSource->code; // Return newly created option
            //     }),

            Select::make('lead_code')
                ->label('Lead Source')
                ->default(function () {
                    $roleId = Auth::user()->role_id;
                    return $roleId == 2 ? 'Salesperson Lead' : ($roleId == 1 ? 'Website' : '');
                })
                ->options(fn () => LeadSource::pluck('lead_code', 'lead_code')->toArray())
                ->searchable()
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
