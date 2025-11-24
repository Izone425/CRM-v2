<?php

namespace App\Livewire;

use App\Classes\Encryptor;
use App\Models\PublicHoliday;
use App\Models\User;
use App\Models\UserLeave;
use Carbon\Carbon;
use Illuminate\Database\Console\DumpCommand;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Illuminate\Support\Str;

class Calendar extends Component
{

    public $rows;
    public Carbon $date;
    public $startDate;
    public $endDate;
    public $weekDays;
    public $selectedMonth;
    public $holidays;
    public $leaves;
    public $monthList;
    public $currentMonth;
    public $weekDate;
    public $newDemoCount;
    public $employees;

    //Dropdown
    public $showDropdown = false;

    // Badge
    public $totalDemos;

    // Dropdown
    public array $status = ["DONE", "NEW", "CANCELLED"];
    public array $selectedStatus = [];
    public bool $allStatusSelected = true;
    public $selectedDepartment = 'all';
    public $leaveSummary = [];
    public $todayLeaveSummary = [];
    public $selectedLeaveType = 'all';
    public $allEmployeeLeaves;

    public Collection $salesPeople;
    public array $selectedSalesPeople = [];
    public bool $allSalesPeopleSelected = true;

    public array $demoTypes = ["NEW DEMO", "WEBINAR DEMO", "HRMS DEMO", "SYSTEM DISCUSSION", "HRDF DISCUSSION"];
    public array $selectedDemoType = [];
    public bool $allDemoTypeSelected = true;

    public array $appointmentTypes = ["ONLINE", "ONSITE"];
    public array $selectedAppointmentType = [];
    public bool $allAppointmentTypeSelected = true;

    public $newDemoCompanySizeBreakdown = [];

    public $salesFilter = 'timetec_hr'; // Default selected filter
    public $timetecHrSalesIds = [10, 6, 7, 8, 9, 11, 12]; // TimeTec HR Sales user IDs

    // Add this new function to update filter selection
    public function updateSalesFilter($filter)
    {
        $this->salesFilter = $filter;

        // Reset salesperson selection based on the filter
        $this->selectedSalesPeople = [];
        $this->allSalesPeopleSelected = false;

        if ($filter === 'timetec_hr') {
            // Auto-select TimeTec HR Sales team members
            $this->selectedSalesPeople = $this->timetecHrSalesIds;
        }
        // For 'all_sales', we leave selectedSalesPeople empty which will show all
    }

    public function filterByDepartment($department)
    {
        $this->selectedDepartment = $department;

        // Recalculate leave summary with new department filter
        $this->calculateLeaveSummary();
    }

    public function filterByLeaveType($leaveType)
    {
        $this->selectedLeaveType = $leaveType;
    }

    // Modify the mount method to set default filter
    public function mount()
    {
        // Load all salespeople model
        $this->salesPeople = $this->getAllSalesPeople();

        // Set Date to today
        $this->date = Carbon::now();

        // If current user is a salesperson then only can access their own calendar
        if (auth()->user()->role_id == 2) {
            $this->selectedSalesPeople[] = auth()->user()->id;
        } else {
            // Default to TimeTec HR Sales for admin users
            $this->updateSalesFilter('timetec_hr');
        }
    }

