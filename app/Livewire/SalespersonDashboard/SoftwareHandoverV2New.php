<?php

namespace App\Livewire\SalespersonDashboard;

use App\Classes\Encryptor;
use App\Filament\Filters\SortFilter;
use App\Http\Controllers\GenerateSoftwareHandoverPdfController;
use App\Models\CompanyDetail;
use App\Models\ImplementerLogs;
use App\Models\Lead;
use App\Models\SoftwareHandover;
use App\Models\User;
use App\Services\CategoryService;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

class SoftwareHandoverV2New extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?int $indexRepeater = 0;
    protected static ?int $indexRepeater2 = 0;

    public $selectedUser;
    public $lastRefreshTime;

    public function mount()
    {
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function refreshTable()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');

        Notification::make()
            ->title('Table refreshed')
            ->success()
            ->send();
    }

    #[On('refresh-softwarehandover-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    #[On('updateTablesForUser')] // Listen for updates
    public function updateTablesForUser($selectedUser)
    {
        $this->selectedUser = $selectedUser;
        session(['selectedUser' => $selectedUser]); // Store for consistency

        $this->resetTable(); // Refresh the table
    }

    public function getNewSoftwareHandovers()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->id();

        $query = SoftwareHandover::query();
        $query->where('hr_version', 2);

        // Salesperson filter logic
        if ($this->selectedUser === 'all-salespersons') {
            $query->whereIn('status', ['Rejected', 'Draft', 'New', 'Approved']);

            // Keep as is - show all salespersons' handovers
            $salespersonIds = User::where('role_id', 2)->pluck('id');
            $query->whereHas('lead', function ($leadQuery) use ($salespersonIds) {
                $leadQuery->whereIn('salesperson', $salespersonIds);
            });
        } elseif (is_numeric($this->selectedUser)) {
            // Validate that the selected user exists and is a salesperson
            $userExists = User::where('id', $this->selectedUser)->where('role_id', 2)->exists();
            $query->whereIn('status', ['Rejected', 'Draft', 'New', 'Approved']);

            if ($userExists) {
                $selectedUser = $this->selectedUser; // Create a local variable
                $query->whereHas('lead', function ($leadQuery) use ($selectedUser) {
                    $leadQuery->where('salesperson', $selectedUser);
                });
            } else {
                // Invalid user ID or not a salesperson, fall back to default
                $query->whereHas('lead', function ($leadQuery) {
                    $leadQuery->where('salesperson', auth()->id());
                });
            }
        } else {
            if (auth()->user()->role_id === 2) {
                // Salespersons (role_id 2) can see Draft, New, Approved, and Completed
                $query->whereIn('status', ['Rejected', 'Draft', 'New', 'Approved']);

                // But only THEIR OWN records
                $userId = auth()->id();
                $query->whereHas('lead', function ($leadQuery) use ($userId) {
                    $leadQuery->where('salesperson', $userId);
                });
            } else {
                // Other users (admin, managers) can only see New, Approved, and Completed
                $query->whereIn('status', ['New', 'Approved']);
                // But they can see ALL records
            }
        }

        $query->orderByRaw("CASE
            when status = 'Rejected' THEN 0
            WHEN status = 'Draft' THEN 1
            WHEN status = 'New' THEN 2
            WHEN status = 'Approved' THEN 3
            WHEN status = 'Completed' THEN 4
            ELSE 5
        END")
            ->orderBy('created_at', 'desc');

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getNewSoftwareHandovers())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn() => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                // Add this new filter for status
                SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'New' => 'New',
                        'Rejected' => 'Rejected',
                        'Completed' => 'Completed',
                    ])
                    ->placeholder('All Statuses')
                    ->multiple(),
                SelectFilter::make('salesperson')
                    ->label('Filter by Salesperson')
                    ->options(function () {
                        return User::where('role_id', '2')
                            ->whereNot('id',15) // Exclude Testing Account
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Salesperson')
                    ->multiple(),

                SortFilter::make("sort_by"),
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, SoftwareHandover $record) {
                        // If no state (ID) is provided, return a fallback
                        if (!$state) {
                            return 'Unknown';
                        }

                        // For handover_pdf, extract filename
                        if ($record->handover_pdf) {
                            // Extract just the filename without extension
                            $filename = basename($record->handover_pdf, '.pdf');
                            return $filename;
                        }


                        return $record->formatted_handover_id;
                    })
                    ->color('primary') // Makes it visually appear as a link
                    ->weight('bold')
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('4xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (SoftwareHandover $record): View {
                                return view('components.software-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('salesperson')
                    ->label('SalesPerson')
                    ->visible(fn(): bool => auth()->user()->role_id !== 2),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        $company = CompanyDetail::where('company_name', $state)->first();

                        if (!empty($record->lead_id)) {
                            $company = CompanyDetail::where('lead_id', $record->lead_id)->first();
                        }

                        if ($company) {
                            $shortened = strtoupper(Str::limit($company->company_name, 20, '...'));
                            $encryptedId = \App\Classes\Encryptor::encrypt($company->lead_id);

                            return new HtmlString('<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($state) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $company->company_name . '
                                </a>');
                        }

                        $shortened = strtoupper(Str::limit($state, 20, '...'));
                        return "<span title='{$state}'>{$state}</span>";
                    })
                    ->html(),

                TextColumn::make('hr_version')
                    ->label('HR Version')
                    ->formatStateUsing(function ($state) {
                        return $state ? 'Version ' . $state : 'N/A';
                    }),

                TextColumn::make('license_type')
                    ->label('License Type')
                    ->formatStateUsing(fn (string $state): string => Str::title($state)),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn(string $state): HtmlString => match ($state) {
                        'Draft' => new HtmlString('<span style="color: orange;">Draft</span>'),
                        'New' => new HtmlString('<span style="color: blue;">New</span>'),
                        'Approved' => new HtmlString('<span style="color: green;">Approved</span>'),
                        'Rejected' => new HtmlString('<span style="color: red;">Rejected</span>'),
                        default => new HtmlString('<span>' . ucfirst($state) . '</span>'),
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('submit_for_approval')
                        ->label('Submit for Approval')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->visible(fn(SoftwareHandover $record): bool => $record->status === 'Draft')
                        ->action(function (SoftwareHandover $record): void {
                            $record->update([
                                'status' => 'New',
                                'submitted_at' => now(),
                            ]);

                            // Use the controller for PDF generation
                            app(GenerateSoftwareHandoverPdfController::class)->generateInBackground($record);

                            Notification::make()
                                ->title('Handover submitted for approval')
                                ->success()
                                ->send();
                        }),
                    Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading(false)
                        ->modalWidth('4xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->visible(fn(SoftwareHandover $record): bool => in_array($record->status, ['New', 'Completed', 'Approved']))
                        // Use a callback function instead of arrow function for more control
                        ->modalContent(function (SoftwareHandover $record): View {

                            // Return the view with the record using $this->record pattern
                            return view('components.software-handover')
                                ->with('extraAttributes', ['record' => $record]);
                        }),
                    Action::make('edit_software_handover')
                        ->label(function (SoftwareHandover $record): string {
                            $formattedId = $record->formatted_handover_id;
                            return "Edit Software Handover {$formattedId}";
                        })
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->modalSubmitActionLabel('Save')
                        ->visible(fn(SoftwareHandover $record): bool => in_array($record->status, ['Draft']))
                        ->modalWidth(MaxWidth::FourExtraLarge)
                        ->slideOver()
                        ->form([
                            Section::make('Step 1: Database')
                                ->collapsible()
                                ->schema([
                                    Grid::make(3)
                                        ->schema([
                                            TextInput::make('company_name')
                                                ->label('Company Name')
                                                ->default(fn(SoftwareHandover $record) =>
                                                $record->company_name ?? $this->getOwnerRecord()->companyDetail->company_name ?? null),
                                            TextInput::make('pic_name')
                                                ->label('Name')
                                                ->default(fn(SoftwareHandover $record) =>
                                                $record->pic_name ?? $this->getOwnerRecord()->companyDetail->name ?? $this->getOwnerRecord()->name),
                                            TextInput::make('pic_phone')
                                                ->label('PIC HP No.')
                                                ->default(fn(SoftwareHandover $record) =>
                                                $record->pic_phone ?? $this->getOwnerRecord()->companyDetail->contact_no ?? $this->getOwnerRecord()->phone),
                                        ]),
                                    Grid::make(3)
                                        ->schema([
                                            TextInput::make('salesperson')
                                                ->readOnly()
                                                ->label('Salesperson')
                                                ->default(fn(SoftwareHandover $record) =>
                                                $record->salesperson ?? ($this->getOwnerRecord()->salesperson ? User::find($this->getOwnerRecord()->salesperson)->name : null)),
                                            TextInput::make('headcount')
                                                ->numeric()
                                                ->label('Company Size')
                                                ->live(debounce: 550)
                                                ->afterStateUpdated(function (Set $set, ?string $state, CategoryService $category) {
                                                    $set('category', $category->retrieve($state));
                                                })
                                                ->default(fn(SoftwareHandover $record) => $record->headcount ?? null)
                                                ->required(),
                                            TextInput::make('category')
                                                ->autocapitalize()
                                                ->live(debounce: 550)
                                                ->placeholder('Select a category')
                                                ->dehydrated(false)
                                                ->default(function (SoftwareHandover $record, CategoryService $category) {
                                                    // If record exists with headcount, calculate category from headcount
                                                    if ($record && $record->headcount) {
                                                        return $category->retrieve($record->headcount);
                                                    }
                                                    // If record has a saved category, use that
                                                    if ($record && $record->category) {
                                                        return $record->category;
                                                    }
                                                    return null;
                                                })
                                                ->readOnly(),
                                        ]),
                                ]),

                            Section::make('Step 2: Invoice Details')
                                ->schema([
                                    Grid::make(1)
                                        ->schema([
                                            Actions::make([
                                                FormAction::make('export_invoice_info')
                                                    ->label('Export AutoCount Debtor')
                                                    ->color('success')
                                                    ->icon('heroicon-o-document-arrow-down')
                                                    ->url(function (SoftwareHandover $record) {
                                                        // Use the record's lead_id instead of getOwnerRecord()->id
                                                        return route('software-handover.export-customer', ['lead' => Encryptor::encrypt($record->lead_id)]);
                                                    })
                                                    ->openUrlInNewTab(),
                                            ])
                                                ->extraAttributes(['class' => 'space-y-2']),
                                        ]),
                                ]),

                            Section::make('Step 3: Implementation PICs')
                                ->schema([
                                    Repeater::make('implementation_pics')
                                        ->label('Implementation PICs')
                                        ->hiddenLabel(true)
                                        ->schema([
                                            Grid::make(4)
                                                ->schema([
                                                    TextInput::make('pic_name_impl')
                                                        ->required()
                                                        ->label('Name'),
                                                    TextInput::make('position')
                                                        ->label('Position'),
                                                    TextInput::make('pic_phone_impl')
                                                        ->required()
                                                        ->label('HP Number'),
                                                    TextInput::make('pic_email_impl')
                                                        ->required()
                                                        ->label('Email Address')
                                                        ->email(),
                                                ]),
                                        ])
                                        ->itemLabel('Person In Charge')
                                        ->columns(2)
                                        ->default(function (SoftwareHandover $record) {
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
                                            return [];
                                        }),
                                ]),

                            Section::make('Step 4: Remark Details')
                                ->schema([
                                    Repeater::make('remarks')
                                        ->label('Remarks')
                                        ->hiddenLabel(true)
                                        ->schema([
                                            Grid::make(2)
                                                ->schema([
                                                    Textarea::make('remark')
                                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                        ->afterStateHydrated(fn($state) => Str::upper($state))
                                                        ->afterStateUpdated(fn($state) => Str::upper($state))
                                                        ->hiddenLabel(true)
                                                        ->label(function (Get $get, ?string $state, $livewire) {
                                                            // Get the current array key from the state path
                                                            $statePath = $livewire->getFormStatePath();
                                                            $matches = [];
                                                            if (preg_match('/remarks\.(\d+)\./', $statePath, $matches)) {
                                                                $index = (int) $matches[1];
                                                                return 'Remark ' . ($index + 1);
                                                            }
                                                            return 'Remark';
                                                        })
                                                        ->placeholder('Enter remark here')
                                                        ->rows(3),

                                                    // Add file attachments for each remark
                                                    FileUpload::make('attachments')
                                                        ->hiddenLabel(true)
                                                        ->disk('public')
                                                        ->directory('handovers/remark_attachments')
                                                        ->visibility('public')
                                                        ->multiple()
                                                        ->maxFiles(5)
                                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                                        ->openable()
                                                        ->downloadable()
                                                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get, SoftwareHandover $record): string {
                                                            // Use the record's formatted handover ID
                                                            $formattedId = $record->formatted_handover_id;
                                                            // Get extension
                                                            $extension = $file->getClientOriginalExtension();

                                                            // Generate a unique identifier (timestamp) to avoid overwriting files
                                                            $timestamp = now()->format('YmdHis');
                                                            $random = rand(1000, 9999);

                                                            return "{$formattedId}-SW-REMARK-{$timestamp}-{$random}.{$extension}";
                                                        }),
                                                ]),
                                        ])
                                        ->itemLabel('Remark')
                                        ->addActionLabel('Add Remark')
                                        ->default(function (SoftwareHandover $record) {
                                            if ($record && $record->remarks) {
                                                // If it's a string, decode it
                                                if (is_string($record->remarks)) {
                                                    $decoded = json_decode($record->remarks, true);

                                                    // Process each remark to handle its attachments
                                                    if (is_array($decoded)) {
                                                        foreach ($decoded as $key => $remark) {
                                                            // Decode the attachments if they're stored as JSON string
                                                            if (isset($remark['attachments']) && is_string($remark['attachments'])) {
                                                                $decoded[$key]['attachments'] = json_decode($remark['attachments'], true);
                                                            }
                                                        }
                                                        return $decoded;
                                                    }
                                                    return [];
                                                }

                                                // If it's already an array, return it but process attachments
                                                if (is_array($record->remarks)) {
                                                    $remarks = $record->remarks;
                                                    foreach ($remarks as $key => $remark) {
                                                        if (isset($remark['attachments']) && is_string($remark['attachments'])) {
                                                            $remarks[$key]['attachments'] = json_decode($remark['attachments'], true);
                                                        }
                                                    }
                                                    return $remarks;
                                                }
                                            }
                                            return [];
                                        }),
                                ]),

                            Section::make('Step 5: Training')
                                ->columnSpan(1)
                                ->schema([
                                    Radio::make('training_type')
                                        ->label('')
                                        ->options([
                                            'online_webinar_training' => 'Online Webinar Training',
                                            'online_hrdf_training' => 'Online HRDF Training',
                                        ])
                                        // ->inline()
                                        ->columns(2)
                                        ->required()
                                        ->default(function (SoftwareHandover $record) {
                                            // Return the saved training type if it exists
                                            return $record->training_type ?? null;
                                        }),
                                ]),

                            Section::make('Step 6: Proforma Invoice')
                                ->columnSpan(1)
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            Select::make('proforma_invoice_product')
                                                ->required()
                                                ->label('Proforma Invoice Product')
                                                ->options(function (SoftwareHandover $record) {
                                                    if (!$record || !$record->lead_id) {
                                                        return [];
                                                    }

                                                    return \App\Models\Quotation::where('lead_id', $record->lead_id)
                                                        ->where('quotation_type', 'product')
                                                        ->where('status', \App\Enums\QuotationStatusEnum::accepted)
                                                        ->pluck('pi_reference_no', 'id')
                                                        ->toArray();
                                                })
                                                ->multiple()
                                                ->searchable()
                                                ->preload()
                                                ->default(function (SoftwareHandover $record) {
                                                    if (!$record || !$record->proforma_invoice_product) {
                                                        return [];
                                                    }
                                                    if (is_string($record->proforma_invoice_product)) {
                                                        return json_decode($record->proforma_invoice_product, true) ?? [];
                                                    }
                                                    return is_array($record->proforma_invoice_product) ? $record->proforma_invoice_product : [];
                                                }),
                                            Select::make('proforma_invoice_hrdf')
                                                ->label('Proforma Invoice HRDF')
                                                ->options(function (SoftwareHandover $record) {
                                                    if (!$record || !$record->lead_id) {
                                                        return [];
                                                    }

                                                    return \App\Models\Quotation::where('lead_id', $record->lead_id)
                                                        ->where('quotation_type', 'hrdf')
                                                        ->where('status', \App\Enums\QuotationStatusEnum::accepted)
                                                        ->pluck('pi_reference_no', 'id')
                                                        ->toArray();
                                                })
                                                ->multiple()
                                                ->searchable()
                                                ->preload()
                                                ->default(function (SoftwareHandover $record) {
                                                    if (!$record || !$record->proforma_invoice_hrdf) {
                                                        return [];
                                                    }
                                                    if (is_string($record->proforma_invoice_hrdf)) {
                                                        return json_decode($record->proforma_invoice_hrdf, true) ?? [];
                                                    }
                                                    return is_array($record->proforma_invoice_hrdf) ? $record->proforma_invoice_hrdf : [];
                                                }),
                                        ])
                                ]),

                            Section::make('Step 7: Attachment')
                                ->columnSpan(1)
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            FileUpload::make('confirmation_order_file')
                                                ->label('Upload Confirmation Order')
                                                ->disk('public')
                                                ->directory('handovers/confirmation_orders')
                                                ->visibility('public')
                                                ->multiple()
                                                ->maxFiles(1)
                                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                                ->openable()
                                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get, SoftwareHandover $record): string {
                                                    // Use the record's formatted handover ID
                                                    $formattedId = $record->formatted_handover_id;
                                                    // Get extension
                                                    $extension = $file->getClientOriginalExtension();

                                                    // Generate a unique identifier (timestamp) to avoid overwriting files
                                                    $timestamp = now()->format('YmdHis');
                                                    $random = rand(1000, 9999);

                                                    return "{$formattedId}-SW-CONFIRM-{$timestamp}-{$random}.{$extension}";
                                                })
                                                ->default(function (SoftwareHandover $record) {
                                                    if (!$record || !$record->confirmation_order_file) {
                                                        return [];
                                                    }
                                                    if (is_string($record->confirmation_order_file)) {
                                                        return json_decode($record->confirmation_order_file, true) ?? [];
                                                    }
                                                    return is_array($record->confirmation_order_file) ? $record->confirmation_order_file : [];
                                                }),

                                            FileUpload::make('payment_slip_file')
                                                ->label('Upload Payment Slip')
                                                ->disk('public')
                                                ->live(debounce: 500)
                                                ->directory('handovers/payment_slips')
                                                ->visibility('public')
                                                ->multiple()
                                                ->maxFiles(1)
                                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                                ->openable()
                                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get, SoftwareHandover $record): string {
                                                    // Use the record's formatted handover ID
                                                    $formattedId = $record->formatted_handover_id;
                                                    // Get extension
                                                    $extension = $file->getClientOriginalExtension();

                                                    // Generate a unique identifier (timestamp) to avoid overwriting files
                                                    $timestamp = now()->format('YmdHis');
                                                    $random = rand(1000, 9999);

                                                    return "{$formattedId}-SW-PAYMENT-{$timestamp}-{$random}.{$extension}";
                                                })
                                                ->default(function (SoftwareHandover $record) {
                                                    if (!$record || !$record->payment_slip_file) {
                                                        return [];
                                                    }
                                                    if (is_string($record->payment_slip_file)) {
                                                        return json_decode($record->payment_slip_file, true) ?? [];
                                                    }
                                                    return is_array($record->payment_slip_file) ? $record->payment_slip_file : [];
                                                }),

                                            FileUpload::make('hrdf_grant_file')
                                                ->label('Upload HRDF Grant Approval Letter')
                                                ->disk('public')
                                                ->directory('handovers/hrdf_grant')
                                                ->visibility('public')
                                                ->multiple()
                                                ->maxFiles(10)
                                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                                ->openable()
                                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get, SoftwareHandover $record): string {
                                                    // Use the record's formatted handover ID
                                                    $formattedId = $record->formatted_handover_id;
                                                    // Get extension
                                                    $extension = $file->getClientOriginalExtension();

                                                    // Generate a unique identifier (timestamp) to avoid overwriting files
                                                    $timestamp = now()->format('YmdHis');
                                                    $random = rand(1000, 9999);

                                                    return "{$formattedId}-SW-HRDF-{$timestamp}-{$random}.{$extension}";
                                                })
                                                ->default(function (SoftwareHandover $record) {
                                                    if (!$record || !$record->hrdf_grant_file) {
                                                        return [];
                                                    }
                                                    if (is_string($record->hrdf_grant_file)) {
                                                        return json_decode($record->hrdf_grant_file, true) ?? [];
                                                    }
                                                    return is_array($record->hrdf_grant_file) ? $record->hrdf_grant_file : [];
                                                }),

                                            FileUpload::make('invoice_file')
                                                ->label('Upload Invoice')
                                                ->disk('public')
                                                ->directory('handovers/invoices')
                                                ->visibility('public')
                                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                                ->multiple()
                                                ->maxFiles(10)
                                                ->required()
                                                ->openable()
                                                ->afterStateUpdated(function (Set $set, ?array $state) {
                                                    Log::info("Invoice upload state received", [
                                                        'state_is_null' => is_null($state),
                                                        'state_is_empty' => empty($state),
                                                        'state_type' => gettype($state),
                                                        'state_count' => is_array($state) ? count($state) : 0,
                                                        'state_keys' => is_array($state) ? array_keys($state) : [],
                                                    ]);

                                                    if (empty($state)) {
                                                        Log::info("No files uploaded - state is empty");
                                                        return;
                                                    }

                                                    // ✅ Collect paths for ALL uploaded files
                                                    $filePaths = [];

                                                    if (is_array($state)) {
                                                        foreach ($state as $file) {
                                                            $tempPath = null;

                                                            if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                                                $tempPath = $file->getRealPath();
                                                            } elseif (is_string($file)) {
                                                                $tempPath = storage_path('app/livewire-tmp/' . $file);
                                                            } elseif (is_object($file) && method_exists($file, 'getRealPath')) {
                                                                $tempPath = $file->getRealPath();
                                                            }

                                                            if ($tempPath && file_exists($tempPath)) {
                                                                $filePaths[] = $tempPath;
                                                            }
                                                        }
                                                    }

                                                    if (empty($filePaths)) {
                                                        Log::warning("No valid file paths extracted from uploaded files");
                                                        return;
                                                    }

                                                    Log::info("Extracted file paths for OCR processing", [
                                                        'total_files' => count($filePaths),
                                                        'paths' => $filePaths
                                                    ]);

                                                    try {
                                                        $ocrService = app(\App\Services\InvoiceOcrService::class);

                                                        // ✅ Scan ALL uploaded files
                                                        $invoiceNumber = $ocrService->extractInvoiceNumberFromMultipleFiles($filePaths);

                                                        if ($invoiceNumber) {
                                                            $set('invoice_number', $invoiceNumber);

                                                            \Filament\Notifications\Notification::make()
                                                                ->title('Invoice Number Detected')
                                                                ->success()
                                                                ->body("Found: {$invoiceNumber} (scanned " . count($filePaths) . " file(s))")
                                                                ->send();
                                                        } else {
                                                            \Filament\Notifications\Notification::make()
                                                                ->title('Invoice Number Not Found')
                                                                ->warning()
                                                                ->body('Could not detect invoice number in any of the ' . count($filePaths) . ' uploaded file(s). Please verify manually.')
                                                                ->send();
                                                        }
                                                    } catch (\Exception $e) {
                                                        Log::error("Invoice OCR failed", [
                                                            'error' => $e->getMessage(),
                                                            'file_count' => count($filePaths)
                                                        ]);

                                                        \Filament\Notifications\Notification::make()
                                                            ->title('OCR Processing Error')
                                                            ->danger()
                                                            ->body('Failed to process invoices: ' . $e->getMessage())
                                                            ->send();
                                                    }
                                                })
                                                ->live(debounce: 2000)
                                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                                                    $companyName = Str::slug($get('company_name') ?? 'invoice');
                                                    $date = now()->format('Y-m-d');
                                                    $random = Str::random(5);
                                                    $extension = $file->getClientOriginalExtension();
                                                    return "{$companyName}-invoice-{$date}-{$random}.{$extension}";
                                                })
                                                ->default(function (SoftwareHandover $record) {
                                                    if (!$record || !$record->invoice_file) {
                                                        return [];
                                                    }
                                                    if (is_string($record->invoice_file)) {
                                                        return json_decode($record->invoice_file, true) ?? [];
                                                    }
                                                    return is_array($record->invoice_file) ? $record->invoice_file : [];
                                                }),
                                        ]),
                                ]),
                        ])
                        ->action(function (SoftwareHandover $record, array $data): void {
                            if (isset($data['remarks']) && is_array($data['remarks'])) {
                                foreach ($data['remarks'] as $key => $remark) {
                                    // Encode attachments only if they exist and are array
                                    if (!empty($remark['attachments'])) {
                                        // If attachments is already a string (JSON), leave it as is
                                        if (!is_string($remark['attachments'])) {
                                            $data['remarks'][$key]['attachments'] = json_encode($remark['attachments']);
                                        }
                                    } else {
                                        // Set to empty array encoded as JSON if no attachments
                                        $data['remarks'][$key]['attachments'] = json_encode([]);
                                    }
                                }

                                // Encode the entire remarks structure after processing attachments
                                $data['remarks'] = json_encode($data['remarks']);
                            }
                            // Handle file array encodings
                            if (isset($data['confirmation_order_file']) && is_array($data['confirmation_order_file'])) {
                                $data['confirmation_order_file'] = json_encode($data['confirmation_order_file']);
                            }

                            if (isset($data['hrdf_grant_file']) && is_array($data['hrdf_grant_file'])) {
                                $data['hrdf_grant_file'] = json_encode($data['hrdf_grant_file']);
                            }

                            if (isset($data['payment_slip_file']) && is_array($data['payment_slip_file'])) {
                                $data['payment_slip_file'] = json_encode($data['payment_slip_file']);
                            }

                            if (isset($data['implementation_pics']) && is_array($data['implementation_pics'])) {
                                $data['implementation_pics'] = json_encode($data['implementation_pics']);
                            }

                            if (isset($data['remarks']) && is_array($data['remarks'])) {
                                $data['remarks'] = json_encode($data['remarks']);
                            }

                            if (isset($data['proforma_invoice_product']) && is_array($data['proforma_invoice_product'])) {
                                $data['proforma_invoice_product'] = json_encode($data['proforma_invoice_product']);
                            }

                            if (isset($data['proforma_invoice_hrdf']) && is_array($data['proforma_invoice_hrdf'])) {
                                $data['proforma_invoice_hrdf'] = json_encode($data['proforma_invoice_hrdf']);
                            }

                            if (isset($data['invoice_file']) && is_array($data['invoice_file'])) {
                                $data['invoice_file'] = json_encode($data['invoice_file']);
                            }
                            // Update the record
                            $record->update($data);

                            // Generate PDF for non-draft handovers
                            if ($record->status !== 'Draft') {
                                // Use the controller for PDF generation
                                app(GenerateSoftwareHandoverPdfController::class)->generateInBackground($record);
                            }

                            Notification::make()
                                ->title('Software handover updated successfully')
                                ->success()
                                ->send();
                        }),

                    // Also add the view reason and convert to draft actions for completeness
                    Action::make('view_reason')
                        ->label('View Reason')
                        ->visible(fn(SoftwareHandover $record): bool => $record->status === 'Rejected')
                        ->icon('heroicon-o-magnifying-glass-plus')
                        ->modalHeading('Change Request Reason')
                        ->modalContent(fn($record) => view('components.view-reason', [
                            'reason' => $record->reject_reason,
                        ]))
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalWidth('3xl')
                        ->color('warning'),
                    Action::make('mark_rejected')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->hidden(
                            fn(SoftwareHandover $record): bool =>
                            $record->status !== 'New' || auth()->user()->role_id === 2
                        )
                        ->form([
                            \Filament\Forms\Components\Textarea::make('reject_reason')
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->afterStateHydrated(fn($state) => Str::upper($state))
                                ->afterStateUpdated(fn($state) => Str::upper($state))
                                ->label('Reason for Rejection')
                                ->required()
                                ->placeholder('Please provide a reason for rejecting this handover')
                                ->maxLength(500)
                        ])
                        ->action(function (SoftwareHandover $record, array $data): void {
                            // Update both status and add the rejection remarks
                            $record->update([
                                'status' => 'Rejected',
                                'reject_reason' => $data['reject_reason']
                            ]);

                            $salespersonName = $record->salesperson;
                            $salesperson = null;

                            if ($salespersonName) {
                                $salesperson = \App\Models\User::where('name', $salespersonName)
                                    ->where('role_id', 2)
                                    ->first();
                            }

                            if (!$salesperson && $record->lead_id) {
                                $lead = \App\Models\Lead::find($record->lead_id);
                                if ($lead && $lead->salesperson) {
                                    $salesperson = \App\Models\User::find($lead->salesperson);
                                }
                            }

                            $salespersonEmail = $salesperson ? $salesperson->email : null;
                            $salespersonName = $salesperson ? $salesperson->name : ($record->salesperson ?? 'Unknown Salesperson');

                            $rejecter = auth()->user();
                            $rejecterName = $rejecter->name ?? 'System';
                            $rejecterEmail = $rejecter->email;

                            $handoverId = $record->formatted_handover_id;

                            if ($salespersonEmail) {
                                try {
                                    $rejectedDate = now()->format('d F Y');
                                    $rejectReason = $data['reject_reason'];

                                    \Illuminate\Support\Facades\Mail::send('emails.software_handover_rejection', [
                                        'rejecterName' => $rejecterName,
                                        'rejectedDate' => $rejectedDate,
                                        'handoverId' => $handoverId,
                                        'salespersonName' => $salespersonName,
                                        'rejectReason' => $rejectReason
                                    ], function ($message) use ($salespersonEmail, $handoverId, $rejecterEmail, $rejecterName) {
                                        $message->to($salespersonEmail)
                                            ->from($rejecterEmail, $rejecterName) // Set the rejecter as the sender
                                            ->subject("REJECTED | SOFTWARE HANDOVER ID {$handoverId}");
                                    });

                                    // Log successful email sending
                                    \Illuminate\Support\Facades\Log::info("Rejection email sent to {$salespersonEmail} for handover {$handoverId}");
                                } catch (\Exception $e) {
                                    // Log email sending failure
                                    \Illuminate\Support\Facades\Log::error("Failed to send rejection email: {$e->getMessage()}");
                                }
                            } else {
                                \Illuminate\Support\Facades\Log::warning("Cannot send rejection email - no email address found for salesperson: {$salespersonName}");
                            }

                            Notification::make()
                                ->title('Software Handover marked as rejected')
                                ->body('Rejection reason: ' . $data['reject_reason'])
                                ->danger()
                                ->send();
                        })
                        ->requiresConfirmation(false),

                    Action::make('mark_completed')
                        ->label('Mark as Completed')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->modalWidth('xl')
                        ->form([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('speaker_category')
                                        ->label('Speaker Category')
                                        ->readOnly()
                                        ->default(function (SoftwareHandover $record) {
                                            if ($record && $record->speaker_category) {
                                                return ucwords($record->speaker_category);
                                            }
                                            return 'Not specified';
                                        })
                                        ->dehydrated(false),

                                    Select::make('implementer_id')
                                        ->label('Implementer')
                                        ->options(function () {
                                            return \App\Models\User::whereIn('role_id', [4,5])
                                                ->orderBy('name')
                                                ->pluck('name', 'id')
                                                ->toArray();
                                        })
                                        ->required()
                                        ->searchable()
                                        ->placeholder('Select an implementer')
                                        ->default(function (SoftwareHandover $record) {
                                            // ✅ If speaker category is Mandarin, auto-select John Low
                                            if ($record && strtolower($record->speaker_category) === 'mandarin') {
                                                $johnLow = \App\Models\User::whereIn('role_id', [4,5])
                                                    ->where('name', 'LIKE', '%John Low%')
                                                    ->first();

                                                if ($johnLow) {
                                                    \Illuminate\Support\Facades\Log::info("Auto-selecting John Low for Mandarin speaker", [
                                                        'handover_id' => $record->id,
                                                        'john_low_id' => $johnLow->id,
                                                    ]);
                                                    return $johnLow->id;
                                                }
                                            }
                                            return null;
                                        })
                                        ->disabled(function (SoftwareHandover $record) {
                                            // ✅ Make readonly if speaker category is Mandarin
                                            return $record && strtolower($record->speaker_category) === 'mandarin';
                                        })
                                        ->dehydrated(true),
                                ]),

                                Grid::make(2)
                                    ->schema([
                                        \Filament\Forms\Components\Placeholder::make('company_size')
                                            ->label(false)
                                            ->content(function (SoftwareHandover $record) {
                                                $companySizeLabel = $record->headcount_company_size_label ?? 'Unknown';
                                                $headcount = $record->headcount ?? 'N/A';

                                                return new HtmlString(
                                                    '<span style="font-weight: 600; color: #475569; font-size: 14px;">' . 'Company Size: ' .
                                                    $companySizeLabel .
                                                    '</span>'
                                                );
                                            }),

                                        \Filament\Forms\Components\Placeholder::make('project_sequence')
                                            ->label(false)
                                            ->content(function (SoftwareHandover $record) {
                                                return new HtmlString(
                                                    '<span style="font-weight: 600; color: #475569; font-size: 14px;">Project Sequence: ' . '<a href="https://crm.timeteccloud.com/admin/implementer-audit-list"
                                                    target="_blank"
                                                    style="color: #3b82f6; text-decoration: none; font-weight: 500; font-size: 14px; display: inline-flex; align-items: center; gap: 4px;"
                                                    onmouseover="this.style.textDecoration=\'underline\'; this.style.color=\'#2563eb\'"
                                                    onmouseout="this.style.textDecoration=\'none\'; this.style.color=\'#3b82f6\'">
                                                    Click Here
                                                    </a></span>'
                                                );
                                            }),
                                    ]),

                            \Filament\Forms\Components\Section::make('License Configuration Preview')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            \Filament\Forms\Components\Placeholder::make('detected_modules')
                                                ->label('Modules to be Activated')
                                                ->content(function (SoftwareHandover $record) {
                                                    // Get all PI IDs
                                                    $allPiIds = [];
                                                    if (!empty($record->proforma_invoice_product)) {
                                                        $productPis = is_string($record->proforma_invoice_product)
                                                            ? json_decode($record->proforma_invoice_product, true)
                                                            : $record->proforma_invoice_product;
                                                        if (is_array($productPis)) {
                                                            $allPiIds = array_merge($allPiIds, $productPis);
                                                        }
                                                    }
                                                    if (!empty($record->proforma_invoice_hrdf)) {
                                                        $hrdfPis = is_string($record->proforma_invoice_hrdf)
                                                            ? json_decode($record->proforma_invoice_hrdf, true)
                                                            : $record->proforma_invoice_hrdf;
                                                        if (is_array($hrdfPis)) {
                                                            $allPiIds = array_merge($allPiIds, $hrdfPis);
                                                        }
                                                    }

                                                    // Auto-detect which modules will be activated
                                                    $selectedModules = [
                                                        'ta' => $this->shouldModuleBeChecked($record, ['TCL_TA USER-NEW', 'TCL_TA USER-ADDON', 'TCL_TA USER-ADDON(R)', 'TCL_TA USER-RENEWAL', 'TCL_FULL USER-NEW']),
                                                        'tl' => $this->shouldModuleBeChecked($record, ['TCL_LEAVE USER-NEW', 'TCL_LEAVE USER-ADDON', 'TCL_LEAVE USER-ADDON(R)', 'TCL_LEAVE USER-RENEWAL', 'TCL_FULL USER-NEW']),
                                                        'tc' => $this->shouldModuleBeChecked($record, ['TCL_CLAIM USER-NEW', 'TCL_CLAIM USER-ADDON', 'TCL_CLAIM USER-ADDON(R)', 'TCL_CLAIM USER-RENEWAL', 'TCL_FULL USER-NEW']),
                                                        'tp' => $this->shouldModuleBeChecked($record, ['TCL_PAYROLL USER-NEW', 'TCL_PAYROLL USER-ADDON', 'TCL_PAYROLL USER-ADDON(R)', 'TCL_PAYROLL USER-RENEWAL', 'TCL_FULL USER-NEW']),
                                                        'tapp' => $this->shouldModuleBeChecked($record, ['TCL_APPRAISAL USER-NEW']),
                                                        'thire' => $this->shouldModuleBeChecked($record, ['TCL_HIRE-NEW', 'TCL_HIRE-RENEWAL']),
                                                        'tacc' => $this->shouldModuleBeChecked($record, ['TCL_ACCESS-NEW', 'TCL_ACCESS-RENEWAL']),
                                                        'tpbi' => $this->shouldModuleBeChecked($record, ['TCL_POWER BI']),
                                                    ];

                                                    $moduleMapping = [
                                                        'ta' => 'Attendance',
                                                        'tl' => 'Leave',
                                                        'tc' => 'Claim',
                                                        'tp' => 'Payroll',
                                                        'tapp' => 'Appraisal',
                                                        'thire' => 'Hire',
                                                        'tacc' => 'Access',
                                                        'tpbi' => 'PowerBI',
                                                    ];

                                                    $activeModules = [];
                                                    foreach ($selectedModules as $key => $isActive) {
                                                        if ($isActive) {
                                                            $activeModules[] = '<span style="color: #10b981; font-weight: 500;">✓ ' . $moduleMapping[$key] . '</span>';
                                                        }
                                                    }

                                                    if (empty($activeModules)) {
                                                        return new HtmlString('<span style="color: #f59e0b; font-weight: 500;">⚠️ No modules detected - Unlimited buffer license will be created</span>');
                                                    }

                                                    return new HtmlString('<div style="display: flex; flex-wrap: wrap; gap: 8px;">' . implode('</div><div>', $activeModules) . '</div>');
                                                }),

                                            \Filament\Forms\Components\Placeholder::make('seat_limits')
                                                ->label('Seat Limits Configuration')
                                                ->content(function (SoftwareHandover $record) {
                                                    // Get seat limits from quotations
                                                    $licenseService = app(\App\Services\LicenseSeatService::class);

                                                    // Get all PI IDs
                                                    $allPiIds = [];
                                                    if (!empty($record->proforma_invoice_product)) {
                                                        $productPis = is_string($record->proforma_invoice_product)
                                                            ? json_decode($record->proforma_invoice_product, true)
                                                            : $record->proforma_invoice_product;
                                                        if (is_array($productPis)) {
                                                            $allPiIds = array_merge($allPiIds, $productPis);
                                                        }
                                                    }
                                                    if (!empty($record->proforma_invoice_hrdf)) {
                                                        $hrdfPis = is_string($record->proforma_invoice_hrdf)
                                                            ? json_decode($record->proforma_invoice_hrdf, true)
                                                            : $record->proforma_invoice_hrdf;
                                                        if (is_array($hrdfPis)) {
                                                            $allPiIds = array_merge($allPiIds, $hrdfPis);
                                                        }
                                                    }

                                                    $handoverId = $record->formatted_handover_id;

                                                    // ✅ Get seat limits and sum them together for multiple PIs
                                                    $seatLimitsRaw = $licenseService->getSeatLimitsFromQuotations($allPiIds, $handoverId);

                                                    // ✅ Sum up seat limits from multiple PIs
                                                    $seatLimits = [];
                                                    if (!empty($seatLimitsRaw)) {
                                                        foreach ($seatLimitsRaw as $app => $limits) {
                                                            if (is_array($limits)) {
                                                                // If multiple PIs have seat limits for same app, sum them
                                                                $totalSeats = 0;
                                                                $hasSeats = false;

                                                                foreach ($limits as $limit) {
                                                                    if ($limit !== null && is_numeric($limit)) {
                                                                        $totalSeats += (int)$limit;
                                                                        $hasSeats = true;
                                                                    }
                                                                }

                                                                $seatLimits[$app] = $hasSeats ? $totalSeats : null;
                                                            } else {
                                                                // Single PI
                                                                $seatLimits[$app] = $limits;
                                                            }
                                                        }
                                                    }

                                                    if (empty($seatLimits)) {
                                                        return new HtmlString('<span style="color: #6b7280; font-style: italic;">No seat limits detected from quotations</span>');
                                                    }

                                                    $seatInfo = [];
                                                    foreach ($seatLimits as $app => $limit) {
                                                        // ✅ Show "No seats" instead of "Unlimited" when null
                                                        $limitText = ($limit === null || $limit === 0) ? 'No seats' : $limit . ' seats';
                                                        $color = ($limit === null || $limit === 0) ? '#dc2626' : '#059669'; // Red for no seats, green for seats
                                                        $seatInfo[] = '<span style="color: #374151; font-weight: 500;">' . $app . ': <span style="color: ' . $color . ';">' . $limitText . '</span></span>';
                                                    }

                                                    return new HtmlString('<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 8px;">' . implode('</div><div>', $seatInfo) . '</div>');
                                                }),
                                            ]),
                                ])
                                ->collapsible()
                                ->collapsed(false),

                            Select::make('buffer_license_months')
                                ->label('Buffer License Duration')
                                ->options([
                                    '1' => '1 month (Default)',
                                    '2' => '2 months',
                                    '3' => '3 months',
                                ])
                                ->default('1')
                                ->required()
                                ->helperText('This will be the trial period before customer needs to activate paid licenses')
                                ->columnSpanFull(),

                            FileUpload::make('invoice_file')
                                ->label('Upload Invoice')
                                ->disk('public')
                                ->directory('handovers/invoices')
                                ->visibility('public')
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                ->multiple()
                                ->maxFiles(10)
                                ->required()
                                ->openable()
                                ->live() // ✅ Enable live updates without debounce
                                ->reactive() // ✅ Make it reactive to all changes
                                ->afterStateUpdated(function (Set $set, Get $get, ?array $state, ?array $old) {
                                    Log::info("📥 Invoice upload state changed (MARK COMPLETED FORM)", [
                                        'state_is_null' => is_null($state),
                                        'state_is_empty' => empty($state),
                                        'state_type' => gettype($state),
                                        'state_count' => is_array($state) ? count($state) : 0,
                                        'old_count' => is_array($old) ? count($old) : 0,
                                        'state_keys' => is_array($state) ? array_keys($state) : [],
                                        'old_keys' => is_array($old) ? array_keys($old) : [],
                                    ]);

                                    // ✅ IF ALL FILES REMOVED, CLEAR INVOICE NUMBER
                                    if (empty($state)) {
                                        Log::info("🗑️ All files removed - clearing invoice number");
                                        $set('invoice_number', null);

                                        Notification::make()
                                            ->title('Invoice Number Cleared')
                                            ->info()
                                            ->body('All invoice files removed - invoice number has been cleared')
                                            ->send();

                                        return;
                                    }

                                    // ✅ CHECK IF FILES WERE REMOVED (state count < old count)
                                    $oldCount = is_array($old) ? count($old) : 0;
                                    $newCount = is_array($state) ? count($state) : 0;

                                    Log::info("📊 File count comparison", [
                                        'old_count' => $oldCount,
                                        'new_count' => $newCount,
                                        'files_removed' => $oldCount - $newCount
                                    ]);

                                    if ($newCount < $oldCount && $oldCount > 0) {
                                        Log::info("🔄 Files were removed - re-scanning remaining files", [
                                            'old_count' => $oldCount,
                                            'new_count' => $newCount,
                                            'files_removed' => $oldCount - $newCount
                                        ]);

                                        // ✅ RE-SCAN ALL REMAINING FILES
                                        $filePaths = [];
                                        foreach ($state as $key => $file) {
                                            $tempPath = null;

                                            if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                                $tempPath = $file->getRealPath();
                                            } elseif (is_string($file)) {
                                                $tempPath = storage_path('app/livewire-tmp/' . $file);
                                            } elseif (is_object($file) && method_exists($file, 'getRealPath')) {
                                                $tempPath = $file->getRealPath();
                                            }

                                            if ($tempPath && file_exists($tempPath)) {
                                                $filePaths[] = $tempPath;
                                                Log::info("✅ Found remaining file", [
                                                    'path' => $tempPath,
                                                    'size' => filesize($tempPath)
                                                ]);
                                            }
                                        }

                                        if (empty($filePaths)) {
                                            Log::info("🗑️ No valid files remaining after removal");
                                            $set('invoice_number', null);

                                            Notification::make()
                                                ->title('Invoice Number Cleared')
                                                ->info()
                                                ->body('No valid files remaining')
                                                ->send();

                                            return;
                                        }

                                        // ✅ Re-scan and replace invoice numbers
                                        try {
                                            $ocrService = app(\App\Services\InvoiceOcrService::class);
                                            $invoiceNumber = $ocrService->extractInvoiceNumberFromMultipleFiles($filePaths);

                                            if ($invoiceNumber) {
                                                $set('invoice_number', $invoiceNumber);

                                                Notification::make()
                                                    ->title('Invoice Numbers Updated')
                                                    ->success()
                                                    ->body("Found: {$invoiceNumber} from {$newCount} remaining file(s)")
                                                    ->send();

                                                Log::info("✅ Invoice numbers updated after file removal", [
                                                    'invoice_number' => $invoiceNumber,
                                                    'remaining_files' => $newCount
                                                ]);
                                            } else {
                                                $set('invoice_number', null);

                                                Notification::make()
                                                    ->title('No Invoice Numbers Found')
                                                    ->warning()
                                                    ->body('Could not detect invoice numbers in remaining files')
                                                    ->send();

                                                Log::info("⚠️ No invoice numbers found in remaining files");
                                            }
                                        } catch (\Exception $e) {
                                            Log::error("❌ Invoice OCR failed after file removal", [
                                                'error' => $e->getMessage(),
                                                'file_count' => count($filePaths)
                                            ]);
                                        }

                                        return;
                                    }

                                    // ✅ NEW FILES ADDED - Only scan new ones
                                    $oldKeys = is_array($old) ? array_keys($old) : [];
                                    $newKeys = is_array($state) ? array_diff(array_keys($state), $oldKeys) : [];

                                    if (empty($newKeys)) {
                                        Log::info("ℹ️ No new files to scan");
                                        return;
                                    }

                                    Log::info("📄 New files detected for scanning", [
                                        'new_files_count' => count($newKeys),
                                        'total_files' => count($state ?? [])
                                    ]);

                                    // ✅ Collect paths ONLY for NEW files
                                    $filePaths = [];
                                    foreach ($newKeys as $key) {
                                        $file = $state[$key];
                                        $tempPath = null;

                                        if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                            $tempPath = $file->getRealPath();
                                        } elseif (is_string($file)) {
                                            $tempPath = storage_path('app/livewire-tmp/' . $file);
                                        } elseif (is_object($file) && method_exists($file, 'getRealPath')) {
                                            $tempPath = $file->getRealPath();
                                        }

                                        if ($tempPath && file_exists($tempPath)) {
                                            $filePaths[] = $tempPath;
                                            Log::info("✅ Added NEW file for scanning", [
                                                'key' => $key,
                                                'path' => $tempPath,
                                                'size' => filesize($tempPath)
                                            ]);
                                        }
                                    }

                                    if (empty($filePaths)) {
                                        Log::warning("⚠️ No valid file paths extracted from NEW files");
                                        return;
                                    }

                                    try {
                                        $ocrService = app(\App\Services\InvoiceOcrService::class);
                                        $newInvoiceNumbers = $ocrService->extractInvoiceNumberFromMultipleFiles($filePaths);

                                        if ($newInvoiceNumbers) {
                                            $existingInvoiceNumbers = $get('invoice_number');

                                            if (!empty($existingInvoiceNumbers)) {
                                                $existingArray = array_map('trim', explode(',', $existingInvoiceNumbers));
                                                $newArray = array_map('trim', explode(',', $newInvoiceNumbers));
                                                $mergedArray = array_unique(array_merge($existingArray, $newArray));
                                                $finalInvoiceNumbers = implode(', ', $mergedArray);

                                                Log::info("🔗 Appending new invoice numbers to existing", [
                                                    'existing' => $existingInvoiceNumbers,
                                                    'new' => $newInvoiceNumbers,
                                                    'merged' => $finalInvoiceNumbers
                                                ]);
                                            } else {
                                                $finalInvoiceNumbers = $newInvoiceNumbers;
                                            }

                                            $set('invoice_number', $finalInvoiceNumbers);

                                            Notification::make()
                                                ->title('Invoice Numbers Detected')
                                                ->success()
                                                ->body("Found: {$finalInvoiceNumbers}")
                                                ->send();

                                            Log::info("✅ Invoice numbers updated", [
                                                'final_invoice_numbers' => $finalInvoiceNumbers,
                                                'new_files_scanned' => count($filePaths)
                                            ]);
                                        }
                                    } catch (\Exception $e) {
                                        Log::error("❌ Invoice OCR failed", [
                                            'error' => $e->getMessage()
                                        ]);
                                    }
                                })
                                ->dehydrated(true)
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                                    $companyName = Str::slug($get('company_name') ?? 'invoice');
                                    $date = now()->format('Y-m-d');
                                    $random = Str::random(5);
                                    $extension = $file->getClientOriginalExtension();
                                    return "{$companyName}-invoice-{$date}-{$random}.{$extension}";
                                })
                                ->default(function (SoftwareHandover $record) {
                                    if (!$record || !$record->invoice_file) {
                                        return [];
                                    }
                                    if (is_string($record->invoice_file)) {
                                        return json_decode($record->invoice_file, true) ?? [];
                                    }
                                    return is_array($record->invoice_file) ? $record->invoice_file : [];
                                }),

                            TextInput::make('invoice_number')
                                ->label('Detected Invoice Number')
                                ->readOnly()
                                ->reactive()
                                ->dehydrated(true)
                                ->default(fn(SoftwareHandover $record) => $record->invoice_number ?? null)
                                ->suffixAction(
                                    \Filament\Forms\Components\Actions\Action::make('refresh_invoice_number')
                                        ->icon('heroicon-o-arrow-path')
                                        ->label('Refresh')
                                        ->color('primary')
                                        ->action(function (Set $set, Get $get) {
                                            $invoiceFiles = $get('invoice_file');

                                            Log::info("🔄 Manual refresh invoice number triggered", [
                                                'files_count' => is_array($invoiceFiles) ? count($invoiceFiles) : 0,
                                                'files_type' => gettype($invoiceFiles)
                                            ]);

                                            if (empty($invoiceFiles)) {
                                                Notification::make()
                                                    ->title('No Invoices to Scan')
                                                    ->warning()
                                                    ->body('Please upload invoice files first')
                                                    ->send();
                                                return;
                                            }

                                            // ✅ Collect paths for ALL uploaded files
                                            $filePaths = [];

                                            if (is_array($invoiceFiles)) {
                                                foreach ($invoiceFiles as $key => $file) {
                                                    $tempPath = null;

                                                    if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                                        $tempPath = $file->getRealPath();
                                                    } elseif (is_string($file)) {
                                                        $tempPath = storage_path('app/livewire-tmp/' . $file);
                                                    } elseif (is_object($file) && method_exists($file, 'getRealPath')) {
                                                        $tempPath = $file->getRealPath();
                                                    }

                                                    if ($tempPath && file_exists($tempPath)) {
                                                        $filePaths[] = $tempPath;
                                                        Log::info("✅ Found file for refresh scan", [
                                                            'key' => $key,
                                                            'path' => $tempPath,
                                                            'size' => filesize($tempPath)
                                                        ]);
                                                    }
                                                }
                                            }

                                            if (empty($filePaths)) {
                                                Log::warning("⚠️ No valid file paths found for refresh");

                                                Notification::make()
                                                    ->title('No Valid Files')
                                                    ->warning()
                                                    ->body('Could not find valid invoice files to scan')
                                                    ->send();
                                                return;
                                            }

                                            Log::info("🔍 Starting manual refresh scan", [
                                                'total_files' => count($filePaths)
                                            ]);

                                            try {
                                                $ocrService = app(\App\Services\InvoiceOcrService::class);
                                                $invoiceNumbers = $ocrService->extractInvoiceNumberFromMultipleFiles($filePaths);

                                                if ($invoiceNumbers) {
                                                    $set('invoice_number', $invoiceNumbers);

                                                    Notification::make()
                                                        ->title('Invoice Numbers Refreshed')
                                                        ->success()
                                                        ->body("Found: {$invoiceNumbers} (scanned " . count($filePaths) . " file(s))")
                                                        ->send();

                                                    Log::info("✅ Manual refresh completed successfully", [
                                                        'invoice_numbers' => $invoiceNumbers,
                                                        'files_scanned' => count($filePaths)
                                                    ]);
                                                } else {
                                                    $set('invoice_number', null);

                                                    Notification::make()
                                                        ->title('No Invoice Numbers Found')
                                                        ->warning()
                                                        ->body('Could not detect invoice numbers in the uploaded files')
                                                        ->send();

                                                    Log::warning("⚠️ Manual refresh found no invoice numbers", [
                                                        'files_scanned' => count($filePaths)
                                                    ]);
                                                }
                                            } catch (\Exception $e) {
                                                Log::error("❌ Manual refresh OCR failed", [
                                                    'error' => $e->getMessage(),
                                                    'trace' => $e->getTraceAsString()
                                                ]);

                                                Notification::make()
                                                    ->title('Scan Failed')
                                                    ->danger()
                                                    ->body('Error: ' . $e->getMessage())
                                                    ->send();
                                            }
                                        })
                                ),
                        ])
                        ->action(function (SoftwareHandover $record, array $data): void {
                            // Handle invoice file encoding
                            if (isset($data['invoice_file']) && is_array($data['invoice_file'])) {
                                $existingInvoiceFiles = [];
                                if ($record->invoice_file) {
                                    if (is_string($record->invoice_file)) {
                                        $existingInvoiceFiles = json_decode($record->invoice_file, true) ?? [];
                                    } else if (is_array($record->invoice_file)) {
                                        $existingInvoiceFiles = $record->invoice_file;
                                    }
                                }
                                $mergedInvoiceFiles = array_merge($existingInvoiceFiles, $data['invoice_file']);
                                $data['invoice_file'] = json_encode($mergedInvoiceFiles);
                            }

                            // Get implementer info
                            $implementerId = $data['implementer_id'];
                            $implementer = \App\Models\User::find($implementerId);
                            $implementerName = $implementer?->name ?? 'Unknown';
                            $implementerEmail = $implementer?->email ?? null;

                            // Get salesperson info
                            $salespersonId = $record->lead->salesperson ?? null;
                            $salesperson = \App\Models\User::find($salespersonId);
                            $salespersonEmail = $salesperson?->email ?? null;
                            $salespersonName = $salesperson?->name ?? 'Unknown Salesperson';

                            // Log implementer assignment
                            ImplementerLogs::create([
                                'lead_id' => $record->lead_id,
                                'description' => 'NEW PROJECT ASSIGNMENT',
                                'subject_id' => $record->id,
                                'causer_id' => auth()->id(),
                                'remark' => "Project assigned to {$implementerName} for {$record->company_name}",
                            ]);

                            // ✅ Get buffer license duration
                            $bufferLicenseMonths = (int)($data['buffer_license_months'] ?? 1);

                            // ✅ AUTO-DETECT MODULES & SEATS FROM QUOTATIONS (BACKEND ONLY)
                            $licenseService = app(\App\Services\LicenseSeatService::class);
                            $handoverId = $record->formatted_handover_id;

                            // Get all PI IDs
                            $allPiIds = [];
                            if (!empty($record->proforma_invoice_product)) {
                                $productPis = is_string($record->proforma_invoice_product)
                                    ? json_decode($record->proforma_invoice_product, true)
                                    : $record->proforma_invoice_product;
                                if (is_array($productPis)) {
                                    $allPiIds = array_merge($allPiIds, $productPis);
                                }
                            }
                            if (!empty($record->proforma_invoice_hrdf)) {
                                $hrdfPis = is_string($record->proforma_invoice_hrdf)
                                    ? json_decode($record->proforma_invoice_hrdf, true)
                                    : $record->proforma_invoice_hrdf;
                                if (is_array($hrdfPis)) {
                                    $allPiIds = array_merge($allPiIds, $hrdfPis);
                                }
                            }

                            // ✅ Auto-detect which modules are purchased
                            $selectedModules = [
                                'ta' => $this->shouldModuleBeChecked($record, ['TCL_TA USER-NEW', 'TCL_TA USER-ADDON', 'TCL_TA USER-ADDON(R)', 'TCL_TA USER-RENEWAL', 'TCL_FULL USER-NEW']),
                                'tl' => $this->shouldModuleBeChecked($record, ['TCL_LEAVE USER-NEW', 'TCL_LEAVE USER-ADDON', 'TCL_LEAVE USER-ADDON(R)', 'TCL_LEAVE USER-RENEWAL', 'TCL_FULL USER-NEW']),
                                'tc' => $this->shouldModuleBeChecked($record, ['TCL_CLAIM USER-NEW', 'TCL_CLAIM USER-ADDON', 'TCL_CLAIM USER-ADDON(R)', 'TCL_CLAIM USER-RENEWAL', 'TCL_FULL USER-NEW']),
                                'tp' => $this->shouldModuleBeChecked($record, ['TCL_PAYROLL USER-NEW', 'TCL_PAYROLL USER-ADDON', 'TCL_PAYROLL USER-ADDON(R)', 'TCL_PAYROLL USER-RENEWAL', 'TCL_FULL USER-NEW']),
                                'tapp' => $this->shouldModuleBeChecked($record, ['TCL_APPRAISAL USER-NEW']),
                                'thire' => $this->shouldModuleBeChecked($record, ['TCL_HIRE-NEW', 'TCL_HIRE-RENEWAL']),
                                'tacc' => $this->shouldModuleBeChecked($record, ['TCL_ACCESS-NEW', 'TCL_ACCESS-RENEWAL']),
                                'tpbi' => $this->shouldModuleBeChecked($record, ['TCL_POWER BI']),
                            ];

                            // ✅ Auto-detect seat limits from quotations
                            $seatLimits = $licenseService->getSeatLimitsFromQuotations($allPiIds, $handoverId);

                            // ✅ Build buffer seat limits array (only for selected modules)
                            $bufferSeatLimits = [];
                            $moduleMapping = [
                                'ta' => 'Attendance',
                                'tl' => 'Leave',
                                'tc' => 'Claim',
                                'tp' => 'Payroll',
                                'tapp' => 'Appraisal',
                                'thire' => 'Hire',
                                'tacc' => 'Access',
                                'tpbi' => 'PowerBI',
                            ];

                            foreach ($moduleMapping as $key => $appName) {
                                if ($selectedModules[$key]) {
                                    // Use seat limit from quotation if exists, otherwise null (unlimited)
                                    $bufferSeatLimits[$appName] = $seatLimits[$appName] ?? null;
                                }
                            }

                            // Prepare update data
                            $updateData = [
                                'project_priority' => 'High',
                                'status' => 'Completed',
                                'completed_at' => now(),
                                'implementer' => $implementerName,
                                'ta' => $selectedModules['ta'],
                                'tl' => $selectedModules['tl'],
                                'tc' => $selectedModules['tc'],
                                'tp' => $selectedModules['tp'],
                                'tapp' => $selectedModules['tapp'],
                                'thire' => $selectedModules['thire'],
                                'tacc' => $selectedModules['tacc'],
                                'tpbi' => $selectedModules['tpbi'],
                                'follow_up_date' => now(),
                                'follow_up_counter' => true,
                                'invoice_number' => $data['invoice_number'] ?? null,
                            ];

                            if (isset($data['invoice_file'])) {
                                $updateData['invoice_file'] = $data['invoice_file'];
                            }

                            // Update the record
                            $record->update($updateData);

                            // Create CRM Account
                            $crmResult = $this->createCRMAccount($record, $handoverId);

                            // ✅ IF HRV2 AND CRM ACCOUNT CREATED, ADD BUFFER LICENSES WITH AUTO-DETECTED MODULES & SEATS
                            if ($record->hr_version == 2 && $crmResult['success']) {
                                $accountId = $crmResult['data']['accountId'] ?? $record->hr_account_id;
                                $companyId = $crmResult['data']['companyId'] ?? $record->hr_company_id;

                                if ($accountId && $companyId) {
                                    // ✅ Check if any modules were detected
                                    $hasSelectedModules = !empty(array_filter($selectedModules));

                                    if ($hasSelectedModules) {
                                        Log::info("Creating buffer license with AUTO-DETECTED modules and seat limits", [
                                            'handover_id' => $handoverId,
                                            'buffer_months' => $bufferLicenseMonths,
                                            'selected_modules' => array_keys(array_filter($selectedModules)),
                                            'seat_limits' => $bufferSeatLimits,
                                        ]);

                                        // ✅ Add buffer license with SELECTED modules and seat limits
                                        $bufferResult = $licenseService->addBufferLicenses(
                                            $record,
                                            $accountId,
                                            $companyId,
                                            $selectedModules,
                                            $handoverId,
                                            null, // start date
                                            null, // end date
                                            $bufferLicenseMonths
                                        );
                                    } else {
                                        Log::info("No modules detected - creating unlimited buffer license", [
                                            'handover_id' => $handoverId,
                                            'buffer_months' => $bufferLicenseMonths,
                                        ]);

                                        // ✅ Fallback: Create unlimited buffer license
                                        $bufferResult = $licenseService->addBufferLicenseUnlimited(
                                            $record,
                                            $accountId,
                                            $companyId,
                                            $handoverId,
                                            $bufferLicenseMonths
                                        );
                                    }

                                    // ✅ Create license certificate
                                    $companyName = $record->company_name ?? $record->lead->companyDetail->company_name ?? 'Unknown Company';
                                    $bufferStartDate = now();
                                    $bufferEndDate = now()->copy()->addMonths($bufferLicenseMonths)->subDay();

                                    // Calculate total paid months for reference
                                    $totalPaidMonths = 12;
                                    if (!empty($allPiIds)) {
                                        $licensePeriods = $licenseService->getLicensePeriodsFromQuotations($allPiIds, $handoverId);
                                        foreach ($licensePeriods as $period) {
                                            if ($period['subscription_period'] > $totalPaidMonths) {
                                                $totalPaidMonths = $period['subscription_period'];
                                            }
                                        }
                                    }

                                    $certificate = \App\Models\LicenseCertificate::create([
                                        'company_name' => $companyName,
                                        'software_handover_id' => $record->id,
                                        'buffer_license_set_id' => $bufferResult['results']['data']['licenseSetId'] ?? null,
                                        'buffer_license_data' => $bufferResult['results']['data'] ?? null,
                                        'kick_off_date' => $record->kick_off_meeting ?? now(),
                                        'buffer_license_start' => $bufferStartDate,
                                        'buffer_license_end' => $bufferEndDate,
                                        'buffer_months' => $bufferLicenseMonths,
                                        'buffer_seat_limits' => !empty($bufferSeatLimits) ? $bufferSeatLimits : null,
                                        'paid_license_start' => null,
                                        'paid_license_end' => null,
                                        'paid_months' => $totalPaidMonths,
                                        'next_renewal_date' => null,
                                        'license_years' => 0,
                                        'status' => 'buffer_only',
                                        'created_by' => auth()->id(),
                                        'updated_by' => auth()->id(),
                                    ]);

                                    $record->update([
                                        'license_certification_id' => $certificate->id,
                                        'license_activated_at' => now(),
                                    ]);

                                    $certificateId = 'LC_' . str_pad($certificate->id, 4, '0', STR_PAD_LEFT);

                                    if ($bufferResult['success']) {
                                        $moduleList = implode(', ', array_map(function($key) use ($moduleMapping) {
                                            return $moduleMapping[$key] ?? $key;
                                        }, array_keys(array_filter($selectedModules))));

                                        $seatInfo = [];
                                        foreach ($bufferSeatLimits as $app => $limit) {
                                            $seatInfo[] = "{$app}: " . ($limit === null ? 'unlimited' : $limit);
                                        }
                                        $seatInfoStr = !empty($seatInfo) ? implode(', ', $seatInfo) : 'unlimited for all';

                                        Notification::make()
                                            ->title('HRV2 Buffer License Activated')
                                            ->success()
                                            ->body("Certificate {$certificateId} created with {$bufferLicenseMonths} month(s) trial.\n\nModules: {$moduleList}\n\nSeats: {$seatInfoStr}")
                                            ->send();

                                        Log::info("Buffer license activated with auto-detected config", [
                                            'handover_id' => $handoverId,
                                            'certificate_id' => $certificateId,
                                            'buffer_months' => $bufferLicenseMonths,
                                            'modules' => $moduleList,
                                            'seat_limits' => $bufferSeatLimits,
                                        ]);
                                    } else {
                                        Notification::make()
                                            ->title('Buffer License Activation Failed')
                                            ->danger()
                                            ->body("Failed to activate buffer license: " . ($bufferResult['error'] ?? 'Unknown error'))
                                            ->send();

                                        Log::error("Buffer license activation failed", [
                                            'handover_id' => $handoverId,
                                            'error' => $bufferResult['error'] ?? 'Unknown error'
                                        ]);
                                    }
                                }
                            }

                            // Send notification emails
                            $this->sendHandoverNotificationEmail($record, $implementerName, $implementerEmail, $salespersonName, $salespersonEmail);
                            $this->sendCustomerActivationEmails($record, $implementerName, $implementerEmail);

                            Notification::make()
                                ->title('Software Handover Completed')
                                ->body("Handover marked as completed and assigned to {$implementerName}")
                                ->success()
                                ->send();
                        })
                        ->modalHeading('Complete Software Handover')
                        ->hidden(
                            fn(SoftwareHandover $record): bool =>
                            $record->status !== 'New' || auth()->user()->role_id === 2
                        ),

                    Action::make('convert_to_draft')
                        ->label('Convert to Draft')
                        ->icon('heroicon-o-document')
                        ->color('warning')
                        ->visible(fn(SoftwareHandover $record): bool => $record->status === 'Rejected')
                        ->action(function (SoftwareHandover $record): void {
                            $record->update([
                                'status' => 'Draft'
                            ]);

                            Notification::make()
                                ->title('Handover converted to draft')
                                ->success()
                                ->send();
                        }),
                ])->button()

            ]);
    }

    /**
     * Create CRM account for the handover
     */
    protected function createCRMAccount(SoftwareHandover $record, string $handoverId)
    {
        try {
            // ✅ CHECK FOR EXISTING SOFTWARE HANDOVER WITH CRM ACCOUNT
            $existingHandover = SoftwareHandover::where('lead_id', $record->lead_id)
                ->whereNotNull('hr_company_id')
                ->whereNotNull('hr_account_id')
                ->whereNotNull('hr_user_id')
                ->where('id', '!=', $record->id) // Exclude current record
                ->first();

            // ✅ IF EXISTING HANDOVER FOUND, REUSE CRM ACCOUNT
            if ($existingHandover) {
                \Illuminate\Support\Facades\Log::info("Reusing existing HRV2 account for handover", [
                    'new_handover_id' => $handoverId,
                    'existing_handover_id' => $existingHandover->id,
                    'lead_id' => $record->lead_id,
                    'hr_account_id' => $existingHandover->hr_account_id,
                    'hr_company_id' => $existingHandover->hr_company_id,
                    'hr_user_id' => $existingHandover->hr_user_id
                ]);

                // Update current record with existing CRM account details
                $record->update([
                    'hr_account_id' => $existingHandover->hr_account_id,
                    'hr_company_id' => $existingHandover->hr_company_id,
                    'hr_user_id' => $existingHandover->hr_user_id,
                ]);

                Notification::make()
                    ->title('Existing HRV2 Account Found')
                    ->success()
                    ->body("Reusing existing HRV2 account from previous handover (ID: {$existingHandover->formatted_handover_id})")
                    ->send();

                return [
                    'success' => true,
                    'reused' => true,
                    'data' => [
                        'accountId' => $existingHandover->hr_account_id,
                        'companyId' => $existingHandover->hr_company_id,
                        'userId' => $existingHandover->hr_user_id,
                    ]
                ];
            }

            // ✅ NO EXISTING ACCOUNT FOUND - CREATE NEW ONE
            \Illuminate\Support\Facades\Log::info("No existing HRV2 account found - creating new account", [
                'handover_id' => $handoverId,
                'lead_id' => $record->lead_id
            ]);

            $lead = $record->lead;

            // Get country details
            $countryService = app(\App\Services\CountryService::class);
            $countries = $countryService->getCountries();
            $leadCountry = $lead->country ?? 'Malaysia';
            $countryData = collect($countries)->firstWhere('name', $leadCountry);

            if (!$countryData) {
                $countryData = collect($countries)->firstWhere('id', 132); // Fallback to Malaysia
            }

            // Get or generate customer credentials
            $credentials = $this->getOrCreateCustomerCredentials($record, $handoverId);

            // Process phone number from implementation PICs
            $phoneData = $this->processPhoneNumber($record, $countryData, $handoverId);

            // ✅ Use the SAME password from customer credentials
            $crmAccountData = [
                'company_name' => $record->company_name,
                'country_id' => (int)$countryData['id'],
                'name' => $credentials['name'],
                'email' => $credentials['email'],
                'password' => $credentials['password'], // ✅ SAME password as customer portal
                'phone_code' => $phoneData['phone_code'],
                'phone' => $phoneData['clean_phone'],
                'timezone' => $countryData['timezone'] ?? 'Asia/Kuala_Lumpur',
            ];

            \Illuminate\Support\Facades\Log::info("Calling HRV2 API with customer credentials", [
                'handover_id' => $handoverId,
                'company_name' => $crmAccountData['company_name'],
                'email' => $crmAccountData['email'],
                'phone_code' => $crmAccountData['phone_code'],
                'password_length' => strlen($crmAccountData['password']),
                'phone' => $crmAccountData['phone'],
                'country_id' => $crmAccountData['country_id'],
                'timezone' => $crmAccountData['timezone'],
                'password_source' => $credentials['customer'] ? 'existing_customer' : 'newly_generated'
            ]);

            // Create account via CRM API
            $crmService = app(\App\Services\CRMApiService::class);
            $crmResult = $crmService->createAccount($crmAccountData);

            if ($crmResult['success']) {
                $this->saveCRMAccountData($record, $crmResult['data'], $credentials, $phoneData['raw_phone']);

                Notification::make()
                    ->title('New CRM Account Created Successfully')
                    ->success()
                    ->body("Account ID: {$crmResult['data']['accountId']} | Company ID: {$crmResult['data']['companyId']}")
                    ->send();
            } else {
                \Illuminate\Support\Facades\Log::error("HRV2 Account creation failed", [
                    'handover_id' => $handoverId,
                    'error' => $crmResult['error'],
                    'status' => $crmResult['status'] ?? 'unknown'
                ]);

                Notification::make()
                    ->title('HRV2 Account Creation Failed')
                    ->warning()
                    ->body($crmResult['error'] ?: 'Unknown error occurred')
                    ->send();
            }

            return $crmResult;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("HRV2 Account creation exception", [
                'handover_id' => $handoverId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Notification::make()
                ->title('HRV2 Account Creation Error')
                ->danger()
                ->body($e->getMessage())
                ->send();

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get existing customer credentials or generate new ones
     */
    protected function getOrCreateCustomerCredentials(SoftwareHandover $record, string $handoverId): array
    {
        $customer = \App\Models\Customer::where('lead_id', $record->lead_id)->first();
        $activationController = app(\App\Http\Controllers\CustomerActivationController::class);

        if ($customer) {
            \Illuminate\Support\Facades\Log::info("Using existing customer credentials", [
                'handover_id' => $handoverId,
                'customer_id' => $customer->id,
                'email' => $customer->email,
                'password_exists' => !empty($customer->plain_password),
                'password_length' => $customer->plain_password ? strlen($customer->plain_password) : 0
            ]);

            // ✅ IMPORTANT: Use the SAME password from customer table
            return [
                'email' => $customer->email,
                'password' => $customer->plain_password, // ✅ Use existing customer password
                'name' => $customer->name,
                'customer' => $customer,
            ];
        }

        // ✅ Generate new credentials (this will now CREATE the customer)
        $credentials = $activationController->generateCRMAccountCredentials(
            $record->lead_id,
            $handoverId
        );

        \Illuminate\Support\Facades\Log::info("New customer created via generateCRMAccountCredentials", [
            'handover_id' => $handoverId,
            'email' => $credentials['email'],
            'name' => $credentials['name'],
            'password_length' => strlen($credentials['password']),
            'customer_was_created' => !($credentials['customer_exists'] ?? false)
        ]);

        // ✅ Fetch the newly created customer record
        $customer = \App\Models\Customer::where('lead_id', $record->lead_id)->first();

        // ✅ Return credentials with customer object
        return [
            'email' => $credentials['email'],
            'password' => $credentials['password'], // ✅ Use password from newly created customer
            'name' => $credentials['name'],
            'customer' => $customer, // ✅ Pass the customer object
        ];
    }

    /**
     * Process phone number from implementation PICs
     */
    protected function processPhoneNumber(SoftwareHandover $record, array $countryData, string $handoverId): array
    {
        $implementationPics = json_decode($record->implementation_pics, true);

        if (!is_array($implementationPics) || empty($implementationPics)) {
            throw new \Exception("No implementation PICs found for handover {$handoverId}");
        }

        $rawPhone = $implementationPics[0]['pic_phone_impl'] ?? null;
        $firstPicName = $implementationPics[0]['pic_name_impl'] ?? null;

        if (!$rawPhone) {
            throw new \Exception("No phone number found in implementation PICs for handover {$handoverId}");
        }

        \Illuminate\Support\Facades\Log::info("Raw phone from PIC", [
            'handover_id' => $handoverId,
            'pic_name' => $firstPicName,
            'raw_phone' => $rawPhone,
        ]);

        // Clean phone number - remove ALL non-numeric characters
        $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);

        // Get country code digits (e.g., "+60" -> "60")
        $phoneCode = $countryData['phone_code'];
        $phoneCodeDigits = preg_replace('/[^0-9]/', '', $phoneCode);

        // Remove country code if present at the start
        if (substr($cleanPhone, 0, strlen($phoneCodeDigits)) === $phoneCodeDigits) {
            $cleanPhone = substr($cleanPhone, strlen($phoneCodeDigits));
        }

        // Remove leading zeros
        $cleanPhone = ltrim($cleanPhone, '0');

        // Validate phone length
        if (strlen($cleanPhone) < 7 || strlen($cleanPhone) > 11) {
            \Illuminate\Support\Facades\Log::warning("Phone number length unusual", [
                'handover_id' => $handoverId,
                'raw_phone' => $rawPhone,
                'clean_phone' => $cleanPhone,
                'length' => strlen($cleanPhone)
            ]);
        }

        \Illuminate\Support\Facades\Log::info("Phone number processed", [
            'handover_id' => $handoverId,
            'raw_phone' => $rawPhone,
            'phone_code' => $phoneCode,
            'clean_phone' => $cleanPhone,
        ]);

        return [
            'raw_phone' => $rawPhone,
            'clean_phone' => $cleanPhone,
            'phone_code' => $phoneCode,
            'pic_name' => $firstPicName,
        ];
    }

    /**
     * Save CRM account data to database
     */
    protected function saveCRMAccountData(SoftwareHandover $record, array $crmData, array $credentials, string $rawPhone): void
    {
        $lead = $record->lead;

        // Update software_handover table
        $record->update([
            'hr_account_id' => $crmData['accountId'] ?? null,
            'hr_company_id' => $crmData['companyId'] ?? null,
            'hr_user_id' => $crmData['userId'] ?? null,
        ]);

        // ✅ Get customer (should already exist from generateCRMAccountCredentials or getOrCreateCustomerCredentials)
        $customer = $credentials['customer'];

        if (!$customer) {
            // ✅ Fallback: try to find customer by lead_id
            $customer = \App\Models\Customer::where('lead_id', $record->lead_id)->first();

            \Illuminate\Support\Facades\Log::warning("Customer not passed in credentials - found via lead_id lookup", [
                'lead_id' => $record->lead_id,
                'customer_id' => $customer ? $customer->id : null
            ]);
        }

        if ($customer) {
            // ✅ Update existing customer with CRM account details
            $customer->update([
                'hr_account_id' => $crmData['accountId'] ?? null,
                'hr_company_id' => $crmData['companyId'] ?? null,
                'hr_user_id' => $crmData['userId'] ?? null,
                'sw_id' => $record->id, // Link to software handover
                'phone' => $rawPhone, // Update phone if needed
                'status' => 'active', // Activate customer
                'email_verified_at' => now(), // Mark as verified
                // ✅ Password is NOT updated - it remains the same
            ]);

            \Illuminate\Support\Facades\Log::info("Updated existing customer with CRM account details", [
                'customer_id' => $customer->id,
                'email' => $customer->email,
                'plain_password' => $customer->plain_password, // ✅ Log to verify password unchanged
                'password_unchanged' => true,
                'hrv2_account_linked' => true
            ]);
        } else {
            // ✅ This should NEVER happen now, but keep as safety fallback
            \Illuminate\Support\Facades\Log::error("CRITICAL: Customer not found after all checks - this should not happen!", [
                'lead_id' => $record->lead_id,
                'handover_id' => $record->id,
                'credentials_had_customer' => isset($credentials['customer'])
            ]);

            // Last resort: create customer
            \App\Models\Customer::create([
                'name' => $credentials['name'],
                'email' => $credentials['email'],
                'original_email' => $lead->companyDetail->email ?? $lead->email ?? $credentials['email'],
                'lead_id' => $lead->id,
                'sw_id' => $record->id,
                'company_name' => $record->company_name,
                'phone' => $rawPhone,
                'password' => \Illuminate\Support\Facades\Hash::make($credentials['password']),
                'plain_password' => $credentials['password'],
                'status' => 'active',
                'email_verified_at' => now(),
                'hr_account_id' => $crmData['accountId'] ?? null,
                'hr_company_id' => $crmData['companyId'] ?? null,
                'hr_user_id' => $crmData['userId'] ?? null,
            ]);

            \Illuminate\Support\Facades\Log::warning("Created customer as emergency fallback", [
                'email' => $credentials['email'],
                'lead_id' => $lead->id
            ]);
        }

        \Illuminate\Support\Facades\Log::info("HRV2 Account data saved successfully", [
            'software_handover_id' => $record->id,
            'account_id' => $crmData['accountId'],
            'company_id' => $crmData['companyId'],
            'user_id' => $crmData['userId'],
        ]);
    }

    /**
     * Send handover notification email to implementer and salesperson
     */
    protected function sendHandoverNotificationEmail(SoftwareHandover $record, string $implementerName, ?string $implementerEmail, string $salespersonName, ?string $salespersonEmail): void
    {
        try {
            $companyName = $record->company_name ?? $record->lead->companyDetail->company_name ?? 'Unknown Company';
            $handoverId = $record->formatted_handover_id;
            $handoverFormUrl = $record->handover_pdf ? url('storage/' . $record->handover_pdf) : null;

            // Get invoice files
            $invoiceFiles = [];
            if ($record->invoice_file) {
                $invoiceFileArray = is_string($record->invoice_file)
                    ? json_decode($record->invoice_file, true)
                    : $record->invoice_file;

                if (is_array($invoiceFileArray)) {
                    foreach ($invoiceFileArray as $file) {
                        $invoiceFiles[] = url('storage/' . $file);
                    }
                }
            }

            $emailContent = [
                'implementer' => ['name' => $implementerName],
                'company' => ['name' => $companyName],
                'salesperson' => ['name' => $salespersonName],
                'handover_id' => $handoverId,
                'createdAt' => $record->completed_at ? \Carbon\Carbon::parse($record->completed_at)->format('d M Y') : now()->format('d M Y'),
                'handoverFormUrl' => $handoverFormUrl,
                'invoiceFiles' => $invoiceFiles,
            ];

            $recipients = [];

            // Only add implementer email if it exists and is valid
            if ($implementerEmail && filter_var($implementerEmail, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = $implementerEmail;
            }

            // Only add salesperson email if it exists and is valid
            if ($salespersonEmail && filter_var($salespersonEmail, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = $salespersonEmail;
            }

            // Only send email if we have at least one valid recipient
            if (count($recipients) > 0) {
                $authUser = auth()->user();
                $senderEmail = $authUser->email;
                $senderName = $authUser->name;

                \Illuminate\Support\Facades\Mail::send('emails.handover_notification', ['emailContent' => $emailContent], function ($message) use ($recipients, $senderEmail, $senderName, $handoverId, $companyName) {
                    $message->from($senderEmail, $senderName)
                        ->to($recipients)
                        ->subject("SOFTWARE HANDOVER ID {$handoverId} | {$companyName}");
                });

                \Illuminate\Support\Facades\Log::info("Handover notification email sent successfully", [
                    'recipients' => $recipients,
                    'handover_id' => $handoverId
                ]);
            } else {
                \Illuminate\Support\Facades\Log::info("No valid email recipients found - skipping email", [
                    'handover_id' => $handoverId,
                    'implementer_email' => $implementerEmail,
                    'salesperson_email' => $salespersonEmail
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Handover notification email failed", [
                'handover_id' => $handoverId ?? 'unknown',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send customer portal activation emails to implementation PICs
     */
    protected function sendCustomerActivationEmails(SoftwareHandover $record, string $implementerName, string $implementerEmail): void
    {
        try {
            $pics = [];
            if (is_string($record->implementation_pics)) {
                $pics = json_decode($record->implementation_pics, true) ?? [];
            } elseif (is_array($record->implementation_pics)) {
                $pics = $record->implementation_pics;
            }

            $picEmails = [];
            foreach ($pics as $pic) {
                if (!empty($pic['pic_email_impl']) && filter_var($pic['pic_email_impl'], FILTER_VALIDATE_EMAIL)) {
                    $picEmails[] = $pic['pic_email_impl'];
                }
            }

            if (empty($picEmails)) {
                $handoverId = $record->formatted_handover_id;
                \Illuminate\Support\Facades\Log::warning("No valid PIC emails found for handover {$handoverId}");
                return;
            }

            $handoverId = $record->formatted_handover_id;
            $activationController = app(\App\Http\Controllers\CustomerActivationController::class);

            $activationController->sendGroupActivationEmail(
                $record->lead_id,
                $picEmails,
                $implementerEmail,
                $implementerName,
                $handoverId
            );

            Notification::make()
                ->title('Customer Portal Activation Emails Sent')
                ->success()
                ->body('Emails sent to: ' . implode(', ', $picEmails))
                ->send();

            activity()
                ->causedBy(auth()->user())
                ->performedOn($record)
                ->withProperties([
                    'emails' => $picEmails,
                    'implementer' => $implementerName,
                    'handover_id' => $handoverId
                ])
                ->log('Customer portal activation emails sent');

        } catch (\Exception $e) {
            Notification::make()
                ->title('Customer Portal Activation Error')
                ->danger()
                ->body('Failed to send emails: ' . $e->getMessage())
                ->send();

            \Illuminate\Support\Facades\Log::error('Customer activation emails failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function shouldModuleBeChecked(SoftwareHandover $record, array $productCodes): bool
    {
        // Get all PI IDs from proforma_invoice_product and proforma_invoice_hrdf
        $allPiIds = [];

        if (!empty($record->proforma_invoice_product)) {
            $productPis = is_string($record->proforma_invoice_product)
                ? json_decode($record->proforma_invoice_product, true)
                : $record->proforma_invoice_product;
            if (is_array($productPis)) {
                $allPiIds = array_merge($allPiIds, $productPis);
            }
        }

        if (!empty($record->proforma_invoice_hrdf)) {
            $hrdfPis = is_string($record->proforma_invoice_hrdf)
                ? json_decode($record->proforma_invoice_hrdf, true)
                : $record->proforma_invoice_hrdf;
            if (is_array($hrdfPis)) {
                $allPiIds = array_merge($allPiIds, $hrdfPis);
            }
        }

        if (empty($allPiIds)) {
            return false;
        }

        // Get quotation details for these PIs
        $quotations = \App\Models\Quotation::whereIn('id', $allPiIds)->get();

        foreach ($quotations as $quotation) {
            $details = \App\Models\QuotationDetail::where('quotation_id', $quotation->id)
                ->with('product')
                ->get();

            foreach ($details as $detail) {
                if (!$detail->product) {
                    continue;
                }

                // Check if this product code matches any of the module's product codes
                if (in_array($detail->product->code, $productCodes)) {
                    \Illuminate\Support\Facades\Log::info("Module auto-checked based on quotation", [
                        'product_code' => $detail->product->code,
                        'pi_reference' => $quotation->pi_reference_no,
                        'handover_id' => $record->id
                    ]);
                    return true;
                }
            }
        }

        return false;
    }

    public function render()
    {
        return view('livewire.salesperson_dashboard.software-handover-v2-new');
    }
}
