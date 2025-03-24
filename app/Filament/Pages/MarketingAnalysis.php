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
    public $selectedMonth;
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

    public $days;
    public Carbon $currentDate;

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

        // Fetch only Salespersons (role_id = 2)
        $this->users = User::where('role_id', 2)->get();

        // Fetch unique lead codes for dropdown options
        $this->leadCodes = Lead::select('lead_code')->distinct()->pluck('lead_code')->toArray();

        // Set default selected user based on role
        if ($authUser->role_id == 1) {
            $this->selectedUser = session('selectedUser', null);
        } elseif ($authUser->role_id == 2) {
            $this->selectedUser = $authUser->id; // Salesperson can only see their data
        }

        // Set default selected month
        $this->selectedMonth = session('selectedMonth', $this->currentDate->format('Y-m'));

        // Set default selected lead code
        $this->selectedLeadCode = session('selectedLeadCode', null);

        // Store in session
        session(['selectedUser' => $this->selectedUser, 'selectedMonth' => $this->selectedMonth, 'selectedLeadCode' => $this->selectedLeadCode]);

        // Fetch initial appointment data
        $this->fetchLeads();
        $this->fetchLeadCategorySummary();
        $this->fetchLeadStageSummary();
        $this->fetchLeadStatusSummary();
        $this->fetchCloseWonAmount();
        $this->fetchMonthlyDealAmounts();
        $this->getLeadTypeCounts();
    }

    public function updatedSelectedUser($userId)
    {
        $this->selectedUser = $userId;
        session(['selectedUser' => $userId]);
        $this->fetchLeads();
        $this->fetchLeadCategorySummary();
        $this->fetchLeadStageSummary();
        $this->fetchLeadStatusSummary();
        $this->fetchCloseWonAmount();
        $this->fetchMonthlyDealAmounts();
        $this->getLeadTypeCounts();
    }

    public function updatedSelectedMonth($month)
    {
        $this->selectedMonth = $month;
        session(['selectedMonth' => $month]);
        $this->fetchLeads();
        $this->fetchLeadCategorySummary();
        $this->fetchLeadStageSummary();
        $this->fetchLeadStatusSummary();
        $this->fetchCloseWonAmount();
        $this->fetchMonthlyDealAmounts();
        $this->getLeadTypeCounts();
    }

    public function updatedSelectedLeadCode($leadCode)
    {
        $this->selectedLeadCode = $leadCode;
        session(['selectedLeadCode' => $leadCode]);
        $this->fetchLeads();
        $this->fetchLeadCategorySummary();
        $this->fetchLeadStageSummary();
        $this->fetchLeadStatusSummary();
        $this->fetchCloseWonAmount();
        $this->fetchMonthlyDealAmounts();
        $this->getLeadTypeCounts();
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
            $this->fetchLeads();
            $this->fetchLeadCategorySummary();
            $this->fetchLeadStageSummary();
            $this->fetchLeadStatusSummary();
            $this->fetchCloseWonAmount();
            $this->fetchMonthlyDealAmounts();
            $this->getLeadTypeCounts();
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

        // If Lead Owner selects a salesperson, filter by that salesperson
        if (in_array($user->role_id, [1, 3]) && $this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        // If Salesperson, show only their assigned leads
        if ($user->role_id == 2) {
            $query->where('salesperson', $user->id);
        }

        if (!empty($this->selectedMonth)) {
            $startDate = Carbon::parse($this->selectedMonth)->startOfMonth();
            $endDate = Carbon::parse($this->selectedMonth)->endOfMonth();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        if (!empty($this->selectedLeadCode)) {
            $query->where('lead_code', $this->selectedLeadCode);
        }

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

        if (!empty($this->selectedLeadCode)) {
            $query->where('lead_code', $this->selectedLeadCode);
        }

        // Filter by selected month
        if (!empty($this->selectedMonth)) {
            $startDate = Carbon::parse($this->selectedMonth)->startOfMonth();
            $endDate = Carbon::parse($this->selectedMonth)->endOfMonth();
            $query->whereBetween('created_at', [$startDate, $endDate]);
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

    public function fetchLeadCategorySummary()
    {
        $user = Auth::user();
        $query = Lead::query();

        // Apply UTM filters
        $utmLeadIds = $this->getLeadIdsFromUtmFilters();
        $utmFilterApplied = $this->utmCampaign || $this->utmAdgroup || $this->utmTerm || $this->utmMatchtype || $this->referrername || $this->device || $this->utmCreative;

        if ($utmFilterApplied && !empty($utmLeadIds)) {
            $query->whereIn('id', $utmLeadIds);
        }

        // Salesperson filtering
        if (in_array($user->role_id, [1, 3]) && $this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        if ($user->role_id === 2) {
            $query->where('salesperson', $user->id);
        }

        if (!empty($this->selectedLeadCode)) {
            $query->where('lead_code', $this->selectedLeadCode);
        }

        // Month filter
        if (!empty($this->selectedMonth)) {
            $startDate = Carbon::parse($this->selectedMonth)->startOfMonth();
            $endDate = Carbon::parse($this->selectedMonth)->endOfMonth();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        // // Lead code filter
        // if (!empty($this->selectedLeadCode)) {
        //     $query->where('lead_code', $this->selectedLeadCode);
        // }

        // Define your expected categories
        $categories = ['New', 'Active', 'Inactive'];

        // Clone query to avoid applying whereIn() twice on same builder
        $countQuery = clone $query;

        // Get total count of leads in the specified categories
        $this->totalLeadsByCategory = $countQuery
            ->whereIn('categories', $categories)
            ->count();

        // Grouped count by category
        $categoryCounts = $query
            ->whereIn('categories', $categories)
            ->select('categories', DB::raw('COUNT(*) as total'))
            ->groupBy('categories')
            ->pluck('total', 'categories')
            ->toArray();

        // Ensure all categories are present
        $this->categoryData = array_merge(array_fill_keys($categories, 0), $categoryCounts);
    }

    public function fetchLeadStageSummary()
    {
        $user = Auth::user();
        $query = Lead::query();

        // Apply UTM filters
        $utmLeadIds = $this->getLeadIdsFromUtmFilters();
        $utmFilterApplied = $this->utmCampaign || $this->utmAdgroup || $this->utmTerm || $this->utmMatchtype || $this->referrername || $this->device || $this->utmCreative;

        if ($utmFilterApplied && !empty($utmLeadIds)) {
            $query->whereIn('id', $utmLeadIds);
        }

        // Salesperson filtering
        if (in_array($user->role_id, [1, 3]) && $this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        if ($user->role_id === 2) {
            $query->where('salesperson', $user->id);
        }

        if (!empty($this->selectedLeadCode)) {
            $query->where('lead_code', $this->selectedLeadCode);
        }

        // Month filter
        if (!empty($this->selectedMonth)) {
            $startDate = Carbon::parse($this->selectedMonth)->startOfMonth();
            $endDate = Carbon::parse($this->selectedMonth)->endOfMonth();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        // Optional: include this if you want to allow filtering by lead code
        // if (!empty($this->selectedLeadCode)) {
        //     $query->where('lead_code', $this->selectedLeadCode);
        // }
        $query->where('categories', 'Active');

        // Define expected stages
        $stages = ['New', 'Transfer', 'Demo', 'Follow Up'];

        // Clone query to avoid applying same filters twice
        $countQuery = clone $query;

        // Total count
        $this->totalLeadsByStage = $countQuery
            ->whereIn('stage', $stages)
            ->count();

        // Count grouped by stage
        $stageCounts = $query
            ->whereIn('stage', $stages)
            ->select('stage', DB::raw('COUNT(*) as total'))
            ->groupBy('stage')
            ->pluck('total', 'stage')
            ->toArray();
        info($stageCounts);
        // Ensure all stages are present
        $this->stageData = array_merge(array_fill_keys($stages, 0), $stageCounts);
    }

    public function fetchLeadStatusSummary()
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

        if ($user->role_id === 2) {
            $query->where('salesperson', $user->id);
        }

        if (!empty($this->selectedLeadCode)) {
            $query->where('lead_code', $this->selectedLeadCode);
        }

        if (!empty($this->selectedMonth)) {
            $startDate = Carbon::parse($this->selectedMonth)->startOfMonth();
            $endDate = Carbon::parse($this->selectedMonth)->endOfMonth();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $statuses = [
            'New', 'RFQ-Transfer', 'Pending Demo', 'Under Review',
            'Demo Cancelled', 'RFQ-Follow Up',
            'Hot', 'Warm', 'Cold', 'Junk', 'On Hold', 'Lost',
            'No Response', 'Closed',
        ];

        $this->totalLeadStatus = (clone $query)
            ->whereIn('lead_status', $statuses)
            ->count();

        $statusCounts = $query
            ->whereIn('lead_status', $statuses)
            ->select('lead_status', DB::raw('COUNT(*) as total'))
            ->groupBy('lead_status')
            ->pluck('total', 'lead_status')
            ->toArray();

        $this->leadStatusData = array_merge(array_fill_keys($statuses, 0), $statusCounts);
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

        if (in_array($user->role_id, [1, 3]) && $this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        if ($user->role_id === 2) {
            $query->where('salesperson', $user->id);
        }

        if (!empty($this->selectedLeadCode)) {
            $query->where('lead_code', $this->selectedLeadCode);
        }

        if (!empty($this->selectedMonth)) {
            $startDate = Carbon::parse($this->selectedMonth)->startOfMonth();
            $endDate = Carbon::parse($this->selectedMonth)->endOfMonth();
            $query->whereBetween('created_at', [$startDate, $endDate]);
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

    $utmLeadIds = $this->getLeadIdsFromUtmFilters();
    $utmFilterApplied = $this->utmCampaign || $this->utmAdgroup || $this->utmTerm || $this->utmMatchtype || $this->referrername || $this->device || $this->utmCreative;

    // Use selectedMonth if available, else fallback to current month
    $endMonth = !empty($this->selectedMonth)
        ? Carbon::parse($this->selectedMonth)
        : Carbon::now();

    $months = collect();

    // Get last 5 months ending at selected month
    for ($i = 4; $i >= 0; $i--) {
        $month = $endMonth->copy()->subMonths($i)->format('Y-m');
        $months->push($month);
    }

    $data = [];

    foreach ($months as $month) {
        $start = Carbon::parse($month)->startOfMonth();
        $end = Carbon::parse($month)->endOfMonth();

        $query = Lead::query();

        // Apply filters...
        if ($utmFilterApplied && !empty($utmLeadIds)) {
            $query->whereIn('id', $utmLeadIds);
        }

        if (in_array($user->role_id, [1, 3]) && $this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        if ($user->role_id === 2) {
            $query->where('salesperson', $user->id);
        }

        if (!empty($this->selectedLeadCode)) {
            $query->where('lead_code', $this->selectedLeadCode);
        }

        $query->whereBetween('created_at', [$start, $end]);

        $amount = $query->where('lead_status', 'Closed')->sum('deal_amount');

        $data[$month] = $amount;
    }

    $this->monthlyDealAmounts = $data;
}
}
