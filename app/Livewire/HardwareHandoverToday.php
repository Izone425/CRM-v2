<?php

namespace App\Livewire;

use App\Classes\Encryptor;
use App\Http\Controllers\GenerateHardwareHandoverPdfController;
use App\Models\HardwareHandover;
use App\Models\Lead;
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

class HardwareHandoverToday extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?int $indexRepeater = 0;
    protected static ?int $indexRepeater2 = 0;

    public $selectedUser;

    #[On('updateTablesForUser')] // Listen for updates
    public function updateTablesForUser($selectedUser)
    {
        $this->selectedUser = $selectedUser;
        session(['selectedUser' => $selectedUser]); // Store for consistency

        $this->resetTable(); // Refresh the table
    }

    public function getNewHardwareHandovers()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->id();

        $query = HardwareHandover::query();

        if (auth()->user()->role_id === 2) {
            // Salespersons (role_id 2) can see Draft, New, Approved, and Completed
            $query->whereIn('status', ['Rejected','Draft', 'New', 'Approved']);

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

        // Salesperson filter logic
        if ($this->selectedUser === 'all-salespersons') {
            // Keep as is - show all salespersons' handovers
            $salespersonIds = User::where('role_id', 2)->pluck('id');
            $query->whereHas('lead', function ($leadQuery) use ($salespersonIds) {
                $leadQuery->whereIn('salesperson', $salespersonIds);
            });
        } elseif (is_numeric($this->selectedUser)) {
            // Validate that the selected user exists and is a salesperson
            $userExists = User::where('id', $this->selectedUser)->where('role_id', 2)->exists();

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
            $query->whereIn('status', ['New', 'Approved']);
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
            ->poll('10s')
            ->query($this->getNewHardwareHandovers())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, HardwareHandover $record) {
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
                        return '250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary') // Makes it visually appear as a link
                    ->weight('bold')
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(' ')
                            ->modalWidth('md')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HardwareHandover $record): View {
                                return view('components.hardware-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('lead.salesperson')
                    ->label('SalesPerson')
                    ->getStateUsing(function (HardwareHandover $record) {
                        $lead = $record->lead;
                        if (!$lead) {
                            return '-';
                        }

                        $salespersonId = $lead->salesperson;
                        return User::find($salespersonId)?->name ?? '-';
                    })
                    ->visible(fn(): bool => auth()->user()->role_id !== 2),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 20, '...'));
                        $encryptedId = \App\Classes\Encryptor::encrypt($record->lead->id);

                        return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($fullName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $shortened . '
                                </a>';
                    })
                    ->html(),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
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
                        ->visible(fn (HardwareHandover $record): bool => $record->status === 'Draft')
                        ->action(function (HardwareHandover $record): void {
                            $record->update([
                                'status' => 'New',
                                'submitted_at' => now(),
                            ]);

                            // Use the controller for PDF generation
                            app(GenerateHardwareHandoverPdfController::class)->generateInBackground($record);

                            Notification::make()
                                ->title('Handover submitted for approval')
                                ->success()
                                ->send();
                        }),
                    Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading(' ')
                        ->modalWidth('md')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->visible(fn (HardwareHandover $record): bool => in_array($record->status, ['New', 'Completed', 'Approved']))
                        // Use a callback function instead of arrow function for more control
                        ->modalContent(function (HardwareHandover $record): View {

                            // Return the view with the record using $this->record pattern
                            return view('components.hardware-handover')
                            ->with('extraAttributes', ['record' => $record]);
                        }),
                    Action::make('edit_hardware_handover')
                        ->label('Edit Hardware Handover')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->modalSubmitActionLabel('Save')
                        ->visible(fn (HardwareHandover $record): bool => in_array($record->status, ['Draft']))
                        ->modalWidth(MaxWidth::SevenExtraLarge)
                        ->slideOver()
                        ->form([
                            Section::make('Step 1: Database')
                                ->collapsible()
                                ->schema([
                                    Grid::make(3)
                                        ->schema([
                                            TextInput::make('company_name')
                                                ->label('Company Name')
                                                ->default(fn (HardwareHandover $record) =>
                                                    $record->company_name ?? $this->getOwnerRecord()->companyDetail->company_name ?? null),
                                            TextInput::make('pic_name')
                                                ->label('Name')
                                                ->default(fn (HardwareHandover $record) =>
                                                    $record->pic_name ?? $this->getOwnerRecord()->companyDetail->name ?? $this->getOwnerRecord()->name),
                                            TextInput::make('pic_phone')
                                                ->label('PIC HP No.')
                                                ->default(fn (HardwareHandover $record) =>
                                                    $record->pic_phone ?? $this->getOwnerRecord()->companyDetail->contact_no ?? $this->getOwnerRecord()->phone),
                                        ]),
                                    Grid::make(3)
                                        ->schema([
                                            TextInput::make('salesperson')
                                                ->readOnly()
                                                ->label('Salesperson')
                                                ->default(fn (HardwareHandover $record) =>
                                                    $record->salesperson ?? ($this->getOwnerRecord()->salesperson ? User::find($this->getOwnerRecord()->salesperson)->name : null)),
                                            TextInput::make('headcount')
                                                ->numeric()
                                                ->label('Company Size')
                                                ->live(debounce:550)
                                                ->afterStateUpdated(function (Set $set, ?string $state, CategoryService $category) {
                                                    $set('category', $category->retrieve($state));
                                                })
                                                ->default(fn (HardwareHandover $record) => $record->headcount ?? null)
                                                ->required(),
                                            TextInput::make('category')
                                                ->autocapitalize()
                                                ->live(debounce:550)
                                                ->placeholder('Select a category')
                                                ->dehydrated(false)
                                                ->default(function (HardwareHandover $record, CategoryService $category) {
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
                                                    ->label('Export Invoice Information to Excel')
                                                    ->color('success')
                                                    ->icon('heroicon-o-document-arrow-down')
                                                    ->url(function (HardwareHandover $record) {
                                                        // Use the record's lead_id instead of getOwnerRecord()->id
                                                        return route('hardware-handover.export-customer', ['lead' => Encryptor::encrypt($record->lead_id)]);
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
                                                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get, HardwareHandover $record): string {
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
                                        ->default(function (HardwareHandover $record) {
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
                                        ->default(function (HardwareHandover $record) {
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
                                            ->options(function (HardwareHandover $record) {
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
                                            ->default(function (HardwareHandover $record) {
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
                                            ->options(function (HardwareHandover $record) {
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
                                            ->default(function (HardwareHandover $record) {
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
                                    Grid::make(3)
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
                                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get, HardwareHandover $record): string {
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
                                                ->default(function (HardwareHandover $record) {
                                                    if (!$record || !$record->confirmation_order_file) {
                                                        return [];
                                                    }
                                                    if (is_string($record->confirmation_order_file)) {
                                                        return json_decode($record->confirmation_order_file, true) ?? [];
                                                    }
                                                    return is_array($record->confirmation_order_file) ? $record->confirmation_order_file : [];
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
                                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get, HardwareHandover $record): string {
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
                                                ->default(function (HardwareHandover $record) {
                                                    if (!$record || !$record->hrdf_grant_file) {
                                                        return [];
                                                    }
                                                    if (is_string($record->hrdf_grant_file)) {
                                                        return json_decode($record->hrdf_grant_file, true) ?? [];
                                                    }
                                                    return is_array($record->hrdf_grant_file) ? $record->hrdf_grant_file : [];
                                                }),

                                            FileUpload::make('payment_slip_file')
                                                ->label('Upload Payment Slip')
                                                ->disk('public')
                                                ->live(debounce:500)
                                                ->directory('handovers/payment_slips')
                                                ->visibility('public')
                                                ->multiple()
                                                ->maxFiles(1)
                                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                                ->openable()
                                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get, HardwareHandover $record): string {
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
                                                ->default(function (HardwareHandover $record) {
                                                    if (!$record || !$record->payment_slip_file) {
                                                        return [];
                                                    }
                                                    if (is_string($record->payment_slip_file)) {
                                                        return json_decode($record->payment_slip_file, true) ?? [];
                                                    }
                                                    return is_array($record->payment_slip_file) ? $record->payment_slip_file : [];
                                                }),
                                        ]),
                                ]),
                        ])
                        ->action(function (HardwareHandover $record, array $data): void {
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

                    // Also add the view reason and convert to draft actions for completeness
                    Action::make('view_reason')
                        ->label('View Reason')
                        ->visible(fn (HardwareHandover $record): bool => $record->status === 'Rejected')
                        ->icon('heroicon-o-magnifying-glass-plus')
                        ->modalHeading('Change Request Reason')
                        ->modalContent(fn ($record) => view('components.view-reason', [
                            'reason' => $record->reject_reason,
                        ]))
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalWidth('md')
                        ->color('warning'),
                    Action::make('mark_approved')
                        ->label('Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (HardwareHandover $record): void {
                            $record->update(['status' => 'Approved']);

                            Notification::make()
                                ->title('Hardware Handover marked as approved')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->hidden(fn (HardwareHandover $record): bool =>
                            $record->status !== 'New' || auth()->user()->role_id === 2
                        ),
                    Action::make('mark_rejected')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->hidden(fn (HardwareHandover $record): bool =>
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
                        ->action(function (HardwareHandover $record, array $data): void {
                            // Update both status and add the rejection remarks
                            $record->update([
                                'status' => 'Rejected',
                                'reject_reason' => $data['reject_reason']
                            ]);

                            Notification::make()
                                ->title('Hardware Handover marked as rejected')
                                ->body('Rejection reason: ' . $data['reject_reason'])
                                ->danger()
                                ->send();
                        })
                        ->requiresConfirmation(false),
                    Action::make('check_sales_order')
                        ->label('Check Sales Order')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Check Sales Order Status')
                        ->modalDescription('Please select the appropriate status for this sales order.')
                        ->modalSubmitAction(false) // Remove default submit button
                        ->modalCancelAction(false) // Remove default cancel button
                        ->extraModalFooterActions([
                            Action::make('no_stock')
                                ->label('No Stock')
                                ->color('danger')
                                ->icon('heroicon-o-x-circle')
                                ->cancelParentActions()
                                ->action(function (HardwareHandover $record) {
                                    $record->update([
                                        'status' => 'No Stock',
                                    ]);

                                    Notification::make()
                                        ->title('Sales order marked as No Stock')
                                        ->warning()
                                        ->body('The hardware handover is on hold until stock becomes available.')
                                        ->send();

                                    $this->dispatch('close-modal', id: 'check_sales_order');
                                }),
                            Action::make('sales_order_completed')
                                ->label('Sales Order Completed')
                                ->color('success')
                                ->icon('heroicon-o-check-circle')
                                ->cancelParentActions()
                                ->action(function (HardwareHandover $record) {
                                    $record->update([
                                        'status' => 'Sales Order Completed',
                                    ]);

                                    Notification::make()
                                        ->title('Sales order marked as completed')
                                        ->success()
                                        ->send();

                                    $this->dispatch('close-modal', id: 'check_sales_order');
                                }),
                        ])
                        ->hidden(fn (HardwareHandover $record): bool =>
                            $record->status !== 'New' || auth()->user()->role_id === 2
                        ),
                    Action::make('mark_data_migration_completed')
                        ->label('Data Migration Completed')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (HardwareHandover $record): void {
                            $record->update(['status' => 'Data Migration Completed']);

                            Notification::make()
                                ->title('Hardware Handover marked as Data Migration Completed')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->hidden(fn (HardwareHandover $record): bool =>
                            $record->status !== 'New' || auth()->user()->role_id === 2
                        ),
                    Action::make('mark_courier_completed')
                        ->label('Courier Completed')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (HardwareHandover $record): void {
                            $record->update(['status' => 'Courier Completed']);

                            Notification::make()
                                ->title('Hardware Handover marked as Courier Completed')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->hidden(fn (HardwareHandover $record): bool =>
                            $record->status !== 'New' ||
                            auth()->user()->role_id === 2 ||
                            $record->installation_type !== 'courier'
                        ),
                    Action::make('mark_installation_completed')
                        ->label('Installation Completed')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (HardwareHandover $record): void {
                            $record->update(['status' => 'Installation Completed']);

                            Notification::make()
                                ->title('Hardware Handover marked as Installation Completed')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->hidden(fn (HardwareHandover $record): bool =>
                            $record->status !== 'New' ||
                            auth()->user()->role_id === 2 ||
                            $record->installation_type === 'courier'
                        ),
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
                ])->button()

            ]);
    }

    public function render()
    {
        return view('livewire.hardware-handover-today');
    }
}
