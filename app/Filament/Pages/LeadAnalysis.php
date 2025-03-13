<?php
namespace App\Filament\Pages;

use App\Models\Lead;
use Filament\Pages\Page;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;

class LeadAnalysis extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static string $view = 'filament.pages.lead-analysis';
    protected static ?string $navigationLabel = 'Lead Analysis';
    protected static ?string $title = '';
    protected static ?int $navigationSort = 8;
    protected static ?string $navigationGroup = 'Analysis';

    public $selectedUser; // Selected Salesperson
    public $users; // List of Salespersons
    public $totalLeads = 0;
    public $activeLeads = 0;
    public $inactiveLeads = 0;
    public $selectedMonth;

    public $activePercentage = 0;
    public $inactivePercentage = 0;
    public $companySizeData = [];

    public $totalActiveLeads = 0;
    public $stagesData = [];

    public $totalInactiveLeads;
    public $inactiveStatusData = [];

    public $totalTransferLeads;
    public $transferStatusData = [];

    public $totalFollowUpLeads;
    public $followUpStatusData = [];

    public function mount()
    {
        $this->users = User::where('role_id', 2)->get(); // Fetch Salespersons

        // Default to session or logged-in user
        $this->selectedUser = session('selectedUser') ?? auth()->user()->id;

        // Fetch all leads and active leads initially
        $this->fetchLeads();
        $this->fetchActiveLeads();
        $this->fetchInactiveLeads();
        $this->fetchTransferLeads();
        $this->fetchFollowUpLeads();
    }

    #[On('selectedUserChanged')]
    public function updatedSelectedUser($userId)
    {
        $this->selectedUser = $userId; // Store selected user
        session(['selectedUser' => $userId]); // Store the selected user in session

        // Fetch data when user changes
        $this->fetchLeads();
        $this->fetchActiveLeads();
        $this->fetchInactiveLeads();
        $this->fetchTransferLeads();
        $this->fetchFollowUpLeads();
    }

    public function updatedSelectedMonth($month)
    {
        $this->selectedMonth = $month;
        session(['selectedMonth' => $month]);

        $this->fetchLeads();
        $this->fetchActiveLeads();
        $this->fetchInactiveLeads();
        $this->fetchTransferLeads();
        $this->fetchFollowUpLeads();
    }

    /**
     * Fetches general leads and calculates percentages
     */
    public function fetchLeads()
    {
        $user = Auth::user();
        $query = Lead::query();

        // If Lead Owner selects a salesperson, filter by that salesperson
        if ($user->role_id == 1 && $this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        // If Salesperson, show only their assigned leads
        if ($user->role_id == 2) {
            $query->where('salesperson', $user->id);
        }

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereBetween('created_at', [$date->startOfMonth()->format('Y-m-d'), $date->endOfMonth()->format('Y-m-d')]);
        }
        // Fetch filtered leads
        $leads = $query->get();

        // ✅ Store Active and Inactive Leads as Class Properties
        $this->totalLeads = $leads->count();
        $this->activeLeads = $leads->where('categories', 'Active')->count();  // Added class property
        $this->inactiveLeads = $leads->where('categories', 'Inactive')->count();  // Added class property

        // Calculate Active & Inactive Percentage
        $this->activePercentage = $this->totalLeads > 0 ? round(($this->activeLeads / $this->totalLeads) * 100, 2) : 0;
        $this->inactivePercentage = $this->totalLeads > 0 ? round(($this->inactiveLeads / $this->totalLeads) * 100, 2) : 0;

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

    /**
     * Fetches active leads and their breakdown by stages
     */
    public function fetchActiveLeads()
    {
        $user = Auth::user();
        $query = Lead::where('categories', 'Active'); // Filter only Active leads

        // If Lead Owner selects a salesperson, filter by that salesperson
        if ($user->role_id == 1 && $this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        // If Salesperson, show only their assigned leads
        if ($user->role_id == 2) {
            $query->where('salesperson', $user->id);
        }

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereBetween('created_at', [$date->startOfMonth()->format('Y-m-d'), $date->endOfMonth()->format('Y-m-d')]);
        }

        // Count total active leads
        $this->totalActiveLeads = $query->count();

        // Define expected stages
        $stages = ['Transfer', 'Demo', 'Follow Up'];

        // Fetch leads grouped by their stage
        $stagesDataRaw = $query
            ->whereIn('stage', $stages)
            ->select('stage', DB::raw('COUNT(*) as total'))
            ->groupBy('stage')
            ->pluck('total', 'stage')
            ->toArray();

        // Ensure all stages exist in the correct order (fill missing ones with 0)
        $this->stagesData = array_merge(array_fill_keys($stages, 0), $stagesDataRaw);
    }

    public function fetchInactiveLeads()
    {
        $user = Auth::user();
        $query = Lead::where('categories', 'Inactive'); // Filter only Inactive leads

        // If Lead Owner selects a salesperson, filter by that salesperson
        if ($user->role_id == 1 && $this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        // If Salesperson, show only their assigned leads
        if ($user->role_id == 2) {
            $query->where('salesperson', $user->id);
        }

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereBetween('created_at', [$date->startOfMonth()->format('Y-m-d'), $date->endOfMonth()->format('Y-m-d')]);
        }

        // Count total inactive leads
        $this->totalInactiveLeads = $query->count();

        // Define expected statuses
        $inactiveStatuses = ['Closed', 'Lost', 'On Hold', 'No Response'];

        // Fetch leads grouped by their status
        $inactiveStatusCounts = $query
            ->whereIn('lead_status', $inactiveStatuses)
            ->select('lead_status', DB::raw('COUNT(*) as total'))
            ->groupBy('lead_status')
            ->pluck('total', 'lead_status')
            ->toArray();

        // Ensure all statuses exist in the result, even if 0
        $this->inactiveStatusData = array_merge(array_fill_keys($inactiveStatuses, 0), $inactiveStatusCounts);
    }

    public function fetchTransferLeads()
    {
        $user = Auth::user();
        $query = Lead::where('stage', 'Transfer'); // Filter only Transfer leads

        // If Lead Owner selects a salesperson, filter by that salesperson
        if ($user->role_id == 1 && $this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        // If Salesperson, show only their assigned leads
        if ($user->role_id == 2) {
            $query->where('salesperson', $user->id);
        }

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereBetween('created_at', [$date->startOfMonth()->format('Y-m-d'), $date->endOfMonth()->format('Y-m-d')]);
        }

        // Define expected statuses
        $transferStatuses = ['RFQ-Transfer', 'Pending Demo', 'Demo Cancelled'];

        // Count total leads in the "Transfer" stage (excluding specific statuses)
        $this->totalTransferLeads = $query
            ->whereNotIn('lead_status', ['Under Review', 'New']) // Exclude these statuses
            ->count();

        // Fetch leads grouped by their "lead_status"
        $transferStatusCounts = $query
            ->whereIn('lead_status', $transferStatuses)
            ->select('lead_status', DB::raw('COUNT(*) as total'))
            ->groupBy('lead_status')
            ->pluck('total', 'lead_status')
            ->toArray();

        // Ensure all statuses exist in the result, even if 0
        $this->transferStatusData = array_merge(array_fill_keys($transferStatuses, 0), $transferStatusCounts);
    }

    public function fetchFollowUpLeads()
    {
        $user = Auth::user();
        $query = Lead::where('stage', 'Follow Up'); // Filter only Follow Up leads

        // If Lead Owner selects a salesperson, filter by that salesperson
        if ($user->role_id == 1 && $this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        // If Salesperson, show only their assigned leads
        if ($user->role_id == 2) {
            $query->where('salesperson', $user->id);
        }

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereBetween('created_at', [$date->startOfMonth()->format('Y-m-d'), $date->endOfMonth()->format('Y-m-d')]);
        }

        // Define expected statuses
        $followUpStatuses = ['RFQ-Follow Up', 'Hot', 'Warm', 'Cold'];

        // Count total leads in the "Follow Up" stage
        $this->totalFollowUpLeads = $query->count();

        // Fetch leads grouped by their "lead_status"
        $followUpStatusCounts = $query
            ->whereIn('lead_status', $followUpStatuses)
            ->select('lead_status', DB::raw('COUNT(*) as total'))
            ->groupBy('lead_status')
            ->pluck('total', 'lead_status')
            ->toArray();

        // Ensure all statuses exist in the result, even if 0
        $this->followUpStatusData = array_merge(array_fill_keys($followUpStatuses, 0), $followUpStatusCounts);
    }
}
