<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductService;
use Filament\Forms;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rule;
use Filament\Forms\Components\Grid;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Illuminate\Support\HtmlString;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationIcon = 'heroicon-o-gift';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.resources.products.index');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                TextInput::make('code')
                                    ->required(fn (Page $livewire) => ($livewire instanceof CreateRecord))
                                    ->disabledOn('edit')
                                    ->rules([
                                        function ($record) {
                                            return [
                                                'required',
                                                Rule::unique('products', 'code')->ignore($record?->id),
                                            ];
                                        }
                                    ])
                                    ->validationMessages([
                                        'unique' => 'This product code already exists. Please use a different code.',
                                    ])
                                    ->helperText('Product codes must be unique across all products.'),

                                RichEditor::make('description')
                                    ->columnSpan(1),

                                Grid::make(2)
                                    ->schema([
                                        Grid::make(1)
                                            ->schema([
                                                Toggle::make('is_active')
                                                    ->label('Is Active?')
                                                    ->inline(false),

                                                Toggle::make('convert_pi')
                                                    ->label('Proforma Invoice')
                                                    ->inline(false)
                                                    ->default(false),

                                                Toggle::make('push_to_autocount')
                                                    ->label('Push Invoice')
                                                    ->inline(false)
                                                    ->default(false)
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                        if ($state) {
                                                            $set('push_so', false);
                                                        }
                                                    }),

                                                Toggle::make('push_so')
                                                    ->label('Push Sales Order')
                                                    ->inline(false)
                                                    ->default(false)
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                        if ($state) {
                                                            $set('push_to_autocount', false);
                                                        }
                                                    }),

                                                Toggle::make('push_sw')
                                                    ->label('Push Software')
                                                    ->inline(false)
                                                    ->default(false)
                                                    ->visible(fn (callable $get) => $get('solution') === 'software')
                                                    ->disabled(fn (callable $get) => $get('solution') !== 'software'),
                                            ])
                                            ->columnSpan(1),
                                        Grid::make(1)
                                            ->schema([
                                                Toggle::make('amount_editable')
                                                    ->label('Edit Amount')
                                                    ->inline(false)
                                                    ->default(true),

                                                Toggle::make('editable')
                                                    ->label('Edit Details')
                                                    ->inline(false)
                                                    ->default(true),

                                                Toggle::make('taxable')
                                                    ->label('Taxation')
                                                    ->inline(false),

                                                Toggle::make('minimum_price')
                                                    ->label('Minimum Price')
                                                    ->inline(false)
                                                    ->default(true),
                                            ])
                                            ->columnSpan(1),
                                    ]),
                            ])->columnSpan(1),

                        Grid::make(1)
                            ->schema([
                                Select::make('solution')
                                    ->placeholder('Select a solution')
                                    ->live()
                                    ->options([
                                        'software' => 'Software',
                                        'hardware' => 'Hardware',
                                        'hrdf' => 'HRDF',
                                        'other' => 'Other',
                                        'installation' => 'Installation',
                                        'door_access_package' => 'Door Access Package',
                                        'door_access_accesories' => 'Door Access Accesories',
                                    ]),

                                TextInput::make('unit_price')
                                    ->label('Cost (RM)'),

                                Select::make('is_commission')
                                    ->label('Commission')
                                    ->options([
                                        'yes' => 'Yes',
                                        'no' => 'No',
                                        'margin' => 'Margin',
                                    ])
                                    ->default('no')
                                    ->required(),

                                TextInput::make('sort_order')
                                    ->label('Sort Order')
                                    ->numeric()
                                    ->default(function ($record) {
                                        if ($record?->sort_order) return $record->sort_order;

                                        $solution = request()->input('data.solution') ?? $record?->solution;
                                        return Product::where('solution', $solution)->max('sort_order') + 1;
                                    })
                                    ->rules(function ($record) {
                                        return [
                                            Rule::unique('products', 'sort_order')
                                                ->ignore($record?->id)
                                                ->where(function ($query) use ($record) {
                                                    $solution = request()->input('data.solution') ?? $record?->solution;
                                                    $query->where('solution', $solution);
                                                }),
                                        ];
                                    })
                                    ->validationMessages([
                                        'unique' => 'This sort order is already in use for this solution type.',
                                    ]),

                                TextInput::make('subscription_period')
                                    ->label('Subscription Period (Months)')
                                    ->numeric()
                                    ->nullable(),

                                // ✅ Changed to multiple select for package groups
                                Select::make('package_group')
                                    ->label('Package Groups')
                                    ->placeholder('Select package groups')
                                    ->multiple() // ✅ Allow multiple selection
                                    ->options([
                                        'Package 1' => 'Package 1 - Standard Package',
                                        'Package 2' => 'Package 2 - 1 Year Subscription',
                                        'Package 3' => 'Package 3 - 2 Year Subscription',
                                        'Package 4' => 'Package 4 - 3 Year Subscription',
                                        'Package 5' => 'Package 5 - 4 Year Subscription',
                                        'Package 6' => 'Package 6 - 5 Year Subscription',
                                        'Other' => 'Other',
                                    ])
                                    ->searchable()
                                    ->nullable(),

                                TextInput::make('package_sort_order')
                                    ->label('Package Sort Order')
                                    ->numeric()
                                    ->nullable(),

                                Select::make('tariff_code')
                                    ->label('Tariff Code')
                                    ->placeholder('Select a tariff code')
                                    ->options([
                                        'N/A' => 'Not Available - N/A',
                                        '9907061674' => 'Consultant - 9907061674',
                                        '9907071675' => 'Management Services - 9907071675',
                                        '9907071685' => 'Training - 9907071685',
                                        '9907101676' => 'IT Services - 9907101676',
                                        '9907131694' => 'Maintenance and Repair - 9907131694',
                                        '9909141687' => 'Imported Services - 9909141687',
                                    ])
                                    ->searchable()
                                    ->nullable(),
                            ])->columnSpan(1),
                    ]),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->recordUrl(false)
            ->paginationPageOptions(['all'])
            ->columns([
                TextColumn::make('sort_order')->label('Order')->sortable(),
                TextColumn::make('code')->width(100),
                TextColumn::make('package_sort_order')
                    ->label('Pkg Order')
                    ->sortable()
                    ->visible(fn ($record) => $record && !empty($record->package_group))
                    ->width(80),
                // ✅ Updated to show multiple package groups
                TextColumn::make('package_group')
                    ->label('Package Groups')
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return '-';
                        }

                        // If it's a JSON string, decode it
                        if (is_string($state)) {
                            $state = json_decode($state, true) ?? [$state];
                        }

                        // If it's not an array, make it one
                        if (!is_array($state)) {
                            $state = [$state];
                        }

                        $packageNames = [
                            'Package 1' => 'Pkg 1',
                            'Package 2' => 'Pkg 2',
                            'Package 3' => 'Pkg 3',
                            'Package 4' => 'Pkg 4',
                            'Package 5' => 'Pkg 5',
                            'Package 6' => 'Pkg 6',
                            'Other' => 'Other',
                        ];

                        return collect($state)
                            ->map(fn ($pkg) => $packageNames[$pkg] ?? $pkg)
                            ->implode(', ');
                    })
                    ->badge()
                    ->separator(',')
                    ->width(150)
                    ->toggleable(),
                TextColumn::make('solution')
                    ->width(100)
                    ->formatStateUsing(function (?string $state): string {
                        $solutionMap = [
                            'software' => 'SOFTWARE',
                            'hardware' => 'HARDWARE',
                            'hrdf' => 'HRDF',
                            'other' => 'OTHER',
                            'installation' => 'INSTALLATION',
                            'door_access_package' => 'DOOR ACCESS PACKAGE',
                            'door_access_accesories' => 'DOOR ACCESS ACCESSORIES',
                        ];

                        return $solutionMap[$state] ?? strtoupper($state ?? '');
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('description')
                    ->html()
                    ->width(500)
                    ->limit(50)
                    ->wrap()
                    ->alignCenter()
                    ->tooltip('Click to view full description')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        if ($state) {
                            return 'View';
                        }
                        return 'No description';
                    })
                    ->action(
                        Action::make('viewDescription')
                            ->label('View Full Description')
                            ->modalHeading(fn ($record) => 'Product Description: ' . $record->code)
                            ->modalContent(function ($record) {
                                $description = $record->description;

                                if ($description) {
                                    $description = html_entity_decode($description);
                                    if (!str_contains($description, '<ul>') && str_contains($description, '<li>')) {
                                        $description = '<ul style="list-style-type: disc; padding-left: 20px;">' . $description . '</ul>';
                                    } else if (str_contains($description, '<ul>')) {
                                        $description = str_replace('<ul>', '<ul style="list-style-type: disc; padding-left: 20px;">', $description);
                                    }
                                    $description = str_replace('<li>', '<li style="display: list-item;">', $description);

                                    return new \Illuminate\Support\HtmlString(
                                        '<div style="padding: 1rem; line-height: 1.6; color: #374151;">' .
                                        $description .
                                        '</div>'
                                    );
                                }

                                return new \Illuminate\Support\HtmlString('<div style="padding: 1rem;">No description available.</div>');
                            })
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Close')
                            ->modalWidth(MaxWidth::Large)
                            ->icon('heroicon-o-eye')
                    )
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('unit_price')->label('RM')->width(100),
                TextColumn::make('subscription_period')->label('Months')->width(150),
                TextColumn::make('tariff_code')->label('Tariff Code')->width(150)->toggleable(isToggledHiddenByDefault: true),

                ToggleColumn::make('is_active')->label('Active')->width(100)->disabled(fn() => auth()->user()->role_id != 3),

                ToggleColumn::make('convert_pi')
                    ->label(new HtmlString('Push<br>to PI'))
                    ->width(100)
                    ->disabled(fn() => auth()->user()->role_id != 3),

                ToggleColumn::make('push_to_autocount')
                    ->label(new HtmlString('Push<br>Invoice'))
                    ->width(100)
                    ->disabled(fn() => auth()->user()->role_id != 3)
                    ->afterStateUpdated(function ($record, $state) {
                        if ($state) {
                            $record->update(['push_so' => false]);
                        }
                    }),

                ToggleColumn::make('push_so')
                    ->label(new HtmlString('Push<br>S/Order'))
                    ->width(100)
                    ->disabled(fn() => auth()->user()->role_id != 3)
                    ->afterStateUpdated(function ($record, $state) {
                        if ($state) {
                            $record->update(['push_to_autocount' => false]);
                        }
                    }),

                ToggleColumn::make('push_sw')
                    ->label(new HtmlString('Push<br>S/Ware'))
                    ->width(100)
                    ->disabled(fn() => auth()->user()->role_id != 3),

                TextColumn::make('is_commission')
                    ->label('Comm')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        return match($state) {
                            'yes' => 'Yes',
                            'no' => 'No',
                            'margin' => 'Margin',
                            default => 'No'
                        };
                    })
                    ->color(function ($state) {
                        return match($state) {
                            'yes' => 'success',
                            'no' => 'danger',
                            'margin' => 'primary',
                            default => 'gray'
                        };
                    })
                    ->width(100),

                ToggleColumn::make('amount_editable')
                    ->label(new HtmlString('Edit<br>Amount'))
                    ->width(100)
                    ->disabled(fn() => auth()->user()->role_id != 3),

                ToggleColumn::make('editable')->label(new HtmlString('Edit<br>Details'))->width(100)->disabled(fn() => auth()->user()->role_id != 3),

                ToggleColumn::make('taxable')->label('Tax')->width(100)->disabled(fn() => auth()->user()->role_id != 3),
                ToggleColumn::make('minimum_price')->label(new HtmlString('Minimum<br>Price'))->width(100)->disabled(fn() => auth()->user()->role_id != 3),
            ])
            ->bulkActions([
                    Tables\Actions\BulkAction::make('batch_update')
                        ->label('Batch Update')
                        ->icon('heroicon-o-pencil-square')
                        ->color('primary')
                        ->form([
                            // Add Tariff Code section at the top
                            Forms\Components\Section::make('Tariff Code')
                                ->schema([
                                    Select::make('tariff_code')
                                        ->label('Tariff Code')
                                        ->placeholder('Select a tariff code')
                                        ->options([
                                            '9907061674' => 'Consultant - 9907061674',
                                            '9907071675' => 'Management Services - 9907071675',
                                            '9907071685' => 'Training - 9907071685',
                                            '9907101676' => 'IT Services - 9907101676',
                                            '9907131694' => 'Maintenance and Repair - 9907131694',
                                            '9909141687' => 'Imported Services - 9909141687',
                                        ])
                                        ->searchable()
                                        ->nullable(),
                                ]),

                            Forms\Components\Section::make('Status & Conversion')
                                ->schema([
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\Toggle::make('is_active')
                                                ->label('Active')
                                                ->inline(false),
                                            Forms\Components\Toggle::make('convert_pi')
                                                ->label('Proforma Invoice')
                                                ->inline(false),
                                            Forms\Components\Toggle::make('taxable')
                                                ->label('Taxation')
                                                ->inline(false),
                                        ]),
                                ]),

                            Forms\Components\Section::make('AutoCount Integration')
                                ->schema([
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\Toggle::make('push_to_autocount')
                                                ->label('Push Invoice')
                                                ->inline(false)
                                                ->live()
                                                ->afterStateUpdated(function ($state, callable $set) {
                                                    if ($state) {
                                                        $set('push_so', false);
                                                    }
                                                }),
                                            Forms\Components\Toggle::make('push_so')
                                                ->label('Push Sales Order')
                                                ->inline(false)
                                                ->live()
                                                ->afterStateUpdated(function ($state, callable $set) {
                                                    if ($state) {
                                                        $set('push_to_autocount', false);
                                                    }
                                                }),
                                            Forms\Components\Toggle::make('push_sw')
                                                ->label('Push Software')
                                                ->inline(false),
                                        ]),
                                ]),

                            Forms\Components\Section::make('Editing Permissions')
                                ->schema([
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\Toggle::make('amount_editable')
                                                ->label('Edit Amount')
                                                ->inline(false),
                                            Forms\Components\Toggle::make('editable')
                                                ->label('Edit Details')
                                                ->inline(false),
                                            Forms\Components\Toggle::make('minimum_price')
                                                ->label('Minimum Price')
                                                ->inline(false),
                                        ]),
                                ]),

                            Forms\Components\Section::make('Update Options')
                                ->schema([
                                    Forms\Components\Fieldset::make('Select fields to update')
                                        ->schema([
                                            Forms\Components\Grid::make(3)
                                                ->schema([
                                                    Forms\Components\Checkbox::make('update_tariff_code')
                                                        ->label('Update Tariff Code'),
                                                    Forms\Components\Checkbox::make('update_is_active')
                                                        ->label('Update Active'),
                                                    Forms\Components\Checkbox::make('update_convert_pi')
                                                        ->label('Update Proforma Invoice'),
                                                    Forms\Components\Checkbox::make('update_taxable')
                                                        ->label('Update Taxation'),
                                                    Forms\Components\Checkbox::make('update_push_to_autocount')
                                                        ->label('Update Push Invoice'),
                                                    Forms\Components\Checkbox::make('update_push_so')
                                                        ->label('Update Push Sales Order'),
                                                    Forms\Components\Checkbox::make('update_push_sw')
                                                        ->label('Update Push Software'),
                                                    Forms\Components\Checkbox::make('update_amount_editable')
                                                        ->label('Update Edit Amount'),
                                                    Forms\Components\Checkbox::make('update_editable')
                                                        ->label('Update Edit Details'),
                                                    Forms\Components\Checkbox::make('update_minimum_price')
                                                        ->label('Update Minimum Price'),
                                                ]),
                                        ])
                                        ->columns(1),
                                ]),
                        ])
                        ->action(function (array $data, \Illuminate\Database\Eloquent\Collection $records) {
                            $updateData = [];
                            $fieldsToUpdate = [];

                            // Add tariff code handling
                            if ($data['update_tariff_code'] ?? false) {
                                $updateData['tariff_code'] = $data['tariff_code'] ?? null;
                                $fieldsToUpdate[] = 'Tariff Code';
                            }

                            // Build update data based on selected checkboxes
                            if ($data['update_is_active'] ?? false) {
                                $updateData['is_active'] = $data['is_active'] ?? false;
                                $fieldsToUpdate[] = 'Active';
                            }

                            if ($data['update_convert_pi'] ?? false) {
                                $updateData['convert_pi'] = $data['convert_pi'] ?? false;
                                $fieldsToUpdate[] = 'Convert PI';
                            }

                            if ($data['update_taxable'] ?? false) {
                                $updateData['taxable'] = $data['taxable'] ?? false;
                                $fieldsToUpdate[] = 'Tax';
                            }

                            if ($data['update_push_to_autocount'] ?? false) {
                                $updateData['push_to_autocount'] = $data['push_to_autocount'] ?? false;
                                $fieldsToUpdate[] = 'Push Invoice';

                                // If pushing invoice is enabled, disable push SO
                                if ($data['push_to_autocount'] ?? false) {
                                    $updateData['push_so'] = false;
                                }
                            }

                            if ($data['update_push_so'] ?? false) {
                                $updateData['push_so'] = $data['push_so'] ?? false;
                                $fieldsToUpdate[] = 'Push S/Order';

                                // If pushing SO is enabled, disable push invoice
                                if ($data['push_so'] ?? false) {
                                    $updateData['push_to_autocount'] = false;
                                }
                            }

                            if ($data['update_push_sw'] ?? false) {
                                $updateData['push_sw'] = $data['push_sw'] ?? false;
                                $fieldsToUpdate[] = 'Push S/Ware';
                            }

                            if ($data['update_amount_editable'] ?? false) {
                                $updateData['amount_editable'] = $data['amount_editable'] ?? false;
                                $fieldsToUpdate[] = 'Edit Amount';
                            }

                            if ($data['update_editable'] ?? false) {
                                $updateData['editable'] = $data['editable'] ?? false;
                                $fieldsToUpdate[] = 'Edit Details';
                            }

                            if ($data['update_minimum_price'] ?? false) {
                                $updateData['minimum_price'] = $data['minimum_price'] ?? false;
                                $fieldsToUpdate[] = 'Min Price';
                            }

                            if (empty($updateData)) {
                                \Filament\Notifications\Notification::make()
                                    ->warning()
                                    ->title('No Fields Selected')
                                    ->body('Please select at least one field to update.')
                                    ->send();
                                return;
                            }

                            // Update all selected records
                            $updatedCount = 0;
                            foreach ($records as $record) {
                                $record->update($updateData);
                                $updatedCount++;
                            }

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Batch Update Successful')
                                ->body("Updated {$updatedCount} products. Fields updated: " . implode(', ', $fieldsToUpdate))
                                ->send();
                        })
                        ->modalSubmitActionLabel('Update Products')
                        ->modalWidth(\Filament\Support\Enums\MaxWidth::FourExtraLarge)
                        ->hidden(fn(): bool => auth()->user()->role_id != 3),
                    // You can keep existing delete action if needed
                    // Tables\Actions\DeleteBulkAction::make()
                    //     ->hidden(fn(): bool => auth()->user()->role_id != 3),
            ])
            ->filters([
                // ✅ Updated package group filter
                Filter::make('package_group')
                    ->form([
                        Select::make('package_group')
                            ->label('Package Groups')
                            ->multiple()
                            ->options([
                                'Package 1' => 'Package 1 - Standard Package',
                                'Package 2' => 'Package 2 - 1 Year Subscription',
                                'Package 3' => 'Package 3 - 2 Year Subscription',
                                'Package 4' => 'Package 4 - 3 Year Subscription',
                                'Package 5' => 'Package 5 - 4 Year Subscription',
                                'Package 6' => 'Package 6 - 5 Year Subscription',
                                'Other' => 'Other',
                            ])
                            ->searchable()
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            !empty($data['package_group']),
                            function (Builder $query) use ($data) {
                                foreach ($data['package_group'] as $package) {
                                    $query->orWhereJsonContains('package_group', $package);
                                }
                                return $query;
                            }
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['package_group'])) {
                            return null;
                        }

                        $packageLabels = [
                            'Package 1' => 'Package 1',
                            'Package 2' => 'Package 2',
                            'Package 3' => 'Package 3',
                            'Package 4' => 'Package 4',
                            'Package 5' => 'Package 5',
                            'Package 6' => 'Package 6',
                            'Other' => 'Other',
                        ];

                        $selectedLabels = collect($data['package_group'])
                            ->map(fn ($package) => $packageLabels[$package] ?? $package)
                            ->implode(', ');

                        return "Package: {$selectedLabels}";
                    }),

                // ...existing filters remain the same...
                Filter::make('solution')
                    ->form([
                        Select::make('solution')
                            ->label('Solution')
                            ->multiple()
                            ->options([
                                'software' => 'Software',
                                'hardware' => 'Hardware',
                                'hrdf' => 'HRDF',
                                'other' => 'Other',
                                'installation' => 'Installation',
                                'door_access_package' => 'Door Access Package',
                                'door_access_accesories' => 'Door Access Accesories',
                            ])
                            ->searchable()
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            !empty($data['solution']),
                            fn (Builder $query): Builder => $query->whereIn('solution', $data['solution'])
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['solution'])) {
                            return null;
                        }

                        $solutionLabels = [
                            'software' => 'Software',
                            'hardware' => 'Hardware',
                            'hrdf' => 'HRDF',
                            'other' => 'Other',
                            'installation' => 'Installation',
                            'door_access_package' => 'Door Access Package',
                            'door_access_accesories' => 'Door Access Accesories',
                        ];

                        $selectedLabels = collect($data['solution'])
                            ->map(fn ($solution) => $solutionLabels[$solution] ?? $solution)
                            ->implode(', ');

                        return "Solution: {$selectedLabels}";
                    }),

                Filter::make('is_commission')
                    ->form([
                        Select::make('is_commission')
                            ->label('Commission')
                            ->multiple()
                            ->options([
                                'yes' => 'Yes',
                                'no' => 'No',
                                'margin' => 'Margin',
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            !empty($data['is_commission']),
                            fn (Builder $query): Builder => $query->whereIn('is_commission', $data['is_commission'])
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['is_commission'])) {
                            return null;
                        }

                        $commissionLabels = [
                            'yes' => 'Yes',
                            'no' => 'No',
                            'margin' => 'Margin',
                        ];

                        $selectedLabels = collect($data['is_commission'])
                            ->map(fn ($commission) => $commissionLabels[$commission] ?? $commission)
                            ->implode(', ');

                        return "Commission: {$selectedLabels}";
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Edit Product')
                    ->closeModalByClickingAway(false)
                    ->hidden(fn(): bool => !auth()->user()->hasRouteAccess('filament.admin.resources.products.edit')),
            ]);
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.resources.products.create');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageProducts::route('/'),
        ];
    }
}
