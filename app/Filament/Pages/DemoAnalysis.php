<?php
namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\User;
use App\Models\Appointment;
use App\Models\PublicHoliday;
use App\Models\UserLeave;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\Concerns\InteractsWithPageTable;

class DemoAnalysis extends Page
{
    use InteractsWithPageTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.pages.demo-analysis';
    protected static ?string $navigationLabel = 'Demo Analysis';
    protected static ?string $title = '';
    protected static ?int $navigationSort = 8;
    protected static ?string $navigationGroup = 'Analysis';

    public $users;
    public $selectedUser;
    public $selectedMonth;

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

    public $days;
    public Carbon $currentDate;

    public function mount()
    {
        $authUser = auth()->user();
        $this->currentDate = Carbon::now();

        // Fetch only Salespersons (role_id = 2)
        $this->users = User::where('role_id', 2)->get();

        // Set default selected user based on role
        if ($authUser->role_id == 1) {
            $this->selectedUser = session('selectedUser', null);
        } elseif ($authUser->role_id == 2) {
            $this->selectedUser = $authUser->id; // Salesperson can only see their data
        }

        // Set default selected month
        $this->selectedMonth = session('selectedMonth', $this->currentDate->format('Y-m'));

        // Store in session
        session(['selectedUser' => $this->selectedUser, 'selectedMonth' => $this->selectedMonth]);

        // Fetch initial appointment data
        $this->fetchAppointments();
        $this->fetchNewDemoByCompanySize();
        $this->fetchWebinarDemoByCompanySize();
        $this->fetchNewDemoByLeadStatus();
        $this->fetchWebinarDemoByLeadStatus();
        $this->fetchSalespersonDays(); // Fetch working days
    }

    public function updatedSelectedUser($userId)
    {
        $this->selectedUser = $userId;
        session(['selectedUser' => $userId]);

        $this->fetchAppointments();
        $this->fetchNewDemoByCompanySize();
        $this->fetchWebinarDemoByCompanySize();
        $this->fetchNewDemoByLeadStatus();
        $this->fetchWebinarDemoByLeadStatus();
        $this->fetchSalespersonDays(); // Refresh working days
    }

    public function updatedSelectedMonth($month)
    {
        $this->selectedMonth = $month;
        session(['selectedMonth' => $month]);

        $this->fetchAppointments();
        $this->fetchNewDemoByCompanySize();
        $this->fetchWebinarDemoByCompanySize();
        $this->fetchNewDemoByLeadStatus();
        $this->fetchWebinarDemoByLeadStatus();
        $this->fetchSalespersonDays(); // Refresh working days
    }

    public function fetchAppointments()
    {
        $authUser = auth()->user();
        $appointmentTypes = ['NEW DEMO', 'WEBINAR DEMO', 'HRMS DEMO', 'SYSTEM DISCUSSION', 'HRDF DISCUSSION'];

        $query = Appointment::where('status', '!=', 'Cancelled');

        // Role-based filtering
        if ($authUser->role_id == 1 && !empty($this->selectedUser)) {
            $query->where('salesperson', $this->selectedUser);
        }
        if ($authUser->role_id == 2) {
            $query->where('salesperson', $authUser->id);
        }

        // Filter by Month
        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereBetween('date', [$date->startOfMonth()->format('Y-m-d'), $date->endOfMonth()->format('Y-m-d')]);
        }

        $this->totalAppointments = $query->count();

        // Fetch appointment type data
        $typeDataRaw = $query->whereIn('type', $appointmentTypes)
            ->select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();