    private function calculateLeaveSummary()
    {
        // Initialize summary arrays
        $this->leaveSummary = [];
        $this->todayLeaveSummary = [];

        // Get departments based on filter
        $departments = collect($this->getAllEmployeesByDepartment());

        // Apply department filter if not showing all
        if ($this->selectedDepartment !== 'all') {
            $departments = $departments->filter(function($employee) {
                return $employee->department === $this->selectedDepartment;
            });
        }

        // Get unique departments excluding Vice President
        $departments = $departments
            ->pluck('department')
            ->unique()
            ->filter(function($dept) {
                return $dept !== 'Vice President';
            })
            ->toArray();

        // Initialize summary for each department
        foreach ($departments as $department) {
            $this->leaveSummary[$department] = [
                'full_day' => 0,
                'half_day_am' => 0,
                'half_day_pm' => 0,
                'am_plus_pm' => 0,  // Add this new counter
                'total' => 0
            ];

            $this->todayLeaveSummary[$department] = [
                'full_day' => 0,
                'half_day_am' => 0,
                'half_day_pm' => 0,
                'am_plus_pm' => 0,  // Add this new counter
                'total' => 0
            ];
        }

        // Use the selected date instead of current date
        $selectedDate = $this->date->format('Y-m-d');

        // Group employees by department
        $employeesByDept = $this->getAllEmployeesByDepartment()
            ->filter(function($employee) {
                return $employee->department !== 'Vice President' &&
                    ($this->selectedDepartment === 'all' || $employee->department === $this->selectedDepartment);
            })
            ->groupBy('department');

        // For each department
        foreach ($employeesByDept as $department => $employees) {
            // Get employee IDs for this department
            $employeeIds = $employees
                ->filter(function($employee) {
                    return !is_string($employee->id) || !str_contains($employee->id, 'placeholder');
                })
                ->pluck('id')
                ->toArray();

            // Get leaves for these employees during the week
            $deptLeaves = UserLeave::getAllLeavesForDateRange($this->startDate, $this->endDate, $employeeIds);

            // Track AM+PM combinations per user per date
            $amPmCombinations = [];

            // Count leaves by type
            foreach ($deptLeaves as $userId => $userLeaves) {
                foreach ($userLeaves as $date => $leave) {
                    // Track AM and PM leaves separately for combination detection
                    if (!isset($amPmCombinations[$userId][$date])) {
                        $amPmCombinations[$userId][$date] = ['am' => false, 'pm' => false];
                    }

                    if ($leave['session'] === 'full') {
                        $this->leaveSummary[$department]['full_day']++;
                        $this->leaveSummary[$department]['total']++;

                        if ($date === $selectedDate) {
                            $this->todayLeaveSummary[$department]['full_day']++;
                            $this->todayLeaveSummary[$department]['total']++;
                        }
                    } else if ($leave['session'] === 'am') {
                        $amPmCombinations[$userId][$date]['am'] = true;
                        $this->leaveSummary[$department]['half_day_am']++;
                        $this->leaveSummary[$department]['total'] += 0.5;

                        if ($date === $selectedDate) {
                            $this->todayLeaveSummary[$department]['half_day_am']++;
                            $this->todayLeaveSummary[$department]['total'] += 0.5;
                        }
                    } else if ($leave['session'] === 'pm') {
                        $amPmCombinations[$userId][$date]['pm'] = true;
                        $this->leaveSummary[$department]['half_day_pm']++;
                        $this->leaveSummary[$department]['total'] += 0.5;

                        if ($date === $selectedDate) {
                            $this->todayLeaveSummary[$department]['half_day_pm']++;
                            $this->todayLeaveSummary[$department]['total'] += 0.5;
                        }
                    }
                }
            }

            // Count AM+PM combinations
            foreach ($amPmCombinations as $userId => $userDates) {
                foreach ($userDates as $date => $sessions) {
                    if ($sessions['am'] && $sessions['pm']) {
                        $this->leaveSummary[$department]['am_plus_pm']++;

                        if ($date === $selectedDate) {
                            $this->todayLeaveSummary[$department]['am_plus_pm']++;
                        }
                    }
                }
            }
        }
    }

    //Update date variable when user choose another date
    public function updatedWeekDate()
    {
        $this->date = Carbon::parse($this->weekDate);
    }

    // For Filtering
    public function updatedAllSalesPeopleSelected()
    {
        if ($this->allSalesPeopleSelected == true)
            $this->selectedSalesPeople = [];
    }

    public function updatedSelectedStatus()
    {
        if (!empty($this->selectedStatus)) {
            $this->allStatusSelected = false;
        } else
            $this->allStatusSelected = true;
    }

    public function updatedAllStatusSelected()
    {
        if ($this->allStatusSelected == true)
            $this->selectedStatus = [];
    }

    public function updatedSelectedDemoType()
    {
        if (!empty($this->selectedDemoType)) {
            $this->allDemoTypeSelected = false;
        } else
            $this->allDemoTypeSelected = true;
    }
    public function updatedAllDemoTypeSelected()
    {
        if ($this->allDemoTypeSelected == true)
            $this->selectedDemoType = [];
    }

