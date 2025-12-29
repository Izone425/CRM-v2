<?php

namespace App\Filament\Resources;

use App\Classes\Encryptor;
use App\Enums\QuotationStatusEnum;
use App\Filament\Resources\QuotationResource\Pages;
use App\Filament\Resources\QuotationResource\RelationManagers;
use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use App\Models\Setting;
use App\Services\AutoCountInvoiceService;
use App\Services\QuotationService;
use Carbon\Carbon;
use Coolsam\FilamentFlatpickr\Forms\Components\Flatpickr;
use Filament\Facades\Filament;
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
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class QuotationResource extends Resource
{
    protected static ?string $model = Quotation::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public $lead;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.resources.quotations.index');
    }

    public function mount($lead_id): void
    {
        try {
            // Decrypt the lead_id
            $decryptedLeadId = Encryptor::decrypt($lead_id);

            // Fetch the lead record using the decrypted ID
            $this->lead = Lead::findOrFail($decryptedLeadId);

        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            // Handle decryption failure gracefully
            abort(403, 'Invalid or tampered lead identifier.');
        }
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(5)
                ->schema([
                    Section::make('Customer Information')
                    ->schema([
                        Select::make('lead_id')
                            ->label('Company Name')
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                return Lead::with('companyDetail')
                                    ->when(auth()->user()->role_id == 2, function ($q) {
                                        $q->where('salesperson', auth()->id());
                                    })
                                    ->whereHas('companyDetail', function ($q) use ($search) {
                                        $q->where('company_name', 'like', "%{$search}%");
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->pluck('companyDetail.company_name', 'id')
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(function ($value) {
                                $lead = Lead::with('companyDetail')->find($value);
                                return $lead?->companyDetail?->company_name ?? 'Unknown Company';
                            })
                            ->default(
                                fn() => request()->has('lead_id')
                                    ? Encryptor::decrypt(request()->query('lead_id'))
                                    : null
                            )
                            ->preload()
                            ->required()
                            ->live(debounce: 550)
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $lead = Lead::find($state);
                                    if ($lead && $lead->eInvoiceDetail) {
                                        // Auto-set currency from e-invoice details
                                        $currency = $lead->eInvoiceDetail->currency ?? 'MYR';
                                        info("Setting currency to: " . $currency); // ✅ Debug log
                                        $set('currency', $currency);

                                        // Set SST rate based on currency
                                        if ($currency === 'USD') {
                                            $set('sst_rate', 0);
                                        } else {
                                            $set('sst_rate', Setting::where('name', 'sst_rate')->first()->value);
                                        }

                                        // ✅ Force recalculation after currency change
                                        self::recalculateAllRowsFromParent(
                                            fn($key) => $key === 'currency' ? $currency : null,
                                            $set
                                        );
                                    }
                                }
                            }),
                        Select::make('subsidiary_id')
                            ->label('Use Subsidiary Details')
                            ->options(function (Forms\Get $get) {
                                $leadId = $get('lead_id');
                                if (!$leadId) {
                                    return ['' => 'Use Default Company Details'];
                                }

                                $options = ['' => 'Use Default Company Details'];

                                // Use efficient query to get subsidiaries
                                $subsidiaries = \App\Models\Subsidiary::where('lead_id', $leadId)
                                    ->select('id', 'company_name')
                                    ->get();

                                foreach ($subsidiaries as $subsidiary) {
                                    $options[$subsidiary->id] = $subsidiary->company_name;
                                }

                                return $options;
                            })
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Clear any previous selections
                                $set('subsidiary_id', $state);
                            })
                            ->placeholder('Use Default Company Details')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->visible(fn (Forms\Get $get) => !empty($get('lead_id'))),
                        Flatpickr::make('quotation_date')
                            ->label('Date')
                            ->dateFormat('j M Y')
                            ->default(now()->format('j M Y'))
                            ->required()
                            ->clickOpens()
                            ->disabled()
                            ->dehydrated(true),
                        Select::make('sales_type')
                            ->label('Sales Type')
                            ->placeholder('Select a sales type')
                            ->options([
                                'NEW SALES' => 'NEW SALES',
                                'RENEWAL SALES' => 'RENEWAL SALES',
                            ])
                            ->default(function () {
                                // If user ID is 4 or 5, default to RENEWAL SALES, otherwise NEW SALES
                                return in_array(auth()->user()?->id, [4, 5]) ? 'RENEWAL SALES' : 'NEW SALES';
                            })
                            ->required()
                            ->disabled(fn () => auth()->user()?->role_id == 2)
                            ->live() // Add this to make it reactive
                            ->afterStateUpdated(function (?string $state, Forms\Get $get, Forms\Set $set) {
                                // If quotation type is hrdf, update the items based on sales type
                                if ($get('quotation_type') === 'hrdf') {
                                    if ($state === 'RENEWAL SALES') {
                                        // Use product IDs 105, 106, 107 for renewal sales
                                        $items = collect([
                                            ['product_id' => 105],
                                            ['product_id' => 106],
                                            ['product_id' => 107],
                                        ])->mapWithKeys(fn ($item) => [
                                            (string) \Illuminate\Support\Str::uuid() => $item
                                        ])->toArray();
                                    } else {
                                        // Use default product IDs 16, 17, 18 for new sales
                                        $items = collect([
                                            ['product_id' => 16],
                                            ['product_id' => 17],
                                            ['product_id' => 18],
                                        ])->mapWithKeys(fn ($item) => [
                                            (string) \Illuminate\Support\Str::uuid() => $item
                                        ])->toArray();
                                    }

                                    $set('items', $items);
                                    QuotationResource::recalculateAllRowsFromParent($get, $set);
                                }
                            }),
                        Hidden::make('currency')
                            ->default(function (Forms\Get $get) {
                                $leadId = $get('lead_id');
                                if ($leadId) {
                                    $lead = Lead::select('id')->with('eInvoiceDetail:id,lead_id,currency')->find($leadId);
                                    return $lead?->eInvoiceDetail?->currency ?? 'MYR';
                                }
                                return 'MYR';
                            })
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (string $state, Forms\Get $get, Forms\Set $set) {
                                // Cache SST rate to avoid repeated queries
                                static $cachedSstRate = null;

                                if ($state === 'USD') {
                                    $set('sst_rate', 0);
                                } else {
                                    if ($cachedSstRate === null) {
                                        $cachedSstRate = \Illuminate\Support\Facades\Cache::remember(
                                            'sst_rate_setting',
                                            3600,
                                            fn() => Setting::where('name', 'sst_rate')->value('value') ?? 8
                                        );
                                    }
                                    $set('sst_rate', $cachedSstRate);
                                }

                                self::recalculateAllRowsFromParent($get, $set);
                            })
                            ->reactive() // Keep reactive for when lead_id changes
                            ->afterStateHydrated(function (Forms\Set $set, Forms\Get $get, $state) {
                                // Auto-set currency when lead is selected
                                $leadId = $get('lead_id');
                                if ($leadId && !$state) {
                                    $lead = Lead::find($leadId);
                                    $currency = $lead?->eInvoiceDetail?->currency ?? 'MYR';
                                    $set('currency', $currency);

                                    // Set SST rate based on currency
                                    if ($currency === 'USD') {
                                        $set('sst_rate', 0);
                                    } else {
                                        $set('sst_rate', Setting::where('name', 'sst_rate')->first()->value);
                                    }
                                }
                            }),

                        Select::make('quotation_type')
                            ->label('Invoice Type')
                            ->required()
                            ->placeholder('Select a type')
                            ->options([
                                'product' => 'Product',
                                'hrdf' => 'HRDF',
                            ])
                            ->live(debounce:500)
                            ->disabledOn('edit')
                            ->afterStateUpdated(function (?string $state, Forms\Get $get, Forms\Set $set) {
                                if ($state === 'hrdf') {
                                    $unitPrice = $get('unit_price') ?? 0;
                                    $salesType = $get('sales_type'); // Get the sales type

                                    // Check if sales type is RENEWAL SALES
                                    if ($salesType === 'RENEWAL SALES') {
                                        // Use product IDs 105, 106, 107 for renewal sales
                                        $items = collect([
                                            ['product_id' => 105],
                                            ['product_id' => 106],
                                            ['product_id' => 107],
                                        ])->mapWithKeys(fn ($item) => [
                                            (string) \Illuminate\Support\Str::uuid() => $item
                                        ])->toArray();
                                    } else {
                                        // Use default product IDs 16, 17, 18 for new sales
                                        $items = collect([
                                            ['product_id' => 16],
                                            ['product_id' => 17],
                                            ['product_id' => 18],
                                        ])->mapWithKeys(fn ($item) => [
                                            (string) \Illuminate\Support\Str::uuid() => $item
                                        ])->toArray();
                                    }

                                    $set('items', $items);

                                    // Trigger recalculation if needed
                                    QuotationResource::recalculateAllRowsFromParent($get, $set);
                                }
                            }),
                        Select::make('package_group')
                            ->label('Package')
                            ->placeholder('Select a package')
                            ->options(function () {
                                return \App\Models\Product::whereNotNull('package_group')
                                    ->get()
                                    ->pluck('package_group')
                                    ->filter()
                                    ->flatten()
                                    ->unique()
                                    ->filter(fn($value) => !empty($value))
                                    ->mapWithKeys(function ($package) {
                                        $packageLabels = [
                                            'Package 1' => 'Package 1 - Standard Package',
                                            'Package 2' => 'Package 2 - 1 Year Subscription',
                                            'Package 3' => 'Package 3 - 2 Year Subscription',
                                            'Package 4' => 'Package 4 - 3 Year Subscription',
                                            'Package 5' => 'Package 5 - 4 Year Subscription',
                                            'Package 6' => 'Package 6 - 5 Year Subscription',
                                            'Other' => 'Other',
                                        ];

                                        return [$package => $packageLabels[$package] ?? $package];
                                    })
                                    ->sort()
                                    ->toArray();
                            })
                            ->searchable()
                            ->live()
                            ->visible(fn(Forms\Get $get) => $get('quotation_type') === 'product')
                            ->afterStateUpdated(function (?string $state, Forms\Get $get, Forms\Set $set) {
                                if ($state) {
                                    // ✅ Get year count but always use 12 months as default
                                    $yearCount = match($state) {
                                        'Package 2' => 1,
                                        'Package 3' => 2,
                                        'Package 4' => 3,
                                        'Package 5' => 4,
                                        'Package 6' => 5,
                                        default => 1,
                                    };

                                    // Get products for this package
                                    $products = \App\Models\Product::where(function ($query) use ($state) {
                                        $query->where(function ($q) use ($state) {
                                            $q->whereNotNull('package_group')
                                            ->whereRaw("JSON_CONTAINS(package_group, ?)", [json_encode($state)]);
                                        })
                                        ->orWhere('package_group', $state);
                                    })
                                    ->orderBy('package_sort_order')
                                    ->get();

                                    $mappedItems = collect();

                                    foreach ($products as $product) {
                                        $isSoftware = $product->solution === 'software';

                                        if ($isSoftware && $yearCount > 1) {
                                            // ✅ Create multiple entries, all with 12 months by default
                                            for ($year = 1; $year <= $yearCount; $year++) {
                                                $mappedItems->push([
                                                    'product_id' => $product->id,
                                                    'quantity' => in_array($product->solution, ['software', 'hardware'])
                                                        ? ($product->quantity ?? 1)
                                                        : ($get('num_of_participant') ?? 1),
                                                    'unit_price' => $product->unit_price,
                                                    'subscription_period' => 12, // ✅ Always default to 12 months
                                                    'description' => $product->description,
                                                    'year' => "Year {$year}",
                                                ]);
                                            }
                                        } else {
                                            $mappedItems->push([
                                                'product_id' => $product->id,
                                                'quantity' => in_array($product->solution, ['software', 'hardware'])
                                                    ? ($product->quantity ?? 1)
                                                    : ($get('num_of_participant') ?? 1),
                                                'unit_price' => $product->unit_price,
                                                'subscription_period' => $isSoftware ? 12 : null, // ✅ 12 for software, null for others
                                                'description' => $product->description,
                                            ]);
                                        }
                                    }

                                    $finalItems = $mappedItems->mapWithKeys(fn($item) => [
                                        (string) \Illuminate\Support\Str::uuid() => $item
                                    ])->toArray();

                                    $set('items', $finalItems);
                                    QuotationResource::recalculateAllRowsFromParent($get, $set);
                                }
                            }),
                        Select::make('taxation_category')
                            ->label('Taxation Category')
                            ->options([
                                'default' => 'Default Setting',
                                'all_taxable' => 'All Items are Taxable',
                                'all_non_taxable' => 'All Items are Non-Taxable',
                            ])
                            ->default('default')
                            ->visible(fn (Forms\Get $get) => $get('currency') === 'MYR')
                            ->live()
                            ->afterStateUpdated(function (string $state, Forms\Get $get, Forms\Set $set) {
                                $items = $get('items') ?? [];

                                // Apply taxation setting to all items
                                foreach ($items as $index => $item) {
                                    if (empty($item['product_id'])) {
                                        continue;
                                    }

                                    $product = Product::find($item['product_id']);
                                    if (!$product) {
                                        continue;
                                    }

                                    // Set the product taxability based on the selected category
                                    $isTaxable = match ($state) {
                                        'default' => $product->taxable, // Use product's default setting
                                        'all_taxable' => true,          // Make all items taxable
                                        'all_non_taxable' => false,     // Make all items non-taxable
                                    };

                                    // Store temporary taxability state for this item
                                    $set("items.{$index}.override_taxable", $isTaxable);
                                }

                                // Recalculate all totals
                                self::recalculateAllRowsFromParent($get, $set);
                            }),

                        // For 'hrdf' type
                        TextInput::make('num_of_participant')
                            ->label('Number Of Participant(s)')
                            ->numeric()
                            ->live(debounce: 1000)
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                if (!$state) return;

                                $set('num_of_participant', $state);

                                $items = $get('items') ?? [];
                                foreach ($items as $index => $item) {
                                    $set("items.{$index}.quantity", $state);
                                }

                                QuotationResource::recalculateAllRowsFromParent($get, $set);
                            })
                            ->hidden(fn(Forms\Get $get) => $get('quotation_type') !== 'hrdf'),

                        TextInput::make('unit_price')
                            ->label('Unit Price')
                            ->numeric()
                            ->live(debounce: 1000)
                            ->afterStateUpdated(function(?string $state, Forms\Get $get, Forms\Set $set) {
                                if (!$state) return;

                                $items = $get('items');
                                foreach ($items as $index => $item) {
                                    $set("items.{$index}.unit_price", $state);
                                }

                                // Recalculate everything
                                QuotationResource::recalculateAllRowsFromParent($get, $set);
                            })
                            ->hidden(fn(Forms\Get $get) => $get('quotation_type') !== 'hrdf'),
                    ])
                    ->columnSpan(3)
                    ->columns(2),
                Tabs::make('financial_tabs')
                    ->tabs([
                        Tabs\Tab::make('Summary')
                            ->schema([
                                TextInput::make('sub_total')
                                    ->label('Sub Total')
                                    ->readOnly()
                                    ->prefix(fn(Forms\Get $get) => $get('currency')),
                                TextInput::make('sst_rate')
                                    ->label('SST Rate')
                                    ->suffix('%')
                                    ->default(function() {
                                        $defaultValue = Setting::where('name','sst_rate')->first()->value;
                                        return $defaultValue;
                                    })
                                    ->afterStateHydrated(fn(Forms\Set $set) => $set('sst_rate', Setting::where('name','sst_rate')->first()->value)),
                                TextInput::make('tax_amount')
                                    ->label('Tax Amount')
                                    ->readOnly()
                                    ->prefix(fn(Forms\Get $get) => $get('currency')),
                                TextInput::make('total')
                                    ->label('Total')
                                    ->readOnly()
                                    ->prefix(fn(Forms\Get $get) => $get('currency')),
                            ]),
                        Tabs\Tab::make('Subsidiary Details')
                            ->schema([
                                // Add a notice when no subsidiary is selected
                                Placeholder::make('no_subsidiary_selected')
                                    ->label('No Subsidiary Selected')
                                    ->content('Please select a subsidiary from the dropdown above to view details.')
                                    ->visible(function (Forms\Get $get, ?Quotation $record) {
                                        $subsidiaryId = $get('subsidiary_id') ?? $record?->subsidiary_id ?? null;
                                        return empty($subsidiaryId);
                                    }),

                                // Only show subsidiary details when a subsidiary is selected
                                Grid::make()
                                    ->schema([
                                        Placeholder::make('subsidiary_address1')
                                            ->label('Address 1')
                                            ->content(function (Forms\Get $get, ?Quotation $record) {
                                                $subsidiaryId = $get('subsidiary_id') ?? $record?->subsidiary_id ?? null;
                                                if (!$subsidiaryId) return 'N/A';
                                                $subsidiary = \App\Models\Subsidiary::find($subsidiaryId);
                                                return $subsidiary?->company_address1 ?? 'N/A';
                                            }),
                                        Placeholder::make('subsidiary_address2')
                                            ->label('Address 2')
                                            ->content(function (Forms\Get $get, ?Quotation $record) {
                                                $subsidiaryId = $get('subsidiary_id') ?? $record?->subsidiary_id ?? null;
                                                if (!$subsidiaryId) return 'N/A';
                                                $subsidiary = \App\Models\Subsidiary::find($subsidiaryId);
                                                return $subsidiary?->company_address2 ?? 'N/A';
                                            }),
                                        Grid::make(2)
                                            ->schema([
                                                Placeholder::make('postcode')
                                                    ->label('Postcode')
                                                    ->content(function (Forms\Get $get, ?Quotation $record) {
                                                        $subsidiaryId = $get('subsidiary_id') ?? $record?->subsidiary_id ?? null;
                                                        if (!$subsidiaryId) return 'N/A';
                                                        $subsidiary = \App\Models\Subsidiary::find($subsidiaryId);
                                                        return $subsidiary?->postcode ?? 'N/A';
                                                    }),
                                                Placeholder::make('subsidiary_state')
                                                ->label('State')
                                                ->content(function (Forms\Get $get, ?Quotation $record) {
                                                    $subsidiaryId = $get('subsidiary_id') ?? $record?->subsidiary_id ?? null;
                                                    if (!$subsidiaryId) return 'N/A';
                                                    $subsidiary = \App\Models\Subsidiary::find($subsidiaryId);
                                                    return $subsidiary?->state ?? 'N/A';
                                                }),
                                            ]),
                                        Grid::make(2)
                                            ->schema([
                                                Placeholder::make('subsidiary_contact_person')
                                                    ->label('Contact Person')
                                                    ->content(function (Forms\Get $get, ?Quotation $record) {
                                                        $subsidiaryId = $get('subsidiary_id') ?? $record?->subsidiary_id ?? null;
                                                        if (!$subsidiaryId) return 'N/A';
                                                        $subsidiary = \App\Models\Subsidiary::find($subsidiaryId);
                                                        return $subsidiary?->name ?? 'N/A';
                                                    }),
                                                Placeholder::make('subsidiary_email')
                                                    ->label('Email')
                                                    ->content(function (Forms\Get $get, ?Quotation $record) {
                                                        $subsidiaryId = $get('subsidiary_id') ?? $record?->subsidiary_id ?? null;
                                                        if (!$subsidiaryId) return 'N/A';
                                                        $subsidiary = \App\Models\Subsidiary::find($subsidiaryId);
                                                        return $subsidiary?->email ?? 'N/A';
                                                    }),
                                            ]),
                                    ])
                                    ->visible(function (Forms\Get $get, ?Quotation $record) {
                                        $subsidiaryId = $get('subsidiary_id') ?? $record?->subsidiary_id ?? null;
                                        return !empty($subsidiaryId);
                                    }),
                            ])
                            // Make the entire tab visible only when subsidiary_id is selected in the form
                            ->visible(function (Forms\Get $get, ?Quotation $record) {
                                // Attempt to get subsidiary_id from form state or record
                                $leadId = $get('lead_id') ?? $record?->lead_id ?? null;

                                // If we have a lead_id, check if it has any subsidiaries
                                if ($leadId) {
                                    $lead = \App\Models\Lead::find($leadId);
                                    if ($lead && $lead->subsidiaries()->count() > 0) {
                                        return true; // Show the tab if the lead has subsidiaries
                                    }
                                }

                                return false; // Hide the tab if no subsidiaries available
                            }),
                    ])
                    ->columnSpan(2),
                ]),
                Section::make('Details')
                    ->schema([
                        Hidden::make('base_subscription')
                            ->afterStateUpdated(fn(Forms\Get $get, Forms\Set $set) => self::recalculateAllRows($get, $set)),
                        Hidden::make('num_of_participant')
                            ->default(fn(Forms\Get $get) => $get('headcount')),
                        Hidden::make('headcount')
                            ->default(fn(Forms\Get $get) => $get('headcount')),
                        Repeater::make('items')
                            // ->hidden(fn(Forms\Get $get) => !$get('quotation_type') || !$get('headcount'))
                            ->relationship()
                            ->label('Quotation Items')
                            ->schema([
                                Select::make('product_id')
                                    ->label('Product Code')
                                    ->live(debounce: 500)
                                    ->options(function (Forms\Get $get) {
                                        $quotationType = $get('../../quotation_type');

                                        $query = \App\Models\Product::query()
                                            ->where('is_active', true)
                                            ->orderBy('solution')
                                            ->orderBy('sort_order'); // 🔄 changed from 'code' to 'sort_order'

                                        if ($quotationType === 'hrdf') {
                                            $query->where('solution', 'hrdf');
                                        } else {
                                            $query->where('solution', '!=', 'hrdf');
                                        }

                                        return $query->get()
                                            ->groupBy('solution')
                                            ->mapWithKeys(function ($group, $solution) {
                                                // Map solution names to their display labels
                                                $solutionLabels = [
                                                    'free_device' => 'Free Device',
                                                    'door_access_package' => 'Door Access Package',
                                                    'door_access_accesories' => 'Door Access Accessories',
                                                    'new_sales_addon' => 'Add On HC (New)',
                                                    'renewal_sales' => 'Renewal Sales',
                                                    'renewal_sales_addon' => 'Add On HC (Renewal)',
                                                ];

                                                // Use custom label if defined, otherwise convert to title case
                                                $displayLabel = $solutionLabels[strtolower($solution)] ?? ucfirst($solution);

                                                return [
                                                    $displayLabel => $group->pluck('code', 'id'),
                                                ];
                                            })
                                            ->toArray();
                                    })

                                    // We are disabling the option if it's already selected in another Repeater row
                                    // ->disableOptionWhen(function ($value, $state, Forms\Get $get) {
                                    //     return collect($get('../*.product_id'))
                                    //         ->reject(fn($id) => $id == $state)
                                    //         ->filter()
                                    //         ->contains($value);
                                    // })
                                    //->preload()
                                    ->disabled(fn (Forms\Get $get) => !$get('../../quotation_type'))
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (?string $state, Forms\Get $get, Forms\Set $set) {
                                        if ($state) {
                                            $product = Product::find($state);

                                            if ($product) {
                                                $set('unit_price', $product->unit_price);
                                                $set('description', $product->description);
                                                $set('convert_pi', $product->convert_pi);
                                                $set('tariff_code', $product->tariff_code);

                                                // ✅ Always default to 12 months for software products
                                                if ($product->solution === 'software') {
                                                    $set('subscription_period', 12); // Default to 12 instead of product's default
                                                } else {
                                                    $set('subscription_period', null);
                                                    $set('year', null);
                                                }
                                            }

                                            // First recalculate this row
                                            self::recalculateAllRows($get, $set, 'product_id', $state);

                                            // Then recalculate all years for all items
                                            self::recalculateAllYearsForAllItems($get, $set);
                                        }
                                    })
                                    ->columnSpan([
                                        'md' => 1
                                    ]),
                                TextInput::make('quantity')
                                    ->label('Quantity / Headcount')
                                    ->numeric()
                                    ->default(function(Forms\Get $get) {
                                        if ($get('../../quotation_type' == 'product')) {
                                            return $get('../../headcount');
                                        } else {
                                            return $get('../../num_of_participant');
                                        }
                                    })
                                    ->columnSpan([
                                        'md' => 1,
                                    ])
                                    ->live(debounce:500)
                                    ->afterStateUpdated(function(?string $state, Forms\Get $get, Forms\Set $set) {
                                        self::recalculateAllRows($get, $set, 'quantity', $state); // ✅ Pass field name
                                    }),
                                TextInput::make('subscription_period')
                                    ->label('Subscription Period')
                                    ->numeric()
                                    ->default(12)
                                    ->maxValue(12)
                                    ->minValue(1)
                                    ->suffix('months')
                                    ->live(onBlur: true) // ✅ Change from live(debounce: 500) to live(onBlur: true)
                                    ->afterStateUpdated(function(?string $state, Forms\Get $get, Forms\Set $set) {
                                        // Validate range
                                        if ($state) {
                                            $value = (int)$state;
                                            if ($value > 12) {
                                                $set('subscription_period', 12);
                                                Notification::make()
                                                    ->warning()
                                                    ->title('Maximum subscription period is 12 months')
                                                    ->send();
                                            } elseif ($value < 1) {
                                                $set('subscription_period', 1);
                                                Notification::make()
                                                    ->warning()
                                                    ->title('Minimum subscription period is 1 month')
                                                    ->send();
                                            }
                                        } else {
                                            $set('subscription_period', 12);
                                        }

                                        // ✅ Mark as manually edited BEFORE recalculation
                                        $set('subscription_manually_edited', true);

                                        // ✅ Add a small delay to ensure the manually edited flag is set
                                        usleep(100000); // 0.1 second

                                        self::recalculateAllRows($get, $set, 'subscription_period', $state); // ✅ Pass field name
                                    })
                                    ->visible(function(Forms\Get $get) {
                                        $productId = $get('product_id');
                                        if ($productId != null) {
                                            $product = Product::find($productId);
                                            if ($product && $get('../../quotation_type') == 'product' && $product->solution == 'software') {
                                                return true;
                                            }
                                        }
                                        return false;
                                    }),
                                TextInput::make('year')
                                    ->label('Year')
                                    ->columnSpan([
                                        'md' => 1,
                                    ])
                                    ->readOnly()
                                    ->dehydrated(true)
                                    ->helperText('Auto-calculated based on duplicate products')
                                    ->visible(function(Forms\Get $get) {
                                        $productId = $get('product_id');
                                        if ($productId != null) {
                                            $product = Product::find($productId);
                                            if ($product && $get('../../quotation_type') == 'product' && $product->solution == 'software') {
                                                return true;
                                            }
                                        }
                                        return false;
                                    })
                                    ->afterStateHydrated(function (Forms\Get $get, Forms\Set $set) {
                                        // Calculate year based on position in items array
                                        $currentProductId = $get('product_id');
                                        if (!$currentProductId) {
                                            return;
                                        }

                                        $product = Product::find($currentProductId);
                                        if (!$product || $product->solution !== 'software') {
                                            return;
                                        }

                                        $items = $get('../../items') ?? [];
                                        $yearCount = 0;

                                        // Find current item and count previous occurrences
                                        foreach ($items as $item) {
                                            if (!empty($item['product_id']) && $item['product_id'] === $currentProductId) {
                                                $yearCount++;
                                                // If this is the current item, break
                                                if ($item === $get('')) {
                                                    break;
                                                }
                                            }
                                        }

                                        if ($yearCount > 0) {
                                            $set('year', "Year {$yearCount}");
                                        }
                                    }),
                                Hidden::make('subscription_manually_edited')
                                    ->default(false)
                                    ->dehydrated(true),

                                TextInput::make('unit_price')
                                    ->label('Unit Price')
                                    ->numeric()
                                    ->columnSpan([
                                        'md' => 1,
                                    ])
                                    ->live(debounce: 500)
                                    ->readOnly(function (Forms\Get $get) {
                                        $productId = $get('product_id');
                                        if (!$productId) {
                                            return false;
                                        }
                                        $product = \App\Models\Product::find($productId);
                                        return $product && $product->amount_editable == false;
                                    })
                                    ->helperText(function (Forms\Get $get) {
                                        $productId = $get('product_id');
                                        if (!$productId) {
                                            return null;
                                        }
                                        $product = \App\Models\Product::find($productId);
                                        return $product && $product->amount_editable == false
                                            ? 'Unit price cannot be modified for this product'
                                            : null;
                                    })
                                    ->afterStateUpdated(function (?string $state, Forms\Get $get, Forms\Set $set) {
                                        $productId = $get('product_id');
                                        if ($productId && $state) {
                                            $product = \App\Models\Product::find($productId);
                                            if ($product && $product->minimum_price && (float)$state < (float)$product->unit_price) {
                                                $set('unit_price', $product->unit_price);
                                                Notification::make()
                                                    ->warning()
                                                    ->title('Price Adjusted')
                                                    ->body("Unit price cannot be lower than the product's base price ({$product->unit_price})")
                                                    ->send();
                                            }
                                        }
                                        self::recalculateAllRows($get, $set, 'unit_price', $state); // ✅ Pass field name
                                    }),
                                TextInput::make('total_before_tax')
                                    ->label('Total Before Tax')
                                    ->columnSpan([
                                        'md' => 1,
                                    ])
                                    ->afterStateHydrated(function(?string $state, Forms\Get $get, Forms\Set $set) {
                                        // self::updateFields('unit_price', $get, $set, $state);
                                        self::recalculateAllRows($get, $set);
                                    })
                                    ->readOnly(),
                                TextInput::make('taxation')
                                    ->columnSpan([
                                        'md' => 1,
                                    ])
                                    ->afterStateHydrated(function(?string $state, Forms\Get $get, Forms\Set $set) {
                                        // self::updateFields('unit_price', $get, $set, $state);
                                        self::recalculateAllRows($get, $set);
                                    })
                                    ->readOnly(),
                                TextInput::make('total_after_tax')
                                    ->label('Total After Tax')
                                    ->columnSpan([
                                        'md' => 1,
                                    ])
                                    ->afterStateHydrated(function(?string $state, Forms\Get $get, Forms\Set $set) {
                                        // self::updateFields('unit_price', $get, $set, $state);
                                        self::recalculateAllRows($get, $set);
                                    })
                                    ->readOnly(),
                                RichEditor::make('description')
                                    ->columnSpan([
                                        'md' => 4
                                    ])
                                    ->reactive()
                                    ->extraInputAttributes(['style'=> 'max-height: 200px; overflow: scroll'])
                                    ->disabled(function (Forms\Get $get) {
                                        $productId = $get('product_id');
                                        if (!$productId) {
                                            return false; // If no product selected, allow editing
                                        }

                                        $product = \App\Models\Product::find($productId);

                                        // Make sure we're explicitly checking for editable === false
                                        // Use strict comparison and explicit boolean check
                                        return $product && $product->editable === 0;
                                    })
                                    ->helperText(function (Forms\Get $get) {
                                        $productId = $get('product_id');
                                        if (!$productId) {
                                            return null;
                                        }

                                        $product = \App\Models\Product::find($productId);

                                        return $product && $product->editable === 0
                                            ? 'This product description cannot be edited.'
                                            : null;
                                    })
                                    ->dehydrated(true)
                                    ->afterStateUpdated(fn(?string $state, Forms\Get $get, Forms\Set $set) => self::recalculateAllRows($get, $set)),

                                Hidden::make('tax_code')
                                    ->dehydrated(true),

                                Hidden::make('tariff_code')
                                    ->dehydrated(true),

                                Hidden::make('convert_pi')
                                    ->dehydrated(true),

                                Hidden::make('subscription_manually_edited')
                                    ->default(false)
                                    ->dehydrated(true)
                                    ->afterStateHydrated(function ($state, Forms\Set $set) {
                                        // Ensure it has a proper boolean value
                                        $set('subscription_manually_edited', (bool)$state);
                                    }),
                            ])
                            ->deleteAction(fn(Actions\Action $action) => $action->requiresConfirmation())
                            ->afterStateUpdated(function(Forms\Get $get, Forms\Set $set) {
                                // Use debounced recalculation to improve performance
                                static $lastUpdate = 0;
                                $currentTime = microtime(true);

                                if ($currentTime - $lastUpdate > 0.5) { // 500ms debounce
                                    self::recalculateAllRowsFromParent($get, $set);
                                    self::recalculateAllYearsFromParent($get, $set);
                                    self::updateFields(null, $get, $set, null);
                                    $lastUpdate = $currentTime;
                                }
                            })
                            ->afterStateHydrated(function(Forms\Get $get, Forms\Set $set) {
                                // Only recalculate if items exist and have meaningful data
                                $items = $get('items');
                                if ($items && count($items) > 0 && !empty(array_filter($items, fn($item) => !empty($item['product_id'])))) {
                                    self::recalculateAllYearsFromParent($get, $set);
                                    self::updateFields(null, $get, $set, null);
                                }
                            })
                            ->defaultItems(1)
                            ->columns(8)
                            ->collapsible()
                            ->reorderable(false)
                            ->itemLabel(
                                function(?array $state): ?string {
                                    if ($state != null && isset($state['product_id'])) {
                                        $product = Product::find($state['product_id']);
                                        if ($product) {
                                            return 'Product Code: ' . $product->code;
                                        }
                                        return null;
                                    }
                                    return null;
                                }
                            )
                            ->cloneable()
                            ->addActionLabel('Add Quotation Item')
                            ->orderColumn('sort_order')
                    ])
            ])
            ->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->recordUrl(null)
            // ->modifyQueryUsing(function(Quotation $quotation) {
            //     $currentUser = auth('web')->user();

            //     // Check if the current user's role_id is 3
            //     if ($currentUser->role_id == 3) {
            //         // If role_id is 3, return all quotations ordered by ID
            //         return $quotation->orderBy('id', 'desc');
            //     }

            //     // Otherwise, return only quotations related to the current user
            //     return $quotation->where('sales_person_id', $currentUser->id)->orderBy('id', 'desc');
            // })
            ->defaultPaginationPageOption(50)
            ->paginated([50, 100])
            ->paginationPageOptions([50, 100])
            ->modifyQueryUsing(function (\Illuminate\Database\Eloquent\Builder $query) {
                $currentUser = auth('web')->user();

                // Always eager load relationships to prevent N+1 queries
                $query->with([
                    'lead.companyDetail:id,lead_id,company_name',
                    'subsidiary:id,company_name',
                    'sales_person:id,name',
                    'items:id,quotation_id,total_before_tax,total_after_tax'
                ]);

                if ($currentUser->role_id == 3) {
                    return $query->orderBy('id', 'desc');
                } elseif ($currentUser->role_id == 2) {
                    return $query->where(function ($q) use ($currentUser) {
                        $q->whereHas('lead', function ($leadQuery) use ($currentUser) {
                            $leadQuery->where('lead_owner', $currentUser->name);
                        })->orWhere('sales_person_id', $currentUser->id);
                    })->orderBy('id', 'desc');
                } else {
                    return $query->whereHas('lead', function ($leadQuery) {
                        $leadQuery->where('lead_owner', auth()->user()->name);
                    })->orderBy('id', 'desc');
                }
            })
            ->columns([
                TextColumn::make('quotation_reference_no')
                    ->label('Ref No'),
                TextColumn::make('quotation_date')
                    ->label('Date')
                    ->formatStateUsing(fn($state) => $state->format('j M Y')),
                TextColumn::make('quotation_type')
                    ->label('Type')
                    ->formatStateUsing(fn($state) => match($state) {
                        'product' => 'Product',
                        'hrdf' => 'HRDF',
                    }),
                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company')
                    ->formatStateUsing(function ($state, Quotation $record) {
                        // Determine which company name to display
                        $companyName = 'N/A';
                        if ($record->subsidiary_id && $record->subsidiary) {
                            $companyName = $record->subsidiary->company_name;
                        } elseif ($record->lead && $record->lead->companyDetail) {
                            $companyName = $record->lead->companyDetail->company_name;
                        }

                        // Format the display name
                        $fullName = $companyName;
                        $shortened = strtoupper(Str::limit($fullName, 25, '...'));
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
                TextColumn::make('currency')
                    ->alignCenter(),
                TextColumn::make('items_sum_total_before_tax')
                    ->label('Value (Before Tax)')
                    ->sum('items','total_before_tax')
                // TextColumn::make('items_sum_total_after_tax')
                //     ->label('Value')
                //     ->sum('items','total_after_tax')
                    // ->summarize([
                    //     Sum::make()
                    //         ->label('Total')
                    //         ->formatStateUsing(fn($state) => number_format($state,2,'.','')),
                    // ])
                    //->formatStateUsing(fn(Model $record, $state) => $record->currency . ' ' . $state)
                    ->alignRight(),
                TextColumn::make('sales_person.name')
                    ->label('Sales Person'),
                    // ->summarize([
                    //     Count::make()
                    // ]),
                TextColumn::make('status')
                    // ->options([
                    //     'new' => 'New',
                    //     'email_sent' => 'Email Sent',
                    //     'accepted' => 'Accepted',
                    //     'rejected' => 'Rejected',
                    // ])
                    // ->disabled(
                    //     function(Quotation $quotation) {
                    //         $lastUpdatedAt = Carbon::parse($quotation->updated_at);
                    //         /**
                    //          * hide duplicate button if it was updated less than 48 hours
                    //          * ago
                    //          */
                    //         return $lastUpdatedAt->diffInHours(now()) > 48;
                    //     }
                    // )
                    ->formatStateUsing(fn($state) => match($state->value) {
                        'new' => 'New',
                        'email_sent' => 'Email Sent',
                        'accepted' => 'Accepted',
                        // 'rejected' => 'Rejected',
                    })
                    ->color(fn($state) => match($state->value) {
                        'new' => 'warning',
                        'email_sent' => 'primary',
                        'accepted' => 'success',
                        // 'rejected' => 'danger',
                    })
            ])
            ->filters([
                SelectFilter::make('quotation_reference_no')
                    ->label('Ref No')
                    ->searchable()
                    ->getSearchResultsUsing(fn(Quotation $quotation, ?string $search, QuotationService $quotationService): array => $quotationService->searchQuotationByReferenceNo($quotation, $search))
                    ->getOptionLabelsUsing(fn(Quotation $quotation, QuotationService $quotationService): array => $quotationService->getQuotationList($quotation)),
                // Filter::make('quotation_reference_no')
                //     ->form([
                //         Select::make('quotation_reference_no')
                //             ->label('Ref No')
                //             ->placeholder('Search by ref no')
                //             ->options(fn(Quotation $quotation, QuotationService $quotationService): array => $quotationService->getQuotationList($quotation))
                //             ->searchable(),
                //     ])
                //     ->query(fn(Builder $query, array $data, QuotationService $quotationService): Builder => $quotationService->searchQuotationByReferenceNo($query, $data)),
                Filter::make('quotation_date')
                    ->label('Date')
                    ->form([
                        Flatpickr::make('quotation_date')
                            ->label('Date')
                            ->dateFormat('j M Y')
                            ->allowInput()
                    ])
                    ->query(fn(Builder $query, array $data, QuotationService $quotationService): Builder => $quotationService->searchQuotationByDate($query, $data)),
                SelectFilter::make('quotation_type')
                    ->label('Type')
                    ->searchable()
                    ->options([
                        'product' => 'Product',
                        'hrdf' => 'HRDF',
                        // 'other' => 'Others'
                    ]),
                // SelectFilter::make('company_id')
                //     ->label('Company')
                //     ->relationship('company', 'company_name')
                //     ->searchable()
                //     ->getSearchResultsUsing(
                //         fn(Lead $lead, ?string $search, QuotationService $quotationService): array => $quotationService->searchLeadByName($lead, $search)
                //     )
                //     ->getOptionLabelUsing(
                //         fn(Lead $lead, $value, QuotationService $quotationService): string => $quotationService->getLeadName($lead, $value)
                //     ),
                Filter::make('any_company_name')
                    ->form([
                        TextInput::make('any_company_name')
                            ->label('Company/Subsidiary Name')
                            ->placeholder('Search main or subsidiary company'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (!empty($data['any_company_name'])) {
                            $searchTerm = $data['any_company_name'];

                            $query->where(function ($query) use ($searchTerm) {
                                // Search in main company
                                $query->whereHas('lead.companyDetail', function ($query) use ($searchTerm) {
                                    $query->where('company_name', 'like', '%'.$searchTerm.'%');
                                })
                                // OR search in subsidiaries
                                ->orWhereHas('lead.subsidiaries', function ($query) use ($searchTerm) {
                                    $query->where('company_name', 'like', '%'.$searchTerm.'%');
                                })
                                // OR search in quotation's subsidiary (if quotation uses subsidiary)
                                ->orWhereHas('subsidiary', function ($query) use ($searchTerm) {
                                    $query->where('company_name', 'like', '%'.$searchTerm.'%');
                                });
                            });
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        return isset($data['any_company_name'])
                            ? 'Company/Subsidiary: ' . $data['any_company_name']
                            : null;
                    }),
                SelectFilter::make('sales_person_id')
                    ->label('Sales Person')
                    ->relationship('sales_person', 'name')
                    ->searchable()
                    ->preload()
                    ->getSearchResultsUsing(
                        fn(User $user, ?string $search, QuotationService $quotationService): array => $quotationService->searchSalesPersonName($user, $search)
                    )
                    ->getOptionLabelUsing(
                        fn(User $user, $value, QuotationService $quotationService): string => $quotationService->getSalesPersonName($user, $value)
                    )
                    ->hidden(function () {
                        $currentUser = auth()->user();

                        // Hide filter for role_id = 2 since they only see their own data
                        return $currentUser->role_id == 2;
                    }),
                SelectFilter::make('status')
                    ->label('Status')
                    ->searchable()
                    ->options([
                        'new' => 'New',
                        'email_sent' => 'Email Sent',
                        'accepted' => 'Accepted',
                        // 'rejected' => 'Rejected',
                    ]),
                SelectFilter::make('sales_type')
                    ->label('Sales Type')
                    ->options([
                        'NEW SALES' => 'NEW SALES',
                        'RENEWAL SALES' => 'RENEWAL SALES',
                    ])
                    ->searchable(),
                Filter::make('customer_type')
                    ->label('Customer Type')
                    ->form([
                        \Filament\Forms\Components\Select::make('customer_type')
                            ->label('Customer Type')
                            ->options([
                                'END USER' => 'END USER',
                                'RESELLER' => 'RESELLER',
                            ])
                            ->placeholder('Select type')
                    ])
                    ->query(fn (Builder $query, array $data) =>
                        !empty($data['customer_type'])
                            ? $query->whereHas('lead', fn ($q) => $q->where('customer_type', $data['customer_type']))
                            : $query
                    )
                    ->indicateUsing(fn (array $data) => $data['customer_type'] ?? null),

                Filter::make('region')
                    ->label('Region')
                    ->form([
                        \Filament\Forms\Components\Select::make('region')
                            ->label('Region')
                            ->options([
                                'LOCAL' => 'LOCAL',
                                'OVERSEA' => 'OVERSEA',
                            ])
                            ->placeholder('Select region')
                    ])
                    ->query(fn (Builder $query, array $data) =>
                        !empty($data['region'])
                            ? $query->whereHas('lead', fn ($q) => $q->where('region', $data['region']))
                            : $query
                    )
                    ->indicateUsing(fn (array $data) => $data['region'] ?? null),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(6)
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->color('danger')
                        ->hidden(
                            function(Quotation $quotation) {
                                $lastUpdatedAt = Carbon::parse($quotation->updated_at);
                                /**
                                 * hide edit button if it was updated more than 48 hours
                                 * ago
                                 */
                                return $lastUpdatedAt->diffInHours(now()) > 48;
                            }
                        ),
                    Tables\Actions\Action::make('duplicate')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('warning')
                        // ->hidden(
                        //     function(Quotation $quotation) {
                        //         $lastUpdatedAt = Carbon::parse($quotation->updated_at);
                        //         /**
                        //          * hide duplicate button if it was updated less than 48 hours
                        //          * ago
                        //          */
                        //         return $lastUpdatedAt->diffInHours(now()) < 48;
                        //     }
                        // )
                        ->action(function(Quotation $quotation, QuotationService $quotationService) {
                            // Ensure the quotation has its items loaded before duplication
                            $quotation->load('items');
                            return $quotationService->duplicate($quotation);
                        }),
                    // Tables\Actions\Action::make('preview_pdf')
                    //     ->label('Preview PDF')
                    //     ->icon('heroicon-o-viewfinder-circle')
                    //     ->infolist([
                    //         PdfViewerEntry::make('file')
                    //             ->label(fn(Quotation $quotation): string => Str::slug($quotation->company->name) . '_' . quotation_reference_no($quotation->id) . '_' . Str::lower($quotation->sales_person->code) . '.pdf')
                    //             ->fileUrl(
                    //                 function(Quotation $quotation) {
                    //                     $quotationFilename = Str::slug($quotation->company->name) . '_' . quotation_reference_no($quotation->id) . '_' . Str::lower($quotation->sales_person->code) . '.pdf';
                    //                     info("Quotation: {$quotationFilename}");
                    //                     return Storage::url('/quotations/'.$quotationFilename);
                    //                 }
                    //             )
                    //             ->columnSpanFull(),
                    // ]),
                    // Tables\Actions\Action::make('pdf')
                    //     ->label('PDF')
                    //     ->color('success')
                    //     ->icon('heroicon-o-arrow-down-on-square')
                    //     ->url(fn (Quotation $record) => route('pdf.print-quotation', $record))
                    //     ->openUrlInNewTab(),
                    // Tables\Actions\Action::make('Quotation')
                    //     ->label('Preview')
                    //     ->color('success')
                    //     ->icon('heroicon-o-arrow-down-on-square')
                    //     ->infolist([
                    //         PdfViewerEntry::make('')
                    //             // ->label(fn(Quotation $quotation): string => Str::slug($quotation->company->name) . '_' . quotation_reference_no($quotation->id) . '_' . Str::lower($quotation->sales_person->code) . '.pdf')
                    //             ->fileUrl(
                    //                 function(Quotation $quotation, GeneratePDFService $generatePDFService, QuotationService $quotationService) {
                    //                     $generatePDFService->generateQuotation($quotation, $quotationService);
                    //                     // $quotationFilename = Str::slug($quotation->company->name) . '_' . quotation_reference_no($quotation->id) . '_' . Str::lower($quotation->sales_person->code) . '.pdf';
                    //                     $quotationFilename = $quotationService->update_reference_no($quotation);
                    //                     $quotationFilename = Str::replace('/','_',$quotationFilename);
                    //                     $quotationFilename .= '_' . Str::upper(Str::replace('-','_',Str::slug($quotation->company->name))) . '.pdf';
                    //                     return Storage::url('/quotations/'.$quotationFilename);
                    //                 }
                    //             )
                    //             ->columnSpanFull()
                    //             ->minHeight('80svh'),
                    //     ]),
                    Tables\Actions\Action::make('View PDF')
                        ->label('Preview')
                        ->icon('heroicon-o-arrow-down-on-square')
                        ->color('success')
                        ->url(fn(Quotation $quotation) => route('pdf.print-quotation-v2', ['quotation' => encrypt($quotation->id)]))
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('Accept')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->requiresConfirmation()
                        ->modalHeading('Convert to Proforma Invoice')
                        ->form(function (Quotation $record) {
                            // ✅ Check if any products cannot be pushed to PI
                            $hasNonPushableProducts = $record->items()
                                ->whereHas('product', function ($query) {
                                    $query->where('convert_pi', false)
                                        ->orWhereNull('convert_pi');
                                })
                                ->exists();

                            if ($hasNonPushableProducts) {
                                // ✅ Get the list of products that cannot be pushed
                                $nonPushableProducts = $record->items()
                                    ->with('product')
                                    ->whereHas('product', function ($query) {
                                        $query->where('convert_pi', false)
                                            ->orWhereNull('convert_pi');
                                    })
                                    ->get()
                                    ->pluck('product.description')
                                    ->filter()
                                    ->unique()
                                    ->values();

                                $productList = $nonPushableProducts->isEmpty()
                                    ? 'Some products'
                                    : '<ul style="margin: 10px 0; padding-left: 20px;">' .
                                    $nonPushableProducts->map(fn($name) => "<li>{$name}</li>")->implode('') .
                                    '</ul>';

                                return [
                                    Forms\Components\Placeholder::make('warning')
                                        ->content(new \Illuminate\Support\HtmlString(
                                            '<div style="padding: 16px; background: #FEF2F2; border: 1px solid #FCA5A5; border-radius: 8px; color: #991B1B;">
                                                <div style="display: flex; align-items: start; gap: 12px;">
                                                    <svg style="width: 24px; height: 24px; flex-shrink: 0; margin-top: 2px;" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
                                                    </svg>
                                                    <div>
                                                        <h4 style="margin: 0 0 8px 0; font-weight: 600; font-size: 16px;">Cannot Convert to Proforma Invoice</h4>
                                                        <p style="margin: 0 0 8px 0; font-size: 14px;">
                                                            Your quotation contains product(s) that cannot be pushed to Proforma Invoice:
                                                        </p>
                                                        ' . $productList . '
                                                        <p style="margin: 8px 0 0 0; font-size: 14px; font-weight: 500;">
                                                            Please remove these products or update their settings before converting to PI.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>'
                                        ))
                                        ->hiddenLabel(),
                                ];
                            }

                            // ✅ If all products can be pushed, show normal confirmation
                            return [
                                Forms\Components\Placeholder::make('confirmation')
                                    ->content(new \Illuminate\Support\HtmlString(
                                        '<div style="padding: 16px; background: #F0FDF4; border: 1px solid #86EFAC; border-radius: 8px; color: #166534;">
                                            <div style="display: flex; align-items: start; gap: 12px;">
                                                <svg style="width: 24px; height: 24px; flex-shrink: 0; margin-top: 2px;" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                                                </svg>
                                                <div>
                                                    <h4 style="margin: 0 0 8px 0; font-weight: 600; font-size: 16px;">Ready to Convert</h4>
                                                    <p style="margin: 0; font-size: 14px;">
                                                        This quotation is ready to be converted to a Proforma Invoice. Click "Accept" to proceed.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>'
                                    ))
                                    ->hiddenLabel(),
                            ];
                        })
                        ->action(
                            function (Quotation $quotation, QuotationService $quotationService, array $data) {
                                // ✅ Double-check before processing
                                $hasNonPushableProducts = $quotation->items()
                                    ->whereHas('product', function ($query) {
                                        $query->where('convert_pi', false)
                                            ->orWhereNull('convert_pi');
                                    })
                                    ->exists();

                                if ($hasNonPushableProducts) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Cannot Convert to PI')
                                        ->body('This quotation contains products that cannot be pushed to Proforma Invoice.')
                                        ->send();
                                    return;
                                }

                                // Proceed with normal acceptance flow
                                $quotation->pi_reference_no = $quotationService->update_pi_reference_no($quotation);
                                $quotation->status = QuotationStatusEnum::accepted;
                                $quotation->save();

                                $notifyUsers = User::whereIn('role_id',['2'])->get();
                                $currentUser = User::find(auth('web')->user()->id);
                                $notifyUsers = $notifyUsers->push($currentUser);

                                Notification::make()
                                    ->success()
                                    ->title('Confirmation Order Document Uploaded!')
                                    ->body('Confirmation order document for quotation ' . $quotation->quotation_reference_no . ' has been uploaded successfully!')
                                    ->send();

                                $lead = $quotation->lead;

                                $lead->update([
                                    'follow_up_date' => null,
                                ]);

                                ActivityLog::create([
                                    'subject_id' => $lead->id,
                                    'description' => 'Order Uploaded. Pending Approval to close lead.',
                                    'causer_id' => auth()->id(),
                                    'causer_type' => get_class(auth()->user()),
                                    'properties' => json_encode([
                                        'attributes' => [
                                            'quotation_reference_no' => $quotation->quotation_reference_no,
                                            'lead_status' => $lead->lead_status,
                                            'stage' => $lead->stage,
                                        ],
                                    ]),
                                ]);
                            }
                        )
                        ->closeModalByClickingAway(false)
                        ->modalWidth(MaxWidth::Medium)
                        // ✅ Hide submit button if products cannot be pushed to PI
                        ->modalSubmitAction(function ($action, Quotation $record) {
                            $hasNonPushableProducts = $record->items()
                                ->whereHas('product', function ($query) {
                                    $query->where('convert_pi', false)
                                        ->orWhereNull('convert_pi');
                                })
                                ->exists();

                            return $hasNonPushableProducts ? $action->hidden() : $action;
                        })
                        ->modalCancelActionLabel(function (Quotation $record) {
                            $hasNonPushableProducts = $record->items()
                                ->whereHas('product', function ($query) {
                                    $query->where('convert_pi', false)
                                        ->orWhereNull('convert_pi');
                                })
                                ->exists();

                            return $hasNonPushableProducts ? 'Close' : 'Cancel';
                        })
                        ->visible(function(Quotation $quotation) {
                            // First, check if the quotation is not already accepted and lead status is closed
                            if ($quotation->status === QuotationStatusEnum::accepted) {
                                return false;
                            }

                            // Then check if company details are complete
                            $lead = $quotation->lead;
                            $companyDetail = $lead->companyDetail ?? null;

                            // If no company details exist at all
                            if (!$companyDetail) {
                                return false;
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
                                    return false;
                                }
                            }

                            // All checks passed, show the button
                            return true;
                        }),
                    Tables\Actions\Action::make('proforma_invoice')
                        ->label('Proforma Invoice')
                        ->color('primary')
                        ->icon('heroicon-o-document-text')
                        ->url(fn(Quotation $quotation) => route('pdf.print-proforma-invoice-v2', $quotation))
                        ->openUrlInNewTab()
                        ->hidden(fn(Quotation $quotation) => $quotation->status != QuotationStatusEnum::accepted),
                    Tables\Actions\Action::make('create_invoice')
                        ->label('Create Invoice')
                        ->icon('heroicon-o-document-plus')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalHeading('Create AutoCount Invoice')
                        ->modalDescription('This will create an invoice in AutoCount accounting system for this HRDF quotation.')
                        ->form([
                            \Filament\Forms\Components\Placeholder::make('info')
                                ->content(new \Illuminate\Support\HtmlString(
                                    '<div style="padding: 16px; background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 8px; color: #1E40AF;">
                                        <div style="display: flex; align-items: start; gap: 12px;">
                                            <svg style="width: 24px; height: 24px; flex-shrink: 0; margin-top: 2px;" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/>
                                            </svg>
                                            <div>
                                                <h4 style="margin: 0 0 8px 0; font-weight: 600; font-size: 16px;">Create AutoCount Invoice</h4>
                                                <p style="margin: 0; font-size: 14px;">
                                                    This will create both a debtor and invoice record in AutoCount accounting system.
                                                </p>
                                            </div>
                                        </div>
                                    </div>'
                                ))
                                ->hiddenLabel(),

                            \Filament\Forms\Components\Toggle::make('create_debtor')
                                ->label('Create Debtor Record')
                                ->helperText('Create customer/debtor record in AutoCount if it doesn\'t exist')
                                ->default(true),
                        ])
                        ->action(function (Quotation $quotation, array $data) {
                            try {
                                $autoCountService = new AutoCountInvoiceService();

                                if ($data['create_debtor'] ?? true) {
                                    // Create both debtor and invoice
                                    $debtorData = $autoCountService->convertQuotationToDebtorData($quotation);
                                    $invoiceData = $autoCountService->convertQuotationToInvoiceData($quotation);

                                    $result = $autoCountService->createDebtorAndInvoice($debtorData, $invoiceData);
                                } else {
                                    // Create invoice only (assumes debtor already exists)
                                    $invoiceData = $autoCountService->convertQuotationToInvoiceData($quotation);
                                    // You'll need to set customer_code manually if not creating debtor
                                    $invoiceData['customer_code'] = $quotation->lead->companyDetail->autocount_debtor_code ?? 'ARM-UNKNOWN';

                                    $result = $autoCountService->createInvoice($invoiceData);
                                }

                                if ($result['success']) {
                                    // Update quotation with AutoCount details
                                    $updateData = [
                                        'synced_to_autocount' => true,
                                        'synced_at' => now(),
                                    ];

                                    if (isset($result['debtor_code'])) {
                                        $updateData['autocount_debtor_code'] = $result['debtor_code'];
                                    }

                                    if (isset($result['invoice_no'])) {
                                        $updateData['autocount_invoice_no'] = $result['invoice_no'];
                                    } elseif (isset($result['data']['dataResults']['newInvoiceNo'])) {
                                        $updateData['autocount_invoice_no'] = $result['data']['dataResults']['newInvoiceNo'];
                                    }

                                    $quotation->update($updateData);

                                    Notification::make()
                                        ->success()
                                        ->title('Invoice Created Successfully!')
                                        ->body(
                                            'AutoCount invoice created successfully. ' .
                                            (isset($result['debtor_code']) ? 'Debtor: ' . $result['debtor_code'] . '. ' : '') .
                                            'Invoice: ' . ($result['invoice_no'] ?? $result['data']['dataResults']['newInvoiceNo'] ?? 'Created')
                                        )
                                        ->send();

                                    // Log activity
                                    ActivityLog::create([
                                        'subject_id' => $quotation->lead->id,
                                        'description' => 'AutoCount invoice created for HRDF quotation.',
                                        'causer_id' => auth()->id(),
                                        'causer_type' => get_class(auth()->user()),
                                        'properties' => json_encode([
                                            'attributes' => [
                                                'quotation_id' => $quotation->id,
                                                'quotation_reference_no' => $quotation->quotation_reference_no,
                                                'autocount_debtor_code' => $result['debtor_code'] ?? null,
                                                'autocount_invoice_no' => $result['invoice_no'] ?? $result['data']['dataResults']['newInvoiceNo'] ?? null,
                                            ],
                                        ]),
                                    ]);
                                } else {
                                    Notification::make()
                                        ->danger()
                                        ->title('Invoice Creation Failed')
                                        ->body('Failed to create AutoCount invoice: ' . $result['error'])
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error('AutoCount invoice creation failed', [
                                    'quotation_id' => $quotation->id,
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString(),
                                ]);

                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('An error occurred while creating the invoice: ' . $e->getMessage())
                                    ->send();
                            }
                        })
                        ->visible(function(Quotation $quotation) {
                            // Only show for HRDF quotations that are already accepted
                            return $quotation->quotation_type === 'hrdf' &&
                                $quotation->status === QuotationStatusEnum::accepted;
                        })
                        ->hidden(function(Quotation $quotation) {
                            // Hide if already synced to AutoCount
                            return $quotation->synced_to_autocount === true;
                        }),
                ])
                ->icon('heroicon-m-ellipsis-vertical')
                ->size(ActionSize::ExtraSmall)
                ->color('primary')
                ->button(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotations::route('/'),
            'create' => Pages\CreateQuotation::route('/create'),
            'edit' => Pages\EditQuotation::route('/{record}/edit'),
            // 'send-quotation-email' => Pages\SendQuotationEmail::route('/{record}/send-quotation-email'), temporary comment
        ];
    }

    protected static function getHeaderActions(): array
    {
        return [];
    }

    // public static function recalculateAllRows($get, $set, $field=null, $state=null): void
    // {
    //     $items = $get('../../items');

    //     $subtotal = 0;
    //     $grandTotal = 0;
    //     $totalTax = 0;
    //     $product = null;

    //     foreach ($items as $index => $item) {

    //         if (array_key_exists('product_id',$item)) {
    //             info("Product ID: {$item['product_id']}");
    //             if ($item['product_id']) {
    //                 $product_id = $item['product_id'];
    //                 $product = Product::find($product_id);
    //                 $set("../../description", $product->description);
    //                 $set("../../items.{$index}.description", $product->description);
    //             } else {
    //                 $quantity = 0;
    //                 $unit_price = 0;
    //             }
    //         }

    //         if ($product?->solution == 'hrdf') {
    //             // $itemQuantity = $get("../../items.{$index}.quantity");
    //             $itemQuantity = 0;
    //             if ($item['product_id'] != null) {
    //                 $itemQuantity = $get("../../items.{$index}.quantity");
    //             }
    //             info("Quantity: {$itemQuantity}");
    //             // $set("../../items.{$index}.quantity",$get("../../number_of_participant"));
    //             $set("../../items.{$index}.quantity", $itemQuantity);
    //         }

    //         if ($product?->solution == 'software' || $product?->solution == 'hardware') {
    //             // $set("../../items.{$index}.quantity",$get("../../items.{$index}.quantity"));
    //             $set("../../items.{$index}.quantity",$get("../../items.{$index}.quantity") ?? $product?->quantity);
    //             if ($get("../../items.{$index}.subscription_period") == 0) {
    //                 $set("../../items.{$index}.subscription_period", $get("../../subscription_period"));
    //             }
    //         }

    //         $quantity = (float) $get("../../items.{$index}.quantity");
    //         if (!$quantity) {
    //             $quantity = (float) $get("../../headcount");
    //             $set("../../items.{$index}.quantity", $quantity);
    //         }
    //         $subscription_period =  $get("../../items.{$index}.subscription_period");
    //         // info("Unit Price: {$item['unit_price']}");
    //         $unit_price = 0;
    //         if (array_key_exists('unit_price',$item)) {
    //             $unit_price = (float) $item['unit_price'];
    //             //info("Unit Price 1: {$unit_price} ({$index})");
    //             if ($item['unit_price'] == 0.00 && $item['product_id'] != null) {
    //                 $unit_price = (float) $product?->unit_price;
    //                 //info("Unit Price 2: {$unit_price} ({$index})");
    //             }
    //         }

    //         $set("../../items.{$index}.unit_price", $unit_price);

    //         // Calculate total before tax
    //         $total_before_tax = (int) $quantity * (float) $unit_price;
    //         if ($product && $product->solution == 'software') {
    //             /**
    //              * include subscription period in calculation for software
    //              */
    //             $total_before_tax = (int) $quantity * (int) $subscription_period * (float) $unit_price;
    //         }

    //         $subtotal += $total_before_tax;
    //         // Calculate taxation amount
    //         $taxation_amount = 0;
    //         if ($product?->taxable) {
    //             $sstRate = $get('../../sst_rate');
    //             $taxation_amount = $total_before_tax * ($sstRate / 100);
    //             $totalTax += $taxation_amount;
    //         }

    //         if (array_key_exists('description',$item)) {
    //             $description = trim($item['description']);
    //             if (Str::length($description) == 0 && $field == 'product_id') {
    //                 $description = $product?->description;
    //             }
    //         } else {
    //             $description = $product?->description;
    //         }

    //         $set("../../items.{$index}.description", $product?->description);
    //         $set("../../description", $product?->description);
    //         // Calculate total after tax
    //         $total_after_tax = $total_before_tax + $taxation_amount;
    //         $grandTotal += $total_after_tax;
    //         // Update the form values
    //         $set("../../items.{$index}.unit_price", number_format($unit_price, 2, '.', ''));
    //         $set("../../items.{$index}.total_before_tax", number_format($total_before_tax, 2, '.', ''));
    //         $set("../../items.{$index}.taxation", number_format($taxation_amount, 2, '.', ''));
    //         $set("../../items.{$index}.total_after_tax", number_format($total_after_tax, 2, '.', ''));
    //     }

    //     /**
    //      * Update summary
    //      */
    //     $set('../../sub_total', number_format($subtotal, 2, '.', ''));
    //     $set('../../tax_amount', number_format($totalTax, 2, '.', ''));
    //     $set('../../total', number_format($grandTotal, 2, '.', ''));
    // }

    public static function recalculateAllRows($get, $set, $field = null, $state = null): void
    {
        $items = $get('../../items');
        $taxationCategory = $get('../../taxation_category');
        $currency = $get('../../currency');

        $subtotal = 0;
        $grandTotal = 0;
        $totalTax = 0;

        $productOccurrences = [];
        $productCounters = [];

        // First pass: count total occurrences of each software product
        foreach ($items as $item) {
            if (!empty($item['product_id'])) {
                $product = Product::find($item['product_id']);
                if ($product && $product->solution === 'software') {
                    $productId = $item['product_id'];
                    $productOccurrences[$productId] = ($productOccurrences[$productId] ?? 0) + 1;
                }
            }
        }

        foreach ($items as $index => $item) {
            $product = null;

            if (!empty($item['product_id'])) {
                $product = Product::find($item['product_id']);

                if ($product && $product->solution === 'software') {
                    $productId = $item['product_id'];

                    if (!isset($productCounters[$productId])) {
                        $productCounters[$productId] = 1;
                    } else {
                        $productCounters[$productId]++;
                    }

                    $isManuallyEdited = $get("../../items.{$index}.subscription_manually_edited");

                    // ✅ ONLY modify subscription period in very specific cases
                    if ($field === 'product_id' && !$isManuallyEdited) {
                        // Only when a new product is selected
                        $set("../../items.{$index}.subscription_period", 12);
                        $set("../../items.{$index}.subscription_manually_edited", false);
                    } elseif ($field === 'subscription_period') {
                        // ✅ If this is a subscription period change, DON'T override it
                        // The user is manually editing, so preserve their input
                    }
                    // ✅ For all other field changes, don't touch subscription period
                }
            }

            // Rest of your calculation logic remains the same...
            $currentQuantity = $get("../../items.{$index}.quantity");

            if (!$currentQuantity || $currentQuantity == 0) {
                if ($product?->solution === 'hrdf') {
                    $numParticipants = $get("../../num_of_participant");
                    if ($numParticipants) {
                        $set("../../items.{$index}.quantity", $numParticipants);
                        $currentQuantity = $numParticipants;
                    }
                } elseif ($product?->solution === 'software' || $product?->solution === 'hardware') {
                    $defaultQuantity = $product?->quantity ?? 1;
                    $set("../../items.{$index}.quantity", $defaultQuantity);
                    $currentQuantity = $defaultQuantity;
                }
            }

            $quantity = (float) $currentQuantity ?: 1;
            $subscriptionPeriod = $get("../../items.{$index}.subscription_period") ?: 12;
            $unitPrice = isset($item['unit_price']) ? (float) $item['unit_price'] : 0;

            if ($unitPrice === 0.00 && $product?->unit_price) {
                $unitPrice = $product->unit_price;
            }
            $set("../../items.{$index}.unit_price", $unitPrice);

            // Calculate totals...
            $totalBeforeTax = $quantity * $unitPrice;
            if ($product?->solution === 'software') {
                $totalBeforeTax = $quantity * $subscriptionPeriod * $unitPrice;
            }

            $subtotal += $totalBeforeTax;

            // Tax calculation...
            $taxAmount = 0;
            $taxCode = null;

            $shouldTax = match ($taxationCategory) {
                'default' => $product?->taxable,
                'all_taxable' => true,
                'all_non_taxable' => false,
                default => $product?->taxable,
            };

            if ($shouldTax) {
                if ($currency === 'MYR') {
                    $sstRate = $get('../../sst_rate');
                    $taxAmount = $totalBeforeTax * ($sstRate / 100);
                    $totalTax += $taxAmount;
                    $taxCode = 'SV-8';
                } elseif ($currency === 'USD') {
                    $taxCode = 'NTS';
                }
            }

            if ($product) {
                $set("../../items.{$index}.convert_pi", $product->convert_pi);
                $set("../../items.{$index}.tariff_code", $product->tariff_code);
            }

            $set("../../items.{$index}.tax_code", $taxCode);

            $currentDescription = $get("../../items.{$index}.description") ?? '';
            if (blank($currentDescription) || ($field === 'product_id' && $state && $index === $get('../../_repeater_index'))) {
                $set("../../items.{$index}.description", $product?->description);
            }

            $totalAfterTax = $totalBeforeTax + $taxAmount;
            $grandTotal += $totalAfterTax;

            $set("../../items.{$index}.total_before_tax", number_format($totalBeforeTax, 2, '.', ''));
            $set("../../items.{$index}.taxation", number_format($taxAmount, 2, '.', ''));
            $set("../../items.{$index}.total_after_tax", number_format($totalAfterTax, 2, '.', ''));
        }

        // Summary totals
        $set('../../sub_total', number_format($subtotal, 2, '.', ''));
        $set('../../tax_amount', number_format($totalTax, 2, '.', ''));
        $set('../../total', number_format($grandTotal, 2, '.', ''));
    }

    public static function updateSubscriptionPeriodInAllRows(Forms\Get $get, Forms\Set $set, ?string $state): void
    {
        $set('../../base_subscription', $state);
        $set('../*.subscription_period', $state);
    }

    public static function updateFields(?string $field, Forms\Get $get, Forms\Set $set, ?string $state): void
    {
        /**
         * if both $field and $state are not null
         */
        if ($field && $state) {
            $productId = $get('product_id');
            /**
             * if there is a change in product
             */
            if ($field == 'product_id') {
                $productId = $state;
                $product = Product::find($productId);
                $set('quantity',1);
                if ($product->solution == 'software') {
                    //$set('quantity',$get('../../headcount'));
                    $set('subscription_period',$get('../../base_subscription'));
                }

                $set('unit_price', $product->unit_price);
            } else {
                $product = Product::find($productId);
            }

            $quantity = $get('quantity');
            /**
             * if there is a change in quantity
             */
            if ($field == 'quantity') {
                $quantity = $state;
            }

            $subscription = $get('subscription_period');
            /**
             * if there is a change in subscription period
             */
            if ($field == 'subscription_period') {
                //$set('../../base_subscription', $state);
                $set('../*.subscription_period', $state);
                $subscription = $state;
            }

            $unitPrice = $get('unit_price');
            /**
             * if there is a change in unit price
             */
            if ($field == 'unit_price') {
                $unitPrice = $state;
            }

            $totalBeforeTax = $quantity * $unitPrice;
            /**
             * if product is a software, we include subscription period in the calculation
             * of total value before tax
             */
            if ($product->solution == 'software') {

                $totalBeforeTax = $quantity * $unitPrice * $subscription;
            } else {
                /**
                 * subscription period is not applicable to hardware,
                 * hence we set it to null
                 */
                $set('subscription_period',null);
            }

            /**
             * if the product is not subject to SST,
             * total value before tax and after tax are the same
             */
            $totalAfterTax = $totalBeforeTax;

            $set('description', $product->description);
            $set('total_before_tax', number_format($totalBeforeTax,2,'.',''));

            $set('taxation', null);
            /**
             * if product is subjected to SST
             */
            if ($product?->taxable) {
                $sstRate = $get('../../sst_rate');
                $taxValue = $totalBeforeTax * ($sstRate/100);
                $totalAfterTax = $totalBeforeTax + $taxValue;

                $set('taxation', number_format($taxValue,2,'.',''));
            }

            $set('total_after_tax', number_format($totalAfterTax,2,'.',''));
        }

        if (!$field && !$state) {
            $selectedProducts = collect($get('items'))->filter(fn($item) => !empty($item['product_id']) && !empty($item['quantity']));
        } else {
            $selectedProducts = collect($get('../../items'))->filter(fn($item) => !empty($item['product_id']) && !empty($item['quantity']));
        }

        // Retrieve prices for all selected products
        //$prices = Product::find($selectedProducts->pluck('product_id'))->pluck('unit_price', 'id');
        // Calculate subtotal based on the selected products and quantities
        $taxAmount = $selectedProducts->reduce(function($taxAmount,$product) {
            return $taxAmount + $product['taxation'];
        });

        $subtotal = $selectedProducts->reduce(function ($subtotal,$product) {
            return $subtotal + $product['total_before_tax'];
        }, 0);

        $total = $selectedProducts->reduce(function ($total,$product) {
            return $total + $product['total_after_tax'];
        }, 0);

        $sstRate = Setting::where('name','sst_rate')->first()->value;

        if (!$field && !$state) {
            $set('sub_total', number_format($subtotal, 2, '.', ''));
            $set('tax_amount', number_format($taxAmount, 2, '.' ,''));
            $set('total', number_format($total, 2, '.', ''));
        } else {
            // Update the state with the new values
            $set('../../sub_total', number_format($subtotal, 2, '.', ''));
            $set('../../tax_amount', number_format($taxAmount, 2, '.' ,''));
            $set('../../total', number_format($total, 2, '.', ''));
        }
    }

    public static function recalculateAllRowsFromParent($get, $set): void
    {
        $items = $get('items') ?? [];
        $taxationCategory = $get('taxation_category');
        $currency = $get('currency');

        $subtotal = 0;
        $grandTotal = 0;
        $totalTax = 0;

        // Bulk fetch all products to reduce database queries
        $productIds = collect($items)->pluck('product_id')->filter()->unique()->toArray();
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        foreach ($items as $index => $item) {
            $product = null;

            if (array_key_exists('product_id', $item) && isset($products[$item['product_id']])) {
                $product = $products[$item['product_id']];

                if ($product) {
                    $set("items.{$index}.convert_pi", $product->convert_pi);
                    $set("items.{$index}.tariff_code", $product->tariff_code);

                    // ✅ ONLY set subscription period if it's empty AND not manually edited
                    if ($product->solution === 'software') {
                        $currentSubscriptionPeriod = $get("items.{$index}.subscription_period");
                        $isManuallyEdited = $get("items.{$index}.subscription_manually_edited");

                        // Only set if empty and not manually edited
                        if (!$currentSubscriptionPeriod && !$isManuallyEdited) {
                            $set("items.{$index}.subscription_period", 12);
                        }
                    }
                }
            }

            // ✅ Fix quantity handling - preserve existing quantity, don't force reset
            $currentQuantity = $get("items.{$index}.quantity");

            // Only set quantity if it's empty/null/zero AND we have context for what it should be
            if (!$currentQuantity || $currentQuantity == 0) {
                if ($product?->solution == 'hrdf') {
                    // For HRDF, use num_of_participant if available
                    $numParticipants = $get("num_of_participant");
                    if ($numParticipants) {
                        $set("items.{$index}.quantity", $numParticipants);
                    }
                } elseif ($product?->solution == 'software' || $product?->solution == 'hardware') {
                    // For software/hardware, use product's default quantity
                    $defaultQuantity = $product?->quantity ?? 1;
                    $set("items.{$index}.quantity", $defaultQuantity);
                }
            }

            $quantity = $get("items.{$index}.quantity") ?: 1;
            $subscription_period = $get("items.{$index}.subscription_period") ?: 12; // ✅ Default to 12
            $unit_price = 0;

            if (array_key_exists('unit_price', $item)) {
                $unit_price = $item['unit_price'];
                if ($unit_price == 0.00) {
                    $unit_price = $product?->unit_price;
                }
            }

            $set("items.{$index}.unit_price",$unit_price);

            // Calculate total before tax
            $total_before_tax = (int) $quantity * (float) $unit_price;
            if ($product && $product->solution == 'software') {
                $total_before_tax = (int) $quantity * (int) $subscription_period * (float) $unit_price;
            }

            $subtotal += $total_before_tax;

            // Calculate taxation amount and tax code
            $taxation_amount = 0;
            $taxCode = null;

            // Determine if this item should be taxed
            $shouldTax = match ($taxationCategory) {
                'default' => $product?->taxable,
                'all_taxable' => true,
                'all_non_taxable' => false,
                default => $product?->taxable,
            };

            if ($shouldTax) {
                if ($currency === 'MYR') {
                    $sstRate = $get('sst_rate');
                    $taxation_amount = $total_before_tax * ($sstRate / 100);
                    $totalTax += $taxation_amount;
                    $taxCode = 'SV-8';
                } elseif ($currency === 'USD') {
                    $taxCode = 'NTS';
                }
            }

            $set("items.{$index}.tax_code", $taxCode);

            $currentDescription = $get("items.{$index}.description") ?? '';

            // Only set product description when current description is empty/blank
            if (blank($currentDescription) && $product) {
                $set("items.{$index}.description", $product?->description);
            }

            // Calculate total after tax
            $total_after_tax = $total_before_tax + $taxation_amount;
            $grandTotal += $total_after_tax;

            // Update the form values
            $set("items.{$index}.unit_price", number_format((float)$unit_price, 2, '.', ''));
            $set("items.{$index}.total_before_tax", number_format($total_before_tax, 2, '.', ''));
            $set("items.{$index}.taxation", number_format($taxation_amount, 2, '.', ''));
            $set("items.{$index}.total_after_tax", number_format($total_after_tax, 2, '.', ''));
        }

        // Update summary
        $set('sub_total', number_format($subtotal, 2, '.', ''));
        $set('tax_amount', number_format($totalTax, 2, '.', ''));
        $set('total', number_format($grandTotal, 2, '.', ''));
    }

    public static function calculateYearForSingleItem(Forms\Get $get, Forms\Set $set): void
    {
        $currentProductId = $get('product_id');
        if (!$currentProductId) {
            $set('year', null);
            return;
        }

        // Get all items
        $items = $get('../../items') ?? [];
        $currentPath = $get('../../_repeater_path'); // This helps identify which item we're working on

        // Count occurrences of the same product before this item
        $yearCount = 0;
        $currentIndex = null;

        // Find current item index first
        foreach ($items as $index => $item) {
            if ($index === count($items) - 1) { // If this is the last item (newly added)
                $currentIndex = $index;
                break;
            }
        }

        // If we couldn't determine current index, try to count based on product occurrences
        if ($currentIndex === null) {
            foreach ($items as $index => $item) {
                if (!empty($item['product_id']) && $item['product_id'] === $currentProductId) {
                    $yearCount++;
                }
            }
        } else {
            // Count occurrences before current index
            for ($i = 0; $i < $currentIndex; $i++) {
                if (!empty($items[$i]['product_id']) && $items[$i]['product_id'] === $currentProductId) {
                    $yearCount++;
                }
            }
            $yearCount++; // Add 1 for current item
        }

        $set('year', "Year {$yearCount}");
    }

    public static function recalculateAllYears(Forms\Get $get, Forms\Set $set): void
    {
        $items = $get('items') ?? [];
        $productCounts = [];

        foreach ($items as $index => $item) {
            if (empty($item['product_id'])) {
                continue;
            }

            $product = Product::find($item['product_id']);
            if (!$product || $product->solution !== 'software') {
                continue;
            }

            $productId = $item['product_id'];

            // Initialize counter for this product if not exists
            if (!isset($productCounts[$productId])) {
                $productCounts[$productId] = 0;
            }

            // Increment counter
            $productCounts[$productId]++;

            // Set year for this item
            $set("items.{$index}.year", "Year {$productCounts[$productId]}");
        }
    }

    public static function calculateYearForDuplicateProducts(Forms\Get $get, Forms\Set $set): void
    {
        $currentProductId = $get('product_id');
        if (!$currentProductId) {
            $set('year', null);
            return;
        }

        $product = Product::find($currentProductId);
        if (!$product || $product->solution !== 'software') {
            $set('year', null);
            return;
        }

        // Get all items and find current item position
        $items = $get('../../items') ?? [];
        $currentItemKey = $get('../../_key'); // Get the current repeater item key

        // Count occurrences of this product up to current position
        $yearCount = 0;
        $foundCurrentItem = false;

        foreach ($items as $key => $item) {
            if (!empty($item['product_id']) && $item['product_id'] === $currentProductId) {
                $yearCount++;

                // If this is the current item, set its year and stop
                if ($key === $currentItemKey) {
                    $foundCurrentItem = true;
                    break;
                }
            }
        }

        // If we couldn't find current item by key, count all occurrences
        if (!$foundCurrentItem) {
            $yearCount = 1;
            foreach ($items as $item) {
                if (!empty($item['product_id']) && $item['product_id'] === $currentProductId) {
                    break;
                }
            }
        }

        $set('year', "Year {$yearCount}");
    }

    public static function recalculateAllYearsForAllItems(Forms\Get $get, Forms\Set $set): void
    {
        $items = $get('../../items') ?? [];
        $productYearCounters = [];
        $productOccurrences = [];

        // First pass: count total occurrences of each product
        foreach ($items as $item) {
            if (!empty($item['product_id'])) {
                $productId = $item['product_id'];
                $productOccurrences[$productId] = ($productOccurrences[$productId] ?? 0) + 1;
            }
        }

        // Second pass: process each item in order
        foreach ($items as $index => $item) {
            if (empty($item['product_id'])) {
                continue;
            }

            $product = Product::find($item['product_id']);
            if (!$product || $product->solution !== 'software') {
                $set("../../items.{$index}.year", null);
                continue;
            }

            $productId = $item['product_id'];

            // Initialize or increment counter for this product
            if (!isset($productYearCounters[$productId])) {
                $productYearCounters[$productId] = 1;
            } else {
                $productYearCounters[$productId]++;
            }

            // ✅ Only set subscription period if not manually edited
            $isManuallyEdited = $get("../../items.{$index}.subscription_manually_edited");

            if (!$isManuallyEdited && $productOccurrences[$productId] > 1) {
                if ($productYearCounters[$productId] < $productOccurrences[$productId]) {
                    // This is NOT the last occurrence, set to 12 months
                    $set("../../items.{$index}.subscription_period", 12);
                } else {
                    // This IS the last occurrence, use product's default
                    $set("../../items.{$index}.subscription_period", $product->subscription_period ?? 1);
                }
            }

            // Set the year for this item
            $year = $productYearCounters[$productId];
            $set("../../items.{$index}.year", "Year {$year}");
        }
    }

    public static function recalculateAllYearsFromParent(Forms\Get $get, Forms\Set $set): void
    {
        $items = $get('items') ?? [];
        $productYearCounters = [];
        $productOccurrences = [];

        // First pass: count total occurrences of each product
        foreach ($items as $item) {
            if (!empty($item['product_id'])) {
                $productId = $item['product_id'];
                $productOccurrences[$productId] = ($productOccurrences[$productId] ?? 0) + 1;
            }
        }

        // Second pass: process each item in order
        foreach ($items as $index => $item) {
            if (empty($item['product_id'])) {
                continue;
            }

            $product = Product::find($item['product_id']);
            if (!$product || $product->solution !== 'software') {
                $set("items.{$index}.year", null);
                continue;
            }

            $productId = $item['product_id'];

            // Initialize or increment counter for this product
            if (!isset($productYearCounters[$productId])) {
                $productYearCounters[$productId] = 1;
            } else {
                $productYearCounters[$productId]++;
            }

            // ✅ Only set subscription period if not manually edited
            $isManuallyEdited = $get("items.{$index}.subscription_manually_edited");

            if (!$isManuallyEdited && $productOccurrences[$productId] > 1) {
                if ($productYearCounters[$productId] < $productOccurrences[$productId]) {
                    // This is NOT the last occurrence, set to 12 months
                    $set("items.{$index}.subscription_period", 12);
                } else {
                    // This IS the last occurrence, use product's default
                    $set("items.{$index}.subscription_period", $product->subscription_period ?? 1);
                }
            }

            // Set the year for this item
            $year = $productYearCounters[$productId];
            $set("items.{$index}.year", "Year {$year}");
        }
    }
}
