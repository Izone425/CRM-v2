<?php
namespace App\Filament\Resources\LeadResource\Tabs;

use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\EInvoiceDetail;
use App\Models\LeadSource;
use App\Models\SoftwareHandover;
use App\Models\HardwareHandover;
use App\Models\Industry;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\View;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View as IlluminateView;
use App\Classes\Encryptor;
use App\Services\IrbmService;

class SubscriberDetailsTabs
{
    public static function getSchema(): array
    {
        return [
            Grid::make(4)
            ->schema([
                Section::make('E-Invoice Details')
                    ->headerActions([
                        // Action::make('export_to_excel')
                        //     ->label('Export to Excel')
                        //     ->icon('heroicon-o-document-arrow-down')
                        //     ->color('success')
                        //     ->url(function ($record) {
                        //         return route('einvoice.export', ['lead' => Encryptor::encrypt($record->id)]);
                        //     })
                        //     ->openUrlInNewTab()
                        //     ->visible(fn (Lead $lead) =>
                        //         // Show export button only if e-invoice details exist
                        //         $lead->eInvoiceDetail !== null
                        //     ),
                        Action::make('edit_e_invoice')
                            ->label('Edit')
                            ->icon('heroicon-o-pencil')
                            ->color('primary')
                            ->modal()
                            ->modalHeading('Edit E-Invoice Details')
                            ->modalWidth('4xl')
                            ->form([
                                Section::make('Company Information')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('company_name')
                                                    ->label('Company Name')
                                                    ->required()
                                                    ->disabled(function ($record) {
                                                        // Define variables here
                                                        $isOlderThan30Days = $record->created_at->diffInDays(now()) > 30;
                                                        $isAdmin = auth()->user()->role_id === 1;

                                                        // Rule 1: If user has role_id 3, never disable the field regardless of lead age
                                                        if (auth()->user()->role_id === 3) {
                                                            return false;
                                                        }

                                                        // Rule 2: If lead has a salesperson assigned and current user is role_id 1, disable the field
                                                        if (!is_null($record->salesperson) && auth()->user()->role_id === 1) {
                                                            return true;
                                                        }

                                                        // Rule 3: Original condition - disable if older than 30 days and not admin
                                                        return $isOlderThan30Days && !$isAdmin;
                                                    })
                                                    ->helperText(function ($record) {
                                                        // Define variables here as well
                                                        $isOlderThan30Days = $record->created_at->diffInDays(now()) > 30;
                                                        $isAdmin = auth()->user()->role_id === 1;

                                                        // If user has role_id 3, no helper text needed
                                                        if (auth()->user()->role_id === 3) {
                                                            return '';
                                                        }

                                                        // If lead has a salesperson assigned and current user is role_id 1
                                                        if (!is_null($record->salesperson) && auth()->user()->role_id === 1) {
                                                            return 'Company name cannot be edited when a salesperson is assigned.';
                                                        }

                                                        // Original helper text
                                                        return $isOlderThan30Days && !$isAdmin ?
                                                            'Company name cannot be changed after 30 days. Please ask for Faiz on this issue.' : '';
                                                    })
                                                    ->maxLength(255),

                                                TextInput::make('business_register_number')
                                                    ->label('New Business Register Number')
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
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->suffixAction(
                                                        Action::make('searchTin')
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
                                                    ->label('Tax Identification Number')
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
                                                    ->readOnly()
                                                    ->dehydrated(true)
                                                    ->maxLength(255),

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
                                    ]),

                                Section::make('Address Information')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('address_1')
                                                    ->label('Address 1')
                                                    ->required()
                                                    ->default(function ($record) {
                                                        // First try eInvoiceDetail, then companyDetail
                                                        return $record->eInvoiceDetail->address_1 ??
                                                            $record->companyDetail->company_address1 ?? '';
                                                    })
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
                                                    ->maxLength(255),

                                                TextInput::make('address_2')
                                                    ->label('Address 2')
                                                    ->default(function ($record) {
                                                        // First try eInvoiceDetail, then companyDetail
                                                        return $record->eInvoiceDetail->address_2 ??
                                                            $record->companyDetail->company_address2 ?? '';
                                                    })
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
                                                    ->maxLength(255),

                                                TextInput::make('postcode')
                                                    ->label('Postcode')
                                                    ->required()
                                                    ->extraAlpineAttributes([
                                                        'x-on:input' => '
                                                            $el.value = $el.value.replace(/[^0-9]/g, "");
                                                        '
                                                    ])
                                                    ->rules(['regex:/^[0-9]+$/'])
                                                    ->default(function ($record) {
                                                        // First try eInvoiceDetail, then companyDetail
                                                        return $record->eInvoiceDetail->postcode ??
                                                            $record->companyDetail->postcode ?? '';
                                                    })
                                                    ->maxLength(5),

                                                TextInput::make('city')
                                                    ->label('City')
                                                    ->required()
                                                    ->default(function ($record) {
                                                        // First try eInvoiceDetail, then companyDetail, then lead city
                                                        return $record->eInvoiceDetail->city ??
                                                            $record->companyDetail->city ??
                                                            $record->city ?? '';
                                                    })
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
                                                    ->maxLength(255),

                                                Select::make('state')
                                                    ->label('State')
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->default(function ($get, $record) {
                                                        // Check country from form state first
                                                        $country = $get('country');

                                                        // If no country in form state, check record
                                                        if (!$country) {
                                                            if ($record && $record->eInvoiceDetail && $record->eInvoiceDetail->country) {
                                                                $country = self::getCountryCode($record->eInvoiceDetail->country);
                                                            } elseif ($record && $record->country) {
                                                                $country = self::getCountryCode($record->country);
                                                            } else {
                                                                $country = 'MYS'; // Default fallback
                                                            }
                                                        }

                                                        // Set default based on country
                                                        if ($country === 'MYS') {
                                                            return '10'; // Selangor code
                                                        } else {
                                                            return '17'; // Not Applicable code
                                                        }
                                                    })
                                                    ->options(function () {
                                                        $filePath = storage_path('app/public/json/StateCodes.json');

                                                        if (file_exists($filePath)) {
                                                            $statesContent = file_get_contents($filePath);
                                                            $states = json_decode($statesContent, true);

                                                            return collect($states)->mapWithKeys(function ($state) {
                                                                return [$state['Code'] => ucfirst(strtolower($state['State']))];
                                                            })->toArray();
                                                        }

                                                        return [];
                                                    })
                                                    ->afterStateHydrated(function ($state, $get, $set) {
                                                        // Auto-adjust state based on country
                                                        $country = $get('country');
                                                        if ($country && $country !== 'MYS' && $state !== '17') {
                                                            $set('state', '17'); // Set to "Not Applicable" for non-Malaysia countries
                                                        } elseif ($country === 'MYS' && $state === '17') {
                                                            $set('state', '10'); // Set to Selangor for Malaysia if currently "Not Applicable"
                                                        }
                                                    })
                                                    ->dehydrateStateUsing(function ($state) {
                                                        // Convert the selected code to the full state name
                                                        $filePath = storage_path('app/public/json/StateCodes.json');

                                                        if (file_exists($filePath)) {
                                                            $statesContent = file_get_contents($filePath);
                                                            $states = json_decode($statesContent, true);

                                                            foreach ($states as $stateData) {
                                                                if ($stateData['Code'] === $state) {
                                                                    return ucfirst(strtolower($stateData['State'])); // Store the full state name
                                                                }
                                                            }
                                                        }

                                                        return $state; // Fallback to the original state if mapping fails
                                                    }),

                                                Select::make('country')
                                                    ->label('Country')
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->live()
                                                    ->default(function ($record) {
                                                        // First check if e-invoice detail already has country data
                                                        if ($record && $record->eInvoiceDetail && $record->eInvoiceDetail->country) {
                                                            return self::getCountryCode($record->eInvoiceDetail->country);
                                                        }

                                                        // Then check the lead's country field
                                                        if ($record && $record->country) {
                                                            return self::getCountryCode($record->country);
                                                        }

                                                        // Final fallback to Malaysia
                                                        return 'MYS';
                                                    })
                                                    ->afterStateUpdated(function ($state, $set) {
                                                        // Auto-update currency, business type, and state based on country
                                                        if ($state === 'MYS') {
                                                            $set('currency', 'MYR');
                                                            $set('business_type', 'local_business');
                                                            $set('state', '10'); // Default to Selangor for Malaysia
                                                        } else {
                                                            $set('currency', 'USD');
                                                            $set('business_type', 'foreign_business');
                                                            $set('state', '17'); // Set to "Not Applicable" for non-Malaysia countries
                                                        }
                                                    })
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
                                            ]),
                                    ]),

                                Section::make('Business Information')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Select::make('currency')
                                                    ->label('Currency')
                                                    ->options(EInvoiceDetail::getCurrencyOptions())
                                                    ->disabled()
                                                    ->dehydrated(true)
                                                    ->default(function ($get, $record) {
                                                        // First try to get from form state (when country changes)
                                                        $country = $get('country');
                                                        if ($country) {
                                                            return $country === 'MYS' ? 'MYR' : 'USD';
                                                        }

                                                        // Then try to get from existing e-invoice record
                                                        if ($record && $record->eInvoiceDetail && $record->eInvoiceDetail->currency) {
                                                            return $record->eInvoiceDetail->currency;
                                                        }

                                                        // Check lead's country field and determine currency
                                                        if ($record && $record->country) {
                                                            $leadCountryCode = self::getCountryCode($record->country);
                                                            return $leadCountryCode === 'MYS' ? 'MYR' : 'USD';
                                                        }

                                                        // Final fallback
                                                        return 'MYR';
                                                    })
                                                    ->live() // Make it reactive
                                                    ->afterStateHydrated(function ($state, $get, $set) {
                                                        // Auto-set currency when country changes
                                                        $country = $get('country');
                                                        if ($country === 'MYS' && $state !== 'MYR') {
                                                            $set('currency', 'MYR');
                                                        } elseif ($country !== 'MYS' && $state !== 'USD') {
                                                            $set('currency', 'USD');
                                                        }
                                                    })
                                                    ->required(),

                                                Select::make('business_type')
                                                    ->label('Business Type')
                                                    ->options(EInvoiceDetail::getBusinessTypeOptions())
                                                    ->disabled()
                                                    ->dehydrated(true)
                                                    ->default(function ($get, $record) {
                                                        // First try to get from form state (when country changes)
                                                        $country = $get('country');
                                                        if ($country) {
                                                            return $country === 'MYS' ? 'local_business' : 'foreign_business';
                                                        }

                                                        // Then try to get from existing e-invoice record
                                                        if ($record && $record->eInvoiceDetail && $record->eInvoiceDetail->business_type) {
                                                            return $record->eInvoiceDetail->business_type;
                                                        }

                                                        // Check lead's country field and determine business type
                                                        if ($record && $record->country) {
                                                            $leadCountryCode = self::getCountryCode($record->country);
                                                            return $leadCountryCode === 'MYS' ? 'local_business' : 'foreign_business';
                                                        }

                                                        // Final fallback
                                                        return 'local_business';
                                                    })
                                                    ->live() // Make it reactive
                                                    ->afterStateHydrated(function ($state, $get, $set) {
                                                        // Auto-set business type when country changes
                                                        $country = $get('country');
                                                        if ($country === 'MYS' && $state !== 'local_business') {
                                                            $set('business_type', 'local_business');
                                                        } elseif ($country !== 'MYS' && $state !== 'foreign_business') {
                                                            $set('business_type', 'foreign_business');
                                                        }
                                                    })
                                                    ->required(),

                                                Select::make('business_category')
                                                    ->label('Business Category')
                                                    ->options(EInvoiceDetail::getBusinessCategoryOptions())
                                                    ->default('business')
                                                    ->required(),

                                                Select::make('billing_category')
                                                    ->label('Billing Category')
                                                    ->options(EInvoiceDetail::getBillingCategoryOptions())
                                                    ->default('billing_to_subscriber')
                                                    ->required(),
                                            ]),
                                    ]),
                            ])
                            ->fillForm(function ($record) {
                                // Pre-fill the form with existing e-invoice data
                                $eInvoiceDetail = $record->eInvoiceDetail;
                                $companyDetail = $record->companyDetail;

                                if ($eInvoiceDetail) {
                                    $data = $eInvoiceDetail->toArray();

                                    // Convert state and country names back to codes for the form
                                    if (!empty($data['country'])) {
                                        $data['country'] = self::getCountryCode($data['country']);
                                    }

                                    if (!empty($data['state'])) {
                                        $data['state'] = self::getStateCode($data['state']);
                                    }

                                    // Fill missing data from companyDetail if available
                                    if ($companyDetail) {
                                        $data['company_name'] = $data['company_name'] ?? $companyDetail->company_name ?? $record->name ?? '';
                                        $data['business_register_number'] = $data['business_register_number'] ?? $companyDetail->reg_no_new ?? '';
                                        $data['address_1'] = $data['address_1'] ?? $companyDetail->company_address1 ?? '';
                                        $data['address_2'] = $data['address_2'] ?? $companyDetail->company_address2 ?? '';
                                        $data['postcode'] = $data['postcode'] ?? $companyDetail->postcode ?? '';
                                        $data['city'] = $data['city'] ?? $companyDetail->city ?? $record->city ?? '';
                                    }

                                    return $data;
                                }

                                // If no eInvoiceDetail exists, use companyDetail as primary source
                                $data = [];

                                if ($companyDetail) {
                                    $data['company_name'] = $companyDetail->company_name ?? $record->name ?? '';
                                    $data['business_register_number'] = $companyDetail->reg_no_new ?? '';
                                    $data['address_1'] = $companyDetail->company_address1 ?? '';
                                    $data['address_2'] = $companyDetail->company_address2 ?? '';
                                    $data['postcode'] = $companyDetail->postcode ?? '';
                                    $data['city'] = $companyDetail->city ?? $record->city ?? '';
                                    $data['state'] = $companyDetail->state ? self::getStateCode($companyDetail->state) : '10';
                                } else {
                                    // Fallback to lead data if no companyDetail
                                    $data['company_name'] = $record->name ?? '';
                                    $data['city'] = $record->city ?? '';
                                }

                                // Determine defaults based on lead's country
                                $leadCountryCode = $record->country ? self::getCountryCode($record->country) : 'MYS';
                                $isLocalBusiness = $leadCountryCode === 'MYS';

                                // Set business defaults
                                $data['business_category'] = 'business';
                                $data['currency'] = $isLocalBusiness ? 'MYR' : 'USD';
                                $data['business_type'] = $isLocalBusiness ? 'local_business' : 'foreign_business';
                                $data['state'] = $data['state'] ?? ($isLocalBusiness ? '10' : '17'); // Selangor for Malaysia, Not Applicable for others
                                $data['country'] = $leadCountryCode;
                                $data['billing_category'] = 'billing_to_subscriber';

                                return $data;
                            })
                            ->action(function ($record, array $data) {
                                // Update or create e-invoice details
                                $record->eInvoiceDetail()->updateOrCreate(
                                    ['lead_id' => $record->id],
                                    $data
                                );

                                // Update CompanyDetail if it exists and has the corresponding fields
                                $companyDetail = $record->companyDetail;
                                if ($companyDetail) {
                                    $companyUpdateData = [];

                                    // Map e-invoice fields to company detail fields if they exist in the fillable array
                                    $fillableFields = $companyDetail->getFillable();

                                    // Check if company detail has 'company_name' field and update it
                                    if (in_array('company_name', $fillableFields) && !empty($data['company_name'])) {
                                        $companyUpdateData['company_name'] = $data['company_name'];
                                    }

                                    // Check if company detail has 'company_address1' field and update it
                                    if (in_array('company_address1', $fillableFields) && !empty($data['address_1'])) {
                                        $companyUpdateData['company_address1'] = $data['address_1'];
                                    }

                                    // Check if company detail has 'company_address2' field and update it
                                    if (in_array('company_address2', $fillableFields) && !empty($data['address_2'])) {
                                        $companyUpdateData['company_address2'] = $data['address_2'];
                                    }

                                    // Check if company detail has 'postcode' field and update it
                                    if (in_array('postcode', $fillableFields) && !empty($data['postcode'])) {
                                        $companyUpdateData['postcode'] = $data['postcode'];
                                    }

                                    // Check if company detail has 'state' field and update it
                                    if (in_array('state', $fillableFields) && !empty($data['state'])) {
                                        $companyUpdateData['state'] = $data['state'];
                                    }

                                    // Check if company detail has 'reg_no_new' field and update it
                                    if (in_array('reg_no_new', $fillableFields) && !empty($data['business_register_number'])) {
                                        $companyUpdateData['reg_no_new'] = $data['business_register_number'];
                                    }

                                    // Only update if there's data to update
                                    if (!empty($companyUpdateData)) {
                                        $companyDetail->update($companyUpdateData);

                                        // Log activity for company detail update
                                        activity()
                                            ->causedBy(auth()->user())
                                            ->performedOn($companyDetail)
                                            ->log('Updated company detail from e-invoice details');
                                    }
                                } else {
                                    // Create CompanyDetail if it doesn't exist
                                    $companyCreateData = [
                                        'lead_id' => $record->id,
                                        'company_name' => $data['company_name'] ?? null,
                                        'company_address1' => $data['address_1'] ?? null,
                                        'company_address2' => $data['address_2'] ?? null,
                                        'postcode' => $data['postcode'] ?? null,
                                        'state' => $data['state'] ?? null,
                                        'reg_no_new' => $data['business_register_number'] ?? null,
                                    ];

                                    // Remove null values
                                    $companyCreateData = array_filter($companyCreateData, function($value) {
                                        return !is_null($value) && $value !== '';
                                    });
                                }

                                // Log activity for e-invoice update
                                activity()
                                    ->causedBy(auth()->user())
                                    ->performedOn($record)
                                    ->log('Updated e-invoice details');

                                Notification::make()
                                    ->title('E-Invoice details updated successfully')
                                    ->body('E-Invoice and Company details have been updated.')
                                    ->success()
                                    ->send();
                            }),
                    ])
                    ->columnSpan(2)
                    ->schema([
                        // Display e-invoice details using the custom blade component
                        View::make('components.einvoice-details-card'),
                    ]),
                Section::make('HR Details')
                    ->headerActions([
                        Action::make('edit_person_in_charge')
                            ->label('Edit') // Button label
                            ->icon('heroicon-o-pencil')
                            ->visible(fn (Lead $lead) =>
                                // First check if user role is not 4 or 5
                                in_array(auth()->user()->role_id, [1, 2, 3]) &&

                                // If user is role 2 (salesperson), they can only edit their own leads
                                (auth()->user()->role_id != 2 || (auth()->user()->role_id == 2 && $lead->salesperson == auth()->user()->id)) &&

                                // Then check if lead owner exists or salesperson exists
                                (!is_null($lead->lead_owner) || (is_null($lead->lead_owner) && !is_null($lead->salesperson)))
                            )
                            ->modalHeading('Edit HR Details') // Modal heading
                            ->modalSubmitActionLabel('Save Changes') // Modal button text
                            ->form([ // Define the form fields to show in the modal
                                TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->default(fn ($record) => $record->companyDetail->name ?? $record->name)
                                    ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('name', strtoupper($state))),
                                TextInput::make('email')
                                    ->label('Email')
                                    ->required()
                                    ->default(fn ($record) => $record->companyDetail->email ?? $record->email),
                                TextInput::make('contact_no')
                                    ->label('Contact No.')
                                    ->required()
                                    ->default(fn ($record) => $record->companyDetail->contact_no ?? $record->phone),
                                TextInput::make('position')
                                    ->label('Position')
                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                    ->afterStateHydrated(fn($state) => Str::upper($state))
                                    ->afterStateUpdated(fn($state) => Str::upper($state))
                                    ->required()
                                    ->default(fn ($record) => $record->companyDetail->position ?? '-'),
                            ])
                            ->action(function (Lead $lead, array $data) {
                                $record = $lead->companyDetail;
                                if ($record) {
                                    // Update the existing record
                                    $record->update($data);

                                    Notification::make()
                                        ->title('Updated Successfully')
                                        ->success()
                                        ->send();
                                } else {
                                    // Create a new record via the relation
                                    $lead->companyDetail()->create($data);

                                    Notification::make()
                                        ->title('Created Successfully')
                                        ->success()
                                        ->send();
                                }
                            }),
                    ])
                    ->columnSpan(1)
                    ->schema([
                        View::make('components.hr-details'),
                    ]),
                Section::make('Finance Details')
                    ->headerActions([
                        Action::make('edit_finance_details')
                            ->label('Edit') // Button label
                            ->icon('heroicon-o-pencil')
                            ->visible(fn (Lead $lead) =>
                                // First check if user role is not 4 or 5
                                in_array(auth()->user()->role_id, [1, 2, 3]) &&

                                // If user is role 2 (salesperson), they can only edit their own leads
                                (auth()->user()->role_id != 2 || (auth()->user()->role_id == 2 && $lead->salesperson == auth()->user()->id)) &&

                                // Then check if lead owner exists or salesperson exists
                                (!is_null($lead->lead_owner) || (is_null($lead->lead_owner) && !is_null($lead->salesperson)))
                            )
                            ->modalHeading('Edit Finance Details') // Modal heading
                            ->modalSubmitActionLabel('Save Changes') // Modal button text
                            ->form([ // Define the form fields to show in the modal
                                TextInput::make('finance_person_name')
                                    ->label('Finance Person Name')
                                    ->required()
                                    ->default(fn ($record) => $record->eInvoiceDetail->finance_person_name ?? '-')
                                    ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('finance_person_name', strtoupper($state))),
                                TextInput::make('finance_person_email')
                                    ->label('Finance Person Email')
                                    ->email()
                                    ->required()
                                    ->default(fn ($record) => $record->eInvoiceDetail->finance_person_email ?? ''),
                                TextInput::make('finance_person_contact')
                                    ->label('Finance Person Contact')
                                    ->required()
                                    ->default(fn ($record) => $record->eInvoiceDetail->finance_person_contact ?? ''),
                                TextInput::make('finance_person_position')
                                    ->label('Finance Person Position')
                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                    ->afterStateHydrated(fn($state) => Str::upper($state))
                                    ->afterStateUpdated(fn($state) => Str::upper($state))
                                    ->required()
                                    ->default(fn ($record) => $record->eInvoiceDetail->finance_person_position ?? '-'),
                            ])
                            ->action(function (Lead $lead, array $data) {
                                // Update or create e-invoice details with finance information
                                $lead->eInvoiceDetail()->updateOrCreate(
                                    ['lead_id' => $lead->id],
                                    $data
                                );

                                Notification::make()
                                    ->title('Finance Details Updated Successfully')
                                    ->success()
                                    ->send();
                            }),
                    ])
                    ->columnSpan(1)
                    ->schema([
                        View::make('components.finance-details'),
                    ]),
            ])
            ->columns(4)
            ->columnSpanFull(),
        ];
    }

    /**
     * Helper method to get state code from state name
     */
    private static function getStateCode($stateName)
    {
        $filePath = storage_path('app/public/json/StateCodes.json');

        if (file_exists($filePath)) {
            $statesContent = file_get_contents($filePath);
            $states = json_decode($statesContent, true);

            foreach ($states as $state) {
                if (strtolower($state['State']) === strtolower($stateName)) {
                    return $state['Code'];
                }
            }
        }

        return '10'; // Default to Selangor code
    }

    /**
     * Helper method to get country code from country name
     */
    private static function getCountryCode($countryName)
    {
        $filePath = storage_path('app/public/json/CountryCodes.json');

        if (file_exists($filePath)) {
            $countriesContent = file_get_contents($filePath);
            $countries = json_decode($countriesContent, true);

            foreach ($countries as $country) {
                if (strtolower($country['Country']) === strtolower($countryName)) {
                    return $country['Code'];
                }
            }
        }

        return 'MYS'; // Default fallback
    }
}
