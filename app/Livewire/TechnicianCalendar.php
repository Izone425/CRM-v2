<?php

namespace App\Livewire;

use App\Classes\Encryptor;
use App\Models\PublicHoliday;
use App\Models\Reseller;
use App\Models\User;
use App\Models\UserLeave;
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
    public $newRepairCount;

    //Dropdown
    public $showDropdown = false;

    // Badge
    public $totalRepairs;

    // Dropdown
    public array $status = ["DONE", "NEW", "CANCELLED"];
    public array $selectedStatus = [];
    public bool $allStatusSelected = true;

    public $technicians;
    public array $selectedTechnicians = [];
    public bool $allTechniciansSelected = true;

    public array $repairTypes = ["NEW INSTALLATION", "REPAIR", "SITE SURVEY", "FINGERTEC TASK", "TIMETEC HR TASK", "TIMETEC PARKING TASK", "TIMETEC PROPERTY TASK"];
    public array $selectedRepairType = [];
    public bool $allRepairTypeSelected = true;

    public array $appointmentTypes = ["ONSITE"];
    public array $selectedAppointmentType = [];
    public bool $allAppointmentTypeSelected = true;

    public $repairBreakdown = [];

    public function mount()
    {
        // Load all technicians
        $this->technicians = $this->getAllTechnicians();

        // Set Date to today
        $this->date = Carbon::now();

        // If current user is a technician then only can access their own calendar
        // if (auth()->user()->role_id == 9) {
        //     $this->selectedTechnicians[] = auth()->user()->id;
        // }
    }

    // Update date variable when user choose another date
    public function updatedWeekDate()
    {
        $this->date = Carbon::parse($this->weekDate);
    }

    // For Filtering
    public function updatedSelectedTechnicians()
    {
        if (!empty($this->selectedTechnicians)) {
            $this->allTechniciansSelected = false;
        } else {
            $this->allTechniciansSelected = true;
        }
    }

    public function updatedAllTechniciansSelected()
    {
        if ($this->allTechniciansSelected == true)
            $this->selectedTechnicians = [];
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

    public function updatedSelectedRepairType()
    {
        if (!empty($this->selectedRepairType)) {
            $this->allRepairTypeSelected = false;
        } else {
            $this->allRepairTypeSelected = true;
        }
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
        } else {
            $this->allAppointmentTypeSelected = true;
        }
    }

    public function updatedAllAppointmentTypeSelected()
    {
        if ($this->allAppointmentTypeSelected == true)
            $this->selectedAppointmentType = [];
    }

    // Get Total Number of Repairs for different types and statuses
    private function getNumberOfRepairs($selectedTechnicians = null)
    {
        // Base query
        $query = DB::table('repair_appointments')
            ->whereBetween('date', [$this->startDate, $this->endDate]);

        // Apply technician filter if provided
        if (!empty($selectedTechnicians)) {
            $query->whereIn("technician", $selectedTechnicians);
        }

        // Initialize counters
        $this->totalRepairs = [
            "ALL" => 0,
            "NEW INSTALLATION" => 0,
            "REPAIR" => 0,
            "SITE SURVEY" => 0,
            "INTERNAL TECHNICIAN TASK" => 0,
        ];

        // Count active appointments (not cancelled)
        $this->totalRepairs["ALL"] = $query->clone()->where('status', '!=', 'Cancelled')->count();

        // Count by repair type
        $this->totalRepairs["NEW INSTALLATION"] = $query->clone()->where('type', 'NEW INSTALLATION')
            ->where('status', '!=', 'Cancelled')->count();
        $this->totalRepairs["REPAIR"] = $query->clone()->where('type', 'REPAIR')
            ->where('status', '!=', 'Cancelled')->count();
        $this->totalRepairs["SITE SURVEY"] = $query->clone()->where('type', 'SITE SURVEY')
            ->where('status', '!=', 'Cancelled')->count();

        // Count combined "INTERNAL TECHNICIAN TASK" category
        $this->totalRepairs["INTERNAL TECHNICIAN TASK"] = $query->clone()
            ->whereIn('type', ['FINGERTEC TASK', 'TIMETEC HR TASK', 'TIMETEC PARKING TASK', 'TIMETEC PROPERTY TASK'])
            ->where('status', '!=', 'Cancelled')->count();
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

    // private function getWeeklyAppointments($date = null)
    // {
    //     // Set weekly date range (Monday to Friday)
    //     $date = $date ? Carbon::parse($date) : Carbon::now();
    //     $this->startDate = $date->copy()->startOfWeek()->toDateString(); // Monday
    //     $this->endDate = $date->copy()->startOfWeek()->addDays(4)->toDateString(); // Friday

    //     // Get internal technicians data
    //     $internalTechnicians = User::where('role_id', 9)
    //         ->select('id', 'name', 'avatar_path')
    //         ->get()
    //         ->keyBy('id')
    //         ->toArray();

    //     // Get reseller companies
    //     $resellerCompanies = Reseller::select('company_name')
    //         ->get()
    //         ->pluck('company_name')
    //         ->flip() // Make company_name the key for lookups
    //         ->toArray();

    //     // Retrieve repair appointments for the selected week
    //     $appointments = DB::table('repair_appointments')
    //         ->leftJoin('leads', 'leads.id', '=', 'repair_appointments.lead_id')
    //         ->leftJoin('company_details', 'company_details.lead_id', '=', 'repair_appointments.lead_id')
    //         ->select(
    //             DB::raw('CASE
    //                 WHEN repair_appointments.lead_id IS NULL THEN "No Company"
    //                 ELSE COALESCE(company_details.company_name, "No Company")
    //             END as company_name'),
    //             'repair_appointments.*'
    //         )
    //         ->whereBetween("date", [$this->startDate, $this->endDate])
    //         ->orderBy('start_time', 'asc')
    //         ->when($this->selectedTechnicians, function ($query) {
    //             return $query->whereIn('technician', $this->selectedTechnicians);
    //         })
    //         ->get();

    //     // Group appointments by technician
    //     $technicianAppointments = [];
    //     foreach ($appointments as $appointment) {
    //         $technicianAppointments[$appointment->technician][] = $appointment;
    //     }

    //     $allTechnicians = $this->selectedTechnicians;

    //     // If none selected (all by default), fallback to internal + reseller names
    //     if (empty($allTechnicians)) {
    //         $allTechnicians = array_merge(
    //             User::where('role_id', 9)->pluck('name')->toArray(),
    //             Reseller::pluck('company_name')->toArray()
    //         );
    //         $this->allTechniciansSelected = true;
    //     } else {
    //         $this->allTechniciansSelected = false;
    //     }
    //     // Apply technician filter
    //     if (!empty($this->selectedTechnicians)) {
    //         $allTechnicians = array_intersect($allTechnicians, $this->selectedTechnicians);
    //         $this->allTechniciansSelected = false;
    //     } else {
    //         $this->allTechniciansSelected = true;
    //     }

    //     $result = [];

    //     // Process each technician
    //     foreach ($allTechnicians as $technicianId) {
    //         if (isset($resellerCompanies[$technicianId])) {
    //             continue;
    //         }
    //         $name = trim($technicianId);

    //         $user = \App\Models\User::where('name', $name)->first();

    //         $reseller = \App\Models\Reseller::where('company_name', $name)->first();

    //         if ($user) {
    //             $technicianName = $user->name;
    //             $avatarPath = $user->avatar_path ?? null;

    //             if ($avatarPath) {
    //                 if (str_starts_with($avatarPath, 'storage/')) {
    //                     $technicianAvatar = asset($avatarPath);
    //                 } elseif (str_starts_with($avatarPath, 'uploads/')) {
    //                     $technicianAvatar = asset('storage/' . $avatarPath);
    //                 } else {
    //                     $technicianAvatar = Storage::url($avatarPath);
    //                 }
    //             } else {
    //                 $technicianAvatar = $user->getFilamentAvatarUrl() ?? asset('storage/uploads/photos/default-avatar.png');
    //             }
    //         } elseif ($reseller) {
    //             $technicianName = $reseller->company_name;
    //             $technicianAvatar = asset('storage/uploads/photos/reseller-avatar.png');
    //         } else {
    //             $technicianName = $technicianId;
    //             $technicianAvatar = asset('storage/uploads/photos/reseller-avatar.png');

    //             Log::warning("Unknown technician name", ['technicianName' => $technicianId]);
    //         }

    //         // Initialize data structure for this technician
    //         $data = [
    //             'technicianID' => $user->id ?? $reseller->id ?? null,
    //             'technicianName' => $technicianName,
    //             'technicianAvatar' => $technicianAvatar,
    //             'mondayAppointments' => [],
    //             'tuesdayAppointments' => [],
    //             'wednesdayAppointments' => [],
    //             'thursdayAppointments' => [],
    //             'fridayAppointments' => [],
    //             'newRepair' => [
    //                 'monday' => 0,
    //                 'tuesday' => 0,
    //                 'wednesday' => 0,
    //                 'thursday' => 0,
    //                 'friday' => 0,
    //             ],
    //             'leave' => !$reseller && $user ? UserLeave::getUserLeavesByDateRange($user->id, $this->startDate, $this->endDate) : [],
    //         ];
    //         // Process appointments for this technician
    //         $technicianAppts = $appointments->where('technician', $technicianId);

    //         foreach ($technicianAppts as $appointment) {
    //             $dayOfWeek = strtolower(Carbon::parse($appointment->date)->format('l')); // e.g., 'monday'
    //             $dayField = "{$dayOfWeek}Appointments";

    //             // Count active repairs for summary
    //             if ($appointment->status !== "Cancelled") {
    //                 $data['newRepair'][$dayOfWeek]++;
    //             }

    //             // Format appointment times
    //             $appointment->start_time = Carbon::parse($appointment->start_time)->format('g:i A');
    //             $appointment->end_time = Carbon::parse($appointment->end_time)->format('g:i A');
    //             $appointment->url = $appointment->lead_id
    //                 ? route('filament.admin.resources.leads.view', ['record' => Encryptor::encrypt($appointment->lead_id)])
    //                 : '#';

    //             // Map internal task types to the combined category for filtering purposes
    //             $displayType = $appointment->type;
    //             if (in_array($appointment->type, ['FINGERTEC TASK', 'TIMETEC HR TASK', 'TIMETEC PARKING TASK', 'TIMETEC PROPERTY TASK'])) {
    //                 $displayType = 'INTERNAL TECHNICIAN TASK';
    //                 $appointment->is_internal_task = true;

    //                 // Truncate remarks for display if they're too long
    //                 $appointment->display_remarks = !empty($appointment->remarks)
    //                     ? (strlen($appointment->remarks) > 30
    //                         ? substr($appointment->remarks, 0, 30) . '...'
    //                         : $appointment->remarks)
    //                     : 'No remarks';
    //             } else {
    //                 $appointment->is_internal_task = false;
    //             }

    //             // Apply filters
    //             $includeRepairType = $this->allRepairTypeSelected ||
    //                                 in_array($displayType, $this->selectedRepairType);

    //             $includeAppointmentType = $this->allAppointmentTypeSelected ||
    //                                     in_array($appointment->appointment_type, $this->selectedAppointmentType);

    //             $includeStatus = $this->allStatusSelected ||
    //                             in_array(strtoupper($appointment->status), $this->selectedStatus);

    //             if ($includeRepairType && $includeAppointmentType && $includeStatus) {
    //                 // Store original type but add display type property for filtering
    //                 $appointment->display_type = $displayType;
    //                 $data[$dayField][] = $appointment;
    //             }
    //         }

    //         // Count repairs for statistics
    //         $this->countRepairs($data['newRepair']);
    //         $result[] = $data;
    //     }

    //     return $result;
    // }

    private function getWeeklyAppointments($date = null)
    {
        // Set weekly date range (Monday to Friday)
        $date = $date ? Carbon::parse($date) : Carbon::now();
        $this->startDate = $date->copy()->startOfWeek()->toDateString(); // Monday
        $this->endDate = $date->copy()->startOfWeek()->addDays(4)->toDateString(); // Friday

        // Get internal technicians data
        $internalTechnicians = User::where('role_id', 9)
            ->select('id', 'name', 'avatar_path')
            ->get()
            ->keyBy('id')
            ->toArray();

        // Get reseller companies
        $resellerCompanies = Reseller::select('company_name')
            ->get()
            ->pluck('company_name')
            ->flip() // Make company_name the key for lookups
            ->toArray();

        // Retrieve repair appointments for the selected week
        $appointments = DB::table('repair_appointments')
            ->leftJoin('leads', 'leads.id', '=', 'repair_appointments.lead_id')
            ->leftJoin('company_details', 'company_details.lead_id', '=', 'repair_appointments.lead_id')
            ->select(
                DB::raw('CASE
                    WHEN repair_appointments.lead_id IS NULL THEN "No Company"
                    ELSE COALESCE(company_details.company_name, "No Company")
                END as company_name'),
                'repair_appointments.*'
            )
            ->whereBetween("date", [$this->startDate, $this->endDate])
            ->orderBy('start_time', 'asc')
            ->when($this->selectedTechnicians, function ($query) {
                return $query->whereIn('technician', $this->selectedTechnicians);
            })
            ->get();

        // Initialize the 3 default rows
        $result = [];

        // ROW 1 - KI (Khairul Izzudin)
        $kiUser = User::where('name', 'Khairul Izzuddin')->first();
        if ($kiUser) {
            $kiAppointments = $appointments->where('technician', 'Khairul Izzuddin');
            $result[] = $this->buildTechnicianRow($kiUser, $kiAppointments, '');
        } else {
            // Fallback if KI not found
            $result[] = $this->buildDefaultRow('', 'Khairul Izzuddin', collect());
        }

        // ROW 2 - GX (GenX Technology)
        $gxAppointments = $appointments->where('technician', 'GENX TECHNOLOGY (M) SDN BHD');
        $result[] = $this->buildResellerRow('GENX', 'GENX TECHNOLOGY (M) SDN BHD', $gxAppointments);

        // ROW 3 - RS (All other Resellers)
        $otherResellerAppointments = $appointments->whereIn('technician',
            Reseller::where('company_name', '!=', 'GENX TECHNOLOGY (M) SDN BHD')->pluck('company_name')
        );
        $result[] = $this->buildCombinedResellerRow('Reseller', 'Other Resellers', $otherResellerAppointments);

        return $result;
    }

    private function buildTechnicianRow($user, $appointments, $shortName)
    {
        $avatarUrl = null;
        if ($user->avatar_path) {
            if (str_starts_with($user->avatar_path, 'storage/')) {
                $avatarUrl = asset($user->avatar_path);
            } elseif (str_starts_with($user->avatar_path, 'uploads/')) {
                $avatarUrl = asset('storage/' . $user->avatar_path);
            } else {
                $avatarUrl = Storage::url($user->avatar_path);
            }
        } else {
            $avatarUrl = $user->getFilamentAvatarUrl() ?? asset('storage/uploads/photos/default-avatar.png');
        }

        $data = [
            'technicianID' => $user->id,
            'technicianName' => $user->name,
            'technicianShortName' => $shortName,
            'technicianAvatar' => $avatarUrl,
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
            'leave' => UserLeave::getUserLeavesByDateRange($user->id, $this->startDate, $this->endDate),
            'type' => 'technician'
        ];

        // Process appointments for this technician
        foreach ($appointments as $appointment) {
            $dayOfWeek = strtolower(Carbon::parse($appointment->date)->format('l')); // e.g., 'monday'
            $dayField = "{$dayOfWeek}Appointments";

            // Count active repairs for summary
            if ($appointment->status !== "Cancelled") {
                $data['newRepair'][$dayOfWeek]++;
            }

            // Format appointment times
            $appointment->start_time = Carbon::parse($appointment->start_time)->format('g:i A');
            $appointment->end_time = Carbon::parse($appointment->end_time)->format('g:i A');
            $appointment->url = $appointment->lead_id
                ? route('filament.admin.resources.leads.view', ['record' => Encryptor::encrypt($appointment->lead_id)])
                : '#';

            // Apply filters and add to appropriate day
            if ($this->passesFilters($appointment)) {
                $data[$dayField][] = $appointment;
            }
        }

        return $data;
    }

    private function buildResellerRow($shortName, $resellerName, $appointments)
    {
        $data = [
            'technicianID' => $resellerName,
            'technicianName' => $resellerName,
            'technicianShortName' => $shortName,
            'technicianAvatar' => asset('storage/uploads/photos/reseller-avatar.png'),
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
            'leave' => [],
            'type' => 'reseller',
            'reseller_name' => $resellerName
        ];

        // Process appointments for this reseller
        foreach ($appointments as $appointment) {
            $dayOfWeek = strtolower(Carbon::parse($appointment->date)->format('l'));
            $dayField = "{$dayOfWeek}Appointments";

            if ($appointment->status !== "Cancelled") {
                $data['newRepair'][$dayOfWeek]++;
            }

            $appointment->start_time = Carbon::parse($appointment->start_time)->format('g:i A');
            $appointment->end_time = Carbon::parse($appointment->end_time)->format('g:i A');
            $appointment->url = $appointment->lead_id
                ? route('filament.admin.resources.leads.view', ['record' => Encryptor::encrypt($appointment->lead_id)])
                : '#';

            if ($this->passesFilters($appointment)) {
                $data[$dayField][] = $appointment;
            }
        }

        return $data;
    }

    private function buildCombinedResellerRow($shortName, $displayName, $appointments)
    {
        $data = [
            'technicianID' => 'combined_resellers',
            'technicianName' => $displayName,
            'technicianShortName' => $shortName,
            'technicianAvatar' => asset('storage/uploads/photos/reseller-avatar.png'),
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
            'leave' => [],
            'type' => 'combined_resellers'
        ];

        // Process appointments for all other resellers
        foreach ($appointments as $appointment) {
            $dayOfWeek = strtolower(Carbon::parse($appointment->date)->format('l'));
            $dayField = "{$dayOfWeek}Appointments";

            if ($appointment->status !== "Cancelled") {
                $data['newRepair'][$dayOfWeek]++;
            }

            $appointment->start_time = Carbon::parse($appointment->start_time)->format('g:i A');
            $appointment->end_time = Carbon::parse($appointment->end_time)->format('g:i A');
            $appointment->url = $appointment->lead_id
                ? route('filament.admin.resources.leads.view', ['record' => Encryptor::encrypt($appointment->lead_id)])
                : '#';

            // Add reseller name to appointment for display
            $appointment->reseller_company = $appointment->technician;

            if ($this->passesFilters($appointment)) {
                $data[$dayField][] = $appointment;
            }
        }

        return $data;
    }

    private function buildDefaultRow($shortName, $name, $appointments)
    {
        return [
            'technicianID' => strtolower($shortName),
            'technicianName' => $name,
            'technicianShortName' => $shortName,
            'technicianAvatar' => asset('storage/uploads/photos/default-avatar.png'),
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
            'leave' => [],
            'type' => 'default'
        ];
    }

    private function passesFilters($appointment)
    {
        // Map internal task types
        $displayType = $appointment->type;
        if (in_array($appointment->type, ['FINGERTEC TASK', 'TIMETEC HR TASK', 'TIMETEC PARKING TASK', 'TIMETEC PROPERTY TASK'])) {
            $displayType = 'INTERNAL TECHNICIAN TASK';
            $appointment->is_internal_task = true;
            $appointment->display_remarks = !empty($appointment->remarks)
                ? (strlen($appointment->remarks) > 30 ? substr($appointment->remarks, 0, 30) . '...' : $appointment->remarks)
                : 'No remarks';
        } else {
            $appointment->is_internal_task = false;
        }

        // Apply filters
        $includeRepairType = $this->allRepairTypeSelected || in_array($displayType, $this->selectedRepairType);
        $includeAppointmentType = $this->allAppointmentTypeSelected || in_array($appointment->appointment_type, $this->selectedAppointmentType);
        $includeStatus = $this->allStatusSelected || in_array(strtoupper($appointment->status), $this->selectedStatus);

        $appointment->display_type = $displayType;
        return $includeRepairType && $includeAppointmentType && $includeStatus;
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
        // Get internal technicians (role_id 9)
        $internalTechnicians = User::where('role_id', 9)
            ->select('id', 'name', 'avatar_path')
            ->orderBy('name')
            ->get()
            ->map(function ($technician) {
                // Process avatar URL
                $avatarUrl = null;
                if ($technician->avatar_path) {
                    if (str_starts_with($technician->avatar_path, 'storage/')) {
                        $avatarUrl = asset($technician->avatar_path);
                    } elseif (str_starts_with($technician->avatar_path, 'uploads/')) {
                        $avatarUrl = asset('storage/' . $technician->avatar_path);
                    } else {
                        $avatarUrl = Storage::url($technician->avatar_path);
                    }
                } else {
                    $avatarUrl = config('filament.default_avatar_url', asset('storage/uploads/photos/default-avatar.png'));
                }

                return [
                    'id' => $technician->id,
                    'name' => $technician->name,
                    'avatar_path' => $technician->avatar_path,
                    'avatar_url' => $avatarUrl,
                    'type' => 'internal',
                    'isReseller' => false
                ];
            })
            ->toArray();

        // Get resellers as "technicians"
        $resellers = Reseller::select('company_name as name')
            ->orderBy('company_name')
            ->get()
            ->map(function ($reseller) {
                return [
                    'id' => $reseller->name, // Use company name as ID
                    'name' => $reseller->name,
                    'avatar_path' => null,
                    'avatar_url' => asset('storage/uploads/photos/reseller-avatar.png'),
                    'type' => 'reseller',
                    'isReseller' => true
                ];
            })
            ->toArray();

        // Combine both sets
        $allTechnicians = array_merge($internalTechnicians, $resellers);

        // Sort: internal first, then alphabetically
        usort($allTechnicians, function($a, $b) {
            if ($a['isReseller'] !== $b['isReseller']) {
                return $a['isReseller'] ? 1 : -1; // Internal first
            }
            return strcmp($a['name'], $b['name']);
        });

        return $allTechnicians;
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

    public $resellerAppointments = [
        'monday' => [],
        'tuesday' => [],
        'wednesday' => [],
        'thursday' => [],
        'friday' => [],
    ];

    private function getResellerAppointments()
    {
        // Get all resellers from your system
        $resellerCompanyNames = \App\Models\Reseller::pluck('company_name')->toArray();

        // Initialize arrays for each day
        $appointments = [
            'monday' => [],
            'tuesday' => [],
            'wednesday' => [],
            'thursday' => [],
            'friday' => [],
        ];

        // Query appointments for these resellers
        $resellerAppointments = DB::table('repair_appointments')
            ->leftJoin('leads', 'leads.id', '=', 'repair_appointments.lead_id')
            ->leftJoin('company_details', 'company_details.lead_id', '=', 'repair_appointments.lead_id')
            ->select(
                DB::raw('CASE
                    WHEN repair_appointments.lead_id IS NULL THEN "No Company"
                    ELSE COALESCE(company_details.company_name, "No Company")
                END as company_name'),
                'repair_appointments.*'
            )
            ->whereIn('technician', $resellerCompanyNames)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->orderBy('start_time', 'asc')
            ->get();

        // Apply filters if selected
        if (!$this->allRepairTypeSelected && !empty($this->selectedRepairType)) {
            $resellerAppointments = $resellerAppointments->filter(function($appointment) {
                return in_array($appointment->type, $this->selectedRepairType);
            });
        }

        if (!$this->allAppointmentTypeSelected && !empty($this->selectedAppointmentType)) {
            $resellerAppointments = $resellerAppointments->filter(function($appointment) {
                return in_array($appointment->appointment_type, $this->selectedAppointmentType);
            });
        }

        if (!$this->allStatusSelected && !empty($this->selectedStatus)) {
            $resellerAppointments = $resellerAppointments->filter(function($appointment) {
                return in_array(strtoupper($appointment->status), $this->selectedStatus);
            });
        }

        // Process and group by day
        foreach ($resellerAppointments as $appointment) {
            // Format appointment times
            $appointment->start_time = Carbon::parse($appointment->start_time)->format('g:i A');
            $appointment->end_time = Carbon::parse($appointment->end_time)->format('g:i A');
            $appointment->url = $appointment->lead_id
                ? route('filament.admin.resources.leads.view', ['record' => Encryptor::encrypt($appointment->lead_id)])
                : '#';

            // Get the day of the week for this appointment
            $dayOfWeek = strtolower(Carbon::parse($appointment->date)->format('l')); // e.g., 'monday'

            // Add to appropriate day array
            $appointments[$dayOfWeek][] = $appointment;
        }

        $this->resellerAppointments = $appointments;
    }

    public function render()
    {
        // Initialize repair counts
        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day) {
            $this->newRepairCount[$day]["noRepair"] = 0;
            $this->newRepairCount[$day]["oneRepair"] = 0;
            $this->newRepairCount[$day]["multipleRepair"] = 0;
        }

        // Load weekly appointments
        $this->rows = $this->getWeeklyAppointments($this->date);

        $this->getResellerAppointments();

        // Load date display
        $this->weekDays = $this->getWeekDateDays($this->date);

        // Get statistics
        $this->getNumberOfRepairs($this->selectedTechnicians);
        $this->calculateRepairBreakdown();

        // Get holidays and leaves
        $this->holidays = PublicHoliday::getPublicHoliday($this->startDate, $this->endDate);
        $selectedNames = $this->selectedTechnicians;

        // Get users matching selected names
        $matchedUsers = \App\Models\User::whereIn('name', $selectedNames)->get();

        $selectedNames = $this->selectedTechnicians;

        // Get internal users (only those that exist in the users table)
        $internalUsers = \App\Models\User::whereIn('name', $selectedNames)->get();

        $technicianIds = $internalUsers->pluck('id')->toArray();

        // Now fetch leaves only if any internal users were selected
        $this->leaves = [];

        if ($this->allTechniciansSelected || count($technicianIds) > 0) {
            $this->leaves = UserLeave::getTechnicianWeeklyLeavesByDateRange(
                $this->startDate,
                $this->endDate,
                $this->allTechniciansSelected ? null : $technicianIds
            );
        }

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
            'SITE SURVEY' => 0,
            'INTERNAL TECHNICIAN TASK' => 0,
        ];

        foreach ($appointments as $appointment) {
            $type = $appointment->type ?? 'Unknown';

            // Group internal tasks
            if (in_array($type, ['FINGERTEC TASK', 'TIMETEC HR TASK', 'TIMETEC PARKING TASK', 'TIMETEC PROPERTY TASK'])) {
                $result['INTERNAL TECHNICIAN TASK']++;
            } else {
                $result[$type] = ($result[$type] ?? 0) + 1;
            }
        }

        $this->repairBreakdown = $result;
    }
}
