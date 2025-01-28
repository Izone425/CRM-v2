<?php

namespace App\Livewire;

use App\Models\Appointment;
use App\Models\User;
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
                'leave' => $this->getSalesPersonOnLeave($salesperson['api_user_id']),
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

    private function getSalesPersonOnLeave($userID) {
        $wsdl = "https://api.timeteccloud.com/webservice/WebServiceTimeTecAPI.asmx?WSDL";
        $LeaveAPIService = new LeaveAPIService($wsdl, "hr@timeteccloud.com", "BAKIt9nKbCxr6JJUvLWySQL4oH7a4zJYhIjv4GIJK5CD9RvlLp");
        $params = ["CompanyID" => 351, "UserID" => $userID, "CheckTimeFrom" => $this->startDate, "CheckTimeTo" => $this->endDate, "RecordStartFrom" => "0", "LimitRecordShow" => "10"];

        $leave = json_decode($LeaveAPIService->getClient()->getUserLeave($params)->GetUserLeaveResult,true);

        $newArray = [];
        if(!empty($leave['Result']['UserLeaveObj'])){
            foreach($leave['Result']['UserLeaveObj'] as $row){
                $newArray[Carbon::parse($row['Date'])->dayOfWeek] = $row; 
            }
            return $newArray;
        }
        return [];
    }

    private function getAllPublicHoliday()
    {
        $wsdl = "https://api.timeteccloud.com/webservice/WebServiceTimeTecAPI.asmx?WSDL";

        $LeaveAPIService = new LeaveAPIService($wsdl, "hr@timeteccloud.com", "BAKIt9nKbCxr6JJUvLWySQL4oH7a4zJYhIjv4GIJK5CD9RvlLp");
        // $params = ["CompanyID"=>351,"DivisionID"=>31010,"DateFrom"=>"2025-02-02","DateTo"=>"2025-02-08","RecordStartFrom"=>"0","LimitRecordShow"=>"10"];
        $params = ["CompanyID" => 351, "DivisionID" => 31010, "DateFrom" => $this->startDate, "DateTo" => $this->endDate, "RecordStartFrom" => "0", "LimitRecordShow" => "10"];

        $holidays = json_decode($LeaveAPIService->getClient()->getOrgStructureHoliday($params)->GetOrgStructureHolidayResult, true);

        if (isset($holidays['Result']['OrgStructureHolidayObj']) && !empty($holidays['Result']['OrgStructureHolidayObj'][0])) {
            $holidays = $holidays['Result']['OrgStructureHolidayObj'][0]["TimeTec Cloud Sdn. Bhd."];
            foreach ($holidays as &$row) {
                $row['day'] = Carbon::parse($row['Date'])->dayOfWeek;
            }
        } else {
            $holidays = [];
        }
        return $holidays;
    }


    public function render()
    {

        $this->weekDays = $this->getWeekDateDays($this->date);
        $this->rows = $this->getWeeklyAppointments($this->date);
        // $this->getSalesPersonNoNewDemo($this->rows);
        $this->holidays = $this->getAllPublicHoliday();
        return view('livewire.calendar');
    }
}
