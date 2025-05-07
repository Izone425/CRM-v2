<?php
namespace App\Filament\Pages;

use App\Models\ActivityLog;
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

class SalesAdminAnalysisV3 extends Page
{
    use InteractsWithPageTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.pages.sales-admin-analysis-v3';
    protected static ?string $navigationLabel = 'Sales Admin Analysis V3';
    protected static ?string $title = '';
    protected static ?int $navigationSort = 7;
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

    public $days;
    public Carbon $currentDate;
    public $startDate;
    public $endDate;
    public $leadsIncoming = 0;
    public $leadOwnerPickupCounts = 0;
    public $totalPickupLeads = 0;
    public $demoStatsByLeadOwner = [];
    public $totalDemos = 0;
    public $rfqTransferStatsByLeadOwner = [];
    public $totalRfqTransfers = 0;
    public $totalCallAttempts = 0;
    public $callAttemptStatsByLeadOwner = [];
    public $automationStatsByLeadOwner = [];
    public $totalAutomationEnabled = 0;
    public $archiveStatsByLeadOwner = [];
    public $totalArchivedLeads = 0;
    public $inactiveCallAttemptStatsByLeadOwner = [];
    public $totalInactiveCallAttempts = 0;
    //Slide Modal Variables
    public $showSlideOver = false;
    public $slideOverTitle = '';
    public $leadList = [];

    public static function canAccess(): bool
    {
        return auth()->user()->role_id !== 2; // Hides the resource from all users
    }

    public function mount()
    {
        $authUser = auth()->user();
        $this->currentDate = Carbon::now();
        $this->startDate = session('startDate', $this->currentDate->copy()->startOfMonth()->toDateString());
        $this->endDate = session('endDate', $this->currentDate->toDateString());

        // Set default selected user based on role
        if ($authUser->role_id == 1) {
            $this->selectedUser = session('selectedUser', null);
        } elseif ($authUser->role_id == 2) {
            $this->selectedUser = $authUser->id; // Salesperson can only see their data
        }

        // Fetch initial appointment data
        $this->fetchLeads();
        $this->fetchLeadOwnerPickups();
        $this->fetchDemoStatsByLeadOwner();
        $this->fetchRfqTransferStatsByLeadOwner();
        $this->fetchCallAttemptStatsByLeadOwner();
        $this->fetchInactiveCallAttemptStatsByLeadOwner();
        $this->fetchAutomationEnabledStatsByLeadOwner();
        $this->fetchArchivedStatsByLeadOwner();
    }

    public function updatedSelectedUser($userId)
    {
        $this->selectedUser = $userId;
        session(['selectedUser' => $userId]);
        $this->fetchLeads();
        $this->fetchLeadOwnerPickups();
        $this->fetchDemoStatsByLeadOwner();
        $this->fetchRfqTransferStatsByLeadOwner();
        $this->fetchCallAttemptStatsByLeadOwner();
        $this->fetchInactiveCallAttemptStatsByLeadOwner();
        $this->fetchAutomationEnabledStatsByLeadOwner();
        $this->fetchArchivedStatsByLeadOwner();
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

    public function refreshDashboardData()
    {
        $this->fetchLeads();
        $this->fetchLeadOwnerPickups();
        $this->fetchDemoStatsByLeadOwner();
        $this->fetchRfqTransferStatsByLeadOwner();
        $this->fetchInactiveCallAttemptStatsByLeadOwner();
        $this->fetchAutomationEnabledStatsByLeadOwner();
        $this->fetchArchivedStatsByLeadOwner();
    }

    public function fetchLeads()
    {
        $query = Lead::query();

        if (!empty($this->startDate) && !empty($this->endDate)) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        $this->leadsIncoming = $query->count();
    }

    public function fetchLeadOwnerPickups()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $leadOwners = User::where('role_id', 1)->pluck('id', 'name')->toArray();

        $result = [];
        foreach ($leadOwners as $name => $userId) {
            $count = Lead::query()
                ->where('lead_owner', $name)
                ->whereBetween('pickup_date', [$start, $end])
                ->count();

            if ($count > 0) {
                $result[$name] = ['count' => $count];
            }
        }

        $total = collect($result)->sum('count');
        $this->totalPickupLeads = $total;

        foreach ($result as $name => $data) {
            $result[$name]['percentage'] = $total > 0
                ? round(($data['count'] / $total) * 100, 2)
                : 0;
        }

        $this->leadOwnerPickupCounts = $result;
    }

