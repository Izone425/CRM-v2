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
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Attributes\On;

class HardwareHandoverRelationManager extends RelationManager
{
    protected static string $relationship = 'hardwareHandover'; // Define the relationship name in the Lead model
    protected static ?int $indexRepeater2 = 0;

    #[On('refresh-hardware-handovers')]
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
            Section::make('Step 1: Invoice Type')
                ->schema([
                    Forms\Components\Radio::make('invoice_type')
                        ->hiddenLabel()
                        ->options([
                            'single' => 'Single Invoice (Hardware Only)',
                            'combined' => 'Combined Invoice (Hardware + Software)',
                        ])
                        ->default(function (?HardwareHandover $record) {
                            // Use the record's value if it exists, otherwise default to 'single'
                            return $record?->invoice_type ?? 'single';
                        })
                        ->reactive()
                        ->inline()
                        ->inlineLabel(false)
                        ->required(),

                    Forms\Components\Select::make('related_software_handovers')
                        ->label('Select Software Handovers to Combine With')
                        ->options(function () {
                            $leadId = $this->getOwnerRecord()->id;
                            return \App\Models\SoftwareHandover::where('lead_id', $leadId)
                                ->orderBy('created_at', 'desc')
                                ->get()
                                ->mapWithKeys(function ($handover) {
                                    $id = $handover->id;
                                    $formattedId = 'SW_250' . str_pad($id, 3, '0', STR_PAD_LEFT);
                                    $date = $handover->created_at ? $handover->created_at->format('d M Y') : 'Unknown date';
                                    return [$id => "{$formattedId} - {$date}"];
                                })
                                ->toArray();
                        })
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->visible(fn (callable $get) => $get('invoice_type') === 'combined')
                        ->required(fn (callable $get) => $get('invoice_type') === 'combined')
                        ->default(function (?HardwareHandover $record) {
                            if (!$record || !$record->related_software_handovers) {
                                return [];
                            }

                            if (is_string($record->related_software_handovers)) {
                                return json_decode($record->related_software_handovers, true) ?? [];
                            }

                            return is_array($record->related_software_handovers) ? $record->related_software_handovers : [];
                        }),
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
            Section::make('Step 3: Contact Detail')
                ->schema([
                    Forms\Components\Repeater::make('contact_detail')
                        ->label('Contact Detail')
                        ->hiddenLabel(true)
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    TextInput::make('pic_name')
                                        ->required()
                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                        ->afterStateHydrated(fn($state) => Str::upper($state))
                                        ->afterStateUpdated(fn($state) => Str::upper($state))
                                        ->label('Name'),
                                    TextInput::make('pic_phone')
                                        ->required()
                                        ->numeric()
                                        ->label('HP Number'),
                                    TextInput::make('pic_email')
                                        ->required()
                                        ->label('Email Address')
                                        ->email(),
                                ]),
                        ])
                        ->itemLabel(function (array $state): ?string {
                            static $counter = 0;
                            $counter++;
                            return 'Contact Person ' . $counter;
                        })
                        ->default(function (?HardwareHandover $record) {
                            if (!$record) {
                                $lead =  $this->getOwnerRecord();
                                return [
                                    [
                                        'pic_name' => $lead->companyDetail->name ?? $lead->name,
                                        'pic_phone' => $lead->companyDetail->contact_no ?? $lead->phone,
                                        'pic_email' => $lead->companyDetail->email ?? $lead->email,
                                    ]
                                ];
                            } elseif ($record && $record->contact_detail) {
                                // Decode the specific contact_detail field, not the entire record
                                return json_decode($record->contact_detail, true);
                            } else {
                                return null;
                            }
                        })
                ]),


            Section::make('Step 4: Category 1')
                ->schema([
                    Forms\Components\Radio::make('installation_type')
                        ->label('')
                        ->options([
                            'courier' => 'Courier',
                            'internal_installation' => 'Internal Installation',
                            'external_installation' => 'External Installation',
                            'self_pick_up' => 'Pick-Up',
                        ])
                        // ->inline()
                        ->live(debounce: 500)
                        ->afterStateUpdated(function ($set, $state) {
                            if ($state === 'external_installation') {
                                $set('category2.pic_name', '');
                                $set('category2.pic_phone', '');
                                $set('category2.email', '');
                            } elseif ($state === 'courier') {
                                $set('category2.pic_name', $this->getOwnerRecord()->companyDetail->name ?? $this->getOwnerRecord()->name);
                                $set('category2.pic_phone', $this->getOwnerRecord()->companyDetail->contact_no ?? $this->getOwnerRecord()->contact_no);
                                $set('category2.email', $this->getOwnerRecord()->companyDetail->email ?? $this->getOwnerRecord()->email);
                            }
                        })
                        ->columns(4)
                        ->default(fn(?HardwareHandover $record) => $record->installation_type ?? null)
                        ->required(),
                ]),

            Section::make('Step 5: Category 2')
                ->schema([
                    Forms\Components\Placeholder::make('installation_type_helper')
                        ->label('')
                        ->content('Please select any option Installation Type 1 at Step 4 to see the relevant fields.')
                        ->visible(fn(callable $get) => empty($get('installation_type')))
                        ->inlineLabel(),

                    Grid::make(1)
                        ->schema([
                            Select::make('category2.installer')
                                ->label('Installer')
                                ->visible(fn(callable $get) => $get('installation_type') === 'internal_installation')
                                ->required()
                                ->options(function () {
                                    // Retrieve options from the installer table
                                    return \App\Models\Installer::pluck('company_name', 'id')->toArray();
                                })
                                ->default(function (?HardwareHandover $record) {
                                    // First check if record has category2 data already
                                    if ($record && $record->category2) {
                                        $category2 = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                        if (isset($category2['installer']) && !empty($category2['installer'])) {
                                            return $category2['installer'];
                                        }
                                    }

                                    // No default installer if none found in the record
                                    return null;
                                })
                                ->searchable()
                                ->preload(),
                            Select::make('category2.reseller')
                                ->label('Reseller')
                                ->visible(fn(callable $get) => $get('installation_type') === 'external_installation')
                                ->required()
                                ->options(function () {
                                    // Retrieve options from the reseller table
                                    return \App\Models\Reseller::pluck('company_name', 'id')->toArray();
                                })
                                ->default(function (?HardwareHandover $record) {
                                    // First check if record has category2 data already
                                    if ($record && $record->category2) {
                                        $category2 = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                        if (isset($category2['reseller']) && !empty($category2['reseller'])) {
                                            return $category2['reseller'];
                                        }
                                    }

                                    // No default reseller if none found in the record
                                    return null;
                                })
                                ->searchable()
                                ->preload(),
                            Forms\Components\Repeater::make('category2.courier_addresses')
                                ->label('Courier Addresses')
                                ->schema([
                                    TextArea::make('address')
                                        ->label('ADDRESS:')
                                        ->required()
                                        ->rows(3)
                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                        ->afterStateHydrated(fn($state) => Str::upper($state))
                                        ->afterStateUpdated(fn($state) => Str::upper($state))
                                        ->default("ADDRESS:\nDEVICE MODEL:\nTOTAL UNIT:"),
                                    ])
                                ->itemLabel(function (array $state): ?string {
                                    static $counter = 0;
                                    $counter++;
                                    return 'Courier Address ' . $counter;
                                })
                                ->addActionLabel('Add Another Address')
                                ->maxItems(10)
                                ->defaultItems(1)
                                ->default(function (?HardwareHandover $record = null) {
                                    // If editing existing record, return saved data
                                    if ($record && $record->category2) {
                                        $category2 = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                        if (isset($category2['courier_addresses']) && !empty($category2['courier_addresses'])) {
                                            return $category2['courier_addresses'];
                                        }
                                    }

                                    // Default template for new records with pre-filled address
                                    $owner = $this->getOwnerRecord();
                                    $defaultAddress = '';

                                    if ($owner->companyDetail) {
                                        $defaultAddress = $owner->companyDetail->company_address1 ?? '';
                                        if (!empty($owner->companyDetail->company_address2)) {
                                            $defaultAddress .= ", " . $owner->companyDetail->company_address2;
                                        }
                                        if (!empty($owner->companyDetail->postcode) || !empty($owner->companyDetail->state)) {
                                            $defaultAddress .= ", " .
                                                ($owner->companyDetail->postcode ?? '') . " " .
                                                ($owner->companyDetail->state ?? '');
                                        }
                                    } else {
                                        $defaultAddress = $owner->address1 ?? '';
                                        if (!empty($owner->address2)) {
                                            $defaultAddress .= ", " . $owner->address2;
                                        }
                                        if (!empty($owner->postcode) || !empty($owner->state)) {
                                            $defaultAddress .= ", " .
                                                ($owner->postcode ?? '') . " " .
                                                ($owner->state ?? '');
                                        }
                                    }

                                    return [
                                        [
                                            'address' => "ADDRESS: " . strtoupper($defaultAddress) . "\nDEVICE MODEL:\nTOTAL UNIT:",
                                        ]
                                    ];
                                })
                                ->visible(fn(callable $get) => $get('installation_type') === 'courier')
                                ->columnSpanFull(),
                            TextArea::make('category2.pickup_address')
                                ->label('Pickup Address')
                                ->required()
                                ->rows(2)
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->afterStateHydrated(fn($state) => Str::upper($state))
                                ->afterStateUpdated(fn($state) => Str::upper($state))
                                ->default(function (?HardwareHandover $record = null) {
                                    return 'TimeTec Cloud @ PFCC, Puchong Selangor';
                                })
                                ->visible(fn(callable $get) => $get('installation_type') === 'self_pick_up'),
                            Grid::make(3)
                                ->schema([
                                    TextInput::make('category2.pic_name')
                                        ->label('Name')
                                        ->required()
                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                        ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : $state)
                                        ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : $state)
                                        ->default(function (?HardwareHandover $record) {
                                            // First check if record has category2 data already
                                            if ($record && $record->category2) {
                                                $category2 = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                                if (isset($category2['pic_name']) && !empty($category2['pic_name'])) {
                                                    return $category2['pic_name'];
                                                }
                                            }

                                            // If no record data, fall back to company detail
                                            return $this->getOwnerRecord()->companyDetail->name ?? $this->getOwnerRecord()->name;
                                        })
                                        ->visible(fn(callable $get) => $get('installation_type') === 'external_installation'),

                                    TextInput::make('category2.pic_phone')
                                        ->label('HP Number')
                                        ->tel()
                                        ->required()
                                        ->default(function (?HardwareHandover $record) {
                                            // First check if record has category2 data already
                                            if ($record && $record->category2) {
                                                $category2 = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                                if (isset($category2['pic_phone']) && !empty($category2['pic_phone'])) {
                                                    return $category2['pic_phone'];
                                                }
                                            }

                                            // If no record data, fall back to company detail
                                            return $this->getOwnerRecord()->companyDetail->contact_no ?? $this->getOwnerRecord()->contact_no;
                                        })
                                        ->visible(fn(callable $get) => $get('installation_type') === 'external_installation'),

                                    TextInput::make('category2.email')
                                        ->label('Email Address')
                                        ->required()
                                        ->email()
                                        ->default(function (?HardwareHandover $record) {
                                            // First check if record has category2 data already
                                            if ($record && $record->category2) {
                                                $category2 = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                                if (isset($category2['email']) && !empty($category2['email'])) {
                                                    return $category2['email'];
                                                }
                                            }

                                            // If no record data, fall back to company detail
                                            return $this->getOwnerRecord()->companyDetail->email ?? $this->getOwnerRecord()->email;
                                        })
                                        ->visible(fn(callable $get) => $get('installation_type') === 'external_installation'),
                                ]),
                        ]),
                ]),

            Section::make('Step 6: Remark Details')
                ->schema([
                    Textarea::make('remarks')
                        ->label('Remarks')
                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                        ->afterStateHydrated(fn($state) => Str::upper($state))
                        ->afterStateUpdated(fn($state) => Str::upper($state))
                        ->placeholder('Enter remark here')
                        ->autosize()
                        ->rows(3)
                        ->default(function (?HardwareHandover $record) {
                            return $record?->remarks ?? '';
                        }),
                ]),

            Section::make('Step 7: Video Details')
                ->schema([
                    FileUpload::make('video_files')
                        ->label('Upload Videos (MP4, MOV, AVI)')
                        ->disk('public')
                        ->directory('handovers/videos')
                        ->visibility('public')
                        ->multiple()
                        ->maxFiles(3)
                        ->maxSize(10000) // 100MB max size
                        ->acceptedFileTypes([
                            'video/mp4',
                            'video/quicktime',
                            'video/x-msvideo',
                            'video/x-ms-wmv',
                            'video/webm'
                        ])
                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                            // Get lead ID from ownerRecord
                            $leadId = $this->getOwnerRecord()->id;
                            // Format ID with prefix (250) and padding
                            $formattedId = '250' . str_pad($leadId, 3, '0', STR_PAD_LEFT);
                            // Get extension
                            $extension = $file->getClientOriginalExtension();

                            // Generate a unique identifier (timestamp) to avoid overwriting files
                            $timestamp = now()->format('YmdHis');
                            $random = rand(1000, 9999);

                            return "{$formattedId}-HW-VIDEO-{$timestamp}-{$random}.{$extension}";
                        })
                        ->openable()
                        ->previewable(false) // No preview for videos directly in form
                        ->downloadable()
                        ->default(function (?HardwareHandover $record) {
                            if (!$record || !$record->video_files) {
                                return [];
                            }
                            if (is_string($record->video_files)) {
                                return json_decode($record->video_files, true) ?? [];
                            }
                            return is_array($record->video_files) ? $record->video_files : [];
                        }),
                ]),

            Section::make('Step 8: Proforma Invoice')
                ->columnSpan(1) // Ensure it spans one column
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('proforma_invoice_product')
                                ->required()
                                ->label('Product')
                                ->options(function (RelationManager $livewire) {
                                    $leadId = $livewire->getOwnerRecord()->id;
                                    $currentRecordId = $this->getCurrentRecordId();

                                    // Get all PI IDs already used in other hardware handovers for this lead
                                    $usedPiIds = [];
                                    $hardwareHandovers = \App\Models\HardwareHandover::where('lead_id', $leadId)
                                        ->when($currentRecordId, function ($query) use ($currentRecordId) {
                                            // Exclude current record if we're editing
                                            return $query->where('id', '!=', $currentRecordId);
                                        })
                                        ->get();

                                    // Extract used product PI IDs from all handovers
                                    foreach ($hardwareHandovers as $handover) {
                                        $piProduct = $handover->proforma_invoice_product;
                                        if (!empty($piProduct)) {
                                            // Handle JSON string format
                                            if (is_string($piProduct)) {
                                                $piIds = json_decode($piProduct, true);
                                                if (is_array($piIds)) {
                                                    $usedPiIds = array_merge($usedPiIds, $piIds);
                                                }
                                            }
                                            // Handle array format
                                            elseif (is_array($piProduct)) {
                                                $usedPiIds = array_merge($usedPiIds, $piProduct);
                                            }
                                        }
                                    }

                                    // Get available product PIs excluding already used ones
                                    return \App\Models\Quotation::where('lead_id', $leadId)
                                        ->where('quotation_type', 'product')
                                        ->where('status', \App\Enums\QuotationStatusEnum::accepted)
                                        ->whereNotIn('id', array_filter($usedPiIds)) // Filter out null/empty values
                                        ->pluck('pi_reference_no', 'id')
                                        ->toArray();
                                })
                                ->multiple()
                                ->searchable()
                                ->default(function (?HardwareHandover $record) {
                                    if (!$record || !$record->proforma_invoice_product) {
                                        return [];
                                    }
                                    if (is_string($record->proforma_invoice_product)) {
                                        return json_decode($record->proforma_invoice_product, true) ?? [];
                                    }
                                    return is_array($record->proforma_invoice_product) ? $record->proforma_invoice_product : [];
                                })
                                ->preload(),

                            Select::make('proforma_invoice_hrdf')
                                ->label('HRDF')
                                ->options(function (RelationManager $livewire) {
                                    $leadId = $livewire->getOwnerRecord()->id;
                                    $currentRecordId = $this->getCurrentRecordId();

                                    // Get all PI IDs already used in other hardware handovers for this lead
                                    $usedPiIds = [];
                                    $hardwareHandovers = \App\Models\HardwareHandover::where('lead_id', $leadId)
                                        ->when($currentRecordId, function ($query) use ($currentRecordId) {
                                            // Exclude current record if we're editing
                                            return $query->where('id', '!=', $currentRecordId);
                                        })
                                        ->get();

                                    // Extract used HRDF PI IDs from all handovers
                                    foreach ($hardwareHandovers as $handover) {
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
                                        ->pluck('pi_reference_no', 'id')
                                        ->toArray();
                                })
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->default(function (?HardwareHandover $record) {
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

            Section::make('Step 9: Attachment')
                ->columnSpan(1) // Ensure it spans one column
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
                                ->default(function (?HardwareHandover $record) {
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
                                ->default(function (?HardwareHandover $record) {
                                    if (!$record || !$record->payment_slip_file) {
                                        return [];
                                    }
                                    if (is_string($record->payment_slip_file)) {
                                        return json_decode($record->payment_slip_file, true) ?? [];
                                    }
                                    return is_array($record->payment_slip_file) ? $record->payment_slip_file : [];
                                }),

                            FileUpload::make('invoice_file')
                                ->label('Upload Invoice')
                                ->disk('public')
                                ->directory('handovers/invoices')
                                ->visibility('public')
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                ->multiple()
                                ->maxFiles(10)
                                ->helperText('Upload invoice files (PDF, JPG, PNG formats accepted)')
                                ->openable()
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                                    $companyName = Str::slug($get('company_name') ?? 'invoice');
                                    $date = now()->format('Y-m-d');
                                    $random = Str::random(5);
                                    $extension = $file->getClientOriginalExtension();

                                    return "{$companyName}-invoice-{$date}-{$random}.{$extension}";
                                })
                                ->default(function (?HardwareHandover $record) {
                                    if (!$record || !$record->invoice_file) {
                                        return [];
                                    }
                                    if (is_string($record->invoice_file)) {
                                        return json_decode($record->invoice_file, true) ?? [];
                                    }
                                    return is_array($record->invoice_file) ? $record->invoice_file : [];
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
                                })
                                ->default(function (?HardwareHandover $record) {
                                    if (!$record || !$record->hrdf_grant_file) {
                                        return [];
                                    }
                                    if (is_string($record->hrdf_grant_file)) {
                                        return json_decode($record->hrdf_grant_file, true) ?? [];
                                    }
                                    return is_array($record->hrdf_grant_file) ? $record->hrdf_grant_file : [];
                                }),


                        ])
                ]),
        ];
    }

    public function headerActions(): array
    {
        $isCompanyDetailsIncomplete = $this->isCompanyDetailsIncomplete();
        $leadStatus = $this->getOwnerRecord()->lead_status ?? '';

        return [
            // Action 1: Warning notification when e-invoice is incomplete
            Tables\Actions\Action::make('EInvoiceWarning')
                ->label('Add Hardware Handover')
                ->icon('heroicon-o-plus')
                ->color('gray')
                ->visible(function () use ($leadStatus, $isCompanyDetailsIncomplete) {
                    return $leadStatus !== 'Closed' || $isCompanyDetailsIncomplete;
                })
                ->action(function () {
                    Notification::make()
                        ->warning()
                        ->title('Action Required')
                        ->body('Please close the lead and complete the company details before proceeding with the hardware handover.')
                        ->persistent()
                        ->send();
                }),

            // Action 2: Actual form when e-invoice is complete
            Tables\Actions\Action::make('AddHardwareHandover')
                ->label('Add Hardware Handover')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->visible(function () use ($leadStatus, $isCompanyDetailsIncomplete) {
                    return $leadStatus === 'Closed' && !$isCompanyDetailsIncomplete;
                })
                ->slideOver()
                ->modalSubmitActionLabel('Submit')
                ->modalHeading('Add Hardware Handover')
                ->modalWidth(MaxWidth::FourExtraLarge)
                ->form($this->defaultForm())
                ->action(function (array $data): void { // CREATE HARDWARE HANDOVER

                    $data['created_by'] = auth()->id();
                    $data['lead_id'] = $this->getOwnerRecord()->id;
                    $data['status'] = 'New';
                    $data['submitted_at'] = now();

                    if(isset($data['contact_detail']) && is_array($data['contact_detail'])){
                        $data['contact_detail'] = json_encode($data['contact_detail']);
                    }

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


                    if (isset($data['invoice_file']) && is_array($data['invoice_file'])) {
                        $data['invoice_file'] = json_encode($data['invoice_file']);
                    }


                    if (isset($data['hrdf_grant_file']) && is_array($data['hrdf_grant_file'])) {
                        $data['hrdf_grant_file'] = json_encode($data['hrdf_grant_file']);
                    }

                    if (isset($data['installation_media']) && is_array($data['installation_media'])) {
                        $data['installation_media'] = json_encode($data['installation_media']);
                    }

                    if (isset($data['proforma_invoice_number']) && is_array($data['proforma_invoice_number'])) {
                        $data['proforma_invoice_number'] = json_encode($data['proforma_invoice_number']);
                    }

                    if (isset($data['invoice_type']) && $data['invoice_type'] === 'combined') {
                        if (isset($data['related_software_handovers']) && is_array($data['related_software_handovers'])) {
                            $data['related_software_handovers'] = json_encode($data['related_software_handovers']);
                        } else {
                            $data['related_software_handovers'] = json_encode([]);
                        }
                    } else {
                        $data['related_software_handovers'] = null;
                    }

                    if (isset($data['video_files']) && is_array($data['video_files'])) {
                        $data['video_files'] = json_encode($data['video_files']);
                    }

                    if (isset($data['related_software_handovers']) && !empty($data['related_software_handovers'])) {
                        // Decode if it's already been JSON encoded
                        $softwareHandovers = is_string($data['related_software_handovers'])
                            ? json_decode($data['related_software_handovers'], true)
                            : $data['related_software_handovers'];

                        if (!empty($softwareHandovers)) {
                            $firstSoftwareHandoverId = is_array($softwareHandovers) ? $softwareHandovers[0] : $softwareHandovers;

                            if ($firstSoftwareHandoverId) {
                                $softwareHandover = \App\Models\SoftwareHandover::find($firstSoftwareHandoverId);

                                if ($softwareHandover && $softwareHandover->implementer) {
                                    $data['implementer'] = $softwareHandover->implementer;
                                }
                            }
                        }
                    } else {
                        // If no related software handovers, check for other software handovers for this lead
                        $leadId = $this->getOwnerRecord()->id;
                        $softwareHandover = \App\Models\SoftwareHandover::where('lead_id', $leadId)
                            ->whereNotNull('implementer')
                            ->orderBy('created_at', 'desc')
                            ->first();

                        if ($softwareHandover && $softwareHandover->implementer) {
                            $data['implementer'] = $softwareHandover->implementer;
                        }
                    }

                    $nextId = $this->getNextAvailableId();

                    // Create the handover record with specific ID
                    $handover = new HardwareHandover();
                    $handover->id = $nextId;
                    $handover->fill($data);
                    $handover->save();

                    app(GenerateHardwareHandoverPdfController::class)->generateInBackground($handover);

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
            ->poll('300s')
            ->emptyState(fn() => view('components.empty-state-question'))
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
                        return 'HW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('6xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HardwareHandover $record): View {
                                return view('components.hardware-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),
                TextColumn::make('submitted_at')
                    ->label('Date Submit')
                    ->date('d M Y'),
                TextColumn::make('installation_type')
                    ->label('Category 1')
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'courier' => 'Courier',
                            'internal_installation' => 'Internal Installation',
                            'external_installation' => 'External Installation',
                            'self_pick_up' => 'Pick-Up',
                            default => ucfirst($state),
                        };
                    }),
                // TextColumn::make('category2')
                //     ->label('Category 2')
                //     ->formatStateUsing(function ($state, HardwareHandover $record) {
                //         // If empty, return a placeholder
                //         if (empty($state)) {
                //             return '-';
                //         }

                //         // Decode JSON if it's a string
                //         $data = is_string($state) ? json_decode($state, true) : $state;

                //         // Format based on installation type
                //         if ($record->installation_type === 'courier') {
                //             $parts = [];

                //             if (!empty($data['email'])) {
                //                 $parts[] = "Email: {$data['email']}";
                //             }

                //             if (!empty($data['pic_name'])) {
                //                 $parts[] = "Name: {$data['pic_name']}";
                //             }

                //             if (!empty($data['pic_phone'])) {
                //                 $parts[] = "Phone: {$data['pic_phone']}";
                //             }

                //             if (!empty($data['courier_address'])) {
                //                 $parts[] = "Address: {$data['courier_address']}";
                //             }

                //             // Return the formatted parts with HTML line breaks instead of pipes
                //             return !empty($parts)
                //                 ? new HtmlString(implode('<br>', $parts))
                //                 : 'No courier details';
                //         } elseif ($record->installation_type === 'internal_installation') {
                //             if (!empty($data['installer'])) {
                //                 $installer = \App\Models\Installer::find($data['installer']);
                //                 return $installer ? $installer->company_name : 'Unknown Installer';
                //             }
                //             return 'No installer selected';
                //         } elseif ($record->installation_type === 'external_installation') {
                //             $parts = [];

                //             // Display reseller company name
                //             if (!empty($data['reseller'])) {
                //                 $reseller = \App\Models\Reseller::find($data['reseller']);
                //                 if ($reseller) {
                //                     $parts[] = "<strong>{$reseller->company_name}</strong>";
                //                 }
                //             }

                //             // Display contact details that are stored in category2
                //             if (!empty($data['pic_name'])) {
                //                 $parts[] = "Reseller Name: {$data['pic_name']}";
                //             }

                //             if (!empty($data['pic_phone'])) {
                //                 $parts[] = "Phone: {$data['pic_phone']}";
                //             }

                //             if (!empty($data['email'])) {
                //                 $parts[] = "Email: {$data['email']}";
                //             }

                //             return !empty($parts)
                //                 ? new HtmlString(implode('<br>', $parts))
                //                 : 'No reseller details';
                //         }

                //         // Fallback for any other case
                //         return json_encode($data);
                //     })
                //     ->wrap()
                //     ->html() // Important: Add this to render the HTML content
                //     ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('STATUS')
                    ->formatStateUsing(fn(string $state): HtmlString => match ($state) {
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
                        ->visible(fn(HardwareHandover $record): bool => $record->status === 'Rejected')
                        ->icon('heroicon-o-magnifying-glass-plus')
                        ->modalHeading('Change Request Reason')
                        ->modalContent(fn($record) => view('components.view-reason', [
                            'reason' => $record->reject_reason,
                        ]))
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalWidth('3xl')
                        ->color('warning'),

                    Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading(false)
                        ->modalWidth('6xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        // ->visible(fn(HardwareHandover $record): bool => in_array($record->status, ['New', 'Completed', 'Pending Migration', 'Pending Stock']))
                        ->modalContent(function (HardwareHandover $record): View {

                            // Return the view with the record using $this->record pattern
                            return view('components.hardware-handover')
                                ->with('extraAttributes', ['record' => $record]);
                        }),

                    Action::make('submit_for_approval')
                        ->label('Submit for Approval')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->visible(fn(HardwareHandover $record): bool => $record->status === 'Draft')
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
                        ->modalHeading(function (HardwareHandover $record): string {
                            // Format ID with prefix 250 and pad with zeros to ensure at least 3 digits
                            $formattedId = '250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                            return "Edit Hardware Handover {$formattedId}";
                        })
                        ->label('Edit Hardware Handover')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->modalSubmitActionLabel('Save')
                        ->visible(fn(HardwareHandover $record): bool => in_array($record->status, ['Draft']))
                        ->modalWidth(MaxWidth::FourExtraLarge)
                        ->slideOver()
                        ->form($this->defaultForm())
                        ->action(function (HardwareHandover $record, array $data): void {
                            // Process the form data to handle any top-level fields that should be in category2
                            $data = $this->processFormData($data);

                            $data['created_by'] = auth()->id();
                            $data['lead_id'] = $this->getOwnerRecord()->id;
                            $data['status'] = 'Draft';

                            // Handle contact_detail encoding if it's not already handled by processFormData
                            if(isset($data['contact_detail']) && is_array($data['contact_detail'])){
                                $data['contact_detail'] = json_encode($data['contact_detail']);
                            }

                            // Handle file array encodings - keep only these that are needed
                            if (isset($data['confirmation_order_file']) && is_array($data['confirmation_order_file'])) {
                                $data['confirmation_order_file'] = json_encode($data['confirmation_order_file']);
                            }

                            if (isset($data['payment_slip_file']) && is_array($data['payment_slip_file'])) {
                                $data['payment_slip_file'] = json_encode($data['payment_slip_file']);
                            }

                            if (isset($data['video_files']) && is_array($data['video_files'])) {
                                $data['video_files'] = json_encode($data['video_files']);
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

                    // Convert to Draft button - only visible for Rejected status
                    Action::make('convert_to_draft')
                        ->label('Convert to Draft')
                        ->icon('heroicon-o-document')
                        ->color('warning')
                        ->visible(fn(HardwareHandover $record): bool => $record->status === 'Rejected')
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
            'industry',
            'contact_no',
            'email',
            'name',
            'position',
            'reg_no_new',
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
        $existingIds = HardwareHandover::pluck('id')->toArray();

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

    protected function processFormData(array $data): array
    {
        // Handle contact details and personal information
        // Make sure contact_detail fields aren't saved as top-level columns
        $contactFields = ['pic_name', 'pic_phone', 'pic_email'];
        $category2 = [];

        // If category2 already exists and is JSON, decode it
        if (isset($data['category2']) && is_string($data['category2'])) {
            $category2 = json_decode($data['category2'], true) ?: [];
        } elseif (isset($data['category2']) && is_array($data['category2'])) {
            $category2 = $data['category2'];
        }

        // Move any standalone contact fields into category2
        foreach ($contactFields as $field) {
            if (isset($data[$field])) {
                $category2[$field] = $data[$field];
                unset($data[$field]); // Remove from top level
            }
        }

        // Encode category2 back to JSON
        $data['category2'] = json_encode($category2);

        return $data;
    }

    protected function getCurrentRecordId()
    {
        // If mountedTableActionRecord is an object, get its ID property
        if (isset($this->mountedTableActionRecord) && is_object($this->mountedTableActionRecord)) {
            return $this->mountedTableActionRecord->id;
        }

        // If mountedTableActionRecord is directly the ID as a string/int
        if (isset($this->mountedTableActionRecord)) {
            return $this->mountedTableActionRecord;
        }

        // If we have a record property that's an object
        if (isset($this->record) && is_object($this->record)) {
            return $this->record->id;
        }

        return null;
    }
}
