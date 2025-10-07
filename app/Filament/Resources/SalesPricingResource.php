<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesPricingResource\Pages;
use App\Models\SalesPricing;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use FilamentTiptapEditor\TiptapEditor;

class SalesPricingResource extends Resource
{
    protected static ?string $model = SalesPricing::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Sales Pricing';
    protected static ?string $navigationGroup = 'Sales Management';
    protected static ?int $navigationSort = 28;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Sales Pricing Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->extraAlpineAttributes([
                                'x-on:input' => '
                                    const start = $el.selectionStart;
                                    const end = $el.selectionEnd;
                                    const value = $el.value;
                                    $el.value = value.toUpperCase();
                                    $el.setSelectionRange(start, end);
                                '
                            ])
                            ->dehydrateStateUsing(fn ($state) => strtoupper($state)),

                        Forms\Components\DatePicker::make('effective_date')
                            ->required()
                            ->default(now()),

                        Forms\Components\Select::make('status')
                            ->options([
                                'Active' => 'Active',
                                'Inactive' => 'Inactive',
                                'Draft' => 'Draft',
                                'Expired' => 'Expired',
                            ])
                            ->default('Active')
                            ->required(),

                        // Hidden fields for tracking creators/updaters
                        Forms\Components\Hidden::make('created_by')
                            ->dehydrated(fn ($state) => filled($state))
                            ->default(fn () => auth()->id()),

                        Forms\Components\Hidden::make('last_updated_by')
                            ->dehydrated(fn ($state) => true)
                            ->default(fn () => auth()->id()),
                    ]),

                Forms\Components\Section::make('Sales Pricing Pages')
                    ->schema([
                        Forms\Components\Repeater::make('pages')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('order')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->label('Order (pages are displayed in ascending order)'),

                                TiptapEditor::make('content')
                                    ->required()
                                    ->columnSpanFull()
                                    ->profile('custom')
                                    ->tools([
                                        'heading', 'bullet-list', 'ordered-list', 'checked-list', 'blockquote', 'hr',
                                        'bold', 'italic', 'strike', 'underline', 'superscript', 'subscript', 'lead', 'small', 'align-left', 'align-center', 'align-right',
                                        'link', 'media', 'oembed', 'table', 'grid-builder', 'details',
                                        'code', 'code-block',
                                    ])
                                    ->maxContentWidth('full'),

                                // Hidden fields for tracking creators/updaters
                                Forms\Components\Hidden::make('created_by')
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->default(fn () => auth()->id()),

                                Forms\Components\Hidden::make('last_updated_by')
                                    ->dehydrated(fn ($state) => true)
                                    ->default(fn () => auth()->id()),
                            ])
                            ->orderable('order')
                            ->defaultItems(1)
                            ->reorderable()
                            ->columnSpanFull()
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                $data['created_by'] = auth()->id();
                                $data['last_updated_by'] = auth()->id();
                                return $data;
                            })
                            ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                                $data['last_updated_by'] = auth()->id();
                                return $data;
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pages_count')
                    ->label('Pages')
                    ->counts('pages')
                    ->sortable(),
                Tables\Columns\TextColumn::make('effective_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->date()
                    ->sortable()
                    ->placeholder('No expiry'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Active' => 'success',
                        'Inactive' => 'danger',
                        'Draft' => 'warning',
                        'Expired' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('access_right')
                    ->label('Access Rights')
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return 'All Users';
                        }

                        if (is_string($state)) {
                            if (strpos($state, '[') !== false) {
                                try {
                                    $state = json_decode($state, true);
                                } catch (\Exception $e) {
                                    return 'Invalid format';
                                }
                            } else {
                                $state = explode(',', $state);
                            }
                        }

                        if (!is_array($state) || empty($state)) {
                            return 'All Users';
                        }

                        $roleNames = [
                            1 => 'Lead Owner',
                            2 => 'Salesperson',
                            3 => 'Manager',
                            4 => 'Implementer',
                            5 => 'Team Lead Impl',
                            6 => 'Trainer',
                            7 => 'Team Lead Trainer',
                            8 => 'Support',
                            9 => 'Technician',
                        ];

                        $roles = [];
                        foreach ($state as $roleId) {
                            $roleIdInt = (int)$roleId;
                            if (array_key_exists($roleIdInt, $roleNames)) {
                                $roles[] = $roleNames[$roleIdInt];
                            }
                        }

                        return !empty($roles) ? implode(', ', $roles) : 'All Users';
                    }),
                Tables\Columns\TextColumn::make('createdByUser.name')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('lastUpdatedByUser.name')
                    ->label('Last Updated By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Active' => 'Active',
                        'Inactive' => 'Inactive',
                        'Draft' => 'Draft',
                        'Expired' => 'Expired',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['last_updated_by'] = auth()->id();
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesPricings::route('/'),
            'create' => Pages\CreateSalesPricing::route('/create'),
            'edit' => Pages\EditSalesPricing::route('/{record}/edit'),
        ];
    }
}
