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
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Attributes\On;
use Filament\Forms\Get;
use Filament\Forms\Set;

class SoftwareHandoverRelationManager extends RelationManager
{
    protected static string $relationship = 'softwareHandover'; // Define the relationship name in the Lead model
    protected static ?int $indexRepeater = 0;
    protected static ?int $indexRepeater2 = 0;

    #[On('refresh-software-handovers')]
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
        return [
            Section::make('Step 1: Database')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextInput::make('company_name')
                                ->label('Company Name')
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->afterStateHydrated(fn($state) => Str::upper($state))
                                ->afterStateUpdated(fn($state) => Str::upper($state))
                                ->default(fn (?SoftwareHandover $record = null) =>
                                    $record?->company_name ?? $this->getOwnerRecord()->companyDetail->company_name ?? null),
                            TextInput::make('pic_name')
                                ->label('Name')
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->afterStateHydrated(fn($state) => Str::upper($state))
                                ->afterStateUpdated(fn($state) => Str::upper($state))
                                ->default(fn (?SoftwareHandover $record = null) =>
                                    $record?->pic_name ?? $this->getOwnerRecord()->companyDetail->name ?? $this->getOwnerRecord()->name),
                            TextInput::make('pic_phone')
                                ->label('HP Number')
                                ->tel()
                                ->default(fn (?SoftwareHandover $record = null) =>
                                    $record?->pic_phone ?? $this->getOwnerRecord()->companyDetail->contact_no ?? $this->getOwnerRecord()->phone),
                        ]),
                    Grid::make(3)
                        ->schema([
                            TextInput::make('salesperson')
                                ->readOnly()
                                ->label('Salesperson')
                                ->default(fn (?SoftwareHandover $record = null) =>
                                    $record?->salesperson ?? ($this->getOwnerRecord()->salesperson ? User::find($this->getOwnerRecord()->salesperson)->name : null)),
                            TextInput::make('headcount')
                                ->numeric()
                                ->live(debounce:550) // delay 550ms to allow user to have sufficient time to do input
                                ->afterStateUpdated(function (Forms\Set $set, ?string $state, CategoryService $category) {
                                    /**
                                     * set this company's category based on head count
                                     */
                                    $set('category', $category->retrieve($state));
                                })
                                ->required()
                                ->default(fn (?SoftwareHandover $record = null) => $record?->headcount ?? null),
                            TextInput::make('category')
                                ->label('Company Size')
                                ->dehydrated(false)
                                ->autocapitalize()
                                ->placeholder('Select a category')
                                ->default(function (?SoftwareHandover $record = null, CategoryService $category = null) {
                                    // If record exists with headcount, calculate category from headcount
                                    if ($record && $record->headcount && $category) {
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
                                    ->label('Export AutoCount Debtor')
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
                                    ->email()
                                    ->extraAlpineAttributes([
                                        'x-on:input' => '
                                            const start = $el.selectionStart;
                                            const end = $el.selectionEnd;
                                            const value = $el.value;
                                            $el.value = value.toLowerCase();
                                            $el.setSelectionRange(start, end);
                                        '
                                    ])
                                    ->dehydrateStateUsing(fn ($state) => strtolower($state)),
                            ]),
                        ])
                        ->addActionLabel('Add PIC')
                        ->minItems(1)
                        ->itemLabel(fn() => __('Person In Charge') . ' ' . ++self::$indexRepeater)
                        ->columns(2)
                        // Add default implementation PICs from lead data or existing record
                        ->default(function (?SoftwareHandover $record = null) {
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

                            // If no record, use lead data as default
                            $lead = $this->getOwnerRecord();
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
                        ->defaultItems(1)
                        ->default(function (?SoftwareHandover $record = null) {
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

            Grid::make(2)
            ->schema([
                Section::make('Step 5: Training Category')
                ->schema([
                    Forms\Components\Radio::make('training_type')
                        ->label('')
                        ->options([
                            'online_webinar_training' => 'Online Webinar Training',
                            'online_hrdf_training' => 'Online HRDF Training',
                        ])
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            // Clear proforma invoice fields when training category changes
                            $set('product_pi', null);
                            $set('non_hrdf_inv', null);
                            $set('hrdf_inv', null);
                            $set('sw_pi', null);
                        })
                        ->default(fn (?SoftwareHandover $record = null) => $record?->training_type ?? null),
                ])->columnSpan(1),

                Section::make('Step 6: Speaker Category')
                    ->schema([
                        Forms\Components\Radio::make('speaker_category')
                            ->label('')
                            ->options([
                                'english / malay' => 'English / Malay',
                                'mandarin' => 'Mandarin',
                            ])
                            ->live() // Make it react to headcount changes
                            ->afterStateHydrated(function (Forms\Set $set, Forms\Get $get, $state) {
                                $headcount = (int)$get('headcount');

                                // If headcount <= 25 and value is mandarin, reset to english/malay
                                if ($headcount <= 25 && $state === 'mandarin') {
                                    $set('speaker_category', 'english / malay');
                                }
                            })
                            ->required()
                            ->default(fn (?SoftwareHandover $record = null) => $record?->speaker_category ?? null),
                    ])->columnSpan(1),
            ]),

            Section::make('Step 7: Proforma Invoice')
                ->columnSpan(1) // Ensure it spans one column
                ->schema([
                    Grid::make(4)
                        ->schema([
                            Select::make('proforma_invoice_product')
                                ->required()
                                ->label('Software + Hardware')
                                ->options(function (RelationManager $livewire) {
                                    $leadId = $livewire->getOwnerRecord()->id;
                                    $currentRecordId = null;
                                    if ($livewire->mountedTableActionRecord) {
                                        if (is_object($livewire->mountedTableActionRecord)) {
                                            $currentRecordId = $livewire->mountedTableActionRecord->id;
                                        } else {
                                            $currentRecordId = $livewire->mountedTableActionRecord;
                                        }
                                    }

                                    $usedPiIds = [];
                                    $softwareHandovers = SoftwareHandover::where('lead_id', $leadId)
                                        ->when($currentRecordId, function ($query) use ($currentRecordId) {
                                            return $query->where('id', '!=', $currentRecordId);
                                        })
                                        ->get();

                                    foreach ($softwareHandovers as $handover) {
                                        $piProduct = $handover->proforma_invoice_product;
                                        if (!empty($piProduct)) {
                                            if (is_string($piProduct)) {
                                                $piIds = json_decode($piProduct, true);
                                                if (is_array($piIds)) {
                                                    $usedPiIds = array_merge($usedPiIds, $piIds);
                                                }
                                            } elseif (is_array($piProduct)) {
                                                $usedPiIds = array_merge($usedPiIds, $piProduct);
                                            }
                                        }
                                    }

                                    return \App\Models\Quotation::where('lead_id', $leadId)
                                        ->where('quotation_type', 'product')
                                        ->where('status', \App\Enums\QuotationStatusEnum::accepted)
                                        ->whereNotIn('id', array_filter($usedPiIds))
                                        ->where('quotation_date', '>=', now()->toDateString())
                                        ->pluck('pi_reference_no', 'id')
                                        ->toArray();
                                })
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->visible(fn (callable $get) => $get('training_type') === 'online_webinar_training')
                                ->required(fn (callable $get) => $get('training_type') === 'online_webinar_training')
                                ->default(function (?SoftwareHandover $record = null) {
                                    if (!$record || !$record->proforma_invoice_product) {
                                        return [];
                                    }
                                    if (is_string($record->proforma_invoice_product)) {
                                        return json_decode($record->proforma_invoice_product, true) ?? [];
                                    }
                                    return is_array($record->proforma_invoice_product) ? $record->proforma_invoice_product : [];
                                }),

                            // Software + Hardware PI - visible only for Online HRDF Training
                            Select::make('software_hardware_pi')
                                ->required(fn (callable $get) => $get('training_type') === 'online_hrdf_training')
                                ->label('Software + Hardware')
                                ->options(function (RelationManager $livewire) {
                                    $leadId = $livewire->getOwnerRecord()->id;
                                    $currentRecordId = null;
                                    if ($livewire->mountedTableActionRecord) {
                                        if (is_object($livewire->mountedTableActionRecord)) {
                                            $currentRecordId = $livewire->mountedTableActionRecord->id;
                                        } else {
                                            $currentRecordId = $livewire->mountedTableActionRecord;
                                        }
                                    }

                                    $usedPiIds = [];
                                    $softwareHandovers = SoftwareHandover::where('lead_id', $leadId)
                                        ->when($currentRecordId, function ($query) use ($currentRecordId) {
                                            return $query->where('id', '!=', $currentRecordId);
                                        })
                                        ->get();

                                    foreach ($softwareHandovers as $handover) {
                                        $fields = ['proforma_invoice_product', 'software_hardware_pi', 'non_hrdf_pi'];

                                        foreach ($fields as $field) {
                                            $piData = $handover->$field;
                                            if (!empty($piData)) {
                                                if (is_string($piData)) {
                                                    $piIds = json_decode($piData, true);
                                                    if (is_array($piIds)) {
                                                        $usedPiIds = array_merge($usedPiIds, $piIds);
                                                    }
                                                } elseif (is_array($piData)) {
                                                    $usedPiIds = array_merge($usedPiIds, $piData);
                                                }
                                            }
                                        }
                                    }

                                    return \App\Models\Quotation::where('lead_id', $leadId)
                                        ->where('quotation_type', 'product')
                                        ->where('status', \App\Enums\QuotationStatusEnum::accepted)
                                        ->whereNotIn('id', array_filter($usedPiIds))
                                        ->where('quotation_date', '>=', now()->toDateString())
                                        ->pluck('pi_reference_no', 'id')
                                        ->toArray();
                                })
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->visible(fn (callable $get) => $get('training_type') === 'online_hrdf_training')
                                ->default(function (?SoftwareHandover $record = null) {
                                    if (!$record || !$record->software_hardware_pi) {
                                        return [];
                                    }
                                    if (is_string($record->software_hardware_pi)) {
                                        return json_decode($record->software_hardware_pi, true) ?? [];
                                    }
                                    return is_array($record->software_hardware_pi) ? $record->software_hardware_pi : [];
                                }),

                            // Non-HRDF PI - visible only for Online HRDF Training
                            Select::make('non_hrdf_pi')
                                ->label('Non-HRDF Invoice')
                                ->options(function (RelationManager $livewire) {
                                    $leadId = $livewire->getOwnerRecord()->id;
                                    $currentRecordId = null;
                                    if ($livewire->mountedTableActionRecord) {
                                        if (is_object($livewire->mountedTableActionRecord)) {
                                            $currentRecordId = $livewire->mountedTableActionRecord->id;
                                        } else {
                                            $currentRecordId = $livewire->mountedTableActionRecord;
                                        }
                                    }

                                    $usedPiIds = [];
                                    $softwareHandovers = SoftwareHandover::where('lead_id', $leadId)
                                        ->when($currentRecordId, function ($query) use ($currentRecordId) {
                                            return $query->where('id', '!=', $currentRecordId);
                                        })
                                        ->get();

                                    foreach ($softwareHandovers as $handover) {
                                        $fields = ['proforma_invoice_product', 'software_hardware_pi', 'non_hrdf_pi'];

                                        foreach ($fields as $field) {
                                            $piData = $handover->$field;
                                            if (!empty($piData)) {
                                                if (is_string($piData)) {
                                                    $piIds = json_decode($piData, true);
                                                    if (is_array($piIds)) {
                                                        $usedPiIds = array_merge($usedPiIds, $piIds);
                                                    }
                                                } elseif (is_array($piData)) {
                                                    $usedPiIds = array_merge($usedPiIds, $piData);
                                                }
                                            }
                                        }
                                    }

                                    return \App\Models\Quotation::where('lead_id', $leadId)
                                        ->where('quotation_type', 'product')
                                        ->where('status', \App\Enums\QuotationStatusEnum::accepted)
                                        ->whereNotIn('id', array_filter($usedPiIds))
                                        ->where('quotation_date', '>=', now()->toDateString())
                                        ->pluck('pi_reference_no', 'id')
                                        ->toArray();
                                })
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->visible(fn (callable $get) => $get('training_type') === 'online_hrdf_training')
                                ->default(function (?SoftwareHandover $record = null) {
                                    if (!$record || !$record->non_hrdf_pi) {
                                        return [];
                                    }
                                    if (is_string($record->non_hrdf_pi)) {
                                        return json_decode($record->non_hrdf_pi, true) ?? [];
                                    }
                                    return is_array($record->non_hrdf_pi) ? $record->non_hrdf_pi : [];
                                }),

                            Select::make('proforma_invoice_hrdf')
                                ->label('HRDF Invoice')
                                ->required(fn (callable $get) => $get('training_type') === 'online_hrdf_training')
                                ->visible(fn (callable $get) => $get('training_type') === 'online_hrdf_training')
                                ->options(function (RelationManager $livewire) {
                                    $leadId = $livewire->getOwnerRecord()->id;
                                    $currentRecordId = null;
                                    if ($livewire->mountedTableActionRecord) {
                                        // Check if it's already a model object
                                        if (is_object($livewire->mountedTableActionRecord)) {
                                            $currentRecordId = $livewire->mountedTableActionRecord->id;
                                        } else {
                                            // If it's a string/ID, use it directly
                                            $currentRecordId = $livewire->mountedTableActionRecord;
                                        }
                                    }

                                    // Get all PI IDs already used in other software handovers for this lead
                                    $usedPiIds = [];
                                    $softwareHandovers = SoftwareHandover::where('lead_id', $leadId)
                                        ->when($currentRecordId, function ($query) use ($currentRecordId) {
                                            // Exclude current record if we're editing
                                            return $query->where('id', '!=', $currentRecordId);
                                        })
                                        ->get();

                                    // Extract used HRDF PI IDs from all handovers
                                    foreach ($softwareHandovers as $handover) {
                                        $piHrdf = $handover->proforma_invoice_hrdf;
                                        if (!empty($piHrdf)) {
                                            // Handle JSON string format
                                            if (is_string($piHrdf)) {
                                                $piIds = json_decode($piHrdf, true);
                                                if (is_array($piIds)) {
                                                    $usedPiIds = array_merge($usedPiIds, $piIds);
                                                }
                                            }
                                            // Handle array format
                                            elseif (is_array($piHrdf)) {
                                                $usedPiIds = array_merge($usedPiIds, $piHrdf);
                                            }
                                        }
                                    }

                                    // Get available HRDF PIs excluding already used ones
                                    return \App\Models\Quotation::where('lead_id', $leadId)
                                        ->where('quotation_type', 'hrdf')
                                        ->where('status', \App\Enums\QuotationStatusEnum::accepted)
                                        ->whereNotIn('id', array_filter($usedPiIds)) // Filter out null/empty values
                                        ->where('quotation_date', '>=', now()->toDateString())
                                        ->pluck('pi_reference_no', 'id')
                                        ->toArray();
                                })
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->default(function (?SoftwareHandover $record = null) {
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
                            })
                            ->default(function (?SoftwareHandover $record = null) {
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
                            ->live(debounce:500)
                            ->directory('handovers/payment_slips')
                            ->visibility('public')
                            ->multiple()
                            ->maxFiles(1)
                            ->openable()
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->openable()
                            ->required(function (Get $get) {
                                // Check if HRDF grant has actual files
                                $hrdfGrantFiles = $get('hrdf_grant_file');
                                $hasHrdfGrant = is_array($hrdfGrantFiles) && count($hrdfGrantFiles) > 0 && !empty(array_filter($hrdfGrantFiles));

                                // Only required if HRDF grant is empty
                                return !$hasHrdfGrant;
                            })
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
                            ->default(function (?SoftwareHandover $record = null) {
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
                            ->openable()
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->openable()
                            ->visible(fn (callable $get) => $get('training_type') === 'online_hrdf_training')
                            ->required(function (Get $get) {
                                // Check if payment slip has actual files
                                $paymentSlipFiles = $get('payment_slip_file');
                                $hasPaymentSlip = is_array($paymentSlipFiles) && count($paymentSlipFiles) > 0 && !empty(array_filter($paymentSlipFiles));

                                // Only required if payment slip is empty
                                return !$hasPaymentSlip;
                            })
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
                            })
                            ->default(function (?SoftwareHandover $record = null) {
                                if (!$record || !$record->hrdf_grant_file) {
                                    return [];
                                }
                                if (is_string($record->hrdf_grant_file)) {
                                    return json_decode($record->hrdf_grant_file, true) ?? [];
                                }
                                return is_array($record->hrdf_grant_file) ? $record->hrdf_grant_file : [];
                            }),

                        FileUpload::make('invoice_file')
                            ->label('Upload Invoice TimeTec Penang')
                            ->disk('public')
                            ->directory('handovers/invoices')
                            ->visibility('public')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->multiple()
                            ->maxFiles(10)
                            ->visible(fn () => in_array(auth()->id(), [1, 25]))
                            ->openable()
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                                $companyName = Str::slug($get('company_name') ?? 'invoice');
                                $date = now()->format('Y-m-d');
                                $random = Str::random(5);
                                $extension = $file->getClientOriginalExtension();

                                return "{$companyName}-invoice-{$date}-{$random}.{$extension}";
                            })
                            ->default(function (?SoftwareHandover $record = null) {
                                if (!$record || !$record->invoice_file) {
                                    return [];
                                }
                                if (is_string($record->invoice_file)) {
                                    return json_decode($record->invoice_file, true) ?? [];
                                }
                                return is_array($record->invoice_file) ? $record->invoice_file : [];
                            }),
                        ])
                ]),
        ];
    }

    public function headerActions(): array
    {
        $leadStatus = $this->getOwnerRecord()->lead_status ?? '';
        $isCompanyDetailsIncomplete = $this->isCompanyDetailsIncomplete();

        return [
            // Action 1: Warning notification when e-invoice is incomplete
            Tables\Actions\Action::make('EInvoiceWarning')
                ->label('Add Software Handover')
                ->icon('heroicon-o-plus')
                ->color('gray')
                ->visible(function () use ($leadStatus, $isCompanyDetailsIncomplete) {
                    return $leadStatus !== 'Closed' || $isCompanyDetailsIncomplete;
                })
                ->action(function () {
                    Notification::make()
                        ->warning()
                        ->title('Action Required')
                        ->body('Please close the lead and complete the company details before proceeding with the software handover.')
                        ->persistent()
                        ->send();
                }),

            // Action 2: Actual form when e-invoice is complete
            Tables\Actions\Action::make('AddSoftwareHandover')
                ->label('Add Software Handover')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->visible(function () use ($leadStatus, $isCompanyDetailsIncomplete) {
                    return $leadStatus === 'Closed' && !$isCompanyDetailsIncomplete;
                })
                ->slideOver()
                ->modalHeading('Software Handover')
                ->modalWidth(MaxWidth::FourExtraLarge)
                ->modalSubmitActionLabel('Submit')
                ->form($this->defaultForm())
                ->action(function (array $data): void {
                    $data['created_by'] = auth()->id();
                    $data['lead_id'] = $this->getOwnerRecord()->id;
                    $data['status'] = 'New';
                    $data['submitted_at'] = now();

                    // Process JSON encoding for array fields
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
                    foreach (['confirmation_order_file', 'payment_slip_file', 'proforma_invoice_hrdf',
                            'proforma_invoice_product', 'invoice_file', 'implementation_pics',
                            'hrdf_grant_file'] as $field) {
                        if (isset($data[$field]) && is_array($data[$field])) {
                            $data[$field] = json_encode($data[$field]);
                        }
                    }

                    // Create the handover record
                    $nextId = $this->getNextAvailableId();

                    // Create the handover record with specific ID
                    $handover = new SoftwareHandover();
                    $handover->id = $nextId;
                    $handover->fill($data);
                    $handover->save();

                    app(GenerateSoftwareHandoverPdfController::class)->generateInBackground($handover);

                    try {
                        // Format handover ID
                        $handoverId = 'SW_250' . str_pad($handover->id, 3, '0', STR_PAD_LEFT);

                        // Get company name from CompanyDetail
                        $companyDetail = \App\Models\CompanyDetail::where('lead_id', $handover->lead_id)->first();
                        $companyName = $companyDetail ? $companyDetail->company_name : ($handover->company_name ?? 'Unknown Company');

                        // Prepare email data
                        $emailData = [
                            'date' => now()->format('d M Y'),
                            'sw_id' => $handoverId,
                            'salesperson' => $handover->salesperson ?? '-',
                            'company_name' => $companyName,
                            'form_url' => $handover->handover_pdf ? url('storage/' . $handover->handover_pdf) : null,
                        ];

                        Mail::send('emails.handover_submitted_notification', [
                            'date' => $emailData['date'],
                            'sw_id' => $emailData['sw_id'],
                            'salesperson' => $emailData['salesperson'],
                            'company_name' => $emailData['company_name'],
                            'form_url' => $emailData['form_url'],
                        ], function ($message) use ($emailData) {
                            $message->to(['faiz@timeteccloud.com', 'fazuliana.mohdarsad@timeteccloud.com'])
                                ->subject("NEW SOFTWARE HANDOVER ID {$emailData['sw_id']}");
                        });

                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Failed to send software handover notification email", [
                            'error' => $e->getMessage(),
                            'handover_id' => $handover->id ?? null
                        ]);
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
            ->poll('300s')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->headerActions($this->headerActions())
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
                            ->modalWidth('6xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (SoftwareHandover $record): View {
                                return view('components.software-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),
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
                        ->modalHeading(false)
                        ->modalWidth('6xl')
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
                        ->modalHeading(function (SoftwareHandover $record): string {
                            // Format ID with prefix 250 and pad with zeros to ensure at least 3 digits
                            $formattedId = 'SW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                            return "Edit Software Handover {$formattedId}";
                        })
                        ->label('Edit Software Handover')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->modalSubmitActionLabel('Save')
                        ->visible(fn (SoftwareHandover $record): bool => in_array($record->status, ['Draft']))
                        ->modalWidth(MaxWidth::FourExtraLarge)
                        ->slideOver()
                        ->form($this->defaultForm())
                        ->action(function (SoftwareHandover $record, array $data): void {
                            // Process JSON encoding for array fields
                            foreach (['remarks', 'confirmation_order_file', 'payment_slip_file', 'implementation_pics',
                                     'proforma_invoice_product', 'proforma_invoice_hrdf', 'invoice_file', 'hrdf_grant_file'] as $field) {
                                if (isset($data[$field]) && is_array($data[$field])) {
                                    // Special handling for remarks to process attachments
                                    if ($field === 'remarks') {
                                        foreach ($data[$field] as $key => $remark) {
                                            // Encode attachments only if they exist and are array
                                            if (!empty($remark['attachments']) && !is_string($remark['attachments'])) {
                                                $data[$field][$key]['attachments'] = json_encode($remark['attachments']);
                                            }
                                        }
                                    }
                                    $data[$field] = json_encode($data[$field]);
                                }
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
                        ->modalWidth('3xl')
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
                ->label('Actions')
                ->color('primary')
                ->button(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    protected function isCompanyDetailsIncomplete(): bool
    {
        $lead = $this->getOwnerRecord();
        $companyDetail = $lead->companyDetail ?? null;

        // If no company details exist at all
        if (!$companyDetail) {
            return true;
        }

        // Check if any essential company details are missing
        $requiredFields = [
            'company_name',
            'contact_no',
            'email',
            'name',
            'position',
            'state',
            'postcode',
            'company_address1',
            'company_address2',
        ];

        foreach ($requiredFields as $field) {
            if (empty($companyDetail->$field)) {
                return true;
            }
        }

        // Special check for reg_no_new - must exist and have exactly 12 digits
        if (empty($companyDetail->reg_no_new)) {
            return true;
        }

        // Convert to string and remove any non-digit characters
        $regNoValue = preg_replace('/[^0-9]/', '', $companyDetail->reg_no_new);

        // Check if the resulting string has exactly 12 digits
        if (strlen($regNoValue) !== 12) {
            return true;
        }

        return false;
    }

    private function getNextAvailableId()
    {
        // Get all existing IDs in the table
        $existingIds = SoftwareHandover::pluck('id')->toArray();

        if (empty($existingIds)) {
            return 1; // If table is empty, start with ID 1
        }

        // Find the highest ID currently in use
        $maxId = max($existingIds);

        // Check for gaps from ID 1 to maxId
        for ($i = 1; $i <= $maxId; $i++) {
            if (!in_array($i, $existingIds)) {
                // Found a gap, return this ID
                return $i;
            }
        }

        // No gaps found, return next ID after max
        return $maxId + 1;
    }
}
