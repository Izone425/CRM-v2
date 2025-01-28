<?php

namespace App\Livewire;

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

        $result = $this->salesPeople->map(function ($salesperson) use ($appointments) {
            // Initialize fields for each day of the week
            $data = [
                'salespersonID' => $salesperson['id'],
                'salespersonName' => $salesperson['name'],
                'salespersonAvatar' => "https://ui-avatars.com/api" . '?' .  http_build_query(["name" => $salesperson['name'], "background" => "random"]),
                'mondayAppointments' => [],
                'tuesdayAppointments' => [],
                'wednesdayAppointments' => [],
                'thursdayAppointments' => [],
                'fridayAppointments' => [],
                'saturdayAppointments' => [],
                'sundayAppointments' => [],
                'mondayNewDemo' => 0,
                'tuesdayNewDemo' => 0,
                'wednesdayNewDemo' => 0,
                'thursdayNewDemo' => 0,
                'fridayNewDemo' => 0,
                'saturdayNewDemo' => 0,
                'sundayNewDemo' => 0,
                'leave' => (new UserLeave())->getUserLeavesByDateRange($salesperson['id'],$this->startDate,$this->endDate),
            ];



            // Filter appointments for the current salesperson
            $salespersonAppointments = $appointments->where('salesperson', $salesperson['id']);

            // Group appointments by the day of the week
            foreach ($salespersonAppointments as $appointment) {
                $dayOfWeek = strtolower(Carbon::parse($appointment->date)->format('l')); // e.g., 'monday'
                $dayField = "{$dayOfWeek}Appointments";
                $data[$dayField][] = $appointment;

                if ($appointment->type === "New Demo" || $appointment->type === "New Private Demo" || $appointment->type === "New Webinar Demo") {
                    $dayFieldNewDemo = "{$dayOfWeek}NewDemo";
                    $data[$dayFieldNewDemo]++;
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
            ->select('id', 'name','api_user_id')
            ->get();
    }

    public function render()
    {

        $this->weekDays = $this->getWeekDateDays($this->date);
        $this->rows = $this->getWeeklyAppointments($this->date);
        // $this->getSalesPersonNoNewDemo($this->rows);
        $this->holidays = PublicHoliday::getPublicHoliday($this->startDate,$this->endDate);
        return view('livewire.calendar');
    }
}
