<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Models\Ticket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\Grid;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(64)
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Forms\Components\Select::make('product')
                                            ->label('Product')
                                            ->required()
                                            ->options([
                                                'TimeTec HR - Version 1' => 'TimeTec HR - Version 1',
                                                'TimeTec HR - Version 2' => 'TimeTec HR - Version 2',
                                            ]),

                                        Forms\Components\Select::make('module')
                                            ->label('Module')
                                            ->options([
                                                'Profile' => 'Profile',
                                                'Attendance' => 'Attendance',
                                                'Leave' => 'Leave',
                                                'Claim' => 'Claim',
                                                'Payroll' => 'Payroll',
                                                'Hardware' => 'Hardware',
                                                'Others' => 'Others',
                                            ])
                                            ->required(),
                                    ]),

                                Grid::make(2)
                                    ->schema([
                                        Forms\Components\Select::make('device_type')
                                            ->label('Device Type')
                                            ->options([
                                                'Mobile' => 'Mobile',
                                                'Browser' => 'Browser',
                                            ])
                                            ->live()
                                            ->required(),

                                        Forms\Components\Select::make('mobile_type')
                                            ->label('Mobile Type')
                                            ->options([
                                                'iOS' => 'iOS',
                                                'Android' => 'Android',
                                                'Huawei' => 'Huawei',
                                            ])
                                            ->visible(fn (Forms\Get $get): bool => $get('device_type') === 'Mobile')
                                            ->required(fn (Forms\Get $get): bool => $get('device_type') === 'Mobile'),

                                        Forms\Components\Select::make('browser_type')
                                            ->label('Browser Type')
                                            ->options([
                                                'Chrome' => 'Chrome',
                                                'Firefox' => 'Firefox',
                                                'Safari' => 'Safari',
                                                'Edge' => 'Edge',
                                                'Opera' => 'Opera',
                                            ])
                                            ->visible(fn (Forms\Get $get): bool => $get('device_type') === 'Browser')
                                            ->required(fn (Forms\Get $get): bool => $get('device_type') === 'Browser'),
                                    ]),

                                // Mobile-specific fields
                                Forms\Components\FileUpload::make('version_screenshot')
                                    ->label('Version Screenshot')
                                    ->image()
                                    ->maxSize(5120)
                                    ->directory('version_screenshots')
                                    ->visibility('public')
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        // Store the full URL when file is uploaded
                                        if ($state) {
                                            $set('version_screenshot_url', url('/file/' . $state));
                                        }
                                    })
                                    ->visible(fn (Forms\Get $get): bool => $get('device_type') === 'Mobile')
                                    ->required(fn (Forms\Get $get): bool => $get('device_type') === 'Mobile'),

                                Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('device_id')
                                            ->label('Device ID')
                                            ->placeholder('Enter device ID')
                                            ->visible(fn (Forms\Get $get): bool => $get('device_type') === 'Mobile')
                                            ->required(fn (Forms\Get $get): bool => $get('device_type') === 'Mobile'),

                                        Forms\Components\TextInput::make('os_version')
                                            ->label('OS Version')
                                            ->placeholder('e.g., Android 14')
                                            ->visible(fn (Forms\Get $get): bool => $get('device_type') === 'Mobile')
                                            ->required(fn (Forms\Get $get): bool => $get('device_type') === 'Mobile'),

                                        Forms\Components\TextInput::make('app_version')
                                            ->label('App Version')
                                            ->placeholder('e.g., 1.2.3')
                                            ->visible(fn (Forms\Get $get): bool => $get('device_type') === 'Mobile')
                                            ->required(fn (Forms\Get $get): bool => $get('device_type') === 'Mobile'),
                                    ])
                                    ->visible(fn (Forms\Get $get): bool => $get('device_type') === 'Mobile'),

                                // Browser-specific field
                                Forms\Components\TextInput::make('windows_os_version')
                                    ->label('Windows/OS Version')
                                    ->placeholder('e.g., Windows 11, macOS 13.1 (optional)')
                                    ->visible(fn (Forms\Get $get): bool => $get('device_type') === 'Browser'),

                                Forms\Components\Select::make('priority')
                                    ->label('Priority')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        // Automatically set status based on priority
                                        if (in_array($state, ['P1 - Software Bugs', 'P2 - Backend Assistance'])) {
                                            $set('status', 'RND - New');
                                        } elseif (in_array($state, ['P3 - Critical Enhancement', 'P4 - Paid Customization', 'P5 - Non-Critical Enhancement'])) {
                                            $set('status', 'PDT - New');
                                        }
                                    })
                                    ->options([
                                        'P1 - Software Bugs' => 'P1 - Software Bugs',
                                        'P2 - Backend Assistance' => 'P2 - Backend Assistance',
                                        'P3 - Critical Enhancement' => 'P3 - Critical Enhancement',
                                        'P4 - Paid Customization' => 'P4 - Paid Customization',
                                        'P5 - Non-Critical Enhancement' => 'P5 - Non-Critical Enhancement',
                                    ]),

                                Forms\Components\Hidden::make('status')
                                    ->default('RND - New'),
                            ])->columnSpan(32),

                        Grid::make(1)
                            ->schema([
                            ])->columnSpan(1),

                        Grid::make(1)
                            ->schema([
                                Forms\Components\Select::make('company_name')
                                    ->label('Company Name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->options(function () {
                                        return \Illuminate\Support\Facades\DB::connection('frontenddb')
                                            ->table('crm_expiring_license')
                                            ->select('f_company_name', 'f_created_time')
                                            ->groupBy('f_company_name', 'f_created_time')
                                            ->orderBy('f_created_time', 'desc')
                                            ->get()
                                            ->mapWithKeys(function ($company) {
                                                return [$company->f_company_name => strtoupper($company->f_company_name)];
                                            })
                                            ->toArray();
                                    })
                                    ->getSearchResultsUsing(function (string $search) {
                                        return \Illuminate\Support\Facades\DB::connection('frontenddb')
                                            ->table('crm_expiring_license')
                                            ->select('f_company_name', 'f_created_time')
                                            ->where('f_company_name', 'like', "%{$search}%")
                                            ->groupBy('f_company_name', 'f_created_time')
                                            ->orderBy('f_created_time', 'desc')
                                            ->limit(50)
                                            ->get()
                                            ->mapWithKeys(function ($company) {
                                                return [$company->f_company_name => strtoupper($company->f_company_name)];
                                            })
                                            ->toArray();
                                    })
                                    ->getOptionLabelUsing(function ($value) {
                                        return strtoupper($value);
                                    }),

                                Forms\Components\TextInput::make('zoho_ticket_number')
                                    ->label('Zoho Ticket Number'),

                                Forms\Components\TextInput::make('title')
                                    ->label('Title')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\RichEditor::make('description')
                                    ->label('Description'),
                            ])->columnSpan(31)
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('product')->sortable(),
                Tables\Columns\BadgeColumn::make('priority')
                    ->colors([
                        'success' => 'Low',
                        'warning' => 'Medium',
                        'danger' => 'High',
                        'danger' => 'Critical',
                    ]),
                Tables\Columns\BadgeColumn::make('status'),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }

    // Hook to post to API after creating
    public static function afterCreate($record): void
    {
        try {
            Http::post(url('/api/tickets'), $record->toArray());
        } catch (\Exception $e) {
            Log::error('Failed to post ticket to API: ' . $e->getMessage());
        }
    }
}