        // Ensure all types exist in the result, even if 0
        $this->typeData = array_merge(array_fill_keys($appointmentTypes, 0), $typeDataRaw);
    }

    public function fetchNewDemoByCompanySize()
    {
        $authUser = auth()->user();
        $defaultCompanySizes = [
            'Small' => 0,
            'Medium' => 0,
            'Large' => 0,
            'Enterprise' => 0,
        ];

        $query = Appointment::where('type', 'NEW DEMO')
            ->where('status', '!=', 'Cancelled')
            ->whereHas('lead', function ($q) {
                $q->whereNotNull('company_size');
            });

        // Role-based filtering
        if ($authUser->role_id == 1 && !empty($this->selectedUser)) {
            $query->where('salesperson', $this->selectedUser);
        }
        if ($authUser->role_id == 2) {
            $query->where('salesperson', $authUser->id);
        }

        // Filter by Month
        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereBetween('date', [$date->startOfMonth()->format('Y-m-d'), $date->endOfMonth()->format('Y-m-d')]);
        }

        // Count total new demo appointments
        $this->totalNewAppointments = $query->count();

        // Fetch company size labels using Eloquent
        $companySizeData = $query
            ->with('lead') // Load the lead relationship
            ->get()
            ->groupBy(fn ($appointment) => $appointment->lead->company_size_label ?? 'Unknown')
            ->map(fn ($group) => $group->count())
            ->toArray();

        // Merge with default values to ensure all sizes are included
        $this->newDemoCompanySizeData = array_merge($defaultCompanySizes, $companySizeData);
    }

    public function fetchWebinarDemoByCompanySize()
    {
        $authUser = auth()->user();
        $defaultCompanySizes = [
            'Small' => 0,
            'Medium' => 0,
            'Large' => 0,
            'Enterprise' => 0,
        ];

        $query = Appointment::where('type', 'WEBINAR DEMO')
            ->where('status', '!=', 'Cancelled')
            ->whereHas('lead', function ($q) {
                $q->whereNotNull('company_size');
            });

        // Role-based filtering
        if ($authUser->role_id == 1 && !empty($this->selectedUser)) {
            $query->where('salesperson', $this->selectedUser);
        }
        if ($authUser->role_id == 2) {
            $query->where('salesperson', $authUser->id);
        }

        // Filter by Month
        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereBetween('date', [$date->startOfMonth()->format('Y-m-d'), $date->endOfMonth()->format('Y-m-d')]);
        }

        // Count total webinar demo appointments
        $this->totalWebinarAppointments = $query->count();

        // Fetch company size labels using Eloquent
        $companySizeData = $query
            ->with('lead') // Load the lead relationship
            ->get()
            ->groupBy(fn ($appointment) => $appointment->lead->company_size_label ?? 'Unknown')
            ->map(fn ($group) => $group->count())
            ->toArray();

        // Merge with default values to ensure all sizes are included
        $this->webinarDemoCompanySizeData = array_merge($defaultCompanySizes, $companySizeData);
    }

    public function fetchNewDemoByLeadStatus()
    {
        $authUser = auth()->user();
        $defaultLeadStatuses = [
            'Closed' => 0,
            'Lost' => 0,
            'On Hold' => 0,
            'No Response' => 0,
        ];

        $query = Appointment::where('type', 'NEW DEMO')
            ->where('status', '!=', 'Cancelled')
            ->whereHas('lead', function ($q) {
                $q->whereIn('lead_status', ['Closed', 'Lost', 'On Hold', 'No Response']);
            });

        // Role-based filtering
        if ($authUser->role_id == 1 && !empty($this->selectedUser)) {
            $query->where('salesperson', $this->selectedUser);
        }
        if ($authUser->role_id == 2) {
            $query->where('salesperson', $authUser->id);
        }

        // Filter by Month
        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereBetween('date', [$date->startOfMonth()->format('Y-m-d'), $date->endOfMonth()->format('Y-m-d')]);
        }

        // Count total new demo appointments
        $this->totalNewAppointmentsByLeadStatus = $query->count();

        // Fetch lead statuses using Eloquent
        $leadStatusData = $query
            ->with('lead') // Load the lead relationship
            ->get()
            ->groupBy(fn ($appointment) => $appointment->lead->lead_status ?? 'Unknown')
            ->map(fn ($group) => $group->count())
            ->toArray();

        // Merge with default values to ensure all statuses exist
        $this->newDemoLeadStatusData = array_merge($defaultLeadStatuses, $leadStatusData);
    }

    public function fetchWebinarDemoByLeadStatus()
    {
        $authUser = auth()->user();
        $defaultLeadStatuses = [
            'Closed' => 0,
            'Lost' => 0,
            'On Hold' => 0,
            'No Response' => 0,
        ];

        $query = Appointment::where('type', 'WEBINAR DEMO')
            ->where('status', '!=', 'Cancelled')
            ->whereHas('lead', function ($q) {
                $q->whereIn('lead_status', ['Closed', 'Lost', 'On Hold', 'No Response']);
            });

        // Role-based filtering
        if ($authUser->role_id == 1 && !empty($this->selectedUser)) {
            $query->where('salesperson', $this->selectedUser);
        }
        if ($authUser->role_id == 2) {
            $query->where('salesperson', $authUser->id);
        }

        // Filter by Month
        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereBetween('date', [$date->startOfMonth()->format('Y-m-d'), $date->endOfMonth()->format('Y-m-d')]);
        }

        // Count total webinar demo appointments
        $this->totalWebinarAppointmentsByLeadStatus = $query->count();

        // Fetch lead statuses using Eloquent
        $leadStatusData = $query
            ->with('lead') // Load the lead relationship
            ->get()
            ->groupBy(fn ($appointment) => $appointment->lead->lead_status ?? 'Unknown')
            ->map(fn ($group) => $group->count())
            ->toArray();

        // Merge with default values to ensure all statuses exist
        $this->webinarDemoLeadStatusData = array_merge($defaultLeadStatuses, $leadStatusData);
    }

    public function fetchSalespersonDays()
    {
        // Ensure currentDate is properly set for selected month
        $this->currentDate = Carbon::parse($this->selectedMonth);

        $this->days = [
            "totalDays" => $this->currentDate->daysInMonth,
            "publicHolidays" => 0,
            "weekends" => 0,
            "leave" => 0,
            "workingDays" => 0,
        ];

        // Get Public Holidays within the selected month
        $this->days['publicHolidays'] = PublicHoliday::whereBetween('date', [
            $this->currentDate->startOfMonth()->toDateString(),
            $this->currentDate->endOfMonth()->toDateString()
        ])->count();

        // Get Salesperson Leave (Only if a salesperson is selected)
        if (!empty($this->selectedUser)) {
            $this->days["leave"] = UserLeave::where('user_ID', $this->selectedUser)
                ->whereBetween('date', [
                    $this->currentDate->startOfMonth()->toDateString(),
                    $this->currentDate->endOfMonth()->toDateString()
                ])->count();
        }

        // Get Weekends (Saturday + Sunday) **Fixed Loop**
        $weekendCount = 0;
        $startDate = $this->currentDate->copy()->startOfMonth();
        $endDate = $this->currentDate->copy()->endOfMonth();

        while ($startDate->lte($endDate)) {
            if ($startDate->isSaturday() || $startDate->isSunday()) {
                $weekendCount++;
            }
            $startDate->addDay();
        }
        $this->days['weekends'] = $weekendCount;

        // Calculate Working Days
        $this->days["workingDays"] = max(0, $this->days['totalDays'] - $this->days['publicHolidays'] - $this->days['leave'] - $this->days['weekends']);
    }


    // Get Total Days in Selected Month
    private function getNumberOfDays(Carbon $date)
    {
        return $date->daysInMonth;
    }
}
