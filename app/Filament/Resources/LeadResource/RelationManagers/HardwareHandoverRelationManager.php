<?php
namespace App\Filament\Resources\LeadResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use App\Http\Controllers\GenerateHardwareHandoverPdfController;
use App\Models\HardwareHandover;
use App\Models\Industry;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;

class HardwareHandoverRelationManager extends RelationManager
{
    protected static string $relationship = 'hardwareHandover'; // Define the relationship name in the Lead model

    // use InteractsWithTable;
    // use InteractsWithForms;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->user_id === auth()->id();
    }

    public function headerActions(): array
    {
        $isEInvoiceIncomplete = $this->isEInvoiceDetailsIncomplete();

        return [
            // Action 1: Warning notification when e-invoice is incomplete
            // Tables\Actions\Action::make('EInvoiceWarning')
            //     ->label('Add Hardware Handover')
            //     ->icon('heroicon-o-pencil')
            //     ->color('gray')
            //     ->visible(fn () => $isEInvoiceIncomplete)
            //     ->action(function () {
            //         Notification::make()
            //             ->warning()
            //             ->title('Action Required')
            //             ->body('Please collect all e-invoices information before proceeding with the handover process.')
            //             ->persistent()
            //             ->actions([
            //                 \Filament\Notifications\Actions\Action::make('copyEInvoiceLink')
            //                     ->label('Copy E-Invoice Link')
            //                     ->button()
            //                     ->color('primary')
            //                     // ->url(route('filament.admin.resources.leads.edit', [
            //                     //     'record' => Encryptor::encrypt($this->getOwnerRecord()->id),
            //                     //     'activeTab' => 'einvoice'
            //                     // ]), true)
            //                     // ->openUrlInNewTab()
            //                     ->close(),
            //                 \Filament\Notifications\Actions\Action::make('cancel')
            //                     ->label('Cancel')
            //                     ->close(),
            //             ])
            //             ->send();
            //     }),

            // Action 2: Actual form when e-invoice is complete
            Tables\Actions\Action::make('AddHardwareHandover')
                ->label('Add Hardware Handover')
                ->icon('heroicon-o-pencil')
                ->color('primary')
                // ->visible(fn () => !$isEInvoiceIncomplete)
                ->slideOver()
                ->modalSubmitActionLabel('Save')
                ->modalHeading('Add Hardware Handover')
                ->modalWidth(MaxWidth::SevenExtraLarge)
                ->form([
                    Section::make('Section 1: Company Details')
                        ->description('Add contact name and email information, for admin to use')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('company_name')
                                        ->label('Company Name')
                                        ->default(fn () => $this->getOwnerRecord()->companyDetail->company_name ?? null),
                                    TextInput::make('industry')
                                        ->label('Industry')
                                        ->default(fn () => $this->getOwnerRecord()->companyDetail->industry ?? null),
                                ]),
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('headcount')
                                        ->label('Headcount')
                                        ->default(fn () => $this->getOwnerRecord()->company_size ?? null),
                                    Select::make('country')
                                        ->label('Country')
                                        ->default(fn () => $this->getOwnerRecord()->country ?? null)
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
                                                        return ucfirst(strtolower($country['Country']));
                                                    }
                                                }
                                            }

                                            return $state; // Fallback to the original state if mapping fails
                                        })
                                        ->searchable()
                                        ->preload(),
                                ]),
                            Grid::make(2)
                                ->schema([
                                    Select::make('state')
                                        ->label('State')
                                        ->default(fn () => $this->getOwnerRecord()->state ?? $this->getOwnerRecord()->companyDetail->state ?? null)
                                        ->options(function () {
                                            $filePath = storage_path('app/public/json/StateCodes.json');

                                            if (file_exists($filePath)) {
                                                $countriesContent = file_get_contents($filePath);
                                                $countries = json_decode($countriesContent, true);

                                                // Map 3-letter country codes to full country names
                                                return collect($countries)->mapWithKeys(function ($country) {
                                                    return [$country['Code'] => ucfirst(strtolower($country['State']))];
                                                })->toArray();
                                            }

                                            return [];
                                        })
                                        ->dehydrateStateUsing(function ($state) {
                                            // Convert the selected code to the full country name
                                            $filePath = storage_path('app/public/json/StateCodes.json');

                                            if (file_exists($filePath)) {
                                                $countriesContent = file_get_contents($filePath);
                                                $countries = json_decode($countriesContent, true);

                                                foreach ($countries as $country) {
                                                    if ($country['Code'] === $state) {
                                                        return ucfirst(strtolower($country['State']));
                                                    }
                                                }
                                            }

                                            return $state; // Fallback to the original state if mapping fails
                                        })
                                        ->searchable()
                                        ->preload(),
                                    TextInput::make('salesperson')
                                        ->label('Salesperson')
                                        ->default(fn () => $this->getOwnerRecord()->salesperson ? User::find($this->getOwnerRecord()->salesperson)->name : null),
                                ]),
                        ]),

                    Section::make('Section 2: Superadmin Details')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('pic_name')
                                        ->label('PIC Name')
                                        ->default(fn () => $this->getOwnerRecord()->companyDetail->name ?? $this->getOwnerRecord()->name),
                                    TextInput::make('pic_phone')
                                        ->label('PIC HP No.')
                                        ->default(fn () => $this->getOwnerRecord()->companyDetail->contact_no ?? $this->getOwnerRecord()->phone),
                                ]),
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('email')
                                        ->label('Email Address')
                                        ->email()
                                        ->default(fn () => $this->getOwnerRecord()->companyDetail->email ?? $this->getOwnerRecord()->email),
                                    TextInput::make('password')
                                        ->label('Password')
                                        ->password(),
                                ]),
                        ]),

                    Section::make('Section 3: Invoice Details')
                        ->description('Add all required billing information, for admin to use')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('company_name_invoice')
                                        ->label('Company Name (Invoice)')
                                        ->default(fn () => $this->getOwnerRecord()->companyDetail->company_name ?? null),
                                    TextInput::make('company_address')
                                        ->label('Company Address')
                                        ->default(function () {
                                            $record = $this->getOwnerRecord();
                                            $companyDetail = $record->companyDetail ?? null;

                                            if (!$companyDetail) {
                                                return null;
                                            }

                                            $address = [];

                                            if (!empty($companyDetail->company_address1)) {
                                                $address[] = $companyDetail->company_address1;
                                            }

                                            if (!empty($companyDetail->company_address2)) {
                                                $address[] = $companyDetail->company_address2;
                                            }

                                            if (!empty($companyDetail->postcode)) {
                                                $address[] = $companyDetail->postcode;
                                            }

                                            return !empty($address) ? implode(', ', $address) : null;
                                        }),
                                ]),
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('salesperson_invoice')
                                        ->label('Salesperson (Invoice)')
                                        ->default(fn () => $this->getOwnerRecord()->salesperson ? User::find($this->getOwnerRecord()->salesperson)->name : null),
                                    TextInput::make('pic_name_invoice')
                                        ->label('PIC Name (Invoice)')
                                        ->default(fn () => $this->getOwnerRecord()->companyDetail->name ?? $this->getOwnerRecord()->name),
                                ]),
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('pic_email_invoice')
                                        ->label('PIC Email (Invoice)')
                                        ->default(fn () => $this->getOwnerRecord()->companyDetail->email ?? $this->getOwnerRecord()->email),
                                    TextInput::make('pic_phone_invoice')
                                        ->label('PIC HP No. (Invoice)')
                                        ->default(fn () => $this->getOwnerRecord()->companyDetail->contact_no ?? $this->getOwnerRecord()->phone),
                                ]),
                        ]),

                    Section::make('Section 4: Implementation PICs')
                        ->schema([
                            Forms\Components\Repeater::make('implementation_pics')
                                ->label('Implementation PICs')
                                ->schema([
                                    TextInput::make('pic_name_impl')
                                        ->label('PIC Name'),
                                    TextInput::make('position')
                                        ->label('Position'),
                                    TextInput::make('pic_phone_impl')
                                        ->label('HP Number'),
                                    TextInput::make('pic_email_impl')
                                        ->label('Email Address')
                                        ->email(),
                                ])
                                ->columns(2),
                        ]),

                    Section::make('Section 5: Module Subscription')
                        ->schema([
                            Forms\Components\Repeater::make('modules')
                                ->label('Modules')
                                ->schema([
                                    Select::make('module_name')
                                        ->label('Module Name')
                                        ->options([
                                            'Attendance' => 'Attendance',
                                            'Leave' => 'Leave',
                                            'Claim' => 'Claim',
                                            'Payroll' => 'Payroll',
                                            'Appraisal' => 'Appraisal',
                                            'Recruitment' => 'Recruitment',
                                            'Power BI' => 'Power BI',
                                        ]),
                                    TextInput::make('headcount')
                                        ->numeric()
                                        ->label('Headcount'),
                                    TextInput::make('subscription_months')
                                        ->numeric()
                                        ->label('Subscription Months'),
                                    Select::make('purchase_type')
                                        ->label('Purchase Type')
                                        ->options(HardwareHandover::getPurchaseTypeOptions()),
                                ])
                                ->columns(4),
                        ]),

                    Section::make('Section 6: Other Details')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Textarea::make('customization_details')
                                        ->label('Customization Details'),
                                    Textarea::make('enhancement_details')
                                        ->label('Enhancement Details'),
                                ]),
                            Grid::make(2)
                                ->schema([
                                    Textarea::make('special_remark')
                                        ->label('Special Remark'),
                                    Textarea::make('device_integration')
                                        ->label('Device Integration'),
                                ]),
                            Grid::make(2)
                                ->schema([
                                    Textarea::make('existing_hr_system')
                                        ->label('Existing HR System'),
                                    Textarea::make('experience_implementing_hr_system')
                                        ->label('Experience Implementing Any HR System'),
                                ]),
                            Grid::make(2)
                                ->schema([
                                    Textarea::make('vip_package')
                                        ->label('VIP Package'),
                                    Textarea::make('fingertec_device')
                                        ->label('FingerTec Device'),
                                ]),
                        ]),

                    Section::make('Section 7: Onsite Package')
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    Checkbox::make('onsite_kick_off_meeting')
                                        ->label('Onsite Kick Off Meeting'),
                                    Checkbox::make('onsite_webinar_training')
                                        ->label('Onsite Webinar Training'),
                                    Checkbox::make('onsite_briefing')
                                        ->label('Onsite Briefing'),
                                ]),
                        ]),
                    Grid::make(3)
                        ->schema([
                            Section::make('Section 8: Payment Terms')
                                ->columnSpan(1) // Ensure it spans one column
                                ->schema([
                                    Forms\Components\Radio::make('payment_term') // Change Select to Radio
                                        ->label('Select Payment Terms')
                                        ->options([
                                            'full_payment' => 'Full Payment',
                                            'payment_via_hrdf' => 'Payment via HRDF',
                                            'payment_via_term' => 'Payment via Term',
                                        ])
                                        ->reactive(), // Make it reactive to trigger changes
                                ]),

                            Section::make('Section 9: Proforma Invoices')
                                ->columnSpan(1) // Ensure it spans one column
                                ->schema([
                                    Select::make('proforma_invoice_number')
                                        ->label('Proforma Invoice Number')
                                        ->options(function (RelationManager $livewire) {
                                            $leadId = $livewire->getOwnerRecord()->id;
                                            return \App\Models\Quotation::where('lead_id', $leadId)
                                                ->where('status', \App\Enums\QuotationStatusEnum::accepted)
                                                ->pluck('pi_reference_no', 'id')
                                                ->toArray();
                                        })
                                        ->multiple()
                                        ->searchable()
                                        ->preload(),
                                ]),

                            Section::make('Section 10: Attachments')
                                ->columnSpan(1) // Ensure it spans one column
                                ->schema([
                                    Grid::make(1)
                                        ->schema([
                                        FileUpload::make('confirmation_order_file')
                                            ->label('Upload Confirmation Order')
                                            ->disk('public')
                                            ->directory('handovers/confirmation_orders')
                                            ->visibility('public')
                                            ->multiple()
                                            ->maxFiles(3)
                                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png']),

                                        FileUpload::make('payment_slip_file')
                                            ->label(fn (callable $get) => $get('payment_term') === 'payment_via_hrdf' ? 'Upload HRDF Approval Letter' : 'Upload Payment Slip')
                                            ->disk('public')
                                            ->live(debounce:500)
                                            ->directory('handovers/payment_slips')
                                            ->visibility('public')
                                            ->multiple()
                                            ->maxFiles(3)
                                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                            ->helperText(fn (callable $get) => $get('payment_term') === 'payment_via_hrdf' ? 'Only PDF, JPEG, or PNG format, max 10MB (HRDF Approval Letter)' : 'Only PDF, JPEG, or PNG format, max 10MB'),
                                        ])
                                ]),
                    ]),
                    Section::make('Section 11: Installation Details')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Textarea::make('installation_special_remark')
                                        ->label('Installation Special Remark'),
                                    FileUpload::make('installation_media')
                                        ->label('Photo/ Video')
                                        ->disk('public')
                                        ->directory('handovers/installation_media') // Changed from confirmation_orders to installation_media
                                        ->visibility('public')
                                        ->multiple()
                                        ->maxFiles(3)
                                        ->acceptedFileTypes([
                                            'application/pdf',
                                            'image/jpeg',
                                            'image/png',
                                            'video/mp4',
                                            'video/quicktime',
                                            'video/x-msvideo',
                                            'video/webm'
                                        ])
                                        ->helperText('Upload photos or videos - PDF, JPEG, PNG, MP4, MOV, AVI, or WebM format')
                                ]),
                        ]),
                    Forms\Components\Radio::make('save_type')
                        ->label('Save As')
                        ->options([
                            'new' => 'Save',
                            'draft' => 'Save as Draft',
                        ])
                        ->default('new')
                        ->inline()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $data['created_by'] = auth()->id();
                    $data['lead_id'] = $this->getOwnerRecord()->id;
                    $data['status'] = 'Draft';

                    // Handle file array encodings
                    if (isset($data['confirmation_order_file']) && is_array($data['confirmation_order_file'])) {
                        $data['confirmation_order_file'] = json_encode($data['confirmation_order_file']);
                    }

                    if (isset($data['payment_slip_file']) && is_array($data['payment_slip_file'])) {
                        $data['payment_slip_file'] = json_encode($data['payment_slip_file']);
                    }

                    if (isset($data['installation_media']) && is_array($data['installation_media'])) {
                        $data['installation_media'] = json_encode($data['installation_media']);
                    }

                    if (isset($data['proforma_invoice_number']) && is_array($data['proforma_invoice_number'])) {
                        $data['proforma_invoice_number'] = json_encode($data['proforma_invoice_number']);
                    }

                    unset($data['save_type']); // Clean up before saving

                    // Create the handover record
                    $handover = HardwareHandover::create($data);

                    // Generate PDF for non-draft handovers
                    if ($handover->status !== 'Draft') {
                        // Use the controller for PDF generation
                        app(GenerateHardwareHandoverPdfController::class)->generateInBackground($handover);
                    }

                    Notification::make()
                        ->title($handover->status === 'Draft' ? 'Saved as Draft' : 'Hardware Handover Created Successfully')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->headerActions($this->headerActions())
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->openUrlInNewTab(),
                TextColumn::make('created_at')
                    ->label('DATE')
                    ->date('d M Y'),
                TextColumn::make('training_type')
                    ->label('TRAINING TYPE')
                    ->formatStateUsing(fn (string $state): string => Str::title(str_replace('_', ' ', $state))),
                TextColumn::make('value')
                    ->label('VALUE')
                    ->formatStateUsing(fn ($state) => 'MYR ' . number_format($state, 2)),
                TextColumn::make('status')
                    ->label('STATUS')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'Draft' => new HtmlString('<span style="color: orange;">Draft</span>'),
                        'New' => new HtmlString('<span style="color: green;">New</span>'),
                        'Approved' => new HtmlString('<span style="color: green;">Approved</span>'),
                        'Rejected' => new HtmlString('<span style="color: red;">Rejected</span>'),
                        default => new HtmlString('<span>' . ucfirst($state) . '</span>'),
                    }),
            ])
            ->filtersFormColumns(6)
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading(' ')
                        ->modalWidth('md')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        // Use a callback function instead of arrow function for more control
                        ->modalContent(function (HardwareHandover $record): View {

                            // Return the view with the record using $this->record pattern
                            return view('components.hardware-handover')
                            ->with('extraAttributes', ['record' => $record]);
                        }),

                    Action::make('edit_Hardware_handover')
                        ->label('Edit Hardware Handover')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->modalSubmitActionLabel('Save')
                        ->visible(fn (HardwareHandover $record): bool => in_array($record->status, ['New', 'Draft']))
                        ->modalWidth(MaxWidth::SevenExtraLarge)
                        ->slideOver()
                        ->form([
                            Section::make('Section 1: Company Details')
                                ->description('Add contact name and email information, for admin to use')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('company_name')
                                                ->label('Company Name')
                                                ->default(function (HardwareHandover $record) {
                                                    // If we have a record (editing), use it
                                                    if ($record && $record->company_name) {
                                                        return $record->company_name;
                                                    }
                                                    // Otherwise fall back to owner record
                                                    return $this->getOwnerRecord()->companyDetail->company_name ?? null;
                                                }),
                                            Select::make('industry')
                                                ->label('Industry')
                                                ->placeholder('Select an industry')
                                                ->default(function (HardwareHandover $record) {
                                                    // If we have a record (editing), use it
                                                    if ($record && $record->industry) {
                                                        return $record->industry;
                                                    }
                                                    // Otherwise fall back to owner record
                                                    return $this->getOwnerRecord()->companyDetail->industry ?? null;
                                                })
                                                ->options(fn () => collect(['None' => 'None'])->merge(Industry::pluck('name', 'name')))
                                                ->searchable()
                                                ->required()
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('headcount')
                                                ->label('Headcount')
                                                ->default(function (HardwareHandover $record) {
                                                    if ($record && $record->headcount) {
                                                        return $record->headcount;
                                                    }
                                                    return $this->getOwnerRecord()->companyDetail->company_size ?? null;
                                                }),
                                            Select::make('country')
                                                ->label('Country')
                                                ->default(function (HardwareHandover $record) {
                                                    // If we have a record (editing), use it
                                                    if ($record && $record->country) {
                                                        return $record->country;
                                                    }
                                                    // Otherwise fall back to owner record
                                                    return $this->getOwnerRecord()->companyDetail->country ?? null;
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
                                                                return ucfirst(strtolower($country['Country']));
                                                            }
                                                        }
                                                    }

                                                    return $state; // Fallback to the original state if mapping fails
                                                })
                                                ->searchable()
                                                ->preload(),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            Select::make('state')
                                                ->label('State')
                                                ->default(function (HardwareHandover $record) {
                                                    // If we have a record (editing), use it
                                                    if ($record && $record->state) {
                                                        return $record->state;
                                                    }
                                                    // Otherwise fall back to owner record
                                                    return $this->getOwnerRecord()->companyDetail->state ?? null;
                                                })
                                                ->options(function () {
                                                    $filePath = storage_path('app/public/json/StateCodes.json');

                                                    if (file_exists($filePath)) {
                                                        $countriesContent = file_get_contents($filePath);
                                                        $countries = json_decode($countriesContent, true);

                                                        // Map 3-letter country codes to full country names
                                                        return collect($countries)->mapWithKeys(function ($country) {
                                                            return [$country['Code'] => ucfirst(strtolower($country['State']))];
                                                        })->toArray();
                                                    }

                                                    return [];
                                                })
                                                ->dehydrateStateUsing(function ($state) {
                                                    // Convert the selected code to the full country name
                                                    $filePath = storage_path('app/public/json/StateCodes.json');

                                                    if (file_exists($filePath)) {
                                                        $countriesContent = file_get_contents($filePath);
                                                        $countries = json_decode($countriesContent, true);

                                                        foreach ($countries as $country) {
                                                            if ($country['Code'] === $state) {
                                                                return ucfirst(strtolower($country['State']));
                                                            }
                                                        }
                                                    }

                                                    return $state; // Fallback to the original state if mapping fails
                                                })
                                                ->searchable()
                                                ->preload(),
                                            TextInput::make('salesperson')
                                                ->label('Salesperson')
                                                ->default(function (HardwareHandover $record) {
                                                    // If we have a record (editing), use it
                                                    if ($record && $record->salesperson) {
                                                        return $record->salesperson;
                                                    }
                                                    // Otherwise fall back to owner record
                                                    return $this->getOwnerRecord()->companyDetail->salesperson ?? null;
                                                }),
                                        ]),
                                ]),

                            Section::make('Section 2: Superadmin Details')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('pic_name')
                                                ->label('PIC Name')
                                                ->default(function (HardwareHandover $record) {
                                                    // If we have a record (editing), use it
                                                    if ($record && $record->pic_name) {
                                                        return $record->pic_name;
                                                    }
                                                    // Otherwise fall back to owner record
                                                    return $this->getOwnerRecord()->companyDetail->name ?? $this->getOwnerRecord()->name;
                                                }),
                                            TextInput::make('pic_phone')
                                                ->label('PIC HP No.')
                                                ->default(function (HardwareHandover $record) {
                                                    // If we have a record (editing), use it
                                                    if ($record && $record->pic_phone) {
                                                        return $record->pic_phone;
                                                    }
                                                    // Otherwise fall back to owner record
                                                    return $this->getOwnerRecord()->companyDetail->contact_no ?? $this->getOwnerRecord()->phone;
                                                }),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('email')
                                                ->label('Email Address')
                                                ->email()
                                                ->default(function (HardwareHandover $record) {
                                                    if ($record && $record->email) {
                                                        return $record->email;
                                                    }
                                                    return $this->getOwnerRecord()->companyDetail->email ?? $this->getOwnerRecord()->email;
                                                }),
                                            TextInput::make('password')
                                                ->label('Password')
                                                ->default(function (HardwareHandover $record) {
                                                    // Only include this if you're storing passwords in plaintext,
                                                    // which you generally shouldn't do
                                                    if ($record && $record->password) {
                                                        return $record->password;
                                                    }
                                                    return null;
                                                }),
                                        ]),
                                ]),

                            Section::make('Section 3: Invoice Details')
                                ->description('Add all required billing information, for admin to use')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('company_name_invoice')
                                                ->label('Company Name (Invoice)')
                                                ->default(function (HardwareHandover $record) {
                                                    // If we have a record (editing), use it
                                                    if ($record && $record->company_name_invoice) {
                                                        return $record->company_name_invoice;
                                                    }
                                                    // Otherwise fall back to owner record
                                                    return $this->getOwnerRecord()->companyDetail->company_name ?? null;
                                                }),
                                            TextInput::make('company_address')
                                                ->label('Company Address')
                                                ->default(function (HardwareHandover $record) {
                                                    // If we have a record (editing), use it
                                                    if ($record && $record->company_address) {
                                                        return $record->company_address;
                                                    }

                                                    // Otherwise fall back to owner record
                                                    $record = $this->getOwnerRecord();
                                                    $companyDetail = $record->companyDetail ?? null;

                                                    if (!$companyDetail) {
                                                        return null;
                                                    }

                                                    $address = [];

                                                    if (!empty($companyDetail->company_address1)) {
                                                        $address[] = $companyDetail->company_address1;
                                                    }

                                                    if (!empty($companyDetail->company_address2)) {
                                                        $address[] = $companyDetail->company_address2;
                                                    }

                                                    if (!empty($companyDetail->postcode)) {
                                                        $address[] = $companyDetail->postcode;
                                                    }

                                                    return !empty($address) ? implode(', ', $address) : null;
                                                }),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('salesperson_invoice')
                                                ->label('Salesperson (Invoice)')
                                                ->default(function (HardwareHandover $record) {
                                                    // If we have a record (editing), use it
                                                    if ($record && $record->salesperson_invoice) {
                                                        return $record->salesperson_invoice;
                                                    }
                                                    // Otherwise fall back to owner record
                                                    return $this->getOwnerRecord()->salesperson ? User::find($this->getOwnerRecord()->salesperson)->name : null;
                                                }),
                                            TextInput::make('pic_name_invoice')
                                                ->label('PIC Name (Invoice)')
                                                ->default(function (HardwareHandover $record) {
                                                    // If we have a record (editing), use it
                                                    if ($record && $record->pic_name_invoice) {
                                                        return $record->pic_name_invoice;
                                                    }
                                                    // Otherwise fall back to owner record
                                                    return $this->getOwnerRecord()->companyDetail->name ?? $this->getOwnerRecord()->name;
                                                }),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('pic_email_invoice')
                                                ->label('PIC Email (Invoice)')
                                                ->default(function (HardwareHandover $record) {
                                                    // If we have a record (editing), use it
                                                    if ($record && $record->pic_email_invoice) {
                                                        return $record->pic_email_invoice;
                                                    }
                                                    // Otherwise fall back to owner record
                                                    return $this->getOwnerRecord()->companyDetail->email ?? $this->getOwnerRecord()->email;
                                                }),
                                            TextInput::make('pic_phone_invoice')
                                                ->label('PIC HP No. (Invoice)')
                                                ->default(function (HardwareHandover $record) {
                                                    // If we have a record (editing), use it
                                                    if ($record && $record->pic_phone_invoice) {
                                                        return $record->pic_phone_invoice;
                                                    }
                                                    // Otherwise fall back to owner record
                                                    return $this->getOwnerRecord()->companyDetail->contact_no ?? $this->getOwnerRecord()->phone;
                                                }),
                                        ]),
                                ]),

                            Section::make('Section 4: Implementation PICs')
                                ->schema([
                                    Forms\Components\Repeater::make('implementation_pics')
                                        ->label('Implementation PICs')
                                        ->schema([
                                            TextInput::make('pic_name_impl')
                                                ->label('PIC Name'),
                                            TextInput::make('position')
                                                ->label('Position'),
                                            TextInput::make('pic_phone_impl')
                                                ->label('HP Number'),
                                            TextInput::make('pic_email_impl')
                                                ->label('Email Address')
                                                ->email(),
                                        ])
                                        ->columns(2)
                                        ->defaultItems(1)
                                        ->default(function (HardwareHandover $record) {
                                            if ($record && $record->implementation_pics) {
                                                // If it's a string, decode it
                                                if (is_string($record->implementation_pics)) {
                                                    return json_decode($record->implementation_pics, true);
                                                }
                                                // If it's already an array, return it
                                                if (is_array($record->implementation_pics)) {
                                                    return $record->implementation_pics;
                                                }
                                            }
                                            // Default empty item
                                            return [[
                                                'pic_name_impl' => null,
                                                'position' => null,
                                                'pic_phone_impl' => null,
                                                'pic_email_impl' => null
                                            ]];
                                        }),
                                ]),

                            Section::make('Section 5: Module Subscription')
                                ->schema([
                                    Forms\Components\Repeater::make('modules')
                                        ->label('Modules')
                                        ->schema([
                                            Select::make('module_name')
                                                ->label('Module Name')
                                                ->options([
                                                    'Attendance' => 'Attendance',
                                                    'Leave' => 'Leave',
                                                    'Claim' => 'Claim',
                                                    'Payroll' => 'Payroll',
                                                    'Appraisal' => 'Appraisal',
                                                    'Recruitment' => 'Recruitment',
                                                    'Power BI' => 'Power BI',
                                                ]),
                                            TextInput::make('headcount')
                                                ->numeric()
                                                ->label('Headcount'),
                                            TextInput::make('subscription_months')
                                                ->numeric()
                                                ->label('Subscription Months'),
                                            Select::make('purchase_type')
                                                ->label('Purchase Type')
                                                ->options(HardwareHandover::getPurchaseTypeOptions()),
                                        ])
                                        ->columns(4)
                                        ->default(function (HardwareHandover $record) {
                                            if (!$record) {
                                                return [];
                                            }

                                            // First try to get modules directly from the 'modules' field
                                            if ($record->modules) {
                                                // If it's a string, try to decode it
                                                if (is_string($record->modules)) {
                                                    $decodedModules = json_decode($record->modules, true);
                                                    if (is_array($decodedModules) && !empty($decodedModules)) {
                                                        return $decodedModules;
                                                    }
                                                }

                                                // If it's already an array, return it
                                                if (is_array($record->modules) && !empty($record->modules)) {
                                                    return $record->modules;
                                                }
                                            }

                                            // If no modules found in the main field, try to build from individual fields
                                            $moduleData = [];
                                            $moduleNames = ['attendance', 'leave', 'claim', 'payroll', 'appraisal', 'recruitment', 'power_bi'];

                                            foreach ($moduleNames as $module) {
                                                $headcountField = "{$module}_module_headcount";
                                                $subscriptionField = "{$module}_subscription_months";
                                                $purchaseTypeField = "{$module}_purchase_type";

                                                if (!empty($record->$headcountField) ||
                                                    !empty($record->$subscriptionField) ||
                                                    !empty($record->$purchaseTypeField)) {

                                                    $moduleData[] = [
                                                        'module_name' => ucfirst($module === 'power_bi' ? 'Power BI' : $module),
                                                        'headcount' => $record->$headcountField ?? '',
                                                        'subscription_months' => $record->$subscriptionField ?? '',
                                                        'purchase_type' => $record->$purchaseTypeField ?? '',
                                                    ];
                                                }
                                            }

                                            return $moduleData;
                                        }),
                                ]),

                            Section::make('Section 6: Other Details')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            Textarea::make('customization_details')
                                                ->label('Customization Details')
                                                ->default(function (HardwareHandover $record) {
                                                    return $record?->customization_details ?? 'No customization required';
                                                }),
                                            Textarea::make('enhancement_details')
                                                ->label('Enhancement Details')
                                                ->default(function (HardwareHandover $record) {
                                                    return $record?->enhancement_details ?? 'No enhancement required';
                                                }),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            Textarea::make('special_remark')
                                                ->label('Special Remark')
                                                ->default(function (HardwareHandover $record) {
                                                    return $record?->special_remark ?? 'N/A';
                                                }),
                                            Textarea::make('device_integration')
                                                ->label('Device Integration')
                                                ->default(function (HardwareHandover $record) {
                                                    return $record?->device_integration ?? 'No device integration required';
                                                }),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            Textarea::make('existing_hr_system')
                                                ->label('Existing HR System')
                                                ->default(function (HardwareHandover $record) {
                                                    return $record?->existing_hr_system ?? 'None';
                                                }),
                                            Textarea::make('experience_implementing_hr_system')
                                                ->label('Experience Implementing Any HR System')
                                                ->default(function (HardwareHandover $record) {
                                                    return $record?->experience_implementing_hr_system ?? 'No prior experience';
                                                }),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            Textarea::make('vip_package')
                                                ->label('VIP Package')
                                                ->default(function (HardwareHandover $record) {
                                                    return $record?->vip_package ?? 'No VIP package selected';
                                                }),
                                            Textarea::make('fingertec_device')
                                                ->label('FingerTec Device')
                                                ->default(function (HardwareHandover $record) {
                                                    return $record?->fingertec_device ?? 'No FingerTec device';
                                                }),
                                        ]),
                                ]),

                            Section::make('Section 7: Onsite Package')
                                ->schema([
                                    Grid::make(3)
                                        ->schema([
                                            Checkbox::make('onsite_kick_off_meeting')
                                                ->label('Onsite Kick Off Meeting')
                                                ->default(function (HardwareHandover $record) {
                                                    return $record?->onsite_kick_off_meeting ?? false;
                                                }),
                                            Checkbox::make('onsite_webinar_training')
                                                ->label('Onsite Webinar Training')
                                                ->default(function (HardwareHandover $record ) {
                                                    return $record?->onsite_webinar_training ?? false;
                                                }),
                                            Checkbox::make('onsite_briefing')
                                                ->label('Onsite Briefing')
                                                ->default(function (HardwareHandover $record ) {
                                                    return $record?->onsite_briefing ?? false;
                                                }),
                                        ]),
                                ]),
                            Grid::make(3)
                                ->schema([
                                    Section::make('Section 8: Payment Terms')
                                        ->columnSpan(1)
                                        ->schema([
                                            Forms\Components\Radio::make('payment_term')
                                                ->label('Select Payment Terms')
                                                ->options([
                                                    'full_payment' => 'Full Payment',
                                                    'payment_via_hrdf' => 'Payment via HRDF',
                                                    'payment_via_term' => 'Payment via Term',
                                                ])
                                                ->default(function (HardwareHandover $record) {
                                                    return $record?->payment_term ?? 'full_payment';
                                                })
                                                ->reactive(),
                                        ]),

                                    Section::make('Section 9: Proforma Invoices')
                                        ->columnSpan(1) // Ensure it spans one column
                                        ->schema([
                                            Select::make('proforma_invoice_number')
                                                ->label('Proforma Invoice Number')
                                                ->options(function (RelationManager $livewire) {
                                                    $leadId = $livewire->getOwnerRecord()->id;
                                                    return \App\Models\Quotation::where('lead_id', $leadId)
                                                        ->where('status', \App\Enums\QuotationStatusEnum::accepted)
                                                        ->pluck('pi_reference_no', 'id')
                                                        ->toArray();
                                                })
                                                ->default(function (HardwareHandover $record) {
                                                    if (!$record || !$record->proforma_invoice_number) {
                                                        return [];
                                                    }

                                                    // Handle string JSON format
                                                    if (is_string($record->proforma_invoice_number)) {
                                                        try {
                                                            $decoded = json_decode($record->proforma_invoice_number, true);
                                                            if (is_array($decoded)) {
                                                                return $decoded;
                                                            }
                                                        } catch (\Exception $e) {
                                                            // If JSON decode fails, treat as a single value
                                                        }

                                                        // If not JSON or decode failed, handle as a single string
                                                        return [$record->proforma_invoice_number];
                                                    }

                                                    // If already an array, return as is
                                                    if (is_array($record->proforma_invoice_number)) {
                                                        return $record->proforma_invoice_number;
                                                    }

                                                    // If a single non-string/non-array value
                                                    return [$record->proforma_invoice_number];
                                                })
                                                ->multiple()
                                                ->searchable()
                                                ->preload(),
                                        ]),

                                    Section::make('Section 10: Attachments')
                                        ->columnSpan(1)
                                        ->schema([
                                            Grid::make(1)
                                                ->schema([
                                                    FileUpload::make('confirmation_order_file')
                                                        ->label('Upload Confirmation Order')
                                                        ->disk('public')
                                                        ->directory('handovers/confirmation_orders')
                                                        ->visibility('public')
                                                        ->multiple()
                                                        ->maxFiles(3)
                                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                                        ->default(function (HardwareHandover $record) {
                                                            if (!$record || !$record->confirmation_order_file) {
                                                                return [];
                                                            }

                                                            // Handle JSON encoded file paths
                                                            if (is_string($record->confirmation_order_file)) {
                                                                try {
                                                                    $decodedFiles = json_decode($record->confirmation_order_file, true);
                                                                    if (is_array($decodedFiles)) {
                                                                        return $decodedFiles;
                                                                    }
                                                                } catch (\Exception $e) {
                                                                    // If JSON decode fails, return as single item
                                                                }

                                                                // If not JSON or decode failed, handle as a single item
                                                                return [$record->confirmation_order_file];
                                                            }

                                                            // If already an array
                                                            if (is_array($record->confirmation_order_file)) {
                                                                return $record->confirmation_order_file;
                                                            }

                                                            return [];
                                                        }),
                                                    FileUpload::make('payment_slip_file')
                                                        ->label(fn (callable $get) => $get('payment_term') === 'payment_via_hrdf' ? 'Upload HRDF Approval Letter' : 'Upload Payment Slip')
                                                        ->disk('public')
                                                        ->live(debounce: 500)
                                                        ->directory('handovers/payment_slips')
                                                        ->visibility('public')
                                                        ->multiple()
                                                        ->maxFiles(3)
                                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                                        ->helperText(fn (callable $get) => $get('payment_term') === 'payment_via_hrdf' ? 'Only PDF, JPEG, or PNG format, max 10MB (HRDF Approval Letter)' : 'Only PDF, JPEG, or PNG format, max 10MB')
                                                        ->default(function (HardwareHandover $record) {
                                                            if (!$record || !$record->payment_slip_file) {
                                                                return [];
                                                            }

                                                            // Handle JSON encoded file paths
                                                            if (is_string($record->payment_slip_file)) {
                                                                try {
                                                                    $decodedFiles = json_decode($record->payment_slip_file, true);
                                                                    if (is_array($decodedFiles)) {
                                                                        return $decodedFiles;
                                                                    }
                                                                } catch (\Exception $e) {
                                                                    // If JSON decode fails, return as single item
                                                                }

                                                                // If not JSON or decode failed, handle as a single item
                                                                return [$record->payment_slip_file];
                                                            }

                                                            // If already an array
                                                            if (is_array($record->payment_slip_file)) {
                                                                return $record->payment_slip_file;
                                                            }

                                                            return [];
                                                        }),
                                                ])
                                        ]),
                                ]),
                            Section::make('Section 11: Installation Details')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            Textarea::make('installation_special_remark')
                                                ->label('Installation Special Remark')
                                                ->default(function (HardwareHandover $record) {
                                                    return $record?->installation_special_remark ?? null;
                                                }),
                                            FileUpload::make('installation_media')
                                                ->label('Photo/ Video')
                                                ->disk('public')
                                                ->directory('handovers/installation_media')
                                                ->visibility('public')
                                                ->multiple()
                                                ->maxFiles(3)
                                                ->acceptedFileTypes([
                                                    'image/jpeg',
                                                    'image/png',
                                                    'video/mp4',
                                                    'video/quicktime',
                                                    'video/x-msvideo',
                                                    'video/webm'
                                                ])
                                                ->maxSize(15360) // 15MB in KB
                                                ->imageResizeMode('cover')
                                                ->imageCropAspectRatio('1:1')
                                                ->imageResizeTargetWidth('300')
                                                ->imageResizeTargetHeight('300')
                                                ->uploadProgressIndicatorPosition('left')
                                                ->helperText('Upload photos or videos (Max 15MB per file)')
                                                ->default(function (HardwareHandover $record) {
                                                    if (!$record || !$record->installation_media) {
                                                        return [];
                                                    }

                                                    // Handle JSON encoded file paths
                                                    if (is_string($record->installation_media)) {
                                                        try {
                                                            $decodedFiles = json_decode($record->installation_media, true);
                                                            if (is_array($decodedFiles)) {
                                                                return $decodedFiles;
                                                            }
                                                        } catch (\Exception $e) {
                                                            // If JSON decode fails, return as single item
                                                        }

                                                        // If not JSON or decode failed, handle as a single item
                                                        return [$record->installation_media];
                                                    }

                                                    // If already an array
                                                    if (is_array($record->installation_media)) {
                                                        return $record->installation_media;
                                                    }

                                                    return [];
                                                }),
                                        ]),
                                ]),
                        ])
                        ->action(function (HardwareHandover $record, array $data): void {
                            // Store modules as JSON
                            if (isset($data['modules']) && is_array($data['modules'])) {
                                // First create a copy of the array for iteration
                                $modulesArray = $data['modules'];

                                // Loop through the array before JSON encoding it
                                foreach ($modulesArray as $module) {
                                    $moduleName = strtolower(str_replace(' ', '_', $module['module_name']));
                                    $data["{$moduleName}_module_headcount"] = $module['headcount'] ?? null;
                                    $data["{$moduleName}_subscription_months"] = $module['subscription_months'] ?? null;
                                    $data["{$moduleName}_purchase_type"] = $module['purchase_type'] ?? null;
                                }

                                // Then encode to JSON after processing the array
                                $data['modules'] = json_encode($modulesArray);
                            }

                            // Handle file array encodings
                            if (isset($data['confirmation_order_file']) && is_array($data['confirmation_order_file'])) {
                                $data['confirmation_order_file'] = json_encode($data['confirmation_order_file']);
                            }

                            if (isset($data['payment_slip_file']) && is_array($data['payment_slip_file'])) {
                                $data['payment_slip_file'] = json_encode($data['payment_slip_file']);
                            }

                            if (isset($data['implementation_pics']) && is_array($data['implementation_pics'])) {
                                $data['implementation_pics'] = json_encode($data['implementation_pics']);
                            }

                            if (isset($data['installation_media']) && is_array($data['installation_media'])) {
                                $data['installation_media'] = json_encode($data['installation_media']);
                            }

                            // Update status if saving as draft
                            if (isset($data['save_type'])) {
                                if ($data['save_type'] === 'draft') {
                                    $data['status'] = 'Draft';
                                }
                            }

                            // Update the record
                            $record->update($data);

                            // Generate PDF for non-draft handovers
                            if ($record->status !== 'Draft') {
                                // Use the controller for PDF generation
                                app(GenerateHardwareHandoverPdfController::class)->generateInBackground($record);
                            }

                            Notification::make()
                                ->title('Hardware handover updated successfully')
                                ->success()
                                ->send();
                        }),

                    // Submit for Approval button - only visible for Draft status
                    Action::make('submit_for_approval')
                        ->label('Submit for Approval')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->visible(fn (HardwareHandover $record): bool => $record->status === 'Draft')
                        ->action(function (HardwareHandover $record): void {
                            $record->update([
                                'status' => 'New'
                            ]);

                            // Use the controller for PDF generation
                            app(GenerateHardwareHandoverPdfController::class)->generateInBackground($record);

                            Notification::make()
                                ->title('Handover submitted for approval')
                                ->success()
                                ->send();
                        }),

                    // Convert to Draft button - only visible for Rejected status
                    Action::make('convert_to_draft')
                        ->label('Convert to Draft')
                        ->icon('heroicon-o-document')
                        ->color('warning')
                        ->visible(fn (HardwareHandover $record): bool => $record->status === 'Rejected')
                        ->action(function (HardwareHandover $record): void {
                            $record->update([
                                'status' => 'Draft'
                            ]);

                            Notification::make()
                                ->title('Handover converted to draft')
                                ->success()
                                ->send();
                        }),
                ])->icon('heroicon-m-list-bullet')
                ->size(ActionSize::Small)
                ->color('primary')
                ->button(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    protected function isEInvoiceDetailsIncomplete(): bool
    {
        $leadId = $this->getOwnerRecord()->id;
        $eInvoiceDetails = \App\Models\EInvoiceDetail::where('lead_id', $leadId)->first();

        // If no e-invoice details exist at all
        if (!$eInvoiceDetails) {
            return true;
        }

        // Check if any required field is null or empty
        $requiredFields = [
            'pic_email',
            'registration_name',
            'identity_type',
            'business_address',
            'contact_number',
            'email_address',
            'city',
            'country',
            'state'
        ];

        foreach ($requiredFields as $field) {
            if (empty($eInvoiceDetails->$field)) {
                return true;
            }
        }

        return false;
    }
}