    public function updatedSelectedAppointmentType()
    {
        if (!empty($this->selectedAppointmentType)) {
            $this->allAppointmentTypeSelected = false;
        } else
            $this->allAppointmentTypeSelected = true;
    }
    public function updatedAllAppointmentTypeSelected()
    {
        if ($this->allAppointmentTypeSelected == true)
            $this->selectedAppointmentType = [];
    }

    // Get Total Number of Demos for New, Webinar and others
    private function getNumberOfDemos($selectedSalesPeople = null)
    {
        // Define internal sales task types
        $internalSalesTaskTypes = [
            'EXHIBITION',
            'INTERNAL MEETING',
            'SALES MEETING',
            'PRODUCT MEETING',
            'TOWNHALL SESSION',
            'FOLLOW UP SESSION',
            'BUSINESS TRIP'
        ];

        if (!empty($selectedSalesPeople)) {
            $this->totalDemos = [
                "ALL" => 0,
                'NEW DEMO' => 0,
                "WEBINAR DEMO" => 0,
                "INTERNAL SALES TASK" => 0,  // New category
                "OTHERS" => 0
            ];

            $this->totalDemos["ALL"] = DB::table('appointments')->whereNot('status', 'Cancelled')->whereIn("salesperson", $selectedSalesPeople)->whereBetween('date', [$this->startDate, $this->endDate])->count();
            $this->totalDemos["NEW DEMO"] = DB::table('appointments')->where("type", "NEW DEMO")->whereNot('status', 'Cancelled')->whereIn("salesperson", $selectedSalesPeople)->whereBetween('date', [$this->startDate, $this->endDate])->count();
            $this->totalDemos["WEBINAR DEMO"] = DB::table('appointments')->where("type", "WEBINAR DEMO")->whereNot('status', 'Cancelled')->whereIn("salesperson", $selectedSalesPeople)->whereBetween('date', [$this->startDate, $this->endDate])->count();
            $this->totalDemos["INTERNAL SALES TASK"] = DB::table('appointments')->whereIn("type", $internalSalesTaskTypes)->whereNot('status', 'Cancelled')->whereIn("salesperson", $selectedSalesPeople)->whereBetween('date', [$this->startDate, $this->endDate])->count();
            $this->totalDemos["OTHERS"] = DB::table('appointments')
                ->whereNotIn("type", array_merge(["NEW DEMO", "WEBINAR DEMO"], $internalSalesTaskTypes))
                ->whereNot('status', 'Cancelled')
                ->whereIn("salesperson", $selectedSalesPeople)
                ->whereBetween('date', [$this->startDate, $this->endDate])
                ->count();

            // Status counts remain the same
            $this->totalDemos["NEW"] = DB::table('appointments')->where("status", "New")->whereIn("salesperson", $selectedSalesPeople)->whereBetween('date', [$this->startDate, $this->endDate])->count();
            $this->totalDemos["DONE"] = DB::table('appointments')->where("status", "Done")->whereIn("salesperson", $selectedSalesPeople)->whereBetween('date', [$this->startDate, $this->endDate])->count();
            $this->totalDemos["CANCELLED"] = DB::table('appointments')->where("status", "Cancelled")->whereIn("salesperson", $selectedSalesPeople)->whereBetween('date', [$this->startDate, $this->endDate])->count();
        } else {
            $this->totalDemos = [
                "ALL" => 0,
                'NEW DEMO' => 0,
                "WEBINAR DEMO" => 0,
                "INTERNAL SALES TASK" => 0,  // New category
                "OTHERS" => 0
            ];

            $this->totalDemos["ALL"] = DB::table('appointments')->whereNot('status', 'Cancelled')->whereBetween('date', [$this->startDate, $this->endDate])->count();
            $this->totalDemos["NEW DEMO"] = DB::table('appointments')->where("type", "NEW DEMO")->whereNot('status', 'Cancelled')->whereBetween('date', [$this->startDate, $this->endDate])->count();
            $this->totalDemos["WEBINAR DEMO"] = DB::table('appointments')->where("type", "WEBINAR DEMO")->whereNot('status', 'Cancelled')->whereBetween('date', [$this->startDate, $this->endDate])->count();
            $this->totalDemos["INTERNAL SALES TASK"] = DB::table('appointments')->whereIn("type", $internalSalesTaskTypes)->whereNot('status', 'Cancelled')->whereBetween('date', [$this->startDate, $this->endDate])->count();
            $this->totalDemos["OTHERS"] = DB::table('appointments')
                ->whereNotIn("type", array_merge(["NEW DEMO", "WEBINAR DEMO"], $internalSalesTaskTypes))
                ->whereNot('status', 'Cancelled')
                ->whereBetween('date', [$this->startDate, $this->endDate])
                ->count();

            // Status counts remain the same
            $this->totalDemos["NEW"] = DB::table('appointments')->where("status", "New")->whereBetween('date', [$this->startDate, $this->endDate])->count();
            $this->totalDemos["DONE"] = DB::table('appointments')->where("status", "Done")->whereBetween('date', [$this->startDate, $this->endDate])->count();
            $this->totalDemos["CANCELLED"] = DB::table('appointments')->where("status", "Cancelled")->whereBetween('date', [$this->startDate, $this->endDate])->count();
        }
    }

