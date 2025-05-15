<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\UserResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\UserResource\RelationManagers;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Settings';

    // Define the route permission mapping
    public static array $routePermissionMap = [
        // Main Navigation
        'leads' => 'filament.admin.resources.leads.index',
        'quotations' => 'filament.admin.resources.quotations.index',
        'proforma_invoices' => 'filament.admin.pages.proforma-invoices',
        'chat_room' => 'filament.admin.pages.chat-room',

        // Sales Forecast
        'sales_forecast' => 'filament.admin.pages.sales-forecast',
        'sales_forecast_summary' => 'filament.admin.pages.sales-forecast-summary',

        // Calendar
        'calendar' => 'filament.admin.pages.calendar',
        'weekly_calendar_v2' => 'filament.admin.pages.weekly-calendar-v2',
        'monthly_calendar' => 'filament.admin.pages.monthly-calendar',
        'demo_ranking' => 'filament.admin.pages.demo-ranking',

        // Analysis
        'lead_analysis' => 'filament.admin.pages.lead-analysis',
        'demo_analysis' => 'filament.admin.pages.demo-analysis',
        'marketing_analysis' => 'filament.admin.pages.marketing-analysis',
        'sales_admin_analysis_v1' => 'filament.admin.pages.sales-admin-analysis-v1',
        'sales_admin_analysis_v2' => 'filament.admin.pages.sales-admin-analysis-v2',
        'sales_admin_analysis_v3' => 'filament.admin.pages.sales-admin-analysis-v3',

        // Admin Settings
        'products' => 'filament.admin.resources.products.index',
        'users' => 'filament.admin.resources.users.index',
        'industries' => 'filament.admin.resources.industries.index',
        'lead_sources' => 'filament.admin.resources.lead-sources.index',
        'invalid_lead_reasons' => 'filament.admin.resources.invalid-lead-reasons.index',
        'resellers' => 'filament.admin.resources.resellers.index',
    ];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.resources.users.index');
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Details')
                    ->schema([
                        Forms\Components\Select::make('role_id')
                            ->label('Role')
                            ->searchable()
                            ->required()
                            ->preload()
                            ->live()
                            ->options([
                                2 => 'Salesperson',
                                1 => 'Lead Owner',
                                3 => 'Manager',
                            ])
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (!$state) return;

                                // Define default permissions for each role
                                $rolePermissions = match ((int) $state) {
                                    // Lead Owner Permissions
                                    1 => [
                                        'leads' => true,
                                        'quotations' => true,
                                        'proforma_invoices' => false,
                                        'chat_room' => true,
                                        'sales_forecast' => true,
                                        'sales_forecast_summary' => true,
                                        'calendar' => true,
                                        'weekly_calendar_v2' => true,
                                        'monthly_calendar' => true,
                                        'demo_ranking' => false,
                                        'lead_analysis' => true,
                                        'demo_analysis' => true,
                                        'marketing_analysis' => false,
                                        'sales_admin_analysis_v1' => true,
                                        'sales_admin_analysis_v2' => true,
                                        'sales_admin_analysis_v3' => true,
                                        'products' => false,
                                        'users' => false,
                                        'industries' => false,
                                        'lead_sources' => false,
                                        'invalid_lead_reasons' => false,
                                        'resellers' => false,
                                    ],

                                    // Salesperson Permissions
                                    2 => [
                                        'leads' => true,
                                        'quotations' => true,
                                        'proforma_invoices' => false,
                                        'chat_room' => false,
                                        'sales_forecast' => true,
                                        'sales_forecast_summary' => false,
                                        'calendar' => true,
                                        'weekly_calendar_v2' => false,
                                        'monthly_calendar' => true,
                                        'demo_ranking' => false,
                                        'lead_analysis' => true,
                                        'demo_analysis' => true,
                                        'marketing_analysis' => false,
                                        'sales_admin_analysis_v1' => false,
                                        'sales_admin_analysis_v2' => false,
                                        'sales_admin_analysis_v3' => false,
                                        'products' => false,
                                        'users' => false,
                                        'industries' => false,
                                        'lead_sources' => false,
                                        'invalid_lead_reasons' => false,
                                        'resellers' => false,
                                    ],

                                    // Manager (full access)
                                    3 => array_fill_keys(array_keys(self::$routePermissionMap), true),

                                    default => [],
                                };

                                // Set form state for all permissions
                                foreach ($rolePermissions as $key => $value) {
                                    $set("permissions.{$key}", $value);
                                }
                            }),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->maxLength(2),
                        Forms\Components\TextInput::make('mobile_number')
                            ->required()
                            ->label('Phone Number'),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                // Only show route permissions section when editing (not creating)
                Forms\Components\Section::make('Route Permissions')
                    ->description('Configure which parts of the system this user can access')
                    ->schema([
                        // Main navigation items
                        Forms\Components\Fieldset::make('Main Navigation')
                            ->schema([
                                Forms\Components\Checkbox::make('permissions.leads')
                                    ->label('Leads')
                                    ->helperText('Access to leads management')
                                    ->afterStateHydrated(function ($component, $state, ?User $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['leads'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                Forms\Components\Checkbox::make('permissions.quotations')
                                    ->label('Quotations')
                                    ->helperText('Access to quotations')
                                    ->afterStateHydrated(function ($component, $state, ?User $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['quotations'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                Forms\Components\Checkbox::make('permissions.proforma_invoices')
                                    ->label('Proforma Invoices')
                                    ->helperText('Access to proforma invoices')
                                    ->afterStateHydrated(function ($component, $state, ?User $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['proforma_invoices'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                Forms\Components\Checkbox::make('permissions.chat_room')
                                    ->label('Chat Room')
                                    ->helperText('Access to WhatsApp chat room')
                                    ->afterStateHydrated(function ($component, $state, ?User $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['chat_room'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),
                            ])
                            ->columns(2),

                        // Sales Forecast section
                        Forms\Components\Fieldset::make('Sales Forecast')
                            ->schema([
                                Forms\Components\Checkbox::make('permissions.sales_forecast')
                                    ->label('Sales Forecast - Salesperson')
                                    ->helperText('View sales forecast by salesperson')
                                    ->afterStateHydrated(function ($component, $state, ?User $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['sales_forecast'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                Forms\Components\Checkbox::make('permissions.sales_forecast_summary')
                                    ->label('Sales Forecast - Summary')
                                    ->helperText('View sales forecast summary')
                                    ->afterStateHydrated(function ($component, $state, ?User $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['sales_forecast_summary'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),
                            ])
                            ->columns(2),

                        // Calendar section
                        Forms\Components\Fieldset::make('Calendar')
                            ->schema([
                                Forms\Components\Checkbox::make('permissions.calendar')
                                    ->label('Weekly Calendar V1')
                                    ->helperText('Access to weekly calendar view 1')
                                    ->afterStateHydrated(function ($component, $state, ?User $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['calendar'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                Forms\Components\Checkbox::make('permissions.weekly_calendar_v2')
                                    ->label('Weekly Calendar V2')
                                    ->helperText('Access to weekly calendar view 2')
                                    ->afterStateHydrated(function ($component, $state, ?User $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['weekly_calendar_v2'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                Forms\Components\Checkbox::make('permissions.monthly_calendar')
                                    ->label('Monthly Calendar')
                                    ->helperText('Access to monthly calendar')
                                    ->afterStateHydrated(function ($component, $state, ?User $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['monthly_calendar'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                Forms\Components\Checkbox::make('permissions.demo_ranking')
                                    ->label('Demo Ranking')
                                    ->helperText('Access to demo ranking')
                                    ->afterStateHydrated(function ($component, $state, ?User $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['demo_ranking'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),
                            ])
                            ->columns(2),

                        // Analysis section
                        Forms\Components\Fieldset::make('Analysis')
                            ->schema([
                                Forms\Components\Checkbox::make('permissions.lead_analysis')
                                    ->label('Lead Analysis')
                                    ->helperText('Access to lead analysis')
                                    ->afterStateHydrated(function ($component, $state, ?User $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['lead_analysis'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                Forms\Components\Checkbox::make('permissions.demo_analysis')
                                    ->label('Demo Analysis')
                                    ->helperText('Access to demo analysis')
                                    ->afterStateHydrated(function ($component, $state, ?User $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['demo_analysis'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                Forms\Components\Checkbox::make('permissions.marketing_analysis')
                                    ->label('Marketing Analysis')
                                    ->helperText('Access to marketing analysis')
                                    ->afterStateHydrated(function ($component, $state, ?User $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['marketing_analysis'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                Forms\Components\Checkbox::make('permissions.sales_admin_analysis_v1')
                                    ->label('Sales Admin Analysis V1')
                                    ->helperText('Access to sales admin analysis v1')
                                    ->afterStateHydrated(function ($component, $state, ?User $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['sales_admin_analysis_v1'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                Forms\Components\Checkbox::make('permissions.sales_admin_analysis_v2')
                                    ->label('Sales Admin Analysis V2')
                                    ->helperText('Access to sales admin analysis v2')
                                    ->afterStateHydrated(function ($component, $state, ?User $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['sales_admin_analysis_v2'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                Forms\Components\Checkbox::make('permissions.sales_admin_analysis_v3')
                                    ->label('Sales Admin Analysis V3')
                                    ->helperText('Access to sales admin analysis v3')
                                    ->afterStateHydrated(function ($component, $state, ?User $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['sales_admin_analysis_v3'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),
                            ])
                            ->columns(2),

                        // Admin settings section - only visible for managers
                        Forms\Components\Fieldset::make('Admin Settings')
                            ->schema([
                                Forms\Components\Checkbox::make('permissions.products')
                                    ->label('Products')
                                    ->helperText('Manage product settings')
                                    ->afterStateHydrated(function ($component, $state, ?User $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['products'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                Forms\Components\Checkbox::make('permissions.users')
                                    ->label('Users')
                                    ->helperText('Manage system users')
                                    ->afterStateHydrated(function ($component, $state, ?User $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['users'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                Forms\Components\Checkbox::make('permissions.industries')
                                    ->label('Industries')
                                    ->helperText('Manage industry settings')
                                    ->afterStateHydrated(function ($component, $state, ?User $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['industries'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                Forms\Components\Checkbox::make('permissions.lead_sources')
                                    ->label('Lead Sources')
                                    ->helperText('Manage lead source settings')
                                    ->afterStateHydrated(function ($component, $state, ?User $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['lead_sources'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                Forms\Components\Checkbox::make('permissions.invalid_lead_reasons')
                                    ->label('Invalid Lead Reasons')
                                    ->helperText('Manage invalid lead reasons')
                                    ->afterStateHydrated(function ($component, $state, ?User $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['invalid_lead_reasons'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                Forms\Components\Checkbox::make('permissions.resellers')
                                    ->label('Resellers')
                                    ->helperText('Manage resellers')
                                    ->afterStateHydrated(function ($component, $state, ?User $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['resellers'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),
                            ])
                            ->columns(2)
                            ->visible(fn (Forms\Get $get) => $get('role_id') == 3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role_id')
                    ->label('Role')
                    ->searchable()
                    ->formatStateUsing(function ($state) {
                        return match ((int) $state) {
                            1 => 'Lead Owner',
                            2 => 'Salesperson',
                            3 => 'Manager',
                            default => 'Unknown',
                        };
                    }),
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    /**
     * Process permissions before saving
     */
    public static function processPermissionsForSave(array $data): array
    {
        if (!isset($data['permissions'])) {
            return $data;
        }

        $permissions = $data['permissions'];
        unset($data['permissions']);

        // Convert from permission keys to route names
        $routePermissions = [];

        foreach ($permissions as $key => $hasAccess) {
            if (isset(self::$routePermissionMap[$key])) {
                $routeName = self::$routePermissionMap[$key];
                $routePermissions[$routeName] = (bool) $hasAccess;
            }
        }

        $data['route_permissions'] = $routePermissions;

        return $data;
    }
}
