<?php

namespace App\Livewire;

use App\Classes\Encryptor;
use App\Models\Appointment;
use App\Models\PublicHoliday;
use App\Models\User;
use App\Models\UserLeave;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class WeeklyCalendarV2 extends Component
{

    public $tableArray;

    public $salespeopleIDArr;

    public $currentDate;
    public $startDate;
    public $endDate;
    public $disablePrevWeek = false;

    public $weekDateArr;

    public function mount()
    {
        $this->currentDate = Carbon::now();
        // $this->currentDate = Carbon::parse("1-05-2025");
    }

    public function prevWeek()
    {
        $this->currentDate->subDays(7);

    }

    public function nextWeek()
    {
        $this->currentDate->addDays(7);
    }

 

    private function loadStartEndDate()
    {
        $this->startDate = $this->currentDate->copy()->startOfWeek()->toDateString(); // Monday
        $this->endDate = $this->currentDate->copy()->startOfWeek()->addDays(4)->toDateString(); // Friday
    }

    private function getWeekDateDays()
    {

        // Get the start of the week (Monday by default)
        $startOfWeek = $this->currentDate->startOfWeek();


        // Get public holiday
        $holidays = PublicHoliday::getPublicHoliday($this->startDate,$this->endDate);

        // Iterate through the week (7 days) and get each day's date
        $weekDays = [];
        for ($i = 0; $i < 5; $i++) {
            $weekDays[$i]["day"] = $startOfWeek->copy()->addDays($i)->format('l');  // Format as Fri,Sat,Mon
            $weekDays[$i]["date"] = $startOfWeek->copy()->addDays($i)->format('d-M');  // Format as Date
            $weekDays[$i]['carbonDate'] = $startOfWeek->copy()->addDays($i);
            
            foreach($holidays as $holiday){
                if(Carbon::parse($holiday['date'])->eq($weekDays[$i]['carbonDate']))
                    $weekDays[$i]['holiday']=$holiday;
            }
        }
        $this->weekDateArr = $weekDays;
        return $this->weekDateArr;
    }

    private function loadTableData($salesperson)
    {
        $newArray = [];
        $newArray['name'] = $salesperson['name'];
        $newArray['id'] = $salesperson['id'];
        $result = Appointment::where('salesperson', $salesperson['id'])
            ->select("type","appointment_type","date","start_time","end_time","salesperson")
            ->whereBetween("date", [$this->startDate, $this->endDate])
            ->whereNot("status","Cancelled")
            ->orderBy('start_time', 'asc')
            ->groupBy("date","start_time","end_time","type","appointment_type","salesperson")
            ->get()
            ->toArray();

        
        
        $result = array_map(function($appointment){
            $appointment['carbonDate'] = Carbon::parse($appointment['date']);
            $appointment['carbonStartTime'] = Carbon::parse($appointment['start_time'])->format('H:i');

            // $appointment['past'] = false;
            // if($appointment['carbonDate']->isBefore(Carbon::today())){
            //     $appointment['past'] = true;
            // }
            return $appointment;
        },$result);

        
        $newArray['appointment'] = $result;
        $newArray['weekDateArr'] = array_map(function($weekDate) use ($salesperson){
            $temp = UserLeave::getUserLeavesByDate($salesperson['id'],$weekDate['carbonDate']);
            if(isset($temp))
                $weekDate['leave'] = $temp;
            
            return $weekDate;
        },$this->weekDateArr);

        $newArray['today'] = Carbon::today();
        return $newArray;
    }

    public function loadTableArray()
    {
        $this->getWeekDateDays(); //Load Date
        $this->loadAllSalespeopleIDArr(); //Load Salesperson
        $this->tableArray = [];
        foreach ($this->salespeopleIDArr as $key => $value) {
            $this->tableArray[] = $this->loadTableData($value);
        }
        // dd($this->tableArray);
    }

    private function loadAllSalespeopleIDArr()
    {
        $this->salespeopleIDArr =  User::where('role_id', '2')
            ->select('users.id', 'users.name', 'users.api_user_id', 'users.avatar_path')
            ->join('demo_rankings', 'users.id', '=', 'demo_rankings.user_id')
            ->orderBy('demo_rankings.rank', 'asc') // or 'desc' if you want highest rank first
            ->get()
            ->toArray();
    }

    public $modalOpen = false;
    public $modalArray = [];
    public $modalProp = [];

    public function openModal($date,$startTime,$endTime,$salespersonID){
        $this->modalOpen = true;
        $this->modalProp = ["date" => $date, "startTime" => $startTime, "endTime" => $endTime, "salespersonID" => $salespersonID];
        $this->loadModalArray();

    }

    public function loadModalArray(){
        $appointments = DB::table('appointments')
        ->join('company_details', 'company_details.lead_id', '=', 'appointments.lead_id')
        ->select('company_details.*','appointments.type',"appointments.status",'appointments.appointment_type')
        ->where("appointments.date",$this->modalProp["date"])
        ->where("appointments.start_time",$this->modalProp["startTime"])
        ->where("appointments.end_time",$this->modalProp["endTime"])
        ->where("appointments.salesperson",$this->modalProp["salespersonID"])
        ->whereNot("appointments.status","Cancelled")
        ->get()
        ->toArray();
        
        $result = array_map(function ($value) {
            $value->url = route('filament.admin.resources.leads.view', ['record' => Encryptor::encrypt($value->lead_id)]);
            return (array)$value;
        }, $appointments);

        $this->modalArray = $result;
    }


    public function render()
    {
        $this->loadStartEndDate(); //Load start and end date
        $this->loadTableArray();

        
        if($this->currentDate->copy()->startOfWeek()->isBefore(Carbon::today())){
            $this->disablePrevWeek = true;
        }
        else
            $this->disablePrevWeek = false;
        return view('livewire.weekly-calendar-v2');
    }
}