    public function fetchDemoStatsByLeadOwner()
    {
        $leadOwners = User::where('role_id', 1)->pluck('id', 'name')->toArray();

        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $result = [];

        foreach ($leadOwners as $name => $userId) {
            $count = Appointment::query()
                ->whereIn('status', ['New', 'Done'])
                ->where('causer_id', $userId)
                ->whereBetween('created_at', [$start, $end])
                ->count();

            if ($count > 0) {
                $result[$name] = ['count' => $count];
            }
        }

        $total = collect($result)->sum('count');
        $this->totalDemos = $total;

        // Add percentage for proportional layout
        foreach ($result as $name => $data) {
            $result[$name]['percentage'] = $total > 0 ? round(($data['count'] / $total) * 100, 2) : 0;
        }

        $this->demoStatsByLeadOwner = $result;
    }

    public function fetchRfqTransferStatsByLeadOwner()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $leadOwners = User::where('role_id', 1)->pluck('id', 'name')->toArray();

        $result = [];
        foreach ($leadOwners as $name => $userId) {
            $count = ActivityLog::query()
                ->where('description', 'like', '%RFQ only%')
                ->whereBetween('created_at', [$start, $end])
                ->where('causer_id', $userId)
                ->count();

            if ($count > 0) {
                $result[$name] = ['count' => $count];
            }
        }

        $total = collect($result)->sum('count');
        $this->totalRfqTransfers = $total;

        foreach ($result as $name => $data) {
            $result[$name]['percentage'] = $total > 0
                ? round(($data['count'] / $total) * 100, 2)
                : 0;
        }

