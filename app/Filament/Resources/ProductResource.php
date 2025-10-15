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

    // public static function canAccess(): bool
    // {
    //     return auth()->user()->role_id == 3 || in_array(auth()->id(), [4, 5]);
    // }

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
                                                    ->label('Convert PI')
                                                    ->inline(false)
                                                    ->default(false),

                                                Toggle::make('push_to_autocount')
                                                    ->label('Push Invoice')
                                                    ->inline(false)
                                                    ->default(false)
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                        // If Push Invoice is turned ON, turn OFF Push SO
                                                        if ($state) {
                                                            $set('push_so', false);
                                                        }
                                                    }),

                                                Toggle::make('push_so')
                                                    ->label('Push S/Order')
                                                    ->inline(false)
                                                    ->default(false)
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                        // If Push SO is turned ON, turn OFF Push Invoice
                                                        if ($state) {
                                                            $set('push_to_autocount', false);
                                                        }
                                                    }),

                                                Toggle::make('push_sw')
                                                    ->label('Push S/Ware')
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
                                                    ->label('Edit Description')
                                                    ->inline(false)
                                                    ->default(true),

                                                Toggle::make('taxable')
                                                    ->label('Tax')
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
                                    ->live() // Make it reactive for other fields
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
                                    ->label('Comm')
                                    ->options([
                                        'yes' => 'Yes',
                                        'no' => 'No',
                                        'margin' => 'Margin',
                                    ])
                                    ->default('no')
                                    ->required()
                                    ->helperText('Select the commission type for this product.'),

                                TextInput::make('sort_order')
                                    ->label('Sort Order')
                                    ->numeric()
                                    ->default(function ($record) {
                                        // If editing, use existing value
                                        if ($record?->sort_order) return $record->sort_order;

                                        // If creating, return max sort_order within the same solution + 1
                                        $solution = request()->input('data.solution') ?? $record?->solution;
                                        return Product::where('solution', $solution)->max('sort_order') + 1;
                                    })
                                    ->helperText('Lower numbers appear first in dropdowns.')
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
                                    ->nullable()
                                    ->helperText('Enter the subscription period in months, if applicable.'),

                                Select::make('package_group')
                                    ->label('Package Group')
                                    ->placeholder('Select a group')
                                    ->options([
                                        'Package 1' => 'Package 1',
                                        'Package 2' => 'Package 2',
                                        'Package 3' => 'Package 3',
                                        'Other' => 'Other',
                                    ])
                                    ->searchable()
                                    ->nullable()
                                    ->helperText('Used to group products into predefined package groups.'),

                                TextInput::make('package_sort_order')
                                    ->label('Package Sort Order')
                                    ->numeric()
                                    ->nullable()
                                    ->helperText('Sort order within this package group. Lower numbers appear first.')
                                    ->rules(function ($record) {
                                        return [
                                            Rule::unique('products', 'package_sort_order')
                                                ->ignore($record?->id)
                                                ->where(function ($query) use ($record) {
                                                    $package = request()->input('data.package_group') ?? $record?->package_group;
                                                    return $query->where('package_group', $package);
                                                }),
                                        ];
                                    })
                                    ->validationMessages([
                                        'unique' => 'This sort order is already in use for this package group.',
                                    ]),
                            ])->columnSpan(1),
                    ]),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            // ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->recordUrl(false)
            ->defaultPaginationPageOption(50)
            ->paginated([50])
            ->paginationPageOptions([50, 100])
            ->columns([
                TextColumn::make('sort_order')->label('Order')->sortable(),
                TextColumn::make('code')->width(100),
                TextColumn::make('package_sort_order')
                    ->label('Pkg Order')
                    ->sortable()
                    ->visible(fn ($record) => $record && !empty($record->package_group)) // Add null check
                    ->width(80),
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
                        // Don't display the actual description content
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
                                    // Apply the same formatting
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

                ToggleColumn::make('is_active')->label('Active')->width(100)->disabled(fn() => auth()->user()->role_id != 3),

                ToggleColumn::make('convert_pi')
                    ->label(new HtmlString('Convert<br>to PI'))
                    ->width(100)
                    ->disabled(fn() => auth()->user()->role_id != 3),

                // New AutoCount Integration Columns
                ToggleColumn::make('push_to_autocount')
                    ->label(new HtmlString('Push<br>Invoice'))
                    ->width(100)
                    ->disabled(fn() => auth()->user()->role_id != 3)
                    ->afterStateUpdated(function ($record, $state) {
                        // If Push Invoice is turned ON, turn OFF Push SO
                        if ($state) {
                            $record->update(['push_so' => false]);
                        }
                    }),

                ToggleColumn::make('push_so')
                    ->label(new HtmlString('Push<br>S/Order'))
                    ->width(100)
                    ->disabled(fn() => auth()->user()->role_id != 3)
                    ->afterStateUpdated(function ($record, $state) {
                        // If Push SO is turned ON, turn OFF Push Invoice
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
                            'yes' => 'success',  // Green
                            'no' => 'danger',    // Red
                            'margin' => 'primary', // Blue (default primary color)
                            default => 'gray'
                        };
                    })
                    ->width(100),

                ToggleColumn::make('amount_editable')
                    ->label(new HtmlString('Edit<br>Amount'))
                    ->width(100)
                    ->disabled(fn() => auth()->user()->role_id != 3),

                ToggleColumn::make('editable')->label(new HtmlString('Edit<br>Details'))->width(100)->disabled(fn() => auth()->user()->role_id != 3),

                // Legacy column (keep for backwards compatibility)
                ToggleColumn::make('taxable')->label('Tax')->width(100)->disabled(fn() => auth()->user()->role_id != 3),
                ToggleColumn::make('minimum_price')->label('Min')->width(100)->disabled(fn() => auth()->user()->role_id != 3),
            ])
            // ...existing filters and actions...
            ->filters([
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
                            'free_device' => 'Free Device',
                            'installation' => 'Installation',
                            'door_access_package' => 'Door Access Package',
                            'door_access_accesories' => 'Door Access Accesories',
                            'new_sales' => 'New Sales',
                            'new_sales_addon' => 'New Sales Add On',
                            'renewal_sales' => 'Renewal Sales',
                            'renewal_sales_addon' => 'Renewal Sales Add On',
                        ];

                        $selectedLabels = collect($data['solution'])
                            ->map(fn ($solution) => $solutionLabels[$solution] ?? $solution)
                            ->implode(', ');

                        return "Solution: {$selectedLabels}";
                    }),

                // Add new filters for the new columns
                Filter::make('autocount_integration')
                    ->form([
                        Select::make('integration_type')
                            ->label('AutoCount Integration')
                            ->options([
                                'push_to_autocount' => 'Push Invoice',
                                'push_so' => 'Push SO',
                                'push_sw' => 'Push SW (Software only)',
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            !empty($data['integration_type']),
                            fn (Builder $query): Builder => $query->where($data['integration_type'], true)
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['integration_type'])) {
                            return null;
                        }

                        $labels = [
                            'push_to_autocount' => 'Push Invoice',
                            'push_so' => 'Push SO',
                            'push_sw' => 'Push SW',
                        ];

                        return "Integration: {$labels[$data['integration_type']]}";
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
