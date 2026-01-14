<?php
namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Models\Industry;
use App\Services\IrbmService;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Filament\Forms\Components\Actions\Action as Actio;

class SubsidiaryRelationManager extends RelationManager
{
    protected static string $relationship = 'subsidiaries';

    #[On('refresh-quotations')]
    #[On('refresh')] // General refresh event
    public function refresh()
    {
        $this->resetTable();
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->user_id === auth()->id();
    }

    public function defaultForm()
    {
        $leadCompany = $this->ownerRecord->companyDetail;
        $leadEInvoice = $this->ownerRecord->eInvoiceDetail;

        return [
            Grid::make(4)
                ->schema([
                    Grid::make(3)
                        ->schema([
                            Section::make('Company Information')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('company_name')
                                                ->label('COMPANY NAME')
                                                ->required()
                                                ->rules([
                                                    'regex:/^[A-Z0-9\s]+$/i',
                                                ])
                                                ->validationMessages([
                                                    'regex' => 'Company name can only contain letters, numbers, and spaces. Special characters are not allowed.',
                                                ])
                                                ->maxLength(255)
                                                ->default(fn() => $leadCompany ? $leadCompany->company_name : null)
                                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                            TextInput::make('business_register_number')
                                                ->label('BUSINESS REGISTER NUMBER')
                                                ->required()
                                                // ->minLength(12)
                                                ->maxLength(12)
                                                // ->rules(['regex:/^[0-9]{12}$/'])
                                                ->extraAlpineAttributes([
                                                    'x-on:input' => '
                                                        const start = $el.selectionStart;
                                                        const end = $el.selectionEnd;
                                                        const value = $el.value;
                                                        $el.value = value.toUpperCase();
                                                        $el.setSelectionRange(start, end);
                                                    '
                                                ])
                                                ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                                                ->rules([
                                                    'regex:/^[A-Z0-9\s]+$/i',
                                                ])
                                                ->validationMessages([
                                                    'regex' => 'Company name can only contain letters, numbers, and spaces. Special characters are not allowed.',
                                                ])
                                                ->suffixAction(
                                                    Actio::make('searchTin')
                                                        ->icon('heroicon-o-magnifying-glass')
                                                        ->color('primary')
                                                        ->action(function ($state, $set, $get) {
                                                            if (empty($state)) {
                                                                Notification::make()
                                                                    ->title('Business Register Number Required')
                                                                    ->body('Please enter a Business Register Number before searching.')
                                                                    ->warning()
                                                                    ->send();
                                                                return;
                                                            }

                                                            try {
                                                                // Get company name from the form
                                                                $companyName = $get('company_name') ?? '';

                                                                // Call IRBM service to search TIN
                                                                $irbmService = new IrbmService();
                                                                $tin = $irbmService->searchTaxPayerTin(
                                                                    name: '',
                                                                    idType: 'BRN',
                                                                    idValue: strtoupper($state)
                                                                );

                                                                if (!empty($tin)) {
                                                                    // Set the TIN in the tax_identification_number field
                                                                    $set('tax_identification_number', $tin);

                                                                    Notification::make()
                                                                        ->title('TIN Found')
                                                                        ->body("Tax Identification Number: {$tin}")
                                                                        ->success()
                                                                        ->send();

                                                                    Log::channel('irbm_log')->info("TIN found for BRN {$state}: {$tin}");
                                                                } else {
                                                                    Notification::make()
                                                                        ->title('TIN Not Found')
                                                                        ->body('No Tax Identification Number found for this Business Register Number.')
                                                                        ->warning()
                                                                        ->send();

                                                                    Log::channel('irbm_log')->warning("No TIN found for BRN: {$state}");
                                                                }
                                                            } catch (\Exception $e) {
                                                                Notification::make()
                                                                    ->title('Search Failed')
                                                                    ->body('Failed to search TIN: ' . $e->getMessage())
                                                                    ->danger()
                                                                    ->send();

                                                                Log::channel('irbm_log')->error('TIN search error: ' . $e->getMessage());
                                                            }
                                                        })
                                                ),

                                            TextInput::make('tax_identification_number')
                                                ->label('TAX IDENTIFICATION NUMBER')
                                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                            Select::make('msic_code')
                                                ->label('MSIC Code')
                                                ->searchable()
                                                ->preload()
                                                ->options(function () {
                                                    try {
                                                        $msicCodes = IrbmService::getMSICCodes();

                                                        // Format: "code - description"
                                                        return collect($msicCodes)->mapWithKeys(function ($item, $key) {
                                                            return [$item['Code'] => "{$item['Code']} - {$item['Description']}"];
                                                        })->toArray();
                                                    } catch (\Exception $e) {
                                                        Log::channel('irbm_log')->error('Error loading MSIC codes: ' . $e->getMessage());
                                                        return [];
                                                    }
                                                })
                                                ->required()
                                                ->default(function ($record) {
                                                    // First try eInvoiceDetail, then companyDetail
                                                    return $record->eInvoiceDetail->msic_code ??
                                                        $record->companyDetail->msic_code ?? '';
                                                })
                                                ->getSearchResultsUsing(function (string $search) {
                                                    try {
                                                        $msicCodes = IrbmService::getMSICCodes();
                                                        // Search by code or description

                                                        return collect($msicCodes)
                                                            ->filter(function ($item, $key) use ($search) {
                                                                return stripos($item['Code'], $search) !== false ||
                                                                    stripos($item['Description'], $search) !== false;
                                                            })
                                                            ->mapWithKeys(function ($item, $key) {
                                                                return [$item['Code'] => "{$item['Code']} - {$item['Description']}"];
                                                            })
                                                            ->take(50) // Limit results
                                                            ->toArray();
                                                    } catch (\Exception $e) {
                                                        Log::channel('irbm_log')->error('Error searching MSIC codes: ' . $e->getMessage());
                                                        return [];
                                                    }
                                                })
                                                ->getOptionLabelUsing(function ($value) {
                                                    try {
                                                        if (empty($value)) {
                                                            return '';
                                                        }

                                                        $description = IrbmService::getMSICCodes($value);
                                                        return "{$value} - {$description}";
                                                    } catch (\Exception $e) {
                                                        return $value;
                                                    }
                                                }),
                                        ]),
                                ])
                                ->columnSpan(3),

                            Section::make('Address Information')
                                ->schema([
                                    TextInput::make('company_address1')
                                        ->label('ADDRESS 1')
                                        ->required()
                                        ->default(fn() => $leadCompany ? $leadCompany->company_address1 : null)
                                        ->maxLength(255)
                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                        ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                        ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                        ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                    TextInput::make('company_address2')
                                        ->label('ADDRESS 2')
                                        ->default(fn() => $leadCompany ? $leadCompany->company_address2 : null)
                                        ->maxLength(255)
                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                        ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                        ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                        ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                    Grid::make(3)
                                        ->schema([
                                            TextInput::make('postcode')
                                                ->label('POSTCODE')
                                                ->required()
                                                ->default(fn() => $leadCompany ? $leadCompany->postcode : null)
                                                ->maxLength(5)
                                                ->rules(['regex:/^[0-9]+$/'])
                                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                            TextInput::make('city')
                                                ->label('CITY')
                                                ->required()
                                                ->default(fn() => $leadCompany ? $leadCompany->city : $this->ownerRecord->city)
                                                ->maxLength(255)
                                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                            Select::make('state')
                                                ->label('STATE')
                                                ->required()
                                                ->default(fn() => $leadCompany ? $leadCompany->state : null)
                                                ->options(function () {
                                                    $filePath = storage_path('app/public/json/StateCodes.json');
                                                    if (file_exists($filePath)) {
                                                        $statesContent = file_get_contents($filePath);
                                                        $states = json_decode($statesContent, true);
                                                        return collect($states)->mapWithKeys(function ($state) {
                                                            return [ucfirst(strtolower($state['State'])) => ucfirst(strtolower($state['State']))];
                                                        })->toArray();
                                                    }
                                                    return ['Selangor' => 'Selangor'];
                                                })
                                                ->searchable()
                                                ->preload(),
                                        ]),

                                    Select::make('country')
                                        ->label('COUNTRY')
                                        ->required()
                                        ->default('MYS')
                                        ->options(function () {
                                            $filePath = storage_path('app/public/json/CountryCodes.json');
                                            if (file_exists($filePath)) {
                                                $countriesContent = file_get_contents($filePath);
                                                $countries = json_decode($countriesContent, true);
                                                return collect($countries)->mapWithKeys(function ($country) {
                                                    return [$country['Code'] => ucfirst(strtolower($country['Country']))];
                                                })->toArray();
                                            }
                                            return ['MYS' => 'Malaysia'];
                                        })
                                        ->searchable()
                                        ->preload(),
                                ])
                                ->columnSpan(3),

                            Section::make('Business Information')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            Select::make('currency')
                                                ->label('CURRENCY')
                                                ->required()
                                                ->default('MYR')
                                                ->options([
                                                    'MYR' => 'MYR',
                                                    'USD' => 'USD',
                                                ])
                                                ->searchable()
                                                ->preload(),

                                            Select::make('business_type')
                                                ->label('BUSINESS TYPE')
                                                ->required()
                                                ->default('local_business')
                                                ->options([
                                                    'local_business' => 'Local Business',
                                                    'foreign_business' => 'Foreign Business',
                                                ])
                                                ->searchable()
                                                ->preload(),
                                        ]),

