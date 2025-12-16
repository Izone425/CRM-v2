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
    public $monthlyCalendar;
    public $selectedMonth;
    public $holidays;
    public $leaves;
    public $monthList;
    public $currentMonth;
    public $monthDate;
    public $newRepairCount;

    // Modal state
    public $selectedAppointment = null;
    public $showAppointmentModal = false;

    //Dropdown
    public $showDropdown = false;

    // Badge
    public $totalRepairs;

    // Dropdown filters
    public array $status = ["DONE", "NEW", "CANCELLED"];
    public array $selectedStatus = [];
    public bool $allStatusSelected = true;

    public $technicians;
    public array $selectedTechnicians = [];
    public bool $allTechniciansSelected = true;

    public array $repairTypes = ["NEW INSTALLATION", "REPAIR", "SITE SURVEY", "INTERNAL TECHNICIAN TASK"];
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

        // Set Date to current month
        $this->date = Carbon::now()->startOfMonth();

        // If current user is technician (role_id 9), filter to show only their appointments
        if (auth()->user()->role_id == 9) {
            $this->selectedTechnicians = [auth()->user()->name];
            $this->allTechniciansSelected = false;
        }
    }

    // Show individual appointment modal
    // public function showAppointment($appointmentId, $date)
    // {
    //     $appointment = $this->getAppointmentById($appointmentId, $date);
    //     if ($appointment) {
    //         $this->selectedAppointment = $appointment;
    //         $this->showAppointmentModal = true;
    //     }
    // }

    // Close appointment modal
    public function closeAppointmentModal()
    {
        $this->showAppointmentModal = false;
        $this->selectedAppointment = null;
    }

    // Get specific appointment by ID
    public function getAppointmentById($appointmentId, $date)
    {
        $appointment = DB::table('repair_appointments')
            ->leftJoin('leads', 'leads.id', '=', 'repair_appointments.lead_id')
            ->leftJoin('company_details', 'company_details.lead_id', '=', 'repair_appointments.lead_id')
            ->leftJoin('hardware_handovers_v2', 'hardware_handovers_v2.lead_id', '=', 'repair_appointments.lead_id')
            ->select(
                DB::raw('CASE
                    WHEN repair_appointments.lead_id IS NULL THEN "No Company"
                    ELSE COALESCE(company_details.company_name, "No Company")
                END as company_name'),
                'repair_appointments.*',
                // ✅ Fix the field mappings from company_details
                'company_details.name as pic_name',
                'company_details.contact_no as pic_phone',
                'company_details.email as pic_email',
                // ✅ Add hardware details from hardware_handovers_v2
                'hardware_handovers_v2.tc10_quantity',
                'hardware_handovers_v2.face_id5_quantity',
                'hardware_handovers_v2.tc20_quantity',
                'hardware_handovers_v2.face_id6_quantity',
                'hardware_handovers_v2.category2',
                'hardware_handovers_v2.handover_pdf'
            )
            ->where('repair_appointments.id', $appointmentId)
            ->where('repair_appointments.date', $date)
            ->first();

        if (!$appointment) {
            return null;
        }

        // Format appointment times
        $appointment->start_time = Carbon::parse($appointment->start_time)->format('g:i A');
        $appointment->end_time = Carbon::parse($appointment->end_time)->format('g:i A');
        $appointment->url = $appointment->lead_id
            ? route('filament.admin.resources.leads.view', ['record' => Encryptor::encrypt($appointment->lead_id)])
            : '#';

        // ✅ Add device data like the email template
        $appointment->devices = [
            'tc10' => $appointment->tc10_quantity ?? 0,
            'face_id5' => $appointment->face_id5_quantity ?? 0,
            'tc20' => $appointment->tc20_quantity ?? 0,
            'face_id6' => $appointment->face_id6_quantity ?? 0,
        ];

        // ✅ Check for appointment-specific data in category2
        if ($appointment->type === 'NEW INSTALLATION' && $appointment->category2) {
            $category2Data = json_decode($appointment->category2, true);
            if (isset($category2Data['installation_appointments'])) {
                foreach ($category2Data['installation_appointments'] as $instApp) {
                    if ($instApp['appointment_id'] == $appointmentId) {
                        // ✅ Override with appointment-specific details
                        if (isset($instApp['appointment_details'])) {
                            $details = $instApp['appointment_details'];
                            $appointment->pic_name = $details['pic_name'] ?? $appointment->pic_name;
                            $appointment->pic_phone = $details['pic_phone'] ?? $appointment->pic_phone;
                            $appointment->pic_email = $details['pic_email'] ?? $appointment->pic_email;
                            $appointment->installation_address = $details['installation_address'] ?? $appointment->installation_address;
                            $appointment->installation_remark = $details['installation_remark'] ?? '';
                        }

                        // ✅ Override with appointment-specific device allocation
                        if (isset($instApp['device_allocation'])) {
                            $allocation = $instApp['device_allocation'];
                            $appointment->devices = [
                                'tc10' => $allocation['tc10_units'] ?? 0,
                                'face_id5' => $allocation['face_id5_units'] ?? 0,
                                'tc20' => $allocation['tc20_units'] ?? 0,
                                'face_id6' => $allocation['face_id6_units'] ?? 0,
                            ];
                        }
                        break;
                    }
                }
            }
        }

        // ✅ Generate hardware ID like the email
        if ($appointment->handover_pdf) {
            $appointment->hardware_id = basename($appointment->handover_pdf, '.pdf');
        } else {
            $hardwareHandover = \App\Models\HardwareHandoverV2::where('lead_id', $appointment->lead_id)->first();
            if ($hardwareHandover) {
                $appointment->hardware_id = 'HW_250' . str_pad($hardwareHandover->id, 3, '0', STR_PAD_LEFT);
            }
        }

        // Set display type for internal tasks
        $displayType = $appointment->type;
        if (in_array($appointment->type, ['FINGERTEC TASK', 'TIMETEC HR TASK', 'TIMETEC PARKING TASK', 'TIMETEC PROPERTY TASK'])) {
            $displayType = 'INTERNAL TECHNICIAN TASK';
        }
        $appointment->display_type = $displayType;

        // Set technician phone with default fallback
        $appointment->technician_phone = $appointment->technician_phone ?? '017-380 4549';

        // Format appointment date
        $appointment->appointment_date = Carbon::parse($appointment->date)->format('d/m/Y');

        return $appointment;
    }

    // ✅ Update the getWeeklyAppointments method to include hardware data
    private function getWeeklyAppointments($date = null)
    {
        // Set weekly date range (Monday to Friday)
        $date = $date ? Carbon::parse($date) : Carbon::now();
        $this->startDate = $date->copy()->startOfWeek()->toDateString(); // Monday
        $this->endDate = $date->copy()->startOfWeek()->addDays(4)->toDateString(); // Friday

        // Retrieve repair appointments for the selected week with enhanced data
        $appointments = DB::table('repair_appointments')
            ->leftJoin('leads', 'leads.id', '=', 'repair_appointments.lead_id')
            ->leftJoin('company_details', 'company_details.lead_id', '=', 'repair_appointments.lead_id')
            ->leftJoin('hardware_handovers_v2', 'hardware_handovers_v2.lead_id', '=', 'repair_appointments.lead_id')
            ->select(
                DB::raw('CASE
                    WHEN repair_appointments.lead_id IS NULL THEN "No Company"
                    ELSE COALESCE(company_details.company_name, "No Company")
                END as company_name'),
                'repair_appointments.*',
                // ✅ Add customer details
                'company_details.name',
                'company_details.contact_no',
                'company_details.email',
                // ✅ Add hardware details
                'hardware_handovers_v2.tc10_quantity',
                'hardware_handovers_v2.face_id5_quantity',
                'hardware_handovers_v2.tc20_quantity',
                'hardware_handovers_v2.face_id6_quantity',
                'hardware_handovers_v2.category2',
                'hardware_handovers_v2.handover_pdf'
            )
            ->whereBetween("repair_appointments.date", [$this->startDate, $this->endDate])
            ->orderBy('repair_appointments.start_time', 'asc')
            ->when($this->selectedTechnicians, function ($query) {
                return $query->whereIn('repair_appointments.technician', $this->selectedTechnicians);
            })
            ->get();

        // Initialize result with only KI (Khairul Izzudin)
        $result = [];

        // ROW 1 - KI (Khairul Izzudin) - Only internal technician row
        $kiUser = User::where('name', 'Khairul Izzuddin')->first();
        if ($kiUser) {
            $kiAppointments = $appointments->where('technician', 'Khairul Izzuddin');
            $result[] = $this->buildTechnicianRow($kiUser, $kiAppointments, 'KI');
        } else {
            // Fallback if KI not found
            $result[] = $this->buildDefaultRow('KI', 'Khairul Izzuddin', collect());
        }

        return $result;
    }

    // ✅ Update buildTechnicianRow to handle enhanced appointment data
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

            // ✅ Add device data and other details
            $appointment->devices = [
                'tc10' => $appointment->tc10_quantity ?? 0,
                'face_id5' => $appointment->face_id5_quantity ?? 0,
                'tc20' => $appointment->tc20_quantity ?? 0,
                'face_id6' => $appointment->face_id6_quantity ?? 0,
            ];

            // ✅ Process category2 data for installation appointments
            if ($appointment->type === 'NEW INSTALLATION' && $appointment->category2) {
                $category2Data = json_decode($appointment->category2, true);
                if (isset($category2Data['installation_appointments'])) {
                    foreach ($category2Data['installation_appointments'] as $instApp) {
                        if ($instApp['appointment_id'] == $appointment->id) {
                            if (isset($instApp['appointment_details'])) {
                                $details = $instApp['appointment_details'];
                                $appointment->pic_name = $details['pic_name'] ?? $appointment->pic_name;
                                $appointment->pic_phone = $details['pic_phone'] ?? $appointment->pic_phone;
                                $appointment->pic_email = $details['pic_email'] ?? $appointment->pic_email;
                                $appointment->installation_address = $details['installation_address'] ?? $appointment->installation_address;
                                $appointment->installation_remark = $details['installation_remark'] ?? '';
                            }

                            if (isset($instApp['device_allocation'])) {
                                $allocation = $instApp['device_allocation'];
                                $appointment->devices = [
                                    'tc10' => $allocation['tc10_units'] ?? 0,
                                    'face_id5' => $allocation['face_id5_units'] ?? 0,
                                    'tc20' => $allocation['tc20_units'] ?? 0,
                                    'face_id6' => $allocation['face_id6_units'] ?? 0,
                                ];
                            }
                            break;
                        }
                    }
                }
            }

            // ✅ Generate hardware ID if available
            if ($appointment->handover_pdf) {
                $appointment->hardware_id = basename($appointment->handover_pdf, '.pdf');
            } else if ($appointment->lead_id) {
                $hardwareHandover = \App\Models\HardwareHandoverV2::where('lead_id', $appointment->lead_id)->first();
                if ($hardwareHandover) {
                    $appointment->hardware_id = 'HW_250' . str_pad($hardwareHandover->id, 3, '0', STR_PAD_LEFT);
                }
            }

            // Set technician phone with default fallback
            $appointment->technician_phone = $appointment->technician_phone ?? '017-380 4549';

            // Format appointment date
            $appointment->appointment_date = Carbon::parse($appointment->date)->format('d/m/Y');

            // Apply filters and add to appropriate day
            if ($this->passesFilters($appointment)) {
                $data[$dayField][] = $appointment;
            }
        }

        return $data;
    }

    // Monthly navigation methods
    public function prevMonth()
    {
        $this->date->subMonth();
    }

    public function nextMonth()
    {
        $this->date->addMonth();
    }

    // Generate monthly calendar grid (WEEKDAYS ONLY)
    private function generateMonthlyCalendar($date = null)
    {
        $date = $date ? Carbon::parse($date) : $this->date;
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        // Find first Monday of the month or the Monday before if month doesn't start on Monday
        $calendarStart = $startOfMonth->copy();
        while ($calendarStart->dayOfWeek !== Carbon::MONDAY) {
            $calendarStart->subDay();
        }

        // Find last Friday of the month or the Friday after if month doesn't end on Friday
        $calendarEnd = $endOfMonth->copy();
        while ($calendarEnd->dayOfWeek !== Carbon::FRIDAY) {
            $calendarEnd->addDay();
        }

        // Set date range for appointments
        $this->startDate = $calendarStart->toDateString();
        $this->endDate = $calendarEnd->toDateString();

        // Get appointments for the entire calendar range
        $appointments = $this->getMonthlyAppointments();

        $calendar = [];
        $currentDay = $calendarStart->copy();

        // Generate weeks with only weekdays (Monday to Friday)
        while ($currentDay <= $calendarEnd) {
            $weekData = [];

            // Only include Monday to Friday (1-5)
            for ($dayOfWeek = Carbon::MONDAY; $dayOfWeek <= Carbon::FRIDAY; $dayOfWeek++) {
                if ($currentDay->dayOfWeek === $dayOfWeek) {
                    $dayKey = $currentDay->toDateString();

                    $dayData = [
                        'date' => $dayKey,
                        'day' => $currentDay->day,
                        'dayName' => $currentDay->format('D'), // Mon, Tue, etc.
                        'isCurrentMonth' => $currentDay->month === $date->month,
                        'isToday' => $currentDay->isToday(),
                        'appointments' => $appointments[$dayKey] ?? [],
                        'carbonDate' => $currentDay->copy(),
                    ];

                    $weekData[] = $dayData;
                }

                if ($currentDay->dayOfWeek === $dayOfWeek) {
                    $currentDay->addDay();
                }
            }

            if (!empty($weekData)) {
                $calendar[] = $weekData;
            }

            // Skip to next Monday if we're at Friday
            if ($currentDay->dayOfWeek === Carbon::SATURDAY) {
                $currentDay->addDays(2); // Skip Saturday and Sunday
            }
        }

        return $calendar;
    }

    // Get appointments grouped by date
    private function getMonthlyAppointments()
    {
        $query = DB::table('repair_appointments')
            ->leftJoin('leads', 'leads.id', '=', 'repair_appointments.lead_id')
            ->leftJoin('company_details', 'company_details.lead_id', '=', 'repair_appointments.lead_id')
            ->leftJoin('hardware_handovers_v2', 'hardware_handovers_v2.lead_id', '=', 'repair_appointments.lead_id')
            ->select(
                DB::raw('CASE
                    WHEN repair_appointments.lead_id IS NULL THEN "No Company"
                    ELSE COALESCE(company_details.company_name, "No Company")
                END as company_name'),
                'repair_appointments.*',
                // ✅ Add the missing PIC fields
                'company_details.name',
                'company_details.contact_no',
                'company_details.email',
                // ✅ Add hardware details
                'hardware_handovers_v2.tc10_quantity',
                'hardware_handovers_v2.face_id5_quantity',
                'hardware_handovers_v2.tc20_quantity',
                'hardware_handovers_v2.face_id6_quantity',
                'hardware_handovers_v2.category2',
                'hardware_handovers_v2.handover_pdf'
            )
            ->whereBetween("repair_appointments.date", [$this->startDate, $this->endDate])
            ->orderBy('repair_appointments.start_time', 'asc');

        // Apply technician filter
        if (!$this->allTechniciansSelected && !empty($this->selectedTechnicians)) {
            $query->whereIn('repair_appointments.technician', $this->selectedTechnicians);
        }

        $appointments = $query->get();

        // Apply filters and enrich appointment data
        $filteredAppointments = $appointments->filter(function($appointment) {
            return $this->passesFilters($appointment);
        });

        // Group by date and enrich with enhanced data
        $groupedAppointments = [];
        foreach ($filteredAppointments as $appointment) {
            $dateKey = Carbon::parse($appointment->date)->toDateString();

            // Format appointment times
            $appointment->start_time = Carbon::parse($appointment->start_time)->format('g:i A');
            $appointment->end_time = Carbon::parse($appointment->end_time)->format('g:i A');
            $appointment->url = $appointment->lead_id
                ? route('filament.admin.resources.leads.view', ['record' => Encryptor::encrypt($appointment->lead_id)])
                : '#';

            // ✅ Add device data like the email template
            $appointment->devices = [
                'tc10' => $appointment->tc10_quantity ?? 0,
                'face_id5' => $appointment->face_id5_quantity ?? 0,
                'tc20' => $appointment->tc20_quantity ?? 0,
                'face_id6' => $appointment->face_id6_quantity ?? 0,
            ];

            // ✅ Process category2 data for installation appointments
            if ($appointment->type === 'NEW INSTALLATION' && $appointment->category2) {
                $category2Data = json_decode($appointment->category2, true);
                if (isset($category2Data['installation_appointments'])) {
                    foreach ($category2Data['installation_appointments'] as $instApp) {
                        if ($instApp['appointment_id'] == $appointment->id) {
                            if (isset($instApp['appointment_details'])) {
                                $details = $instApp['appointment_details'];
                                $appointment->pic_name = $details['pic_name'] ?? $appointment->pic_name;
                                $appointment->pic_phone = $details['pic_phone'] ?? $appointment->pic_phone;
                                $appointment->pic_email = $details['pic_email'] ?? $appointment->pic_email;
                                $appointment->installation_address = $details['installation_address'] ?? $appointment->installation_address;
                                $appointment->installation_remark = $details['installation_remark'] ?? '';
                            }

                            if (isset($instApp['device_allocation'])) {
                                $allocation = $instApp['device_allocation'];
                                $appointment->devices = [
                                    'tc10' => $allocation['tc10_units'] ?? 0,
                                    'face_id5' => $allocation['face_id5_units'] ?? 0,
                                    'tc20' => $allocation['tc20_units'] ?? 0,
                                    'face_id6' => $allocation['face_id6_units'] ?? 0,
                                ];
                            }
                            break;
                        }
                    }
                }
            }

            // ✅ Generate hardware ID if available
            if ($appointment->handover_pdf) {
                $appointment->hardware_id = basename($appointment->handover_pdf, '.pdf');
            } else if ($appointment->lead_id) {
                $hardwareHandover = \App\Models\HardwareHandoverV2::where('lead_id', $appointment->lead_id)->first();
                if ($hardwareHandover) {
                    $appointment->hardware_id = 'HW_250' . str_pad($hardwareHandover->id, 3, '0', STR_PAD_LEFT);
                }
            }

            // Set display type for internal tasks
            $displayType = $appointment->type;
            if (in_array($appointment->type, ['FINGERTEC TASK', 'TIMETEC HR TASK', 'TIMETEC PARKING TASK', 'TIMETEC PROPERTY TASK'])) {
                $displayType = 'INTERNAL TECHNICIAN TASK';
            }
            $appointment->display_type = $displayType;

            // Set technician phone with default fallback
            $appointment->technician_phone = $appointment->technician_phone ?? '017-380 4549';

            // Format appointment date
            $appointment->appointment_date = Carbon::parse($appointment->date)->format('d/m/Y');

            if (!isset($groupedAppointments[$dateKey])) {
                $groupedAppointments[$dateKey] = [];
            }

            $groupedAppointments[$dateKey][] = $appointment;
        }

        return $groupedAppointments;
    }

    // ✅ Add a property to store selected appointment for modal
    public $modalAppointment = null;

    // ✅ Update the showAppointment method to use Livewire properties
    public function showAppointment($appointmentId, $date)
    {
        $this->modalAppointment = $this->getAppointmentById($appointmentId, $date);
        $this->showAppointmentModal = true;
        $this->dispatch('appointment-loaded');
    }

    // Filter methods
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
        if ($this->allTechniciansSelected == true) {
            $this->selectedTechnicians = [];
        }
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
        if ($this->allStatusSelected == true) {
            $this->selectedStatus = [];
        }
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
        if ($this->allRepairTypeSelected == true) {
            $this->selectedRepairType = [];
        }
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
        if ($this->allAppointmentTypeSelected == true) {
            $this->selectedAppointmentType = [];
        }
    }

    private function passesFilters($appointment)
    {
        // Map internal task types
        $displayType = $appointment->type;
        if (in_array($appointment->type, ['FINGERTEC TASK', 'TIMETEC HR TASK', 'TIMETEC PARKING TASK', 'TIMETEC PROPERTY TASK'])) {
            $displayType = 'INTERNAL TECHNICIAN TASK';
            $appointment->is_internal_task = true;
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
            "NEW" => 0,
            "DONE" => 0,
            "CANCELLED" => 0,
            "NEW INSTALLATION" => 0,
            "REPAIR" => 0,
            "SITE SURVEY" => 0,
            "INTERNAL TECHNICIAN TASK" => 0,
        ];

        // Count all appointments
        $this->totalRepairs["ALL"] = $query->clone()->where('status', '!=', 'Cancelled')->count();

        // Count by status
        $this->totalRepairs["NEW"] = $query->clone()->where('status', 'New')->count();
        $this->totalRepairs["DONE"] = $query->clone()->where('status', 'Done')->count();
        $this->totalRepairs["CANCELLED"] = $query->clone()->where('status', 'Cancelled')->count();

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

    public function render()
    {
        // Generate monthly calendar
        $this->monthlyCalendar = $this->generateMonthlyCalendar($this->date);

        // Get statistics
        $this->getNumberOfRepairs($this->selectedTechnicians);
        $this->calculateRepairBreakdown();

        // Get holidays for the month
        $this->holidays = PublicHoliday::getPublicHoliday($this->startDate, $this->endDate);

        // Set current month display
        $this->currentMonth = $this->date->format('F Y');

        return view('livewire.technician-calendar');
    }
}
