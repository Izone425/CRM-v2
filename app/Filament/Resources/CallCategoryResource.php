<?php
namespace App\Filament\Resources;

use App\Filament\Resources\CallCategoryResource\Pages;
use App\Models\CallCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CallCategoryResource extends Resource
{
    protected static ?string $model = CallCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?string $navigationLabel = 'Call Categories';

    protected static ?int $navigationSort = 86;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(100),

                Forms\Components\Select::make('tier')
                    ->options([
                        '1' => 'Tier 1 - Module',
                        '2' => 'Tier 2 - Main Category',
                        '3' => 'Tier 3 - Sub Category',
                    ])
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state === '1') {
                            $set('parent_id', null);
                            $set('tier1_parent_id', null);
                        } elseif ($state === '2') {
                            $set('tier1_parent_id', null);
                        }
                    }),

                Forms\Components\Select::make('tier1_parent_id')
                    ->label('Tier 1 Module')
                    ->options(fn () => CallCategory::where('tier', '1')
                        ->where('is_active', true)
                        ->pluck('name', 'id'))
                    ->visible(fn (callable $get) => $get('tier') === '3')
                    ->required(fn (callable $get) => $get('tier') === '3')
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('parent_id', null))
                    ->searchable(),

                Forms\Components\Select::make('parent_id')
                    ->label(function (callable $get) {
                        $tier = $get('tier');
                        if ($tier === '2') {
                            return 'Tier 1 Module';
                        } elseif ($tier === '3') {
                            return 'Tier 2 Main Category';
                        }
                        return 'Parent';
                    })
                    ->options(function (callable $get) {
                        $tier = $get('tier');
                        if ($tier === '2') {
                            return CallCategory::where('tier', '1')
                                ->where('is_active', true)
                                ->pluck('name', 'id');
                        } elseif ($tier === '3') {
                            $tier1Id = $get('tier1_parent_id');
                            if (!$tier1Id) {
                                return [];
                            }
                            return CallCategory::where('tier', '2')
                                ->where('parent_id', $tier1Id)
                                ->where('is_active', true)
                                ->pluck('name', 'id');
                        }
                        return [];
                    })
                    ->required(function (callable $get) {
                        return in_array($get('tier'), ['2', '3']);
                    })
                    ->visible(function (callable $get) {
                        return in_array($get('tier'), ['2', '3']);
                    })
                    ->searchable(),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('tier')
                    ->colors([
                        'primary' => '1',
                        'warning' => '2',
                        'success' => '3',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        '1' => 'Tier 1 - Module',
                        '2' => 'Tier 2 - Main Category',
                        '3' => 'Tier 3 - Sub Category',
                        default => "Tier {$state}",
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tier')
                    ->options([
                        '1' => 'Tier 1 - Module',
                        '2' => 'Tier 2 - Main Category',
                        '3' => 'Tier 3 - Sub Category',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->indicator('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn() => auth()->user()->role_id == 3),
            ])
            ->defaultSort('tier', 'asc')
            ->defaultSort('name', 'asc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCallCategories::route('/'),
            'create' => Pages\CreateCallCategory::route('/create'),
            'edit' => Pages\EditCallCategory::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.resources.call-categories.index');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->role_id == 3;
    }
}