                                    Grid::make(2)
                                        ->schema([
                                            Select::make('business_category')
                                                ->label('BUSINESS CATEGORY')
                                                ->required()
                                                ->default('business')
                                                ->options([
                                                    'business' => 'Business',
                                                    'government' => 'Government',
                                                ])
                                                ->searchable()
                                                ->preload(),

                                            Select::make('billing_category')
                                                ->label('BILLING CATEGORY')
                                                ->required()
                                                ->default('billing_to_subscriber')
                                                ->options([
                                                    'billing_to_subscriber' => 'Billing to Subscriber',
                                                    'billing_to_reseller' => 'Billing to Reseller',
                                                ])
                                                ->searchable()
                                                ->preload(),
                                        ]),
                                ])
                                ->columnSpan(3),
                        ])->columnSpan(3),

                    Grid::make(1)
                        ->schema([
                            Section::make('HR Contact Person')
                                ->schema([
                                    TextInput::make('name')
                                        ->label('NAME')
                                        ->default(fn() => $leadCompany->name ? $leadCompany->name : $this->ownerRecord->name)
                                        ->required()
                                        ->maxLength(255)
                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                        ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                        ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                    TextInput::make('contact_number')
                                        ->label('CONTACT NUMBER')
                                        ->default(fn() => $leadCompany->contact_no ? $leadCompany->contact_no : $this->ownerRecord->phone)
                                        ->required()
                                        ->tel()
                                        ->maxLength(20)
                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                        ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                        ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                    TextInput::make('email')
                                        ->label('EMAIL ADDRESS')
                                        ->default(fn() => $leadCompany->email ? $leadCompany->email : $this->ownerRecord->email)
                                        ->required()
                                        ->email()
                                        ->maxLength(255),

                                    TextInput::make('position')
                                        ->label('POSITION')
                                        ->default(fn() => $leadCompany->position ? $leadCompany->position : ($this->ownerRecord->position ?? null))
                                        ->required()
                                        ->maxLength(100)
                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                        ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                        ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),
                                ])
                                ->columnSpan(1),

                            Section::make('Finance Contact Person')
                                ->schema([
                                    TextInput::make('finance_person_name')
                                        ->label('FINANCE PERSON NAME')
                                        ->default(fn() => $leadEInvoice ? $leadEInvoice->finance_person_name : null)
                                        ->required()
                                        ->maxLength(255)
                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                        ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                        ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                    TextInput::make('finance_person_contact')
                                        ->label('FINANCE PERSON CONTACT')
                                        ->default(fn() => $leadEInvoice ? $leadEInvoice->finance_person_contact : null)
                                        ->required()
                                        ->maxLength(20)
                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                        ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                        ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                    TextInput::make('finance_person_email')
                                        ->label('FINANCE PERSON EMAIL')
                                        ->default(fn() => $leadEInvoice ? $leadEInvoice->finance_person_email : null)
                                        ->required()
                                        ->email()
                                        ->maxLength(255),

                                    TextInput::make('finance_person_position')
                                        ->label('FINANCE PERSON POSITION')
                                        ->default(fn() => $leadEInvoice ? $leadEInvoice->finance_person_position : null)
                                        ->required()
                                        ->maxLength(100)
                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                        ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                        ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),
                                ])
                                ->columnSpan(1),
                        ])->columnSpan(1),
                ])
                ->columns(4),
            ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('company_name')
            ->columns([
                Tables\Columns\TextColumn::make('company_name')
                    ->label('COMPANY NAME')
                    ->sortable(),

                Tables\Columns\TextColumn::make('business_register_number')
                    ->label('BUSINESS REG NO')
                    ->limit(15),

                Tables\Columns\TextColumn::make('name')
                    ->label('HR CONTACT NAME')
                    ->limit(20),

                Tables\Columns\TextColumn::make('contact_number')
                    ->label('HR CONTACT NO.')
                    ->limit(15),

                Tables\Columns\TextColumn::make('finance_person_name')
                    ->label('FINANCE CONTACT NAME')
                    ->limit(20),

                Tables\Columns\TextColumn::make('finance_person_contact')
                    ->label('FINANCE CONTACT NO.')
                    ->limit(15),

                Tables\Columns\TextColumn::make('state')
                    ->label('STATE')
                    ->limit(10),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('ADDED ON')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Add Subsidiary')
                    ->icon('heroicon-o-plus')
                    ->modalWidth('6xl')
                    ->form($this->defaultForm())
                    ->action(function (array $data) {
                        $this->ownerRecord->subsidiaries()->create($data);
                        Notification::make()
                            ->title('Subsidiary added successfully')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('edit')
                        ->icon('heroicon-o-pencil-square')
                        ->label('Edit')
                        ->modalHeading(fn($record) => 'Edit Subsidiary: ' . $record->company_name)
                        ->modalWidth('7xl')
                        ->form(function ($record) {
                            // ✅ Return form with pre-filled values from the record
                            return [
                                Grid::make(4)
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Section::make('Company Information')
                                                    ->schema([
                                                        Grid::make(2)
                                                            ->schema([
                                                                TextInput::make('company_name')
                                                                    ->label('COMPANY NAME')
                                                                    ->required()
                                                                    ->rules([
                                                                        'regex:/^[A-Z0-9\s]+$/i',
                                                                    ])
                                                                    ->validationMessages([
                                                                        'regex' => 'Company name can only contain letters, numbers, and spaces. Special characters are not allowed.',
                                                                    ])
                                                                    ->maxLength(255)
                                                                    ->default($record->company_name)
                                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                                    ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                                    ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                                                TextInput::make('business_register_number')
                                                                    ->label('BUSINESS REGISTER NUMBER')
                                                                    ->required()
                                                                    // ->minLength(12)
                                                                    ->maxLength(12)
                                                                    // ->rules(['regex:/^[0-9]{12}$/'])
                                                                    ->extraAlpineAttributes([
                                                                        'x-on:input' => '
                                                                            const start = $el.selectionStart;
                                                                            const end = $el.selectionEnd;
                                                                            const value = $el.value;
                                                                            $el.value = value.toUpperCase();
                                                                            $el.setSelectionRange(start, end);
                                                                        '
                                                                    ])
                                                                    ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                                                                    ->rules([
                                                                        'regex:/^[A-Z0-9\s]+$/i',
                                                                    ])
                                                                    ->validationMessages([
                                                                        'regex' => 'Company name can only contain letters, numbers, and spaces. Special characters are not allowed.',
                                                                    ])
                                                                    ->default($record->business_register_number)
                                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                                    ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                                    ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null)
                                                                    ->suffixAction(
                                                                        Actio::make('searchTin')
                                                                            ->icon('heroicon-o-magnifying-glass')
                                                                            ->color('primary')
                                                                            ->action(function ($state, $set, $get) {
                                                                                if (empty($state)) {
                                                                                    Notification::make()
                                                                                        ->title('Business Register Number Required')
                                                                                        ->body('Please enter a Business Register Number before searching.')
                                                                                        ->warning()
                                                                                        ->send();
                                                                                    return;
                                                                                }

                                                                                try {
                                                                                    // Get company name from the form
                                                                                    $companyName = $get('company_name') ?? '';

                                                                                    // Call IRBM service to search TIN
                                                                                    $irbmService = new IrbmService();
                                                                                    $tin = $irbmService->searchTaxPayerTin(
                                                                                        name: '',
                                                                                        idType: 'BRN',
                                                                                        idValue: strtoupper($state)
                                                                                    );

                                                                                    if (!empty($tin)) {
                                                                                        // Set the TIN in the tax_identification_number field
                                                                                        $set('tax_identification_number', $tin);

                                                                                        Notification::make()
                                                                                            ->title('TIN Found')
                                                                                            ->body("Tax Identification Number: {$tin}")
                                                                                            ->success()
                                                                                            ->send();

                                                                                        Log::channel('irbm_log')->info("TIN found for BRN {$state}: {$tin}");
                                                                                    } else {
                                                                                        Notification::make()
                                                                                            ->title('TIN Not Found')
                                                                                            ->body('No Tax Identification Number found for this Business Register Number.')
                                                                                            ->warning()
                                                                                            ->send();

                                                                                        Log::channel('irbm_log')->warning("No TIN found for BRN: {$state}");
                                                                                    }
                                                                                } catch (\Exception $e) {
                                                                                    Notification::make()
                                                                                        ->title('Search Failed')
                                                                                        ->body('Failed to search TIN: ' . $e->getMessage())
                                                                                        ->danger()
                                                                                        ->send();

                                                                                    Log::channel('irbm_log')->error('TIN search error: ' . $e->getMessage());
                                                                                }
                                                                            })
                                                                    ),

                                                                TextInput::make('tax_identification_number')
                                                                    ->label('TAX IDENTIFICATION NUMBER')
                                                                    ->default($record->tax_identification_number)
                                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                                    ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                                    ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                                                Select::make('msic_code')
                                                                    ->label('MSIC Code')
                                                                    ->searchable()
                                                                    ->preload()
                                                                    ->options(function () {
                                                                        try {
                                                                            $msicCodes = IrbmService::getMSICCodes();

                                                                            // Format: "code - description"
                                                                            return collect($msicCodes)->mapWithKeys(function ($item, $key) {
                                                                                return [$item['Code'] => "{$item['Code']} - {$item['Description']}"];
                                                                            })->toArray();
                                                                        } catch (\Exception $e) {
                                                                            Log::channel('irbm_log')->error('Error loading MSIC codes: ' . $e->getMessage());
                                                                            return [];
                                                                        }
                                                                    })
                                                                    ->required()
                                                                    ->default(function ($record) {
                                                                        // First try eInvoiceDetail, then companyDetail
                                                                        return $record->msic_code ?? '';
                                                                    })
                                                                    ->getSearchResultsUsing(function (string $search) {
                                                                        try {
                                                                            $msicCodes = IrbmService::getMSICCodes();
                                                                            // Search by code or description

                                                                            return collect($msicCodes)
                                                                                ->filter(function ($item, $key) use ($search) {
                                                                                    return stripos($item['Code'], $search) !== false ||
                                                                                        stripos($item['Description'], $search) !== false;
                                                                                })
                                                                                ->mapWithKeys(function ($item, $key) {
                                                                                    return [$item['Code'] => "{$item['Code']} - {$item['Description']}"];
                                                                                })
                                                                                ->take(50) // Limit results
                                                                                ->toArray();
                                                                        } catch (\Exception $e) {
                                                                            Log::channel('irbm_log')->error('Error searching MSIC codes: ' . $e->getMessage());
                                                                            return [];
                                                                        }
                                                                    })
                                                                    ->getOptionLabelUsing(function ($value) {
                                                                        try {
                                                                            if (empty($value)) {
                                                                                return '';
                                                                            }

                                                                            $description = IrbmService::getMSICCodes($value);
                                                                            return "{$value} - {$description}";
                                                                        } catch (\Exception $e) {
                                                                            return $value;
                                                                        }
                                                                    }),
                                                            ]),
                                                    ])
                                                    ->columnSpan(3),

                                                Section::make('Address Information')
                                                    ->schema([
                                                        TextInput::make('company_address1')
                                                            ->label('ADDRESS 1')
                                                            ->required()
                                                            ->default($record->company_address1)
                                                            ->maxLength(255)
                                                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                            ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                            ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                            ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                                        TextInput::make('company_address2')
                                                            ->label('ADDRESS 2')
                                                            ->default($record->company_address2)
                                                            ->maxLength(255)
                                                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                            ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                            ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                            ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                                        Grid::make(3)
                                                            ->schema([
                                                                TextInput::make('postcode')
                                                                    ->label('POSTCODE')
                                                                    ->required()
                                                                    ->default($record->postcode)
                                                                    ->maxLength(5)
                                                                    ->rules(['regex:/^[0-9]+$/'])
                                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                                    ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                                    ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                                                TextInput::make('city')
                                                                    ->label('CITY')
                                                                    ->required()
                                                                    ->default($record->city)
                                                                    ->maxLength(255)
                                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                                    ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                                    ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                                    ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                                                Select::make('state')
                                                                    ->label('STATE')
                                                                    ->required()
                                                                    ->default($record->state)
                                                                    ->options(function () {
                                                                        $filePath = storage_path('app/public/json/StateCodes.json');
                                                                        if (file_exists($filePath)) {
                                                                            $statesContent = file_get_contents($filePath);
                                                                            $states = json_decode($statesContent, true);
                                                                            return collect($states)->mapWithKeys(function ($state) {
                                                                                return [ucfirst(strtolower($state['State'])) => ucfirst(strtolower($state['State']))];
                                                                            })->toArray();
                                                                        }
                                                                        return ['Selangor' => 'Selangor'];
                                                                    })
                                                                    ->searchable()
                                                                    ->preload(),
                                                            ]),

                                                        Select::make('country')
                                                            ->label('COUNTRY')
                                                            ->required()
                                                            ->default($record->country ?? 'MYS')
                                                            ->options(function () {
                                                                $filePath = storage_path('app/public/json/CountryCodes.json');
                                                                if (file_exists($filePath)) {
                                                                    $countriesContent = file_get_contents($filePath);
                                                                    $countries = json_decode($countriesContent, true);
                                                                    return collect($countries)->mapWithKeys(function ($country) {
                                                                        return [$country['Code'] => ucfirst(strtolower($country['Country']))];
                                                                    })->toArray();
                                                                }
                                                                return ['MYS' => 'Malaysia'];
                                                            })
                                                            ->searchable()
                                                            ->preload(),
                                                    ])
                                                    ->columnSpan(3),

                                                Section::make('Business Information')
                                                    ->schema([
                                                        Grid::make(2)
                                                            ->schema([
                                                                Select::make('currency')
                                                                    ->label('CURRENCY')
                                                                    ->required()
                                                                    ->default($record->currency ?? 'MYR')
                                                                    ->options([
                                                                        'MYR' => 'MYR',
                                                                        'USD' => 'USD',
                                                                    ])
                                                                    ->searchable()
                                                                    ->preload(),

                                                                Select::make('business_type')
                                                                    ->label('BUSINESS TYPE')
                                                                    ->required()
                                                                    ->default($record->business_type ?? 'local_business')
                                                                    ->options([
                                                                        'local_business' => 'Local Business',
                                                                        'foreign_business' => 'Foreign Business',
                                                                    ])
                                                                    ->searchable()
                                                                    ->preload(),
                                                            ]),

                                                        Grid::make(2)
                                                            ->schema([
                                                                Select::make('business_category')
                                                                    ->label('BUSINESS CATEGORY')
                                                                    ->required()
                                                                    ->default($record->business_category ?? 'business')
                                                                    ->options([
                                                                        'business' => 'Business',
                                                                        'government' => 'Government',
                                                                    ])
                                                                    ->searchable()
                                                                    ->preload(),

                                                                Select::make('billing_category')
                                                                    ->label('BILLING CATEGORY')
                                                                    ->required()
                                                                    ->default($record->billing_category ?? 'billing_to_subscriber')
                                                                    ->options([
                                                                        'billing_to_subscriber' => 'Billing to Subscriber',
                                                                        'billing_to_reseller' => 'Billing to Reseller',
                                                                    ])
                                                                    ->searchable()
                                                                    ->preload(),
                                                            ]),
                                                    ])
                                                    ->columnSpan(3),
                                            ])->columnSpan(3),
                                        Grid::make(1)
                                            ->schema([
                                                Section::make('HR Contact Person')
                                                    ->schema([
                                                        TextInput::make('name')
                                                            ->label('NAME')
                                                            ->default($record->name)
                                                            ->required()
                                                            ->maxLength(255)
                                                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                            ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                            ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                                        TextInput::make('contact_number')
                                                            ->label('CONTACT NUMBER')
                                                            ->default($record->contact_number)
                                                            ->required()
                                                            ->tel()
                                                            ->maxLength(20)
                                                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                            ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                            ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                                        TextInput::make('email')
                                                            ->label('EMAIL ADDRESS')
                                                            ->default($record->email)
                                                            ->required()
                                                            ->email()
                                                            ->maxLength(255),

                                                        TextInput::make('position')
                                                            ->label('POSITION')
                                                            ->default($record->position)
                                                            ->required()
                                                            ->maxLength(100)
                                                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                            ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                            ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),
                                                    ])
                                                    ->columnSpan(1),

                                                Section::make('Finance Contact Person')
                                                    ->schema([
                                                        TextInput::make('finance_person_name')
                                                            ->label('FINANCE PERSON NAME')
                                                            ->default($record->finance_person_name)
                                                            ->required()
                                                            ->maxLength(255)
                                                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                            ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                            ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                                        TextInput::make('finance_person_email')
                                                            ->label('FINANCE PERSON EMAIL')
                                                            ->default($record->finance_person_email)
                                                            ->required()
                                                            ->email()
                                                            ->maxLength(255),

                                                        TextInput::make('finance_person_contact')
                                                            ->label('FINANCE PERSON CONTACT')
                                                            ->default($record->finance_person_contact)
                                                            ->required()
                                                            ->maxLength(20)
                                                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                            ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                            ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                                        TextInput::make('finance_person_position')
                                                            ->label('FINANCE PERSON POSITION')
                                                            ->default($record->finance_person_position)
                                                            ->required()
                                                            ->maxLength(100)
                                                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                            ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                            ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),
                                                    ])
                                                    ->columnSpan(1),
                                            ])->columnSpan(1),

                                        // Export Buttons Section
                                        \Filament\Forms\Components\View::make('components.subsidiary-export-buttons')
                                            ->viewData(['record' => $record])
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(4),
                            ];
                        })
                        ->action(function ($record, array $data) {
                            // Convert all data to uppercase except email and specific fields
                            foreach ($data as $key => $value) {
                                if (is_string($value) && !in_array($key, ['email', 'finance_person_email', 'business_type', 'business_category', 'billing_category', 'tax_identification_number', 'msic_code'])) {
                                    $data[$key] = Str::upper($value);
                                }
                            }

                            $record->update($data);

                            Notification::make()
                                ->title('Subsidiary updated successfully')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
}
