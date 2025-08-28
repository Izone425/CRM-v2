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
                TextInput::make('code')
                    ->required(fn (Page $livewire) => ($livewire instanceof CreateRecord))
                    ->disabledOn('edit'),
                Select::make('solution')
                    ->placeholder('Select a solution')
                    ->options([
                        'software' => 'Software',
                        'hardware' => 'Hardware',
                        'hrdf' => 'HRDF',
                        'other' => 'Other',
                        'free_device' => 'Free Device',
                        'installation' => 'Installation',
                        'door_access_package' => 'Door Access Package',
                        'door_access_accesories' => 'Door Access Accesories',
                    ]),
                RichEditor::make('description'),
                TextInput::make('unit_price')
                        ->label('Cost (RM)'),
                Grid::make(4)
                    ->schema([
                    Toggle::make('taxable')
                        ->label('Taxable?')
                        ->inline(false),
                    Toggle::make('is_active')
                        ->label('Is Active?')
                        ->inline(false),
                    Toggle::make('editable')
                        ->label('Editable?')
                        ->inline(false)
                        ->default(true),
                    Toggle::make('minimum_price')
                        ->label('Minimum Price?')
                        ->inline(false)
                        ->default(true),
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
                    ])
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            // ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->recordUrl(false)
            ->columns([
                TextColumn::make('sort_order')->label('Order')->sortable(),
                TextColumn::make('code')->width(100),
                TextColumn::make('package_group')
                    ->label('Package Group')
                    ->sortable(),
                TextColumn::make('package_sort_order')
                    ->label('Pkg Order')
                    ->sortable()
                    ->visible(fn ($record) => !empty($record->package_group))
                    ->width(80),
                TextColumn::make('solution')->width(100),
                TextColumn::make('description')
                    ->html()
                    ->width(500)
                    ->wrap()
                    ->formatStateUsing(function ($state) {
                        // Ensure the HTML is properly formatted and encoded
                        if ($state) {
                            // Clean up any potential double-encoding issues
                            $state = html_entity_decode($state);

                            // Check if it's a list without proper <ul> tags
                            if (!str_contains($state, '<ul>') && str_contains($state, '<li>')) {
                                $state = '<ul style="list-style-type: disc; padding-left: 20px;">' . $state . '</ul>';
                            }
                            // If it already has <ul> tags, add styling to them
                            else if (str_contains($state, '<ul>')) {
                                $state = str_replace('<ul>', '<ul style="list-style-type: disc; padding-left: 20px;">', $state);
                            }

                            // Make sure <li> tags also have proper styling
                            $state = str_replace('<li>', '<li style="display: list-item;">', $state);

                            return $state;
                        }
                        return '';
                    }),
                TextColumn::make('unit_price')->label('Cost (RM)')->width(100),
                TextColumn::make('subscription_period')->label('Sub Period (Months)')->width(150),
                ToggleColumn::make('taxable')->label('Taxable?')->width(100)->disabled(),
                ToggleColumn::make('is_active')->label('Is Active?')->width(100)->disabled(),
                ToggleColumn::make('editable')->label('Editable?')->width(100)->disabled(),
                ToggleColumn::make('minimum_price')->label('Minimum Price?')->width(100)->disabled(),
            ])
            ->filters([
                Filter::make('package_group')
                    ->form([
                        Select::make('package_group')
                            ->options([
                                'Package 1' => 'Package 1',
                                'Package 2' => 'Package 2',
                                'Package 3' => 'Package 3',
                            ])
                            ->searchable()
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['package_group'], fn ($q) => $q->where('package_group', $data['package_group']));
                    }),
                Filter::make('code')
                    ->form([
                        Select::make('code')
                            ->options(fn(Product $product, ProductService $productService): array => $productService->getCode($product))
                            ->searchable()
                    ])
                    ->query(fn(Builder $query, array $data, ProductService $productService): Builder => $productService->filterByCode($query, $data)),
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
