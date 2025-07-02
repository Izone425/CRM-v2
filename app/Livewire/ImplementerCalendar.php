<?php

namespace App\Livewire;

use App\Classes\Encryptor;
use App\Models\PublicHoliday;
use App\Models\User;
use App\Models\UserLeave;
use App\Models\ImplementerAppointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class ImplementerCalendar extends Component
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
    public $newAppointmentCount;

    //Dropdown
    public $showDropdown = false;

    // Badge
    public $totalAppointments;
    public $totalAppointmentsStatus;

    // Dropdown
    public array $status = ["COMPLETED", "NEW", "CANCELLED"];
    public array $selectedStatus = [];
    public bool $allStatusSelected = true;

    public $implementers;
    public array $selectedImplementers = [];
    public bool $allImplementersSelected = true;

    public array $appointmentTypes = ["KICK OFF MEETING SESSION (NEW)", "IMPLEMENTATION SESSION 1", "IMPLEMENTATION SESSION 2", "IMPLEMENTATION SESSION 3", "IMPLEMENTATION SESSION 4", "IMPLEMENTATION SESSION 5"];
    public array $selectedAppointmentType = [];
    public bool $allAppointmentTypeSelected = true;

    public array $sessionTypes = ["ONLINE", "ONSITE", "INHOUSE"];
    public array $selectedSessionType = [];
    public bool $allSessionTypeSelected = true;

    public $appointmentBreakdown = [];

    public function mount()
    {
        // Load all implementers
        $this->implementers = $this->getAllImplementers();

        // Set Date to today
        $this->date = Carbon::now();

        // If current user is an implementer then only can access their own calendar
        if (auth()->user()->role_id == 4 || auth()->user()->role_id == 5) {
            $this->selectedImplementers[] = auth()->user()->name;
        }
    }

    // Update date variable when user choose another date
    public function updatedWeekDate()
    {
        $this->date = Carbon::parse($this->weekDate);
    }

    // For Filtering
    public function updatedSelectedImplementers()
    {
        if (!empty($this->selectedImplementers)) {
            $this->allImplementersSelected = false;
        } else {
            $this->allImplementersSelected = true;
        }
    }

    public function updatedAllImplementersSelected()
    {
        if ($this->allImplementersSelected == true)
            $this->selectedImplementers = [];
    }

    public function updatedSelectedStatus()
    {
        if (!empty($this->selectedStatus)) {
            $this->allStatusSelected = false;
        } else {
            $this->allStatusSelected = true;
        }
    }

    public function updatedAllStatusSelected()
    {
        if ($this->allStatusSelected == true)
            $this->selectedStatus = [];
    }

    public function updatedSelectedAppointmentType()
    {
        if (!empty($this->selectedAppointmentType)) {
            $this->allAppointmentTypeSelected = false;
        } else {
            $this->allAppointmentTypeSelected = true;
        }
    }

    public function updatedAllAppointmentTypeSelected()
    {
        if ($this->allAppointmentTypeSelected == true)
            $this->selectedAppointmentType = [];
    }

    public function updatedSelectedSessionType()
    {
        if (!empty($this->selectedSessionType)) {
            $this->allSessionTypeSelected = false;
        } else {
            $this->allSessionTypeSelected = true;
        }
    }

    public function updatedAllSessionTypeSelected()
    {
        if ($this->allSessionTypeSelected == true)
            $this->selectedSessionType = [];
    }

    // Get Total Number of Appointments for different types and statuses
    private function getNumberOfAppointments($selectedImplementers = null)
    {
        // Base query
        $query = DB::table('implementer_appointments')
            ->whereBetween('date', [$this->startDate, $this->endDate]);

        // Apply implementer filter if provided
        if (!empty($selectedImplementers)) {
            $query->whereIn("implementer", $selectedImplementers);
        }

        // Initialize counters
        $this->totalAppointments = [
            "ALL" => 0,
            "KICK OFF MEETING SESSION (NEW)" => 0,
            "IMPLEMENTATION SESSION 1" => 0,
            "IMPLEMENTATION SESSION 2" => 0,
            "IMPLEMENTATION SESSION 3" => 0,
            "IMPLEMENTATION SESSION 4" => 0,
            "IMPLEMENTATION SESSION 5" => 0,
        ];

        // Initialize status counters
        $this->totalAppointmentsStatus = [
            "ALL" => 0,
            "NEW" => 0,
            "COMPLETED" => 0,
            "CANCELLED" => 0
        ];

        // Count active appointments (not cancelled)
        $this->totalAppointments["ALL"] = $query->clone()->where('status', '!=', 'Cancelled')->count();
        $this->totalAppointmentsStatus["ALL"] = $query->clone()->count();

        // Count by appointment type
        $this->totalAppointments["KICK OFF MEETING SESSION (NEW)"] = $query->clone()->where('type', 'KICK OFF MEETING SESSION (NEW)')
            ->where('status', '!=', 'Cancelled')->count();
        $this->totalAppointments["IMPLEMENTATION SESSION 1"] = $query->clone()->where('type', 'IMPLEMENTATION SESSION 1')
            ->where('status', '!=', 'Cancelled')->count();
        $this->totalAppointments["IMPLEMENTATION SESSION 2"] = $query->clone()->where('type', 'IMPLEMENTATION SESSION 2')
            ->where('status', '!=', 'Cancelled')->count();
        $this->totalAppointments["IMPLEMENTATION SESSION 3"] = $query->clone()->where('type', 'IMPLEMENTATION SESSION 3')
            ->where('status', '!=', 'Cancelled')->count();
        $this->totalAppointments["IMPLEMENTATION SESSION 4"] = $query->clone()->where('type', 'IMPLEMENTATION SESSION 4')
            ->where('status', '!=', 'Cancelled')->count();
        $this->totalAppointments["IMPLEMENTATION SESSION 5"] = $query->clone()->where('type', 'IMPLEMENTATION SESSION 5')
            ->where('status', '!=', 'Cancelled')->count();

        // Count by status
        $this->totalAppointmentsStatus["NEW"] = $query->clone()->where('status', 'New')->count();
        $this->totalAppointmentsStatus["COMPLETED"] = $query->clone()->where('status', 'Completed')->count();
        $this->totalAppointmentsStatus["CANCELLED"] = $query->clone()->where('status', 'Cancelled')->count();
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
            $weekDays[$i]['carbonDate'] = $day->format('Y-m-d');  // Store as string instead of Carbon object
            $weekDays[$i]["today"] = $day->isToday();
        }
        return $weekDays;
    }

    private function getWeeklyAppointments($date = null)
    {
        // Set weekly date range (Monday to Friday)
        $date = $date ? Carbon::parse($date) : Carbon::now();
        $this->startDate = $date->copy()->startOfWeek()->toDateString(); // Monday
        $this->endDate = $date->copy()->startOfWeek()->addDays(4)->toDateString(); // Friday

        // Get implementers data
        $implementerUsers = User::whereIn('role_id', [4, 5])
            ->select('id', 'name', 'avatar_path')
            ->get()
            ->keyBy('name')
            ->toArray();

        // Retrieve implementer appointments for the selected week
        $appointments = DB::table('implementer_appointments')
            ->join('leads', 'leads.id', '=', 'implementer_appointments.lead_id')
            ->join('company_details', 'company_details.lead_id', '=', 'implementer_appointments.lead_id')
            ->select('company_details.company_name', 'implementer_appointments.*')
            ->whereBetween("date", [$this->startDate, $this->endDate])
            ->orderBy('start_time', 'asc')
            ->when($this->selectedImplementers, function ($query) {
                return $query->whereIn('implementer', $this->selectedImplementers);
            })
            ->get();

        // Group appointments by implementer
        $implementerAppointments = [];
        foreach ($appointments as $appointment) {
            $implementerAppointments[$appointment->implementer][] = $appointment;
        }

        $allImplementers = $this->selectedImplementers;

        // If none selected (all by default), fallback to all implementer names
        if (empty($allImplementers)) {
            $allImplementers = User::whereIn('role_id', [4, 5])->pluck('name')->toArray();
            $this->allImplementersSelected = true;
        } else {
            $this->allImplementersSelected = false;
        }

        // Apply implementer filter
        if (!empty($this->selectedImplementers)) {
            $allImplementers = array_intersect($allImplementers, $this->selectedImplementers);
            $this->allImplementersSelected = false;
        } else {
            $this->allImplementersSelected = true;
        }

        $result = [];

        // Process each implementer
        foreach ($allImplementers as $implementerId) {
            $name = trim($implementerId);

            $user = \App\Models\User::where('name', $name)->first();

            if ($user) {
                $implementerName = $user->name;
                $avatarPath = $user->avatar_path ?? null;

                if ($avatarPath) {
                    if (str_starts_with($avatarPath, 'storage/')) {
                        $implementerAvatar = asset($avatarPath);
                    } elseif (str_starts_with($avatarPath, 'uploads/')) {
                        $implementerAvatar = asset('storage/' . $avatarPath);
                    } else {
                        $implementerAvatar = Storage::url($avatarPath);
                    }
                } else {
                    $implementerAvatar = $user->getFilamentAvatarUrl() ?? asset('storage/uploads/photos/default-avatar.png');
                }
            } else {
                $implementerName = $implementerId;
                $implementerAvatar = asset('storage/uploads/photos/default-avatar.png');

                Log::warning("Unknown implementer name", ['implementerName' => $implementerId]);
            }

            // Initialize data structure for this implementer
            $data = [
                'implementerId' => $user->id ?? null,
                'implementerName' => $implementerName,
                'implementerAvatar' => $implementerAvatar,
                'mondayAppointments' => [],
                'tuesdayAppointments' => [],
                'wednesdayAppointments' => [],
                'thursdayAppointments' => [],
                'fridayAppointments' => [],
                'newAppointment' => [
                    'monday' => 0,
                    'tuesday' => 0,
                    'wednesday' => 0,
                    'thursday' => 0,
                    'friday' => 0,
                ],
                'leave' => $user ? UserLeave::getUserLeavesByDateRange($user->id, $this->startDate, $this->endDate) : [],
            ];

            // Process appointments for this implementer
            $implementerAppts = $appointments->where('implementer', $implementerId);

            foreach ($implementerAppts as $appointment) {
                $dayOfWeek = strtolower(Carbon::parse($appointment->date)->format('l')); // e.g., 'monday'
                $dayField = "{$dayOfWeek}Appointments";

                // Count active appointments for summary
                if ($appointment->status !== "Cancelled") {
                    $data['newAppointment'][$dayOfWeek]++;
                }

                // Format appointment times
                $appointment->start_time = Carbon::parse($appointment->start_time)->format('g:i A');
                $appointment->end_time = Carbon::parse($appointment->end_time)->format('g:i A');
                $appointment->url = route('filament.admin.resources.leads.view', ['record' => Encryptor::encrypt($appointment->lead_id)]);

                // Apply filters
                $includeAppointmentType = $this->allAppointmentTypeSelected ||
                                         in_array($appointment->type, $this->selectedAppointmentType);

                $includeSessionType = $this->allSessionTypeSelected ||
                                     in_array($appointment->appointment_type, $this->selectedSessionType);

                $includeStatus = $this->allStatusSelected ||
                               in_array(strtoupper($appointment->status), $this->selectedStatus);

                if ($includeAppointmentType && $includeSessionType && $includeStatus) {
                    $data[$dayField][] = $appointment;
                }
            }

            // Count appointments for statistics
            $this->countAppointments($data['newAppointment']);
            $result[] = $data;
        }

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

    public function getAllImplementers()
    {
        // Get implementers (role_id 4 and 5)
        $implementers = User::whereIn('role_id', [4, 5])
            ->select('id', 'name', 'avatar_path')
            ->orderBy('name')
            ->get()
            ->map(function ($implementer) {
                // Process avatar URL
                $avatarUrl = null;
                if ($implementer->avatar_path) {
                    if (str_starts_with($implementer->avatar_path, 'storage/')) {
                        $avatarUrl = asset($implementer->avatar_path);
                    } elseif (str_starts_with($implementer->avatar_path, 'uploads/')) {
                        $avatarUrl = asset('storage/' . $implementer->avatar_path);
                    } else {
                        $avatarUrl = Storage::url($implementer->avatar_path);
                    }
                } else {
                    $avatarUrl = config('filament.default_avatar_url', asset('storage/uploads/photos/default-avatar.png'));
                }

                return [
                    'id' => $implementer->name,
                    'name' => $implementer->name,
                    'avatar_path' => $implementer->avatar_path,
                    'avatar_url' => $avatarUrl
                ];
            })
            ->toArray();

        return $implementers;
    }

    private function countAppointments($data)
    {
        foreach ($data as $day => $value) {
            if ($value == 0) {
                $this->newAppointmentCount[$day]["noAppointment"] = ($this->newAppointmentCount[$day]["noAppointment"] ?? 0) + 1;
            } else if ($value == 1) {
                $this->newAppointmentCount[$day]["oneAppointment"] = ($this->newAppointmentCount[$day]["oneAppointment"] ?? 0) + 1;
            } else if ($value >= 2) {
                $this->newAppointmentCount[$day]["multipleAppointment"] = ($this->newAppointmentCount[$day]["multipleAppointment"] ?? 0) + 1;
            }
        }
    }

    public function render()
    {
        // Initialize appointment counts
        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day) {
            $this->newAppointmentCount[$day]["noAppointment"] = 0;
            $this->newAppointmentCount[$day]["oneAppointment"] = 0;
            $this->newAppointmentCount[$day]["multipleAppointment"] = 0;
        }

        // Load weekly appointments
        $this->rows = $this->getWeeklyAppointments($this->date);

        // Load date display
        $this->weekDays = $this->getWeekDateDays($this->date);

        // Get statistics
        $this->getNumberOfAppointments($this->selectedImplementers);
        $this->calculateAppointmentBreakdown();

        // Get holidays and leaves
        $this->holidays = PublicHoliday::getPublicHoliday($this->startDate, $this->endDate);
        $selectedNames = $this->selectedImplementers;

        // Get users matching selected names
        $implementerUsers = User::whereIn('name', $selectedNames)->get();
        $implementerIds = $implementerUsers->pluck('id')->toArray();

        // Now fetch leaves only if any implementers were selected
        $this->leaves = [];

        if ($this->allImplementersSelected || count($implementerIds) > 0) {
            $this->leaves = UserLeave::getImplementerWeeklyLeavesByDateRange(
                $this->startDate,
                $this->endDate,
                $this->allImplementersSelected ? null : $implementerIds
            );
        }

        $this->currentMonth = $this->date->startOfWeek()->format('F Y');

        return view('livewire.implementer-calendar');
    }

    public function calculateAppointmentBreakdown()
    {
        $query = DB::table('implementer_appointments')
            ->where('status', '!=', 'Cancelled')
            ->whereBetween('date', [$this->startDate, $this->endDate]);

        if (!empty($this->selectedImplementers)) {
            $query->whereIn('implementer', $this->selectedImplementers);
        }

        $appointments = $query->get();

        $result = [
            'KICK OFF MEETING SESSION (NEW)' => 0,
            'IMPLEMENTATION SESSION 1' => 0,
            'IMPLEMENTATION SESSION 2' => 0,
            'IMPLEMENTATION SESSION 3' => 0,
            'IMPLEMENTATION SESSION 4' => 0,
            'IMPLEMENTATION SESSION 5' => 0,
        ];

        foreach ($appointments as $appointment) {
            $type = $appointment->type ?? 'Unknown';
            $result[$type] = ($result[$type] ?? 0) + 1;
        }

        $this->appointmentBreakdown = $result;
    }
}