        $this->rfqTransferStatsByLeadOwner = $result;
    }

    public function fetchCallAttemptStatsByLeadOwner()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $leadOwners = User::where('role_id', 1)->pluck('id', 'name')->toArray();

        $result = [];
        foreach ($leadOwners as $name => $userId) {
            $count = ActivityLog::query()
                ->where('description', 'Transfer to Call Attempt, Done Call')
                ->whereBetween('created_at', [$start, $end])
                ->where('causer_id', $userId)
                ->count();

            if ($count > 0) {
                $result[$name] = ['count' => $count];
            }
        }

        $total = collect($result)->sum('count');
        $this->totalCallAttempts = $total;

        foreach ($result as $name => $data) {
            $result[$name]['percentage'] = $total > 0
                ? round(($data['count'] / $total) * 100, 2)
                : 0;
        }

        $this->callAttemptStatsByLeadOwner = $result;
    }

    public function fetchInactiveCallAttemptStatsByLeadOwner()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $leadOwners = User::where('role_id', 1)->pluck('id', 'name')->toArray();

        $result = [];
        foreach ($leadOwners as $name => $userId) {
            $count = ActivityLog::query()
                ->where('description', 'Transfer to Inactive Follow Up 2, Done Call')
                ->whereBetween('created_at', [$start, $end])
                ->where('causer_id', $userId)
                ->count();

            if ($count > 0) {
                $result[$name] = ['count' => $count];
            }
        }

        $total = collect($result)->sum('count');
        $this->totalInactiveCallAttempts = $total; // Use a separate property for inactive call attempts

        foreach ($result as $name => $data) {
            $result[$name]['percentage'] = $total > 0
                ? round(($data['count'] / $total) * 100, 2)
                : 0;
        }

        $this->inactiveCallAttemptStatsByLeadOwner = $result; // Store in a separate property
    }

    public function fetchArchivedStatsByLeadOwner()
    {
        $leadOwners = User::where('role_id', 1)->pluck('id', 'name')->toArray();

        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $result = [];

        foreach ($leadOwners as $name => $userId) {
            $count = ActivityLog::query()
                ->where('description', 'Lead marked as inactive.')
                ->whereBetween('created_at', [$start, $end])
                ->where('causer_id', $userId)
                ->count();

            if ($count > 0) {
                $result[$name] = ['count' => $count];
            }
        }

        $total = collect($result)->sum('count');
        $this->totalArchivedLeads = $total;

        foreach ($result as $name => $data) {
            $result[$name]['percentage'] = $total > 0
                ? round(($data['count'] / $total) * 100, 2)
                : 0;
        }

        $this->archiveStatsByLeadOwner = $result;
    }

    public function fetchAutomationEnabledStatsByLeadOwner()
    {
        $leadOwners = User::where('role_id', 1)->pluck('id', 'name')->toArray();

        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $result = [];
        foreach ($leadOwners as $name => $userId) {
            $count = ActivityLog::query()
                ->where('description', 'Automation Enabled')
                ->whereBetween('created_at', [$start, $end])
                ->where('causer_id', $userId)
                ->count();

            if ($count > 0) {
                $result[$name] = ['count' => $count];
            }
        }

        $total = collect($result)->sum('count');
        $this->totalAutomationEnabled = $total;

        foreach ($result as $name => $data) {
            $result[$name]['percentage'] = $total > 0
                ? round(($data['count'] / $total) * 100, 2)
                : 0;
        }

        $this->automationStatsByLeadOwner = $result;
    }

    public function openSlideOver($type, $owner)
    {
        $this->slideOverTitle = strtoupper($type) . ' Leads - ' . $owner;

        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $userId = User::where('name', $owner)->value('id');

        $query = Lead::query();

        if (in_array($type, ['pickup', 'demo', 'rfq'])) {
            $query->where('lead_owner', $owner);
        }

        if ($type === 'pickup') {
            $query->whereBetween('pickup_date', [$start, $end]);
        } elseif ($type === 'demo') {
            $userId = User::where('name', $owner)->value('id');

            $leadIds = Appointment::query()
                ->whereIn('status', ['New', 'Done'])
                ->where('causer_id', $userId)
                ->whereBetween('created_at', [$start, $end])
                ->pluck('lead_id')
                ->toArray();

            $query = Lead::whereIn('id', $leadIds);
        } elseif ($type === 'rfq') {
            $leadIds = ActivityLog::query()
                ->where('description', 'like', '%RFQ only%')
                ->whereBetween('created_at', [$start, $end])
                ->where('causer_id', $userId)
                ->pluck('subject_id')
                ->toArray();

            $query = Lead::whereIn('id', $leadIds);
        } elseif ($type === 'call') {
            $leadIds = ActivityLog::query()
                ->where('description', 'Transfer to Call Attempt, Done Call')
                ->whereBetween('created_at', [$start, $end])
                ->where('causer_id', $userId)
                ->pluck('subject_id')
                ->toArray();

            $query = Lead::whereIn('id', $leadIds);
        } elseif ($type === 'automation') {
            $leadIds = ActivityLog::query()
                ->where('description', 'Automation Enabled')
                ->whereBetween('created_at', [$start, $end])
                ->where('causer_id', $userId)
                ->pluck('subject_id')
                ->toArray();

            $query = Lead::whereIn('id', $leadIds);
        } elseif ($type === 'archive') {
            $leadIds = ActivityLog::query()
                ->where('description', 'Lead marked as inactive.')
                ->whereBetween('created_at', [$start, $end])
                ->where('causer_id', $userId)
                ->pluck('subject_id')
                ->toArray();

            $query = Lead::whereIn('id', $leadIds);
        } elseif ($type === 'inactivecall') {
            $leadIds = ActivityLog::query()
                ->where('description', 'Transfer to Inactive Follow Up 2, Done Call')
                ->whereBetween('created_at', [$start, $end])
                ->where('causer_id', $userId)
                ->pluck('subject_id')
                ->toArray();

            $query = Lead::whereIn('id', $leadIds);
        }

        $this->leadList = $query->with('companyDetail')->get();
        $this->showSlideOver = true;
    }
}
