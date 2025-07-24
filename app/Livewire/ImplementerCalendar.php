<?php

namespace App\Livewire;

use App\Classes\Encryptor;
use App\Models\ActivityLog;
use App\Models\PublicHoliday;
use App\Models\User;
use App\Models\UserLeave;
use App\Models\ImplementerAppointment;
use Carbon\Carbon;
use Filament\Notifications\Notification;
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
    public $showBookingModal = false;
    public $bookingDate;
    public $bookingSession;
    public $bookingStartTime;
    public $bookingEndTime;
    public $bookingImplementerId;
    public $selectedCompany;
    public $companies = [];

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

    public array $appointmentTypes = ["KICK OFF MEETING SESSION", "IMPLEMENTATION REVIEW SESSION", "DATA MIGRATION SESSION", "SYSTEM SETTING SESSION", "WEEKLY FOLLOW UP SESSION"];
    public array $selectedAppointmentType = [];
    public bool $allAppointmentTypeSelected = true;

    public array $sessionTypes = ["ONLINE", "ONSITE", "INHOUSE"];
    public array $selectedSessionType = [];
    public bool $allSessionTypeSelected = true;

    public $appointmentBreakdown = [];
    public $companySearch = '';
    public $filteredCompanies = [];
    public $appointmentType = 'ONLINE';
    public $requiredAttendees = '';
    public $remarks = '';

    public function mount()
    {
        // Load all implementers
        $this->implementers = $this->getAllImplementers();

        $this->companies = \App\Models\CompanyDetail::join('leads', 'company_details.lead_id', '=', 'leads.id')
            ->where('leads.lead_status', 'Closed')
            ->orderBy('company_details.company_name')
            ->pluck('company_details.company_name', 'company_details.id')
            ->toArray();
        $this->filteredCompanies = $this->companies;

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
            "KICK OFF MEETING SESSION" => 0,
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
        $this->totalAppointments["KICK OFF MEETING SESSION"] = $query->clone()->where('type', 'KICK OFF MEETING SESSION')
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
        $weekDays = $this->getWeekDateDays($date);

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
                'mondaySessionSlots' => $this->getSessionSlots('monday', $weekDays[0]['carbonDate'], $user->id ?? null),
                'tuesdaySessionSlots' => $this->getSessionSlots('tuesday', $weekDays[1]['carbonDate'], $user->id ?? null),
                'wednesdaySessionSlots' => $this->getSessionSlots('wednesday', $weekDays[2]['carbonDate'], $user->id ?? null),
                'thursdaySessionSlots' => $this->getSessionSlots('thursday', $weekDays[3]['carbonDate'], $user->id ?? null),
                'fridaySessionSlots' => $this->getSessionSlots('friday', $weekDays[4]['carbonDate'], $user->id ?? null),
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

                    // Mark this session as booked
                    // Find which session this appointment belongs to based on its start time
                    $appointmentStartTime = Carbon::parse($appointment->date . ' ' . $appointment->start_time)->format('H:i:s');
                    $daySessionSlots = "{$dayOfWeek}SessionSlots";

                    foreach ($data[$daySessionSlots] as $sessionName => $sessionInfo) {
                        if ($appointmentStartTime == $sessionInfo['start_time']) {
                            $data[$daySessionSlots][$sessionName]['booked'] = true;
                            $data[$daySessionSlots][$sessionName]['appointment'] = $appointment;

                            // Update the status based on the appointment type
                            if ($appointment->status === 'Cancelled') {
                                // For cancelled appointments, check if it should be shown based on time
                                $currentTime = Carbon::now();
                                $appointmentTime = Carbon::parse($appointment->date . ' ' . $appointmentStartTime);

                                if ($currentTime->format('Y-m-d') > Carbon::parse($appointment->date)->format('Y-m-d')) {
                                    // Past day - show as grey
                                    $data[$daySessionSlots][$sessionName]['status'] = 'past';
                                } else if ($appointmentTime < $currentTime) {
                                    // Same day but past time - show as grey
                                    $data[$daySessionSlots][$sessionName]['status'] = 'past';
                                } else {
                                    // Same day future time - show as available (green)
                                    $data[$daySessionSlots][$sessionName]['status'] = 'available';
                                }
                            } else if ($appointment->type === 'IMPLEMENTER REQUEST') {
                                // Yellow for implementer requests
                                $data[$daySessionSlots][$sessionName]['status'] = 'implementer_request';
                            } else {
                                // Red for implementation sessions
                                $data[$daySessionSlots][$sessionName]['status'] = 'implementation_session';
                            }

                            break;
                        }
                    }
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
            'KICK OFF MEETING SESSION' => 0,
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

    public function updatedCompanySearch()
    {
        if (empty($this->companySearch)) {
            $this->filteredCompanies = $this->companies;
        } else {
            // Filter companies based on search term
            $this->filteredCompanies = collect($this->companies)
                ->filter(function ($company, $id) {
                    return stripos($company, $this->companySearch) !== false;
                })
                ->toArray();
        }
    }

    public function bookSession($implementerId, $date, $session, $startTime, $endTime)
    {
        // Reset form fields
        $this->appointmentType = 'ONLINE';
        $this->requiredAttendees = '';
        $this->remarks = '';

        // Existing code
        $this->bookingImplementerId = $implementerId;
        $this->bookingDate = $date;
        $this->bookingSession = $session;
        $this->bookingStartTime = $startTime;
        $this->bookingEndTime = $endTime;
        $this->selectedCompany = null;

        // Show the booking modal
        $this->showBookingModal = true;
    }

    public function submitBooking()
    {
        $this->validate([
            'selectedCompany' => 'required|exists:company_details,id',
            'appointmentType' => 'required|in:ONLINE,ONSITE,INHOUSE',
            'requiredAttendees' => 'required|string',
        ]);

        // Get the company and lead data
        $companyDetail = \App\Models\CompanyDetail::find($this->selectedCompany);
        if (!$companyDetail) {
            Notification::make()
                ->title('Company not found')
                ->danger()
                ->send();
            return;
        }

        $leadId = $companyDetail->lead_id;
        if (!$leadId) {
            Notification::make()
                ->title('No lead associated with this company')
                ->danger()
                ->send();
            return;
        }

        // Get implementer name
        $implementer = User::find($this->bookingImplementerId);
        if (!$implementer) {
            Notification::make()
                ->title('Implementer not found')
                ->danger()
                ->send();
            return;
        }

        // Check if the slot is still available (another user might have booked it)
        $conflictingAppointment = \App\Models\ImplementerAppointment::where('implementer', $implementer->name)
            ->where('date', $this->bookingDate)
            ->where(function ($query) {
                $query->whereBetween('start_time', [$this->bookingStartTime, $this->bookingEndTime])
                    ->orWhereBetween('end_time', [$this->bookingStartTime, $this->bookingEndTime])
                    ->orWhere(function ($q) {
                        $q->where('start_time', '<=', $this->bookingStartTime)
                            ->where('end_time', '>=', $this->bookingEndTime);
                    });
            })
            ->first();

        if ($conflictingAppointment) {
            Notification::make()
                ->title('Session no longer available')
                ->danger()
                ->body('This slot has been booked by another user. Please select a different slot.')
                ->send();
            return;
        }

        // Determine the appropriate implementation type based on existing appointments
        $existingAppointmentsCount = \App\Models\ImplementerAppointment::where('lead_id', $leadId)
            ->where('status', '!=', 'Cancelled')
            ->count();

        $implementationType = 'KICK OFF MEETING SESSION';
        if ($existingAppointmentsCount == 1) {
            $implementationType = 'IMPLEMENTATION SESSION 1';
        } else if ($existingAppointmentsCount == 2) {
            $implementationType = 'IMPLEMENTATION SESSION 2';
        } else if ($existingAppointmentsCount == 3) {
            $implementationType = 'IMPLEMENTATION SESSION 3';
        } else if ($existingAppointmentsCount == 4) {
            $implementationType = 'IMPLEMENTATION SESSION 4';
        } else if ($existingAppointmentsCount >= 5) {
            $implementationType = 'IMPLEMENTATION SESSION 5';
        }

        // Create the appointment
        $appointment = new \App\Models\ImplementerAppointment();
        $appointment->fill([
            'lead_id' => $leadId,
            'type' => $implementationType,
            'appointment_type' => $this->appointmentType, // Use the selected appointment type
            'date' => $this->bookingDate,
            'start_time' => $this->bookingStartTime,
            'end_time' => $this->bookingEndTime,
            'implementer' => $implementer->name,
            'causer_id' => auth()->user()->id,
            'implementer_assigned_date' => now(),
            'title' => $implementationType . ' | ' . $this->appointmentType . ' | TIMETEC IMPLEMENTER | ' . $companyDetail->company_name,
            'status' => 'New',
            'session' => $this->bookingSession,
            'required_attendees' => $this->requiredAttendees, // Add attendees
            'remarks' => $this->remarks, // Add remarks
        ]);

        try {
            $appointment->save();

            $lead = \App\Models\Lead::find($leadId);
            if (!$lead) {
                Notification::make()
                    ->title('Error preparing email notification')
                    ->danger()
                    ->body('Lead record not found')
                    ->send();
                return;
            }

            $recipients = ['zilih.ng@timeteccloud.com']; // Always include admin

            // Parse and add required attendees if they have valid emails
            $attendeeEmails = array_map('trim', explode(';', $this->requiredAttendees));
            foreach ($attendeeEmails as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $recipients[] = $email;
                }
            }

            // Get lead owner details
            $leadowner = User::where('name', $lead->lead_owner)->first();

            // Prepare email content with correct data
            $viewName = 'emails.implementer_appointment_notification';
            $emailContent = [
                'leadOwnerName' => $lead->lead_owner ?? 'Unknown Manager',
                'lead' => [
                    'lastName' => $lead->companyDetail->name ?? $lead->name ?? 'Client',
                    'company' => $companyDetail->company_name ?? 'N/A',
                    'implementerName' => $implementer->name ?? 'N/A',
                    'phone' => optional($lead->companyDetail)->contact_no ?? $lead->phone ?? 'N/A',
                    'pic' => optional($lead->companyDetail)->name ?? $lead->name ?? 'N/A',
                    'email' => optional($lead->companyDetail)->email ?? $lead->email ?? 'N/A',
                    'date' => Carbon::parse($this->bookingDate)->format('d/m/Y') ?? 'N/A',
                    'startTime' => Carbon::parse($this->bookingStartTime)->format('h:i A') ?? 'N/A',
                    'endTime' => Carbon::parse($this->bookingEndTime)->format('h:i A') ?? 'N/A',
                    'leadOwnerMobileNumber' => $leadowner->mobile_number ?? 'N/A',
                    'session' => $this->bookingSession ?? 'N/A',
                    'demo_type' => $implementationType,
                    'appointment_type' => $this->appointmentType,
                    'remarks' => $this->remarks ?? 'N/A',
                ],
            ];

            // Get authenticated user's email for sender
            $authUser = auth()->user();
            $senderEmail = $authUser->email;
            $senderName = $authUser->name;

            try {
                // Send email with template and custom subject format
                if (count($recipients) > 0) {
                    \Illuminate\Support\Facades\Mail::send($viewName, ['content' => $emailContent], function ($message) use ($recipients, $senderEmail, $senderName, $lead, $implementationType, $companyDetail) {
                        $message->from($senderEmail, $senderName)
                            ->to($recipients)
                            ->subject("TIMETEC IMPLEMENTER APPOINTMENT | {$implementationType} | {$companyDetail->company_name} | " . Carbon::parse($this->bookingDate)->format('d/m/Y'));
                    });

                    Notification::make()
                        ->title('Implementer appointment notification sent')
                        ->success()
                        ->body('Email notification sent to administrator and required attendees')
                        ->send();
                }
            } catch (\Exception $e) {
                // Handle email sending failure
                Log::error("Email sending failed for implementer appointment: Error: {$e->getMessage()}");

                Notification::make()
                    ->title('Email Notification Failed')
                    ->danger()
                    ->body('Could not send email notification: ' . $e->getMessage())
                    ->send();
            }

            // Log activity
            ActivityLog::create([
                'user_id' => auth()->id(),
                'causer_id' => auth()->id(),
                'action' => 'Booked Implementer Session',
                'description' => "Booked {$this->bookingSession} ({$implementationType}) for {$companyDetail->company_name} with {$implementer->name}",
                'subject_type' => get_class($appointment),
                'subject_id' => $appointment->id,
            ]);

            Notification::make()
                ->title('Session booked successfully')
                ->success()
                ->send();

            // Close the modal
            $this->reset(['selectedCompany', 'appointmentType', 'requiredAttendees', 'remarks']);
            $this->showBookingModal = false;

            // Refresh the calendar
            $this->dispatch('refresh');

        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to book session')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function cancelBooking()
    {
        $this->showBookingModal = false;
    }

    private function getSessionSlots($dayOfWeek, $date = null, $implementerId = null)
    {
        // Define the standard session slots for Monday-Thursday
        $standardSessions = [
            'SESSION 1' => [
                'start_time' => '09:30:00',
                'end_time' => '10:30:00',
                'formatted_start' => '9:30 AM',
                'formatted_end' => '10:30 AM',
                'booked' => false,
                'appointment' => null,
                'status' => 'available', // Default status
                'time_period' => 'am' // AM session
            ],
            'SESSION 2' => [
                'start_time' => '11:00:00',
                'end_time' => '12:30:00',
                'formatted_start' => '11:00 AM',
                'formatted_end' => '12:30 PM',
                'booked' => false,
                'appointment' => null,
                'status' => 'available', // Default status
                'time_period' => 'am' // AM session
            ],
            'SESSION 3' => [
                'start_time' => '14:00:00',
                'end_time' => '15:00:00',
                'formatted_start' => '2:00 PM',
                'formatted_end' => '3:00 PM',
                'booked' => false,
                'appointment' => null,
                'status' => 'available', // Default status
                'time_period' => 'pm' // PM session
            ],
            'SESSION 4' => [
                'start_time' => '15:30:00',
                'end_time' => '16:30:00',
                'formatted_start' => '3:30 PM',
                'formatted_end' => '4:30 PM',
                'booked' => false,
                'appointment' => null,
                'status' => 'available', // Default status
                'time_period' => 'pm' // PM session
            ],
            'SESSION 5' => [
                'start_time' => '17:00:00',
                'end_time' => '18:00:00',
                'formatted_start' => '5:00 PM',
                'formatted_end' => '6:00 PM',
                'booked' => false,
                'appointment' => null,
                'status' => 'available', // Default status
                'time_period' => 'pm' // PM session
            ],
        ];

        // Friday has different schedule and no SESSION 3
        if (strtolower($dayOfWeek) === 'friday') {
            $standardSessions['SESSION 1'] = [
                'start_time' => '09:30:00',
                'end_time' => '10:30:00',
                'formatted_start' => '9:30 AM',
                'formatted_end' => '10:30 AM',
                'booked' => false,
                'appointment' => null,
                'status' => 'available', // Default status
                'time_period' => 'am' // AM session
            ];

            $standardSessions['SESSION 2'] = [
                'start_time' => '11:00:00',
                'end_time' => '12:30:00',
                'formatted_start' => '11:00 AM',
                'formatted_end' => '12:30 PM',
                'booked' => false,
                'appointment' => null,
                'status' => 'available', // Default status
                'time_period' => 'am' // AM session
            ];

            // Remove SESSION 3
            unset($standardSessions['SESSION 3']);

            // Update SESSION 4 and 5 times for Friday
            $standardSessions['SESSION 4'] = [
                'start_time' => '15:00:00',
                'end_time' => '16:00:00',
                'formatted_start' => '3:00 PM',
                'formatted_end' => '4:00 PM',
                'booked' => false,
                'appointment' => null,
                'status' => 'available', // Default status
                'time_period' => 'pm' // PM session
            ];

            $standardSessions['SESSION 5'] = [
                'start_time' => '16:30:00',
                'end_time' => '17:30:00',
                'formatted_start' => '4:30 PM',
                'formatted_end' => '5:30 PM',
                'booked' => false,
                'appointment' => null,
                'status' => 'available', // Default status
                'time_period' => 'pm' // PM session
            ];
        }

        // If a date is provided, we can check for public holidays and leaves
        if ($date && $implementerId) {
            $formattedDate = Carbon::parse($date)->format('Y-m-d');
            $currentTime = Carbon::now();

            // Check for public holidays (make session unavailable)
            $isPublicHoliday = PublicHoliday::where('date', $formattedDate)->exists();
            if ($isPublicHoliday) {
                // If it's a public holiday, all sessions are unavailable
                foreach ($standardSessions as $key => $session) {
                    $standardSessions[$key]['status'] = 'holiday';
                }
                return $standardSessions;
            }

            // Check for leave applications
            $user = User::find($implementerId);
            if ($user) {
                $leave = UserLeave::where('user_id', $implementerId)
                    ->where('date', $formattedDate)
                    ->where('status', 'Approved')
                    ->first();

                if ($leave) {
                    if ($leave->session === 'full') {
                        // Full day leave - all sessions unavailable
                        foreach ($standardSessions as $key => $session) {
                            $standardSessions[$key]['status'] = 'leave';
                        }
                    } elseif ($leave->session === 'am') {
                        // AM leave - Remove morning sessions entirely
                        foreach ($standardSessions as $key => $session) {
                            if ($session['time_period'] === 'am') {
                                unset($standardSessions[$key]);
                            }
                        }
                    } elseif ($leave->session === 'pm') {
                        // PM leave - Remove afternoon sessions entirely
                        foreach ($standardSessions as $key => $session) {
                            if ($session['time_period'] === 'pm') {
                                unset($standardSessions[$key]);
                            }
                        }
                    }
                }
            }

            // Check if the session is in the past (before current time)
            foreach ($standardSessions as $key => $session) {
                $sessionStart = Carbon::parse($formattedDate . ' ' . $session['start_time']);

                // If session is in the past, mark it as past
                if ($sessionStart < $currentTime) {
                    $standardSessions[$key]['status'] = 'past';
                }
            }

            // Process any cancelled appointments
            // This will mark the session as cancelled but still display it in the calendar
            if ($user) {
                $cancelledAppointments = \App\Models\ImplementerAppointment::where('implementer', $user->name)
                    ->where('date', $formattedDate)
                    ->where('status', 'Cancelled')
                    ->get();

                foreach ($cancelledAppointments as $appointment) {
                    $appointmentStartTime = Carbon::parse($appointment->start_time)->format('H:i:s');

                    foreach ($standardSessions as $key => $session) {
                        if ($session['start_time'] === $appointmentStartTime) {
                            // If the current time is after 12 AM of the next day
                            if (Carbon::now()->format('Y-m-d') > $formattedDate) {
                                $standardSessions[$key]['status'] = 'past';
                            } else {
                                $standardSessions[$key]['status'] = 'cancelled';
                                $standardSessions[$key]['appointment'] = $appointment;
                            }
                        }
                    }
                }
            }
        }

        return $standardSessions;
    }

    public function openAddAppointmentModal($params)
    {
        // This is a hook for the JavaScript to capture and process
        $this->dispatch('openAddAppointmentModal', $params);
    }
}
