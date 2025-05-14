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
                ->modalHeading('Add Software Handover')
                ->modalWidth(MaxWidth::SevenExtraLarge)
                ->modalSubmitActionLabel('Save')
                ->form([
                    Section::make('Section 1: DATABASE - NON PAYROLL')
                        ->collapsible()
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('company_name')
                                        ->label('Company Name')
                                        ->default(fn () => $this->getOwnerRecord()->companyDetail->company_name ?? null),
                                    TextInput::make('salesperson')
                                        ->readOnly()
                                        ->label('Salesperson')
                                        ->default(fn () => $this->getOwnerRecord()->salesperson ? User::find($this->getOwnerRecord()->salesperson)->name : null),
                                ]),
                            Grid::make(2)
                                ->schema([
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
                                        ->autocapitalize()
                                        ->placeholder('Select a category')
                                        // ->options([
                                        //     'small' => 'SMALL',
                                        //     'medium' => 'MEDIUM',
                                        //     'large' => 'LARGE',
                                        //     'enterprise' => 'ENTERPRISE'
                                        // ])
                                        // ->searchable()
                                        ->readOnly(),
                                ]),
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('pic_name')
                                        ->label('PIC Name')
                                        ->default(fn () => $this->getOwnerRecord()->companyDetail->name ?? $this->getOwnerRecord()->name),
                                    TextInput::make('pic_phone')
                                        ->label('PIC HP No.')
                                        ->default(fn () => $this->getOwnerRecord()->companyDetail->contact_no ?? $this->getOwnerRecord()->phone),
                                ]),
                        ]),

                    Section::make('Section 2: DATABASE - PAYROLL')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('account_name')
                                        ->label('Account Name')
                                        ->readOnly()
                                        ->default(function () {
                                            // For new records, get the next ID by counting existing records + 1
                                            $nextId = \App\Models\SoftwareHandover::count() + 1;

                                            // Format with TTC prefix and ID
                                            return "TTC{$nextId}";
                                        }),
                                    TextInput::make('company_name')
                                        ->label('Company Name')
                                        ->default(fn () => $this->getOwnerRecord()->companyDetail->company_name ?? null),
                                ]),
                        ]),

                    Section::make('Section 3: INVOICE INFOFORMATION')
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
                                ->itemLabel(fn() => __('PIC') . ' ' . ++self::$indexRepeater)
                                ->columns(2),
                        ]),

                    Section::make('Section 5: REMARK INFORMATION')
                            ->schema([
                                Forms\Components\Repeater::make('remarks')
                                    ->label('Remarks')
                                    ->schema([
                                        Textarea::make('remark')
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
                                    ])
                                    ->itemLabel(fn() => __('Remark') . ' ' . ++self::$indexRepeater2)
                                    ->addActionLabel('Add Another Remark')
                                    ->defaultItems(1),
                            ]),


                    Grid::make(3)
                        ->schema([
                            Section::make('Section 6: TRAINING')
                            ->columnSpan(1)
                            ->schema([
                                Forms\Components\Radio::make('training_type')
                                    ->label('')
                                    ->options([
                                        'onsite_webinar_training' => 'Onsite Webinar Training',
                                        'online_hrdf_training' => 'Onsite HRDF Training',
                                    ])
                                    ->inline()
                                    ->required(),
                            ]),
                            Section::make('Section 7: PROFORMA INVOICE')
                                ->columnSpan(1) // Ensure it spans one column
                                ->schema([
                                    Select::make('proforma_invoice_product')
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
                                        ->preload(),
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
                                        ->preload(),
                                ]),

                            Section::make('Section 8: ATTACHMENTS')
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

                                        FileUpload::make('hrdf_grant_file')
                                            ->label('Upload HRDF Grant Approval Letter')
                                            ->disk('public')
                                            ->directory('handovers/hrdf_grant')
                                            ->visibility('public')
                                            ->multiple()
                                            ->maxFiles(3)
                                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png']),

                                        FileUpload::make('payment_slip_file')
                                            ->label('Upload Payment Slip')
                                            ->disk('public')
                                            ->live(debounce:500)
                                            ->directory('handovers/payment_slips')
                                            ->visibility('public')
                                            ->multiple()
                                            ->maxFiles(3)
                                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png']),
                                        ])
                                ]),
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
                    ->openUrlInNewTab(),
                TextColumn::make('created_at')
                    ->label('DATE')
                    ->date('d M Y'),
                TextColumn::make('payment_term')
                    ->label('PAYMENT TYPE')
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
                        ->modalContent(function (SoftwareHandover $record): View {

                            // Return the view with the record using $this->record pattern
                            return view('components.software-handover')
                            ->with('extraAttributes', ['record' => $record]);
                        }),

                    Action::make('edit_software_handover')
                        ->label('Edit Software Handover')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->modalSubmitActionLabel('Save')
                        ->visible(fn (SoftwareHandover $record): bool => in_array($record->status, ['New', 'Draft']))
                        ->modalWidth(MaxWidth::SevenExtraLarge)
                        ->slideOver()
                        ->form([
                            Section::make('Section 1: DATABASE - NON PAYROLL')
                                ->collapsible()
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('company_name')
                                                ->label('Company Name')
                                                ->default(fn (SoftwareHandover $record) =>
                                                    $record->company_name ?? $this->getOwnerRecord()->companyDetail->company_name ?? null),
                                            TextInput::make('salesperson')
                                                ->readOnly()
                                                ->label('Salesperson')
                                                ->default(fn (SoftwareHandover $record) =>
                                                    $record->salesperson ?? ($this->getOwnerRecord()->salesperson ? User::find($this->getOwnerRecord()->salesperson)->name : null)),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('headcount')
                                                ->numeric()
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
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('pic_name')
                                                ->label('PIC Name')
                                                ->default(fn (SoftwareHandover $record) =>
                                                    $record->pic_name ?? $this->getOwnerRecord()->companyDetail->name ?? $this->getOwnerRecord()->name),
                                            TextInput::make('pic_phone')
                                                ->label('PIC HP No.')
                                                ->default(fn (SoftwareHandover $record) =>
                                                    $record->pic_phone ?? $this->getOwnerRecord()->companyDetail->contact_no ?? $this->getOwnerRecord()->phone),
                                        ]),
                                ]),

                            Section::make('Section 2: DATABASE - PAYROLL')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('account_name')
                                                ->label('Account Name')
                                                ->readOnly()
                                                ->default(function () {
                                                    // For new records, get the next ID by counting existing records + 1
                                                    $nextId = \App\Models\SoftwareHandover::count() + 1;

                                                    // Format with TTC prefix and ID
                                                    return "TTC{$nextId}";
                                                }),
                                            TextInput::make('company_name')
                                                ->label('Company Name')
                                                ->default(fn (SoftwareHandover $record) =>
                                                    $record->company_name ?? $this->getOwnerRecord()->companyDetail->company_name ?? null),
                                        ]),
                                ]),

                            Section::make('Section 3: INVOICE INFOFORMATION')
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
                                        ->itemLabel(fn() => __('PIC') . ' ' . ++self::$indexRepeater)
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

                            Section::make('Section 5: REMARK INFORMATION')
                                ->schema([
                                    Forms\Components\Repeater::make('remarks')
                                        ->label('Remarks')
                                        ->schema([
                                            Textarea::make('remark')
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
                                        ->addActionLabel('Add Another Remark')
                                        ->default(function (SoftwareHandover $record) {
                                            if ($record && $record->remarks) {
                                                // If it's a string, decode it
                                                if (is_string($record->remarks)) {
                                                    return json_decode($record->remarks, true);
                                                }
                                                // If it's already an array, return it
                                                if (is_array($record->remarks)) {
                                                    return $record->remarks;
                                                }
                                            }
                                            return [];
                                        }),
                                ]),

                            Grid::make(3)
                                ->schema([
                                    Section::make('Section 6: TRAINING')
                                    ->columnSpan(1)
                                    ->schema([
                                        Forms\Components\Radio::make('training_type')
                                            ->label('')
                                            ->options([
                                                'onsite_webinar_training' => 'Onsite Webinar Training',
                                                'online_hrdf_training' => 'Onsite HRDF Training',
                                            ])
                                            ->inline()
                                            ->default(fn (SoftwareHandover $record) => $record->training_type ?? null)
                                            ->required(),
                                    ]),

                                    Section::make('Section 7: PROFORMA INVOICE')
                                        ->columnSpan(1)
                                        ->schema([
                                            Select::make('proforma_invoice_product')
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
                                        ]),

                                    Section::make('Section 8: ATTACHMENTS')
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
                                                        ->maxFiles(3)
                                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
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
                                                        ->maxFiles(3)
                                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
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
                                ]),
                        ])
                        ->action(function (SoftwareHandover $record, array $data): void {
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
                                app(GenerateSoftwareHandoverPdfController::class)->generateInBackground($record);
                            }

                            Notification::make()
                                ->title('Software handover updated successfully')
                                ->success()
                                ->send();
                        }),

                    // Submit for Approval button - only visible for Draft status
                    Action::make('submit_for_approval')
                        ->label('Submit for Approval')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->visible(fn (SoftwareHandover $record): bool => $record->status === 'Draft')
                        ->action(function (SoftwareHandover $record): void {
                            $record->update([
                                'status' => 'New'
                            ]);

                            // Use the controller for PDF generation
                            app(GenerateSoftwareHandoverPdfController::class)->generateInBackground($record);

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
