<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Resources\RoleResource\RelationManagers;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'System Roles';
    protected static ?int $navigationSort = 100;

    // Define the route permission mapping (copied from UserResource)
    public static array $routePermissionMap = [
        // Main Navigation
        'leads' => 'filament.admin.resources.leads.index',
        'quotations' => 'filament.admin.resources.quotations.index',
        'proforma_invoices' => 'filament.admin.pages.proforma-invoices',
        'chat_room' => 'filament.admin.pages.chat-room',
        'sales-lead' => 'filament.admin.pages.sales-lead',

        // Handover
        'software_handover' => 'filament.admin.resources.software-handovers.index',
        'hardware_handover' => 'filament.admin.resources.hardware-handovers.index',

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
                Forms\Components\Section::make('Role Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Role Name')
                            ->required()
                            ->maxLength(50)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('description')
                            ->label('Description')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Route Permissions')
                    ->description('Configure which parts of the system this role can access')
                    ->schema([
                        // Main Navigation
                        Forms\Components\Fieldset::make('Main Navigation')
                            ->schema([
                                Forms\Components\Checkbox::make('permissions.leads')
                                    ->label('Leads')
                                    ->helperText('Access to leads management')
                                    ->afterStateHydrated(function ($component, $state, ?Role $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['leads'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                Forms\Components\Checkbox::make('permissions.sales-lead')
                                    ->label('Sales Lead')
                                    ->helperText('Access to sales lead page')
                                    ->afterStateHydrated(function ($component, $state, ?Role $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['sales-lead'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),
                            ])
                            ->columns(2),

                        // Lead Owner Section
                        Forms\Components\Fieldset::make('Lead Owner')
                            ->schema([
                                // Sales Admin
                                Forms\Components\Fieldset::make('Sales Admin')
                                    ->schema([
                                        // Calendar
                                        Forms\Components\Checkbox::make('permissions.calendar')
                                            ->label('Calendar V1')
                                            ->helperText('Access to weekly calendar view 1')
                                            ->afterStateHydrated(function ($component, $state, ?Role $record) {
                                                if ($record) {
                                                    $permissions = $record->route_permissions ?? [];
                                                    $routeName = self::$routePermissionMap['calendar'];
                                                    $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                                }
                                            }),

                                        Forms\Components\Checkbox::make('permissions.weekly_calendar_v2')
                                            ->label('Calendar V2')
                                            ->helperText('Access to weekly calendar view 2')
                                            ->afterStateHydrated(function ($component, $state, ?Role $record) {
                                                if ($record) {
                                                    $permissions = $record->route_permissions ?? [];
                                                    $routeName = self::$routePermissionMap['weekly_calendar_v2'];
                                                    $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                                }
                                            }),

                                        // Prospects Automation
                                        Forms\Components\Checkbox::make('permissions.chat_room')
                                            ->label('WhatsApp')
                                            ->helperText('Access to WhatsApp chat room')
                                            ->afterStateHydrated(function ($component, $state, ?Role $record) {
                                                if ($record) {
                                                    $permissions = $record->route_permissions ?? [];
                                                    $routeName = self::$routePermissionMap['chat_room'];
                                                    $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                                }
                                            }),

                                        // Analysis
                                        Forms\Components\Checkbox::make('permissions.sales_admin_analysis_v1')
                                            ->label('Sales Admin - Leads')
                                            ->helperText('Access to sales admin analysis v1')
                                            ->afterStateHydrated(function ($component, $state, ?Role $record) {
                                                if ($record) {
                                                    $permissions = $record->route_permissions ?? [];
                                                    $routeName = self::$routePermissionMap['sales_admin_analysis_v1'];
                                                    $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                                }
                                            }),

                                        Forms\Components\Checkbox::make('permissions.sales_admin_analysis_v2')
                                            ->label('Sales Admin - Performance')
                                            ->helperText('Access to sales admin analysis v2')
                                            ->afterStateHydrated(function ($component, $state, ?Role $record) {
                                                if ($record) {
                                                    $permissions = $record->route_permissions ?? [];
                                                    $routeName = self::$routePermissionMap['sales_admin_analysis_v2'];
                                                    $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                                }
                                            }),

                                        Forms\Components\Checkbox::make('permissions.sales_admin_analysis_v3')
                                            ->label('Sales Admin - Action Task')
                                            ->helperText('Access to sales admin analysis v3')
                                            ->afterStateHydrated(function ($component, $state, ?Role $record) {
                                                if ($record) {
                                                    $permissions = $record->route_permissions ?? [];
                                                    $routeName = self::$routePermissionMap['sales_admin_analysis_v3'];
                                                    $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                                }
                                            }),

                                        Forms\Components\Checkbox::make('permissions.demo_ranking')
                                            ->label('Demo Ranking')
                                            ->helperText('Access to demo ranking')
                                            ->afterStateHydrated(function ($component, $state, ?Role $record) {
                                                if ($record) {
                                                    $permissions = $record->route_permissions ?? [];
                                                    $routeName = self::$routePermissionMap['demo_ranking'];
                                                    $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                                }
                                            }),
                                    ])
                                    ->columns(2),
                            ])
                            ->columns(1),

                        // Salesperson Section
                        Forms\Components\Fieldset::make('Salesperson')
                            ->schema([
                                // Calendar
                                Forms\Components\Checkbox::make('permissions.monthly_calendar')
                                    ->label('Monthly Calendar')
                                    ->helperText('Access to monthly calendar view for salesperson')
                                    ->afterStateHydrated(function ($component, $state, ?Role $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['monthly_calendar'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                // Commercial Part
                                Forms\Components\Checkbox::make('permissions.quotations')
                                    ->label('Quotations')
                                    ->helperText('Access to quotations')
                                    ->afterStateHydrated(function ($component, $state, ?Role $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['quotations'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                Forms\Components\Checkbox::make('permissions.proforma_invoices')
                                    ->label('Proforma Invoices')
                                    ->helperText('Access to proforma invoices')
                                    ->afterStateHydrated(function ($component, $state, ?Role $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['proforma_invoices'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                // Analysis
                                Forms\Components\Checkbox::make('permissions.lead_analysis')
                                    ->label('Lead Analysis')
                                    ->helperText('Access to lead analysis')
                                    ->afterStateHydrated(function ($component, $state, ?Role $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['lead_analysis'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                Forms\Components\Checkbox::make('permissions.demo_analysis')
                                    ->label('Demo Analysis')
                                    ->helperText('Access to demo analysis')
                                    ->afterStateHydrated(function ($component, $state, ?Role $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['demo_analysis'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                // Forecast
                                Forms\Components\Checkbox::make('permissions.sales_forecast')
                                    ->label('Forecast - Salesperson')
                                    ->helperText('View sales forecast by salesperson')
                                    ->afterStateHydrated(function ($component, $state, ?Role $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['sales_forecast'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                Forms\Components\Checkbox::make('permissions.sales_forecast_summary')
                                    ->label('Forecast - Summary')
                                    ->helperText('View sales forecast summary')
                                    ->afterStateHydrated(function ($component, $state, ?Role $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['sales_forecast_summary'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),
                            ])
                            ->columns(2),

                        // Admin Section
                        Forms\Components\Fieldset::make('Admin')
                            ->schema([
                                // Handover section
                                Forms\Components\Checkbox::make('permissions.software_handover')
                                    ->label('Software Handover')
                                    ->helperText('Manage software handover documents')
                                    ->afterStateHydrated(function ($component, $state, ?Role $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['software_handover'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                Forms\Components\Checkbox::make('permissions.hardware_handover')
                                    ->label('Hardware Handover')
                                    ->helperText('Manage hardware handover documents')
                                    ->afterStateHydrated(function ($component, $state, ?Role $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['hardware_handover'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),
                            ])
                            ->columns(2),

                        // Marketing Section
                        Forms\Components\Fieldset::make('Marketing')
                            ->schema([
                                Forms\Components\Checkbox::make('permissions.marketing_analysis')
                                    ->label('Marketing Analysis')
                                    ->helperText('Access to marketing analysis')
                                    ->afterStateHydrated(function ($component, $state, ?Role $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['marketing_analysis'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),
                            ])
                            ->columns(2),

                        // Settings Section
                        Forms\Components\Fieldset::make('Settings')
                            ->schema([
                                // System Label
                                Forms\Components\Checkbox::make('permissions.products')
                                    ->label('Products')
                                    ->helperText('Manage product settings')
                                    ->afterStateHydrated(function ($component, $state, ?Role $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['products'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                Forms\Components\Checkbox::make('permissions.industries')
                                    ->label('Industries')
                                    ->helperText('Manage industry settings')
                                    ->afterStateHydrated(function ($component, $state, ?Role $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['industries'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                Forms\Components\Checkbox::make('permissions.lead_sources')
                                    ->label('Lead Sources')
                                    ->helperText('Manage lead source settings')
                                    ->afterStateHydrated(function ($component, $state, ?Role $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['lead_sources'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                Forms\Components\Checkbox::make('permissions.invalid_lead_reasons')
                                    ->label('Invalid Lead Reasons')
                                    ->helperText('Manage invalid lead reasons')
                                    ->afterStateHydrated(function ($component, $state, ?Role $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['invalid_lead_reasons'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                Forms\Components\Checkbox::make('permissions.resellers')
                                    ->label('Resellers')
                                    ->helperText('Manage resellers')
                                    ->afterStateHydrated(function ($component, $state, ?Role $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['resellers'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),

                                // Access Rights
                                Forms\Components\Checkbox::make('permissions.users')
                                    ->label('System Admin')
                                    ->helperText('Manage system users')
                                    ->afterStateHydrated(function ($component, $state, ?Role $record) {
                                        if ($record) {
                                            $permissions = $record->route_permissions ?? [];
                                            $routeName = self::$routePermissionMap['users'];
                                            $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                        }
                                    }),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Role Name')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        return match ($record->id) {
                            1 => 'Lead Owner',
                            2 => 'Salesperson',
                            3 => 'Manager',
                            default => $state,
                        };
                    }),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record): ?string => $record->description),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->sortable(),
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
                Tables\Actions\Action::make('clone')
                    ->label('Clone')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function (Role $record) {
                        $newRole = $record->replicate();
                        $newRole->name = "Copy of {$record->name}";
                        $newRole->save();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // public static function getRelations(): array
    // {
    //     return [
    //         RelationManagers\UsersRelationManager::class,
    //     ];
    // }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
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
