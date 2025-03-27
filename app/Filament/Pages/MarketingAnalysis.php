<?php
namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Lead;
use App\Models\PublicHoliday;
use App\Models\UserLeave;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\Auth;

class MarketingAnalysis extends Page
{
    use InteractsWithPageTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.pages.marketing-analysis';
    protected static ?string $navigationLabel = 'Marketing Analysis';
    protected static ?string $title = '';
    protected static ?int $navigationSort = 8;
    protected static ?string $navigationGroup = 'Analysis';

    public $users;
    public $selectedUser;
    // public $selectedMonth;
    public $selectedLeadCode;
    public $leadCodes;

    public $totalAppointments = 0;
    public $typeData = [];

    public $totalNewAppointments = 0;
    public $newDemoCompanySizeData = [];

    public $totalWebinarAppointments = 0;
    public $webinarDemoCompanySizeData = [];

    public $totalNewAppointmentsByLeadStatus = 0;
    public $newDemoLeadStatusData = [];

    public $totalWebinarAppointmentsByLeadStatus = 0;
    public $webinarDemoLeadStatusData = [];
    public $companySizeData = [];
    public $demoCompanySizeData = [];
    public $demoTypeData = [];
    public $demoRateBySize = [];
    public $webinarDemoAverages = [];
    public $selectedLeadOwner;
    public $leadOwners;

    public $days;
    public Carbon $currentDate;
    public $startDate;
    public $endDate;

    public $utmCampaign;
    public $utmAdgroup;
    public $utmTerm;
    public $utmMatchtype;
    public $referrername;
    public $device;
    public $utmCreative;
    public $showUtmFilters = false;

    public $categoryData = [];
    public $totalLeadsByCategory = 0;

    public $stageData = [];
    public $totalLeadsByStage = 0;

    public $leadStatusData = [];
    public $totalLeadStatus = 0;

    public $closeWonAmount = 0;
    public $closedDealsCount = 0;
    public $monthlyDealAmounts = [];

    public static function canAccess(): bool
    {
        return auth()->user()->role_id == '3'; // Hides the resource from all users
    }

    public function mount()
    {
        $authUser = auth()->user();
        $this->currentDate = Carbon::now();
        $this->startDate = session('startDate', $this->currentDate->copy()->startOfMonth()->toDateString());
        $this->endDate = session('endDate', $this->currentDate->toDateString());

        // Fetch only Salespersons (role_id = 2)
        $this->users = User::where('role_id', 2)->get();
        $this->leadOwners = User::where('role_id', 1)->get();

        // Fetch unique lead codes for dropdown options
        $this->leadCodes = Lead::select('lead_code')->distinct()->pluck('lead_code')->toArray();

        // Set default selected user based on role
        if ($authUser->role_id == 1) {
            $this->selectedUser = session('selectedUser', null);
        } elseif ($authUser->role_id == 2) {
            $this->selectedUser = $authUser->id; // Salesperson can only see their data
        }

        // Set default selected month
        // $this->selectedMonth = session('selectedMonth', $this->currentDate->format('Y-m'));

        // Set default selected lead code
        $this->selectedLeadCode = session('selectedLeadCode', null);

        // Store in session
        session(['selectedUser' => $this->selectedUser, 'selectedLeadCode' => $this->selectedLeadCode]);

        $this->selectedLeadOwner = session('selectedLeadOwner', null);
        session(['selectedLeadOwner' => $this->selectedLeadOwner]);

        // Fetch initial appointment data
        $this->refreshDashboardData();
    }

    public function updatedSelectedUser($userId)
    {
        $this->selectedUser = $userId;
        session(['selectedUser' => $userId]);
        $this->refreshDashboardData();
    }

    public function updatedStartDate($value)
    {
        $this->startDate = $value;
        session(['startDate' => $value]);
        $this->refreshDashboardData();
    }

    public function updatedEndDate($value)
    {
        $this->endDate = $value;
        session(['endDate' => $value]);
        $this->refreshDashboardData();
    }

    public function updatedSelectedLeadCode($leadCode)
    {
        $this->selectedLeadCode = $leadCode;
        session(['selectedLeadCode' => $leadCode]);
        $this->refreshDashboardData();
    }

