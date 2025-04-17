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

    // Badge
    public $totalDemos;

    // Dropdown
    public array $status = ["DONE", "NEW", "CANCELLED"];
    public array $selectedStatus = [];
    public bool $allStatusSelected = true;

    public Collection $salesPeople;
    public array $selectedSalesPeople = [];
    public bool $allSalesPeopleSelected = true;

    public array $demoTypes = ["NEW DEMO", "WEBINAR DEMO", "HRMS DEMO", "SYSTEM DISCUSSION", "HRDF DISCUSSION"];
    public array $selectedDemoType = [];
    public bool $allDemoTypeSelected = true;

    public array $appointmentTypes = ["ONLINE", "ONSITE"];
    public array $selectedAppointmentType = [];
    public bool $allAppointmentTypeSelected = true;



    public function mount()
    {

        //Load all salespeople model
        $this->salesPeople = $this->getAllSalesPeople();

        //Set Date to today
        $this->date = Carbon::now();

        //If current user is a salesperson then only can access their own calendar
        if (auth()->user()->role_id == 2) {
            $this->selectedSalesPeople[] = auth()->user()->id;
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
        if (!empty($selectedSalesPeople)) {
            $this->totalDemos = ["ALL", 'NEW DEMO' => 0, "WEBINAR DEMO" => 0, "OTHERS" => 0];
            $this->totalDemos["ALL"] = DB::table('appointments')->whereNot('status', 'Cancelled')->whereIn("salesperson", $selectedSalesPeople)->whereBetween('date', [$this->startDate, $this->endDate])->count();
            $this->totalDemos["NEW DEMO"] = DB::table('appointments')->where("type", "NEW DEMO")->whereNot('status', 'Cancelled')->whereIn("salesperson", $selectedSalesPeople)->whereBetween('date', [$this->startDate, $this->endDate])->count();
            $this->totalDemos["WEBINAR DEMO"] = DB::table('appointments')->where("type", "WEBINAR DEMO")->whereNot('status', 'Cancelled')->whereIn("salesperson", $selectedSalesPeople)->whereBetween('date', [$this->startDate, $this->endDate])->count();
            $this->totalDemos["OTHERS"] = DB::table('appointments')->whereNotIn("type", ["NEW DEMO", "WEBINAR DEMO"])->whereNot('status', 'Cancelled')->whereIn("salesperson", $selectedSalesPeople)->whereBetween('date', [$this->startDate, $this->endDate])->count();
            $this->totalDemos["NEW"] = DB::table('appointments')->where("status", "New")->whereIn("salesperson", $selectedSalesPeople)->whereBetween('date', [$this->startDate, $this->endDate])->count();
            $this->totalDemos["DONE"] = DB::table('appointments')->where("status", "Done")->whereIn("salesperson", $selectedSalesPeople)->whereBetween('date', [$this->startDate, $this->endDate])->count();
            $this->totalDemos["CANCELLED"] = DB::table('appointments')->where("status", "Cancelled")->whereIn("salesperson", $selectedSalesPeople)->whereBetween('date', [$this->startDate, $this->endDate])->count();
        } else {
            $this->totalDemos = ["ALL", 'NEW DEMO' => 0, "WEBINAR DEMO" => 0, "OTHERS" => 0];
            $this->totalDemos["ALL"] = DB::table('appointments')->whereNot('status', 'Cancelled')->whereBetween('date', [$this->startDate, $this->endDate])->count();
            $this->totalDemos["NEW DEMO"] = DB::table('appointments')->where("type", "NEW DEMO")->whereNot('status', 'Cancelled')->whereBetween('date', [$this->startDate, $this->endDate])->count();
            $this->totalDemos["WEBINAR DEMO"] = DB::table('appointments')->where("type", "WEBINAR DEMO")->whereNot('status', 'Cancelled')->whereBetween('date', [$this->startDate, $this->endDate])->count();
            $this->totalDemos["OTHERS"] = DB::table('appointments')->whereNot('status', 'Cancelled')->whereNotIn("type", ["NEW DEMO", "WEBINAR DEMO"])->whereBetween('date', [$this->startDate, $this->endDate])->count();
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

        //Have to make sure weekly is weekly date. Monday to Friday
        $date = $date ? Carbon::parse($date) : Carbon::now();
        $this->startDate = $date->copy()->startOfWeek()->toDateString(); // Monday
        $this->endDate = $date->copy()->startOfWeek()->addDays(4)->toDateString(); // Friday
        //Retreive all appointments for each salesperson with company details between start and end date. If filter present, then filter
        $appointments = DB::table('appointments')
            ->join('users', 'users.id', '=', 'appointments.salesperson')
            ->join('company_details', 'company_details.lead_id', '=', 'appointments.lead_id')
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

        //Initialize 
        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day) {
            $this->newDemoCount[$day]["noDemo"] = 0;
            $this->newDemoCount[$day]["oneDemo"] = 0;
            $this->newDemoCount[$day]["twoDemo"] = 0;
        }

        // Load Total Demos
        $this->rows = $this->getWeeklyAppointments($this->date);

        //Load Date Display
        $this->weekDays = $this->getWeekDateDays($this->date);

        //Count Demos
        $this->getNumberOfDemos($this->selectedSalesPeople);

        $this->holidays = PublicHoliday::getPublicHoliday($this->startDate, $this->endDate);
        $this->leaves = UserLeave::getWeeklyLeavesByDateRange($this->startDate, $this->endDate, $this->selectedSalesPeople);
        // $this->setSelectedMonthToCurrentMonth(); //Not used
        $this->currentMonth = $this->date->startOfWeek()->format('F Y');
        return view('livewire.calendar');
    }
}