    private function getWeekDateDays($date = null)
    {
        $date = $date ? Carbon::parse($date) : Carbon::now();

        // Get the start of the week (Monday by default)
        $startOfWeek = $date->startOfWeek();

        // Iterate through the week (7 days) and get each day's date
        $weekDays = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $startOfWeek->copy()->addDays($i);
            $weekDays[$i]["day"] = $day->format('D');  // Format as Fri,Sat,Mon
            $weekDays[$i]["date"] = $day->format('j');  // Format as Date
            $weekDays[$i]['full_date'] = $day->format('Y-m-d'); // Add full date in YYYY-MM-DD format
            $weekDays[$i]['carbonDate'] = $day;
            if ($day->isToday()) {
                $weekDays[$i]["today"] = true; // Set to true if today's date is found
            } else {
                $weekDays[$i]["today"] = false;
            }
        }
        return $weekDays;
    }

    private function getWeeklyAppointments($date = null)
    {

        //Have to make sure weekly is weekly date. Monday to Friday
        $date = $date ? Carbon::parse($date) : Carbon::now();
        $this->startDate = $date->copy()->startOfWeek()->toDateString(); // Monday
        $this->endDate = $date->copy()->startOfWeek()->addDays(4)->toDateString(); // Friday
        //Retreive all appointments for each salesperson with company details between start and end date. If filter present, then filter
        $appointments = DB::table('appointments')
            ->join('users', 'users.id', '=', 'appointments.salesperson')
            ->leftJoin('company_details', 'company_details.lead_id', '=', 'appointments.lead_id')
            ->select('users.name', "company_details.company_name", 'appointments.*')
            ->whereBetween("date", [$this->startDate, $this->endDate])
            ->orderBy('start_time', 'asc')
            ->when($this->selectedSalesPeople, function ($query) {
                return $query->whereIn('users.id', $this->selectedSalesPeople);
            })
            ->get();

        //Salespeople filtering, retrieve only selected or all
        if (!empty($this->selectedSalesPeople)) {
            $salesPeople = $this->getSelectedSalesPeople($this->selectedSalesPeople);
            $this->allSalesPeopleSelected = false;
        } else {
            $this->allSalesPeopleSelected = true;
            $salesPeople = $this->salesPeople;
        }

        $result = $salesPeople->map(function (User $salesperson) use ($appointments) {

            // Initialize fields for each day of the week
            $data = [
                'salespersonID' => $salesperson['id'],
                'salespersonName' => $salesperson['name'],
                'salespersonAvatar' => $salesperson->getFilamentAvatarUrl(),
                'mondayAppointments' => [],
                'tuesdayAppointments' => [],
                'wednesdayAppointments' => [],
                'thursdayAppointments' => [],
                'fridayAppointments' => [],
                'newDemo' => [
                    'monday' => 0,
                    'tuesday' => 0,
                    'wednesday' => 0,
                    'thursday' => 0,
                    'friday' => 0,
                ],
                'leave' => UserLeave::getUserLeavesByDateRange($salesperson['id'], $this->startDate, $this->endDate),
            ];

            // Retrieve from $appointments using salesperson ID
            $salespersonAppointments = $appointments->where('salesperson', $salesperson['id']);

            // Group appointments by the day of the week
            foreach ($salespersonAppointments as $appointment) {
                $dayOfWeek = strtolower(Carbon::parse($appointment->date)->format('l')); // e.g., 'monday'
                $dayField = "{$dayOfWeek}Appointments";

                $appointment->start_time = Carbon::parse($appointment->start_time)->format('g:i A');
                $appointment->end_time = Carbon::parse($appointment->end_time)->format('g:i A');

                // Set a default company name for internal tasks or null lead_id
                if (empty($appointment->company_name)) {
                    // For internal tasks or appointments without company names
                    if (in_array($appointment->type, [
                        'EXHIBITION',
                        'INTERNAL MEETING',
                        'SALES MEETING',
                        'PRODUCT MEETING',
                        'TOWNHALL SESSION',
                        'FOLLOW UP SESSION',
                        'BUSINESS TRIP'
                    ])) {
                        // For internal tasks, use the type as the company name
                        $appointment->company_name = $appointment->type;
                        $appointment->is_internal_task = true;
                        // Truncate remarks for display if they're too long
                        $appointment->display_remarks = !empty($appointment->remarks)
                            ? (strlen($appointment->remarks) > 30
                                ? substr($appointment->remarks, 0, 30) . '...'
                                : $appointment->remarks)
                            : 'No remarks';
                    } else {
                        $appointment->company_name = 'INTERNAL TASK';
                        $appointment->is_internal_task = false;
                    }
                } else {
                    $appointment->is_internal_task = false;
                }

                // Set URL - handle null lead_id
                if ($appointment->lead_id) {
                    $appointment->url = route('filament.admin.resources.leads.view', ['record' => Encryptor::encrypt($appointment->lead_id)]);
                } else {
                    // Provide a fallback URL for internal tasks without lead ID
                    $appointment->url = '#';
                }

                // For new demo summary which shows no,1,2 new demo
                if ($appointment->type === "NEW DEMO") {
                    if ($appointment->status !== "Cancelled") {
                        $data['newDemo'][$dayOfWeek]++;
                    }
                }

                // Filtering Demo Type and Appointment Type
                if (
                    $this->allAppointmentTypeSelected && $this->allDemoTypeSelected
                    || in_array($appointment->type, $this->selectedDemoType) && $this->allAppointmentTypeSelected
                    || $this->allDemoTypeSelected && in_array($appointment->appointment_type, $this->selectedAppointmentType)
                    || in_array($appointment->type, $this->selectedDemoType) && in_array($appointment->appointment_type, $this->selectedAppointmentType)
                ) {
                    if ($this->allStatusSelected || in_array(Str::upper($appointment->status), $this->selectedStatus)) {
                        $data[$dayField][] = $appointment;
                        $appointment->start_time = Carbon::parse($appointment->start_time)->format('g:i A');
                        $appointment->end_time = Carbon::parse($appointment->end_time)->format('g:i A');
                        $appointment->url = route('filament.admin.resources.leads.view', ['record' => Encryptor::encrypt($appointment->lead_id)]);
                    }
                }
            }

            $this->countNewDemos($data['newDemo']);
            return $data;
        });
        return $result;
    }

    public function prevWeek()
    {
        $this->date->subDays(7);
    }

    public function nextWeek()
    {
        $this->date->addDays(7);
    }

    public function getAllSalesPeople()
    {
        return User::where('role_id', '2')
            ->select('users.id', 'users.name', 'users.api_user_id', 'users.avatar_path')
            ->join('demo_rankings', 'users.id', '=', 'demo_rankings.user_id')
            ->orderBy('demo_rankings.rank', 'asc') // or 'desc' if you want highest rank first
            ->get();
    }

    public function getSelectedSalesPeople(array $arr)
    {
        return User::where('role_id', '2')
            ->select('users.id', 'users.name', 'users.api_user_id', 'users.avatar_path')
            ->whereIn('users.id', $arr)
            ->join('demo_rankings', 'users.id', '=', 'demo_rankings.user_id')
            ->orderBy('demo_rankings.rank', 'asc') // or 'desc' if you want highest rank first
            ->get();
    }

    private function countNewDemos($data)
    {

        foreach ($data as $day => $value) {
            if ($value == 0) {
                $this->newDemoCount[$day]["noDemo"] = ($this->newDemoCount[$day]["noDemo"] ?? 0) + 1;
            } else if ($value == 1) {
                $this->newDemoCount[$day]["oneDemo"] = ($this->newDemoCount[$day]["oneDemo"] ?? 0) + 1;
            } else if ($value == 2) {
                $this->newDemoCount[$day]["twoDemo"] = ($this->newDemoCount[$day]["twoDemo"] ?? 0) + 1;
            }
        }
    }

    public function render()
    {
        // Set date range for the week
        $date = $this->date ? Carbon::parse($this->date) : Carbon::now();
        $this->startDate = $date->copy()->startOfWeek()->toDateString(); // Monday
        $this->endDate = $date->copy()->startOfWeek()->addDays(4)->toDateString(); // Friday

        // Get week days for display
        $this->weekDays = $this->getWeekDateDays($date);

        // Get current month for display
        $this->currentMonth = $date->format('F Y');

        // Get all employees organized by department in the specified order
        $allEmployees = $this->getAllEmployeesByDepartment();

        // Filter employees by department if a specific one is selected
        if ($this->selectedDepartment !== 'all') {
            $this->employees = $allEmployees->filter(function($employee) {
                return $employee->department === $this->selectedDepartment;
            });
        } else {
            $this->employees = $allEmployees;
        }

        // Get public holidays for the week
        $this->holidays = PublicHoliday::getPublicHoliday($this->startDate, $this->endDate);

        // Get leaves for filtered employees
        $employeeIds = $this->employees
            ->filter(function($employee) {
                return !is_string($employee->id) || !str_contains($employee->id, 'placeholder');
            })
            ->pluck('id')
            ->toArray();

        // Get leaves from UserLeave model
        $allLeaves = UserLeave::getAllLeavesForDateRange($this->startDate, $this->endDate, $employeeIds);

        if ($this->selectedLeaveType !== 'all') {
            // Keep the original leaves for reference
            $this->allEmployeeLeaves = $allLeaves;

            // Filter leaves by type
            $filteredLeaves = [];

            if ($this->selectedLeaveType === 'am_plus_pm') {
                // Special handling for AM+PM combination
                foreach ($allLeaves as $userId => $userLeaves) {
                    $matching = [];
                    $datesSessions = [];

                    // Group leaves by date
                    foreach ($userLeaves as $date => $leave) {
                        if (!isset($datesSessions[$date])) {
                            $datesSessions[$date] = [];
                        }
                        $datesSessions[$date][] = $leave['session'];
                    }

                    // Check for dates with both AM and PM
                    foreach ($datesSessions as $date => $sessions) {
                        if (in_array('am', $sessions) && in_array('pm', $sessions)) {
                            // Include both AM and PM leaves for this date
                            foreach ($userLeaves as $leaveDate => $leave) {
                                if ($leaveDate === $date && in_array($leave['session'], ['am', 'pm'])) {
                                    $matching[$leaveDate] = $leave;
                                }
                            }
                        }
                    }

                    // Only include employees with AM+PM combinations
                    if (!empty($matching)) {
                        $filteredLeaves[$userId] = $matching;
                    }
                }
            } else {
                // Regular filtering for other leave types
                foreach ($allLeaves as $userId => $userLeaves) {
                    $matching = [];

                    foreach ($userLeaves as $date => $leave) {
                        if ($leave['session'] === $this->selectedLeaveType) {
                            $matching[$date] = $leave;
                        }
                    }

                    // Only include employees with matching leave types
                    if (!empty($matching)) {
                        $filteredLeaves[$userId] = $matching;
                    }
                }
            }

            $this->leaves = $filteredLeaves;
        } else {
            $this->leaves = $allLeaves;
        }

        // Calculate leave summaries
        $this->calculateLeaveSummary();

        return view('livewire.calendar');
    }


    public function getDepartments()
    {
        $departments = collect($this->getAllEmployeesByDepartment())->pluck('department')->unique()->toArray();
        return $departments;
    }

    public function calculateNewDemoCompanySize()
    {
        $query = \App\Models\Appointment::with('lead')
            ->where('type', 'NEW DEMO')
            ->where('status', '!=', 'Cancelled')
            ->whereBetween('date', [$this->startDate, $this->endDate]);

        if (!empty($this->selectedSalesPeople)) {
            $query->whereIn('salesperson', $this->selectedSalesPeople);
        }

        $appointments = $query->get();

        $result = [
            'Small' => 0,
            'Medium' => 0,
            'Large' => 0,
            'Enterprise' => 0,
        ];

        foreach ($appointments as $appointment) {
            $label = $appointment->lead?->company_size_label ?? 'Unknown';
            $result[$label] = ($result[$label] ?? 0) + 1;
        }

        $this->newDemoCompanySizeBreakdown = $result;
    }

    private function getAllEmployeesByDepartment()
    {
        // Define department order and employee sequence according to requirements
        $departmentEmployees = [
            'Vice President' => [
                ['name' => 'Faiz Shu - Izhan', 'order' => 1]
            ],
            'Admin Department' => [
                ['name' => 'Fatimah Nurnabilah', 'order' => 2],
                ['name' => 'Nurul Najaa Nadiah', 'order' => 3],
                ['name' => 'Norhaiyati', 'order' => 4],
                ['name' => 'Sheena Liew', 'order' => 5],
                ['name' => 'Nur Irdina', 'order' => 6],
            ],
            'SalesPerson Department' => [
                ['name' => 'Wan Amirul Muim', 'order' => 7],
                ['name' => 'Muhammad Khoirul Bariah', 'order' => 8],
                ['name' => 'Yasmin', 'order' => 9],
                ['name' => 'Abdul Aziz', 'order' => 10],
                ['name' => 'Farhanah Jamil', 'order' => 11],
                ['name' => 'Joshua Ho', 'order' => 12],
                ['name' => 'Vince Leong', 'order' => 13]
            ],
            'Support Department' => [
                ['name' => 'Siti Nadia', 'order' => 14],
                ['name' => 'Noor Syazana', 'order' => 15],
                ['name' => 'Ummu Najwa Fajrina', 'order' => 16],
                ['name' => 'Ahmad Syazwan', 'order' => 17],
                ['name' => 'Rahmah', 'order' => 18],
                ['name' => 'Hanif Razali', 'order' => 19]
            ],
            'Implementer Department' => [
                ['name' => 'Ahmad Syamim', 'order' => 20],
                ['name' => 'Siti Shahilah', 'order' => 21],
                ['name' => 'Muhamad Izzul Aiman', 'order' => 22],
                ['name' => 'Zulhilmie', 'order' => 23],
                ['name' => 'Nur Alia', 'order' => 24],
                ['name' => 'John Low', 'order' => 25],
                ['name' => 'Mohd Amirul Ashraf', 'order' => 26],
                ['name' => 'Nur Fazuliana', 'order' => 27],
                ['name' => 'Ameerul Asyraf', 'order' => 28],
            ],
            'Trainer Department' => [
                ['name' => 'Mohd Fairos', 'order' => 29]
            ],
            'Technician Department' => [
                ['name' => 'Khairul Izzuddin', 'order' => 30]
            ]
        ];

        // Convert to a collection
        $employees = collect();

        foreach ($departmentEmployees as $department => $staffs) {
            foreach ($staffs as $staff) {
                // Find the user in the database
                $user = User::where('name', 'like', "%{$staff['name']}%")->first();

                if (!$user) {
                    // Create a placeholder if user not found in database
                    $user = new \stdClass();
                    $user->id = "placeholder-{$staff['order']}";
                    $user->name = $staff['name'];
                    $user->avatar_path = null;
                }

                $user->department = $department;
                $user->display_order = $staff['order'];
                $employees->push($user);
            }
        }

        return $employees->sortBy('display_order');
    }
}
