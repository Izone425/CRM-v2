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

                        // Format ID with 250 prefix and pad with zeros to ensure at least 3 digits
                        return 'SW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
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
                            // Format ID with prefix 250 and pad with zeros to ensure at least 3 digits
                            $formattedId = 'SW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
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
                                                            // Get lead ID directly from the record
                                                            $leadId = $record->lead_id;
                                                            // Format ID with prefix (250) and padding
                                                            $formattedId = '250' . str_pad($leadId, 3, '0', STR_PAD_LEFT);
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
                                                    // Get lead ID directly from the record
                                                    $leadId = $record->lead_id;
                                                    // Format ID with prefix (250) and padding
                                                    $formattedId = '250' . str_pad($leadId, 3, '0', STR_PAD_LEFT);
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
                                                    // Get lead ID directly from the record
                                                    $leadId = $record->lead_id;
                                                    // Format ID with prefix (250) and padding
                                                    $formattedId = '250' . str_pad($leadId, 3, '0', STR_PAD_LEFT);
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
                                                    // Get lead ID directly from the record
                                                    $leadId = $record->lead_id;
                                                    // Format ID with prefix (250) and padding
                                                    $formattedId = '250' . str_pad($leadId, 3, '0', STR_PAD_LEFT);
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
                                                ->helperText('Upload invoice files (PDF, JPG, PNG formats accepted)')
                                                ->openable()
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

                            $handoverId = 'SW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);

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
                                        ->placeholder('Select an implementer'),
                                ]),

                            Grid::make(1)
                                ->schema([
                                    Section::make('Module Selection')
                                        ->schema([
                                            Grid::make(2)
                                                ->schema([
                                                    Checkbox::make('ta')
                                                        ->label('Time Attendance (TA)')
                                                        ->inline()
                                                        ->disabled()
                                                        ->dehydrated(true)
                                                        ->default(function (SoftwareHandover $record) {
                                                            return $this->shouldModuleBeChecked($record, ['TCL_TA USER-NEW', 'TCL_TA USER-ADDON', 'TCL_TA USER-ADDON(R)', 'TCL_TA USER-RENEWAL', 'TCL_FULL USER-NEW']);
                                                        }),
                                                    Checkbox::make('tapp')
                                                        ->label('TimeTec Appraisal (T-APP)')
                                                        ->inline()
                                                        ->disabled()
                                                        ->dehydrated(true)
                                                        ->default(function (SoftwareHandover $record) {
                                                            return $this->shouldModuleBeChecked($record, ['TCL_APPRAISAL USER-NEW']);
                                                        }),
                                                    Checkbox::make('tl')
                                                        ->label('TimeTec Leave (TL)')
                                                        ->inline()
                                                        ->disabled()
                                                        ->dehydrated(true)
                                                        ->default(function (SoftwareHandover $record) {
                                                            return $this->shouldModuleBeChecked($record, ['TCL_LEAVE USER-NEW', 'TCL_LEAVE USER-ADDON', 'TCL_LEAVE USER-ADDON(R)', 'TCL_LEAVE USER-RENEWAL', 'TCL_FULL USER-NEW']);
                                                        }),
                                                    Checkbox::make('thire')
                                                        ->label('TimeTec Hire (T-HIRE)')
                                                        ->inline()
                                                        ->disabled()
                                                        ->dehydrated(true)
                                                        ->default(function (SoftwareHandover $record) {
                                                            return $this->shouldModuleBeChecked($record, ['TCL_HIRE-NEW', 'TCL_HIRE-RENEWAL']);
                                                        }),
                                                    Checkbox::make('tc')
                                                        ->label('TimeTec Claim (TC)')
                                                        ->inline()
                                                        ->disabled()
                                                        ->dehydrated(true)
                                                        ->default(function (SoftwareHandover $record) {
                                                            return $this->shouldModuleBeChecked($record, ['TCL_CLAIM USER-NEW', 'TCL_CLAIM USER-ADDON', 'TCL_CLAIM USER-ADDON(R)', 'TCL_CLAIM USER-RENEWAL', 'TCL_FULL USER-NEW']);
                                                        }),
                                                    Checkbox::make('tacc')
                                                        ->label('TimeTec Access (T-ACC)')
                                                        ->inline()
                                                        ->disabled()
                                                        ->dehydrated(true)
                                                        ->default(function (SoftwareHandover $record) {
                                                            return $this->shouldModuleBeChecked($record, ['TCL_ACCESS-NEW', 'TCL_ACCESS-RENEWAL']);
                                                        }),
                                                    Checkbox::make('tp')
                                                        ->label('TimeTec Payroll (TP)')
                                                        ->inline()
                                                        ->disabled()
                                                        ->dehydrated(true)
                                                        ->default(function (SoftwareHandover $record) {
                                                            return $this->shouldModuleBeChecked($record, ['TCL_PAYROLL USER-NEW', 'TCL_PAYROLL USER-ADDON', 'TCL_PAYROLL USER-ADDON(R)', 'TCL_PAYROLL USER-RENEWAL', 'TCL_FULL USER-NEW']);
                                                        }),
                                                    Checkbox::make('tpbi')
                                                        ->label('TimeTec Power BI (T-PBI)')
                                                        ->inline()
                                                        ->disabled()
                                                        ->dehydrated(true)
                                                        ->default(function (SoftwareHandover $record) {
                                                            return $this->shouldModuleBeChecked($record, ['TCL_POWER BI']);
                                                        }),
                                                ])
                                        ])
                                        ->hidden() // Hide the entire section
                                ]),

                            FileUpload::make('invoice_file')
                                ->label('Upload Invoice')
                                ->disk('public')
                                ->directory('handovers/invoices')
                                ->visibility('public')
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                ->multiple()
                                ->maxFiles(10)
                                ->minFiles(1)
                                ->helperText('Upload invoice files (PDF, JPG, PNG formats accepted)')
                                ->openable()
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                                    $companyName = Str::slug($get('company_name') ?? 'invoice');
                                    $date = now()->format('Y-m-d');
                                    $random = Str::random(5);
                                    $extension = $file->getClientOriginalExtension();
                                    return "{$companyName}-invoice-{$date}-{$random}.{$extension}";
                                }),
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

                            // Extract module selections for license setup
                            $moduleSelections = [
                                'ta' => $data['ta'] ?? false,
                                'tl' => $data['tl'] ?? false,
                                'tc' => $data['tc'] ?? false,
                                'tp' => $data['tp'] ?? false,
                                'tapp' => $data['tapp'] ?? false,
                                'thire' => $data['thire'] ?? false,
                                'tacc' => $data['tacc'] ?? false,
                                'tpbi' => $data['tpbi'] ?? false,
                            ];

                            // Prepare update data
                            $updateData = [
                                'project_priority' => 'High',
                                'status' => 'Completed',
                                'completed_at' => now(),
                                'implementer' => $implementerName,
                                'ta' => $moduleSelections['ta'],
                                'tl' => $moduleSelections['tl'],
                                'tc' => $moduleSelections['tc'],
                                'tp' => $moduleSelections['tp'],
                                'tapp' => $moduleSelections['tapp'],
                                'thire' => $moduleSelections['thire'],
                                'tacc' => $moduleSelections['tacc'],
                                'tpbi' => $moduleSelections['tpbi'],
                                'follow_up_date' => now(),
                                'follow_up_counter' => true,
                            ];

                            if (isset($data['invoice_file'])) {
                                $updateData['invoice_file'] = $data['invoice_file'];
                            }

                            // Update the record
                            $record->update($updateData);

                            // Create CRM Account
                            $handoverId = 'SW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                            $crmResult = $this->createCRMAccount($record, $handoverId);

                            // ✅ Setup CRM Licenses if account creation was successful
                            if ($crmResult['success'] && !empty($crmResult['data'])) {
                                $this->setupCRMLicenses($record, $crmResult['data'], $moduleSelections, $handoverId);
                            } else {
                                \Illuminate\Support\Facades\Log::warning("Skipping license setup - CRM account creation failed", [
                                    'handover_id' => $handoverId
                                ]);
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
                        ->modalWidth('3xl')
                        ->modalHeading('Complete Software Handover')
                        ->requiresConfirmation(false)
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

            // Prepare CRM account data
            $crmAccountData = [
                'company_name' => $record->company_name,
                'country_id' => (int)$countryData['id'],
                'name' => $credentials['name'],
                'email' => $credentials['email'],
                'password' => $credentials['password'],
                'phone_code' => $phoneData['phone_code'],
                'phone' => $phoneData['clean_phone'],
                'timezone' => $countryData['timezone'] ?? 'Asia/Kuala_Lumpur',
            ];

            \Illuminate\Support\Facades\Log::info("Calling CRM API", [
                'handover_id' => $handoverId,
                'company_name' => $crmAccountData['company_name'],
                'email' => $crmAccountData['email'],
                'phone_code' => $crmAccountData['phone_code'],
                'phone' => $crmAccountData['phone'],
                'country_id' => $crmAccountData['country_id'],
                'timezone' => $crmAccountData['timezone']
            ]);

            // Create account via CRM API
            $crmService = app(\App\Services\CRMApiService::class);
            $crmResult = $crmService->createAccount($crmAccountData);

            if ($crmResult['success']) {
                $this->saveCRMAccountData($record, $crmResult['data'], $credentials, $phoneData['raw_phone']);

                Notification::make()
                    ->title('CRM Account Created Successfully')
                    ->success()
                    ->body("Account ID: {$crmResult['data']['accountId']} | Company ID: {$crmResult['data']['companyId']}")
                    ->send();
            } else {
                \Illuminate\Support\Facades\Log::error("CRM Account creation failed", [
                    'handover_id' => $handoverId,
                    'error' => $crmResult['error'],
                    'status' => $crmResult['status'] ?? 'unknown'
                ]);

                Notification::make()
                    ->title('CRM Account Creation Failed')
                    ->warning()
                    ->body($crmResult['error'] ?: 'Unknown error occurred')
                    ->send();
            }

            return $crmResult;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("CRM Account creation exception", [
                'handover_id' => $handoverId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Notification::make()
                ->title('CRM Account Creation Error')
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
            ]);

            return [
                'email' => $customer->email,
                'password' => $customer->plain_password,
                'name' => $customer->name,
                'customer' => $customer,
            ];
        }

        // Generate new credentials
        $credentials = $activationController->generateCRMAccountCredentials(
            $record->lead_id,
            $handoverId
        );

        \Illuminate\Support\Facades\Log::info("Generated new customer credentials", [
            'handover_id' => $handoverId,
            'email' => $credentials['email'],
            'name' => $credentials['name']
        ]);

        return [
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'name' => $credentials['name'],
            'customer' => null,
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

        // Update or create customer record
        if ($credentials['customer']) {
            $credentials['customer']->update([
                'hr_account_id' => $crmData['accountId'] ?? null,
                'hr_company_id' => $crmData['companyId'] ?? null,
                'hr_user_id' => $crmData['userId'] ?? null,
            ]);
        } else {
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
        }

        \Illuminate\Support\Facades\Log::info("CRM Account data saved successfully", [
            'software_handover_id' => $record->id,
            'account_id' => $crmData['accountId'],
            'company_id' => $crmData['companyId'],
            'user_id' => $crmData['userId'],
        ]);
    }

    /**
     * Send handover notification email to implementer and salesperson
     */
    protected function sendHandoverNotificationEmail(SoftwareHandover $record, string $implementerName, string $implementerEmail, string $salespersonName, string $salespersonEmail): void
    {
        try {
            $companyName = $record->company_name ?? $record->lead->companyDetail->company_name ?? 'Unknown Company';
            $handoverId = 'SW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
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
            if ($implementerEmail && filter_var($implementerEmail, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = $implementerEmail;
            }
            if ($salespersonEmail && filter_var($salespersonEmail, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = $salespersonEmail;
            }

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
                $handoverId = 'SW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                \Illuminate\Support\Facades\Log::warning("No valid PIC emails found for handover {$handoverId}");
                return;
            }

            $handoverId = 'SW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
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

    /**
     * Setup CRM licenses (buffer + paid applications)
     */
    protected function setupCRMLicenses(SoftwareHandover $record, array $crmData, array $modules, string $handoverId): void
    {
        $accountId = $crmData['accountId'] ?? null;
        $companyId = $crmData['companyId'] ?? null;

        if (!$accountId || !$companyId) {
            \Illuminate\Support\Facades\Log::warning("Cannot setup licenses - missing account or company ID", [
                'handover_id' => $handoverId,
                'account_id' => $accountId,
                'company_id' => $companyId
            ]);
            return;
        }

        // Add buffer license
        $bufferResult = $this->addBufferLicense($record, $accountId, $companyId, $handoverId);

        if ($bufferResult['success']) {
            // Store buffer license ID
            $record->update([
                'crm_buffer_license_id' => $bufferResult['data']['licenseSetId'] ?? null
            ]);

            Notification::make()
                ->title('Buffer License Added')
                ->success()
                ->body('30-day buffer license has been added')
                ->send();
        } else {
            Notification::make()
                ->title('Buffer License Failed')
                ->warning()
                ->body($bufferResult['error'] ?? 'Failed to add buffer license')
                ->send();
        }

        // Add paid application licenses based on selected modules
        $licenseResults = $this->addPaidApplicationLicenses($record, $accountId, $companyId, $modules, $handoverId);

        if ($licenseResults['success']) {
            $successCount = 0;
            $failCount = 0;
            $licenseIds = [];

            foreach ($licenseResults['results'] as $app => $result) {
                if ($result['success']) {
                    $successCount++;
                    $licenseIds[$app] = $result['data']['periodId'] ?? null;
                } else {
                    $failCount++;
                }
            }

            // Store paid license IDs as JSON
            if (!empty($licenseIds)) {
                $record->update([
                    'crm_paid_license_ids' => json_encode($licenseIds)
                ]);
            }

            Notification::make()
                ->title('Application Licenses Setup')
                ->success()
                ->body("Successfully added {$successCount} application license(s)" .
                    ($failCount > 0 ? ", {$failCount} failed" : ''))
                ->send();

            \Illuminate\Support\Facades\Log::info("License setup completed", [
                'handover_id' => $handoverId,
                'success_count' => $successCount,
                'fail_count' => $failCount,
                'license_ids' => $licenseIds
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
