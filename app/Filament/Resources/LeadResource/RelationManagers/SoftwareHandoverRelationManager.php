<?php
namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Classes\Encryptor;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table as TablesTable;
use App\Enums\QuotationStatusEnum;
use App\Filament\Resources\QuotationResource\Pages;
use App\Filament\Resources\QuotationResource\RelationManagers;
use App\Http\Controllers\GenerateSoftwareHandoverPdfController;
use App\Models\ActivityLog;
use App\Models\Industry;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use App\Models\Setting;
use App\Models\SoftwareHandover;
use App\Services\CategoryService;
use App\Services\QuotationService;
use Carbon\Carbon;
use Coolsam\FilamentFlatpickr\Forms\Components\Flatpickr;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\View as ViewComponent;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\View\View;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class SoftwareHandoverRelationManager extends RelationManager
{
    protected static string $relationship = 'softwareHandover'; // Define the relationship name in the Lead model
    protected static ?int $indexRepeater = 0;
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
            Tables\Actions\Action::make('EInvoiceWarning')
                ->label('Add Software Handover')
                ->icon('heroicon-o-pencil')
                ->color('gray')
                ->visible(function () {
                    $leadStatus = $this->getOwnerRecord()->lead_status ?? '';
                    // $isEInvoiceIncomplete = $this->isEInvoiceDetailsIncomplete();

                    // Only show this warning if:
                    // 1. The lead status is NOT closed, AND
                    // 2. The e-invoice information is incomplete
                    // return $leadStatus !== 'Closed' && $isEInvoiceIncomplete;
                    return $leadStatus !== 'Closed';
                })
                ->action(function () {
                    Notification::make()
                        ->warning()
                        ->title('Action Required')
                        ->body('Please close the lead before proceeding with the software handover.')
                        ->persistent()
                        // ->actions([
                        //     \Filament\Notifications\Actions\Action::make('copyEInvoiceLink')
                        //         ->label('Copy E-Invoice Link')
                        //         ->button()
                        //         ->color('primary')
                        //         ->close(),
                        //     \Filament\Notifications\Actions\Action::make('cancel')
                        //         ->label('Cancel')
                        //         ->close(),
                        // ])
                        ->send();
                }),

            // Action 2: Actual form when e-invoice is complete
            Tables\Actions\Action::make('AddSoftwareHandover')
                ->label('Add Software Handover')
                ->icon('heroicon-o-pencil')
                ->color('primary')
                ->visible(function () {
                    $leadStatus = $this->getOwnerRecord()->lead_status ?? '';
                    return $leadStatus === 'Closed';
                })
                ->slideOver()
                ->modalHeading('Software Handover')
                ->modalWidth(MaxWidth::FourExtraLarge)
                ->modalSubmitActionLabel('Save')
                ->form([
                    Section::make('Section 1: Database')
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    TextInput::make('company_name')
                                        ->label('Company Name')
                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                        ->afterStateHydrated(fn($state) => Str::upper($state))
                                        ->afterStateUpdated(fn($state) => Str::upper($state))
                                        ->default(fn () => $this->getOwnerRecord()->companyDetail->company_name ?? null),
                                    TextInput::make('pic_name')
                                        ->label('Name')
                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                        ->afterStateHydrated(fn($state) => Str::upper($state))
                                        ->afterStateUpdated(fn($state) => Str::upper($state))
                                        ->default(fn () => $this->getOwnerRecord()->companyDetail->name ?? $this->getOwnerRecord()->name),
                                    TextInput::make('pic_phone')
                                        ->label('HP Number')
                                        ->tel()
                                        ->default(fn () => $this->getOwnerRecord()->companyDetail->contact_no ?? $this->getOwnerRecord()->phone),

                                ]),
                            Grid::make(3)
                                ->schema([
                                    TextInput::make('salesperson')
                                        ->readOnly()
                                        ->label('Salesperson')
                                        ->default(fn () => $this->getOwnerRecord()->salesperson ? User::find($this->getOwnerRecord()->salesperson)->name : null),
                                    TextInput::make('headcount')
                                        ->numeric()
                                        ->live(debounce:550) // delay 550ms to allow user to have sufficient time to do input
                                        ->afterStateUpdated(function (Forms\Set $set, ?string $state, CategoryService $category) {
                                            /**
                                             * set this company's category based on head count
                                             */
                                            $set('category', $category->retrieve($state));
                                        })
                                        ->required(),
                                    TextInput::make('category')
                                        ->label('Company Size')
                                        ->dehydrated(false)
                                        ->autocapitalize()
                                        ->placeholder('Select a category')
                                        ->readOnly(),
                                ]),
                        ]),

                    Section::make('Step 2: Invoice Details')
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

                    Section::make('Step 3: Implementation Details')
                        ->schema([
                            Forms\Components\Repeater::make('implementation_pics')
                                ->hiddenLabel(true)
                                ->schema([
                                    Grid::make(4)
                                    ->schema([
                                        TextInput::make('pic_name_impl')
                                            ->required()
                                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                            ->afterStateHydrated(fn($state) => Str::upper($state))
                                            ->afterStateUpdated(fn($state) => Str::upper($state))
                                            ->label('Name'),
                                        TextInput::make('position')
                                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                            ->afterStateHydrated(fn($state) => Str::upper($state))
                                            ->afterStateUpdated(fn($state) => Str::upper($state))
                                            ->label('Position'),
                                        TextInput::make('pic_phone_impl')
                                            ->required()
                                            ->tel()
                                            ->label('HP Number'),
                                        TextInput::make('pic_email_impl')
                                            ->label('Email Address')
                                            ->required()
                                            ->email(),
                                    ]),
                                ])
                                ->addActionLabel('Add PIC')
                                ->minItems(1)
                                ->itemLabel(fn() => __('Person In Charge') . ' ' . ++self::$indexRepeater)
                                ->columns(2)
                                // Add default implementation PICs from lead data
                                ->default(function () {
                                    $lead = $this->getOwnerRecord();

                                    // Create an array with the lead's default contact information
                                    return [
                                        [
                                            'pic_name_impl' => $lead->companyDetail->name ?? $lead->name ?? '',
                                            'position' => $lead->companyDetail->position ?? '',
                                            'pic_phone_impl' => $lead->companyDetail->contact_no ?? $lead->phone ?? '',
                                            'pic_email_impl' => $lead->companyDetail->email ?? $lead->email ?? '',
                                        ],
                                    ];
                                }),
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

                                                    return "{$formattedId}-SW-REMARK-{$timestamp}-{$random}.{$extension}";
                                                }),
                                        ])
                                    ])
                                    ->itemLabel(fn() => __('Remark') . ' ' . ++self::$indexRepeater2)
                                    ->addActionLabel('Add Remark')
                                    ->maxItems(5)
                                    ->defaultItems(1),
                            ]),

                        Section::make('Step 5: Training')
                            ->schema([
                                Forms\Components\Radio::make('training_type')
                                    ->label('')
                                    ->options([
                                        'online_webinar_training' => 'Online Webinar Training',
                                        'online_hrdf_training' => 'Online HRDF Training',
                                    ])
                                    // ->inline()
                                    ->columns(2)
                                    ->required(),
                            ]),

                        Section::make('Step 6: Speaker Category')
                            ->schema([
                                Forms\Components\Radio::make('speaker_category')
                                    ->label('')
                                    ->options([
                                        'english / malay' => 'English / Malay',
                                        'mandarin' => 'Mandarin',
                                    ])
                                    // ->inline()
                                    ->columns(2)
                                    ->required(),
                            ]),

                        Section::make('Step 7: Proforma Invoice')
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

                        Section::make('Step 8: Attachment')
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

                                            return "{$formattedId}-SW-CONFIRM-{$timestamp}-{$random}.{$extension}";
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

                                            return "{$formattedId}-SW-HRDF-{$timestamp}-{$random}.{$extension}";
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

                                            return "{$formattedId}-SW-PAYMENT-{$timestamp}-{$random}.{$extension}";
                                        }),
                                    ])
                            ]),
                ])
                ->action(function (array $data): void {
                    $data['created_by'] = auth()->id();
                    $data['lead_id'] = $this->getOwnerRecord()->id;
                    $data['status'] = 'Draft';

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

                    if (isset($data['proforma_invoice_hrdf']) && is_array($data['proforma_invoice_hrdf'])) {
                        $data['proforma_invoice_hrdf'] = json_encode($data['proforma_invoice_hrdf']);
                    }

                    if (isset($data['proforma_invoice_product']) && is_array($data['proforma_invoice_product'])) {
                        $data['proforma_invoice_product'] = json_encode($data['proforma_invoice_product']);
                    }

                    // Create the handover record
                    $handover = SoftwareHandover::create($data);

                    // Generate PDF for non-draft handovers
                    if ($handover->status !== 'Draft') {
                        // Use the controller for PDF generation
                        app(GenerateSoftwareHandoverPdfController::class)->generateInBackground($handover);
                    }

                    Notification::make()
                        ->title($handover->status === 'Draft' ? 'Saved as Draft' : 'Software Handover Created Successfully')
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
                    ->formatStateUsing(function ($state, SoftwareHandover $record) {
                        // If no ID is provided, return a fallback
                        if (!$state) {
                            return 'Unknown';
                        }

                        // Format ID with prefix 250 and padding to ensure at least 3 digits
                        return '250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    }),
                TextColumn::make('submitted_at')
                    ->label('Date Submit')
                    ->date('d M Y'),
                TextColumn::make('training_type')
                    ->label('Training Type')
                    ->formatStateUsing(fn (string $state): string => Str::title(str_replace('_', ' ', $state))),
                TextColumn::make('kick_off_meeting')
                    ->label('Kick Off Meeting Date')
                    ->formatStateUsing(function ($state) {
                        return $state ? Carbon::parse($state)->format('d M Y') : 'N/A';
                    })
                    ->date('d M Y'),
                TextColumn::make('webinar_training')
                    ->label('Training Date')
                    ->formatStateUsing(function ($state) {
                        return $state ? Carbon::parse($state)->format('d M Y') : 'N/A';
                    })
                    ->date('d M Y'),
                TextColumn::make('implementer')
                    ->label('Implementer'),
                TextColumn::make('status')
                    ->label('STATUS')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'Draft' => new HtmlString('<span style="color: orange;">Draft</span>'),
                        'New' => new HtmlString('<span style="color: blue;">New</span>'),
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
                        ->visible(fn (SoftwareHandover $record): bool => in_array($record->status, ['New', 'Completed', 'Approved']))
                        // Use a callback function instead of arrow function for more control
                        ->modalContent(function (SoftwareHandover $record): View {

                            // Return the view with the record using $this->record pattern
                            return view('components.software-handover')
                            ->with('extraAttributes', ['record' => $record]);
                        }),

                        // Submit for Approval button - only visible for Draft status
                    Action::make('submit_for_approval')
                        ->label('Submit for Approval')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->visible(fn (SoftwareHandover $record): bool => $record->status === 'Draft')
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


                    Action::make('edit_software_handover')
                        ->label(function (SoftwareHandover $record): string {
                            // Format ID with prefix 250 and pad with zeros to ensure at least 3 digits
                            $formattedId = '250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                            return "Edit Software Handover {$formattedId}";
                        })
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->modalSubmitActionLabel('Save')
                        ->visible(fn (SoftwareHandover $record): bool => in_array($record->status, ['Draft']))
                        ->modalWidth(MaxWidth::SevenExtraLarge)
                        ->slideOver()
                        ->form([
                            Section::make('Step 1: Database')
                                ->schema([
                                    Grid::make(3)
                                        ->schema([
                                            TextInput::make('company_name')
                                                ->label('Company Name')
                                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                ->afterStateHydrated(fn($state) => Str::upper($state))
                                                ->afterStateUpdated(fn($state) => Str::upper($state))
                                                ->default(fn (SoftwareHandover $record) =>
                                                    $record->company_name ?? $this->getOwnerRecord()->companyDetail->company_name ?? null),
                                            TextInput::make('pic_name')
                                                ->label('Name')
                                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                ->afterStateHydrated(fn($state) => Str::upper($state))
                                                ->afterStateUpdated(fn($state) => Str::upper($state))
                                                ->default(fn (SoftwareHandover $record) =>
                                                    $record->pic_name ?? $this->getOwnerRecord()->companyDetail->name ?? $this->getOwnerRecord()->name),
                                            TextInput::make('pic_phone')
                                                ->label('HP Number')
                                                ->tel()
                                                ->default(fn (SoftwareHandover $record) =>
                                                    $record->pic_phone ?? $this->getOwnerRecord()->companyDetail->contact_no ?? $this->getOwnerRecord()->phone),
                                        ]),
                                    Grid::make(3)
                                        ->schema([
                                            TextInput::make('salesperson')
                                                ->readOnly()
                                                ->label('Salesperson')
                                                ->default(fn (SoftwareHandover $record) =>
                                                    $record->salesperson ?? ($this->getOwnerRecord()->salesperson ? User::find($this->getOwnerRecord()->salesperson)->name : null)),
                                            TextInput::make('headcount')
                                                ->numeric()
                                                ->label('Company Size')
                                                ->live(debounce:550)
                                                ->afterStateUpdated(function (Forms\Set $set, ?string $state, CategoryService $category) {
                                                    $set('category', $category->retrieve($state));
                                                })
                                                ->default(fn (SoftwareHandover $record) => $record->headcount ?? null)
                                                ->required(),
                                            TextInput::make('category')
                                                ->autocapitalize()
                                                ->live(debounce:550)
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

                            Section::make('Step 3: Implementation PICs')
                                ->schema([
                                    Forms\Components\Repeater::make('implementation_pics')
                                        ->label('Implementation PICs')
                                        ->hiddenLabel(true)
                                        ->schema([
                                            Grid::make(4)
                                            ->schema([
                                                TextInput::make('pic_name_impl')
                                                    ->required()
                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                    ->afterStateHydrated(fn($state) => Str::upper($state))
                                                    ->afterStateUpdated(fn($state) => Str::upper($state))
                                                    ->label('Name'),
                                                TextInput::make('position')
                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                    ->afterStateHydrated(fn($state) => Str::upper($state))
                                                    ->afterStateUpdated(fn($state) => Str::upper($state))
                                                    ->label('Position'),
                                                TextInput::make('pic_phone_impl')
                                                    ->required()
                                                    ->numeric()
                                                    ->label('HP Number'),
                                                TextInput::make('pic_email_impl')
                                                    ->required()
                                                    ->label('Email Address')
                                                    ->email(),
                                            ]),
                                        ])
                                        ->itemLabel(fn() => __('Person In Charge') . ' ' . ++self::$indexRepeater)
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

                                                        return "{$formattedId}-SW-REMARK-{$timestamp}-{$random}.{$extension}";
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
                                    Forms\Components\Radio::make('training_type')
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

                            Section::make('Step 6: Speaker Category')
                                ->columnSpan(1)
                                ->schema([
                                    Forms\Components\Radio::make('speaker_category')
                                        ->label('')
                                        ->options([
                                            'english / malay' => 'English / Malay',
                                            'mandarin' => 'Mandarin',
                                        ])
                                        // ->inline()
                                        ->columns(2)
                                        ->required()
                                        ->default(function (SoftwareHandover $record) {
                                            // Return the saved training type if it exists
                                            return $record->speaker_category ?? null;
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
                                        ]),
                                ]),
                        ])
                        ->action(function (SoftwareHandover $record, array $data): void {
                            // Handle file array encodings
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
                                app(GenerateSoftwareHandoverPdfController::class)->generateInBackground($record);
                            }

                            Notification::make()
                                ->title('Software handover updated successfully')
                                ->success()
                                ->send();
                        }),

                    Action::make('view_reason')
                        ->label('View Reason')
                        ->visible(fn (SoftwareHandover $record): bool => $record->status === 'Rejected')
                        ->icon('heroicon-o-magnifying-glass-plus')
                        ->modalHeading('Change Request Reason')
                        ->modalContent(fn ($record) => view('components.view-reason', [
                            'reason' => $record->reject_reason,
                        ]))
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalWidth('md')
                        ->color('warning'),

                    // Convert to Draft button - only visible for Rejected status
                    Action::make('convert_to_draft')
                        ->label('Convert to Draft')
                        ->icon('heroicon-o-document')
                        ->color('warning')
                        ->visible(fn (SoftwareHandover $record): bool => $record->status === 'Rejected')
                        ->action(function (SoftwareHandover $record): void {
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
