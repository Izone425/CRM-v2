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
    public array $status = ["DONE", "NEW", "CANCELLED"];
    public array $selectedStatus = [];
    public bool $allStatusSelected = true;

    public $implementers;
    public array $selectedImplementers = [];
    public bool $allImplementersSelected = true;

    public array $appointmentTypes = [
        "KICK OFF MEETING SESSION",
        "REVIEW SESSION",
        "DATA MIGRATION SESSION",
        "SYSTEM SETTING SESSION",
        "WEEKLY FOLLOW UP SESSION"
    ];
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

    public $showImplementerRequestModal = false;
    public $showImplementationSessionModal = false;
    public $requestSessionType = '';
    public $selectedYear;
    public $selectedWeek;
    public $availableYears = [];
    public $availableWeeks = [];
    public $implementationDemoType = 'REVIEW SESSION';
    public $filteredOpenDelayCompanies = [];
    public $showAppointmentDetailsModal = false;
    public $currentAppointment = null;
    public $isLoadingAttendees = false;

    public $hasKickOffMeeting = false;

    public $showOnsiteRequestModal = false;
    public $onsiteDayType = '';
    public $onsiteCategory = '';
    public $onsiteRemarks = '';
    public $selectedOnsiteSessions = [];
    public $implementerCompanies = [];
    public $skipEmailAndTeams = false;

    public $showCancellationModal = false;
    public $implementer_remark = '';
    public $appointmentToCancel = null;

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

    public function showAppointmentDetails($appointmentId)
    {
        if (!$appointmentId) {
            return;
        }

        $this->currentAppointment = \App\Models\ImplementerAppointment::find($appointmentId);

        if ($this->currentAppointment) {
            // Get company name if not already set
            if (!$this->currentAppointment->company_name && $this->currentAppointment->lead_id) {
                $companyDetail = \App\Models\CompanyDetail::where('lead_id', $this->currentAppointment->lead_id)->first();
                if ($companyDetail) {
                    $this->currentAppointment->company_name = $companyDetail->company_name;
                }
            }

            $this->showAppointmentDetailsModal = true;
        } else {
            Notification::make()
                ->title('Appointment not found')
                ->danger()
                ->send();
        }
    }

    public function closeAppointmentDetails()
    {
        $this->showAppointmentDetailsModal = false;
        $this->currentAppointment = null;
    }

    public function cancelAppointment($appointmentId)
    {
        $this->appointmentToCancel = \App\Models\ImplementerAppointment::find($appointmentId);

        if (!$this->appointmentToCancel) {
            Notification::make()
                ->title('Appointment not found')
                ->danger()
                ->send();
            return;
        }

        $this->implementer_remark = '';
        $this->showCancellationModal = true;
    }

    public function confirmCancelAppointment()
    {
        $this->validate([
            'implementer_remark' => 'required|string|min:5|max:500',
        ], [
            'implementer_remark.required' => 'Please provide a reason for cancellation',
            'implementer_remark.min' => 'Cancellation reason must be at least 5 characters',
            'implementer_remark.max' => 'Cancellation reason cannot exceed 500 characters',
        ]);

        if (!$this->appointmentToCancel) {
            Notification::make()
                ->title('Appointment not found')
                ->danger()
                ->send();
            return;
        }

        try {
            // Update status to Cancelled and add implementer remark
            $this->appointmentToCancel->status = 'Cancelled';
            $this->appointmentToCancel->request_status = 'CANCELLED';
            $this->appointmentToCancel->implementer_remark = $this->implementer_remark;
            $this->appointmentToCancel->cancelled_by = auth()->user()->id;
            $this->appointmentToCancel->cancelled_at = now();

            // Cancel Teams meeting if exists
            if ($this->appointmentToCancel->event_id) {
                $eventId = $this->appointmentToCancel->event_id;

                // Get implementer's email instead of using organizer_email
                $implementer = User::where('name', $this->appointmentToCancel->implementer)->first();

                if ($implementer && $implementer->email) {
                    $implementerEmail = $implementer->email;

                    try {
                        $accessToken = \App\Services\MicrosoftGraphService::getAccessToken();
                        $graph = new \Microsoft\Graph\Graph();
                        $graph->setAccessToken($accessToken);

                        // Cancel the Teams meeting using implementer's email
                        $graph->createRequest("DELETE", "/users/$implementerEmail/events/$eventId")->execute();

                        Notification::make()
                            ->title('Teams Meeting Cancelled Successfully')
                            ->warning()
                            ->body('The meeting has been cancelled in Microsoft Teams.')
                            ->send();

                    } catch (\Exception $e) {
                        Log::error('Failed to cancel Teams meeting: ' . $e->getMessage(), [
                            'event_id' => $eventId,
                            'implementer' => $implementerEmail,
                            'trace' => $e->getTraceAsString()
                        ]);

                        Notification::make()
                            ->title('Failed to Cancel Teams Meeting')
                            ->warning()
                            ->body('The appointment was cancelled, but there was an error cancelling the Teams meeting: ' . $e->getMessage())
                            ->send();
                    }
                } else {
                    Log::error('Failed to cancel Teams meeting: Implementer email not found', [
                        'event_id' => $eventId,
                        'implementer_name' => $this->appointmentToCancel->implementer
                    ]);

                    Notification::make()
                        ->title('Failed to Cancel Teams Meeting')
                        ->warning()
                        ->body('The appointment was cancelled, but the implementer email was not found.')
                        ->send();
                }
            }

            if ($this->appointmentToCancel->lead_id) {
                try {
                    // Find the customer associated with this lead
                    $customer = \App\Models\Customer::where('lead_id', $this->appointmentToCancel->lead_id)->first();

                    if ($customer) {
                        // Update customer's able_set_meeting to true
                        $customer->update(['able_set_meeting' => true]);

                        Log::info('Customer able_set_meeting enabled after implementer cancellation', [
                            'customer_id' => $customer->id,
                            'customer_email' => $customer->email,
                            'appointment_id' => $this->appointmentToCancel->id,
                            'company_name' => $customer->company_name,
                            'cancelled_by' => auth()->user()->name,
                            'cancellation_type' => 'implementer_cancellation'
                        ]);
                    } else {
                        Log::warning('Customer not found for lead_id during implementer cancellation', [
                            'lead_id' => $this->appointmentToCancel->lead_id,
                            'appointment_id' => $this->appointmentToCancel->id
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to update customer able_set_meeting during implementer cancellation: ' . $e->getMessage(), [
                        'appointment_id' => $this->appointmentToCancel->id,
                        'lead_id' => $this->appointmentToCancel->lead_id,
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            $this->appointmentToCancel->save();

            // Send email notification about cancellation
            $this->sendCancellationEmail($this->appointmentToCancel);

            Notification::make()
                ->title('Appointment cancelled successfully')
                ->success()
                ->body('Cancellation reason has been recorded')
                ->send();

            // Close modals and refresh calendar
            $this->closeCancellationModal();
            $this->closeAppointmentDetails();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error cancelling appointment')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function closeCancellationModal()
    {
        $this->showCancellationModal = false;
        $this->implementer_remark = '';
        $this->appointmentToCancel = null;
    }

    private function sendCancellationEmail($appointment)
    {
        try {
            $companyDetail = null;
            if ($appointment->lead_id) {
                $companyDetail = \App\Models\CompanyDetail::where('lead_id', $appointment->lead_id)->first();
            }

            $companyName = $companyDetail ? $companyDetail->company_name :
                ($appointment->title ?: 'Unknown Company');

            $recipients = [];

            // Add attendees from the appointment
            if ($appointment->required_attendees) {
                $attendeeEmails = array_map('trim', explode(';', $appointment->required_attendees));
                foreach ($attendeeEmails as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $recipients[] = $email;
                    }
                }
            }

            // Get authenticated user's email for sender
            $authUser = auth()->user();
            $senderEmail = $authUser->email;
            $senderName = $authUser->name;

            // Prepare email data
            $emailData = [
                'appointmentType' => $appointment->type,
                'companyName' => $companyName,
                'date' => Carbon::parse($appointment->date)->format('d F Y'),
                'time' => Carbon::parse($appointment->start_time)->format('g:i A') . ' - ' .
                        Carbon::parse($appointment->end_time)->format('g:i A'),
                'implementer' => $appointment->implementer,
            ];

            if (count($recipients) > 0) {
                \Illuminate\Support\Facades\Mail::send(
                    'emails.implementer_appointment_cancelled',
                    ['content' => $emailData],
                    function ($message) use ($recipients, $senderEmail, $senderName, $companyName, $appointment) {
                        $message->from($senderEmail, $senderName)
                            ->to($recipients)
                            ->cc($senderEmail)
                            ->subject("CANCELLED: TIMETEC IMPLEMENTER APPOINTMENT | {$appointment->type} | {$companyName}");
                    }
                );
            }
        } catch (\Exception $e) {
            Log::error("Email sending failed for cancelled implementer appointment: Error: {$e->getMessage()}");
        }
    }

    // Update date variable when user choose another date
    public function updatedWeekDate()
    {
        $this->date = Carbon::parse($this->weekDate);
    }

    public function updatedSelectedCompany($value)
    {
        if (!empty($value)) {
            // Load attendees if needed
            $this->loadAttendees();

            // Check if company already has a kick off meeting session
            $companyDetail = \App\Models\CompanyDetail::find($value);
            if ($companyDetail && $companyDetail->lead_id) {
                $hasKickOffMeeting = \App\Models\ImplementerAppointment::where('lead_id', $companyDetail->lead_id)
                    ->where('type', 'KICK OFF MEETING SESSION')
                    ->where('status', '!=', 'Cancelled')
                    ->exists();

                if ($hasKickOffMeeting) {
                    // If company already had a kick off meeting, restrict to implementation review only
                    $this->implementationDemoType = "REVIEW SESSION";
                    $this->hasKickOffMeeting = true;
                } else {
                    // If no kick off meeting yet, default to kick off meeting
                    $this->implementationDemoType = "KICK OFF MEETING SESSION";
                    $this->hasKickOffMeeting = false;
                }
            }
        }
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

        // Get all appointments
        $allAppointments = $query->get();

        // Initialize counters
        $this->totalAppointments = [
            "ALL" => 0,
            "Kick Off Meeting Session" => 0,
            "REVIEW SESSION" => 0,
            "Data Migration Session" => 0,
            "System Setting Session" => 0,
            "Weekly Follow Up Session" => 0
        ];

        // Count active appointments (not cancelled)
        $activeAppointments = $allAppointments->where('status', '!=', 'Cancelled');
        $this->totalAppointments["ALL"] = $activeAppointments->count();

        // Count by appointment type
        foreach ($activeAppointments as $appointment) {
            $type = $appointment->type;
            switch ($type) {
                case 'KICK OFF MEETING SESSION':
                    $this->totalAppointments["Kick Off Meeting Session"]++;
                    break;
                case 'REVIEW SESSION':
                    $this->totalAppointments["REVIEW SESSION"]++;
                    break;
                case 'DATA MIGRATION SESSION':
                    $this->totalAppointments["Data Migration Session"]++;
                    break;
                case 'SYSTEM SETTING SESSION':
                    $this->totalAppointments["System Setting Session"]++;
                    break;
                case 'WEEKLY FOLLOW UP SESSION':
                    $this->totalAppointments["Weekly Follow Up Session"]++;
                    break;
            }
        }

        // Handle status counts with special logic for cancelled appointments
        $this->totalAppointmentsStatus["NEW"] = $allAppointments->where('status', 'New')->count();
        $this->totalAppointmentsStatus["DONE"] = $allAppointments->where('status', 'Done')->count();

        // For cancelled appointments, only count those that haven't been replaced
        $cancelledAppointments = $allAppointments->where('status', 'Cancelled');
        $actualCancelledCount = 0;

        foreach ($cancelledAppointments as $cancelled) {
            // Check if this cancelled appointment's time slot has been replaced
            $isReplaced = $allAppointments->where('implementer', $cancelled->implementer)
                ->where('date', $cancelled->date)
                ->where('start_time', $cancelled->start_time)
                ->whereIn('status', ['New', 'Done'])
                ->count() > 0;

            // Only count as cancelled if the slot hasn't been replaced
            if (!$isReplaced) {
                $actualCancelledCount++;
            }
        }

        $this->totalAppointmentsStatus["CANCELLED"] = $actualCancelledCount;

        // Update total to include actual cancelled count
        $this->totalAppointmentsStatus["ALL"] = $this->totalAppointmentsStatus["NEW"] +
                                            $this->totalAppointmentsStatus["DONE"] +
                                            $this->totalAppointmentsStatus["CANCELLED"];
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
        $date = $date ? Carbon::parse($date) : Carbon::now();
        $this->startDate = $date->copy()->startOfWeek()->toDateString(); // Monday
        $this->endDate = $date->copy()->startOfWeek()->addDays(4)->toDateString(); // Friday

        // Define the custom order for implementers
        $customOrder = [
            'Ahmad Syamim' => 1,
            'Ahmad Syazwan' => 2,
            'Siti Shahilah' => 3,
            'Muhamad Izzul Aiman' => 4,
            'Zulhilmie' => 5,
            'Mohd Amirul Ashraf' => 6,
            'John Low' => 7,
            'Nur Alia' => 8,
            'Nur Fazuliana' => 9,
            'Ummu Najwa Fajrina' => 10,
            'Noor Syazana' => 11
        ];

        // Get implementers data
        $implementerUsers = User::whereIn('role_id', [4, 5])
            ->select('id', 'name', 'avatar_path')
            ->get()
            ->keyBy('name')
            ->toArray();

        // Retrieve implementer appointments for the selected week
        // Using leftJoin instead of join to include records without lead_id
        $appointments = DB::table('implementer_appointments')
            ->leftJoin('leads', 'leads.id', '=', 'implementer_appointments.lead_id')
            ->leftJoin('company_details', 'company_details.lead_id', '=', 'implementer_appointments.lead_id')
            ->select(
                'implementer_appointments.*',
                'company_details.company_name',
                DB::raw('CASE WHEN implementer_appointments.lead_id IS NULL AND implementer_appointments.title IS NOT NULL THEN implementer_appointments.title ELSE company_details.company_name END as display_name')
            )
            ->whereBetween("date", [$this->startDate, $this->endDate])
            ->orderBy('start_time', 'asc')
            ->when($this->selectedImplementers, function ($query) {
                return $query->whereIn('implementer', $this->selectedImplementers);
            })
            ->get();


        // Map company names for display
        $appointments = $appointments->map(function($appointment) {
            // For appointments without lead_id, extract company name from title
            if (!$appointment->lead_id && $appointment->title) {
                // For Weekly Follow Up Sessions with week and year
                if (strpos($appointment->title, 'WEEK') !== false) {
                    $appointment->company_name = $appointment->title;
                }
                // For other types, try to extract company name from title
                else if (strpos($appointment->title, '|') !== false) {
                    $parts = explode('|', $appointment->title);
                    $appointment->company_name = trim(end($parts));
                } else {
                    $appointment->company_name = 'No company specified';
                }
            } else if (!$appointment->company_name) {
                $appointment->company_name = 'No company specified';
            }
            return $appointment;
        });

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

        // Create a collection for sorting
        $implementerCollection = collect();

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
                'order' => $customOrder[$implementerName] ?? 999, // Custom order for sorting
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

                // Set URL only if there's a lead_id
                if ($appointment->lead_id) {
                    $appointment->url = route('filament.admin.resources.leads.view', ['record' => Encryptor::encrypt($appointment->lead_id)]);
                } else {
                    $appointment->url = null; // No URL for appointments without lead_id
                }

                // Apply filters
                $includeAppointmentType = $this->allAppointmentTypeSelected ||
                                        in_array($appointment->type, $this->selectedAppointmentType);

                $includeSessionType = $this->allSessionTypeSelected ||
                                    in_array($appointment->appointment_type, $this->selectedSessionType);

                $includeStatus = $this->allStatusSelected ||
                                in_array(strtoupper($appointment->status), $this->selectedStatus);

                // *** ADD THIS CHECK: Skip cancelled appointments in the regular appointments list ***
                // Only add cancelled appointments to the appointments array if they don't match a session slot
                $isCancelled = $appointment->status === 'Cancelled';
                $matchesSessionSlot = false;

                // Check if this cancelled appointment matches any session slot
                if ($isCancelled) {
                    $appointmentStartTime = Carbon::parse($appointment->date . ' ' . $appointment->start_time)->format('H:i:s');
                    $daySessionSlots = "{$dayOfWeek}SessionSlots";

                    foreach ($data[$daySessionSlots] as $sessionName => $sessionInfo) {
                        if ($appointmentStartTime == $sessionInfo['start_time']) {
                            $matchesSessionSlot = true;
                            break;
                        }
                    }

                    // If it matches a session slot, don't add it to the appointments list
                    if ($matchesSessionSlot) {
                        continue;
                    }
                }

                if ($includeAppointmentType && $includeSessionType && $includeStatus) {
                    $data[$dayField][] = $appointment;

                    // Mark this session as booked
                    // Find which session this appointment belongs to based on its start time
                    $appointmentStartTime = Carbon::parse($appointment->date . ' ' . $appointment->start_time)->format('H:i:s');
                    $daySessionSlots = "{$dayOfWeek}SessionSlots";

                    foreach ($data[$daySessionSlots] as $sessionName => $sessionInfo) {
                        if ($appointmentStartTime == $sessionInfo['start_time']) {
                            // Add this crucial check for cancelled appointments:
                            if ($appointment->status === 'Cancelled') {
                                $currentTime = Carbon::now();
                                $appointmentTime = Carbon::parse($appointment->date . ' ' . $appointmentStartTime);

                                if ($currentTime->format('Y-m-d') > Carbon::parse($appointment->date)->format('Y-m-d')
                                    || $appointmentTime < $currentTime) {
                                    // Past cancelled appointment - show as past
                                    $data[$daySessionSlots][$sessionName]['status'] = 'past';
                                    $data[$daySessionSlots][$sessionName]['booked'] = false;
                                    $data[$daySessionSlots][$sessionName]['appointment'] = null;
                                } else {
                                    // Future cancelled appointment - show as available
                                    $data[$daySessionSlots][$sessionName]['status'] = 'available';
                                    $data[$daySessionSlots][$sessionName]['booked'] = false;
                                    $data[$daySessionSlots][$sessionName]['appointment'] = null;
                                    $data[$daySessionSlots][$sessionName]['wasCancelled'] = true; // Add this flag
                                }
                            } else {
                                $data[$daySessionSlots][$sessionName]['booked'] = true;
                                $data[$daySessionSlots][$sessionName]['appointment'] = $appointment;

                                // Update the status based on the appointment type
                                if ($appointment->request_status === 'PENDING') {
                                    // Yellow for pending implementer requests
                                    $data[$daySessionSlots][$sessionName]['status'] = 'implementer_request';
                                } else if (in_array($appointment->type, ['DATA MIGRATION SESSION', 'SYSTEM SETTING SESSION', 'WEEKLY FOLLOW UP SESSION'])) {
                                    // Yellow for these specific session types
                                    $data[$daySessionSlots][$sessionName]['status'] = 'implementer_request';
                                } else if (in_array($appointment->type, ['KICK OFF MEETING SESSION', 'REVIEW SESSION'])) {
                                    // Red for implementation sessions
                                    if (!$appointment->required_attendees && !$appointment->event_id && !$appointment->meeting_link) {
                                        // Blue for appointments created with skipEmailAndTeams
                                        $data[$daySessionSlots][$sessionName]['status'] = 'skip_email_teams';
                                    } else {
                                        // Red for regular implementation sessions
                                        $data[$daySessionSlots][$sessionName]['status'] = 'implementation_session';
                                    }
                                } else {
                                    // Default fallback (should rarely be used)
                                    $data[$daySessionSlots][$sessionName]['status'] = 'implementation_session';
                                }
                            }
                            break;
                        }
                    }
                }
            }

            // Count appointments for statistics
            $this->countAppointments($data['newAppointment']);
            $result[] = $data;
            $implementerCollection->push($data);
        }
        $result = $implementerCollection->sortBy('order')->values()->toArray();

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
        // Define the custom order for implementers
        $customOrder = [
            'Ahmad Syamim' => 2,
            'Ahmad Syazwan' => 3,
            'Siti Shahilah' => 4,
            'Muhamad Izzul Aiman' => 5,
            'Zulhilmie' => 6,
            'Mohd Amirul Ashraf' => 7,
            'John Low' => 8,
            'Nur Alia' => 9,
            'Nur Fazuliana' => 10,
            'Ummu Najwa Fajrina' => 11,
            'Noor Syazana' => 12
        ];

        // Get implementers (role_id 4 and 5)
        $implementers = User::whereIn('role_id', [4, 5])
            ->select('id', 'name', 'avatar_path')
            ->get()
            ->map(function ($implementer) use ($customOrder) {
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
                    'avatar_url' => $avatarUrl,
                    'order' => $customOrder[$implementer->name] ?? 999 // Default high order for names not in the list
                ];
            })
            ->sortBy('order') // Sort by the custom order
            ->values() // Reset array keys
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
            ->whereBetween('date', [$this->startDate, $this->endDate]);

        if (!empty($this->selectedImplementers)) {
            $query->whereIn('implementer', $this->selectedImplementers);
        }

        // Get all appointments (including cancelled ones)
        $allAppointments = $query->get();

        $result = [
            'KICK OFF MEETING SESSION' => 0,
            'REVIEW SESSION' => 0,
            'DATA MIGRATION SESSION' => 0,
            'SYSTEM SETTING SESSION' => 0,
            'WEEKLY FOLLOW UP SESSION' => 0,
        ];

        // Count non-cancelled appointments first
        foreach ($allAppointments as $appointment) {
            if ($appointment->status !== 'Cancelled') {
                $type = $appointment->type ?? 'Unknown';
                $result[$type] = ($result[$type] ?? 0) + 1;
            }
        }

        // Now handle cancelled appointments
        $cancelledAppointments = $allAppointments->where('status', 'Cancelled');

        foreach ($cancelledAppointments as $cancelled) {
            // Check if this cancelled appointment's time slot has been replaced
            $isReplaced = $allAppointments->where('implementer', $cancelled->implementer)
                ->where('date', $cancelled->date)
                ->where('start_time', $cancelled->start_time)
                ->whereIn('status', ['New', 'Done'])
                ->count() > 0;

            // Only count as cancelled if the slot hasn't been replaced
            if (!$isReplaced) {
                $type = $cancelled->type ?? 'Unknown';
                // Don't increment the result since we want to show actual active appointments
                // The cancelled count will be handled separately in totalAppointmentsStatus
            }
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

    public function getWeekInfo($date = null)
    {
        $dateCarbon = $date ? Carbon::parse($date) : Carbon::now();

        return [
            'year' => (int) $dateCarbon->format('Y'),
            'week' => (int) $dateCarbon->format('W')
        ];
    }

    public function updateSelectedYearAndWeek()
    {
        if ($this->bookingDate) {
            $weekInfo = $this->getWeekInfo($this->bookingDate);
            $this->selectedYear = $weekInfo['year'];
            $this->selectedWeek = $weekInfo['week'];

            // Update available weeks to ensure this week is included
            $this->updateAvailableWeeks();
        }
    }

    public function bookSession($implementerId, $date, $session, $startTime, $endTime)
    {
        // Reset form fields
        $this->appointmentType = 'ONLINE';
        $this->requiredAttendees = '';
        $this->remarks = '';
        $this->requestSessionType = '';
        $this->implementationDemoType = 'REVIEW SESSION';

        // Store booking details
        $this->bookingImplementerId = $implementerId;
        $this->bookingDate = $date;
        $this->bookingSession = $session;
        $this->bookingStartTime = $startTime;
        $this->bookingEndTime = $endTime;
        $this->selectedCompany = null;

        // Set up available years and weeks for weekly follow-up
        $currentYear = Carbon::now()->year;
        $this->availableYears = [$currentYear, $currentYear + 1];
        $this->selectedYear = $currentYear;
        $this->updateAvailableWeeks();

        $this->updateSelectedYearAndWeek();

        // Show the session type selection modal
        $this->showBookingModal = true;

        // Filter companies to only show open/delay projects
        $this->updateOpenDelayCompanies();
    }

    public function updateOpenDelayCompanies()
    {
        // Get the current authenticated user's name if they're an implementer
        $currentUserName = null;
        if (auth()->user()->role_id == 4 || auth()->user()->role_id == 5) {
            $currentUserName = auth()->user()->name;
        }

        // Base query to get companies with Open or Delay status - CASE INSENSITIVE
        $query = \App\Models\CompanyDetail::select(
                'company_details.id',
                'company_details.company_name',
                'software_handovers.id as handover_id',
                'software_handovers.status_handover as status',
                'software_handovers.lead_id as lead_id' // Make sure this is included
            )
            ->join('software_handovers', 'company_details.lead_id', '=', 'software_handovers.lead_id')
            ->where(function($q) {
                $q->whereRaw('LOWER(software_handovers.status_handover) IN (?, ?)', ['open', 'delay']);
            });

        // Filter by implementer if the current user is an implementer
        if ($currentUserName) {
            $query->where('software_handovers.implementer', $currentUserName);
        }

        // Add company name search filter
        if (!empty($this->companySearch)) {
            $searchTerm = '%' . $this->companySearch . '%';
            $query->where('company_details.company_name', 'LIKE', $searchTerm);
        }

        // Execute the query with sorting
        $companies = $query->orderBy('software_handovers.id', 'desc')
            ->orderBy('software_handovers.status_handover')
            ->orderBy('company_details.company_name')
            ->get();

        // Format the company data for the dropdown
        $this->filteredOpenDelayCompanies = [];

        foreach ($companies as $company) {
            // Check how many DATA MIGRATION SESSION appointments exist for this software handover
            $dataMigrationSessionCount = \App\Models\ImplementerAppointment::where('lead_id', $company->lead_id)
                ->where('type', 'DATA MIGRATION SESSION')
                ->where('status', '!=', 'Cancelled')
                ->count();

            // Check how many SYSTEM SETTING SESSION appointments exist for this software handover
            $systemSettingSessionCount = \App\Models\ImplementerAppointment::where('lead_id', $company->lead_id)
                ->where('type', 'SYSTEM SETTING SESSION')
                ->where('status', '!=', 'Cancelled')
                ->count();

            // Only add to dropdown if:
            // - It's not for DATA MIGRATION SESSION OR if it has less than 2 DATA MIGRATION SESSIONs
            // - It's not for SYSTEM SETTING SESSION OR if it has less than 4 SYSTEM SETTING SESSIONs
            if (($this->requestSessionType !== 'DATA MIGRATION SESSION' || $dataMigrationSessionCount < 2) &&
                ($this->requestSessionType !== 'SYSTEM SETTING SESSION' || $systemSettingSessionCount < 4)) {

                $this->filteredOpenDelayCompanies[$company->id] = [
                    'name' => $company->company_name,
                    'handover_id' => str_pad($company->handover_id, 6, '0', STR_PAD_LEFT),
                    'status' => $company->status,
                    'lead_id' => $company->lead_id, // Include this for future session lookup
                    'data_migration_count' => $dataMigrationSessionCount,
                    'system_setting_count' => $systemSettingSessionCount
                ];
            }
        }
    }

    public function resetCompanySearch()
    {
        $this->companySearch = '';
        $this->updateOpenDelayCompanies();
    }

    public function updateAvailableWeeks()
    {
        $currentDate = Carbon::now();
        $currentYear = $currentDate->year;
        $currentWeek = $currentDate->weekOfYear;

        // If selected year is current year, only show weeks from current week forward
        if ($this->selectedYear == $currentYear) {
            $this->availableWeeks = range($currentWeek, 53);
        } else {
            // If future year, show all weeks
            $this->availableWeeks = range(1, 53);
        }

        // Reset selected week if not in available weeks
        if (!in_array($this->selectedWeek, $this->availableWeeks)) {
            $this->selectedWeek = null;
        }
    }

    public function updatedSelectedYear()
    {
        $this->updateAvailableWeeks();
    }

    // Add method to handle session type selection
    public function selectSessionType($type)
    {
        $this->showBookingModal = false;

        if ($type === 'implementer_request') {
            // For Weekly Follow Up Session, check if already exists for the selected week
            if ($this->requestSessionType === 'WEEKLY FOLLOW UP SESSION') {
                // Ensure week number is set based on the booking date
                $this->updateSelectedYearAndWeek();

                // Check if this week already has a session
                if ($this->weekHasFollowUpSession($this->selectedYear, $this->selectedWeek)) {
                    Notification::make()
                        ->title('Weekly Follow-up Session limit reached')
                        ->warning()
                        ->body('There is already a Weekly Follow-up Session scheduled for Week ' . $this->selectedWeek . ', ' . $this->selectedYear)
                        ->send();

                    return;
                }
            }

            $this->showImplementerRequestModal = true;
        } elseif ($type === 'implementation_session') {
            $this->showImplementationSessionModal = true;
        } elseif ($type === 'onsite_request') {
            // Load companies assigned to the current implementer
            $this->loadImplementerCompanies();
            // Show the onsite request modal
            $this->showOnsiteRequestModal = true;
        }
    }

    private function loadImplementerCompanies()
    {
        // Get the implementer name
        $implementer = User::find($this->bookingImplementerId);
        if (!$implementer) {
            return;
        }

        // Get software handovers assigned to this implementer with Open or Delay status
        $query = \App\Models\CompanyDetail::select(
                'company_details.id',
                'company_details.company_name',
                'software_handovers.id as handover_id',
                'software_handovers.status_handover as status',
                'software_handovers.lead_id as lead_id'
            )
            ->join('software_handovers', 'company_details.lead_id', '=', 'software_handovers.lead_id')
            ->where('software_handovers.implementer', $implementer->name)
            ->whereIn('software_handovers.status_handover', ['Open', 'Delay']);

        // Execute the query with sorting by handover ID in descending order
        $companies = $query->orderBy('software_handovers.id', 'desc')
            ->orderBy('software_handovers.status_handover')
            ->orderBy('company_details.company_name')
            ->get();

        // Format the company data for the dropdown
        $this->implementerCompanies = [];

        foreach ($companies as $company) {
            $this->implementerCompanies[$company->id] = [
                'name' => $company->company_name,
                'handover_id' => str_pad($company->handover_id, 6, '0', STR_PAD_LEFT),
                'status' => $company->status,
                'lead_id' => $company->lead_id
            ];
        }
    }

    public function updateOnsiteSessions()
    {
        // First check if the selected day type is still available
        $availableDayTypes = $this->getAvailableDayTypes();

        if (!empty($this->onsiteDayType) && !isset($availableDayTypes[$this->onsiteDayType]) ||
            !empty($this->onsiteDayType) && isset($availableDayTypes[$this->onsiteDayType]) && !$availableDayTypes[$this->onsiteDayType]) {
            // Reset the selection if it's no longer available
            $this->onsiteDayType = '';
            $this->selectedOnsiteSessions = [];

            // Notify the user
            Notification::make()
                ->title('Day type not available')
                ->warning()
                ->body('One or more sessions in this day type are already booked. Please select another option.')
                ->send();

            return;
        }

        // Clear previously selected sessions
        $this->selectedOnsiteSessions = [];

        // Get all available session slots for the selected day
        $dayOfWeek = strtolower(Carbon::parse($this->bookingDate)->format('l'));
        $sessionSlots = $this->getSessionSlots($dayOfWeek, $this->bookingDate, $this->bookingImplementerId);

        // Based on the day type, select appropriate sessions
        switch ($this->onsiteDayType) {
            case 'FULL_DAY':
                // Select all sessions
                foreach ($sessionSlots as $sessionName => $session) {
                    // Only add if the session is available
                    if (!isset($session['booked']) || !$session['booked']) {
                        // Check if it's not on leave/holiday
                        if (!in_array($session['status'] ?? '', ['leave', 'holiday', 'past'])) {
                            $this->selectedOnsiteSessions[] = [
                                'name' => $sessionName,
                                'start' => $session['formatted_start'],
                                'end' => $session['formatted_end'],
                                'start_time' => $session['start_time'],
                                'end_time' => $session['end_time']
                            ];
                        }
                    }
                }
                break;

            case 'HALF_DAY_MORNING':
                // Select morning sessions (1 & 2)
                foreach (['SESSION 1', 'SESSION 2'] as $morningSession) {
                    if (isset($sessionSlots[$morningSession]) &&
                        (!isset($sessionSlots[$morningSession]['booked']) || !$sessionSlots[$morningSession]['booked']) &&
                        !in_array($sessionSlots[$morningSession]['status'] ?? '', ['leave', 'holiday', 'past'])) {

                        $this->selectedOnsiteSessions[] = [
                            'name' => $morningSession,
                            'start' => $sessionSlots[$morningSession]['formatted_start'],
                            'end' => $sessionSlots[$morningSession]['formatted_end'],
                            'start_time' => $sessionSlots[$morningSession]['start_time'],
                            'end_time' => $sessionSlots[$morningSession]['end_time']
                        ];
                    }
                }
                break;

            case 'HALF_DAY_EVENING':
                // Select afternoon sessions (3, 4, 5)
                $afternoonSessions = ['SESSION 3', 'SESSION 4', 'SESSION 5'];
                if ($dayOfWeek === 'friday') {
                    // For Friday, only SESSION 3 and SESSION 4 (which are the renamed sessions)
                    $afternoonSessions = ['SESSION 3', 'SESSION 4'];
                }

                foreach ($afternoonSessions as $afternoonSession) {
                    if (isset($sessionSlots[$afternoonSession]) &&
                        (!isset($sessionSlots[$afternoonSession]['booked']) || !$sessionSlots[$afternoonSession]['booked']) &&
                        !in_array($sessionSlots[$afternoonSession]['status'] ?? '', ['leave', 'holiday', 'past'])) {

                        $this->selectedOnsiteSessions[] = [
                            'name' => $afternoonSession,
                            'start' => $sessionSlots[$afternoonSession]['formatted_start'],
                            'end' => $sessionSlots[$afternoonSession]['formatted_end'],
                            'start_time' => $sessionSlots[$afternoonSession]['start_time'],
                            'end_time' => $sessionSlots[$afternoonSession]['end_time']
                        ];
                    }
                }
                break;
        }

        // If no sessions were added (all are booked), reset the day type
        if (empty($this->selectedOnsiteSessions)) {
            $this->onsiteDayType = '';

            Notification::make()
                ->title('No available sessions')
                ->warning()
                ->body('There are no available sessions for the selected day type. Please select another option.')
                ->send();
        }
    }

    public function submitOnsiteRequest()
    {
        // Validate form inputs - conditional validation based on category
        $rules = [
            'onsiteDayType' => 'required|in:FULL_DAY,HALF_DAY_MORNING,HALF_DAY_EVENING',
            'onsiteCategory' => 'required|string',
        ];

        // Only require company and attendees if NOT backup support
        if ($this->onsiteCategory !== 'BACKUP SUPPORT') {
            $rules['selectedCompany'] = 'required|exists:company_details,id';
            $rules['requiredAttendees'] = 'required|string';
        }

        $this->validate($rules);

        // Ensure we have sessions selected
        if (empty($this->selectedOnsiteSessions)) {
            Notification::make()
                ->title('No sessions selected')
                ->danger()
                ->body('Please select a day type to specify which sessions to book')
                ->send();
            return;
        }

        // Get company details (only if not backup support)
        $companyDetail = null;
        $leadId = null;
        $softwareHandover = null;

        if ($this->onsiteCategory !== 'BACKUP SUPPORT' && $this->selectedCompany) {
            $companyDetail = \App\Models\CompanyDetail::find($this->selectedCompany);
            if (!$companyDetail) {
                Notification::make()
                    ->title('Company not found')
                    ->danger()
                    ->send();
                return;
            }
            $leadId = $companyDetail->lead_id;

            if ($leadId) {
                $softwareHandover = \App\Models\SoftwareHandover::where('lead_id', $leadId)
                    ->orderBy('id', 'desc')
                    ->first();
            }
        }

        // Get implementer details
        $implementer = User::find($this->bookingImplementerId);
        if (!$implementer) {
            Notification::make()
                ->title('Implementer not found')
                ->danger()
                ->send();
            return;
        }

        try {
            DB::beginTransaction();

            // Create one appointment for each selected session
            $createdAppointments = [];

            foreach ($this->selectedOnsiteSessions as $session) {
                $appointment = new \App\Models\ImplementerAppointment();

                // Set title based on category
                $appointmentTitle = $this->onsiteCategory;
                if ($this->onsiteCategory === 'BACKUP SUPPORT') {
                    $appointmentTitle .= ' | ' . $session['name'];
                } else {
                    $appointmentTitle .= ' | ' . ($companyDetail ? $companyDetail->company_name : 'Unknown Company') . ' | ' . $session['name'];
                }

                $appointment->fill([
                    'lead_id' => $leadId, // Will be null for backup support
                    'type' => $this->onsiteCategory,
                    'appointment_type' => 'ONSITE', // Always ONSITE for onsite requests
                    'date' => $this->bookingDate,
                    'start_time' => $session['start_time'],
                    'end_time' => $session['end_time'],
                    'implementer' => $implementer->name,
                    'causer_id' => auth()->user()->id,
                    'title' => $appointmentTitle,
                    'status' => 'New',
                    'request_status' => 'PENDING',
                    'required_attendees' => $this->onsiteCategory === 'BACKUP SUPPORT' ? null : $this->requiredAttendees,
                    'software_handover_id' => $softwareHandover ? $softwareHandover->id : null,
                ]);

                $appointment->save();
                $createdAppointments[] = $appointment;
            }

            DB::commit();

            // Send email notification
            try {
                // Get authenticated user's email for sender
                $authUser = auth()->user();
                $senderEmail = $authUser->email;
                $senderName = $authUser->name;

                $recipients = []; // Main recipient - you may want to add recipients here

                // Format session information for email
                $sessionInfo = [];
                foreach ($this->selectedOnsiteSessions as $session) {
                    $sessionInfo[] = "{$session['name']}: {$session['start']} - {$session['end']}";
                }

                $emailData = [
                    'implementerId' => 'IMP_' . str_pad($implementer->id, 6, '0', STR_PAD_LEFT),
                    'implementerName' => strtoupper($implementer->name),
                    'requestDateTime' => Carbon::now()->format('d F Y h:i A'),
                    'companyName' => $this->onsiteCategory === 'BACKUP SUPPORT'
                        ? 'BACKUP SUPPORT REQUEST'
                        : ($companyDetail ? $companyDetail->company_name : 'Unknown Company'),
                    'onsiteCategory' => $this->onsiteCategory,
                    'dateAndDay' => Carbon::parse($this->bookingDate)->format('d F Y / l'),
                    'dayType' => str_replace('_', ' ', $this->onsiteDayType),
                    'sessions' => implode('<br>', $sessionInfo),
                    'attendees' => $this->onsiteCategory === 'BACKUP SUPPORT'
                        ? 'N/A - Backup Support Request'
                        : $this->requiredAttendees,
                    'remarks' => $this->onsiteRemarks ?? 'No additional remarks',
                ];

                if (!empty($recipients)) {
                    \Illuminate\Support\Facades\Mail::send(
                        'emails.implementer_onsite_request',
                        ['content' => $emailData],
                        function ($message) use ($recipients, $senderEmail, $senderName, $implementer) {
                            $message->from($senderEmail, $senderName)
                                ->to($recipients)
                                ->cc($senderEmail)
                                ->subject("ONSITE REQUEST: " . strtoupper($implementer->name));
                        }
                    );
                }

                Notification::make()
                    ->title('Onsite request submitted successfully')
                    ->success()
                    ->body($this->onsiteCategory === 'BACKUP SUPPORT'
                        ? 'Backup support request has been submitted'
                        : 'Email notification has been sent')
                    ->send();

            } catch (\Exception $e) {
                Log::error("Email sending failed for onsite request: Error: {$e->getMessage()}");

                Notification::make()
                    ->title('Request submitted but email failed')
                    ->warning()
                    ->body('Error sending email: ' . $e->getMessage())
                    ->send();
            }

            // Close modal and reset form
            $this->showOnsiteRequestModal = false;
            $this->reset([
                'onsiteDayType',
                'onsiteCategory',
                'selectedCompany',
                'requiredAttendees',
                'onsiteRemarks',
                'selectedOnsiteSessions',
                'companySearch'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title('Error submitting request')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    public function getAvailableDayTypes()
    {
        $dayOfWeek = strtolower(Carbon::parse($this->bookingDate)->format('l'));
        $date = $this->bookingDate;
        $implementer = User::find($this->bookingImplementerId);

        if (!$implementer) {
            return [
                'FULL_DAY' => false,
                'HALF_DAY_MORNING' => false,
                'HALF_DAY_EVENING' => false
            ];
        }

        // Initialize all day types as available
        $availableDayTypes = [
            'FULL_DAY' => true,
            'HALF_DAY_MORNING' => true,
            'HALF_DAY_EVENING' => true
        ];

        // Get all session time slots for the day
        $sessionSlots = $this->getSessionSlots($dayOfWeek);

        // Check for public holiday
        $isPublicHoliday = PublicHoliday::where('date', $date)->exists();
        if ($isPublicHoliday) {
            return [
                'FULL_DAY' => false,
                'HALF_DAY_MORNING' => false,
                'HALF_DAY_EVENING' => false
            ];
        }

        // Check for leave - GET ALL leave records, not just first()
        $leaves = UserLeave::where('user_id', $this->bookingImplementerId)
            ->where('date', $date)
            ->whereIn('status', ['Approved', 'Pending'])
            ->get(); // Changed from ->first() to ->get()

        if ($leaves->count() > 0) {
            $hasAmLeave = $leaves->where('session', 'am')->count() > 0;
            $hasPmLeave = $leaves->where('session', 'pm')->count() > 0;
            $hasFullLeave = $leaves->where('session', 'full')->count() > 0;

            if ($hasFullLeave || ($hasAmLeave && $hasPmLeave)) {
                // Full day leave (either explicit or both AM/PM)
                return [
                    'FULL_DAY' => false,
                    'HALF_DAY_MORNING' => false,
                    'HALF_DAY_EVENING' => false
                ];
            } elseif ($hasAmLeave) {
                $availableDayTypes['HALF_DAY_MORNING'] = false;
                $availableDayTypes['FULL_DAY'] = false;
            } elseif ($hasPmLeave) {
                $availableDayTypes['HALF_DAY_EVENING'] = false;
                $availableDayTypes['FULL_DAY'] = false;
            }
        }

        // Get existing active appointments directly from database
        $existingAppointments = \App\Models\ImplementerAppointment::where('implementer', $implementer->name)
            ->where('date', $date)
            ->where('status', '!=', 'Cancelled')
            ->get();

        // Define which sessions belong to which day type
        $morningSessionTimes = ['09:30:00', '11:00:00'];
        $eveningSessionTimes = ($dayOfWeek === 'friday')
            ? ['15:00:00', '16:30:00']  // Friday times for SESSION 3 and SESSION 4
            : ['14:00:00', '15:30:00', '17:00:00'];  // Mon-Thu times

        // Check morning sessions (SESSION 1 and SESSION 2)
        foreach ($morningSessionTimes as $timeSlot) {
            foreach ($existingAppointments as $appointment) {
                $appointmentStartTime = Carbon::parse($appointment->start_time)->format('H:i:s');
                if ($appointmentStartTime == $timeSlot) {
                    $availableDayTypes['HALF_DAY_MORNING'] = false;
                    $availableDayTypes['FULL_DAY'] = false;
                    break 2; // Break out of both loops
                }
            }
        }

        // Check evening sessions
        foreach ($eveningSessionTimes as $timeSlot) {
            foreach ($existingAppointments as $appointment) {
                $appointmentStartTime = Carbon::parse($appointment->start_time)->format('H:i:s');
                if ($appointmentStartTime == $timeSlot) {
                    $availableDayTypes['HALF_DAY_EVENING'] = false;
                    $availableDayTypes['FULL_DAY'] = false;
                    break 2; // Break out of both loops
                }
            }
        }

        return $availableDayTypes;
    }

    // Add method to handle session type change in implementer request
    public function onRequestSessionTypeChange()
    {
        if ($this->requestSessionType === 'WEEKLY FOLLOW UP SESSION') {
            $this->updateAvailableWeeks();

            // Set the selected year and week from the booking date
            $this->updateSelectedYearAndWeek();

            // Check if this week already has a session
            if ($this->selectedYear && $this->selectedWeek && $this->weekHasFollowUpSession($this->selectedYear, $this->selectedWeek)) {
                Notification::make()
                    ->title('Weekly Follow-up Session limit reached')
                    ->warning()
                    ->body('There is already a Weekly Follow-up Session scheduled for Week ' . $this->selectedWeek . ', ' . $this->selectedYear . '. Please choose a different session type.')
                    ->send();

                // Reset session type selection
                $this->requestSessionType = '';
            }
        } else {
            $this->updateOpenDelayCompanies();
        }
    }

    public function loadAttendees()
    {
        if (!$this->selectedCompany) {
            return;
        }

        try {
            // Get company details
            $companyDetail = \App\Models\CompanyDetail::find($this->selectedCompany);
            if (!$companyDetail || !$companyDetail->lead_id) {
                // Don't show a notification since this is automatic now
                return;
            }

            $emails = [];
            $resignedEmails = []; // Track resigned emails to exclude them

            // 1. Get company email from company_details table
            if ($companyDetail && !empty($companyDetail->email)) {
                $email = trim($companyDetail->email);
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $emails[] = $email;
                }
            }

            // 2. Get emails from SoftwareHandover implementation_pics
            $softwareHandover = \App\Models\SoftwareHandover::where('lead_id', $companyDetail->lead_id)->latest()->first();

            if ($softwareHandover && !empty($softwareHandover->implementation_pics)) {
                try {
                    $implementationPics = $softwareHandover->implementation_pics;

                    // Check if it's already an array or needs to be decoded
                    if (!is_array($implementationPics)) {
                        $implementationPics = json_decode($implementationPics, true);
                    }

                    if (is_array($implementationPics)) {
                        foreach ($implementationPics as $pic) {
                            if (isset($pic['pic_email_impl']) && !empty($pic['pic_email_impl'])) {
                                $email = trim($pic['pic_email_impl']);
                                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                    // Check status - if resigned, add to resigned emails list
                                    if (isset($pic['status']) && strtolower($pic['status']) === 'resign') {
                                        $resignedEmails[] = $email;
                                    } elseif (isset($pic['status']) && $pic['status'] === 'Available') {
                                        // Only include PICs with "Available" status
                                        $emails[] = $email;
                                    }
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Error parsing implementation_pics JSON: " . $e->getMessage());
                }
            }

            // 3. Get emails from company_details additional_pic field
            if ($companyDetail && !empty($companyDetail->additional_pic)) {
                try {
                    $additionalPics = $companyDetail->additional_pic;

                    // Check if it's already an array or needs to be decoded
                    if (!is_array($additionalPics)) {
                        $additionalPics = json_decode($additionalPics, true);
                    }

                    if (is_array($additionalPics)) {
                        foreach ($additionalPics as $pic) {
                            if (isset($pic['email']) && !empty($pic['email'])) {
                                $email = trim($pic['email']);
                                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                    // Check status - if resigned, add to resigned emails list
                                    if (isset($pic['status']) && strtolower($pic['status']) === 'resign') {
                                        $resignedEmails[] = $email;
                                    } elseif (isset($pic['status']) && $pic['status'] !== 'Resign') {
                                        // Only include PICs with non-resigned status
                                        $emails[] = $email;
                                    }
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Error parsing additional_pic JSON: " . $e->getMessage());
                }
            }

            // 4. Get salesperson email
            if ($companyDetail && $companyDetail->lead_id) {
                try {
                    $lead = \App\Models\Lead::find($companyDetail->lead_id);
                    if ($lead && !empty($lead->salesperson)) {
                        // Find the user with this salesperson ID
                        $salesperson = \App\Models\User::where('id', $lead->salesperson)->first();

                        if ($salesperson && !empty($salesperson->email)) {
                            $email = trim($salesperson->email);
                            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                $emails[] = $email;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Error fetching salesperson email: " . $e->getMessage());
                }
            }

            // Remove duplicates from emails array
            $emails = array_unique($emails);

            // Remove resigned emails from the final emails array
            $emails = array_diff($emails, $resignedEmails);

            // Re-index the array after filtering
            $emails = array_values($emails);

            // Update required attendees field with all found emails
            $this->requiredAttendees = implode(';', $emails);

            // Only show a notification if we're not auto-loading
            if (!$this->isLoadingAttendees) {
                if (empty($emails)) {
                    Notification::make()
                        ->title('No attendees found')
                        ->warning()
                        ->body('Please enter attendees manually')
                        ->send();
                } else {
                    $emailSources = [];
                    if ($companyDetail && !empty($companyDetail->email)) {
                        $emailSources[] = 'Company Email';
                    }
                    if (!empty($implementationPics)) {
                        $emailSources[] = 'Implementation PICs';
                    }
                    if (!empty($additionalPics)) {
                        $emailSources[] = 'Company PICs';
                    }
                    if ($lead && !empty($lead->salesperson)) {
                        $emailSources[] = 'Salesperson';
                    }

                    // Show additional info if some emails were excluded due to resignation
                    $bodyMessage = 'Found ' . count($emails) . ' email addresses from: ' . implode(', ', $emailSources);
                    if (!empty($resignedEmails)) {
                        $bodyMessage .= '. Excluded ' . count($resignedEmails) . ' resigned PIC email(s).';
                    }

                    Notification::make()
                        ->title('Attendees loaded successfully')
                        ->success()
                        ->body($bodyMessage)
                        ->send();
                }
            }
        } catch (\Exception $e) {
            Log::error("Error in loadAttendees: " . $e->getMessage());

            // Only show an error notification if we're not auto-loading
            if (!$this->isLoadingAttendees) {
                Notification::make()
                    ->title('Error loading attendees')
                    ->danger()
                    ->body($e->getMessage())
                    ->send();
            }
        }
    }

    public function submitImplementerRequest()
    {
        if ($this->requestSessionType === 'WEEKLY FOLLOW UP SESSION') {
            $this->validate([
                'requestSessionType' => 'required|string',
                'selectedYear' => 'required|integer',
                'selectedWeek' => 'required|integer|min:1|max:53',
            ]);

            // Check one more time if a weekly session already exists for this week
            if ($this->weekHasFollowUpSession($this->selectedYear, $this->selectedWeek)) {
                Notification::make()
                    ->title('Weekly Follow-up Session limit reached')
                    ->warning()
                    ->body('There is already a Weekly Follow-up Session scheduled for Week ' . $this->selectedWeek . ', ' . $this->selectedYear)
                    ->send();

                return;
            }

            // Continue with existing code...
            $leadId = null;
            $companyName = '';
            $softwareHandoverId = null;
            $selectedYear = $this->selectedYear;
            $selectedWeek = $this->selectedWeek;
            $title = $this->requestSessionType . ' | IMPLEMENTER REQUEST | WEEK ' . $this->selectedWeek . ', ' . $this->selectedYear;
        }  else {
            $this->validate([
                'requestSessionType' => 'required|string',
                'selectedCompany' => 'required|exists:company_details,id',
            ]);

            // Initialize variables
            $leadId = null;
            $companyName = '';
            $softwareHandoverId = null;
            $selectedYear = null;
            $selectedWeek = null;

            // Fetch company details
            $companyDetail = \App\Models\CompanyDetail::find($this->selectedCompany);
            if (!$companyDetail) {
                Notification::make()
                    ->title('Company not found')
                    ->danger()
                    ->send();
                return;
            }

            // Now it's safe to access lead_id
            $leadId = $companyDetail->lead_id;
            $companyName = $companyDetail->company_name;

            // Find software handover ID if lead ID exists
            if ($leadId) {
                $softwareHandoverId = \App\Models\SoftwareHandover::where('lead_id', $leadId)
                    ->orderBy('id', 'desc')
                    ->value('id');

                if ($this->requestSessionType === 'DATA MIGRATION SESSION') {
                    $dataMigrationSessionCount = \App\Models\ImplementerAppointment::where('lead_id', $leadId)
                        ->where('type', 'DATA MIGRATION SESSION')
                        ->where('status', '!=', 'Cancelled')
                        ->count();

                    if ($dataMigrationSessionCount >= 2) {
                        Notification::make()
                            ->title('Maximum data migration sessions reached')
                            ->warning()
                            ->body('This software handover already has 2 DATA MIGRATION SESSION appointments.')
                            ->send();
                        return;
                    }
                }

                if ($this->requestSessionType === 'SYSTEM SETTING SESSION') {
                    $systemSettingSessionCount = \App\Models\ImplementerAppointment::where('lead_id', $leadId)
                        ->where('type', 'SYSTEM SETTING SESSION')
                        ->where('status', '!=', 'Cancelled')
                        ->count();

                    if ($systemSettingSessionCount >= 4) {
                        Notification::make()
                            ->title('Maximum system setting sessions reached')
                            ->warning()
                            ->body('This software handover already has 4 SYSTEM SETTING SESSION appointments.')
                            ->send();
                        return;
                    }
                }
            }

            $title = $this->requestSessionType . ' | IMPLEMENTER REQUEST | ' . $companyName;
        }

        // Get implementer details
        $implementer = User::find($this->bookingImplementerId);
        if (!$implementer) {
            Notification::make()
                ->title('Implementer not found')
                ->danger()
                ->send();
            return;
        }

        try {
            // Create appointment record with request_status
            $appointment = new \App\Models\ImplementerAppointment();
            $appointment->fill([
                'lead_id' => $leadId ?? null,
                'type' => $this->requestSessionType,
                'appointment_type' => 'ONLINE',
                'date' => $this->bookingDate,
                'start_time' => $this->bookingStartTime,
                'end_time' => $this->bookingEndTime,
                'implementer' => $implementer->name,
                'causer_id' => auth()->user()->id,
                'implementer_assigned_date' => now(),
                'title' => $title,
                'status' => 'New',
                'request_status' => 'PENDING',
                'selected_year' => $selectedYear,
                'selected_week' => $selectedWeek,
                'session' => $this->bookingSession,
                'remarks' => $this->requestSessionType !== 'WEEKLY FOLLOW UP SESSION' ?
                        "Request for {$this->requestSessionType} for {$companyName}" :
                        "Request for {$this->requestSessionType} for Week {$this->selectedWeek}, {$this->selectedYear}",
                'software_handover_id' => $softwareHandoverId,
            ]);

            $appointment->save();

            // Prepare email content
            $emailData = [
                'implementerId' => 'IMP_' . str_pad($implementer->id, 6, '0', STR_PAD_LEFT),
                'implementerName' => strtoupper($implementer->name),
                'requestDateTime' => Carbon::now()->format('d F Y h:i A'),
                'companyName' => $this->requestSessionType !== 'WEEKLY FOLLOW UP SESSION' ?
                            "{$softwareHandoverId} | {$companyName}" :
                            "Week {$this->selectedWeek}-{$this->selectedYear}",
                'sessionType' => $this->requestSessionType,
                'dateAndDay' => Carbon::parse($this->bookingDate)->format('d F Y / l'),
                'implementationSession' => "{$this->bookingSession}: " .
                                        Carbon::parse($this->bookingStartTime)->format('h:iA') . ' – ' .
                                        Carbon::parse($this->bookingEndTime)->format('h:iA'),
                'status' => 'PENDING',
                'appointmentId' => $appointment->id,
                'selectedYear' => $selectedYear,
                'selectedWeek' => $selectedWeek,
            ];

            // Create an activity log entry
            ActivityLog::create([
                'user_id' => auth()->id(),
                'causer_id' => auth()->id(),
                'action' => 'Submitted Implementer Request',
                'description' => "Submitted {$this->requestSessionType} request for " .
                                ($this->requestSessionType !== 'WEEKLY FOLLOW UP SESSION' ?
                                $companyName : "Week {$this->selectedWeek}, {$this->selectedYear}"),
                'subject_type' => get_class($appointment),
                'subject_id' => $appointment->id,
            ]);

            // Send email
            try {
                // Get authenticated user's email for sender
                $authUser = auth()->user();
                $senderEmail = $authUser->email;
                $senderName = $authUser->name;

                // Recipients
                $recipients = ['fazuliana.mohdarsad@timeteccloud.com']; // Main recipient
                // $recipients = ['zilih.ng@timeteccloud.com']; // Main recipient
                $ccRecipients = [$senderEmail]; // CC implementer

                \Illuminate\Support\Facades\Mail::send('emails.implementer_request',
                    ['content' => $emailData],
                    function ($message) use ($recipients, $ccRecipients, $senderEmail, $senderName, $implementer) {
                        $message->from($senderEmail, $senderName)
                            ->to($recipients)
                            ->cc($ccRecipients)
                            ->subject("IMPLEMENTER REQUEST: " . strtoupper($implementer->name));
                    }
                );

                Notification::make()
                    ->title('Request submitted successfully')
                    ->success()
                    ->body('Email notification has been sent')
                    ->send();
            } catch (\Exception $e) {
                Log::error("Email sending failed for implementer request: Error: {$e->getMessage()}");

                Notification::make()
                    ->title('Request submitted but email failed')
                    ->warning()
                    ->body('Error sending email: ' . $e->getMessage())
                    ->send();
            }

            // Close modals
            $this->showImplementerRequestModal = false;
            $this->reset(['requestSessionType', 'selectedCompany', 'selectedYear', 'selectedWeek', 'companySearch']);

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error submitting request')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    // Add method to submit implementation session
    public function submitImplementationSession()
    {
        $rules = [
            'selectedCompany' => 'required|exists:company_details,id',
            'implementationDemoType' => 'required|string',
            'appointmentType' => 'required|in:ONLINE,ONSITE,INHOUSE',
        ];

        // Only require attendees if not skipping emails
        if (!$this->skipEmailAndTeams) {
            $rules['requiredAttendees'] = 'required|string';
        }

        // Apply validation
        $this->validate($rules);

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

        if ($this->implementationDemoType === 'DATA MIGRATION SESSION') {
            $dataMigrationSessionCount = \App\Models\ImplementerAppointment::where('lead_id', $leadId)
                ->where('type', 'DATA MIGRATION SESSION')
                ->where('status', '!=', 'Cancelled')
                ->count();

            if ($dataMigrationSessionCount >= 2) {
                Notification::make()
                    ->title('Maximum data migration sessions reached')
                    ->warning()
                    ->body('This software handover already has 2 DATA MIGRATION SESSION appointments.')
                    ->send();
                return;
            }
        }

        if ($this->implementationDemoType === 'SYSTEM SETTING SESSION') {
            $systemSettingSessionCount = \App\Models\ImplementerAppointment::where('lead_id', $leadId)
                ->where('type', 'SYSTEM SETTING SESSION')
                ->where('status', '!=', 'Cancelled')
                ->count();

            if ($systemSettingSessionCount >= 4) {
                Notification::make()
                    ->title('Maximum system setting sessions reached')
                    ->warning()
                    ->body('This software handover already has 4 SYSTEM SETTING SESSION appointments.')
                    ->send();
                return;
            }
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

        try {
            // Count existing appointments for this company
            $existingAppointmentsCount = \App\Models\ImplementerAppointment::where('lead_id', $leadId)
                ->where('status', '!=', 'Cancelled')
                ->where('type', 'REVIEW SESSION')
                ->count();

            // Create appointment
            $appointment = new \App\Models\ImplementerAppointment();

            // Create Teams meeting if appointment type is ONLINE
            $teamsEventId = null;
            $meetingLink = null;
            $meetingId = null;
            $meetingPassword = null;
            $onlineMeetingId = null; // ✅ Add this

            if (!$this->skipEmailAndTeams) {
                try {
                    // Parse required attendees for Teams meeting
                    $attendeeEmails = [];
                    foreach (array_map('trim', explode(';', $this->requiredAttendees)) as $email) {
                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $attendeeEmails[] = $email;
                        }
                    }

                    // Create Teams meeting
                    $startDateTime = Carbon::parse($this->bookingDate . ' ' . $this->bookingStartTime);
                    $endDateTime = Carbon::parse($this->bookingDate . ' ' . $this->bookingEndTime);

                    $meetingTitle = "{$this->implementationDemoType} | {$companyDetail->company_name}";
                    $meetingBody = "Implementation session scheduled by {$implementer->name}";

                    // Create Teams meeting through Microsoft Graph API
                    $accessToken = \App\Services\MicrosoftGraphService::getAccessToken();
                    $graph = new \Microsoft\Graph\Graph();
                    $graph->setAccessToken($accessToken);

                    // Format meeting request
                    $meetingRequest = [
                        'subject' => $meetingTitle,
                        'body' => [
                            'contentType' => 'text',
                            'content' => $meetingBody
                        ],
                        'start' => [
                            'dateTime' => $startDateTime->format('Y-m-d\TH:i:s'),
                            'timeZone' => config('app.timezone', 'Asia/Kuala_Lumpur')
                        ],
                        'end' => [
                            'dateTime' => $endDateTime->format('Y-m-d\TH:i:s'),
                            'timeZone' => config('app.timezone', 'Asia/Kuala_Lumpur')
                        ],
                        'location' => [
                            'displayName' => 'Microsoft Teams Meeting'
                        ],
                        'attendees' => array_map(function($email) {
                            return [
                                'emailAddress' => [
                                    'address' => $email,
                                ],
                                'type' => 'required'
                            ];
                        }, $attendeeEmails),
                        'isOnlineMeeting' => true,
                        'onlineMeetingProvider' => 'teamsForBusiness'
                    ];

                    // ✅ STEP 1: Create the event using EMAIL
                    $organizerEmail = $implementer->email;

                    $response = $graph->createRequest("POST", "/users/$organizerEmail/events")
                        ->attachBody($meetingRequest)
                        ->setReturnType(\Microsoft\Graph\Model\Event::class)
                        ->execute();

                    // Extract meeting details
                    $teamsEventId = $response->getId();
                    $meetingLink = null;

                    // Add null check before accessing getOnlineMeeting()
                    if ($response->getOnlineMeeting() !== null) {
                        $meetingLink = $response->getOnlineMeeting()->getJoinUrl();
                    }

                    Log::info('✅ Step 1: Event created successfully (Implementer Calendar)', [
                        'event_id' => $teamsEventId,
                        'join_url' => $meetingLink,
                        'organizer_email' => $organizerEmail,
                        'company' => $companyDetail->company_name,
                        'session_type' => $this->implementationDemoType
                    ]);

                    // ✅ STEP 2: Query onlineMeetings using AZURE_USER_ID or EMAIL
                    if ($meetingLink && $meetingLink !== 'N/A') {
                        try {
                            $queryIdentifier = $implementer->azure_user_id ?? $organizerEmail;
                            $filterQuery = "joinWebUrl eq '$meetingLink'";

                            // Query to get the online meeting ID
                            $onlineMeetingResponse = $graph->createRequest("GET", "/users/$queryIdentifier/onlineMeetings?\$filter=$filterQuery")
                                ->execute();

                            $responseBody = $onlineMeetingResponse->getBody();

                            Log::info('✅ Step 2: Online meeting query response (Implementer Calendar)', [
                                'response' => $responseBody,
                                'join_url' => $meetingLink,
                                'query_identifier' => $queryIdentifier,
                                'company' => $companyDetail->company_name
                            ]);

                            // Extract the online meeting ID from response
                            if (isset($responseBody['value']) && count($responseBody['value']) > 0) {
                                $onlineMeetingId = $responseBody['value'][0]['id'] ?? null;

                                Log::info('✅ Step 2: Online meeting ID retrieved (Implementer Calendar)', [
                                    'online_meeting_id' => $onlineMeetingId,
                                    'event_id' => $teamsEventId,
                                    'company' => $companyDetail->company_name
                                ]);

                                // ✅ STEP 3: Enable automatic recording
                                if ($onlineMeetingId) {
                                    try {
                                        $recordingPayload = [
                                            'recordAutomatically' => true
                                        ];

                                        $recordingResponse = $graph->createRequest("PATCH", "/users/$queryIdentifier/onlineMeetings/$onlineMeetingId")
                                            ->attachBody($recordingPayload)
                                            ->execute();

                                        Log::info('✅ Step 3: Automatic recording enabled (Implementer Calendar)', [
                                            'online_meeting_id' => $onlineMeetingId,
                                            'company' => $companyDetail->company_name,
                                            'session_type' => $this->implementationDemoType
                                        ]);

                                    } catch (\Exception $e) {
                                        Log::error('❌ Step 3: Failed to enable automatic recording (Implementer Calendar)', [
                                            'error' => $e->getMessage(),
                                            'online_meeting_id' => $onlineMeetingId,
                                            'company' => $companyDetail->company_name,
                                            'trace' => $e->getTraceAsString()
                                        ]);
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            Log::error('❌ Step 2: Failed to retrieve online meeting ID (Implementer Calendar)', [
                                'error' => $e->getMessage(),
                                'join_url' => $meetingLink,
                                'company' => $companyDetail->company_name,
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    }

                } catch (\Exception $e) {
                    Log::error('Failed to create Teams meeting: ' . $e->getMessage(), [
                        'trace' => $e->getTraceAsString()
                    ]);

                    // Continue without Teams meeting if it fails
                    Notification::make()
                        ->title('Teams meeting creation failed')
                        ->warning()
                        ->body('The appointment will be created without Teams meeting details.')
                        ->send();
                }
            } else {
                Notification::make()
                    ->title('Session booked')
                    ->success()
                    ->body('Email and Teams meeting were skipped as requested')
                    ->send();
            }

            if ($leadId) {
                $softwareHandoverId = \App\Models\SoftwareHandover::where('lead_id', $leadId)
                    ->orderBy('id', 'desc')
                    ->value('id');
            }

            // Continue creating the appointment
            $appointment->fill([
                'lead_id' => $leadId,
                'type' => $this->implementationDemoType,
                'appointment_type' => $this->appointmentType,
                'date' => $this->bookingDate,
                'start_time' => $this->bookingStartTime,
                'end_time' => $this->bookingEndTime,
                'implementer' => $implementer->name,
                'causer_id' => auth()->user()->id,
                'title' => $this->implementationDemoType . ' | ' . $this->appointmentType . ' | TIMETEC IMPLEMENTER | ' . $companyDetail->company_name,
                'status' => 'New',
                'session' => $this->bookingSession,
                'required_attendees' => $this->skipEmailAndTeams ? null : $this->requiredAttendees,
                'remarks' => $this->remarks ?? null,
                'software_handover_id' => $softwareHandoverId,
                'event_id' => $teamsEventId,
                'meeting_link' => $meetingLink,
                'online_meeting_id' => $onlineMeetingId, // ✅ Add this
            ]);

            $appointment->save();

            // ✅ Log the final saved appointment details
            Log::info('✅ Step 4: Implementer appointment saved with meeting details', [
                'appointment_id' => $appointment->id,
                'event_id' => $teamsEventId,
                'online_meeting_id' => $onlineMeetingId,
                'meeting_link' => $meetingLink,
                'recording_enabled' => !empty($onlineMeetingId),
                'company' => $companyDetail->company_name,
                'session_type' => $this->implementationDemoType,
                'implementer' => $implementer->name
            ]);

            if(!$this->skipEmailAndTeams) {
                // Parse required attendees
                $recipients = [];
                $attendeeEmails = array_map('trim', explode(';', $this->requiredAttendees));
                foreach ($attendeeEmails as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $recipients[] = $email;
                    }
                }

                // Calculate implementation session count
                $implementationCount = $existingAppointmentsCount + 1;
                if ($this->implementationDemoType === 'KICK OFF MEETING SESSION') {
                    $implementationCount = 1;
                }

                // Format the date for day display
                $appointmentDate = Carbon::parse($this->bookingDate);
                $formattedDate = $appointmentDate->format('d F Y / l');

                try {
                    if (!empty($recipients)) {
                        // Use implementer's email as sender
                        $senderEmail = $implementer->email;
                        $senderName = $implementer->name;

                        // Select template based on appointment type
                        $emailTemplate = ($this->implementationDemoType === 'KICK OFF MEETING SESSION')
                            ? 'emails.implementer_appointment_notification'
                            : 'emails.implementation_session';

                        // Set the appropriate email content
                        $emailContent = [
                            'lead' => [
                                'implementerName' => $implementer->name,
                                'implementerEmail' => $implementer->email,
                                'company' => $companyDetail->company_name,
                                'date' => $this->bookingDate,
                                'startTime' => Carbon::parse($this->bookingStartTime)->format('H:i'),
                                'endTime' => Carbon::parse($this->bookingEndTime)->format('H:i'),
                                'demo_type' => $this->implementationDemoType,
                                'appointment_type' => $this->appointmentType,
                                'meetingLink' => $meetingLink,
                                'type' => $this->implementationDemoType,
                            ],
                            'appointmentType' => $this->appointmentType,
                            'type' => $this->implementationDemoType,
                            'date' => $this->bookingDate,
                            'startTime' => Carbon::parse($this->bookingStartTime)->format('H:i'),
                            'endTime' => Carbon::parse($this->bookingEndTime)->format('H:i'),
                            'meetingLink' => $meetingLink,
                            'companyName' => $companyDetail->company_name,
                            'implementerName' => $implementer->name,
                            'implementerEmail' => $implementer->email,
                            'remarks' => $this->remarks ?? null,
                        ];

                        if($this->implementationDemoType === 'REVIEW SESSION') {
                            $this->implementationDemoType = 'REVIEW SESSION';
                        }

                        \Illuminate\Support\Facades\Mail::send(
                            $emailTemplate,
                            ['content' => $emailContent],
                            function ($message) use ($recipients, $senderEmail, $senderName, $implementer, $companyDetail) {
                                $message->from($senderEmail, $senderName)
                                    ->to($recipients)
                                    ->cc([$senderEmail])
                                    ->subject("{$this->implementationDemoType} | {$companyDetail->company_name}");
                            }
                        );

                        Notification::make()
                            ->title('Session booked')
                            ->success()
                            ->body('Email notification sent to attendees')
                            ->send();
                    }
                } catch (\Exception $e) {
                    Log::error("Email sending failed: Error: {$e->getMessage()}");

                    Notification::make()
                        ->title('Session booked but email failed')
                        ->warning()
                        ->body('Error sending email: ' . $e->getMessage())
                        ->send();
                }
            }

            // Close modals and reset form
            $this->showImplementationSessionModal = false;
            $this->reset(['selectedCompany', 'appointmentType', 'requiredAttendees', 'remarks', 'implementationDemoType','companySearch']);

            // Refresh the calendar
            $this->dispatch('refresh');

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error booking session')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    public function cancelBooking()
    {
        // Close all modals
        $this->showBookingModal = false;
        $this->showImplementerRequestModal = false;
        $this->showImplementationSessionModal = false;
        $this->showOnsiteRequestModal = false;

        // Reset all form fields
        $this->reset([
            // Existing fields
            'selectedCompany',
            'appointmentType',
            'requiredAttendees',
            'remarks',
            'implementationDemoType',
            'requestSessionType',
            'selectedYear',
            'selectedWeek',

            // Onsite request fields
            'onsiteDayType',
            'onsiteCategory',
            'selectedCompany',
            'requiredAttendees',
            'onsiteRemarks',
            'selectedOnsiteSessions',
            'companySearch'
        ]);
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
                'end_time' => '12:00:00',
                'formatted_start' => '11:00 AM',
                'formatted_end' => '12:00 PM',
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

        // Friday has different schedule
        if (strtolower($dayOfWeek) === 'friday') {
            $standardSessions['SESSION 1'] = [
                'start_time' => '09:30:00',
                'end_time' => '10:30:00',
                'formatted_start' => '9:30 AM',
                'formatted_end' => '10:30 AM',
                'booked' => false,
                'appointment' => null,
                'status' => 'available',
                'time_period' => 'am'
            ];

            $standardSessions['SESSION 2'] = [
                'start_time' => '11:00:00',
                'end_time' => '12:00:00',
                'formatted_start' => '11:00 AM',
                'formatted_end' => '12:00 PM',
                'booked' => false,
                'appointment' => null,
                'status' => 'available',
                'time_period' => 'am'
            ];

            $standardSessions['SESSION 3'] = [
                'start_time' => '15:00:00',
                'end_time' => '16:00:00',
                'formatted_start' => '3:00 PM',
                'formatted_end' => '4:00 PM',
                'booked' => false,
                'appointment' => null,
                'status' => 'available',
                'time_period' => 'pm'
            ];

            $standardSessions['SESSION 4'] = [
                'start_time' => '16:30:00',
                'end_time' => '17:30:00',
                'formatted_start' => '4:30 PM',
                'formatted_end' => '5:30 PM',
                'booked' => false,
                'appointment' => null,
                'status' => 'available',
                'time_period' => 'pm'
            ];

            // Remove SESSION 5 completely for Friday
            unset($standardSessions['SESSION 5']);
        }

        // If a date is provided, we can check for public holidays and leaves
        if ($date && $implementerId) {
            $formattedDate = Carbon::parse($date)->format('Y-m-d');
            $currentDate = Carbon::now()->format('Y-m-d'); // Only compare dates, not time

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
                $leaves = UserLeave::where('user_id', $implementerId)
                    ->where('date', $formattedDate)
                    ->whereIn('status', ['Approved', 'Pending'])
                    ->get();

                if ($leaves->count() > 0) {
                    $hasAmLeave = $leaves->where('session', 'am')->count() > 0;
                    $hasPmLeave = $leaves->where('session', 'pm')->count() > 0;
                    $hasFullLeave = $leaves->where('session', 'full')->count() > 0;

                    if ($hasFullLeave || ($hasAmLeave && $hasPmLeave)) {
                        // Full day leave (either explicit full or both AM and PM) - all sessions unavailable
                        foreach ($standardSessions as $key => $session) {
                            $standardSessions[$key]['status'] = 'leave';
                        }
                    } elseif ($hasAmLeave) {
                        // AM leave only - Remove morning sessions entirely
                        foreach ($standardSessions as $key => $session) {
                            if ($session['time_period'] === 'am') {
                                unset($standardSessions[$key]);
                            }
                        }
                    } elseif ($hasPmLeave) {
                        // PM leave only - Remove afternoon sessions entirely
                        foreach ($standardSessions as $key => $session) {
                            if ($session['time_period'] === 'pm') {
                                unset($standardSessions[$key]);
                            }
                        }
                    }
                }
            }

            // **UPDATED LOGIC**: Check if the session is in the past based on DATE only
            foreach ($standardSessions as $key => $session) {
                // Check if there's a WEEKLY FOLLOW UP SESSION booked for this slot
                $hasWeeklyFollowUp = false;
                if ($user) {
                    $weeklyFollowUpAppointment = \App\Models\ImplementerAppointment::where('implementer', $user->name)
                        ->where('date', $formattedDate)
                        ->where('start_time', $session['start_time'])
                        ->where('type', 'WEEKLY FOLLOW UP SESSION')
                        ->where('status', '!=', 'Cancelled')
                        ->first();

                    if ($weeklyFollowUpAppointment) {
                        $hasWeeklyFollowUp = true;
                    }
                }

                // **NEW LOGIC**: Only mark as past if the DATE is before today
                // Compare dates only, not time
                if ($formattedDate < $currentDate && !$hasWeeklyFollowUp) {
                    $standardSessions[$key]['status'] = 'past';
                } else {
                    // Session is today or in the future - always available
                    $standardSessions[$key]['status'] = 'available';
                }
            }

            // Process any cancelled appointments
            if ($user) {
                $cancelledAppointments = \App\Models\ImplementerAppointment::where('implementer', $user->name)
                    ->where('date', $formattedDate)
                    ->where('status', 'Cancelled')
                    ->get();

                foreach ($cancelledAppointments as $appointment) {
                    $appointmentStartTime = Carbon::parse($appointment->start_time)->format('H:i:s');

                    foreach ($standardSessions as $key => $session) {
                        if ($session['start_time'] === $appointmentStartTime) {
                            // Special handling for WEEKLY FOLLOW UP SESSIONS
                            if ($appointment->type === 'WEEKLY FOLLOW UP SESSION') {
                                // For Weekly Follow Up Sessions, check if the DATE is before today
                                if ($formattedDate < $currentDate) {
                                    $standardSessions[$key]['status'] = 'past';
                                } else {
                                    $standardSessions[$key]['status'] = 'available';
                                }
                                $standardSessions[$key]['booked'] = false;
                                $standardSessions[$key]['appointment'] = null;
                                $standardSessions[$key]['wasCancelled'] = true;
                            }
                            // For other appointment types
                            else {
                                // **UPDATED**: If the DATE is before today, mark as past
                                if ($formattedDate < $currentDate) {
                                    $standardSessions[$key]['status'] = 'past';
                                } else {
                                    // Today or future cancelled appointment - show as available
                                    $standardSessions[$key]['status'] = 'available';
                                }

                                // Remove association with the cancelled appointment
                                $standardSessions[$key]['booked'] = false;
                                $standardSessions[$key]['appointment'] = null;
                                $standardSessions[$key]['wasCancelled'] = true;
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

    public function weekHasFollowUpSession($year, $week)
    {
        // Don't check if year or week is not set
        if (!$year || !$week) {
            return false;
        }

        // Get the implementer name for the current booking
        $implementerName = null;
        if ($this->bookingImplementerId) {
            $implementer = User::find($this->bookingImplementerId);
            if ($implementer) {
                $implementerName = $implementer->name;
            }
        }

        // If we don't have an implementer name, we can't check specifically
        if (!$implementerName) {
            return false;
        }

        // Check if this specific implementer already has a Weekly Follow-up Session for this week
        return \App\Models\ImplementerAppointment::where('type', 'WEEKLY FOLLOW UP SESSION')
            ->where('implementer', $implementerName)
            ->where('selected_year', $year)
            ->where('selected_week', $week)
            ->where('status', '!=', 'Cancelled')
            ->exists();
    }

    public function checkFutureSessions($companyName, $leadId = null)
    {
        try {
            // Get current date for comparison
            $currentDate = Carbon::now()->format('Y-m-d');

            // Query for future appointments with "New" status for this company
            $query = \App\Models\ImplementerAppointment::where('status', 'New')
                ->where('date', '>', $currentDate); // Only future dates

            // Filter by lead_id if available, otherwise by company name in title
            if ($leadId) {
                $query->where('lead_id', $leadId);
            } else {
                $query->where('title', 'LIKE', '%' . $companyName . '%');
            }

            $futureAppointments = $query->orderBy('date', 'asc')
                ->orderBy('start_time', 'asc')
                ->get();

            if ($futureAppointments->count() > 0) {
                // Format the future sessions for display
                $sessionsList = [];

                foreach ($futureAppointments as $index => $appointment) {
                    $appointmentDate = Carbon::parse($appointment->date);
                    $startTime = Carbon::parse($appointment->start_time);
                    $endTime = Carbon::parse($appointment->end_time);

                    // Determine which session this is based on start time
                    $sessionName = $this->determineSessionName($appointment->start_time, $appointmentDate->format('l'));

                    $sessionInfo = sprintf(
                        "%s/ DATE - %s / DAY - %s / %s: %s – %s",
                        ($index + 1),
                        $appointmentDate->format('j F Y'),
                        strtoupper($appointmentDate->format('l')),
                        $sessionName,
                        $startTime->format('g:iA'),
                        $endTime->format('g:iA')
                    );

                    $sessionsList[] = $sessionInfo;
                }

                // Show notification with future sessions
                $notificationBody = "FUTURE SESSION:\n" . implode("\n", $sessionsList);

                Notification::make()
                    ->title('Upcoming Sessions Found')
                    ->info()
                    ->body($notificationBody)
                    ->duration(8000) // Show for 8 seconds
                    ->send();
            }

        } catch (\Exception $e) {
            Log::error("Error checking future sessions: " . $e->getMessage());
        }
    }

    private function determineSessionName($startTime, $dayOfWeek)
    {
        $time = Carbon::parse($startTime)->format('H:i:s');
        $dayOfWeek = strtolower($dayOfWeek);

        // Define session mappings based on day and time
        if ($dayOfWeek === 'friday') {
            // Friday schedule
            switch ($time) {
                case '09:30:00':
                    return 'SESSION 1';
                case '11:00:00':
                    return 'SESSION 2';
                case '15:00:00':
                    return 'SESSION 3';
                case '16:30:00':
                    return 'SESSION 4';
                default:
                    return 'CUSTOM SESSION';
            }
        } else {
            // Monday-Thursday schedule
            switch ($time) {
                case '09:30:00':
                    return 'SESSION 1';
                case '11:00:00':
                    return 'SESSION 2';
                case '14:00:00':
                    return 'SESSION 3';
                case '15:30:00':
                    return 'SESSION 4';
                case '17:00:00':
                    return 'SESSION 5';
                default:
                    return 'CUSTOM SESSION';
            }
        }
    }

    public function getFutureSessionsForCompany($companyName, $leadId = null)
    {
        try {
            // Get current date for comparison
            $currentDate = Carbon::now()->format('Y-m-d');

            // Query for future appointments with "New" status for this company
            $query = \App\Models\ImplementerAppointment::where('status', 'New')
                ->where('date', '>', $currentDate); // Only future dates

            // Filter by lead_id if available, otherwise by company name in title
            if ($leadId) {
                $query->where('lead_id', $leadId);
            } else {
                $query->where('title', 'LIKE', '%' . $companyName . '%');
            }

            $futureAppointments = $query->orderBy('date', 'asc')
                ->orderBy('start_time', 'asc')
                ->get();

            $sessionsList = [];

            foreach ($futureAppointments as $appointment) {
                $appointmentDate = Carbon::parse($appointment->date);
                $startTime = Carbon::parse($appointment->start_time);
                $endTime = Carbon::parse($appointment->end_time);

                // Determine which session this is based on start time
                $sessionName = $this->determineSessionName($appointment->start_time, $appointmentDate->format('l'));

                $sessionsList[] = [
                    'formatted_date' => $appointmentDate->format('j F Y'),
                    'day' => strtoupper($appointmentDate->format('l')),
                    'session_name' => $sessionName,
                    'time_range' => $startTime->format('g:iA') . ' – ' . $endTime->format('g:iA'),
                    'type' => $appointment->type ?? 'N/A',
                    'implementer' => $appointment->implementer ?? 'N/A'
                ];
            }

            return $sessionsList;

        } catch (\Exception $e) {
            Log::error("Error getting future sessions for company: " . $e->getMessage());
            return [];
        }
    }
}
