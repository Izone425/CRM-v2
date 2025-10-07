<?php
namespace App\Filament\Resources;

use App\Filament\Resources\DeviceModelResource\Pages;
use App\Models\DeviceModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class DeviceModelResource extends Resource
{
    protected static ?string $model = DeviceModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-tablet';

    protected static ?string $navigationLabel = 'Device Models';

    protected static ?string $navigationGroup = 'Repair Management';

    protected static ?int $navigationSort = 15; // Position before Spare Parts

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.resources.device-models.index');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Device Model Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Model Name')
                            ->required()
                            ->maxLength(255)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->afterStateHydrated(fn($state) => Str::upper($state))
                            ->afterStateUpdated(fn($state) => Str::upper($state))
                            ->unique(ignoreRecord: true),

                        Forms\Components\Select::make('warranty_category')
                            ->label('Warranty Category')
                            ->options([
                                '1 year' => '1 Year',
                                '2 years' => '2 Years',
                            ])
                            ->default('1 year')
                            ->required(),

                        Forms\Components\Toggle::make('serial_number_required')
                            ->label('Serial Number Required')
                            ->helperText('If enabled, serial number will be mandatory for repairs')
                            ->default(false),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Model Name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('warranty_category')
                    ->label('Warranty')
                    ->badge()
                    ->sortable()
                    ->color(fn ($state) => $state === '2 years' ? 'success' : 'primary'),

                Tables\Columns\IconColumn::make('serial_number_required')
                    ->label('Serial Required')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active')
                    ->disabled()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warranty_category')
                    ->options([
                        '1 year' => '1 Year',
                        '2 years' => '2 Years',
                    ]),

                Tables\Filters\SelectFilter::make('serial_number_required')
                    ->options([
                        '1' => 'Required',
                        '0' => 'Optional',
                    ])
                    ->label('Serial Number'),

                Tables\Filters\SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ])
                    ->label('Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Edit Product')
                    ->closeModalByClickingAway(false)
                    ->hidden(fn(): bool => !auth()->user()->hasRouteAccess('filament.admin.resources.products.edit')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('activate')
                        ->label('Set Active')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (array $records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => true]);
                            }
                        }),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Set Inactive')
                        ->icon('heroicon-o-x-circle')
                        ->color('gray')
                        ->action(function (array $records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => false]);
                            }
                        }),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeviceModels::route('/'),
            'create' => Pages\CreateDeviceModel::route('/create'),
            'edit' => Pages\EditDeviceModel::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.resources.products.create');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }
}
