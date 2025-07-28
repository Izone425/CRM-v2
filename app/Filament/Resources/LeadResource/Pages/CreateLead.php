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
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;

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

            // Get all users with role_id 1
            $adminUsers = User::where('role_id', 1)->get();

            // Start with fixed recipient
            $recipients = collect([
                (object)[
                    'email' => 'faiz@timeteccloud.com',
                    'name' => 'Faiz'
                ]
            ]);

            // Add all users with role_id 1 to recipients
            foreach ($adminUsers as $adminUser) {
                $recipients->push((object)[
                    'email' => $adminUser->email,
                    'name' => $adminUser->name
                ]);
            }

            // Remove any duplicates by email
            $recipients = $recipients->unique('email');

            foreach ($recipients as $recipient) {
                $emailContent = [
                    'leadOwnerName' => $recipient->name ?? 'Unknown Person',
                    'lead' => [
                        'lead_code' => $lead->lead_code ?? 'N/A',
                        'creator' => auth()->user()->name,
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
                // ->reactive()
                // Replace suffix icon with a clickable search action
                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                ->afterStateHydrated(fn($state) => Str::upper($state))
                ->afterStateUpdated(fn($state) => Str::upper($state))
                ->suffixAction(
                    \Filament\Forms\Components\Actions\Action::make('searchCompanies')
                        ->label('Search')
                        ->icon('heroicon-o-magnifying-glass')
                        ->color('primary')
                        ->action(function ($state, $set, $livewire) {
                            if (empty($state)) {
                                $set('company_name_helper_text', "Type something to search");
                                return;
                            }

                            // Show loading state
                            $set('company_search_loading', true);

                            // Use sleep to slow down the search for visual effect
                            usleep(1000000); // 1 second delay

                            // Check if company with similar name already exists
                            $baseCompanyName = preg_replace('/ SDN\.? BHD\.?$/i', '', $state);
                            $existingCompanies = \App\Models\CompanyDetail::where('company_name', 'LIKE', $baseCompanyName . '%')
                                ->get();

                            // To this to find any occurrence of the search term:
                            $searchTerm = trim(preg_replace('/ SDN\.? BHD\.?$/i', '', $state));
                            $existingCompanies = \App\Models\CompanyDetail::where(function($query) use ($searchTerm) {
                                // Try exact match
                                $query->where('company_name', $searchTerm)
                                // Or name starts with search term
                                ->orWhere('company_name', 'LIKE', $searchTerm . '%')
                                // Or name contains search term
                                ->orWhere('company_name', 'LIKE', '%' . $searchTerm . '%');
                            })
                            ->get();

                            // If exists, set helper text with found company details
                            if ($existingCompanies->isNotEmpty()) {
                                $duplicateInfo = $existingCompanies->map(function($company) {
                                    $leadId = $company->lead_id;
                                    return "• {$company->company_name} (Lead ID: " . str_pad($leadId, 5, '0', STR_PAD_LEFT) . ")";
                                })->implode("\n");

                                // Store as plain string with HTML markup instead of HtmlString object
                                $set('company_name_helper_text', '<span style="color:red;">⚠️ Similar companies already exist:</span><br>' . nl2br(htmlspecialchars($duplicateInfo)));
                            } else {
                                // Store as plain string with HTML markup
                                $set('company_name_helper_text', '<span style="color:green;">✓ No similar companies found</span>');
                            }

                            // Reset loading state
                            $set('company_search_loading', false);
                        })
                )
                ->helperText(function (callable $get) {
                    if ($get('company_search_loading')) {
                        return "Searching for similar companies...";
                    }

                    // Get the helper text which is now stored as a string with HTML markup
                    $helperText = $get('company_name_helper_text');

                    // Convert it to HtmlString only when rendering, not when storing
                    return $helperText ? new HtmlString($helperText) : null;
                })
                // Make sure you have this in your component's properties
                ->dehydrateStateUsing(function ($state, $set, $get) {
                    // Fix: Assign the result of strtoupper back to $state
                    $state = strtoupper(trim($state));

                    $latestLeadId = \App\Models\Lead::max('id') ?? 0;

                    // Determine the next Lead ID
                    $nextLeadId = $latestLeadId + 1;

                    // Create a new CompanyDetail record and associate it with the Lead
                    $companyDetail = \App\Models\CompanyDetail::create([
                        'company_name' => $state, // The company name (now properly uppercase)
                        'lead_id' => $nextLeadId  // Associate with the current Lead
                    ]);

                    // Store the new CompanyDetail ID in the `company_name` field of the Lead table
                    $set('company_name', $companyDetail->id);

                    return $companyDetail->id;
                }),
            TextInput::make('name')
                ->label('Name')
                ->required()
                ->reactive()
                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                ->afterStateHydrated(fn($state) => Str::upper($state))
                ->afterStateUpdated(fn($state) => Str::upper($state)),
            TextInput::make('email')
                ->label('Work Email Address')
                ->email()
                ->required()
                ->suffixAction(
                    \Filament\Forms\Components\Actions\Action::make('searchEmail')
                        ->label('Verify')
                        ->icon('heroicon-o-magnifying-glass')
                        ->color('primary')
                        ->action(function ($state, $set, $livewire) {
                            if (empty($state)) {
                                $set('email_helper_text', "Please enter an email to verify");
                                return;
                            }

                            // Show loading state
                            $set('email_search_loading', true);

                            // Use sleep for visual effect
                            usleep(800000); // 0.8 second delay

                            // Check if email already exists in the Lead table
                            $existingLeadsWithEmail = \App\Models\Lead::where('email', $state)->get();

                            // If exists, set helper text with found lead details
                            if ($existingLeadsWithEmail->isNotEmpty()) {
                                $duplicateInfo = $existingLeadsWithEmail->map(function($lead) {
                                    $companyName = $lead->companyDetail ? $lead->companyDetail->company_name : 'Unknown Company';
                                    return "• {$companyName} (Lead ID: " . str_pad($lead->id, 5, '0', STR_PAD_LEFT) . ")";
                                })->implode("\n");

                                // Store as plain string with HTML markup
                                $set('email_helper_text', '<span style="color:red;">⚠️ This email is already in use:</span><br>' . nl2br(htmlspecialchars($duplicateInfo)));
                            } else {
                                // Store as plain string with HTML markup
                                $set('email_helper_text', '<span style="color:green;">✓ Email is unique</span>');
                            }

                            // Reset loading state
                            $set('email_search_loading', false);
                        })
                )
                ->helperText(function (callable $get) {
                    if ($get('email_search_loading')) {
                        return "Verifying email...";
                    }

                    // Get the helper text which is now stored as a string with HTML markup
                    $helperText = $get('email_helper_text');

                    // Convert it to HtmlString only when rendering, not when storing
                    return $helperText ? new HtmlString($helperText) : null;
                }),
            PhoneInput::make('phone')
                ->label('Phone Number')
                ->required()
                ->suffixAction(
                    \Filament\Forms\Components\Actions\Action::make('searchPhone')
                        ->label('Verify')
                        ->icon('heroicon-o-magnifying-glass')
                        ->color('primary')
                        ->action(function ($state, $set, $livewire) {
                            if (empty($state)) {
                                $set('phone_helper_text', "Please enter a phone number to verify");
                                return;
                            }

                            // Show loading state
                            $set('phone_search_loading', true);

                            // Use sleep for visual effect
                            usleep(800000); // 0.8 second delay

                            // Remove the "+" symbol from the phone number for searching
                            $searchPhone = ltrim($state, '+');

                            // Check if phone already exists in the Lead table
                            $existingLeadsWithPhone = \App\Models\Lead::where('phone', $searchPhone)->get();

                            // If exists, set helper text with found lead details
                            if ($existingLeadsWithPhone->isNotEmpty()) {
                                $duplicateInfo = $existingLeadsWithPhone->map(function($lead) {
                                    $companyName = $lead->companyDetail ? $lead->companyDetail->company_name : 'Unknown Company';
                                    return "• {$companyName} (Lead ID: " . str_pad($lead->id, 5, '0', STR_PAD_LEFT) . ")";
                                })->implode("\n");

                                // Store as plain string with HTML markup
                                $set('phone_helper_text', '<span style="color:red;">⚠️ This phone number is already in use:</span><br>' . nl2br(htmlspecialchars($duplicateInfo)));
                            } else {
                                // Store as plain string with HTML markup
                                $set('phone_helper_text', '<span style="color:green;">✓ Phone number is unique</span>');
                            }

                            // Reset loading state
                            $set('phone_search_loading', false);
                        })
                )
                ->helperText(function (callable $get) {
                    if ($get('phone_search_loading')) {
                        return "Verifying phone number...";
                    }

                    // Get the helper text which is now stored as a string with HTML markup
                    $helperText = $get('phone_helper_text');

                    // Convert it to HtmlString only when rendering, not when storing
                    return $helperText ? new HtmlString($helperText) : null;
                })
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

            // Select::make('lead_code')
            //     ->label('Lead Source')
            //     ->options(function () {
            //         $user = auth()->user();

            //         // Get all lead sources
            //         $query = LeadSource::query();

            //         // Apply role-based filtering
            //         if ($user->role_id === 1) { // Lead Owner
            //             $query->where('accessible_by_lead_owners', true);
            //         } elseif ($user->role_id === 2) { // Salesperson
            //             if ($user->is_timetec_hr) {
            //                 $query->where('accessible_by_timetec_hr_salespeople', true);
            //             } else {
            //                 $query->where('accessible_by_non_timetec_hr_salespeople', true);
            //             }
            //         }
            //         // Managers (role_id 3) can see all options

            //         return $query->pluck('lead_code', 'lead_code');
            //     })
            //     ->required(),

            Select::make('lead_code')
                ->label('Lead Source')
                ->default(function () {
                    $roleId = Auth::user()->role_id;
                    return $roleId == 2 ? 'Salesperson Lead' : ($roleId == 1 ? 'Website' : '');
                })
                ->options(function () {
                    $user = Auth::user();

                    // For other users, get only the lead sources they have access to
                    $leadSources = LeadSource::all();

                    $accessibleLeadSources = $leadSources->filter(function($leadSource) use ($user) {
                        // If allowed_users is not set or empty, everyone can access
                        if (empty($leadSource->allowed_users)) {
                            return false;  // Change to true if you want unassigned lead sources to be available to everyone
                        }

                        // Check if user ID is in the allowed_users array
                        $allowedUsers = is_array($leadSource->allowed_users)
                            ? $leadSource->allowed_users
                            : json_decode($leadSource->allowed_users, true);

                        return in_array($user->id, $allowedUsers);
                    });

                    return $accessibleLeadSources->pluck('lead_code', 'lead_code')->toArray();
                })
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
