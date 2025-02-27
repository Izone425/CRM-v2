<?php
namespace App\Livewire;

use App\Models\Appointment;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\Attributes\On;

class DemoAnalysisSession extends Component
{
    public $totalAppointments;
    public $typeData = [];
    public $selectedUser;
    public $selectedMonth;

    protected $listeners = ['selectedUserChanged' => 'updateSelectedUser', 'selectedMonthChanged' => 'updateSelectedMonth'];

    public function mount($selectedUser = null, $selectedMonth = null)
    {
        $this->selectedUser = $selectedUser;
        $this->selectedMonth = $selectedMonth ?: Carbon::now()->format('Y-m');
        $this->fetchAppointments();
    }

    #[On('selectedUserChanged')]
    public function updateSelectedUser($userId)
    {
        $this->selectedUser = $userId;
        $this->fetchAppointments();
    }

    #[On('selectedMonthChanged')]
    public function updateSelectedMonth($month)
    {
        $this->selectedMonth = $month;
        $this->fetchAppointments();
    }

    public function fetchAppointments()
    {
        $appointmentTypes = ['NEW DEMO', 'WEBINAR DEMO', 'HRMS DEMO', 'SYSTEM DISCUSSION', 'HRDF DISCUSSION'];

        $query = Appointment::where('status', '!=', 'Cancelled');

        // Filter by Salesperson (ONLY role_id = 2)
        if (!empty($this->selectedUser)) {
            $query->whereHas('salesperson', function ($q) {
                $q->where('role_id', 2);
            })->where('salesperson_id', $this->selectedUser);
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

        $this->typeData = array_merge(array_fill_keys($appointmentTypes, 0), $typeDataRaw);
    }

    public function render()
    {
        return view('livewire.demo-analysis-session', [
            'typeData' => $this->typeData,
            'totalAppointments' => $this->totalAppointments,
        ]);
    }
}
