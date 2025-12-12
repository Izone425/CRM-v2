<?php

namespace App\Filament\Resources;

use App\Classes\Encryptor;
use App\Enums\LeadCategoriesEnum;
use App\Enums\LeadStageEnum;
use App\Enums\LeadStatusEnum;
use App\Filament\Actions\LeadActions;
use App\Filament\Resources\LeadResource\Pages;
use App\Filament\Resources\LeadResource\RelationManagers\ActivityLogRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\DemoAppointmentRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\FinanceHandoverRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\HardwareHandoverRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\HardwareHandoverV2RelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\HeadcountHandoverRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\HHTableRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\HRDFHandoverRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\ImplementerAppointmentRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\ImplementerFollowUpRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\ProformaInvoiceRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\QuotationRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\RenewalHandoverRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\RenewalQuotationRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\RepairAppointmentRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\RPTableRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\SHTableRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\SoftwareHandoverRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\SubsidiaryRelationManager;
use App\Filament\Resources\LeadResource\Tabs\AppointmentTabs;
use App\Filament\Resources\LeadResource\Tabs\ARDetailsTabs;
use App\Filament\Resources\LeadResource\Tabs\ARFollowUpTabs;
use App\Filament\Resources\LeadResource\Tabs\ARHandoverTabs;
use App\Filament\Resources\LeadResource\Tabs\ARLicenseTabs;
use App\Filament\Resources\LeadResource\Tabs\ARNotesTabs;
use App\Filament\Resources\LeadResource\Tabs\ARProformaInvoiceTabs;
use App\Filament\Resources\LeadResource\Tabs\ARQuotationTabs;
use App\Filament\Resources\LeadResource\Tabs\CommercialItemTabs;
use App\Filament\Resources\LeadResource\Tabs\CompanyTabs;
use App\Filament\Resources\LeadResource\Tabs\DataFileTabs;
use App\Filament\Resources\LeadResource\Tabs\HandoverDetailTabs;
use App\Filament\Resources\LeadResource\Tabs\HardwareHandoverTabs;
use App\Filament\Resources\LeadResource\Tabs\ImplementerAppointmentTabs;
use App\Filament\Resources\LeadResource\Tabs\ImplementerFollowUpTabs;
use App\Filament\Resources\LeadResource\Tabs\ImplementerHandoverTabs;
use App\Filament\Resources\LeadResource\Tabs\ImplementerHardwareHandoverTabs;
use App\Filament\Resources\LeadResource\Tabs\ImplementerNoteTabs;
use App\Filament\Resources\LeadResource\Tabs\ImplementerPICTabs;
use App\Filament\Resources\LeadResource\Tabs\ImplementerServiceFormTabs;
use App\Filament\Resources\LeadResource\Tabs\ImplementerSoftwareHandoverTabs;
use App\Filament\Resources\LeadResource\Tabs\LeadTabs;
use App\Filament\Resources\LeadResource\Tabs\OtherFormTabs;
use App\Filament\Resources\LeadResource\Tabs\ProformaInvoiceTabs;
use App\Filament\Resources\LeadResource\Tabs\ProjectPlanTabs;
use App\Filament\Resources\LeadResource\Tabs\ProspectDetailsTabs;
use App\Filament\Resources\LeadResource\Tabs\ProspectFollowUpTabs;
use App\Filament\Resources\LeadResource\Tabs\ProspectPICTabs;
use App\Filament\Resources\LeadResource\Tabs\QuotationTabs;
use App\Filament\Resources\LeadResource\Tabs\ReferEarnTabs;
use App\Filament\Resources\LeadResource\Tabs\RepairAppointmentTabs;
use App\Filament\Resources\LeadResource\Tabs\SalesProgressTabs;
use App\Filament\Resources\LeadResource\Tabs\SoftwareHandoverTabs;
use App\Filament\Resources\LeadResource\Tabs\SubscriberDetailsTabs;
use App\Filament\Resources\LeadResource\Tabs\SystemTabs;
use App\Filament\Resources\LeadResource\Tabs\TicketingTabs;
use App\Models\Lead;
use App\Models\Renewal;
use Carbon\Carbon;
use Fiber;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $label = 'leads';

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    public $modules;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user || ! ($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.resources.leads.index');
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public static function form(Form $form): Form
    {
        $tabs = [];

        // Get tabs from session (set by ViewLeadRecord component)
        $activeTabs = session('lead_visible_tabs', []);

        if (empty($activeTabs)) {
            // Default tabs based on user role
            $user = auth()->user();

            if (!$user) {
                $activeTabs = ['lead', 'company'];
            } elseif ($user->role_id === 1) { // Lead Owner
                $activeTabs = ['prospect_details', 'subscriber_details', 'sales_progress', 'commercial_items', 'handover_details'];
            } elseif ($user->role_id === 2) { // Salesperson
                $activeTabs = ['prospect_details', 'subscriber_details', 'sales_progress', 'commercial_items', 'handover_details'];
            } elseif ($user->role_id === 4) { // Implementer
                $activeTabs = ['implementer_software_handover', 'implementer_hardware_handover', 'implementer_pic_details',
                    'implementer_notes', 'implementer_appointment', 'implementer_follow_up',
                    'data_file', 'implementer_service_form', 'ticketing', 'project_plan', 'project_plan'];
            } elseif ($user->role_id === 5) { // Implementer
                $activeTabs = ['implementer_software_handover', 'implementer_hardware_handover','implementer_pic_details',
                    'implementer_notes', 'implementer_appointment', 'implementer_follow_up',
                    'data_file', 'implementer_service_form', 'other_form', 'ticketing', 'project_plan'];
            } elseif ($user->role_id === 9) { // Technician
                $activeTabs = ['company', 'quotation', 'repair_appointment'];
            } else { // Manager (role_id = 3) or others
                $activeTabs = [
                    'prospect_details', 'subscriber_details', 'sales_progress', 'commercial_items', 'handover_details',
                ];
            }
        }

        // Add tabs based on permissions - MAKE SURE ALL TABS ARE DEFINED
        if (in_array('lead', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('Lead')
                ->schema(LeadTabs::getSchema());
        }

        if (in_array('company', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('Company')
                ->schema(CompanyTabs::getSchema());
        }

        // Add ALL the missing tab definitions
        if (in_array('prospect_pic_details', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('HR Details')
                ->schema(ProspectPICTabs::getSchema());
        }

        if (in_array('system', $activeTabs)) {
            $tabs[] = Tab::make('System')
                ->schema(SystemTabs::getSchema());
        }

        if (in_array('refer_earn', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('Refer & Earn')
                ->schema(ReferEarnTabs::getSchema());
        }

        if (in_array('prospect_details', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('Prospect')
                ->schema(ProspectDetailsTabs::getSchema());
        }

        if (in_array('subscriber_details', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('Subscriber')
                ->schema(SubscriberDetailsTabs::getSchema());
        }

        if (in_array('sales_progress', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('Sales Progress')
                ->schema(SalesProgressTabs::getSchema());
        }

        if (in_array('appointment', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('Sales Demo')
                ->schema(AppointmentTabs::getSchema());
        }

        if (in_array('implementer_handover', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('Handover')
                ->schema(ImplementerHandoverTabs::getSchema());
        }

        if (in_array('implementer_software_handover', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('Software')
                ->schema(ImplementerSoftwareHandoverTabs::getSchema());
        }

        if (in_array('implementer_hardware_handover', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('Hardware')
                ->schema(ImplementerHardwareHandoverTabs::getSchema());
        }

        if (in_array('implementer_pic_details', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('PIC Details')
                ->schema(ImplementerPICTabs::getSchema());
        }

        if (in_array('implementer_notes', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('Notes')
                ->schema(ImplementerNoteTabs::getSchema());
        }

        if (in_array('implementer_follow_up', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('Follow Up')
                ->schema(ImplementerFollowUpTabs::getSchema());
        }

        if (in_array('implementer_appointment', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('Sessions')
                ->schema(ImplementerAppointmentTabs::getSchema());
        }

        if (in_array('project_plan', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('Project Plan')
                ->schema(ProjectPlanTabs::getSchema());
        }

        // if (in_array('data_file', $activeTabs)) {
        //     $tabs[] = Tabs\Tab::make('Data Files')
        //         ->schema(DataFileTabs::getSchema());
        // }

        // if (in_array('implementer_service_form', $activeTabs)) {
        //     $tabs[] = Tabs\Tab::make('Service Form')
        //         ->schema(ImplementerServiceFormTabs::getSchema());
        // }

        // if (in_array('other_form', $activeTabs)) {
        //     $tabs[] = Tabs\Tab::make('Other Form')
        //         ->schema(OtherFormTabs::getSchema());
        // }

        if (in_array('ar_details', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('Renewal Details')
                ->schema(ARDetailsTabs::getSchema());
        }

        if (in_array('ar_license', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('Renewal License')
                ->schema(ARLicenseTabs::getSchema());
        }

        if (in_array('ar_quotation', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('Renewal Quotation')
                ->schema(ARQuotationTabs::getSchema());
        }

        if (in_array('ar_proforma_invoice', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('Renewal PI')
                ->schema(ARProformaInvoiceTabs::getSchema());
        }

        if (in_array('ar_follow_up', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('Renewal Follow Up')
                ->schema(ARFollowUpTabs::getSchema());
        }

        if (in_array('ar_notes', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('Renewal Notes')
                ->schema(ARNotesTabs::getSchema());
        }

        if (in_array('ar_handover', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('Renewal Handover')
                ->schema(ARHandoverTabs::getSchema());
        }

        if (in_array('prospect_follow_up', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('Sales Follow Up')
                ->schema(ProspectFollowUpTabs::getSchema());
        }

        if (in_array('commercial_items', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('Commercial Details')
                ->schema(CommercialItemTabs::getSchema());
        }

        if (in_array('handover_details', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('Handover Details')
                ->schema(HandoverDetailTabs::getSchema());
        }

        // UNCOMMENT THESE IF NEEDED
        if (in_array('quotation', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('Quotation')
                ->schema(QuotationTabs::getSchema());
        }

        if (in_array('proforma_invoice', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('Proforma Invoice')
                ->schema(ProformaInvoiceTabs::getSchema());
        }

        if (in_array('software_handover', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('Software Handover')
                ->schema(SoftwareHandoverTabs::getSchema());
        }

        if (in_array('hardware_handover', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('Hardware Handover')
                ->schema(HardwareHandoverTabs::getSchema());
        }

        if (in_array('repair_appointment', $activeTabs)) {
            $tabs[] = Tabs\Tab::make('Repair Appointment')
                ->schema(RepairAppointmentTabs::getSchema());
        }

        // if (in_array('ticketing', $activeTabs)) {
        //     $tabs[] = Tabs\Tab::make('Ticketing')
        //         ->schema(TicketingTabs::getSchema());
        // }

        // Ensure at least one tab is always shown
        if (empty($tabs)) {
            $tabs[] = Tabs\Tab::make('Company')
                ->schema(CompanyTabs::getSchema());
        }

        return $form
            ->schema([
                Grid::make(1)
                    ->schema([
                        Tabs::make('lead_tabs')
                            ->tabs($tabs)
                            ->persistTabInQueryString()
                            ->columnSpan('full'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->defaultPaginationPageOption(50)
            ->paginated([10, 25, 50])
            ->modifyQueryUsing(function ($query) {
                $query->orderByRaw("FIELD(categories, 'New', 'Active', 'Inactive')")
                    ->orderBy('created_at', 'desc');

                return $query;
            })
            ->filters([
                // Filter for Lead Owner
                SelectFilter::make('lead_owner')
                    ->label('')
                    ->multiple()
                    ->options(function () {
                        // Create base options array with 'None' option
                        $options = [
                            'none' => 'None',
                        ];

                        // Get all users who are assigned as lead owners
                        $leadOwners = \App\Models\Lead::whereNotNull('lead_owner')
                            ->distinct()
                            ->pluck('lead_owner')
                            ->toArray();

                        // Find these users in the users table to get their role_id
                        $users = \App\Models\User::whereIn('name', $leadOwners)
                            ->orderBy('name')
                            ->get(['name', 'role_id'])
                            ->groupBy('role_id');

                        // Group them by role_id
                        // Add Sales Admins (role_id 1)
                        if (isset($users[1])) {
                            $salesAdmins = $users[1]->pluck('name', 'name')->toArray();
                            $options['Sales Admins'] = $salesAdmins;
                        }

                        // Add Managers (role_id 3)
                        if (isset($users[3])) {
                            $managers = $users[3]->pluck('name', 'name')->toArray();
                            $options['Managers'] = $managers;
                        }

                        // You can add other role groups if needed
                        if (isset($users[4])) {
                            $implementers = $users[4]->pluck('name', 'name')->toArray();
                            $options['Implementers'] = $implementers;
                        }

                        return $options;
                    })
                    ->placeholder('Select Lead Owner')
                    ->query(function ($query, $data) {
                        $values = collect($data)->flatten()->filter()->values();

                        if ($values->isEmpty()) {
                            return; // ✅ Don't filter if nothing selected
                        }

                        if ($values->contains('none')) {
                            $query->where(function ($q) use ($values) {
                                $q->whereNull('lead_owner');

                                $filtered = $values->reject(fn ($val) => $val === 'none');
                                if ($filtered->isNotEmpty()) {
                                    $q->orWhereIn('lead_owner', $filtered->all());
                                }
                            });
                        } else {
                            $query->whereIn('lead_owner', $values->all());
                        }
                    }),

                // Filter for Salesperson
                SelectFilter::make('salesperson')
                    ->label('')
                    ->multiple()
                    ->options([
                        'none' => 'None',
                        6 => 'Wan Amirul Muim',
                        7 => 'Yasmin',
                        8 => 'Farhanah Jamil',
                        9 => 'Joshua Ho',
                        10 => 'Abdul Aziz',
                        11 => 'Muhammad Khoirul Bariah',
                        12 => 'Vince Leong',
                        18 => 'Jonathan',
                        25 => 'Wirson Lim',
                        21 => 'Tina',
                    ])
                    ->placeholder('Select Salesperson')
                    ->query(function ($query, $data) {
                        $values = collect($data)->flatten()->filter()->values();

                        if ($values->isEmpty()) {
                            return; // ✅ Don't filter if nothing selected
                        }

                        if ($values->contains('none')) {
                            $query->where(function ($q) use ($values) {
                                $q->whereNull('salesperson');

                                $filtered = $values->reject(fn ($val) => $val === 'none');
                                if ($filtered->isNotEmpty()) {
                                    $q->orWhereIn('salesperson', $filtered->all());
                                }
                            });
                        } else {
                            $query->whereIn('salesperson', $values->all());
                        }
                    }),

                // Filter for Created At
                Filter::make('created_at')
                    ->form([
                        DateRangePicker::make('date_range')
                            ->label('')
                            ->placeholder('Select date range'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (! empty($data['date_range'])) {
                            // Parse the date range from the "start - end" format
                            [$start, $end] = explode(' - ', $data['date_range']);

                            // Ensure valid dates
                            $startDate = Carbon::createFromFormat('d/m/Y', $start)->startOfDay();
                            $endDate = Carbon::createFromFormat('d/m/Y', $end)->endOfDay();

                            // Apply the filter
                            $query->whereBetween('created_at', [$startDate, $endDate]);
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        if (! empty($data['date_range'])) {
                            // Parse the date range for display
                            [$start, $end] = explode(' - ', $data['date_range']);

                            return 'From: '.Carbon::createFromFormat('d/m/Y', $start)->format('j M Y').
                                ' To: '.Carbon::createFromFormat('d/m/Y', $end)->format('j M Y');
                        }

                        return null;
                    }),
                // Filter for Categories
                SelectFilter::make('categories')
                    ->label('')
                    ->multiple()
                    ->options(
                        collect(LeadCategoriesEnum::cases())
                            ->mapWithKeys(fn ($case) => [$case->value => ucfirst(strtolower($case->name))])
                            ->toArray()
                    )
                    ->placeholder('Select Category')
                    ->hidden(fn ($livewire) => in_array($livewire->activeTab, ['transfer', 'active', 'demo', 'follow_up', 'inactive'])),

                // Filter for Stage
                SelectFilter::make('stage')
                    ->label('')
                    ->multiple()
                    ->options(function ($livewire) {
                        // Default options from Enum
                        $defaultOptions = collect(LeadStageEnum::cases())
                            ->mapWithKeys(fn ($case) => [$case->value => $case->name])
                            ->toArray();

                        // If activeTab is "transfer", set specific options
                        if ($livewire->activeTab === 'active') {
                            return [
                                'Transfer' => 'TRANSFER',
                                'Demo' => 'DEMO',
                                'Follow Up' => 'FOLLOW UP',
                            ];
                        }

                        return $defaultOptions;
                    })
                    ->placeholder('Select Stage')
                    ->hidden(fn ($livewire) => in_array($livewire->activeTab, ['all', 'transfer', 'demo', 'follow_up', 'inactive'])),

                // Filter for Lead Status
                SelectFilter::make('lead_status')
                    ->label('')
                    ->multiple()
                    ->options(function ($livewire) {
                        // Default options from Enum
                        $defaultOptions = collect(LeadStatusEnum::cases())
                            ->mapWithKeys(fn ($case) => [$case->value => $case->name])
                            ->toArray();

                        // If activeTab is "transfer", use specific options
                        if ($livewire->activeTab === 'transfer') {
                            return [
                                'New' => 'NEW',
                                'RFQ-TRANSFER' => 'RFQ TRANSFER',
                                'Pending Demo' => 'PENDING DEMO',
                                'Demo Cancelled' => 'DEMO CANCELLED',
                                'Under Review' => 'UNDER REVIEW',
                            ];
                        }

                        if ($livewire->activeTab === 'demo') {
                            return [
                                'Demo-Assigned' => 'DEMO ASSIGNED',
                                'Demo Cancelled' => 'DEMO CANCELLED',
                            ];
                        }

                        return $defaultOptions;
                    })
                    ->placeholder('Select Lead Status')
                    ->hidden(fn ($livewire) => in_array($livewire->activeTab, ['all', 'active'])),

                Filter::make('company_name')
                    ->form([
                        TextInput::make('company_name')
                            ->hiddenLabel()
                            ->placeholder('Enter company name'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (! empty($data['company_name'])) {
                            $query->whereHas('companyDetail', function ($query) use ($data) {
                                $query->where('company_name', 'like', '%'.$data['company_name'].'%');
                            });
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        return isset($data['company_name'])
                            ? 'Company Name: '.$data['company_name']
                            : null;
                    }),

                SelectFilter::make('company_size_label') // Use the correct filter key
                    ->label('')
                    ->options([
                        'Small' => 'Small',
                        'Medium' => 'Medium',
                        'Large' => 'Large',
                        'Enterprise' => 'Enterprise',
                    ])
                    ->multiple() // Enables multi-selection
                    ->placeholder('Select Company Size')
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (! empty($data['values'])) { // 'values' stores multiple selections
                            $sizeMap = [
                                'Small' => '1-24',
                                'Small' => '20-24',
                                'Medium' => '25-99',
                                'Large' => '100-500',
                                'Enterprise' => '501 and Above',
                            ];

                            // Convert selected sizes to DB values
                            $dbValues = collect($data['values'])->map(fn ($size) => $sizeMap[$size] ?? null)->filter();

                            if ($dbValues->isNotEmpty()) {
                                $query->whereHas('companyDetail', function ($query) use ($dbValues) {
                                    $query->whereIn('company_size', $dbValues);
                                });
                            }
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        return ! empty($data['values'])
                            ? 'Company Size: '.implode(', ', $data['values'])
                            : null;
                    }),
                Filter::make('id')
                    ->form([
                        TextInput::make('id')
                            ->hiddenLabel()
                            // ->numeric()
                            ->placeholder('Enter Lead ID'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['id'])) {
                            $query->where('id', $data['id']);
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        return isset($data['id']) && $data['id'] !== null
                            ? 'ID: '.$data['id']
                            : null;
                    }),

                // Filter for Lead Code (source)
                SelectFilter::make('lead_code')
                    ->label('')
                    ->multiple()
                    ->options(function () {
                        // Get all unique lead_code values from the database
                        $leadCodes = Lead::select('lead_code')
                            ->distinct()
                            ->whereNotNull('lead_code')
                            ->pluck('lead_code')
                            ->toArray();

                        // Add a 'Null' option for leads without a code
                        $options = array_combine($leadCodes, $leadCodes);
                        $options['Null'] = 'No Lead Source';

                        return $options;
                    })
                    ->placeholder('Select Lead Source')
                    ->query(function (Builder $query, array $data) {
                        $values = collect($data)->flatten()->filter()->values();

                        if ($values->isEmpty()) {
                            return; // Don't filter if nothing selected
                        }

                        $query->where(function ($subQuery) use ($values) {
                            foreach ($values as $value) {
                                if ($value === 'Null') {
                                    $subQuery->orWhereNull('lead_code');
                                } else {
                                    $subQuery->orWhere('lead_code', $value);
                                }
                            }
                        });
                    }),

                SelectFilter::make('state')
                    ->label('')
                    ->multiple()
                    ->options(function () {
                        // List of all states in Malaysia
                        $malaysiaStates = [
                            'Johor',
                            'Kedah',
                            'Kelantan',
                            'Melaka',
                            'Negeri Sembilan',
                            'Pahang',
                            'Penang',
                            'Perak',
                            'Perlis',
                            'Sabah',
                            'Sarawak',
                            'Selangor',
                            'Terengganu',
                            'Kuala Lumpur',
                            'Labuan',
                            'Putrajaya',
                        ];

                        // // Get all unique states from the related company details
                        // $dbStates = \App\Models\CompanyDetail::select('state')
                        //     ->distinct()
                        //     ->whereNotNull('state')
                        //     ->pluck('state')
                        //     ->toArray();

                        // // Merge and deduplicate
                        // $allStates = array_unique(array_merge($malaysiaStates, $dbStates));
                        sort($malaysiaStates);

                        // Add 'None' option at the top
                        $options = ['Not applicable' => 'None'];
                        foreach ($malaysiaStates as $state) {

                            if ($state === 'Kuala Lumpur') {
                                $options['Wilayah persekutuan kuala lumpur'] = $state;
                            } elseif ($state === 'Labuan') {
                                $options['Wilayah persekutuan labuan'] = $state;
                            } elseif ($state === 'Putrajaya') {
                                $options['Wilayah persekutuan putrajaya'] = $state;
                            } elseif ($state === 'Negeri Sembilan') {
                                $options['Negeri sembilan'] = $state;
                            } else {
                                $options[$state] = $state;
                            }

                        }

                        return $options;
                    })
                    ->placeholder('Select State')
                    ->query(function (Builder $query, array $data) {
                        $values = collect($data)->flatten()->filter()->values();

                        if ($values->isEmpty()) {
                            return; // Don't filter if nothing selected
                        }

                        $query->whereHas('companyDetail', function ($q) use ($values) {
                            // If 'none' is selected, include records with null state
                            if ($values->contains('none')) {
                                $q->whereNull('state');
                                $filtered = $values->reject(fn ($val) => $val === 'none');
                                if ($filtered->isNotEmpty()) {
                                    $q->orWhereIn('state', $filtered->all());
                                }
                            } else {
                                $q->whereIn('state', $values->all());
                            }
                        });
                    })
                    ->hidden(fn ($livewire) => in_array($livewire->activeTab, ['demo'])),

                Filter::make('deal_amount')
                    ->form([
                        Grid::make(3)
                            ->schema([
                                \Filament\Forms\Components\Select::make('type')
                                    ->hiddenLabel()
                                    ->options([
                                        'above' => 'Above Amount',
                                        'below' => 'Below Amount',
                                        'between' => 'Between Amounts',
                                    ])
                                    ->required()
                                    ->reactive()
                                    ->default('above')
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('max_amount', $state !== 'between' ? null : 10000)),

                                \Filament\Forms\Components\TextInput::make('min_amount')
                                    ->hiddenLabel()
                                    ->numeric()
                                    ->required()
                                    ->placeholder('Enter minimum amount in RM'),

                                \Filament\Forms\Components\TextInput::make('max_amount')
                                    ->hiddenLabel()
                                    ->numeric()
                                    ->required()
                                    ->placeholder('Enter maximum amount in RM')
                                    ->visible(fn (callable $get) => $get('type') === 'between'),
                            ]),

                    ])
                    ->columnSpan(3)
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['type']) || empty($data['min_amount'])) {
                            return $query;
                        }

                        return match ($data['type']) {
                            'above' => $query->where('deal_amount', '>=', $data['min_amount']),
                            'below' => $query->where(function ($query) use ($data) {
                                $query->where('deal_amount', '<=', $data['min_amount'])
                                    ->orWhereNull('deal_amount');
                            }),
                            'between' => $query->whereBetween('deal_amount', [
                                $data['min_amount'],
                                $data['max_amount'] ?? $data['min_amount'],
                            ]),
                            default => $query,
                        };
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['type']) || empty($data['min_amount'])) {
                            return null;
                        }

                        return match ($data['type']) {
                            'above' => 'Deal amount ≥ RM '.number_format($data['min_amount'], 2),
                            'below' => 'Deal amount ≤ RM '.number_format($data['min_amount'], 2),
                            'between' => 'Deal amount between RM '.number_format($data['min_amount'], 2).
                                        ' and RM '.number_format($data['max_amount'] ?? $data['min_amount'], 2),
                            default => null,
                        };
                    })
                    ->hidden(fn ($livewire) => $livewire->activeTab === 'demo'),

                Filter::make('phone_number')
                    ->form([
                        TextInput::make('phone_number')
                            ->hiddenLabel()
                            ->placeholder('Enter phone number')
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (!empty($data['phone_number'])) {
                            $phoneNumber = $data['phone_number'];

                            $query->where(function ($query) use ($phoneNumber) {
                                // Search in lead's direct phone field
                                $query->where('phone', 'like', '%' . $phoneNumber . '%')
                                    // OR search in company detail's contact_no field
                                    ->orWhereHas('companyDetail', function ($query) use ($phoneNumber) {
                                        $query->where('contact_no', 'like', '%' . $phoneNumber . '%');
                                    });
                            });
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        return isset($data['phone_number']) && !empty($data['phone_number'])
                            ? 'Phone: ' . $data['phone_number']
                            : null;
                    }),

                SelectFilter::make('exclude_apollo')
                    ->label(false)
                    ->options([
                        'exclude' => 'Exclude Apollo',
                        'include' => 'Include Apollo',
                        'only' => 'Only Apollo',
                    ])
                    ->default('exclude') // ✅ Default to exclude Apollo
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? 'exclude';

                        return match($value) {
                            'exclude' => $query->where(function($q) {
                                $q->where('lead_code', '!=', 'Apollo')
                                ->orWhereNull('lead_code');
                            }),
                            'only' => $query->where('lead_code', 'Apollo'),
                            'include' => $query, // No filtering - show all
                            default => $query->where(function($q) {
                                $q->where('lead_code', '!=', 'Apollo')
                                ->orWhereNull('lead_code');
                            }),
                        };
                    })
                    ->placeholder('Select Apollo Filter'),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(6)
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->rowIndex(),
                TextColumn::make('lead_owner')
                    ->label('LEAD OWNER')
                    ->getStateUsing(fn (Lead $record) => $record->lead_owner ?? '-')
                    ->hidden(fn ($livewire) => in_array($livewire->activeTab, ['demo', 'follow_up'])),
                TextColumn::make('salesperson')
                    ->label('SALESPERSON')
                    ->getStateUsing(fn (Lead $record) => \App\Models\User::find($record->salesperson)?->name ?? '-'),
                TextColumn::make('created_at')
                    ->label('CREATED ON')
                    ->dateTime('d M Y, h:i A')
                    ->formatStateUsing(fn ($state) => Carbon::parse($state)->setTimezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A')),
                TextColumn::make('categories')
                    ->label('MAIN CATEGORY')
                    ->alignCenter()
                    // ->visible(function () {
                    //     // dd(request()->query('activeTab')); // Debug the value of activeTab
                    //     return request()->query('activeTab') === 'all';
                    // })
                    ->extraAttributes(fn ($state) => [
                        'style' => optional(LeadCategoriesEnum::tryFrom($state))->getColor()
                            ? 'background-color: '.LeadCategoriesEnum::tryFrom($state)->getColor().'; border-radius: 25px; width: 60%; height: 27px;'
                            : '',  // Fallback if the state is invalid or null
                    ])
                    ->hidden(fn ($livewire) => in_array($livewire->activeTab, ['transfer', 'active', 'demo', 'follow_up', 'inactive'])),
                TextColumn::make('stage')
                    ->label('STAGE')
                    ->alignCenter()
                    ->extraAttributes(fn ($state) => [
                        'style' => optional(LeadStageEnum::tryFrom($state))->getColor()
                            ? 'background-color: '.LeadStageEnum::tryFrom($state)->getColor().'; border-radius: 25px; width: 90%; height: 27px;'
                            : '',  // Fallback if the state is invalid or null
                    ])
                    ->hidden(fn ($livewire) => in_array($livewire->activeTab, ['all', 'transfer', 'demo', 'follow_up', 'inactive'])),
                TextColumn::make('lead_status')
                    ->label('LEAD STATUS')
                    ->alignCenter()
                    ->extraAttributes(fn ($state) => [
                        'style' => optional(LeadStatusEnum::tryFrom($state))->getColor()
                            ? 'background-color: '.LeadStatusEnum::tryFrom($state)->getColor().';'.
                              'border-radius: 25px; width: 90%; height: 27px;'.
                              (in_array($state, ['Hot', 'Warm', 'Cold', 'RFQ-Transfer']) ? 'color: white;' : '') // Change text color to white for specific statuses
                            : '',  // Fallback if the state is invalid or null
                    ])
                    ->hidden(fn ($livewire) => in_array($livewire->activeTab, ['all', 'active'])),
                TextColumn::make('company_name')
                    ->wrap()
                    ->label('COMPANY NAME')
                    ->weight(FontWeight::Bold)
                    ->getStateUsing(fn (Lead $record) => $record->companyDetail?->company_name ?? '-'),
                TextColumn::make('from_lead_created')
                    ->label('FROM LEAD CREATED')
                    ->getStateUsing(fn (Lead $record) => $record->created_at
                            ? Carbon::parse($record->created_at)->diffInDays(Carbon::now()).' days'
                            : 'N/A'
                    )
                    ->extraAttributes(fn ($state) => [
                        'style' => optional(LeadStageEnum::tryFrom($state))->getColor()
                            ? 'background-color: '.LeadStageEnum::tryFrom($state)->getColor().'; border-radius: 25px; width: 70%;'
                            : '', // Fallback if the state is invalid or null
                    ])
                    ->hidden(fn ($livewire) => in_array($livewire->activeTab, ['all', 'transfer', 'demo', 'active', 'inactive'])),
                TextColumn::make('appointment_date')
                    ->label('APPOINTMENT DATE')
                    ->getStateUsing(fn (Lead $record) => $record->demoAppointment->first()
                            ? sprintf(
                                '%s, %s',
                                Carbon::parse($record->demoAppointment->first()->date)->format('d M Y'),
                                Carbon::parse($record->demoAppointment->first()->start_time)->format('h:i A'),
                            )
                            : '-'
                    )
                    ->hidden(fn ($livewire) => in_array($livewire->activeTab, ['all', 'active', 'transfer', 'follow_up', 'inactive'])),
                TextColumn::make('day_taken_to_close_deal')
                    ->label('IN-ACTIVE DAYS')
                    ->getStateUsing(fn (Lead $record) => $record->lead_status === 'Closed'
                        ? sprintf(
                            '%s days',
                            Carbon::parse($record->created_at)->diffInDays(Carbon::parse($record->updated_at))
                        ) : '-'
                    )
                    ->hidden(fn ($livewire) => in_array($livewire->activeTab, ['all', 'active', 'transfer', 'follow_up', 'demo'])),
                TextColumn::make('from_new_demo')
                    ->label('FROM NEW DEMO')
                    ->getStateUsing(fn (Lead $record) => ($days = $record->calculateDaysFromNewDemo()) !== '-'
                            ? $days.' days'
                            : $days
                    )
                    ->extraAttributes(fn ($state) => [
                        'style' => optional(LeadStageEnum::tryFrom($state))->getColor()
                            ? 'background-color: '.LeadStageEnum::tryFrom($state)->getColor().'; border-radius: 25px; width: 70%;'
                            : '',  // Fallback if the state is invalid or null
                    ])
                    ->hidden(fn ($livewire) => in_array($livewire->activeTab, ['all', 'transfer', 'demo', 'active', 'inactive'])),
                TextColumn::make('company_size_label')
                    ->label('COMPANY SIZE'),
                TextColumn::make('company_size')
                    ->label('HEADCOUNT')
                    ->hidden(fn ($livewire) => in_array($livewire->activeTab, ['inactive'])),
            ])
            // ->defaultSort('created_at', 'asc')
            // ->defaultSort('categories', 'New')
            ->defaultSort(function (Builder $query): Builder {
                return $query
                    ->orderBy('categories', 'asc') // Sort 'New -> Active -> Inactive' first
                    ->orderBy('updated_at', 'desc');
            })
            ->bulkActions([
                \Filament\Tables\Actions\BulkAction::make('changeLeadOwner')
                    ->label('Change Lead Owner')
                    ->icon('heroicon-o-user-circle')
                    ->visible(fn () => auth()->user()?->role_id === 3)
                    ->form([
                        \Filament\Forms\Components\Select::make('lead_owner')
                            ->label('New Lead Owner')
                            ->options(
                                \App\Models\User::where('role_id', 1)->pluck('name', 'name')->toArray()
                            )
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (\Illuminate\Support\Collection $records, array $data): void {
                        /** @var \Illuminate\Support\Collection<int, \App\Models\Lead> $records */
                        foreach ($records as $lead) {
                            /** @var \App\Models\Lead $lead */
                            $lead->update([
                                'lead_owner' => $data['lead_owner'],
                            ]);

                            // Update latest activity log description
                            $latestActivityLog = \App\Models\ActivityLog::where('subject_id', $lead->id)
                                ->orderByDesc('created_at')
                                ->first();

                            if ($latestActivityLog) {
                                $latestActivityLog->update([
                                    'description' => 'Lead Owner changed by Manager',
                                ]);
                            }

                            // Optional: Create new activity log entry
                            activity()
                                ->causedBy(auth()->user())
                                ->performedOn($lead)
                                ->log('Bulk lead owner changed to: '.$data['lead_owner']);
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Lead Owner Updated')
                            ->success()
                            ->body(count($records).' leads updated with new Lead Owner.')
                            ->send();
                    }),

                \Filament\Tables\Actions\BulkAction::make('changeSalesperson')
                    ->label('Change Salesperson')
                    ->icon('heroicon-o-user-group')
                    ->visible(fn () => auth()->user()?->role_id === 3)
                    ->form([
                        \Filament\Forms\Components\Select::make('salesperson')
                            ->label('New Salesperson')
                            ->options(
                                \App\Models\User::where('role_id', 2)->pluck('name', 'id')->toArray()
                            )
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (\Illuminate\Support\Collection $records, array $data): void {
                        /** @var \Illuminate\Support\Collection<int, \App\Models\Lead> $records */
                        $salespersonId = $data['salesperson'];
                        $salespersonName = \App\Models\User::find($salespersonId)?->name ?? 'Unknown';

                        foreach ($records as $lead) {
                            /** @var \App\Models\Lead $lead */
                            // Store old salesperson for logging
                            $oldSalespersonId = $lead->salesperson;
                            $oldSalespersonName = \App\Models\User::find($oldSalespersonId)?->name ?? 'None';

                            // Update the salesperson and assigned date
                            $lead->update([
                                'salesperson' => $salespersonId,
                                'salesperson_assigned_date' => now(),
                            ]);

                            // Optional: Create activity log entry
                            activity()
                                ->causedBy(auth()->user())
                                ->performedOn($lead)
                                ->log('Bulk changed salesperson from '.$oldSalespersonName.' to '.$salespersonName);
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Salesperson Updated')
                            ->success()
                            ->body(count($records).' leads updated with new Salesperson: '.$salespersonName)
                            ->send();
                    }),

                \Filament\Tables\Actions\BulkAction::make('changeLeadSource')
                    ->label('Change Lead Source')
                    ->icon('heroicon-o-tag')
                    ->visible(fn () => auth()->user()?->role_id === 3)
                    ->form([
                        \Filament\Forms\Components\Select::make('lead_code')
                            ->label('New Lead Source')
                            ->options(function () {
                                // Get all unique lead_code values from the database
                                $leadCodes = \App\Models\Lead::select('lead_code')
                                    ->distinct()
                                    ->whereNotNull('lead_code')
                                    ->pluck('lead_code')
                                    ->toArray();

                                // Create options from the unique codes
                                return array_combine($leadCodes, $leadCodes);
                            })
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (\Illuminate\Support\Collection $records, array $data): void {
                        /** @var \Illuminate\Support\Collection<int, \App\Models\Lead> $records */
                        $newLeadCode = $data['lead_code'];

                        foreach ($records as $lead) {
                            /** @var \App\Models\Lead $lead */
                            $oldLeadCode = $lead->lead_code ?? 'None';

                            $lead->update([
                                'lead_code' => $newLeadCode,
                            ]);

                            // Optional: Create activity log entry
                            activity()
                                ->causedBy(auth()->user())
                                ->performedOn($lead)
                                ->log('Bulk changed lead source from "'.$oldLeadCode.'" to "'.$newLeadCode.'"');
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Lead Source Updated')
                            ->success()
                            ->body(count($records).' leads updated with new Lead Source: '.$newLeadCode)
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                LeadActions::getTimeSinceCreationAction(),
                // LeadActions::getAssignToMeAction(),
                LeadActions::getViewAction(),
                LeadActions::getAddDemoAction()
                    ->visible(fn (Lead $record) => $record->categories === 'Active'
                        && ! is_null($record->lead_owner)
                        && is_null($record->salesperson)
                    ),
                LeadActions::getAddRFQ()
                    ->visible(fn (Lead $record) => $record->categories === 'Active'
                        && ! is_null($record->lead_owner)
                        && is_null($record->salesperson)
                    ),

                LeadActions::getAddFollowUp()
                    ->visible(fn (Lead $record) => $record->categories === 'Active'
                        && ! is_null($record->lead_owner)
                    ),

                LeadActions::getAddAutomation()
                    ->visible(fn (Lead $record) => $record->categories === 'Active'
                        && ! is_null($record->lead_owner)
                        && is_null($record->salesperson)
                    ),

                LeadActions::getArchiveAction()
                    ->visible(fn (Lead $record) => $record->categories === 'Active'
                        && ! is_null($record->lead_owner)
                    ),
                LeadActions::getChangeLeadOwnerAction(),

                LeadActions::getRequestChangeLeadOwnerAction(),

                LeadActions::getViewReferralDetailsAction(),

                // Tables\Actions\Action::make('handoverMapping')
                //     ->label(__('Handover Mapping'))
                //     ->visible(fn (Lead $lead) => in_array(auth()->user()->role_id, [4, 5, 3])
                //     )
                //     ->modalHeading('Map Lead to Software Handover')
                //     ->color('danger')
                //     ->icon('heroicon-o-link')
                //     ->form([
                //         Placeholder::make('')
                //             ->content(__('Update the company name, mark this lead as Closed, and link to a software handover.')),

                //         TextInput::make('company_name')
                //             ->label('Company Name')
                //             ->required()
                //             ->default(fn (Lead $record) => $record->companyDetail?->company_name ?? '')
                //             ->reactive()
                //             ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),

                //         Select::make('status')
                //             ->label('INACTIVE STATUS')
                //             ->options(function () {
                //                 // Create base options array
                //                 $options = [
                //                     'Closed' => 'Closed',
                //                 ];

                //                 return $options;
                //             })
                //             ->default('Closed')
                //             ->required()
                //             ->reactive(),

                //         Select::make('software_handover_id')
                //             ->label('Link Software Handover')
                //             ->options(function (callable $get, $livewire) {
                //                 $companyName = $get('company_name');

                //                 if (! $companyName) {
                //                     return [];
                //                 }

                //                 // Find orphaned software handovers with matching company name
                //                 return \App\Models\SoftwareHandover::whereNull('lead_id')
                //                     ->where('company_name', 'LIKE', "%{$companyName}%")
                //                     ->get()
                //                     ->mapWithKeys(function ($handover) {
                //                         $date = $handover->created_at->format('d M Y');
                //                         $implementer = $handover->implementer ?? 'Unknown';

                //                         return [$handover->id => "#{$handover->id} - {$handover->company_name} ({$implementer} - {$date})"];
                //                     })
                //                     ->toArray();
                //             })
                //             ->searchable()
                //             ->placeholder('Select handover to link')
                //             ->live(),
                //     ])
                //     ->action(function (Lead $record, array $data) {
                //         $lead = $record;

                //         // First, update the company name in the company details
                //         if ($lead->companyDetail) {
                //             $lead->companyDetail->update([
                //                 'company_name' => $data['company_name'],
                //             ]);
                //         }

                //         // Update the lead status to Closed
                //         $updateData = [
                //             'categories' => 'Inactive',
                //             'lead_status' => 'Closed',
                //             'stage' => null,
                //         ];

                //         $lead->update($updateData);

                //         // Link to software handover if selected
                //         if (! empty($data['software_handover_id'])) {
                //             $handoverId = $data['software_handover_id'];
                //             $handover = \App\Models\SoftwareHandover::find($handoverId);

                //             if ($handover) {
                //                 // Update the software handover with the lead_id
                //                 $handover->update([
                //                     'lead_id' => $lead->id,
                //                 ]);

                //                 // Log this action
                //                 activity()
                //                     ->causedBy(auth()->user())
                //                     ->performedOn($lead)
                //                     ->log('Software handover #'.$handoverId.' linked to this lead');
                //             }
                //         }

                //         // Log activity
                //         $latestActivityLog = \App\Models\ActivityLog::where('subject_id', $lead->id)
                //             ->orderByDesc('created_at')
                //             ->first();

                //         if ($latestActivityLog) {
                //             $latestActivityLog->update([
                //                 'description' => 'Company name updated to '.$data['company_name'].' and linked to software handover',
                //             ]);
                //         }

                //         \Filament\Notifications\Notification::make()
                //             ->title('Handover Mapping Complete')
                //             ->success()
                //             ->body('You have successfully updated the company name and linked the software handover.')
                //             ->send();
                //     }),

                Tables\Actions\Action::make('resetLead')
                    ->label(__('Reset Lead'))
                    ->color('danger')
                    ->icon('heroicon-o-shield-exclamation')
                    ->visible(fn (Lead $record) => auth()->user()->role_id === 3 && $record->id === 7581
                    )
                    ->action(function (Lead $record) {
                        // Reset the specific lead record
                        $record->update([
                            'categories' => 'New',
                            'stage' => 'New',
                            'lead_status' => 'None',
                            'lead_owner' => null,
                            'remark' => null,
                            'follow_up_date' => null,
                            'salesperson' => null,
                            'salesperson_assigned_date' => null,
                            'demo_appointment' => null,
                            'rfq_followup_at' => null,
                            'follow_up_counter' => 0,
                            'follow_up_needed' => 0,
                            'follow_up_count' => 0,
                            'call_attempt' => 0,
                            'done_call' => 0,
                        ]);

                        // Delete all related data
                        DB::table('appointments')->where('lead_id', $record->id)->delete();
                        DB::table('system_questions')->where('lead_id', $record->id)->delete();
                        DB::table('bank_details')->where('lead_id', $record->id)->delete();
                        DB::table('activity_logs')->where('subject_id', $record->id)->delete();
                        DB::table('quotations')->where('lead_id', $record->id)->delete();

                        // Send a notification after resetting the lead
                        Notification::make()
                            ->title('Lead Reset Successfully')
                            ->success()
                            ->send();
                    }),
                ])
                ->button(),
                // ->visible(fn () => in_array(auth()->user()->role_id, [1, 3])),
                Tables\Actions\ViewAction::make()
                ->url(fn ($record) => route('filament.admin.resources.leads.view', [
                    'record' => Encryptor::encrypt($record->id),
                    ]))
                ->label('') // Remove the label
                ->extraAttributes(['class' => 'hidden']),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                // Get the current user and their role
                $user = auth()->user();
                $roleId = $user->role_id;
                $userName = $user->name;
                $userId = $user->id;

                // Check if the user is an admin (role_id = 1)
                if ($roleId === 2) {
                    $query->where('salesperson', $userId)
                        ->whereIn('categories', ['Inactive', 'Active', 'New']); // Add more statuses if needed
                }

                // elseif ($roleId === 1) {
                //     // Salespeople (role_id = 2) can see only their records or those without a lead owner
                //     $query->where(function ($query) use ($userName) {
                //         $query->where('lead_owner', $userName)
                //               ->orWhereNull('lead_owner');
                //     });
                // }
            });

    }

    public static function getRelations(): array
    {
        return [
            ActivityLogRelationManager::class,
            DemoAppointmentRelationManager::class,
            QuotationRelationManager::class,
            ProformaInvoiceRelationManager::class,
            SoftwareHandoverRelationManager::class,
            HardwareHandoverRelationManager::class,
            RepairAppointmentRelationManager::class,
            ImplementerAppointmentRelationManager::class,
            ImplementerFollowUpRelationManager::class,
            SHTableRelationManager::class,
            HHTableRelationManager::class,
            RPTableRelationManager::class,
            SubsidiaryRelationManager::class,
            RenewalQuotationRelationManager::class,
            HRDFHandoverRelationManager::class,
            HeadcountHandoverRelationManager::class,
            HardwareHandoverV2RelationManager::class,
            FinanceHandoverRelationManager::class,
            RenewalHandoverRelationManager::class,
        ];
    }

    public static function getLeadCount(): int
    {
        // Start the Lead query
        $query = Lead::query();

        // Get the current user and their role
        $user = auth()->user();
        $roleId = $user->role_id;
        $userName = $user->name;

        // Apply filters based on role
        if ($roleId === 2) {
            // Role 2: Filter by salesperson or inactive category
            $query->where(function ($query) use ($user) {
                $query->where('salesperson', $user->id)
                    ->orWhere('categories', 'Inactive');
            });
        }

        // Return the count based on the modified query
        return $query->count();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'view' => Pages\ViewLeadRecord::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    // public static function canCreate(): bool
    // {
    //     return auth()->user()->role_id !== 2;
    // }
}
