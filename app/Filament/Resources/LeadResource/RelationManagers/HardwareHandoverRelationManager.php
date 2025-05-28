<?php
namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Classes\Encryptor;
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
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class HardwareHandoverRelationManager extends RelationManager
{
    protected static string $relationship = 'hardwareHandover'; // Define the relationship name in the Lead model
    protected static ?int $indexRepeater2 = 0;

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
                    Section::make('Step 1: Invoice Details')
                        ->schema([
                            Grid::make(1)
                                ->schema([
                                    Forms\Components\Actions::make([
                                        Forms\Components\Actions\Action::make('export_invoice_info')
                                            ->label('Export Invoice Information to Excel')
                                            ->color('success')
                                            ->icon('heroicon-o-document-arrow-down')
                                            ->url(function () {
                                                $leadId = $this->getOwnerRecord()->id;
                                                return route('software-handover.export-customer', ['lead' => Encryptor::encrypt($leadId)]);
                                            })
                                            ->openUrlInNewTab(),
                                    ])
                                    ->extraAttributes(['class' => 'space-y-2']),
                                ]),
                        ]),

                    Section::make('Step 2: Category')
                        ->schema([
                            Forms\Components\Radio::make('installation_type')
                                ->label('')
                                ->options([
                                    'courier' => 'Courier',
                                    'internal_installation' => 'Internal Installation',
                                    'external_installation' => 'External Installation',
                                ])
                                // ->inline()
                                ->live(debounce:500)
                                ->columns(3)
                                ->required(),
                        ]),

                    Section::make('Step 3: Category 2')
                        ->schema([
                            Forms\Components\Placeholder::make('installation_type_helper')
                            ->label('')
                            ->content('Please select an installation type in Step 2 to see the relevant fields')
                            ->visible(fn (callable $get) => empty($get('installation_type')))
                            ->inlineLabel(),

                            Grid::make(2)
                                ->schema([
                                    Select::make('category2.installer')
                                        ->label('Installer')
                                        ->visible(fn (callable $get) => $get('installation_type') === 'internal_installation')
                                        ->options(function () {
                                            // Retrieve options from the installer table
                                            return \App\Models\Installer::pluck('company_name', 'id')->toArray();
                                        })
                                        ->searchable()
                                        ->preload(),
                                    Select::make('category2.reseller')
                                        ->label('Reseller')
                                        ->visible(fn (callable $get) => $get('installation_type') === 'external_installation')
                                        ->options(function () {
                                            // Retrieve options from the reseller table
                                            return \App\Models\Reseller::pluck('company_name', 'id')->toArray();
                                        })
                                        ->searchable()
                                        ->preload(),
                                    Grid::make(4)
                                    ->schema([
                                        TextInput::make('category2.pic_name')
                                            ->label('Name')
                                            ->visible(fn (callable $get) => $get('installation_type') === 'courier'),
                                        TextInput::make('category2.pic_phone')
                                            ->label('HP Number')
                                            ->visible(fn (callable $get) => $get('installation_type') === 'courier'),
                                        TextInput::make('category2.email')
                                            ->label('Email Address')
                                            ->email()
                                            ->visible(fn (callable $get) => $get('installation_type') === 'courier'),
                                        TextInput::make('category2.courier_address')
                                            ->label('Courier Address')
                                            ->visible(fn (callable $get) => $get('installation_type') === 'courier'),
                                    ]),
                                ]),
                        ]),

                    Section::make('Step 4: Remark Details')
                        ->schema([
                            Forms\Components\Repeater::make('remarks')
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
                                            ->label(function (Forms\Get $get, ?string $state, $livewire) {
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
                                            ->autosize()
                                            ->rows(3),

                                        FileUpload::make('attachments')
                                            ->hiddenLabel(true)
                                            ->disk('public')
                                            ->directory('handovers/remark_attachments')
                                            ->visibility('public')
                                            ->multiple()
                                            ->maxFiles(3)
                                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                            ->openable()
                                            ->downloadable()
                                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                                                // Get lead ID from ownerRecord
                                                $leadId = $this->getOwnerRecord()->id;
                                                // Format ID with prefix (250) and padding
                                                $formattedId = '250' . str_pad($leadId, 3, '0', STR_PAD_LEFT);
                                                // Get extension
                                                $extension = $file->getClientOriginalExtension();

                                                // Generate a unique identifier (timestamp) to avoid overwriting files
                                                $timestamp = now()->format('YmdHis');
                                                $random = rand(1000, 9999);

                                                return "{$formattedId}-HW-REMARK-{$timestamp}-{$random}.{$extension}";
                                            }),
                                    ])
                                ])
                                ->itemLabel(fn() => __('Remark') . ' ' . ++self::$indexRepeater2)
                                ->addActionLabel('Add Remark')
                                ->maxItems(5)
                                ->defaultItems(1),
                        ]),

                    Section::make('Step 5: Proforma Invoice')
                        ->columnSpan(1) // Ensure it spans one column
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Select::make('proforma_invoice_product')
                                        ->required()
                                        ->label('Product')
                                        ->options(function (RelationManager $livewire) {
                                            $leadId = $livewire->getOwnerRecord()->id;
                                            return \App\Models\Quotation::where('lead_id', $leadId)
                                                ->where('quotation_type', 'product')
                                                ->where('status', \App\Enums\QuotationStatusEnum::accepted)
                                                ->pluck('pi_reference_no', 'id')
                                                ->toArray();
                                        })
                                        ->multiple()
                                        ->searchable()
                                        ->preload(),
                                    Select::make('proforma_invoice_hrdf')
                                        ->label('HRDF')
                                        ->options(function (RelationManager $livewire) {
                                            $leadId = $livewire->getOwnerRecord()->id;
                                            return \App\Models\Quotation::where('lead_id', $leadId)
                                                ->where('quotation_type', 'hrdf')
                                                ->where('status', \App\Enums\QuotationStatusEnum::accepted)
                                                ->pluck('pi_reference_no', 'id')
                                                ->toArray();
                                        })
                                        ->multiple()
                                        ->searchable()
                                        ->preload(),
                                ])
                        ]),

                    Section::make('Step 6: Attachment')
                        ->columnSpan(1) // Ensure it spans one column
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
                                    ->openable()
                                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                                        // Get lead ID from ownerRecord
                                        $leadId = $this->getOwnerRecord()->id;
                                        // Format ID with prefix (250) and padding
                                        $formattedId = '250' . str_pad($leadId, 3, '0', STR_PAD_LEFT);
                                        // Get extension
                                        $extension = $file->getClientOriginalExtension();

                                        // Generate a unique identifier (timestamp) to avoid overwriting files
                                        $timestamp = now()->format('YmdHis');
                                        $random = rand(1000, 9999);

                                        return "{$formattedId}-HW-CONFIRM-{$timestamp}-{$random}.{$extension}";
                                    }),

                                FileUpload::make('hrdf_grant_file')
                                    ->label('Upload HRDF Grant Approval Letter')
                                    ->disk('public')
                                    ->directory('handovers/hrdf_grant')
                                    ->visibility('public')
                                    ->multiple()
                                    ->maxFiles(10)
                                    ->openable()
                                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                    ->openable()
                                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                                        // Get lead ID from ownerRecord
                                        $leadId = $this->getOwnerRecord()->id;
                                        // Format ID with prefix (250) and padding
                                        $formattedId = '250' . str_pad($leadId, 3, '0', STR_PAD_LEFT);
                                        // Get extension
                                        $extension = $file->getClientOriginalExtension();

                                        // Generate a unique identifier (timestamp) to avoid overwriting files
                                        $timestamp = now()->format('YmdHis');
                                        $random = rand(1000, 9999);

                                        return "{$formattedId}-HW-HRDF-{$timestamp}-{$random}.{$extension}";
                                    })
                                    ->afterStateUpdated(function () {
                                        // Reset the counter after the upload is complete
                                        session()->forget('hrdf_upload_count');
                                    }),

                                FileUpload::make('payment_slip_file')
                                    ->label('Upload Payment Slip')
                                    ->disk('public')
                                    ->live(debounce:500)
                                    ->directory('handovers/payment_slips')
                                    ->visibility('public')
                                    ->multiple()
                                    ->maxFiles(1)
                                    ->openable()
                                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                    ->openable()
                                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                                        // Get lead ID from ownerRecord
                                        $leadId = $this->getOwnerRecord()->id;
                                        // Format ID with prefix (250) and padding
                                        $formattedId = '250' . str_pad($leadId, 3, '0', STR_PAD_LEFT);
                                        // Get extension
                                        $extension = $file->getClientOriginalExtension();

                                        // Generate a unique identifier (timestamp) to avoid overwriting files
                                        $timestamp = now()->format('YmdHis');
                                        $random = rand(1000, 9999);

                                        return "{$formattedId}-HW-PAYMENT-{$timestamp}-{$random}.{$extension}";
                                    }),
                                ])
                        ]),
                ])
                ->action(function (array $data): void {
                    $data['created_by'] = auth()->id();
                    $data['lead_id'] = $this->getOwnerRecord()->id;
                    $data['status'] = 'Draft';

                    if (isset($data['category2'])) {
                        $data['category2'] = json_encode($data['category2']);
                    } else {
                        $data['category2'] = json_encode([]);
                    }

                    if (isset($data['remarks']) && is_array($data['remarks'])) {
                        foreach ($data['remarks'] as $key => $remark) {
                            // Encode the attachments array for each remark
                            if (isset($remark['attachments']) && is_array($remark['attachments'])) {
                                $data['remarks'][$key]['attachments'] = json_encode($remark['attachments']);
                            }
                        }
                        // Encode the entire remarks structure
                        $data['remarks'] = json_encode($data['remarks']);
                    }

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
                    ->formatStateUsing(function ($state, HardwareHandover $record) {
                        // If no ID is provided, return a fallback
                        if (!$state) {
                            return 'Unknown';
                        }

                        // Format ID with prefix 250 and padding to ensure at least 3 digits
                        return '250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    }),
                TextColumn::make('submitted_at')
                    ->label('Date Submit')
                    ->date('d M Y')
                    ->toggleable(),
                TextColumn::make('installation_type')
                    ->label('Category (Installation Type)')
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'courier' => 'Courier',
                            'internal_installation' => 'Internal Installation',
                            'external_installation' => 'External Installation',
                            default => ucfirst($state),
                        };
                    })
                    ->toggleable(),
                TextColumn::make('category2')
                    ->label('Category 2')
                    ->formatStateUsing(function ($state, HardwareHandover $record) {
                        // If empty, return a placeholder
                        if (empty($state)) {
                            return '-';
                        }

                        // Decode JSON if it's a string
                        $data = is_string($state) ? json_decode($state, true) : $state;

                        // Format based on installation type
                        if ($record->installation_type === 'courier') {
                            $parts = [];

                            if (!empty($data['email'])) {
                                $parts[] = "Email: {$data['email']}";
                            }

                            if (!empty($data['pic_name'])) {
                                $parts[] = "Name: {$data['pic_name']}";
                            }

                            if (!empty($data['pic_phone'])) {
                                $parts[] = "Phone: {$data['pic_phone']}";
                            }

                            if (!empty($data['courier_address'])) {
                                $parts[] = "Address: {$data['courier_address']}";
                            }

                            // Return the formatted parts with HTML line breaks instead of pipes
                            return !empty($parts)
                                ? new HtmlString(implode('<br>', $parts))
                                : 'No courier details';
                        }
                        elseif ($record->installation_type === 'internal_installation') {
                            if (!empty($data['installer'])) {
                                $installer = \App\Models\Installer::find($data['installer']);
                                return $installer ? $installer->company_name : 'Unknown Installer';
                            }
                            return 'No installer selected';
                        }
                        elseif ($record->installation_type === 'external_installation') {
                            if (!empty($data['reseller'])) {
                                $reseller = \App\Models\Reseller::find($data['reseller']);
                                return $reseller ? $reseller->company_name : 'Unknown Reseller';
                            }
                            return 'No reseller selected';
                        }

                        // Fallback for any other case
                        return json_encode($data);
                    })
                    ->wrap()
                    ->html() // Important: Add this to render the HTML content
                    ->toggleable(),
                TextColumn::make('action_date')
                    ->label('Action Date')
                    ->toggleable(),
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

                    Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading(' ')
                        ->modalWidth('md')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->visible(fn (HardwareHandover $record): bool => in_array($record->status, ['New', 'Completed', 'Approved']))
                        ->modalContent(function (HardwareHandover $record): View {

                            // Return the view with the record using $this->record pattern
                            return view('components.hardware-handover')
                            ->with('extraAttributes', ['record' => $record]);
                        }),

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

                    Action::make('edit_hardware_handover')
                        ->label(function (HardwareHandover $record): string {
                            // Format ID with prefix 250 and pad with zeros to ensure at least 3 digits
                            $formattedId = '250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                            return "Edit Hardware Handover {$formattedId}";
                        })
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->modalSubmitActionLabel('Save')
                        ->visible(fn (HardwareHandover $record): bool => in_array($record->status, ['Draft']))
                        ->modalWidth(MaxWidth::SevenExtraLarge)
                        ->slideOver()
                        ->form([
                            Section::make('Step 1: Invoice Details')
                                ->schema([
                                    Grid::make(1)
                                        ->schema([
                                            Forms\Components\Actions::make([
                                                Forms\Components\Actions\Action::make('export_invoice_info')
                                                    ->label('Export Invoice Information to Excel')
                                                    ->color('success')
                                                    ->icon('heroicon-o-document-arrow-down')
                                                    ->url(function () {
                                                        $leadId = $this->getOwnerRecord()->id;
                                                        return route('software-handover.export-customer', ['lead' => Encryptor::encrypt($leadId)]);
                                                    })
                                                    ->openUrlInNewTab(),
                                            ])
                                            ->extraAttributes(['class' => 'space-y-2']),
                                        ]),
                                ]),

                            Section::make('Step 2: Category 1')
                                ->schema([
                                    Forms\Components\Radio::make('installation_type')
                                        ->hiddenLabel()
                                        ->options([
                                            'courier' => 'Courier',
                                            'internal_installation' => 'Internal Installation',
                                            'external_installation' => 'External Installation',
                                        ])
                                        // ->inline()
                                        ->columns(2)
                                        ->required()
                                        ->live(debounce:500)
                                        ->default(fn (HardwareHandover $record) => $record->installation_type ?? null),
                                ]),

                            Section::make('Step 3: Category 2')
                                ->schema([
                                    Grid::make(2)
                                    ->schema([
                                        Select::make('category2.installer')
                                                ->label('Installer')
                                                ->visible(fn (callable $get) => $get('installation_type') === 'internal_installation')
                                                ->options(function () {
                                                    // Retrieve options from the installer table
                                                    return \App\Models\Installer::pluck('company_name', 'id')->toArray();
                                                })
                                                ->searchable()
                                                ->preload()
                                                ->default(function (HardwareHandover $record) {
                                                    if (!$record || empty($record->category2)) {
                                                        return null;
                                                    }
                                                    $categoryData = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                                    return $categoryData['installer'] ?? null;
                                                }),
                                            Select::make('category2.reseller')
                                                ->label('Reseller')
                                                ->visible(fn (callable $get) => $get('installation_type') === 'external_installation')
                                                ->options(function () {
                                                    // Retrieve options from the reseller table
                                                    return \App\Models\Reseller::pluck('company_name', 'id')->toArray();
                                                })
                                                ->searchable()
                                                ->preload()
                                                ->default(function (HardwareHandover $record) {
                                                    if (!$record || empty($record->category2)) {
                                                        return null;
                                                    }
                                                    $categoryData = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                                    return $categoryData['reseller'] ?? null;
                                                }),
                                        Grid::make(4)
                                        ->schema([
                                            TextInput::make('category2.pic_name')
                                                ->label('Name')
                                                ->visible(fn (callable $get) => $get('installation_type') === 'courier')
                                                ->default(function (HardwareHandover $record) {
                                                    if (!$record || empty($record->category2)) {
                                                        return null;
                                                    }
                                                    $categoryData = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                                    return $categoryData['pic_name'] ?? null;
                                                }),
                                            TextInput::make('category2.pic_phone')
                                                ->label('HP Number')
                                                ->visible(fn (callable $get) => $get('installation_type') === 'courier')
                                                ->default(function (HardwareHandover $record) {
                                                    if (!$record || empty($record->category2)) {
                                                        return null;
                                                    }
                                                    $categoryData = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                                    return $categoryData['pic_phone'] ?? null;
                                                }),
                                            TextInput::make('category2.email')
                                                ->label('Email Address')
                                                ->email()
                                                ->visible(fn (callable $get) => $get('installation_type') === 'courier')
                                                ->default(function (HardwareHandover $record) {
                                                    if (!$record || empty($record->category2)) {
                                                        return null;
                                                    }
                                                    $categoryData = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                                    return $categoryData['email'] ?? null;
                                                }),
                                            TextInput::make('category2.courier_address')
                                                ->label('Courier Address')
                                                ->visible(fn (callable $get) => $get('installation_type') === 'courier')
                                                ->default(function (HardwareHandover $record) {
                                                    if (!$record || empty($record->category2)) {
                                                        return null;
                                                    }
                                                    $categoryData = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                                    return $categoryData['courier_address'] ?? null;
                                                }),
                                        ]),
                                    ]),
                                ]),

                                Section::make('Step 4: Remark Details')
                                ->schema([
                                    Forms\Components\Repeater::make('remarks')
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
                                                    ->label(function (Forms\Get $get, ?string $state, $livewire) {
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
                                                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                                                        // Get lead ID from ownerRecord
                                                        $leadId = $this->getOwnerRecord()->id;
                                                        // Format ID with prefix (250) and padding
                                                        $formattedId = '250' . str_pad($leadId, 3, '0', STR_PAD_LEFT);
                                                        // Get extension
                                                        $extension = $file->getClientOriginalExtension();

                                                        // Generate a unique identifier (timestamp) to avoid overwriting files
                                                        $timestamp = now()->format('YmdHis');
                                                        $random = rand(1000, 9999);

                                                        return "{$formattedId}-HW-REMARK-{$timestamp}-{$random}.{$extension}";
                                                    }),
                                            ]),
                                        ])
                                        ->itemLabel(function (array $state, Forms\Components\Component $component) {
                                            // Extract the index from the state path using regex
                                            $statePath = $component->getStatePath();
                                            $matches = [];
                                            if (preg_match('/remarks\.(\d+)/', $statePath, $matches)) {
                                                $index = (int) $matches[1];
                                                return 'Remark ' . ($index + 1);
                                            }
                                            return 'Remark';
                                        })
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

                            Section::make('Step 5: Proforma Invoice')
                                ->columnSpan(1) // Ensure it spans one column
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            Select::make('proforma_invoice_product')
                                                ->required()
                                                ->label('Product')
                                                ->options(function (RelationManager $livewire) {
                                                    $leadId = $livewire->getOwnerRecord()->id;
                                                    return \App\Models\Quotation::where('lead_id', $leadId)
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
                                                ->label('HRDF')
                                                ->options(function (RelationManager $livewire) {
                                                    $leadId = $livewire->getOwnerRecord()->id;
                                                    return \App\Models\Quotation::where('lead_id', $leadId)
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

                            Section::make('Step 6: Attachment')
                                ->columnSpan(1) // Ensure it spans one column
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
                                            ->openable()
                                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                                                // Get lead ID from ownerRecord
                                                $leadId = $this->getOwnerRecord()->id;
                                                // Format ID with prefix (250) and padding
                                                $formattedId = '250' . str_pad($leadId, 3, '0', STR_PAD_LEFT);
                                                // Get extension
                                                $extension = $file->getClientOriginalExtension();

                                                // Generate a unique identifier (timestamp) to avoid overwriting files
                                                $timestamp = now()->format('YmdHis');
                                                $random = rand(1000, 9999);

                                                return "{$formattedId}-HW-CONFIRM-{$timestamp}-{$random}.{$extension}";
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
                                            ->openable()
                                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                            ->openable()
                                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                                                // Get lead ID from ownerRecord
                                                $leadId = $this->getOwnerRecord()->id;
                                                // Format ID with prefix (250) and padding
                                                $formattedId = '250' . str_pad($leadId, 3, '0', STR_PAD_LEFT);
                                                // Get extension
                                                $extension = $file->getClientOriginalExtension();

                                                // Generate a unique identifier (timestamp) to avoid overwriting files
                                                $timestamp = now()->format('YmdHis');
                                                $random = rand(1000, 9999);

                                                return "{$formattedId}-HW-HRDF-{$timestamp}-{$random}.{$extension}";
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
                                            ->openable()
                                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                            ->openable()
                                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                                                // Get lead ID from ownerRecord
                                                $leadId = $this->getOwnerRecord()->id;
                                                // Format ID with prefix (250) and padding
                                                $formattedId = '250' . str_pad($leadId, 3, '0', STR_PAD_LEFT);
                                                // Get extension
                                                $extension = $file->getClientOriginalExtension();

                                                // Generate a unique identifier (timestamp) to avoid overwriting files
                                                $timestamp = now()->format('YmdHis');
                                                $random = rand(1000, 9999);

                                                return "{$formattedId}-HW-PAYMENT-{$timestamp}-{$random}.{$extension}";
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
                                        ])
                                ]),
                        ])
                        ->action(function (HardwareHandover $record, array $data): void {
                            $data['created_by'] = auth()->id();
                            $data['lead_id'] = $this->getOwnerRecord()->id;
                            $data['status'] = 'Draft';

                            if (isset($data['category2'])) {
                                $data['category2'] = json_encode($data['category2']);
                            } else {
                                $data['category2'] = json_encode([]);
                            }

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

                            // Create the handover record
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
