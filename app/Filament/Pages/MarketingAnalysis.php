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
    public $companySizeDistribution = [];
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

    //Slide Modal Variables
    public $showSlideOver = false;
    public $slideOverTitle = '';
    public $slideOverList = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.pages.marketing-analysis');
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
            $query->where('utm_campaign', '=', $this->utmCampaign);
        }
        if (!empty($this->utmAdgroup)) {
            $query->where('utm_adgroup', '=', $this->utmAdgroup);
        }
        if (!empty($this->utmTerm)) {
            $query->where('utm_term', '=', $this->utmTerm);
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
            $query->where('utm_creative', '=', $this->utmCreative);
        }
        return $query->pluck('lead_id')->toArray();
    }

    public function fetchLeadStatusSummary()
    {
        $user = Auth::user();

        $utmLeadIds = $this->getLeadIdsFromUtmFilters();
        $utmFilterApplied = $this->utmCampaign || $this->utmAdgroup || $this->utmTerm || $this->utmMatchtype || $this->referrername || $this->device || $this->utmCreative;

        $activeStatuses = [
            'None','New','RFQ-Transfer','Pending Demo','Under Review','Demo Cancelled',
            'Demo-Assigned','Hot','Warm','Cold'
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

        if (!empty($this->selectedLeadCode)) {
            if ($this->selectedLeadCode === 'Null') {
                $query->whereNull('lead_code');
            } else {
                $query->where('lead_code', $this->selectedLeadCode);
            }
        }

        $query->where(function ($q) use ($activeStatuses, $otherStatuses) {
            $q->where(function ($sub) use ($activeStatuses) {
                $sub->where(function ($inner) {
                    $inner->where('categories', 'Active')
                          ->orWhere('categories', 'New');
                })->whereIn('lead_status', $activeStatuses);
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
        $this->leadStatusData = collect(array_merge(array_fill_keys($allStatuses, 0), $statusCounts))
            ->sortDesc()
            ->toArray();
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

        if (!empty($this->selectedLeadCode)) {
            if ($this->selectedLeadCode === 'Null') {
                $query->whereNull('lead_code');
            } else {
                $query->where('lead_code', $this->selectedLeadCode);
            }
        }

        $this->leadCodes = Lead::query()
            ->select('lead_code')
            ->distinct()
            ->pluck('lead_code')
            ->map(function ($value) {
                return $value ?? 'Null'; // Replace null with 'Null' string
            })
            ->sort()
            ->values()
            ->toArray();

        $leads = $query->get();

        $counts = collect([
            'Facebook Ads'       => $leads->where('lead_code', 'Facebook Ads')->count(),
            'Google AdWords'     => $leads->where('lead_code', 'Google AdWords')->count(),
            'Refer & Earn'       => $leads->where('lead_code', 'Refer & Earn')->count(),
            'WhatsApp - TimeTec' => $leads->where('lead_code', 'WhatsApp - TimeTec')->count(),
            'Facebook Messenger' => $leads->where('lead_code', 'Facebook Messenger')->count(),
            'Salesperson Lead'   => $leads->where('lead_code', 'Salesperson Lead')->count(),
            'BD Referral'        => $leads->where('lead_code', 'BD Referral Program')->count(),
            'Website'            => $leads->where('lead_code', 'Website')->count(),
            'Criteo'             => $leads->where('lead_code', 'Criteo')->count(),
            'Null'               => $leads->where('lead_code', null)->count(),
        ]);

        return $counts->sortDesc()->toArray();
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

        if (!empty($this->selectedLeadCode)) {
            if ($this->selectedLeadCode === 'Null') {
                $query->whereNull('lead_code');
            } else {
                $query->where('lead_code', $this->selectedLeadCode);
            }
        }

        $query->where(function ($q) {
            $q->whereNotIn('lead_status', ['Junk', 'On Hold', 'Lost'])
            ->orWhere(function ($sub) {
                $sub->where('lead_status', 'Lost')
                    ->whereNotNull('demo_appointment');
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

        $this->companySizeDistribution = array_merge($defaultCompanySizes, $companySizeCounts);
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

        if (!empty($this->selectedLeadCode)) {
            if ($this->selectedLeadCode === 'Null') {
                $query->whereNull('lead_code');
            } else {
                $query->where('lead_code', $this->selectedLeadCode);
            }
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
        $utmFilterApplied = $this->utmCampaign || $this->utmAdgroup || $this->utmTerm ||
                            $this->utmMatchtype || $this->referrername || $this->device || $this->utmCreative;

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

        // ✅ Filter by lead created_at
        if (!empty($this->startDate) && !empty($this->endDate)) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        if (!empty($this->selectedLeadCode)) {
            if ($this->selectedLeadCode === 'Null') {
                $query->whereNull('lead_code');
            } else {
                $query->where('lead_code', $this->selectedLeadCode);
            }
        }

        // ✅ Lead status filter
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

        // Load leads with demo appointments
        $leads = $query->with('demoAppointment')->get();

        $newDemoCount = 0;
        $webinarKeys = [];

        foreach ($leads as $lead) {
            $appointments = $lead->demoAppointment ?? collect();

            foreach ($appointments as $demo) {
                if ($demo->status === 'Cancelled') {
                    continue;
                }

                if ($demo->type === 'NEW DEMO') {
                    $newDemoCount++;
                } elseif ($demo->type === 'WEBINAR DEMO') {
                    $key = $demo->date . '|' . $demo->start_time . '|' . $demo->end_time . '|' . $demo->salesperson;
                    $webinarKeys[$key] = true;
                }
            }
        }

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

        if (!empty($this->selectedLeadCode)) {
            if ($this->selectedLeadCode === 'Null') {
                $query->whereNull('lead_code');
            } else {
                $query->where('lead_code', $this->selectedLeadCode);
            }
        }

        // ✅ Exclude junk/on hold/lost unless has demo
        $query->where(function ($q) {
            $q->whereNotIn('lead_status', ['Junk', 'On Hold', 'Lost'])
            ->orWhere(function ($sub) {
                $sub->where('lead_status', 'Lost')
                    ->whereNotNull('demo_appointment');
            });
        });

        // Fetch filtered leads
        $leads = $query->get();

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

        // 🔵 Demo Leads by Company Size
        $demoLeads = $leads->filter(function ($lead) {
            return in_array($lead->lead_status, [
                'Closed', 'Demo-Assigned', 'RFQ-Follow Up', 'Hot', 'Warm', 'Cold',
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
        $this->webinarDemoAverages = [];

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

        // ✅ Lead status filter
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

        // ✅ Filter by lead created_at instead of demo date
        if (!empty($this->startDate) && !empty($this->endDate)) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        if (!empty($this->selectedLeadCode)) {
            if ($this->selectedLeadCode === 'Null') {
                $query->whereNull('lead_code');
            } else {
                $query->where('lead_code', $this->selectedLeadCode);
            }
        }

        // Load leads with demo appointments
        $leads = $query->with('demoAppointment')->get();

        $webinarData = [];

        foreach ($leads as $lead) {
            $appointments = $lead->demoAppointment ?? collect();

            foreach ($appointments as $demo) {
                // ✅ Still skip cancelled demos and only include WEBINAR DEMO
                if ($demo->type !== 'WEBINAR DEMO' || $demo->status === 'Cancelled') {
                    continue;
                }

                $salespersonId = $demo->salesperson;
                $key = $demo->date . '|' . $demo->start_time . '|' . $demo->end_time . '|' . $salespersonId . '|' . $lead->id;

                if (!isset($webinarData[$key])) {
                    $webinarData[$key] = [
                        'salesperson_id' => $salespersonId,
                        'lead_count' => 0,
                    ];
                }

                $webinarData[$key]['lead_count'] = 1;
            }
        }

        // Final summary
        $uniqueSessions = [];

        foreach ($webinarData as $key => $data) {
            $salespersonId = $data['salesperson_id'];

            // Extract session key (remove leadId)
            [$date, $startTime, $endTime, $salesperson] = explode('|', $key);
            $sessionKey = $date . '|' . $startTime . '|' . $endTime . '|' . $salesperson;

            if (!isset($uniqueSessions[$salespersonId])) {
                $uniqueSessions[$salespersonId] = [];
            }

            if (!in_array($sessionKey, $uniqueSessions[$salespersonId])) {
                $uniqueSessions[$salespersonId][] = $sessionKey;

                if (!isset($this->webinarDemoAverages[$salespersonId])) {
                    $this->webinarDemoAverages[$salespersonId] = [
                        'webinar_count' => 0,
                        'total_leads' => 0,
                    ];
                }

                $this->webinarDemoAverages[$salespersonId]['webinar_count'] += 1;
            }

            $this->webinarDemoAverages[$salespersonId]['total_leads'] += $data['lead_count'];
        }

        // Convert to name-indexed output
        foreach ($this->webinarDemoAverages as $salespersonId => $summary) {
            $average = $summary['webinar_count'] > 0
                ? round($summary['total_leads'] / $summary['webinar_count'], 2)
                : 0;

            $salespersonName = User::find($salespersonId)?->name ?? 'Unknown';

            if (empty($salespersonName) || $salespersonName === 'Unknown') {
                continue;
            }

            $this->webinarDemoAverages[$salespersonName] = [
                'webinar_count' => $summary['webinar_count'],
                'total_leads' => $summary['total_leads'],
                'average_per_webinar' => $average,
            ];

            unset($this->webinarDemoAverages[$salespersonId]);
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

        if (!empty($this->selectedLeadCode)) {
            if ($this->selectedLeadCode === 'Null') {
                $query->whereNull('lead_code');
            } else {
                $query->where('lead_code', $this->selectedLeadCode);
            }
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

        if (!empty($this->selectedLeadCode)) {
            if ($this->selectedLeadCode === 'Null') {
                $query->whereNull('lead_code');
            } else {
                $query->where('lead_code', $this->selectedLeadCode);
            }
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

    public function openLeadStatusSlideOver($status)
    {
        $this->slideOverTitle = "Leads - " . ucfirst($status);

        $query = Lead::with('companyDetail')->where('lead_status', $status);

        // UTM filters
        $utmLeadIds = $this->getLeadIdsFromUtmFilters();
        $utmFilterApplied = $this->utmCampaign || $this->utmAdgroup || $this->utmTerm || $this->utmMatchtype || $this->referrername || $this->device || $this->utmCreative;

        if ($utmFilterApplied && !empty($utmLeadIds)) {
            $query->whereIn('id', $utmLeadIds);
        }

        // Apply same filters as in fetchLeadStatusSummary
        if (!empty($this->selectedLeadOwner)) {
            $ownerName = User::where('id', $this->selectedLeadOwner)->value('name');
            $query->where('lead_owner', $ownerName);
        }

        if (in_array(auth()->user()->role_id, [1, 3]) && $this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        if (auth()->user()->role_id == 2) {
            $query->where('salesperson', auth()->user()->id);
        }

        if (!empty($this->startDate) && !empty($this->endDate)) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        if (!empty($this->selectedLeadCode)) {
            if ($this->selectedLeadCode === 'Null') {
                $query->whereNull('lead_code');
            } else {
                $query->where('lead_code', $this->selectedLeadCode);
            }
        }

        $this->slideOverList = $query->get();
        $this->showSlideOver = true;
    }

    public function openLeadSourceSlideOver($source)
    {
        $this->slideOverTitle = "Leads from: " . $source;

        $user = Auth::user();
        $query = Lead::with('companyDetail');

        if ($source === 'Null') {
            $query->whereNull('lead_code');
        } else {
            $query->where('lead_code', $source);
        }

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

        if ($user->role_id === 2) {
            $query->where('salesperson', $user->id);
        }

        if (!empty($this->startDate) && !empty($this->endDate)) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        if (!empty($this->selectedLeadCode)) {
            if ($this->selectedLeadCode === 'Null') {
                $query->whereNull('lead_code');
            } else {
                $query->where('lead_code', $this->selectedLeadCode);
            }
        }

        $this->slideOverList = $query->get();
        $this->showSlideOver = true;
    }

    public function openCompanySizeSlideOver($label)
    {
        $this->slideOverTitle = "Company Size: " . ucfirst($label);

        $sizeMap = [
            'Small' => ['1-24'],
            'Medium' => ['25-99'],
            'Large' => ['100-500'],
            'Enterprise' => ['501 and Above'],
        ];

        $rawSizes = $sizeMap[$label] ?? [];

        $query = Lead::with('companyDetail')->whereIn('company_size', $rawSizes);

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

        if (in_array(auth()->user()->role_id, [1, 3]) && $this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        if (auth()->user()->role_id == 2) {
            $query->where('salesperson', auth()->id());
        }

        if (!empty($this->startDate) && !empty($this->endDate)) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        if (!empty($this->selectedLeadCode)) {
            if ($this->selectedLeadCode === 'Null') {
                $query->whereNull('lead_code');
            } else {
                $query->where('lead_code', $this->selectedLeadCode);
            }
        }

        // Same filtering logic as fetchLeads()
        $query->where(function ($q) {
            $q->whereNotIn('lead_status', ['Junk', 'On Hold', 'Lost'])
            ->orWhere(function ($sub) {
                $sub->where('lead_status', 'Lost')->whereNotNull('demo_appointment');
            });
        });

        $this->slideOverList = $query->get();
        $this->showSlideOver = true;
    }

    public function openDemoCompanySizeSlideOver($label)
    {
        $this->slideOverTitle = "Demo Leads - " . ucfirst($label);

        $sizeMap = [
            'Small' => ['1-24'],
            'Medium' => ['25-99'],
            'Large' => ['100-500'],
            'Enterprise' => ['501 and Above'],
        ];

        $rawSizes = $sizeMap[$label] ?? [];

        $query = Lead::with('companyDetail')
            ->whereIn('company_size', $rawSizes)
            ->where(function ($q) {
                $q->whereIn('lead_status', [
                    'Closed',
                    'Demo-Assigned',
                    'RFQ-Follow Up',
                    'Hot',
                    'Warm',
                    'Cold',
                ])->orWhere(function ($sub) {
                    $sub->whereIn('lead_status', ['Lost', 'No Response'])
                        ->whereNotNull('demo_appointment');
                });
            });

        // UTM filters
        $utmLeadIds = $this->getLeadIdsFromUtmFilters();
        $utmFilterApplied = $this->utmCampaign || $this->utmAdgroup || $this->utmTerm || $this->utmMatchtype || $this->referrername || $this->device || $this->utmCreative;

        if ($utmFilterApplied && !empty($utmLeadIds)) {
            $query->whereIn('id', $utmLeadIds);
        }

        // Filter by lead owner
        if (!empty($this->selectedLeadOwner)) {
            $ownerName = User::where('id', $this->selectedLeadOwner)->value('name');
            $query->where('lead_owner', $ownerName);
        }

        // Role-based filtering
        if (in_array(auth()->user()->role_id, [1, 3]) && $this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        if (auth()->user()->role_id == 2) {
            $query->where('salesperson', auth()->id());
        }

        // Date filter
        if (!empty($this->startDate) && !empty($this->endDate)) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        if (!empty($this->selectedLeadCode)) {
            if ($this->selectedLeadCode === 'Null') {
                $query->whereNull('lead_code');
            } else {
                $query->where('lead_code', $this->selectedLeadCode);
            }
        }

        $this->slideOverList = $query->get();
        $this->showSlideOver = true;
    }

    public function openDemoTypeSlideOver($type)
    {
        $this->slideOverTitle = $type . ' Leads';

        $user = Auth::user();
        $query = Lead::query();

        // UTM filters
        $utmLeadIds = $this->getLeadIdsFromUtmFilters();
        $utmFilterApplied = $this->utmCampaign || $this->utmAdgroup || $this->utmTerm ||
                            $this->utmMatchtype || $this->referrername || $this->device || $this->utmCreative;

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

        // ✅ Filter by lead created_at instead of demo date
        if (!empty($this->startDate) && !empty($this->endDate)) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        if (!empty($this->selectedLeadCode)) {
            if ($this->selectedLeadCode === 'Null') {
                $query->whereNull('lead_code');
            } else {
                $query->where('lead_code', $this->selectedLeadCode);
            }
        }

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

        // Fetch leads with demo appointments
        $leads = $query->with('demoAppointment', 'companyDetail')->get();

        $matchedLeads = [];

        foreach ($leads as $lead) {
            $appointments = $lead->demoAppointment ?? collect();

            $appointments = $appointments->filter(function ($demo) use ($type) {
                return $demo->status !== 'Cancelled' &&
                    $demo->type === strtoupper($type);
            });

            if ($appointments->isNotEmpty()) {
                $matchedLeads[] = $lead;
            }
        }

        $this->slideOverList = collect($matchedLeads);
        $this->showSlideOver = true;
    }

    // public function openWebinarLeadList($salespersonName)
    // {
    //     $this->slideOverTitle = "Webinar Demo - " . $salespersonName;

    //     $salespersonId = User::where('name', $salespersonName)->value('id');
    //     if (!$salespersonId) {
    //         $this->slideOverList = collect();
    //         $this->showSlideOver = true;
    //         return;
    //     }

    //     $query = Lead::query()->with('companyDetail', 'demoAppointment');

    //     // UTM filters
    //     $utmLeadIds = $this->getLeadIdsFromUtmFilters();
    //     $utmFilterApplied = $this->utmCampaign || $this->utmAdgroup || $this->utmTerm || $this->utmMatchtype || $this->referrername || $this->device || $this->utmCreative;

    //     if ($utmFilterApplied && !empty($utmLeadIds)) {
    //         $query->whereIn('id', $utmLeadIds);
    //     }

    //     if (!empty($this->selectedLeadOwner)) {
    //         $ownerName = User::where('id', $this->selectedLeadOwner)->value('name');
    //         $query->where('lead_owner', $ownerName);
    //     }

    //     if (in_array(auth()->user()->role_id, [1, 3]) && $this->selectedUser) {
    //         $query->where('salesperson', $this->selectedUser);
    //     }

    //     if (auth()->user()->role_id == 2) {
    //         $query->where('salesperson', auth()->user()->id);
    //     }

    //     $leads = $query->get();

    //     $start = Carbon::parse($this->startDate)->toDateString();
    //     $end = Carbon::parse($this->endDate)->toDateString();

    //     $filteredLeads = [];

    //     foreach ($leads as $lead) {
    //         $appointments = $lead->demoAppointment ?? collect();

    //         $matched = $appointments->filter(function ($demo) use ($salespersonId, $start, $end) {
    //             return $demo->type === 'WEBINAR DEMO'
    //                 && $demo->status !== 'Cancelled'
    //                 && $demo->salesperson == $salespersonId
    //                 && $demo->date >= $start && $demo->date <= $end;
    //         });

    //         if ($matched->isNotEmpty()) {
    //             $filteredLeads[] = $lead;
    //         }
    //     }

    //     $this->slideOverList = collect($filteredLeads);
    //     $this->showSlideOver = true;
    // }

    public function openWebinarLeadList($salespersonName)
    {
        $this->slideOverTitle = "Webinar Demo - " . $salespersonName;

        $salespersonId = User::where('name', $salespersonName)->value('id');
        if (!$salespersonId) {
            $this->slideOverList = collect();
            $this->showSlideOver = true;
            return;
        }

        $query = Lead::query()->with('companyDetail', 'demoAppointment');

        // UTM filters
        $utmLeadIds = $this->getLeadIdsFromUtmFilters();
        $utmFilterApplied = $this->utmCampaign || $this->utmAdgroup || $this->utmTerm ||
                            $this->utmMatchtype || $this->referrername || $this->device || $this->utmCreative;

        if ($utmFilterApplied && !empty($utmLeadIds)) {
            $query->whereIn('id', $utmLeadIds);
        }

        if (!empty($this->selectedLeadOwner)) {
            $ownerName = User::where('id', $this->selectedLeadOwner)->value('name');
            $query->where('lead_owner', $ownerName);
        }

        if (in_array(auth()->user()->role_id, [1, 3]) && $this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        if (auth()->user()->role_id == 2) {
            $query->where('salesperson', auth()->user()->id);
        }

        // ✅ Filter by lead created_at instead of demo date
        if (!empty($this->startDate) && !empty($this->endDate)) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        if (!empty($this->selectedLeadCode)) {
            if ($this->selectedLeadCode === 'Null') {
                $query->whereNull('lead_code');
            } else {
                $query->where('lead_code', $this->selectedLeadCode);
            }
        }

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

        $leads = $query->get();

        $filteredLeads = [];

        foreach ($leads as $lead) {
            $appointments = $lead->demoAppointment ?? collect();

            $matched = $appointments->filter(function ($demo) use ($salespersonId) {
                return $demo->type === 'WEBINAR DEMO'
                    && $demo->status !== 'Cancelled'
                    && $demo->salesperson == $salespersonId;
            });

            if ($matched->isNotEmpty()) {
                $filteredLeads[] = $lead;
            }
        }

        $this->slideOverList = collect($filteredLeads);
        $this->showSlideOver = true;
    }

    public function openMonthlyDealsSlideOver($monthKey)
    {
        $this->slideOverTitle = "Closed Deals - " . Carbon::parse($monthKey)->format('F Y');

        $user = Auth::user();
        $query = Lead::query()->with('companyDetail');

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

        if (!empty($this->selectedLeadCode)) {
            if ($this->selectedLeadCode === 'Null') {
                $query->whereNull('lead_code');
            } else {
                $query->where('lead_code', $this->selectedLeadCode);
            }
        }

        // Monthly filter
        $start = Carbon::parse($monthKey)->startOfMonth()->startOfDay();
        $end = Carbon::parse($monthKey)->endOfMonth()->endOfDay();

        $query->whereBetween('closing_date', [$start, $end])
            ->where('lead_status', 'Closed');

        $this->slideOverList = $query->get();
        $this->showSlideOver = true;
    }

    public function getSelectedMonthRangeLabelProperty()
    {
        $start = Carbon::parse($this->startDate)->format('F Y');
        $end = Carbon::parse($this->endDate)->format('F Y');

        return $start === $end ? $start : "{$start} - {$end}";
    }
}
