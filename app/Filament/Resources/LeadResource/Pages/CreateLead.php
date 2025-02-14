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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Ysfkaya\FilamentPhoneInput\Infolists\PhoneEntry;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\Tables\PhoneColumn;

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
        return 'New lead created successfully';
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

        // try {
        //     $viewName = 'emails.new_lead'; // Replace with a valid default view
        //     $recipients = User::where('email', 'zilih.ng@timeteccloud.com')->get(['email', 'name']);
        //     foreach ($recipients as $recipient) {
        //         $emailContent = [
        //             'leadOwnerName' => $recipient->name ?? 'Unknown Person', // Lead Owner/Manager Name
        //             'lead' => [
        //                 'lead_code' => 'CRM',
        //                 'lastName' => $lead->name ?? 'N/A', // Lead's Last Name
        //                 'company' => $lead->companyDetail->company_name ?? 'N/A', // Lead's Company
        //                 'companySize' => $lead->company_size ?? 'N/A', // Company Size
        //                 'phone' => $lead->phone ?? 'N/A', // Lead's Phone
        //                 'email' => $lead->email ?? 'N/A', // Lead's Email
        //                 'country' => $lead->country ?? 'N/A', // Lead's Country
        //                 'products' => $lead->products ?? 'N/A', // Products
        //                 // 'solutions' => $lead->solutions ?? 'N/A', // Solutions
        //             ],
        //             'remark' => $data['remark'] ?? 'No remarks provided', // Custom Remark
        //             'formatted_products' => $this->record->formatted_products, // Add formatted products
        //         ];
        //         if (!empty($recipients)) {
        //             Mail::mailer('smtp')
        //                 ->to($recipient->email)
        //                 ->send(new NewLeadNotification($emailContent, $viewName));
        //         } else {
        //             info('No recipients with role_id = 2 found.');
        //         }
        //     }
        // } catch (\Exception $e) {
        //     // Handle email sending failure
        //     Log::error("Error: {$e->getMessage()}");
        // }
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

            Select::make('lead_code')
                ->label('Lead Source')
                ->default('CRM')
                ->options(fn () => LeadSource::pluck('salesperson')->toArray()) // Fetch existing lead sources
                ->searchable()
                ->createOptionForm([
                    TextInput::make('lead_code')
                        ->label('Lead Code')
                        ->required()
                        ->unique(\App\Models\LeadSource::class, 'lead_code')
                        ->rules(['required', 'string', 'max:255', 'unique:lead_sources,lead_code']),

                    TextInput::make('salesperson')
                        ->label('Salesperson')
                        ->required(),

                    TextInput::make('platform')
                    ->label('Platform')
                    ->required(),
                ])
                ->createOptionUsing(function (array $data) {
                    // Validate again before saving
                    $validatedData = validator($data, [
                        'code' => 'required|string|max:255|unique:lead_sources,code',
                        'name' => 'required|string|max:255',
                    ])->validate();

                    // Create new lead source if validation passes
                    $leadSource = \App\Models\LeadSource::create($validatedData);
                    return $leadSource->code; // Return newly created option
                }),

            Select::make('lead_code')
                ->label('Lead Source')
                ->default('CRM')
                ->options(fn () => LeadSource::pluck('salesperson')->toArray()) // Fetch existing lead sources
                ->searchable()
                ->createOptionForm([
                    TextInput::make('lead_code')
                        ->label('Lead Code')
                        ->required()
                        ->unique(\App\Models\LeadSource::class, 'lead_code')
                        ->rules(['required', 'string', 'max:255', 'unique:lead_sources,lead_code']),

                    TextInput::make('salesperson')
                        ->label('Salesperson')
                        ->required(),

                    TextInput::make('platform')
                        ->label('Platform')
                        ->required(),
                ])
                ->createOptionUsing(function (array $data) {
                    if (auth()->user()->role_id !== 3) {
                        Notification::make()
                            ->title('Access Denied')
                            ->body('You are not allowed to create a Lead Source.')
                            ->danger()
                            ->send();

                        return null; // Prevent creation
                    }

                    // Validate before saving
                    $validatedData = validator($data, [
                        'lead_code' => 'required|string|max:255|unique:lead_sources,lead_code',
                        'salesperson' => 'required|string|max:255',
                        'platform' => 'required|string|max:255',
                    ])->validate();

                    // Create new lead source if validation passes
                    $leadSource = \App\Models\LeadSource::create($validatedData);
                    return $leadSource->lead_code; // Return newly created option
                })
                ->visible(fn () => auth()->user()->role_id === 3),

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
