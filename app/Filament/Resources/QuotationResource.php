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
                Section::make('Customer Information')
                    ->schema([
                        Select::make('lead_id')
                            ->label('Company Name')
                            ->searchable()
                            ->options(function () {
                                $query = Lead::with('companyDetail')
                                    ->when(auth()->user()->role_id == 2, function ($q) {
                                        $q->where('salesperson', auth()->id()); // Filter leads by salesperson if role_id == 2
                                    })
                                    ->get();

                                return $query
                                    ->filter(fn($lead) => $lead->companyDetail && $lead->companyDetail->company_name)
                                    ->mapWithKeys(function ($lead) {
                                        return [$lead->id => $lead->companyDetail->company_name];
                                    })
                                    ->toArray();
                            })
                            ->default(
                                fn() => request()->has('lead_id')
                                    ? Encryptor::decrypt(request()->query('lead_id'))
                                    : null
                            )
                            ->preload()
                            ->required()
                            ->live(debounce: 550),
                        Flatpickr::make('quotation_date')
                            ->label('Date')
                            ->dateFormat('j M Y')
                            ->default(now()->format('j M Y'))
                            ->required()
                            ->clickOpens(),
                        Select::make('sales_type')
                            ->label('Sales Type')
                            ->placeholder('Select a sales type')
                            ->options([
                                'NEW SALES' => 'NEW SALES',
                                'RENEWAL SALES' => 'RENEWAL SALES',
                            ])
                            ->default('NEW SALES')
                            ->required()
                            ->disabled(fn () => auth()->user()?->role_id == 2),
                        Select::make('currency')
                            ->placeholder('Select a currency')
                            ->options([
                                'MYR' => 'Malaysian Ringgit (MYR)',
                                'USD' => 'U.S. Dollar',
                            ])
                            ->default('MYR')
                            ->required()
                            ->live()  // Make sure it's marked as live to react immediately
                            ->afterStateUpdated(function (string $state, Forms\Get $get, Forms\Set $set) {
                                // When USD is selected, set SST rate to 0
                                if ($state === 'USD') {
                                    $set('sst_rate', 0);
                                } else {
                                    // Reset to default SST rate from settings when MYR is selected
                                    $set('sst_rate', Setting::where('name', 'sst_rate')->first()->value);
                                }

                                // Recalculate all totals after changing the SST rate
                                self::recalculateAllRowsFromParent($get, $set);
                            }),

                        Select::make('quotation_type')
                            ->label('Type')
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

                                    $items = collect([
                                        ['product_id' => 16],
                                        ['product_id' => 17],
                                        ['product_id' => 18],
                                    ])->mapWithKeys(fn ($item) => [
                                        (string) \Illuminate\Support\Str::uuid() => $item
                                    ])->toArray();

                                    $set('items', $items);

                                    // Trigger recalculation if needed
                                    QuotationResource::recalculateAllRowsFromParent($get, $set);
                                }
                            }),
                        Select::make('package_group')
                            ->label('Package')
                            ->placeholder('Select a package')
                            ->options(
                                \App\Models\Product::whereNotNull('package_group')
                                    ->distinct()
                                    ->pluck('package_group', 'package_group')
                                    ->toArray()
                            )
                            ->searchable()
                            ->live()
                            ->visible(fn(Forms\Get $get) => $get('quotation_type') === 'product')
                            ->afterStateUpdated(function (?string $state, Forms\Get $get, Forms\Set $set) {
                                if ($state) {
                                    $products = \App\Models\Product::where('package_group', $state)
                                        ->orderBy('package_sort_order')
                                        ->get();

                                    $mappedItems = $products->map(function ($product) use ($get) {
                                        return [
                                            'product_id' => $product->id,
                                            'quantity' => in_array($product->solution, ['software', 'hardware'])
                                                ? ($product->quantity ?? 1)
                                                : ($get('num_of_participant') ?? 1),
                                            'unit_price' => $product->unit_price,
                                            'subscription_period' => $product->subscription_period,
                                            'description' => $product->description,
                                        ];
                                    });

                                    $finalItems = collect($mappedItems)->mapWithKeys(fn($item) => [
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
                            ->required()
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
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
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
                            ->live(onBlur: true)
                            ->afterStateUpdated(function(?string $state, Forms\Get $get, Forms\Set $set) {
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
                Section::make('Summary')
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
                                //info("Default: {$defaultValue}");
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
                    ])
                    ->columnSpan(1),
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
                                    ->options(function (Forms\Get $get) {
                                        $quotationType = $get('../../quotation_type');

                                        $query = \App\Models\Product::query()
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
                                                return [
                                                    ucfirst($solution) => $group->pluck('code', 'id'),
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
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (?string $state, Forms\Get $get, Forms\Set $set) {
                                        if ($state) {
                                            $product = Product::find($state); // 🛠 Fetch Product from DB

                                            if ($product) {
                                                $set('unit_price', $product->unit_price); // Set unit price from DB
                                                $set('description', $product->description); // Set description from DB

                                                if ($product->solution === 'software') {
                                                    $set('subscription_period', $product->subscription_period); // ✨ Set subscription period from DB
                                                } else {
                                                    $set('subscription_period', null); // Not needed for hardware/hrdf
                                                }
                                            }

                                            self::recalculateAllRows($get, $set, 'product_id', $state);
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
                                    ->afterStateUpdated(
                                        function(?string $state, Forms\Get $get, Forms\Set $set) {
                                            //self::updateFields('quantity', $get, $set, $state);
                                            self::recalculateAllRows($get, $set);
                                    }),
                                TextInput::make('subscription_period')
                                    ->label('Subscription Period')
                                    ->numeric()
                                    ->live(debounce:500)
                                    //->default(fn(Forms\Get $get) => $get('../../base_subscription'))
                                    ->afterStateUpdated(function(?string $state, Forms\Get $get, Forms\Set $set) {
                                        // self::updateFields('subscription_period', $get, $set, $state);
                                        // self::updateSubscriptionPeriodInAllRows($get, $set, $state);
                                        self::recalculateAllRows($get, $set);
                                    })
                                    // ->live(debounce:500)
                                    // ->readOnly()
                                    ->visible(function(Forms\Get $get)  {
                                        $productId = $get('product_id');
                                        if ($productId != null) {
                                            $product = Product::find($productId);
                                            if ($get('../../quotation_type') == 'product' && $product->solution == 'software') {
                                                return true;
                                            }
                                        }
                                        return false;
                                    }),
                                TextInput::make('unit_price')
                                    ->label('Unit Price')
                                    ->numeric()
                                    ->columnSpan([
                                        'md' => 1,
                                    ])
                                    ->live(debounce:500)
                                    ->afterStateUpdated(function(?string $state, Forms\Get $get, Forms\Set $set) {
                                        // self::updateFields('unit_price', $get, $set, $state);
                                        //$set('unit_price', $state);
                                        self::recalculateAllRows($get, $set);
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
                                    ->afterStateUpdated(fn(?string $state, Forms\Get $get, Forms\Set $set) => self::recalculateAllRows($get, $set))
                            ])
                            ->deleteAction(fn(Actions\Action $action) => $action->requiresConfirmation())
                            ->afterStateUpdated(fn(Forms\Get $get, Forms\Set $set) => self::updateFields(null, $get, $set, null))
                            ->afterStateHydrated(fn(Forms\Get $get, Forms\Set $set) => self::updateFields(null, $get, $set, null))
                            ->defaultItems(1)
                            ->columns(7)
                            ->collapsible()
                            ->reorderable(false)
                            // ->reorderable()
                            // ->reorderableWithDragAndDrop()
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
                            ->addActionLabel('Add Quotation Item')
                            ->orderColumn('sort_order')
                    ])
            ])
            ->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('10s')
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
            ->modifyQueryUsing(function (Quotation $quotation) {
                $currentUser = auth('web')->user();

                if ($currentUser->role_id == 3) {
                    // If role_id is 3, return all quotations ordered by ID
                    return $quotation->orderBy('id', 'desc');
                }else if ($currentUser->role_id == 2) {
                    // Fetch the quotations related to the lead where the current user is either the lead owner or the salesperson
                    return $quotation->whereHas('lead', function ($query) use ($currentUser) {
                        $query->where('lead_owner', $currentUser->name); // Lead owner (by name)
                    })->orWhere('sales_person_id', $currentUser->id) // Salesperson (by ID)
                    ->orderBy('id', 'desc');
                }else{
                    return $quotation->whereHas('lead', function ($query) {
                        $query->where('lead_owner', auth()->user()->name);
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
                    ->label('Lead')
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = Str::upper($state ?? 'N/A'); // Convert to UPPERCASE
                        $shortened = Str::limit($fullName, 30, '...'); // Limit for display
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
                Filter::make('company_name')
                    ->form([
                        TextInput::make('company_name')
                            ->label('Company Name')
                            // ->hiddenLabel()
                            ->placeholder('Enter company name'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (!empty($data['company_name'])) {
                            $query->whereHas('lead.companyDetail', function ($query) use ($data) {
                                $query->where('company_name', 'like', '%' . $data['company_name'] . '%');
                            });
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        return isset($data['company_name'])
                            ? 'Company Name: ' . $data['company_name']
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
                    ),
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
                        ->action(fn(Quotation $quotation, QuotationService $quotationService) => $quotationService->duplicate($quotation)),
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
                        ->url(fn(Quotation $quotation) => route('pdf.print-quotation-v2', $quotation))
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('Accept')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->form([
                            FileUpload::make('attachment')
                                ->label('Upload Confirmation Order Document')
                                ->acceptedFileTypes(['application/pdf','image/jpg','image/jpeg'])
                                ->uploadingMessage('Uploading document...')
                                ->previewable(false)
                                ->preserveFilenames()
                                ->disk('public')
                                ->directory('confirmation_orders')
                        ])
                        ->action(
                            function (Quotation $quotation, QuotationService $quotationService, array $data) {
                                $quotation->confirmation_order_document = $data['attachment'];
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
                                    ->sendToDatabase($notifyUsers)
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
                        // ->url(fn(Quotation $quotation) => route('pdf.print-proforma-invoice', $quotation))
                        // ->openUrlInNewTab()
                        ->closeModalByClickingAway(false)
                        ->modalWidth(MaxWidth::Medium)
                        ->visible(function(Quotation $quotation) {
                            // First, check if the quotation is not already accepted and lead status is closed
                            if ($quotation->status === QuotationStatusEnum::accepted ||
                                $quotation->lead?->lead_status !== 'Closed') {
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
                                // 'reg_no_old', //Remove Old Register Number
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

        foreach ($items as $index => $item) {
            // $product = null;

            // if (!empty($item['product_id'])) {
            //     $product = Product::find($item['product_id']);
            // }

            // // Handle quantity based on type
            // if ($product?->solution === 'hrdf') {
            //     $itemQuantity = $get("../../items.{$index}.quantity") ?? 0;
            //     $set("../../items.{$index}.quantity", $itemQuantity);
            // }

            // if ($product?->solution === 'software' || $product?->solution === 'hardware') {
            //     $set("../../items.{$index}.quantity", $get("../../items.{$index}.quantity") ?? $product?->quantity);
            //     if ((int) $get("../../items.{$index}.subscription_period") === 0) {
            //         $set("../../items.{$index}.subscription_period", $get("../../subscription_period"));
            //     }
            // }

            // $quantity = (float) $get("../../items.{$index}.quantity") ?: (float) $get("../../headcount");
            // $set("../../items.{$index}.quantity", $quantity);

            // $subscriptionPeriod = $get("../../items.{$index}.subscription_period");
            // $unitPrice = isset($item['unit_price']) ? (float) $item['unit_price'] : 0;

            // // Auto-fill unit price if zero
            // if ($unitPrice === 0.00 && $product?->unit_price) {
            //     $unitPrice = $product->unit_price;
            // }
            // $set("../../items.{$index}.unit_price", $unitPrice);

            // // Total before tax
            // $totalBeforeTax = $quantity * $unitPrice;
            // if ($product?->solution === 'software') {
            //     $totalBeforeTax = $quantity * $subscriptionPeriod * $unitPrice;
            // }
            $product = null;

            if (!empty($item['product_id'])) {
                $product = Product::find($item['product_id']);
            }

            // Handle quantity based on type
            if ($product?->solution === 'hrdf') {
                $itemQuantity = $get("../../items.{$index}.quantity") ?? 0;
                $set("../../items.{$index}.quantity", $itemQuantity);
            }

            if ($product?->solution === 'software' || $product?->solution === 'hardware') {
                $set("../../items.{$index}.quantity", $get("../../items.{$index}.quantity") ?? $product?->quantity);
                if ((int) $get("../../items.{$index}.subscription_period") === 0) {
                    $set("../../items.{$index}.subscription_period", $get("../../subscription_period"));
                }
            }

            $quantity = (float) $get("../../items.{$index}.quantity") ?: (float) $get("../../headcount");
            $set("../../items.{$index}.quantity", $quantity);

            $subscriptionPeriod = $get("../../items.{$index}.subscription_period");
            $unitPrice = isset($item['unit_price']) ? (float) $item['unit_price'] : 0;

            // Auto-fill unit price if zero
            if ($unitPrice === 0.00 && $product?->unit_price) {
                $unitPrice = $product->unit_price;
            }
            $set("../../items.{$index}.unit_price", $unitPrice);

            // Total before tax
            $totalBeforeTax = $quantity * $unitPrice;
            if ($product?->solution === 'software') {
                $totalBeforeTax = $quantity * $subscriptionPeriod * $unitPrice;
            }

            $subtotal += $totalBeforeTax;

            // Tax
            $taxAmount = 0;
            if ($currency === 'MYR') {
                // Determine if this item should be taxed
                $shouldTax = match ($taxationCategory) {
                    'default' => $product?->taxable,
                    'all_taxable' => true,
                    'all_non_taxable' => false,
                    default => $product?->taxable,
                };

                if ($shouldTax) {
                    $sstRate = $get('../../sst_rate');
                    $taxAmount = $totalBeforeTax * ($sstRate / 100);
                    $totalTax += $taxAmount;
                }
            }

            // Preserve manually edited descriptions
            $currentDescription = $get("../../items.{$index}.description") ?? '';
            // Only set product description when:
            // 1. Current description is empty/blank, OR
            // 2. This specific item's product was just changed (field='product_id' AND the index matches)
            if (blank($currentDescription) || ($field === 'product_id' && $state && $index === $get('../../_repeater_index'))) {
                $set("../../items.{$index}.description", $product?->description);
            }

            $totalAfterTax = $totalBeforeTax + $taxAmount;
            $grandTotal += $totalAfterTax;

            // Format and set totals
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
        $items = $get('items');
        $taxationCategory = $get('taxation_category');
        $currency = $get('currency');

        $subtotal = 0;
        $grandTotal = 0;
        $totalTax = 0;

        foreach ($items as $index => $item) {
            if (array_key_exists('product_id',$item)) {
                $product_id = $item['product_id'];
                $product = Product::find($product_id);
            }

            $set("items.{$index}.quantity",$get("num_of_participant") ?? 0);
            if ($product?->solution == 'software' || $product?->solution == 'hardware') {
                $set("items.{$index}.quantity",$get("items.{$index}.quantity"));
                if (!isset($item['subscription_period']) || $item['subscription_period'] == 0) {
                    $set("items.{$index}.subscription_period", $product?->subscription_period ?? 1);
                }
            }

            $quantity = $get("items.{$index}.quantity");
            $subscription_period =  $get("items.{$index}.subscription_period");
            // $subscription_period = $get("base_subscription");
            $unit_price = 0;
            if (array_key_exists('unit_price', $item)) {
                $unit_price = $item['unit_price'];
                if ($unit_price == 0.00) {
                    $unit_price = $product?->unit_price;
                }
            }

            $set("items.{$index}.unit_price",$unit_price);
            //unit_price = $get("items.{$index}.unit_price");

            // Calculate total before tax
            $total_before_tax = (int) $quantity * (float) $unit_price;
            if ($product && $product->solution == 'software') {
                /**
                 * include subscription period in calculation for software
                 */
                $total_before_tax = (int) $quantity * (int) $subscription_period * (float) $unit_price;
            }

            $subtotal += $total_before_tax;
            // Calculate taxation amount
            $taxation_amount = 0;
            if ($currency === 'MYR') {
                // Determine if this item should be taxed
                $shouldTax = match ($taxationCategory) {
                    'default' => $product?->taxable,
                    'all_taxable' => true,
                    'all_non_taxable' => false,
                    default => $product?->taxable,
                };

                if ($shouldTax) {
                    $sstRate = $get('sst_rate');
                    $taxation_amount = $total_before_tax * ($sstRate / 100);
                    $totalTax += $taxation_amount;
                }
            }

            $currentDescription = $get("items.{$index}.description") ?? '';

            // Only set product description when:
            // 1. Current description is empty/blank, OR
            // 2. This is a newly selected product (check if this is the item being modified)
            if (blank($currentDescription) || ($index === $get('_repeater_index') && $product)) {
                $set("items.{$index}.description", $product?->description);
            }

            // Calculate total after tax
            $total_after_tax = $total_before_tax + $taxation_amount;
            $grandTotal += $total_after_tax;
            // Update the form values
            $set("items.{$index}.unit_price", number_format($unit_price, 2, '.', ''));
            $set("items.{$index}.total_before_tax", number_format($total_before_tax, 2, '.', ''));
            $set("items.{$index}.taxation", number_format($taxation_amount, 2, '.', ''));
            $set("items.{$index}.total_after_tax", number_format($total_after_tax, 2, '.', ''));
        }

        /**
         * Update summary
         */
        $set('sub_total', number_format($subtotal, 2, '.', ''));
        $set('tax_amount', number_format($totalTax, 2, '.', ''));
        $set('total', number_format($grandTotal, 2, '.', ''));
    }
}
