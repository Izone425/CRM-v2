<?php

namespace App\Livewire;

use App\Classes\Encryptor;
use App\Models\PublicHoliday;
use App\Models\User;
use App\Models\UserLeave;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

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
    public $newDemo;

    // Badge
    public $totalDemos;

    // Dropdown
    public Collection $salesPeople;
    public array $selectedSalesPeople = [];
    public bool $allSalesPeopleSelected = true;

    public array $demoTypes = ["NEW DEMO","WEBINAR DEMO","HRMS DEMO","SYSTEM DISCUSSION","HRDF DISCUSSION"];
    public array $selectedDemoType = [];
    public bool $allDemoTypeSelected = true;

    public array $appointmentTypes = ["ONLINE","ONSITE"];
    public array $selectedAppointmentType = [];
    public bool $allAppointmentTypeSelected = true;



    public function mount()
    {
        $this->salesPeople = $this->getAllSalesPeople();
        $this->date = Carbon::now();
    }

    // For Filter
    public function updatedAllSalesPeopleSelected(){
        if($this->allSalesPeopleSelected == true)
            $this->selectedSalesPeople = [];
    }


    public function updatedSelectedDemoType(){
        if(!empty($this->selectedDemoType)){
            $this->allDemoTypeSelected = false;
        }
        else
        $this->allDemoTypeSelected = true;

    }
    public function updatedAllDemoTypeSelected(){
        if($this->allDemoTypeSelected == true)
            $this->selectedDemoType = [];
    }

    public function updatedSelectedAppointmentType(){
        if(!empty($this->selectedAppointmentType)){
            $this->allAppointmentTypeSelected = false;
        }
        else
            $this->allAppointmentTypeSelected = true;

    }
    public function updatedAllAppointmentTypeSelected(){
        if($this->allAppointmentTypeSelected == true)
            $this->selectedAppointmentType = [];
    }

    // Get Total Number of Demos for New, Webinar and others
    private function getNumberOfDemos()
    {
        $this->totalDemos = ["ALL", 'NEW DEMO' => 0, "WEBINAR DEMO" => 0, "OTHERS" => 0];
        $this->totalDemos["ALL"] = DB::table('appointments')->count();
        $this->totalDemos["NEW DEMO"] = DB::table('appointments')->where("type", "NEW DEMO")->count();
        $this->totalDemos["WEBINAR DEMO"] = DB::table('appointments')->where("type", "WEBINAR DEMO")->count();
        $this->totalDemos["OTHERS"] = DB::table('appointments')->whereNotIn("type", ["NEW DEMO", "WEBINAR DEMO"])->count();
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
            $weekDays[$i]["day"] = $startOfWeek->copy()->addDays($i)->format('D');  // Format as Fri,Sat,Mon
            $weekDays[$i]["date"] = $startOfWeek->copy()->addDays($i)->format('j');  // Format as Date
            $weekDays[$i]['carbonDate'] = $startOfWeek->copy()->addDays($i);
            if ($day->isToday()) {
                $weekDays[$i]["today"] = true; // Set to true if today's date is found
            } else
                $weekDays[$i]["today"] = false;
        }
        return $weekDays;
    }

    private function getWeeklyAppointments($date = null)
    {

        //Have to make sure weekly is weekly date. Monday to sunday
        $date = $date ? Carbon::parse($date) : Carbon::now();
        $this->startDate = $date->copy()->startOfWeek()->toDateString();
        $this->endDate = $date->copy()->endOfWeek()->toDateString();
        $appointments = DB::table('appointments')
            ->join('users', 'users.id', '=', 'appointments.salesperson')
            ->join('company_details', 'company_details.lead_id', '=', 'appointments.lead_id')
            ->select('users.name', "company_details.company_name", 'appointments.*')
            // ->whereBetween("date",[$this->startDate,$this->endDate])
            ->whereBetween("date", [$this->startDate, $this->endDate])
            ->orderBy('start_time', 'asc')
            ->when($this->selectedSalesPeople, function ($query) {
                return $query->whereIn('users.id', $this->selectedSalesPeople);
            })
            // ->when($this->selectedDemoType, function ($query) {
            //     return $query->whereIn('appointments.type', $this->selectedDemoType);
            // })
            // ->when($this->selectedAppointmentType, function ($query) {
            //     return $query->whereIn('appointments.appointment_type', $this->selectedAppointmentType);
            // })
            ->get();

        $this->newDemo = [
            "monday" => [],
            "tuesday" => [],
            "wednesday" => [],
            "thursday" => [],
            "friday" => [],
        ];

        if (!empty($this->selectedSalesPeople)) {
            $salesPeople = $this->getSelectedSalesPeople($this->selectedSalesPeople);
            $this->allSalesPeopleSelected = false;
        }
        else{
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
                'saturdayAppointments' => [],
                'sundayAppointments' => [],
                'newDemo' => [
                    'monday' => 0,
                    'tuesday' => 0,
                    'wednesday' => 0,
                    'thursday' => 0,
                    'friday' => 0,
                    'saturday' => 0,
                    'sunday' => 0,
                ],
                'leave' => UserLeave::getUserLeavesByDateRange($salesperson['id'], $this->startDate, $this->endDate),
            ];

            // Filter appointments for the current salesperson
            $salespersonAppointments = $appointments->where('salesperson', $salesperson['id']);


            //Demo Type and Appointment Type Condition Checking
            if(!empty($this->selectedAppointmentType))
                $salespersonAppointments->filter();

            if(!empty($this->selectedDemoType))
                $salespersonAppointments->filter();

                
            // Group appointments by the day of the week
            foreach ($salespersonAppointments as $appointment) {
                $dayOfWeek = strtolower(Carbon::parse($appointment->date)->format('l')); // e.g., 'monday'
                $dayField = "{$dayOfWeek}Appointments";
                // For new demo summary which shows no,1,2 new demo
                if ($appointment->type === "NEW DEMO" || $appointment->type === "WEBINAR DEMO") {
                    $data['newDemo'][$dayOfWeek]++;
                }

                // Filtering Demo Type and Appointment Type
                if($this->allAppointmentTypeSelected && $this-> allDemoTypeSelected
                || in_array($appointment->type,$this->selectedDemoType) && $this-> allAppointmentTypeSelected 
                || $this->allDemoTypeSelected && in_array($appointment->appointment_type,$this->selectedAppointmentType) 
                || in_array($appointment->type,$this->selectedDemoType) && in_array($appointment->appointment_type,$this->selectedAppointmentType) )
                {
                    $data[$dayField][] = $appointment;
                    $appointment->start_time = Carbon::parse($appointment->start_time)->format('g:i A');
                    $appointment->end_time = Carbon::parse($appointment->end_time)->format('g:i A');
                    $appointment->url = route('filament.admin.resources.leads.view', ['record' => Encryptor::encrypt($appointment->lead_id)]);    
                }
            }
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
            ->select('id', 'name', 'api_user_id', 'avatar_path')
            ->get();
    }

    public function getSelectedSalesPeople(array $arr)
    {
        return User::where('role_id','2')
        ->select('id', 'name', 'api_user_id', 'avatar_path')
        ->whereIn('id',$arr)
        ->get();
    }

    // Not used
    public function updatedSelectedMonth()
    {
        $this->date = Carbon::create(null, $this->selectedMonth, 1)->startOfMonth();
    }

    // Not used
    public function setSelectedMonthToCurrentMonth()
    {
        $this->selectedMonth = $this->date->month;
    }

    // Not used
    public function getAllMonthForCurrentYear()
    {
        $nextYearSuffix = "'" . Carbon::now()->format('y');

        $months = [];
        for ($month = 1; $month <= 12; $month++) {
            // Get the abbreviated month name (Jan, Feb, etc.)
            $monthName = Carbon::create(null, $month, 1)->format('M');
            // Format as "Jan '25"
            $formattedMonth = $monthName . ' ' . $nextYearSuffix;
            // Map to its decimal value (e.g., 1.0)
            $months[$formattedMonth] = (float)$month;
        }
        $this->monthList = $months;
    }

    public function render()
    {
        // Load Total Demos
        $this->getNumberOfDemos();
        $this->getAllMonthForCurrentYear();
        $this->weekDays = $this->getWeekDateDays($this->date);
        $this->rows = $this->getWeeklyAppointments($this->date);
        $this->holidays = PublicHoliday::getPublicHoliday($this->startDate, $this->endDate);
        $this->leaves = UserLeave::getWeeklyLeavesByDateRange($this->startDate, $this->endDate);
        // $this->setSelectedMonthToCurrentMonth(); //Not used
        $this->currentMonth = $this->date->startOfWeek()->format('F Y');
        return view('livewire.calendar');
    }
}
