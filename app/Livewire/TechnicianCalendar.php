<?php

namespace App\Livewire;

use App\Classes\Encryptor;
use App\Models\PublicHoliday;
use App\Models\User;
use App\Models\UserLeave;
use App\Models\Reseller;
use App\Models\RepairAppointment;
use Carbon\Carbon;
use Illuminate\Database\Console\DumpCommand;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Illuminate\Support\Str;

class TechnicianCalendar extends Component
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
    public $newRepairCount = [];

    //Dropdown
    public $showDropdown = true;

    // Badge
    public $totalRepairs;

    // Dropdown
    public array $status = ["NEW", "DONE", "CANCELLED"];
    public array $selectedStatus = [];
    public bool $allStatusSelected = true;

    public $technicians;
    public array $selectedTechnicians = [];
    public bool $allTechniciansSelected = true;

    public array $repairTypes = ["NEW INSTALLATION", "REPAIR", "MAINTENANCE SERVICE"];
    public array $selectedRepairType = [];
    public bool $allRepairTypeSelected = true;

    public array $appointmentTypes = ["ONSITE"];
    public array $selectedAppointmentType = [];
    public bool $allAppointmentTypeSelected = true;

    public $repairBreakdown = [];

    public function mount()
    {
        // Load all technicians model - convert to array to prevent collection serialization issues
        $this->technicians = $this->getAllTechnicians();

        // Set Date to today
        $this->date = Carbon::now();

        // If current user is a technician then only can access their own calendar
        if (auth()->user()->role_id == 9) {
            $this->selectedTechnicians[] = auth()->user()->id;
        }

        // Initialize the newRepairCount array
        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day) {
            $this->newRepairCount[$day] = [
                "noRepair" => 0,
                "oneRepair" => 0,
                "multipleRepair" => 0
            ];
        }
    }

    // Update date variable when user choose another date
    public function updatedWeekDate()
    {
        $this->date = Carbon::parse($this->weekDate);
    }

    // For Filtering
    public function updatedAllTechniciansSelected()
    {
        if ($this->allTechniciansSelected == true)
            $this->selectedTechnicians = [];
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

    public function updatedSelectedRepairType()
    {
        if (!empty($this->selectedRepairType)) {
            $this->allRepairTypeSelected = false;
        } else
            $this->allRepairTypeSelected = true;
    }

    public function updatedAllRepairTypeSelected()
    {
        if ($this->allRepairTypeSelected == true)
            $this->selectedRepairType = [];
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

    // Get Total Number of Repair Appointments by type and status
    private function getNumberOfRepairs($selectedTechnicians = null)
    {
        $query = DB::table('repair_appointments')->whereBetween('date', [$this->startDate, $this->endDate]);

        if (!empty($selectedTechnicians)) {
            $query->whereIn("technician", $selectedTechnicians);
            $this->totalRepairs = ["ALL" => 0, 'NEW INSTALLATION' => 0, "REPAIR" => 0, "MAINTENANCE SERVICE" => 0];
            $this->totalRepairs["ALL"] = $query->clone()->whereNot('status', 'Cancelled')->count();
            $this->totalRepairs["NEW INSTALLATION"] = $query->clone()->where("type", "NEW INSTALLATION")->whereNot('status', 'Cancelled')->count();
            $this->totalRepairs["REPAIR"] = $query->clone()->where("type", "REPAIR")->whereNot('status', 'Cancelled')->count();
            $this->totalRepairs["MAINTENANCE SERVICE"] = $query->clone()->where("type", "MAINTENANCE SERVICE")->whereNot('status', 'Cancelled')->count();
            $this->totalRepairs["NEW"] = $query->clone()->where("status", "New")->count();
            $this->totalRepairs["DONE"] = $query->clone()->where("status", "DONE")->count();
            $this->totalRepairs["CANCELLED"] = $query->clone()->where("status", "Cancelled")->count();
        } else {
            $this->totalRepairs = ["ALL" => 0, 'NEW INSTALLATION' => 0, "REPAIR" => 0, "MAINTENANCE SERVICE" => 0];
            $this->totalRepairs["ALL"] = $query->clone()->whereNot('status', 'Cancelled')->count();
            $this->totalRepairs["NEW INSTALLATION"] = $query->clone()->where("type", "NEW INSTALLATION")->whereNot('status', 'Cancelled')->count();
            $this->totalRepairs["REPAIR"] = $query->clone()->where("type", "REPAIR")->whereNot('status', 'Cancelled')->count();
            $this->totalRepairs["MAINTENANCE SERVICE"] = $query->clone()->where("type", "MAINTENANCE SERVICE")->whereNot('status', 'Cancelled')->count();
            $this->totalRepairs["NEW"] = $query->clone()->where("status", "New")->count();
            $this->totalRepairs["DONE"] = $query->clone()->where("status", "Done")->count();
            $this->totalRepairs["CANCELLED"] = $query->clone()->where("status", "Cancelled")->count();
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
            $weekDays[$i]['carbonDate'] = $startOfWeek->copy()->addDays($i)->format('Y-m-d');  // Store as string instead of Carbon object
            if ($day->isToday()) {
                $weekDays[$i]["today"] = true; // Set to true if today's date is found
            } else
                $weekDays[$i]["today"] = false;
        }
        return $weekDays;
    }

    private function getInternalTechnicianIdByName($name) {
        // Look up the ID for an internal technician by their name
        $user = User::where('name', $name)
            ->where('role_id', 9)
            ->first();

        return $user ? $user->id : null;
    }

    private function getWeeklyAppointments($date = null)
    {
        // Have to make sure weekly is weekly date. Monday to Friday
        $date = $date ? Carbon::parse($date) : Carbon::now();
        $this->startDate = $date->copy()->startOfWeek()->toDateString(); // Monday
        $this->endDate = $date->copy()->startOfWeek()->addDays(4)->toDateString(); // Friday

        // Get internal technicians
        $internalTechnicians = User::where('role_id', 9)
            ->select('id', 'name', 'avatar_path') // Added avatar_path here
            ->get()
            ->keyBy('id')
            ->toArray();

        // Get reseller technicians as a keyed array for faster lookup
        $resellerCompanies = Reseller::select('company_name')
            ->get()
            ->pluck('company_name')
            ->flip() // Make company_name the key for quick lookups
            ->toArray();

        // Retrieve all repair appointments between start and end date
        $appointments = DB::table('repair_appointments')
            ->join('leads', 'leads.id', '=', 'repair_appointments.lead_id')
            ->join('company_details', 'company_details.lead_id', '=', 'repair_appointments.lead_id')
            ->select('company_details.company_name', 'repair_appointments.*')
            ->whereBetween("date", [$this->startDate, $this->endDate])
            ->orderBy('start_time', 'asc')
            ->get();

        // Group appointments by technician (either user ID or reseller company name)
        $technicianAppointments = [];

        foreach ($appointments as $appointment) {
            $technicianAppointments[$appointment->technician][] = $appointment;
        }

        // Get all technicians who have appointments or are selected
        $allTechnicians = array_unique(array_merge(
            array_keys($technicianAppointments),
            $this->selectedTechnicians
        ));

        // Prepare the result
        $result = [];

        foreach ($allTechnicians as $technicianId) {
            // Skip if filtering and not in selected technicians
            if (!empty($this->selectedTechnicians) && !in_array($technicianId, $this->selectedTechnicians)) {
                continue;
            }

            // NEW LOGIC: Determine if this is an internal technician or reseller
            // Check if the ID exists as a company_name in the reseller table
            $isReseller = isset($resellerCompanies[$technicianId]);
            $isInternal = !$isReseller && isset($internalTechnicians[$technicianId]);

            // Get name based on type
            if (!$isReseller) {
                info("Internal Technician111");

                $internalTechId = $this->getInternalTechnicianIdByName($technicianId);

                if ($internalTechId && isset($internalTechnicians[$internalTechId])) {
                    // Found the technician by name
                    $technicianName = $internalTechnicians[$internalTechId]['name'];
                    $technicianAvatar = isset($internalTechnicians[$internalTechId]['avatar_path']) &&
                                        $internalTechnicians[$internalTechId]['avatar_path'] ?
                                        $this->getAvatarUrl($internalTechnicians[$internalTechId]['avatar_path']) :
                                        asset('storage/uploads/photos/reseller-avatar.png');
                } else {
                    // Cannot find this technician, use default values
                    Log::warning("Technician not found in internal list: " . $technicianId);
                    $technicianName = $technicianId;
                    $technicianAvatar = asset('storage/uploads/photos/reseller-avatar.png');
                }
            } else {
                // This is a reseller or an unknown technician
                $technicianName = $technicianId;
                $technicianAvatar = asset('storage/uploads/photos/reseller-avatar.png');
            }
            // Initialize technician data structure
            $data = [
                'technicianID' => $technicianId,
                'technicianName' => $technicianName,
                'technicianAvatar' => $technicianAvatar,
                'isReseller' => $isReseller,
                'mondayAppointments' => [],
                'tuesdayAppointments' => [],
                'wednesdayAppointments' => [],
                'thursdayAppointments' => [],
                'fridayAppointments' => [],
                'newRepair' => [
                    'monday' => 0,
                    'tuesday' => 0,
                    'wednesday' => 0,
                    'thursday' => 0,
                    'friday' => 0,
                ],
                'leave' => $isInternal ? UserLeave::getUserLeavesByDateRange($technicianId, $this->startDate, $this->endDate) : [],
            ];

            // Get this technician's appointments
            $technicianAppts = $appointments->where('technician', $technicianId);

            // Group appointments by the day of the week
            foreach ($technicianAppts as $appointment) {
                $dayOfWeek = strtolower(Carbon::parse($appointment->date)->format('l')); // e.g., 'monday'
                $dayField = "{$dayOfWeek}Appointments";

                // For repair summary which shows repairs per day
                if ($appointment->status !== "Cancelled") {
                    $data['newRepair'][$dayOfWeek]++;
                }

                // Convert start_time and end_time to formatted time strings
                $appointment->start_time = Carbon::parse($appointment->start_time)->format('g:i A');
                $appointment->end_time = Carbon::parse($appointment->end_time)->format('g:i A');
                $appointment->url = route('filament.admin.resources.leads.view', ['record' => Encryptor::encrypt($appointment->lead_id)]);

                // Filtering Repair Type and Appointment Type
                if (
                    $this->allAppointmentTypeSelected && $this->allRepairTypeSelected
                    || in_array($appointment->type, $this->selectedRepairType) && $this->allAppointmentTypeSelected
                    || $this->allRepairTypeSelected && in_array($appointment->appointment_type, $this->selectedAppointmentType)
                    || in_array($appointment->type, $this->selectedRepairType) && in_array($appointment->appointment_type, $this->selectedAppointmentType)
                ) {
                    if ($this->allStatusSelected || in_array(Str::upper($appointment->status), $this->selectedStatus)) {
                        $data[$dayField][] = $appointment;
                    }
                }
            }

            $this->countRepairs($data['newRepair']);
            $result[] = $data;
        }

        // Sort result by technician name
        usort($result, function($a, $b) {
            // Sort by type first (internal technicians before resellers)
            if ($a['isReseller'] !== $b['isReseller']) {
                return $a['isReseller'] ? 1 : -1; // Internal technicians (false) come before resellers (true)
            }
            // If same type, sort by name
            return strcmp($a['technicianName'], $b['technicianName']);
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

    public function getAllTechnicians()
    {
        // Get all internal technicians (role_id 9)
        $internalTechnicians = User::where('role_id', 9)
        ->select('id', 'name', 'avatar_path')
        ->orderBy('name')
        ->get()
        ->map(function ($technician) {
            // Process avatar path
            $avatarUrl = null;
            if ($technician->avatar_path) {
                if (str_starts_with($technician->avatar_path, 'http://') ||
                    str_starts_with($technician->avatar_path, 'https://')) {
                    $avatarUrl = $technician->avatar_path;
                } else if (str_starts_with($technician->avatar_path, 'storage/') ||
                          str_starts_with($technician->avatar_path, 'uploads/')) {
                    $avatarUrl = asset($technician->avatar_path);
                } else {
                    $avatarUrl = Storage::url($technician->avatar_path);
                }
            } else {
                $avatarUrl = asset('storage/uploads/photos/reseller-avatar.png');
            }

            return [
                'id' => $technician->id,
                'name' => $technician->name,
                'avatar_path' => $technician->avatar_path, // Keep original path
                'avatar_url' => $avatarUrl, // Add processed URL
                'type' => 'user',
                'isReseller' => false
            ];
        })
        ->toArray();

        // Get all resellers as "technicians"
        $resellers = Reseller::select('company_name as name')
            ->orderBy('company_name')
            ->get()
            ->map(function ($reseller) {
                return [
                    'id' => $reseller->name, // Use company name as ID for resellers
                    'name' => $reseller->name,
                    'type' => 'reseller',
                    'avatar_path' => null,
                    'isReseller' => true
                ];
            })
            ->toArray();

        // Combine both arrays and sort
        $combined = array_merge($internalTechnicians, $resellers);

        // Sort by isReseller first (internals first), then by name
        usort($combined, function($a, $b) {
            if ($a['isReseller'] !== $b['isReseller']) {
                return $a['isReseller'] ? 1 : -1; // Internal first
            }
            return strcmp($a['name'], $b['name']);
        });

        return $combined;
    }

    public function getSelectedTechnicians(array $ids)
    {
        // This method returns an array instead of a Collection to avoid serialization issues
        $result = [];

        foreach ($ids as $id) {
            // Check if this is a numeric ID (internal technician) or a string (reseller)
            if (is_numeric($id)) {
                $user = User::where('id', $id)->where('role_id', 9)->first();
                if ($user) {
                    $result[] = [
                        'id' => $user->id,
                        'name' => $user->name,
                        'avatar_path' => $user->avatar_path,
                        'type' => 'user'
                    ];
                }
            } else {
                // This is a reseller company name
                $result[] = [
                    'id' => $id,
                    'name' => $id,
                    'type' => 'reseller'
                ];
            }
        }

        // Sort by name
        usort($result, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $result;
    }

    private function countRepairs($data)
    {
        foreach ($data as $day => $value) {
            if ($value == 0) {
                $this->newRepairCount[$day]["noRepair"] = ($this->newRepairCount[$day]["noRepair"] ?? 0) + 1;
            } else if ($value == 1) {
                $this->newRepairCount[$day]["oneRepair"] = ($this->newRepairCount[$day]["oneRepair"] ?? 0) + 1;
            } else if ($value >= 2) {
                $this->newRepairCount[$day]["multipleRepair"] = ($this->newRepairCount[$day]["multipleRepair"] ?? 0) + 1;
            }
        }
    }

    public function render()
    {
        // Initialize
        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day) {
            $this->newRepairCount[$day]["noRepair"] = 0;
            $this->newRepairCount[$day]["oneRepair"] = 0;
            $this->newRepairCount[$day]["multipleRepair"] = 0;
        }

        // Load Weekly Appointments
        $this->rows = $this->getWeeklyAppointments($this->date);

        // Load Date Display
        $this->weekDays = $this->getWeekDateDays($this->date);

        // Count Repairs
        $this->getNumberOfRepairs($this->selectedTechnicians);

        // Get holidays and leaves - convert PublicHoliday model collection to array
        $this->holidays = PublicHoliday::getPublicHoliday($this->startDate, $this->endDate);

        // Only get leaves for internal technicians (not resellers)
        $internalTechnicians = array_filter($this->selectedTechnicians, 'is_numeric');

        // Get leaves data
        $leaves = UserLeave::getWeeklyLeavesByDateRange($this->startDate, $this->endDate, $internalTechnicians);

        // Process each leave record to add technician avatar and ensure it's a technician
        $processedLeaves = [];
        foreach ($leaves as $leave) {
            // Only include users with role_id = 9 (technicians)
            $user = User::where('id', $leave['user_id'])->where('role_id', 9)->first();
            if ($user) {
                $leave['technicianName'] = $user->name;
                $leave['technicianAvatar'] = $this->getAvatarUrl($user->avatar_path);
                $processedLeaves[] = $leave;
            }
        }

        $this->leaves = $processedLeaves;

        $this->currentMonth = $this->date->startOfWeek()->format('F Y');

        return view('livewire.technician-calendar');
    }

    public function calculateRepairBreakdown()
    {
        $query = DB::table('repair_appointments')
            ->where('status', '!=', 'Cancelled')
            ->whereBetween('date', [$this->startDate, $this->endDate]);

        if (!empty($this->selectedTechnicians)) {
            $query->whereIn('technician', $this->selectedTechnicians);
        }

        $appointments = $query->get();

        $result = [
            'NEW INSTALLATION' => 0,
            'REPAIR' => 0,
            'MAINTENANCE SERVICE' => 0,
        ];

        foreach ($appointments as $appointment) {
            $type = $appointment->type ?? 'Unknown';
            $result[$type] = ($result[$type] ?? 0) + 1;
        }

        $this->repairBreakdown = $result;
    }

    private function getAvatarUrl($path)
    {
        if (!$path) {
            return asset('storage/uploads/photos/reseller-avatar.png');
        }

        // If path already starts with http or https, use as-is
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // If path starts with "storage/" treat as a public asset
        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        // If path starts with "uploads/", add storage/ in front
        if (str_starts_with($path, 'uploads/')) {
            return asset('storage/' . $path);
        }

        // Otherwise use Storage::url for paths like "app/public/..."
        return Storage::url($path);
    }
}
