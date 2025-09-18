<?php

namespace App\Filament\Pages;

use App\Classes\Encryptor;
use App\Models\Appointment;
use App\Models\PublicHoliday;
use App\Models\User;
use App\Models\UserLeave;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class SalespersonCalendarV3 extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Daily Calendar';
    protected static string $view = 'filament.pages.salesperson-calendar-v3';

    public $currentDate;
    public $salespeopleData = [];
    public $selectedDate;
    public $disablePrevDay = false;
    public $modalOpen = false;
    public $modalArray = [];

    public $internalSalesTaskTypes = [
        'EXHIBITION',
        'INTERNAL MEETING',
        'SALES MEETING',
        'PRODUCT MEETING',
        'TOWNHALL SESSION',
        'FOLLOW UP SESSION',
        'BUSINESS TRIP'
    ];

    public function mount()
    {
        $this->currentDate = Carbon::now();
        $this->selectedDate = $this->currentDate->format('Y-m-d');
        $this->loadData();
    }

    public function prevDay()
    {
        $this->currentDate->subDay();
        $this->selectedDate = $this->currentDate->format('Y-m-d');
        $this->loadData();
    }

    public function nextDay()
    {
        $this->currentDate->addDay();
        $this->selectedDate = $this->currentDate->format('Y-m-d');
        $this->loadData();
    }

    public function loadData()
    {
        // Check if previous day should be disabled
        $this->disablePrevDay = $this->currentDate->copy()->isBefore(Carbon::today());

        // Get all salespeople
        $salespeople = User::where('role_id', '2')
            ->select('users.id', 'users.name', 'users.api_user_id', 'users.avatar_path')
            ->join('demo_rankings', 'users.id', '=', 'demo_rankings.user_id')
            ->orderBy('demo_rankings.rank', 'asc')
            ->get();

        // Check if it's a public holiday
        $holiday = PublicHoliday::where('date', $this->selectedDate)->first();

        $this->salespeopleData = [];

        foreach ($salespeople as $salesperson) {
            // Get appointments for this salesperson on this date
            $appointments = Appointment::where('salesperson', $salesperson->id)
                ->where('date', $this->selectedDate)
                ->whereNot('status', 'Cancelled')
                ->orderBy('start_time', 'asc')
                ->get();

            // Get leave information
            $leaves = UserLeave::getUserLeavesByDate($salesperson->id, $this->currentDate);

            // Process appointments into 4 sessions
            $sessions = $this->processAppointments($appointments, $leaves, $holiday);

            $this->salespeopleData[] = [
                'id' => $salesperson->id,
                'name' => $salesperson->name,
                'sessions' => $sessions,
                'is_holiday' => !is_null($holiday),
                'is_past_date' => $this->currentDate->isBefore(Carbon::today()),
                'leaves' => $leaves
            ];
        }
    }

    public function calculateSummary()
    {
        $summary = [];

        for ($sessionNumber = 1; $sessionNumber <= 4; $sessionNumber++) {
            $hasDemo = false;
            $allFree = true;
            $activePeople = 0; // Only count people who are not on leave/holiday/past
            $freePeople = 0;

            foreach ($this->salespeopleData as $person) {
                $session = $person['sessions'][$sessionNumber];

                // Skip people who are on leave, holiday, or past date
                if (in_array($session['type'], ['leave', 'holiday', 'past'])) {
                    continue;
                }

                $activePeople++; // Only count active people

                // Check if this session has a demo (any appointment type)
                if (in_array($session['type'], ['new-demo', 'webinar-demo', 'others', 'internal-sales-task'])) {
                    $hasDemo = true;
                    $allFree = false;
                }

                // Check if this person is free (empty session)
                if ($session['type'] === 'empty') {
                    $freePeople++;
                } else {
                    $allFree = false;
                }
            }

            // If no active people (all on leave/holiday/past), show as mixed
            if ($activePeople === 0) {
                $summary[$sessionNumber] = [
                    'type' => 'mixed',
                    'label' => 'N/A',
                    'color' => 'gray'
                ];
            }
            // Determine summary status based only on active people
            elseif ($hasDemo) {
                $summary[$sessionNumber] = [
                    'type' => 'has-demo',
                    'label' => 'BUSY',
                    'color' => 'red'
                ];
            } elseif ($allFree) {
                $summary[$sessionNumber] = [
                    'type' => 'all-free',
                    'label' => 'FREE',
                    'color' => 'green'
                ];
            } else {
                // This shouldn't happen with the new logic, but keep as fallback
                $summary[$sessionNumber] = [
                    'type' => 'mixed',
                    'label' => 'MIXED',
                    'color' => 'yellow'
                ];
            }
        }

        return $summary;
    }

    private function processAppointments($appointments, $leaves, $holiday)
    {
        $sessions = [1 => null, 2 => null, 3 => null, 4 => null];

        // If it's a holiday, mark all sessions
        if ($holiday) {
            return [
                1 => ['type' => 'holiday', 'label' => 'P. HOLIDAY'],
                2 => ['type' => 'holiday', 'label' => 'P. HOLIDAY'],
                3 => ['type' => 'holiday', 'label' => 'P. HOLIDAY'],
                4 => ['type' => 'holiday', 'label' => 'P. HOLIDAY']
            ];
        }

        // If it's a past date, mark all sessions as past
        if ($this->currentDate->isBefore(Carbon::today())) {
            return [
                1 => ['type' => 'past', 'label' => ''],
                2 => ['type' => 'past', 'label' => ''],
                3 => ['type' => 'past', 'label' => ''],
                4 => ['type' => 'past', 'label' => '']
            ];
        }

        // Handle leaves
        if ($leaves) {
            foreach ($leaves as $leave) {
                if ($leave['session'] === 'full') {
                    return [
                        1 => ['type' => 'leave', 'label' => 'On-Leave'],
                        2 => ['type' => 'leave', 'label' => 'On-Leave'],
                        3 => ['type' => 'leave', 'label' => 'On-Leave'],
                        4 => ['type' => 'leave', 'label' => 'On-Leave']
                    ];
                } elseif ($leave['session'] === 'am') {
                    $sessions[1] = ['type' => 'leave', 'label' => 'On-Leave'];
                    $sessions[2] = ['type' => 'leave', 'label' => 'On-Leave'];
                } elseif ($leave['session'] === 'pm') {
                    $sessions[3] = ['type' => 'leave', 'label' => 'On-Leave'];
                    $sessions[4] = ['type' => 'leave', 'label' => 'On-Leave'];
                }
            }
        }

        // Fill remaining sessions with appointments
        $appointmentIndex = 0;
        foreach ($sessions as $sessionNumber => $session) {
            if ($session === null && $appointmentIndex < count($appointments)) {
                $appointment = $appointments[$appointmentIndex];
                $sessions[$sessionNumber] = [
                    'type' => $this->getAppointmentType($appointment->type),
                    'label' => Carbon::parse($appointment->start_time)->format('H:i'),
                    'appointment' => $appointment
                ];
                $appointmentIndex++;
            } elseif ($session === null) {
                $sessions[$sessionNumber] = ['type' => 'empty', 'label' => ''];
            }
        }

        return $sessions;
    }

    private function getAppointmentType($type)
    {
        if ($type === 'NEW DEMO') {
            return 'new-demo';
        } elseif ($type === 'WEBINAR DEMO') {
            return 'webinar-demo';
        } elseif (in_array($type, $this->internalSalesTaskTypes)) {
            return 'internal-sales-task';
        } else {
            return 'others';
        }
    }

    public function openModal($salespersonId, $sessionData)
    {
        if (!isset($sessionData['appointment'])) {
            return;
        }

        $appointment = $sessionData['appointment'];
        $this->modalOpen = true;

        // Load modal data based on appointment type
        $this->loadModalData($appointment);
    }

    private function loadModalData($appointment)
    {
        $isInternalTask = in_array($appointment->type, $this->internalSalesTaskTypes);

        if ($isInternalTask) {
            $this->modalArray = [[
                'is_internal_task' => true,
                'type' => $appointment->type,
                'status' => $appointment->status,
                'appointment_type' => $appointment->appointment_type,
                'date' => $appointment->date,
                'start_time' => $appointment->start_time,
                'end_time' => $appointment->end_time,
                'remarks' => $appointment->remarks ?? null,
            ]];
        } else {
            $appointmentData = DB::table('appointments')
                ->join('company_details', 'company_details.lead_id', '=', 'appointments.lead_id')
                ->select('company_details.*', 'appointments.type', 'appointments.status',
                        'appointments.appointment_type', 'appointments.date',
                        'appointments.start_time', 'appointments.end_time', 'appointments.remarks')
                ->where('appointments.id', $appointment->id)
                ->first();

            if ($appointmentData) {
                $data = (array)$appointmentData;
                if (isset($appointmentData->lead_id)) {
                    $data['url'] = route('filament.admin.resources.leads.view',
                                        ['record' => Encryptor::encrypt($appointmentData->lead_id)]);
                }
                $data['is_internal_task'] = false;
                $this->modalArray = [$data];
            }
        }
    }

    public function closeModal()
    {
        $this->modalOpen = false;
        $this->modalArray = [];
    }
}
