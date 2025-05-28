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
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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
                            TextInput::make('courier')
                                ->label('Courier'),

                            Forms\Components\Radio::make('installation_type')
                                ->label('')
                                ->options([
                                    'internal_installation' => 'Internal Installation',
                                    'external_installation' => 'External Installation',
                                ])
                                // ->inline()
                                ->columns(2)
                                ->required(),
                        ]),

                    Section::make('Step 3: Category 2')
                        ->schema([
                            Grid::make(4)
                            ->schema([
                                TextInput::make('pic_name')
                                    ->label('Name'),
                                TextInput::make('pic_phone')
                                    ->label('HP Number'),
                                TextInput::make('email')
                                    ->label('Email Address')
                                    ->email(),
                                TextInput::make('courier_address')
                                    ->label('Courier Address'),
                            ]),
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('installer')
                                        ->label('Installer'),
                                    TextInput::make('reseller')
                                        ->label('Reseller'),
                                ]),
                        ]),

                    Section::make('Step 4: Proforma Invoice')
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

                    Section::make('Step 5: Attachment')
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
                        ->label(function (HardwareHandover $record): string {
                            // Format ID with prefix 250 and pad with zeros to ensure at least 3 digits
                            $formattedId = '250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                            return "Edit Hardware Handover {$formattedId}";
                        })
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->modalSubmitActionLabel('Save')
                        ->visible(fn (HardwareHandover $record): bool => in_array($record->status, ['New', 'Draft']))
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
                                    TextInput::make('courier')
                                        ->label('Courier')
                                        ->default(fn (HardwareHandover $record) => $record->courier ?? null),

                                    Forms\Components\Radio::make('installation_type')
                                        ->label('')
                                        ->options([
                                            'internal_installation' => 'Internal Installation',
                                            'external_installation' => 'External Installation',
                                        ])
                                        // ->inline()
                                        ->columns(2)
                                        ->required()
                                        ->default(fn (HardwareHandover $record) => $record->installation_type ?? null),
                                ]),

                            Section::make('Step 3: Category 2')
                                ->schema([
                                    Grid::make(4)
                                    ->schema([
                                        TextInput::make('pic_name')
                                            ->label('Name')
                                            ->default(fn (HardwareHandover $record) => $record->pic_name ?? null),
                                        TextInput::make('pic_phone')
                                            ->label('HP Number')
                                            ->default(fn (HardwareHandover $record) => $record->pic_phone ?? null),
                                        TextInput::make('pic_email')
                                            ->label('Email Address')
                                            ->email()
                                            ->default(fn (HardwareHandover $record) => $record->email ?? null),
                                        TextInput::make('courier_address')
                                            ->label('Courier Address')
                                            ->default(fn (HardwareHandover $record) => $record->courier_address ?? null),
                                    ]),
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('installer')
                                                ->label('Installer')
                                                ->default(fn (HardwareHandover $record) => $record->installer ?? null),
                                            TextInput::make('reseller')
                                                ->label('Reseller')
                                                ->default(fn (HardwareHandover $record) => $record->reseller ?? null),
                                        ]),
                                ]),

                            Section::make('Step 4: Proforma Invoice')
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

                            Section::make('Step 5: Attachment')
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
