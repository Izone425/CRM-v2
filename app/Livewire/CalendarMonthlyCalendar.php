<?php

namespace App\Livewire;

use App\Classes\Encryptor;
use App\Models\Appointment;
use App\Models\PublicHoliday;
use App\Models\User;
use App\Models\UserLeave;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

use function Laravel\Prompts\select;

class CalendarMonthlyCalendar extends Component
{

    public array $totalDemos;
    public int $numOfDays;
    public Carbon $currentDate;
    public int $firstDay;
    public $salesPeople;
    public $selectedSalesPerson;
    public $demos;
    public $days;
    public $month;

    public function mount() {
        if(auth()->user()->role_id == 2){
            $this->selectUser(auth()->user()->id);
        }
        $this->currentDate = Carbon::now();
        $this->month = $this->currentDate->copy()->format('F Y');
    }
    public function updated($property)
    {
        if($property === "month"){
            $this->currentDate = Carbon::parse($this->month);
        }

    }

    public function getSalespersonDays($id)
    {

        $days = [
            "totalDays" => $this->getNumberOfDays($this->currentDate),
            "pb" => 0,
            "weekend" => 0,
            "leave" => 0,
            "working" => 0,
        ];


        $days['pb'] = PublicHoliday::whereBetween('date', [$this->currentDate->copy()->startOfMonth()->format("Y-m-d"), $this->currentDate->copy()->endOfMonth()->format("Y-m-d")])->count();
        $days["leave"] = UserLeave::where('user_ID', $id)->whereBetween('date', [$this->currentDate->copy()->startOfMonth()->format("Y-m-d"), $this->currentDate->copy()->endOfMonth()->format("Y-m-d")])->count();
        $days['weekend'] = $this->currentDate->copy()->startOfMonth()->diffInDaysFiltered(function (Carbon $date) {
            return $date->isSaturday() || $date->isSunday();
        }, $this->currentDate->copy()->endOfMonth());
        
        $days["working"] = $days['totalDays'] - $days['pb'] - $days['leave'] - $days['weekend'];

        return $days;
    }
    // Get Total Number of Demos for New, Webinar and others
    private function getNumberOfDemos($salesPerson)
    {
        $this->totalDemos = ["ALL", 'NEW DEMO' => 0, "WEBINAR DEMO" => 0, "OTHERS" => 0];
        $this->totalDemos["ALL"] = DB::table('appointments')->where('salesperson', $salesPerson['id'])->whereBetween('date', [$this->currentDate->copy()->startOfMonth()->format("Y-m-d"), $this->currentDate->copy()->endOfMonth()->format("Y-m-d")])
            ->count();
        $this->totalDemos["NEW DEMO"] = DB::table('appointments')->where("type", "NEW DEMO")->where('salesperson', $salesPerson['id'])->whereBetween('date', [$this->currentDate->copy()->startOfMonth()->format("Y-m-d"), $this->currentDate->copy()->endOfMonth()->format("Y-m-d")])
            ->count();
        $this->totalDemos["WEBINAR DEMO"] = DB::table('appointments')->where("type", "WEBINAR DEMO")->where('salesperson', $salesPerson['id'])->whereBetween('date', [$this->currentDate->copy()->startOfMonth()->format("Y-m-d"), $this->currentDate->copy()->endOfMonth()->format("Y-m-d")])
            ->count();
        $this->totalDemos["OTHERS"] = DB::table('appointments')->whereNotIn("type", ["NEW DEMO", "WEBINAR DEMO"])->where('salesperson', $salesPerson['id'])->whereBetween('date', [$this->currentDate->copy()->startOfMonth()->format("Y-m-d"), $this->currentDate->copy()->endOfMonth()->format("Y-m-d")])
            ->count();
    }

    private function getNumberOfDays(Carbon $date)
    {
        return $date->daysInMonth;
    }

    private function getAllSalesPeople()
    {
        $this->salesPeople = User::where('role_id', '=', '2')->get()->toArray();
    }

    public function selectUser($id)
    {
        $this->selectedSalesPerson = User::where('id', $id)->first()->toArray();
    }

    private function getDemosForSalesPerson()
    {
        $this->demos = DB::table("appointments")
            ->join('users', 'users.id', '=', 'appointments.salesperson')
            ->join('company_details', 'company_details.lead_id', '=', 'appointments.lead_id')
            ->select('appointments.*', 'users.id as user_id', 'users.name', 'company_details.company_name')
            ->where("users.role_id", '=', '2')
            ->where('users.id', '=', $this->selectedSalesPerson['id'])
            ->whereBetween('date', [$this->currentDate->copy()->startOfMonth()->format("Y-m-d"), $this->currentDate->copy()->endOfMonth()->format("Y-m-d")])
            ->get()
            ->map(function ($item) {
                $item->formattedStartTime = Carbon::parse($item->start_time)->format('H:i');
                $item->url = route('filament.admin.resources.leads.view', ['record' => Encryptor::encrypt($item->lead_id)]);
                return $item;
            })
            ->groupBy(function ($item) {
                return Carbon::parse($item->date)->format('d'); // Group by formatted date
            })
            ->toArray();
    }

    public function render()
    {
        $this->firstDay = $this->currentDate->copy()->startOfMonth()->dayOfWeekIso;
        $this->numOfDays = $this->currentDate->daysInMonth;
        $this->getAllSalesPeople();

        if (isset($this->selectedSalesPerson)) {
            $this->getDemosForSalesPerson();
            $this->getNumberOfDemos($this->selectedSalesPerson);
            $this->days = $this->getSalespersonDays($this->selectedSalesPerson['id']);
        }

        return view('livewire.calendar-monthly-calendar');
    }
}
