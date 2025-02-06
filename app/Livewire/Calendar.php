<?php

namespace App\Livewire;

use App\Classes\Encryptor;
use App\Models\Appointment;
use App\Models\PublicHoliday;
use App\Models\User;
use App\Models\UserLeave;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use PDO;
use App\Services\LeaveAPIService;
use Illuminate\Support\Arr;

class Calendar extends Component
{

    public $rows;
    public Carbon $date;
    public $startDate;
    public $endDate;
    public Collection $salesPeople;
    public $weekDays;
    public $selectedMonth;
    public $holidays;
    public $leaves;
    public $monthList;
    public $currentMonth;
    public $newDemo;


    public function mount()
    {
        $this->getAllSalesPeople();
        $this->date = Carbon::now();
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
            $weekDays[$i]['carbonDate'] =$startOfWeek->copy()->addDays($i);
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
            ->get();

        $this->newDemo = [
            "monday"=>[],
            "tuesday"=>[],
            "wednesday"=>[],
            "thursday"=>[],
            "friday"=>[],
        ];

        $result = $this->salesPeople->map(function ($salesperson) use ($appointments) {
            // Initialize fields for each day of the week
            $data = [
                'salespersonID' => $salesperson['id'],
                'salespersonName' => $salesperson['name'],
                'salespersonAvatar' => !empty($salesperson['avatar_path']) ? $salesperson['avatar_path']: "https://ui-avatars.com/api" . '?' .  http_build_query(["name" => $salesperson['name'], "background" => "random"]),
                'mondayAppointments' => [],
                'tuesdayAppointments' => [],
                'wednesdayAppointments' => [],
                'thursdayAppointments' => [],
                'fridayAppointments' => [],
                'saturdayAppointments' => [],
                'sundayAppointments' => [],
                'newDemo'=>[
                    'monday'=>0,
                    'tuesday'=>0,
                    'wednesday'=>0,
                    'thursday'=>0,
                    'friday'=>0,
                    'saturday'=>0,
                    'sunday'=>0,
                ],
                'leave' => UserLeave::getUserLeavesByDateRange($salesperson['id'], $this->startDate, $this->endDate),
            ];

            // Filter appointments for the current salesperson
            $salespersonAppointments = $appointments->where('salesperson', $salesperson['id']);

            // Group appointments by the day of the week
            foreach ($salespersonAppointments as $appointment) {
                $dayOfWeek = strtolower(Carbon::parse($appointment->date)->format('l')); // e.g., 'monday'
                $dayField = "{$dayOfWeek}Appointments";
                $data[$dayField][] = $appointment;
                $appointment->start_time = Carbon::parse($appointment->start_time)->format('g:i A');
                $appointment->end_time = Carbon::parse($appointment->end_time)->format('g:i A');
                $appointment->url = route('filament.admin.resources.leads.view', ['record' => Encryptor::encrypt($appointment->lead_id)]);

                // For new demo summary which shows no,1,2 new demo
                if ($appointment->type === "NEW DEMO" || $appointment->type === "New Private Demo" || $appointment->type === "New Webinar Demo") {
                    $data['newDemo'][$dayOfWeek]++;
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
        $this->salesPeople = User::where('role_id', '2')
            ->select('id', 'name', 'api_user_id','avatar_path')
            ->get();
    }

    public function updatedSelectedMonth()
    {
        $this->date = Carbon::create(null, $this->selectedMonth, 1)->startOfMonth();
    }

    public function setSelectedMonthToCurrentMonth()
    {
        $this->selectedMonth = $this->date->month;
    }

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

        
        $this->getAllMonthForCurrentYear();
        $this->weekDays = $this->getWeekDateDays($this->date);
        // foreach($this->weekDays as $day){
        //     $this->getNewDemo($day['carbonDate']);
        // }
        $this->rows = $this->getWeeklyAppointments($this->date);
        // $this->getSalesPersonNoNewDemo($this->rows);
        $this->holidays = PublicHoliday::getPublicHoliday($this->startDate, $this->endDate);
        $this->leaves = UserLeave::getWeeklyLeavesByDateRange($this->startDate, $this->endDate);
        $this->setSelectedMonthToCurrentMonth();
        return view('livewire.calendar');
    }
}
