<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SparePartResource\Pages;
use App\Models\SparePart;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class SparePartResource extends Resource
{
    protected static ?string $model = SparePart::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench';

    protected static ?string $navigationLabel = 'Spare Parts';

    protected static ?string $navigationGroup = 'Repair Management';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Spare Part Details')
                    ->schema([
                        Grid::make(3)
                        ->schema([
                            Select::make('device_model')
                                ->label('Device Model')
                                ->columnSpan(1)
                                ->options([
                                    'TC10' => 'TC10',
                                    'TC20' => 'TC20',
                                    'FACE ID 5' => 'FACE ID 5',
                                    'FACE ID 6' => 'FACE ID 6',
                                    'TIME BEACON' => 'TIME BEACON',
                                    'NFC TAG' => 'NFC TAG',
                                    'TA100C / HID' => 'TA100C / HID',
                                    'TA100C / R' => 'TA100C / R',
                                    'TA100C / MF' => ' TA100C / MF',
                                    'TA100C / R / W' => 'TA100C / R / W',
                                    'TA100C / MF / W' => 'TA100C / MF / W',
                                    'TA100C / HID / W' => 'TA100C / HID / W',
                                    'TA100C / W' => 'TA100C / W',
                                    'TIME ATTENDANCE' => 'TIME ATTENDANCE',
                                    'DOOR ACCESS' => 'DOOR ACCESS',
                                ])
                                ->searchable()
                                ->required(),

                            TextInput::make('name')
                                ->label('Spare Part Name')
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->afterStateHydrated(fn($state) => Str::upper($state))
                                ->afterStateUpdated(fn($state) => Str::upper($state))
                                ->columnSpan(1)
                                ->required()
                                ->maxLength(255),

                            TextInput::make('autocount_code')
                                ->label('Autocount Code')
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->afterStateHydrated(fn($state) => Str::upper($state))
                                ->afterStateUpdated(fn($state) => Str::upper($state))
                                ->columnSpan(1)
                                ->maxLength(255),
                        ]),
                        // FileUpload::make('picture_url')
                        //     ->label('Part Image')
                        //     ->image()
                        //     ->maxSize(5120) // 5MB
                        //     ->directory('spare-parts')
                        //     ->visibility('public')
                        //     ->imageResizeMode('contain')
                        //     ->imageResizeTargetWidth('800')
                        //     ->imageResizeTargetHeight('800')
                        //     ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive parts won\'t appear in the repair form'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('device_model')
                    ->label('Device Model')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Part Name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('autocount_code')
                    ->label('Autocount Code')
                    ->sortable()
                    ->searchable(),

                // TextColumn::make('picture_url')
                //     ->label('Image')
                //     ->formatStateUsing(fn ($state) => $state ? 'Available' : 'Not Available')
                //     ->badge()
                //     ->color(fn ($state) => $state ? 'success' : 'danger')
                //     ->url(fn ($record) => $record->picture_url && $record->picture_url !== url('images/no-image.jpg')
                //         ? $record->picture_url
                //         : null)
                //     ->openUrlInNewTab(),

                ToggleColumn::make('is_active')
                    ->label('Active')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('device_model')
                    ->label('Filter by Device Model')
                    ->options([
                        'TC10' => 'TC10',
                        'TC20' => 'TC20',
                        'FACE ID 5' => 'FACE ID 5',
                        'FACE ID 6' => 'FACE ID 6',
                        'TIME BEACON' => 'TIME BEACON',
                        'NFC TAG' => 'NFC TAG',
                        'TA100C / HID' => 'TA100C / HID',
                        'TA100C / R' => 'TA100C / R',
                        'TA100C / MF' => ' TA100C / MF',
                        'TA100C / R / W' => 'TA100C / R / W',
                        'TA100C / MF / W' => 'TA100C / MF / W',
                        'TA100C / HID / W' => 'TA100C / HID / W',
                        'TA100C / W' => 'TA100C / W',
                        'TIME ATTENDANCE' => 'TIME ATTENDANCE',
                        'DOOR ACCESS' => 'DOOR ACCESS',
                    ])
                    ->multiple(),

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
            ])
            ->actions([
                // Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            ->defaultSort('device_model', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            // No relations needed for this basic implementation
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSpareParts::route('/'),
            'create' => Pages\CreateSparePart::route('/create'),
            'edit' => Pages\EditSparePart::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->orderBy('device_model', 'asc')
            ->orderBy('name', 'asc');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