    public function updatedSelectedLeadOwner($value)
    {
        session(['selectedLeadOwner' => $value]);
        $this->refreshDashboardData();
    }

    public function refreshDashboardData()
    {
        $this->fetchLeads();
        $this->fetchLeadsDemo();
        $this->getLeadTypeCounts();
        $this->fetchLeadsDemoType();
        $this->calculateFilteredDemoRateByCompanySize();
        $this->fetchCloseWonAmount();
        $this->fetchMonthlyDealAmounts();
        $this->fetchLeadStatusSummary();
        $this->calculateWebinarDemoAverages();
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, [
            'utmCampaign',
            'utmAdgroup',
            'utmTerm',
            'utmMatchtype',
            'referrername',
            'device',
            'utmCreative',
        ])) {
            $this->refreshDashboardData();
        }
    }

    public function toggleUtmFilters()
    {
        $this->showUtmFilters = !$this->showUtmFilters;
    }

    public function getLeadIdsFromUtmFilters()
    {
        $query = \App\Models\UtmDetail::query();

        if (!empty($this->utmCampaign)) {
            $query->where('utm_campaign', 'like', '%' . $this->utmCampaign . '%');
        }
        if (!empty($this->utmAdgroup)) {
            $query->where('utm_adgroup', 'like', '%' . $this->utmAdgroup . '%');
        }
        if (!empty($this->utmTerm)) {
            $query->where('utm_term', 'like', '%' . $this->utmTerm . '%');
        }
        if (!empty($this->utmMatchtype)) {
            $query->where('utm_matchtype', 'like', '%' . $this->utmMatchtype . '%');
        }
        if (!empty($this->referrername)) {
            $query->where('referrername', 'like', '%' . $this->referrername . '%');
        }
        if (!empty($this->device)) {
            $query->where('device', 'like', '%' . $this->device . '%');
        }
        if (!empty($this->utmCreative)) {
            $query->where('utm_creative', 'like', '%' . $this->utmCreative . '%');
        }
        return $query->pluck('lead_id')->toArray();
    }

    public function fetchLeadStatusSummary()
    {
        $user = Auth::user();

        $utmLeadIds = $this->getLeadIdsFromUtmFilters();
        $utmFilterApplied = $this->utmCampaign || $this->utmAdgroup || $this->utmTerm || $this->utmMatchtype || $this->referrername || $this->device || $this->utmCreative;

        $activeStatuses = [
            'None', 'New', 'RFQ-Transfer', 'Pending Demo', 'Under Review',
            'Demo Cancelled', 'Demo-Assigned', 'RFQ-Follow Up', 'Hot', 'Warm', 'Cold'
        ];

        $otherStatuses = ['Closed', 'No Response', 'Junk', 'On Hold', 'Lost'];

        $allStatuses = array_merge($activeStatuses, $otherStatuses);

        $query = Lead::query();

        if ($utmFilterApplied && !empty($utmLeadIds)) {
            $query->whereIn('id', $utmLeadIds);
        }

        if (!empty($this->selectedLeadOwner)) {
            $ownerName = User::where('id', $this->selectedLeadOwner)->value('name');
            $query->where('lead_owner', $ownerName);
        }

        if (in_array($user->role_id, [1, 3]) && $this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        if ($user->role_id === 2) {
            $query->where('salesperson', $user->id);
        }

        if (!empty($this->startDate) && !empty($this->endDate)) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        $query->where(function ($q) use ($activeStatuses, $otherStatuses) {
            $q->where(function ($sub) use ($activeStatuses) {
                $sub->where('categories', 'Active')
                    ->whereIn('lead_status', $activeStatuses);
            })->orWhereIn('lead_status', $otherStatuses);
        });

        // ✅ Total Count
        $this->totalLeadStatus = (clone $query)->count();

        // ✅ Status-wise count
        $statusCounts = $query
            ->select('lead_status', DB::raw('COUNT(*) as total'))
            ->groupBy('lead_status')
            ->pluck('total', 'lead_status')
            ->toArray();

        // ✅ Fill missing ones with 0
        $this->leadStatusData = array_merge(array_fill_keys($allStatuses, 0), $statusCounts);
    }

    public function getLeadTypeCounts()
    {
        $user = Auth::user();
        $query = Lead::query();

        $utmLeadIds = $this->getLeadIdsFromUtmFilters();
        $utmFilterApplied = $this->utmCampaign || $this->utmAdgroup || $this->utmTerm || $this->utmMatchtype || $this->referrername || $this->device || $this->utmCreative;

        if ($utmFilterApplied && !empty($utmLeadIds)) {
            $query->whereIn('id', $utmLeadIds);
        }

        // Filter by selected user
        if (in_array($user->role_id, [1, 3]) && $this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        if ($user->role_id == 2) {
            $query->where('salesperson', $user->id);
        }

        if (!empty($this->selectedLeadOwner)) {
            $ownerName = User::where('id', $this->selectedLeadOwner)->value('name');
            $query->where('lead_owner', $ownerName);
        }

        if (!empty($this->startDate) && !empty($this->endDate)) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        // Get all relevant leads
        $leads = $query->get();

        // Group and count by specific lead codes
        return [
            'Facebook Ads'       => $leads->where('lead_code', 'Facebook Ads')->count(),
            'Google AdWords'     => $leads->where('lead_code', 'Google AdWords')->count(),
            'Refer & Earn'       => $leads->where('lead_code', 'Refer & Earn')->count(),
            'WhatsApp - TimeTec' => $leads->where('lead_code', 'WhatsApp - TimeTec')->count(),
            'Facebook Messenger' => $leads->where('lead_code', 'Facebook Messenger')->count(),
            'Website'            => $leads->where('lead_code', 'Website')->count(),
            'Crm'                => $leads->where('lead_code', 'CRM')->count(),
            'Criteo'             => $leads->where('lead_code', 'Criteo')->count(),
            'Null'               => $leads->where('lead_code', null)->count(),
        ];
    }

    public function fetchLeads()
    {
        $user = Auth::user();
        $query = Lead::query();

        // Filter by UTM fields if any are filled
        $utmLeadIds = $this->getLeadIdsFromUtmFilters();
        $utmFilterApplied = $this->utmCampaign || $this->utmAdgroup || $this->utmTerm || $this->utmMatchtype || $this->referrername || $this->device || $this->utmCreative;

        if ($utmFilterApplied && !empty($utmLeadIds)) {
            $query->whereIn('id', $utmLeadIds);
        }

        if (!empty($this->selectedLeadOwner)) {
            $ownerName = User::where('id', $this->selectedLeadOwner)->value('name');
            $query->where('lead_owner', $ownerName);
        }

        // If Lead Owner selects a salesperson, filter by that salesperson
        if (in_array($user->role_id, [1, 3]) && $this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        // If Salesperson, show only their assigned leads
        if ($user->role_id == 2) {
            $query->where('salesperson', $user->id);
        }

        if (!empty($this->startDate) && !empty($this->endDate)) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        $query->where(function ($q) {
            $q->whereNotIn('lead_status', ['Junk', 'On Hold']) // Exclude Junk & On Hold
            ->orWhere(function ($sub) {
                $sub->where('lead_status', 'Lost')
                    ->whereNotNull('demo_appointment'); // Allow Lost only if demo is present
            });
        });

        // Fetch filtered leads
        $leads = $query->get();

        // Fetch company size data
        $defaultCompanySizes = [
            'Small' => 0,
            'Medium' => 0,
            'Large' => 0,
            'Enterprise' => 0,
        ];

        $companySizeCounts = $leads
            ->whereNotNull('company_size_label')
            ->groupBy('company_size_label')
            ->map(fn($group) => $group->count())
            ->toArray();

        $this->companySizeData = array_merge($defaultCompanySizes, $companySizeCounts);
    }

    public function fetchLeadsDemo()
    {
        $user = Auth::user();
        $query = Lead::query();

        // UTM filter
        $utmLeadIds = $this->getLeadIdsFromUtmFilters();
        $utmFilterApplied = $this->utmCampaign || $this->utmAdgroup || $this->utmTerm || $this->utmMatchtype || $this->referrername || $this->device || $this->utmCreative;

        if ($utmFilterApplied && !empty($utmLeadIds)) {
            $query->whereIn('id', $utmLeadIds);
        }

        if (!empty($this->selectedLeadOwner)) {
            $ownerName = User::where('id', $this->selectedLeadOwner)->value('name');
            $query->where('lead_owner', $ownerName);
        }

        // Role-based filtering
        if (in_array($user->role_id, [1, 3]) && $this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        if ($user->role_id == 2) {
            $query->where('salesperson', $user->id);
        }

        if (!empty($this->startDate) && !empty($this->endDate)) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        // ✅ Custom status filtering
        $query->where(function ($q) {
            $q->whereIn('lead_status', [
                'Closed',
                'Demo-Assigned',
                'RFQ-Follow Up',
                'Hot',
                'Warm',
                'Cold',
            ])
            ->orWhere(function ($sub) {
                $sub->whereIn('lead_status', ['Lost', 'No Response'])
                    ->whereNotNull('demo_appointment');
            });
        });

        // Get filtered leads
        $leads = $query->get();

        // Group by company size
        $defaultCompanySizes = [
            'Small' => 0,
            'Medium' => 0,
            'Large' => 0,
            'Enterprise' => 0,
        ];

        $companySizeCounts = $leads
            ->whereNotNull('company_size_label')
            ->groupBy('company_size_label')
            ->map(fn($group) => $group->count())
            ->toArray();

            $this->demoCompanySizeData = array_merge($defaultCompanySizes, $companySizeCounts);
    }

    public function fetchLeadsDemoType()
    {
        $user = Auth::user();
        $query = Lead::query();

        // UTM filters
        $utmLeadIds = $this->getLeadIdsFromUtmFilters();
        $utmFilterApplied = $this->utmCampaign || $this->utmAdgroup || $this->utmTerm || $this->utmMatchtype || $this->referrername || $this->device || $this->utmCreative;

        if ($utmFilterApplied && !empty($utmLeadIds)) {
            $query->whereIn('id', $utmLeadIds);
        }

        if (!empty($this->selectedLeadOwner)) {
            $ownerName = User::where('id', $this->selectedLeadOwner)->value('name');
            $query->where('lead_owner', $ownerName);
        }

        if (in_array($user->role_id, [1, 3]) && $this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        if ($user->role_id == 2) {
            $query->where('salesperson', $user->id);
        }

        if (!empty($this->startDate) && !empty($this->endDate)) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        // ✅ Custom status filtering
        $query->where(function ($q) {
            $q->whereIn('lead_status', [
                'Closed',
                'Demo-Assigned',
                'RFQ-Follow Up',
                'Hot',
                'Warm',
                'Cold',
            ])
            ->orWhere(function ($sub) {
                $sub->whereIn('lead_status', ['Lost', 'No Response'])
                    ->whereNotNull('demo_appointment');
            });
        });

        // Fetch leads + demo data
        $leads = $query->with('demoAppointment')->get();

        // ✅ Count New Demo and Webinar Demo
        $newDemoCount = 0;
        $webinarKeys = [];

        foreach ($leads as $lead) {
            foreach ($lead->demoAppointment as $demo) {
                if ($demo->status === 'Cancelled') {
                    continue;
                }

                if ($demo->type === 'NEW DEMO') {
                    $newDemoCount++;
                } elseif ($demo->type === 'WEBINAR DEMO') {
                    $key = $demo->date . '|' . $demo->start_time . '|' . $demo->end_time . '|' . $lead->salesperson;
                    $webinarKeys[$key] = true;
                }
            }
        }

        // ✅ Set data for Blade
        $this->demoTypeData = [
            'New Demo' => $newDemoCount,
            'Webinar Demo' => count($webinarKeys),
        ];
    }

    public function calculateFilteredDemoRateByCompanySize()
    {
        $user = Auth::user();
        $query = Lead::query();

        // UTM filters
        $utmLeadIds = $this->getLeadIdsFromUtmFilters();
        $utmFilterApplied = $this->utmCampaign || $this->utmAdgroup || $this->utmTerm || $this->utmMatchtype || $this->referrername || $this->device || $this->utmCreative;

        if ($utmFilterApplied && !empty($utmLeadIds)) {
            $query->whereIn('id', $utmLeadIds);
        }

        if (!empty($this->selectedLeadOwner)) {
            $ownerName = User::where('id', $this->selectedLeadOwner)->value('name');
            $query->where('lead_owner', $ownerName);
        }

        if (in_array($user->role_id, [1, 3]) && $this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        if ($user->role_id == 2) {
            $query->where('salesperson', $user->id);
        }

        if (!empty($this->startDate) && !empty($this->endDate)) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        // Fetch all filtered leads
        $leads = $query->get();

        // Define sizes
        $defaultCompanySizes = [
            'Small' => 0,
            'Medium' => 0,
            'Large' => 0,
            'Enterprise' => 0,
        ];

        // 🟡 Total Leads by Company Size
        $companySizeCounts = $leads
            ->whereNotNull('company_size_label')
            ->groupBy('company_size_label')
            ->map(fn($group) => $group->count())
            ->toArray();

        $this->companySizeData = array_merge($defaultCompanySizes, $companySizeCounts);

        // 🔵 Demo Leads by Company Size (apply status filter)
        $demoLeads = $leads->filter(function ($lead) {
            return in_array($lead->lead_status, [
                'Closed',
                'Demo-Assigned',
                'RFQ-Follow Up',
                'Hot',
                'Warm',
                'Cold',
            ]) || (
                in_array($lead->lead_status, ['Lost', 'No Response']) &&
                $lead->demo_appointment !== null
            );
        });

        $demoSizeCounts = $demoLeads
            ->whereNotNull('company_size_label')
            ->groupBy('company_size_label')
            ->map(fn($group) => $group->count())
            ->toArray();

        $this->demoCompanySizeData = array_merge($defaultCompanySizes, $demoSizeCounts);

        // 🔢 Calculate Demo Rate
        $sizes = ['Small', 'Medium', 'Large', 'Enterprise'];
        $demoRates = [];

        foreach ($sizes as $size) {
            $total = $this->companySizeData[$size] ?? 0;
            $demo = $this->demoCompanySizeData[$size] ?? 0;

            $demoRates[$size] = $total > 0
                ? round(($demo / $total) * 100, 2)
                : 0;
        }

        $this->demoRateBySize = $demoRates;
    }

    public function calculateWebinarDemoAverages()
    {
        $user = Auth::user();
        $query = Lead::query();

        // UTM filters
        $utmLeadIds = $this->getLeadIdsFromUtmFilters();
        $utmFilterApplied = $this->utmCampaign || $this->utmAdgroup || $this->utmTerm || $this->utmMatchtype || $this->referrername || $this->device || $this->utmCreative;

        if ($utmFilterApplied && !empty($utmLeadIds)) {
            $query->whereIn('id', $utmLeadIds);
        }

        if (!empty($this->selectedLeadOwner)) {
            $ownerName = User::where('id', $this->selectedLeadOwner)->value('name');
            $query->where('lead_owner', $ownerName);
        }

        if (in_array($user->role_id, [1, 3]) && $this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        if ($user->role_id == 2) {
            $query->where('salesperson', $user->id);
        }

        if (!empty($this->startDate) && !empty($this->endDate)) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        // Filter status
        $query->where(function ($q) {
            $q->whereIn('lead_status', [
                'Closed', 'Demo-Assigned', 'RFQ-Follow Up', 'Hot', 'Warm', 'Cold',
            ])
            ->orWhere(function ($sub) {
                $sub->whereIn('lead_status', ['Lost', 'No Response'])
                    ->whereNotNull('demo_appointment');
            });
        });

        // Get leads + demo appointments
        $leads = $query->with('demoAppointment')->get();

        $webinarData = [];

        foreach ($leads as $lead) {
            foreach ($lead->demoAppointment as $demo) {
                if ($demo->status === 'Cancelled' || $demo->type !== 'WEBINAR DEMO') {
                    continue;
                }

                $salespersonId = $lead->salesperson;
                $key = $demo->date . '|' . $demo->start_time . '|' . $demo->end_time;

                if (!isset($webinarData[$salespersonId])) {
                    $webinarData[$salespersonId] = [
                        'webinars' => [],
                        'total_leads' => 0,
                    ];
                }

                // Group webinar by time block
                $webinarData[$salespersonId]['webinars'][$key] = true;

                // Count lead per webinar
                $webinarData[$salespersonId]['total_leads']++;
            }
        }

        // Final format
        $this->webinarDemoAverages = [];

        foreach ($webinarData as $salespersonId => $data) {
            $webinarCount = count($data['webinars']);
            $totalLeads = $data['total_leads'];
            $average = $webinarCount > 0 ? round($totalLeads / $webinarCount, 2) : 0;

            $salespersonName = User::find($salespersonId)?->name ?? 'Unknown';

            $this->webinarDemoAverages[$salespersonName] = [
                'webinar_count' => $webinarCount,
                'total_leads' => $totalLeads,
                'average_per_webinar' => $average,
            ];
        }
    }

    public function fetchCloseWonAmount()
    {
        $user = Auth::user();
        $query = Lead::query();

        $utmLeadIds = $this->getLeadIdsFromUtmFilters();
        $utmFilterApplied = $this->utmCampaign || $this->utmAdgroup || $this->utmTerm || $this->utmMatchtype || $this->referrername || $this->device || $this->utmCreative;

        if ($utmFilterApplied && !empty($utmLeadIds)) {
            $query->whereIn('id', $utmLeadIds);
        }

        if (!empty($this->selectedLeadOwner)) {
            $ownerName = User::where('id', $this->selectedLeadOwner)->value('name');
            $query->where('lead_owner', $ownerName);
        }

        if (in_array($user->role_id, [1, 3]) && $this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        if ($user->role_id === 2) {
            $query->where('salesperson', $user->id);
        }

        if (!empty($this->startDate) && !empty($this->endDate)) {
            $query->whereBetween('closing_date', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        $this->closedDealsCount = $query
            ->where('lead_status', 'Closed')
            ->count();

        // Sum of deal_amount for closed leads
        $this->closeWonAmount = $query
            ->where('lead_status', 'Closed')
            ->sum('deal_amount');
    }

    public function fetchMonthlyDealAmounts()
    {
        $user = Auth::user();
        $query = Lead::query();

        $utmLeadIds = $this->getLeadIdsFromUtmFilters();
        $utmFilterApplied = $this->utmCampaign || $this->utmAdgroup || $this->utmTerm || $this->utmMatchtype || $this->referrername || $this->device || $this->utmCreative;

        if ($utmFilterApplied && !empty($utmLeadIds)) {
            $query->whereIn('id', $utmLeadIds);
        }

        if (!empty($this->selectedLeadOwner)) {
            $ownerName = User::where('id', $this->selectedLeadOwner)->value('name');
            $query->where('lead_owner', $ownerName);
        }

        if (in_array($user->role_id, [1, 3]) && $this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        if ($user->role_id === 2) {
            $query->where('salesperson', $user->id);
        }

        if (!empty($this->startDate) && !empty($this->endDate)) {
            $query->whereBetween('closing_date', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        // ✅ Now fetch and group results by year-month
        $results = $query
            ->where('lead_status', 'Closed')
            ->get()
            ->groupBy(function ($lead) {
                return Carbon::parse($lead->closing_date)->format('Y-m');
            })
            ->mapWithKeys(function ($group, $month) {
                return [$month => $group->sum('deal_amount')];
            })
            ->toArray();

        // ✅ Make sure empty months are included
        $start = Carbon::parse($this->startDate)->startOfMonth();
        $end = Carbon::parse($this->endDate)->endOfMonth();
        $period = \Carbon\CarbonPeriod::create($start, '1 month', $end);

        $data = [];
        foreach ($period as $date) {
            $monthKey = $date->format('Y-m');
            $data[$monthKey] = $results[$monthKey] ?? 0;
        }

        $this->monthlyDealAmounts = $data;
    }
}
