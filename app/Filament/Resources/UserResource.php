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
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;

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
        'search_lead' => 'filament.admin.pages.search-lead',

        // Handover
        'software_handover' => 'filament.admin.resources.software-handovers.index',
        'hardware_handover' => 'filament.admin.resources.hardware-handovers.index',
        'software_attachments' => 'filament.admin.resources.software-attachments.index',
        'hardware_attachments' => 'filament.admin.resources.hardware-attachments.index',

        // Hardware Dashboard
        'hardware_dashboard_all' => 'filament.admin.pages.hardware-dashboard-all',
        'hardware_dashboard_pending_stock' => 'filament.admin.pages.hardware-dashboard-pending-stock',

        // Repair
        'admin_repair_dashboard' => 'filament.admin.pages.admin-repair-dashboard',
        'admin_repairs' => 'filament.admin.resources.admin-repairs.index',

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
        'installers' => 'filament.admin.resources.installers.index',
        'spare_parts' => 'filament.admin.resources.spare-parts.index',
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
                        Grid::make(5)
                            ->schema([
                                FileUpload::make("avatar_path")
                                    ->label('Profile Pic')         // Removes the label text
                                    ->placeholder('')
                                    ->disk('public')
                                    ->directory('uploads/photos')
                                    ->image()
                                    ->avatar()
                                    ->imageEditor()
                                    ->extraAttributes(['class' => 'mx-auto'])
                                    ->columnSpan(1),
                                Grid::make(1)
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->required()
                                        ->maxLength(255),

                                    Forms\Components\Select::make('role_id')
                                        ->label('Role')
                                        ->searchable()
                                        ->required()
                                        ->preload()
                                        ->live()
                                        ->options([
                                            1 => 'Lead Owner',
                                            2 => 'Salesperson',
                                            3 => 'Manager',
                                            4 => 'Implementer',
                                            5 => 'Team Lead Implementer',
                                            6 => 'Trainer',
                                            7 => 'Team Lead Trainer',
                                            8 => 'Support',
                                            9 => 'Technician',
                                            // 10 => 'Admin Handover',
                                        ])
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            if (!$state) return;

                                            if ((int)$state === 10) {
                                                $set('role_id', 1);
                                                $set('additional_role', 1);
                                            } else {
                                                $set('additional_role', 0);
                                            }

                                            // Define default permissions for each role
                                            $rolePermissions = match ((int) $state) {
                                                // Lead Owner Permissions
                                                1 => [
                                                    'leads' => true,
                                                    'quotations' => true,
                                                    'proforma_invoices' => false,
                                                    'chat_room' => true,
                                                    'software_handover' => false,
                                                    'hardware_handover' => false,
                                                    'sales_forecast' => true,
                                                    'sales_forecast_summary' => true,
                                                    'calendar' => true,
                                                    'search_lead' => false,
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
                                                    'installers' => false,
                                                    'spare_parts' => false,
                                                    'software_attachments' => false,
                                                    'hardware_attachments' => false,
                                                    'hardware_dashboard_all' => false,
                                                    'hardware_dashboard_pending_stock' => false,
                                                    'admin_repair_dashboard' => false,
                                                    'admin_repairs' => false,
                                                ],

                                                // Salesperson Permissions
                                                2 => [
                                                    'leads' => true,
                                                    'search_lead' => true,
                                                    'quotations' => true,
                                                    'proforma_invoices' => false,
                                                    'chat_room' => false,
                                                    'software_handover' => true,
                                                    'hardware_handover' => true,
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
                                                    'installers' => false,
                                                    'spare_parts' => false,
                                                    'software_attachments' => true,
                                                    'hardware_attachments' => true,
                                                    'hardware_dashboard_all' => true,
                                                    'hardware_dashboard_pending_stock' => true,
                                                    'admin_repair_dashboard' => false,
                                                    'admin_repairs' => false,
                                                ],

                                                // Implementer Permissions
                                                4 => [
                                                    'leads' => false,
                                                    'search_lead' => false,
                                                    'quotations' => false,
                                                    'proforma_invoices' => false,
                                                    'chat_room' => false,
                                                    'software_handover' => true,
                                                    'hardware_handover' => true,
                                                    'sales_forecast' => false,
                                                    'sales_forecast_summary' => false,
                                                    'calendar' => false,
                                                    'weekly_calendar_v2' => false,
                                                    'monthly_calendar' => false,
                                                    'demo_ranking' => false,
                                                    'lead_analysis' => false,
                                                    'demo_analysis' => false,
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
                                                    'installers' => false,
                                                    'spare_parts' => false,
                                                    'software_attachments' => true,
                                                    'hardware_attachments' => true,
                                                    'hardware_dashboard_all' => true,
                                                    'hardware_dashboard_pending_stock' => true,
                                                    'admin_repair_dashboard' => false,
                                                    'admin_repairs' => false,
                                                ],

                                                // Trainer Permissions
                                                6 => [
                                                    'leads' => false,
                                                    'search_lead' => false,
                                                    'quotations' => false,
                                                    'proforma_invoices' => false,
                                                    'chat_room' => false,
                                                    'software_handover' => false,
                                                    'hardware_handover' => false,
                                                    'sales_forecast' => false,
                                                    'sales_forecast_summary' => false,
                                                    'calendar' => false,
                                                    'weekly_calendar_v2' => false,
                                                    'monthly_calendar' => false,
                                                    'demo_ranking' => false,
                                                    'lead_analysis' => false,
                                                    'demo_analysis' => false,
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
                                                    'installers' => false,
                                                    'spare_parts' => false,
                                                    'software_attachments' => false,
                                                    'hardware_attachments' => false,
                                                    'hardware_dashboard_all' => false,
                                                    'hardware_dashboard_pending_stock' => false,
                                                    'admin_repair_dashboard' => false,
                                                    'admin_repairs' => false,
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
                                ])->columnSpan(2),

                                Grid::make(1)
                                    ->schema([
                                        Forms\Components\TextInput::make('email')
                                            ->email()
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('code')
                                            ->label('Code')
                                            ->maxLength(2),
                                    ])->columnspan(2),
                            ]),
                        Forms\Components\TextInput::make('mobile_number')
                            ->label('Phone Number'),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->visible(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('api_user_id')
                            ->label('Staff ID'),
                    ])
                    ->columns(2),

                // Only show route permissions section when editing (not creating)
                Forms\Components\Section::make('Route Permissions')
                ->description('Configure which parts of the system this user can access')
                ->schema([
                    // Main Navigation
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

                            Forms\Components\Checkbox::make('permissions.search_lead')
                                ->label('Search Lead')
                                ->helperText('Access to search leads')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['search_lead'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),
                        ])
                        ->columns(2),

                    // Lead Owner Section
                    Forms\Components\Fieldset::make('Lead Owner')
                        ->schema([
                            Forms\Components\Checkbox::make('permissions.calendar')
                                ->label('Calendar V1')
                                ->helperText('Access to weekly calendar view 1')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['calendar'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            Forms\Components\Checkbox::make('permissions.weekly_calendar_v2')
                                ->label('Calendar V2')
                                ->helperText('Access to weekly calendar view 2')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
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
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
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
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['sales_admin_analysis_v1'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            Forms\Components\Checkbox::make('permissions.sales_admin_analysis_v2')
                                ->label('Sales Admin - Performance')
                                ->helperText('Access to sales admin analysis v2')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['sales_admin_analysis_v2'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            Forms\Components\Checkbox::make('permissions.sales_admin_analysis_v3')
                                ->label('Sales Admin - Action Task')
                                ->helperText('Access to sales admin analysis v3')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['sales_admin_analysis_v3'];
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

                    // Salesperson Section
                    Forms\Components\Fieldset::make('Salesperson')
                        ->schema([
                            // Commercial Part
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

                            // Analysis
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

                            // Forecast
                            Forms\Components\Checkbox::make('permissions.sales_forecast')
                                ->label('Forecast - Salesperson')
                                ->helperText('View sales forecast by salesperson')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['sales_forecast'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            Forms\Components\Checkbox::make('permissions.sales_forecast_summary')
                                ->label('Forecast - Summary')
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

                    // Implementer Section
                    Forms\Components\Fieldset::make('Implementer')
                        ->schema([
                            Forms\Components\Checkbox::make('permissions.software_handover')
                                ->label('Software Handover')
                                ->helperText('Manage software handover documents')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['software_handover'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            Forms\Components\Checkbox::make('permissions.hardware_handover')
                                ->label('Hardware Handover')
                                ->helperText('Manage hardware handover documents')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['hardware_handover'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            Forms\Components\Checkbox::make('permissions.software_attachments')
                                ->label('Software Attachments')
                                ->helperText('Access to software attachments')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['software_attachments'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            Forms\Components\Checkbox::make('permissions.hardware_attachments')
                                ->label('Hardware Attachments')
                                ->helperText('Access to hardware attachments')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['hardware_attachments'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),
                        ])
                        ->columns(2),

                    // Hardware Dashboard Section
                    Forms\Components\Fieldset::make('Hardware Dashboard')
                        ->schema([
                            Forms\Components\Checkbox::make('permissions.hardware_dashboard_all')
                                ->label('Hardware Dashboard - All')
                                ->helperText('Access to hardware dashboard - all view')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['hardware_dashboard_all'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            Forms\Components\Checkbox::make('permissions.hardware_dashboard_pending_stock')
                                ->label('Hardware Dashboard - Pending Stock')
                                ->helperText('Access to hardware dashboard - pending stock')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['hardware_dashboard_pending_stock'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),
                        ])
                        ->columns(2),

                    // Repair Section
                    Forms\Components\Fieldset::make('Repair')
                        ->schema([
                            Forms\Components\Checkbox::make('permissions.admin_repair_dashboard')
                                ->label('Repair Dashboard')
                                ->helperText('Access to repair dashboard')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['admin_repair_dashboard'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            Forms\Components\Checkbox::make('permissions.admin_repairs')
                                ->label('Repair Attachments')
                                ->helperText('Access to repair attachments')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['admin_repairs'];
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
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
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
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['products'];
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

                            Forms\Components\Checkbox::make('permissions.installers')
                                ->label('Installers')
                                ->helperText('Manage installers')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['installers'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            Forms\Components\Checkbox::make('permissions.spare_parts')
                                ->label('Spare Parts')
                                ->helperText('Manage spare parts')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['spare_parts'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            // Access Rights
                            Forms\Components\Checkbox::make('permissions.users')
                                ->label('System Admin')
                                ->helperText('Manage system users')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
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
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role_id')
                    ->label('Role')
                    ->searchable()
                    ->sortable(query: function(Builder $query, string $direction): Builder {
                        return $query->orderBy('role_id', $direction);
                    })
                    ->formatStateUsing(function ($state) {
                        return match ((int) $state) {
                            1 => 'Lead Owner',
                            2 => 'Salesperson',
                            3 => 'Manager',
                            4 => 'Implementer',
                            5 => 'Team Lead Implementer',
                            6 => 'Trainer',
                            7 => 'Team Lead Trainer',
                            // 10 => 'Admin Handover',
                            8 => 'Support',
                            9 => 'Technician',
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



    // public static function mutateFormDataBeforeSave(array $data): array
    // {
    //     if ($data['role_id'] === 10) {
    //         $data['role_id'] = 1;
    //         $data['additional_role'] = 1;
    //     }

    //     return $data;
    // }
}
