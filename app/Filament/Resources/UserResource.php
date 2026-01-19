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
use Filament\Forms\Components\Toggle;
use Filament\Tables\Grouping\Group;
use Illuminate\Support\Facades\DB;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Settings';

    // Define the route permission mapping
    public static array $routePermissionMap = [
        // Dashboard
        'dashboard' => 'filament.admin.pages.dashboard-form',

        // Sales Admin
        'monthly_calendar' => 'filament.admin.pages.monthly-calendar',
        'weekly_calendar_v2' => 'filament.admin.pages.salesperson-calendar-v2',
        'all_calendar' => 'filament.admin.pages.calendar',
        'salesperson_lead_sequence' => 'filament.admin.pages.salesperson-lead-sequence',
        'whatsapp' => 'filament.admin.pages.whatsapp',
        'sales_admin_analysis_v1' => 'filament.admin.pages.sales-admin-analysis-v1',
        'sales_admin_analysis_v2' => 'filament.admin.pages.sales-admin-analysis-v2',
        'sales_admin_analysis_v3' => 'filament.admin.pages.sales-admin-analysis-v3',
        'demo_ranking' => 'filament.admin.pages.demo-ranking',
        'whatsapp' => 'filament.admin.pages.whatsapp',

        // Salesperson
        'leads' => 'filament.admin.resources.leads.index',
        'search_lead' => 'filament.admin.pages.search-lead',
        'quotations' => 'filament.admin.resources.quotations.index',
        'proforma_invoices' => 'filament.admin.pages.proforma-invoices',
        'lead_analysis' => 'filament.admin.pages.lead-analysis',
        'demo_analysis' => 'filament.admin.pages.demo-analysis',
        'sales_forecast' => 'filament.admin.pages.sales-forecast',
        'sales_forecast_summary' => 'filament.admin.pages.sales-forecast-summary',
        'salesperson_calendar' => 'filament.admin.pages.salesperson-calendar-v1',
        'implementer_calendar' => 'filament.admin.pages.implementer-calendar',
        'salesperson_appointment' => 'filament.admin.pages.salesperson-appointment',
        'site_survey_request' => 'filament.admin.pages.sales.site-survey-request',

        // Handover
        'software_project_list' => 'filament.admin.resources.software.index',
        'software_project_analysis' => 'filament.admin.pages.software.project-analysis',
        'hardware_dashboard_all' => 'filament.admin.pages.hardware-dashboard-all',
        'hardware_dashboard_pending_stock' => 'filament.admin.pages.hardware-dashboard-pending-stock',
        'onsite_repair_list' => 'filament.admin.pages.repair.onsite-repair-list',
        'technician_calendar' => 'filament.admin.pages.technician-calendar',

        // Admin Handover (additional_role = 1)
        'software_handover' => 'filament.admin.resources.software-handovers.index',
        'hardware_handover' => 'filament.admin.resources.hardware-handovers.index',
        'software_attachments' => 'filament.admin.resources.software-attachments.index',
        'hardware_attachments' => 'filament.admin.resources.hardware-attachments.index',
        'admin_repair_dashboard' => 'filament.admin.pages.admin-repair-dashboard',
        'admin_repairs' => 'filament.admin.resources.admin-repairs.index',

        // Support
        'call_logs' => 'filament.admin.pages.call-logs',
        'call_categories' => 'filament.admin.resources.call-categories.index',
        'overtime_calendar' => 'filament.admin.pages.overtime-calendar',

        // Technician
        'technician_appointment' => 'filament.admin.pages.technician-appointment',

        // Implementer
        'implementer_audit_list' => 'filament.admin.pages.implementer-audit-list',
        'implementer_request_count' => 'filament.admin.pages.implementer-request-count',
        'implementer_request_list' => 'filament.admin.pages.implementer-request-list',
        'email_templates' => 'filament.admin.resources.email-templates.index',

        // Marketing
        'marketing_analysis' => 'filament.admin.pages.marketing-analysis',
        'demo_analysis_table_form' => 'filament.admin.pages.demo-analysis-table-form',

        // Finance
        'finance' => 'filament.admin.pages.finance',

        // Settings
        'device_models' => 'filament.admin.resources.device-models.index',
        'products' => 'filament.admin.resources.products.index',
        'products_create' => 'filament.admin.resources.products.create',
        'products_edit' => 'filament.admin.resources.products.edit',
        'industries' => 'filament.admin.resources.industries.index',
        'lead_sources' => 'filament.admin.resources.lead-sources.index',
        'invalid_lead_reasons' => 'filament.admin.resources.invalid-lead-reasons.index',
        'resellers' => 'filament.admin.resources.resellers.index',
        'installers' => 'filament.admin.resources.installers.index',
        'spare_parts' => 'filament.admin.resources.spare-parts.index',
        'users' => 'filament.admin.resources.users.index',
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
                                Grid::make(1)
                                    ->schema([
                                        FileUpload::make("avatar_path")
                                            ->label('Profile Pic')
                                            ->placeholder('')
                                            ->disk('public')
                                            ->directory('uploads/photos')
                                            ->image()
                                            ->avatar()
                                            ->imageEditor()
                                            ->extraAttributes(['class' => 'mx-auto']),

                                        Toggle::make('is_timetec_hr')
                                            ->label('TimeTec HR')
                                            ->helperText('Activate it if this user part of the TimeTec HR sales team')
                                            ->default(false),
                                    ])
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
                                            3 => 'Master Admin',
                                            4 => 'Implementer',
                                            5 => 'Team Lead Implementer',
                                            6 => 'Trainer',
                                            7 => 'Team Lead Trainer',
                                            8 => 'Support',
                                            9 => 'Technician',
                                            10 => 'Finance',
                                        ])
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            if (!$state) return;

                                            // Define default permissions for each role
                                            $rolePermissions = match ((int) $state) {
                                                // Lead Owner Permissions
                                                1 => [
                                                    // Sales Admin
                                                    'dashboard' => true,

                                                    'monthly_calendar' => true,
                                                    'weekly_calendar_v2' => true,
                                                    'all_calendar' => true,
                                                    'salesperson_lead_sequence' => true,
                                                    'sales_admin_analysis_v1' => true,
                                                    'sales_admin_analysis_v2' => true,
                                                    'sales_admin_analysis_v3' => true,
                                                    'demo_ranking' => false,
                                                    'whatsapp' => true,
                                                    'leads' => true,
                                                    'search_lead' => true,

                                                    'software_handover' => true,
                                                    'hardware_handover' => true,
                                                    'software_attachments' => true,
                                                    'hardware_attachments' => true,
                                                    'admin_repair_dashboard' => true,
                                                    'admin_repairs' => true,
                                                ],

                                                // Salesperson Permissions (role_id = 2)
                                                2 => [
                                                    // Salesperson
                                                    'dashboard' => true,

                                                    'leads' => true,
                                                    'search_lead' => true,
                                                    'salesperson_calendar' => true,
                                                    'implementer_calendar' => true,
                                                    'all_calendar' => true,
                                                    'quotations' => true,
                                                    'proforma_invoices' => true,
                                                    'lead_analysis' => true,
                                                    'demo_analysis' => true,
                                                    'sales_forecast' => true,
                                                    'sales_forecast_summary' => false,
                                                    'salesperson_appointment' => true,
                                                    'site_survey_request' => true,

                                                    'software_handover' => true,
                                                    'hardware_handover' => true,
                                                    'software_attachments' => true,
                                                    'hardware_attachments' => true,
                                                    'admin_repair_dashboard' => true,
                                                    'admin_repairs' => true,

                                                    'device_models' => true,
                                                ],

                                                // Implementer Permissions (role_id = 4)
                                                4 => [
                                                    // Dashboard
                                                    'dashboard' => true,

                                                    // Handover
                                                    'software_project_list' => true,
                                                    'software_project_analysis' => true,
                                                    'hardware_dashboard_all' => true,
                                                    'hardware_dashboard_pending_stock' => true,
                                                    'onsite_repair_list' => true,
                                                    'technician_calendar' => false,

                                                    // Implementer
                                                    'implementer_calendar' => true,
                                                    'all_calendar' => true,
                                                    'implementer_audit_list' => true,
                                                    'implementer_request_count' => true,
                                                    'implementer_request_list' => true,
                                                    'email_templates' => false,

                                                    // Admin Handover
                                                    'software_handover' => true,
                                                    'hardware_handover' => true,
                                                    'software_attachments' => true,
                                                    'hardware_attachments' => true,
                                                    'admin_repair_dashboard' => true,
                                                    'admin_repairs' => true,

                                                    // Settings
                                                    'device_models' => false,
                                                    'products' => false,
                                                    'industries' => false,
                                                    'lead_sources' => false,
                                                    'invalid_lead_reasons' => false,
                                                    'resellers' => false,
                                                    'installers' => false,
                                                    'spare_parts' => false,
                                                    'users' => false,
                                                ],

                                                // Team Lead Implementer Permissions (role_id = 5)
                                                5 => [
                                                    // Dashboard
                                                    'dashboard' => true,

                                                    // Handover
                                                    'software_project_list' => true,
                                                    'software_project_analysis' => true,
                                                    'hardware_dashboard_all' => true,
                                                    'hardware_dashboard_pending_stock' => true,
                                                    'onsite_repair_list' => true,
                                                    'technician_calendar' => false,

                                                    // Implementer
                                                    'implementer_calendar' => true,
                                                    'all_calendar' => true,
                                                    'implementer_audit_list' => true,
                                                    'implementer_request_count' => true,
                                                    'implementer_request_list' => true,
                                                    'email_templates' => false,

                                                    // Admin Handover
                                                    'software_handover' => true,
                                                    'hardware_handover' => true,
                                                    'software_attachments' => true,
                                                    'hardware_attachments' => true,
                                                    'admin_repair_dashboard' => true,
                                                    'admin_repairs' => true,

                                                    // Settings
                                                    'device_models' => false,
                                                    'products' => false,
                                                    'industries' => false,
                                                    'lead_sources' => false,
                                                    'invalid_lead_reasons' => false,
                                                    'resellers' => false,
                                                    'installers' => false,
                                                    'spare_parts' => false,
                                                    'users' => false,
                                                ],

                                                // Trainer Permissions (role_id = 6)
                                                6 => [
                                                    // Dashboard
                                                    'dashboard' => true,

                                                    'software_handover' => true,
                                                    'hardware_handover' => true,
                                                    'software_attachments' => true,
                                                    'hardware_attachments' => true,
                                                    'admin_repair_dashboard' => true,
                                                    'admin_repairs' => true,

                                                    // Settings
                                                    'device_models' => false,
                                                    'products' => false,
                                                    'industries' => false,
                                                    'lead_sources' => false,
                                                    'invalid_lead_reasons' => false,
                                                    'resellers' => false,
                                                    'installers' => false,
                                                    'spare_parts' => false,
                                                    'users' => false,
                                                ],

                                                // Team Lead Trainer Permissions (role_id = 7)
                                                7 => [
                                                    // Dashboard
                                                    'dashboard' => true,

                                                    'software_handover' => true,
                                                    'hardware_handover' => true,
                                                    'software_attachments' => true,
                                                    'hardware_attachments' => true,
                                                    'admin_repair_dashboard' => true,
                                                    'admin_repairs' => true,

                                                    // Settings
                                                    'device_models' => false,
                                                    'products' => false,
                                                    'industries' => false,
                                                    'lead_sources' => false,
                                                    'invalid_lead_reasons' => false,
                                                    'resellers' => false,
                                                    'installers' => false,
                                                    'spare_parts' => false,
                                                    'users' => false,
                                                ],

                                                // Support Permissions (role_id = 8)
                                                8 => [
                                                    // Dashboard
                                                    'dashboard' => true,

                                                    // Sales Admin
                                                    'monthly_calendar' => false,
                                                    'weekly_calendar_v2' => false,
                                                    'all_calendar' => false,
                                                    'salesperson_lead_sequence' => false,
                                                    'whatsapp' => false,
                                                    'sales_admin_analysis_v1' => false,
                                                    'sales_admin_analysis_v2' => false,
                                                    'sales_admin_analysis_v3' => false,
                                                    'demo_ranking' => false,
                                                    'whatsapp' => false,

                                                    // Salesperson
                                                    'leads' => false,
                                                    'search_lead' => false,
                                                    'quotations' => false,
                                                    'proforma_invoices' => false,
                                                    'lead_analysis' => false,
                                                    'demo_analysis' => false,
                                                    'sales_forecast' => false,
                                                    'sales_forecast_summary' => false,
                                                    'salesperson_calendar' => false,
                                                    'implementer_calendar' => false,
                                                    'salesperson_appointment' => false,
                                                    'site_survey_request' => false,

                                                    // Handover
                                                    'software_project_list' => true,
                                                    'software_project_analysis' => false,
                                                    'hardware_dashboard_all' => false,
                                                    'hardware_dashboard_pending_stock' => false,
                                                    'onsite_repair_list' => false,
                                                    'technician_calendar' => false,

                                                    // Support
                                                    'call_logs' => true,
                                                    'call_categories' => true,
                                                    'overtime_calendar' => true,

                                                    // Settings
                                                    'device_models' => false,
                                                    'products' => false,
                                                    'industries' => false,
                                                    'lead_sources' => false,
                                                    'invalid_lead_reasons' => false,
                                                    'resellers' => false,
                                                    'installers' => false,
                                                    'spare_parts' => false,
                                                    'users' => false,
                                                ],

                                                // Technician Permissions (role_id = 9)
                                                9 => [
                                                    // Dashboard
                                                    'dashboard' => true,

                                                    // Handover/Technician
                                                    'onsite_repair_list' => true,
                                                    'technician_calendar' => true,
                                                    'technician_appointment' => true,

                                                    // Settings
                                                    'device_models' => false,
                                                    'products' => false,
                                                    'industries' => false,
                                                    'lead_sources' => false,
                                                    'invalid_lead_reasons' => false,
                                                    'resellers' => false,
                                                    'installers' => false,
                                                    'spare_parts' => false,
                                                    'users' => false,
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
                                    Forms\Components\TextInput::make('mobile_number')
                                        ->label('Phone Number'),
                                    Forms\Components\TextInput::make('password')
                                        ->password()
                                        ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                                        ->dehydrated(fn (?string $state): bool => filled($state))
                                        ->required(fn (string $operation): bool => $operation === 'create')
                                        ->visible(fn (string $operation): bool => $operation === 'create')
                                        ->maxLength(255),
                                ])->columnSpan(2),

                                Grid::make(1)
                                    ->schema([
                                        Forms\Components\TextInput::make('email')
                                            ->email()
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('code')
                                            ->label('Code')
                                            ->required()
                                            ->maxLength(3),
                                        Forms\Components\TextInput::make('api_user_id')
                                            ->label('Staff ID'),
                                    ])->columnspan(2),
                            ]),
                    ])
                    ->columns(2),

                // Only show route permissions section when editing (not creating)
                Forms\Components\Section::make('Route Permissions')
                ->description('Configure which parts of the system this user can access')
                ->schema([
                    // Dashboard Section
                    Forms\Components\Fieldset::make('Dashboard')
                        ->schema([
                            Forms\Components\Checkbox::make('permissions.dashboard')
                                ->label('Dashboard')
                                ->helperText('Access to main dashboard')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['dashboard'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),
                        ])
                        ->columns(2),

                    // Sales Admin Section
                    Forms\Components\Fieldset::make('Sales Admin')
                        ->schema([
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

                            Forms\Components\Checkbox::make('permissions.weekly_calendar_v2')
                                ->label('Weekly Calendar')
                                ->helperText('Access to weekly calendar v2')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['weekly_calendar_v2'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            Forms\Components\Checkbox::make('permissions.all_calendar')
                                ->label('All Calendar')
                                ->helperText('Access to all calendar views')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['all_calendar'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            Forms\Components\Checkbox::make('permissions.salesperson_lead_sequence')
                                ->label('Lead Sequence')
                                ->helperText('Access to salesperson lead sequence')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['salesperson_lead_sequence'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            Forms\Components\Checkbox::make('permissions.whatsapp')
                                ->label('WhatsApp')
                                ->helperText('Access to WhatsApp integration')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['whatsapp'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            Forms\Components\Checkbox::make('permissions.sales_admin_analysis_v1')
                                ->label('Analysis - Leads')
                                ->helperText('Access to sales admin analysis v1')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['sales_admin_analysis_v1'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            Forms\Components\Checkbox::make('permissions.sales_admin_analysis_v2')
                                ->label('Analysis - Performance')
                                ->helperText('Access to sales admin analysis v2')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['sales_admin_analysis_v2'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            Forms\Components\Checkbox::make('permissions.sales_admin_analysis_v3')
                                ->label('Analysis - Action Task')
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

                            Forms\Components\Checkbox::make('permissions.whatsapp')
                                ->label('Whatsapp')
                                ->helperText('Access to Whatsapp')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['whatsapp'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),
                        ])
                        ->columns(2),

                    // Salesperson Section
                    Forms\Components\Fieldset::make('Salesperson')
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

                            Forms\Components\Checkbox::make('permissions.salesperson_calendar')
                                ->label('Salesperson Calendar')
                                ->helperText('Access to salesperson calendar')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['salesperson_calendar'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            Forms\Components\Checkbox::make('permissions.implementer_calendar')
                                ->label('Implementer Calendar')
                                ->helperText('Access to implementer calendar')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['implementer_calendar'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            Forms\Components\Checkbox::make('permissions.salesperson_appointment')
                                ->label('Appointments')
                                ->helperText('Access to salesperson appointments')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['salesperson_appointment'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            Forms\Components\Checkbox::make('permissions.site_survey_request')
                                ->label('Site Survey Request')
                                ->helperText('Access to site survey requests')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['site_survey_request'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),
                        ])
                        ->columns(2),

                    // Handover Section
                    Forms\Components\Fieldset::make('Handover')
                        ->schema([
                            Forms\Components\Checkbox::make('permissions.software_project_list')
                                ->label('Software Projects')
                                ->helperText('Access to software project list')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['software_project_list'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            Forms\Components\Checkbox::make('permissions.software_project_analysis')
                                ->label('Software Project Analysis')
                                ->helperText('Access to software project analysis')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['software_project_analysis'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

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

                            Forms\Components\Checkbox::make('permissions.onsite_repair_list')
                                ->label('Onsite Repair List')
                                ->helperText('Access to onsite repair list')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['onsite_repair_list'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            Forms\Components\Checkbox::make('permissions.technician_calendar')
                                ->label('Technician Calendar')
                                ->helperText('Access to technician calendar')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['technician_calendar'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),
                        ])
                        ->columns(2),

                    // Admin Handover Section
                    Forms\Components\Fieldset::make('Admin Handover')
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

                    // Support Section
                    Forms\Components\Fieldset::make('Support')
                        ->schema([
                            Forms\Components\Checkbox::make('permissions.call_logs')
                                ->label('Call Logs')
                                ->helperText('Access to call logs')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['call_logs'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            Forms\Components\Checkbox::make('permissions.call_categories')
                                ->label('Call Categories')
                                ->helperText('Access to call categories')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['call_categories'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            Forms\Components\Checkbox::make('permissions.overtime_calendar')
                                ->label('Overtime Calendar')
                                ->helperText('Access to overtime calendar')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['overtime_calendar'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),
                        ])
                        ->columns(2),

                    // Technician Section
                    Forms\Components\Fieldset::make('Technician')
                        ->schema([
                            Forms\Components\Checkbox::make('permissions.technician_appointment')
                                ->label('Technician Appointment')
                                ->helperText('Access to technician appointments')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['technician_appointment'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),
                        ])
                        ->columns(2),

                    // Implementer Section
                    Forms\Components\Fieldset::make('Implementer')
                        ->schema([
                            Forms\Components\Checkbox::make('permissions.implementer_audit_list')
                                ->label('Implementer Audit List')
                                ->helperText('Access to implementer audit list')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['implementer_audit_list'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            Forms\Components\Checkbox::make('permissions.implementer_request_count')
                                ->label('Request Count')
                                ->helperText('Access to implementer request count')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['implementer_request_count'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            Forms\Components\Checkbox::make('permissions.implementer_request_list')
                                ->label('Request List')
                                ->helperText('Access to implementer request list')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['implementer_request_list'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            Forms\Components\Checkbox::make('permissions.email_templates')
                                ->label('Email Templates')
                                ->helperText('Access to email templates')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['email_templates'];
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

                            Forms\Components\Checkbox::make('permissions.demo_analysis_table_form')
                                ->label('Demo Analysis Table')
                                ->helperText('Access to demo analysis table form')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['demo_analysis_table_form'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),
                        ])
                        ->columns(2),

                    // Settings Section
                    Forms\Components\Fieldset::make('Settings')
                        ->schema([
                            Forms\Components\Checkbox::make('permissions.device_models')
                                ->label('Device Models')
                                ->helperText('Manage device model settings')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['device_models'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            Forms\Components\Checkbox::make('permissions.products')
                                ->label('Products - View')
                                ->helperText('View product listings')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['products'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            Forms\Components\Checkbox::make('permissions.products_create')
                                ->label('Products - Create')
                                ->helperText('Create new products')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['products_create'];
                                        $component->state(isset($permissions[$routeName]) ? $permissions[$routeName] : false);
                                    }
                                }),

                            Forms\Components\Checkbox::make('permissions.products_edit')
                                ->label('Products - Edit')
                                ->helperText('Edit existing products')
                                ->afterStateHydrated(function ($component, $state, ?User $record) {
                                    if ($record) {
                                        $permissions = $record->route_permissions ?? [];
                                        $routeName = self::$routePermissionMap['products_edit'];
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
                        ])
                        ->columns(3),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
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
                            3 => 'Master Admin',
                            4 => 'Implementer',
                            5 => 'Team Lead Implementer',
                            6 => 'Trainer',
                            7 => 'Team Lead Trainer',
                            8 => 'Support',
                            9 => 'Technician',
                            10 => 'Finance',
                            default => 'Unknown',
                        };
                    }),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('is_active')
                    ->label('Status')
                    ->formatStateUsing(function ($state) {
                        return match ((int) $state) {
                            1 => 'Active',
                            0 => 'Deactivated',
                            default => 'Active',
                        };
                    })
                    ->color(function ($state) {
                        return match ((int) $state) {
                            1 => 'success',
                            0 => 'danger',
                            default => 'success',
                        };
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Deactivate User')
                        ->modalDescription('Are you sure you want to deactivate this user? This will disable their access and reset their password.')
                        ->modalSubmitActionLabel('Yes, deactivate')
                        ->action(function (User $record) {
                            $record->update([
                                'is_active' => 0,
                                'password' => Hash::make('3DMWMvfc%RRU'),
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('User deactivated successfully')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (User $record) => $record->is_active == 1),
                ])->icon('heroicon-o-ellipsis-vertical')
                    ->size(\Filament\Support\Enums\ActionSize::Small)
                    ->label(false)
                    ->color('primary'),
            ])
            // ->groupingColumn('division_role')
            // ->defaultGroup('division_role')
            ->groups([
                Tables\Grouping\Group::make('role_id')
                    ->getTitleFromRecordUsing(function (User $record): string {
                        // Format the role title
                        return match ((int) $record->role_id) {
                            1 => 'Lead Owner',
                            2 => 'Salesperson',
                            3 => 'Manager',
                            4 => 'Implementer',
                            5 => 'Team Lead Implementer',
                            6 => 'Trainer',
                            7 => 'Team Lead Trainer',
                            8 => 'Support',
                            9 => 'Technician',
                            10 => 'Finance',
                            default => 'Unknown Role',
                        };
                    })
                    ->getDescriptionFromRecordUsing(fn (User $record): string =>
                        $record->is_timetec_hr ? 'TimeTec HR Division' : 'Others Division')
                    ->collapsible()
            ])
            ->defaultGroup('role_id')
            ->groupingDirectionSettingHidden()
            ->groupingSettingsHidden()
            // Adding a virtual column for grouping purposes
            ->modifyQueryUsing(function (Builder $query) {
                $query->addSelect([
                    '*',
                    // Create a virtual column for division_role grouping
                    DB::raw("CONCAT(
                        CASE WHEN is_timetec_hr = 1 THEN 'TimeTec HR Division | Role: ' ELSE 'Others Division' END,
                        CASE
                            WHEN role_id = 1 THEN 'Lead Owner'
                            WHEN role_id = 2 THEN 'SalesPerson'
                            WHEN role_id = 3 THEN 'Manager'
                            WHEN role_id = 4 THEN 'Implementer'
                            WHEN role_id = 5 THEN 'Team Lead Implementer'
                            WHEN role_id = 6 THEN 'Trainer'
                            WHEN role_id = 7 THEN 'Team Lead Trainer'
                            WHEN role_id = 8 THEN 'Support'
                            WHEN role_id = 9 THEN 'Technician'
                            WHEN role_id = 10 THEN 'Finance'
                            ELSE 'Unknown'
                        END
                    ) as division_role")
                ]);
            });
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
