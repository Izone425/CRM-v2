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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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
            Forms\Components\ToggleButtons::make('hr_version')
                ->label('Select HR Version')
                ->options([
                    '1' => 'HR Version 1',
                    '2' => 'HR Version 2',
                ])
                ->default('1')
                ->inline()
                ->required()
                ->live()
                ->visible(fn () => auth()->user()->role_id === 3),

            Section::make('Step 1: Database Details')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextInput::make('company_name')
                                ->label('Company Name')
                                ->hidden()
                                ->dehydrated(true)
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
                                ->dehydrated(true)
                                ->label('Salesperson')
                                ->default(fn (?SoftwareHandover $record = null) =>
                                    $record?->salesperson ?? ($this->getOwnerRecord()->salesperson ? User::find($this->getOwnerRecord()->salesperson)->name : null))
                                ->hidden(),

                            TextInput::make('headcount')
                                ->numeric()
                                ->live(debounce: 550)
                                ->afterStateUpdated(function (Forms\Set $set, ?string $state, CategoryService $category) {
                                    /**
                                    * set this company's category based on head count
                                    */
                                    $set('category', $category->retrieve($state));
                                })
                                ->required()
                                ->disabled()
                                ->dehydrated(true)
                                ->default(fn (?SoftwareHandover $record = null) => $record?->headcount ?? null)
                                ->hidden(),

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
                                ->readOnly()
                                ->hidden(),
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
                            Grid::make(1)
                            ->schema([
                                Textarea::make('remark')
                                    ->extraAlpineAttributes([
                                        'x-on:input' => '
                                            const start = $el.selectionStart;
                                            const end = $el.selectionEnd;
                                            const value = $el.value;
                                            $el.value = value.toUpperCase();
                                            $el.setSelectionRange(start, end);
                                        '
                                    ])
                                    ->dehydrateStateUsing(fn ($state) => strtoupper($state))
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
                                    ->placeholder('Write Remark')
                                    ->autosize()
                                    ->rows(2),
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
                                ->label('Software + Hardware')
                                ->required(fn (callable $get) => $get('training_type') === 'online_webinar_training')
                                ->options(function (?SoftwareHandover $record = null, RelationManager $livewire) {
                                    // ✅ Get lead ID properly
                                    $leadId = null;
                                    $currentRecordId = null;

                                    if ($record) {
                                        // Edit mode - we have a record
                                        $leadId = $record->lead_id;
                                        $currentRecordId = $record->id;
                                    } else {
                                        // ✅ Create mode - get lead ID from RelationManager
                                        $leadId = $livewire->getOwnerRecord()->id;
                                    }

                                    // ✅ Handle case where we still can't get leadId
                                    if (!$leadId) {
                                        return [];
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

                                    // ✅ Apply the module checking filter
                                    $availableQuotations = \App\Models\Quotation::where('lead_id', $leadId)
                                        ->where('quotation_type', 'product')
                                        ->where('status', \App\Enums\QuotationStatusEnum::accepted)
                                        ->whereNotIn('id', array_filter($usedPiIds))
                                        ->where('quotation_date', '>=', now()->toDateString());

                                    // ✅ Filter quotations that contain the required module products
                                    $moduleProductIds = [31, 118, 114, 108, 60, 38, 119, 115, 109, 60, 39, 120, 116, 110, 60, 40, 121, 117, 111, 60, 59, 41, 112, 93, 113, 42];

                                    $availableQuotations = $availableQuotations->whereHas('items', function ($query) use ($moduleProductIds) {
                                        $query->whereIn('product_id', $moduleProductIds);
                                    })
                                    // ✅ CONDITION 1: Only show quotations that contain product ID 94
                                    ->whereHas('items', function ($query) {
                                        $query->where('product_id', 94);
                                    })
                                    // ✅ CONDITION 2: Exclude quotations that contain both product ID 94 AND 99
                                    ->whereDoesntHave('items', function ($query) {
                                        $query->where('product_id', 99)
                                              ->whereExists(function ($subQuery) {
                                                  $subQuery->select('id')
                                                           ->from('quotation_details')
                                                           ->whereColumn('quotation_details.quotation_id', 'quotations.id')
                                                           ->where('quotation_details.product_id', 94);
                                              });
                                    });

                                    return $availableQuotations->pluck('pi_reference_no', 'id')->toArray();
                                })
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, ?array $state, CategoryService $category) {
                                    if (empty($state)) {
                                        return;
                                    }
                                    $highestQuantity = \App\Models\QuotationDetail::whereIn('quotation_id', $state)
                                        ->max('quantity');
                                    if ($highestQuantity) {
                                        $set('headcount', $highestQuantity);
                                        $set('category', $category->retrieve($highestQuantity));
                                    }
                                })
                                ->visible(fn (callable $get) => $get('training_type') === 'online_webinar_training')
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
                                        // ✅ CONDITION 1: Only show quotations that contain product ID 94
                                        ->whereHas('items', function ($query) {
                                            $query->where('product_id', 94);
                                        })
                                        // ✅ CONDITION 2: Exclude quotations that contain both product ID 94 AND 99
                                        ->whereDoesntHave('items', function ($query) {
                                            $query->where('product_id', 99)
                                                  ->whereExists(function ($subQuery) {
                                                      $subQuery->select('id')
                                                               ->from('quotation_details')
                                                               ->whereColumn('quotation_details.quotation_id', 'quotations.id')
                                                               ->where('quotation_details.product_id', 94);
                                                  });
                                        })
                                        ->pluck('pi_reference_no', 'id')
                                        ->toArray();
                                })
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->live() // Add live to trigger updates
                                ->afterStateUpdated(function (Forms\Set $set, ?array $state, CategoryService $category) {
                                    if (empty($state)) {
                                        return;
                                    }

                                    // Get the highest quantity from selected quotations
                                    $highestQuantity = \App\Models\QuotationDetail::whereIn('quotation_id', $state)
                                        ->max('quantity');

                                    if ($highestQuantity) {
                                        $set('headcount', $highestQuantity);
                                        // Also update the category based on the new headcount
                                        $set('category', $category->retrieve($highestQuantity));
                                    }
                                })
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
                                        // ✅ CONDITION 1: Only show quotations that contain product ID 94
                                        ->whereHas('items', function ($query) {
                                            $query->where('product_id', 94);
                                        })
                                        // ✅ CONDITION 2: Exclude quotations that contain both product ID 94 AND 99
                                        ->whereDoesntHave('items', function ($query) {
                                            $query->where('product_id', 99)
                                                  ->whereExists(function ($subQuery) {
                                                      $subQuery->select('id')
                                                               ->from('quotation_details')
                                                               ->whereColumn('quotation_details.quotation_id', 'quotations.id')
                                                               ->where('quotation_details.product_id', 94);
                                                  });
                                        })
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
                                // Use standardized format matching SoftwareHandover accessor
                                $formattedId = SoftwareHandover::generateFormattedId($leadId);
                                // Get extension
                                $extension = $file->getClientOriginalExtension();

                                // Generate a unique identifier (timestamp) to avoid overwriting files
                                $timestamp = now()->format('YmdHis');
                                $random = rand(1000, 9999);

                                return "{$formattedId}-CONFIRM-{$timestamp}-{$random}.{$extension}";
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
                                // Use standardized format matching SoftwareHandover accessor
                                $formattedId = SoftwareHandover::generateFormattedId($leadId);
                                // Get extension
                                $extension = $file->getClientOriginalExtension();

                                // Generate a unique identifier (timestamp) to avoid overwriting files
                                $timestamp = now()->format('YmdHis');
                                $random = rand(1000, 9999);

                                return "{$formattedId}-PAYMENT-{$timestamp}-{$random}.{$extension}";
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
                                // Use standardized format matching SoftwareHandover accessor
                                $formattedId = SoftwareHandover::generateFormattedId($leadId);
                                // Get extension
                                $extension = $file->getClientOriginalExtension();

                                // Generate a unique identifier (timestamp) to avoid overwriting files
                                $timestamp = now()->format('YmdHis');
                                $random = rand(1000, 9999);

                                return "{$formattedId}-HRDF-{$timestamp}-{$random}.{$extension}";
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

            Section::make('Step 9: Renewal Note')
                ->columnSpan(1)
                ->schema([
                    Grid::make(1)
                        ->schema([
                            Textarea::make('renewal_note')
                                ->label('Renewal Note')
                                ->placeholder('Write Renewal Notes')
                                ->rows(2)
                                ->maxLength(1000)
                                ->extraAlpineAttributes([
                                    'x-on:input' => '
                                        const start = $el.selectionStart;
                                        const end = $el.selectionEnd;
                                        const value = $el.value;
                                        $el.value = value.toUpperCase();
                                        $el.setSelectionRange(start, end);
                                    '
                                ])
                                ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                                ->default(function (?SoftwareHandover $record = null) {
                                    if (!$record) {
                                        return null;
                                    }

                                    // Get the latest renewal note for this lead
                                    $latestNote = \App\Models\RenewalNote::where('lead_id', $record->lead_id)
                                        ->latest()
                                        ->first();

                                    return $latestNote?->content ?? null;
                                }),
                        ])
                ]),

            Section::make('Step 10: Invoice to Reseller')
                ->columnSpan(1)
                ->schema([
                    Grid::make(1)
                        ->schema([
                            Select::make('reseller_id')
                                ->label('Reseller Company')
                                ->placeholder('Select Reseller Company (Optional)')
                                ->options(function () {
                                    return \App\Models\Reseller::orderBy('company_name')
                                        ->pluck('company_name', 'id')
                                        ->toArray();
                                })
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->default(function (?SoftwareHandover $record = null) {
                                    return $record?->reseller_id ?? null;
                                }),
                        ])
                ]),
        ];
    }

    public function headerActions(): array
    {
        $leadStatus = $this->getOwnerRecord()->lead_status ?? '';
        $isCompanyDetailsIncomplete = $this->isCompanyDetailsIncomplete();
        $hasIncompleteSoftwareHandover = $this->hasIncompleteSoftwareHandover();

        return [
            // Action 1: Warning notification when e-invoice is incomplete
            Tables\Actions\Action::make('EInvoiceWarning')
                ->label('Add Software Handover')
                ->icon('heroicon-o-plus')
                ->color('gray')
                // ->visible(false)
                ->visible(function () use ($leadStatus, $isCompanyDetailsIncomplete, $hasIncompleteSoftwareHandover) {
                    return $leadStatus !== 'Closed' || $isCompanyDetailsIncomplete || $hasIncompleteSoftwareHandover;
                })
                ->action(function () use ($leadStatus, $isCompanyDetailsIncomplete, $hasIncompleteSoftwareHandover) {
                    $message = '';

                    if ($leadStatus !== 'Closed') {
                        $message .= 'Please close the lead first. ';
                    }

                    if ($isCompanyDetailsIncomplete) {
                        $message .= 'Please complete the company details. ';
                    }

                    if ($hasIncompleteSoftwareHandover) {
                        $message .= 'Please complete all existing software handovers (status must not be "Draft", "New", or "Rejected") before creating a new one.';
                    }

                    Notification::make()
                        ->warning()
                        ->title('Action Required')
                        ->body(trim($message))
                        ->persistent()
                        ->send();
                }),

            // Action 2: Actual form when e-invoice is complete
            Tables\Actions\Action::make('AddSoftwareHandover')
                ->label('Add Software Handover')
                ->icon('heroicon-o-plus')
                ->color('primary')
                // ->visible(fn () => auth()->id() === 1)
                ->visible(function () use ($leadStatus, $isCompanyDetailsIncomplete, $hasIncompleteSoftwareHandover) {
                    return $leadStatus === 'Closed' && !$isCompanyDetailsIncomplete && !$hasIncompleteSoftwareHandover;
                })
                ->slideOver()
                ->modalHeading('Software Handover')
                ->modalWidth(MaxWidth::FourExtraLarge)
                ->modalSubmitActionLabel('Submit')
                ->form($this->defaultForm())
                ->action(function (array $data): void {
                    $renewalNote = $data['renewal_note'] ?? null;
                    unset($data['renewal_note']);

                    $data['created_by'] = auth()->id();
                    $data['lead_id'] = $this->getOwnerRecord()->id;
                    $data['status'] = 'New';
                    $data['submitted_at'] = now();

                    $existingHandovers = SoftwareHandover::where('lead_id', $this->getOwnerRecord()->id)
                        ->exists();

                    $data['license_type'] = $existingHandovers ? 'addon module' : 'new sales';

                    if (empty($data['company_name'])) {
                        $data['company_name'] = $this->getOwnerRecord()->companyDetail->company_name ?? null;
                    }

                    if (empty($data['salesperson'])) {
                        $salespersonId = $this->getOwnerRecord()->salesperson;
                        $data['salesperson'] = $salespersonId ? User::find($salespersonId)?->name : null;
                    }

                    if (empty($data['headcount'])) {
                        // Try to get headcount from proforma_invoice selections
                        $headcount = null;

                        // Check from proforma_invoice_product
                        if (!empty($data['proforma_invoice_product'])) {
                            $quotationIds = is_array($data['proforma_invoice_product'])
                                ? $data['proforma_invoice_product']
                                : json_decode($data['proforma_invoice_product'], true);

                            if (!empty($quotationIds)) {
                                $headcount = \App\Models\QuotationDetail::whereIn('quotation_id', $quotationIds)
                                    ->max('quantity');
                            }
                        }

                        // Check from software_hardware_pi if still null
                        if (!$headcount && !empty($data['software_hardware_pi'])) {
                            $quotationIds = is_array($data['software_hardware_pi'])
                                ? $data['software_hardware_pi']
                                : json_decode($data['software_hardware_pi'], true);

                            if (!empty($quotationIds)) {
                                $headcount = \App\Models\QuotationDetail::whereIn('quotation_id', $quotationIds)
                                    ->max('quantity');
                            }
                        }

                        $data['headcount'] = $headcount;
                    }

                    // Process JSON encoding for array fields
                    if (isset($data['remarks']) && is_array($data['remarks'])) {
                        foreach ($data['remarks'] as $key => $remark) {
                            if (isset($remark['attachments']) && is_array($remark['attachments'])) {
                                $data['remarks'][$key]['attachments'] = json_encode($remark['attachments']);
                            }
                        }
                        $data['remarks'] = json_encode($data['remarks']);
                    }

                    // Handle file array encodings
                    foreach (['confirmation_order_file', 'payment_slip_file', 'proforma_invoice_hrdf',
                            'proforma_invoice_product', 'invoice_file', 'implementation_pics',
                            'hrdf_grant_file', 'software_hardware_pi', 'non_hrdf_pi'] as $field) {
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

                    // ✅ Save renewal note to renewal_notes table if provided
                    if (!empty($renewalNote)) {
                        try {
                            $savedNote = \App\Models\RenewalNote::create([
                                'lead_id' => $this->getOwnerRecord()->id,
                                'user_id' => auth()->id(),
                                'content' => strtoupper($renewalNote),
                            ]);
                        } catch (\Exception $e) {
                            Log::error('❌ Failed to save renewal note', [
                                'error_message' => $e->getMessage(),
                                'error_trace' => $e->getTraceAsString(),
                                'lead_id' => $this->getOwnerRecord()->id,
                                'renewal_note' => $renewalNote,
                            ]);
                        }
                    } else {
                        Log::info('Renewal note is empty, skipping save');
                    }

                    app(GenerateSoftwareHandoverPdfController::class)->generateInBackground($handover);

                    try {
                        // Format handover ID using model accessor
                        $handoverId = $handover->formatted_handover_id;

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
                TextColumn::make('submitted_at')
                    ->label('Date Submit')
                    ->date('d M Y'),
                TextColumn::make('training_type')
                    ->label('Training Type')
                    ->formatStateUsing(fn (string $state): string => Str::title(str_replace('_', ' ', $state))),
                TextColumn::make('hr_version')
                    ->label('HR Version')
                    ->formatStateUsing(function ($state) {
                        return $state ? 'Version ' . $state : 'N/A';
                    }),

                TextColumn::make('license_type')
                    ->label('License Type')
                    ->formatStateUsing(fn (string $state): string => Str::title($state)),
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
                            $formattedId = $record->formatted_handover_id;
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
                            $renewalNote = $data['renewal_note'] ?? null;
                            unset($data['renewal_note']);
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

                            if (!empty($renewalNote)) {
                                try {
                                    $savedNote = \App\Models\RenewalNote::create([
                                        'lead_id' => $record->lead_id,
                                        'user_id' => auth()->id(),
                                        'content' => strtoupper($renewalNote),
                                    ]);

                                    info('Renewal note updated', [
                                        'note_id' => $savedNote->id,
                                        'lead_id' => $savedNote->lead_id,
                                        'content' => $savedNote->content,
                                    ]);
                                } catch (\Exception $e) {
                                    Log::error('Failed to update renewal note', [
                                        'error' => $e->getMessage(),
                                        'lead_id' => $record->lead_id,
                                    ]);
                                }
                            }

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

    protected function hasIncompleteSoftwareHandover(): bool
    {
        $lead = $this->getOwnerRecord();

        // Check if there are any existing software handovers that are not completed
        $incompleteHandovers = $lead->softwareHandover()
            ->whereIn('status', ['Draft', 'New', 'Rejected'])
            ->exists();

        return $incompleteHandovers;
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
        $regNoValue = (string) $regNoValue;

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
