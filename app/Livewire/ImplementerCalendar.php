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

    public array $appointmentTypes = [
        "KICK OFF MEETING SESSION",
        "IMPLEMENTATION REVIEW SESSION",
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
    public $implementationDemoType = 'IMPLEMENTATION REVIEW SESSION';
    public $filteredOpenDelayCompanies = [];
    public $showAppointmentDetailsModal = false;
    public $currentAppointment = null;

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
        $appointment = \App\Models\ImplementerAppointment::find($appointmentId);

        if (!$appointment) {
            Notification::make()
                ->title('Appointment not found')
                ->danger()
                ->send();
            return;
        }

        try {
            // Update status to Cancelled
            $appointment->status = 'Cancelled';
            $appointment->save();

            // Create activity log entry
            ActivityLog::create([
                'user_id' => auth()->id(),
                'causer_id' => auth()->id(),
                'action' => 'Cancelled Implementer Session',
                'description' => "Cancelled {$appointment->type} for " .
                    ($appointment->lead_id ? $appointment->company_name : $appointment->title) .
                    " with {$appointment->implementer}",
                'subject_type' => get_class($appointment),
                'subject_id' => $appointment->id,
            ]);

            // Send email notification about cancellation
            $this->sendCancellationEmail($appointment);

            Notification::make()
                ->title('Appointment cancelled successfully')
                ->success()
                ->send();

            // Close modal and refresh calendar
            $this->closeAppointmentDetails();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error cancelling appointment')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
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

            // Always include admin
            $recipients[] = 'zilih.ng@timeteccloud.com';

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
                'cancelledBy' => $authUser->name,
                'cancelledDate' => Carbon::now()->format('d F Y g:i A'),
                'remarks' => $appointment->remarks ?? 'N/A'
            ];

            if (count($recipients) > 0) {
                \Illuminate\Support\Facades\Mail::send(
                    'emails.implementer_appointment_cancelled',
                    ['content' => $emailData],
                    function ($message) use ($recipients, $senderEmail, $senderName, $companyName, $appointment) {
                        $message->from($senderEmail, $senderName)
                            ->to($recipients)
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

        // Initialize counters with proper capitalization matching appointmentTypes array
        $this->totalAppointments = [
            "ALL" => 0,
            "Kick Off Meeting Session" => 0,
            "Implementation Review Session" => 0,
            "Data Migration Session" => 0,
            "System Setting Session" => 0,
            "Weekly Follow Up Session" => 0
        ];

        // Count active appointments (not cancelled)
        $this->totalAppointments["ALL"] = $query->clone()->where('status', '!=', 'Cancelled')->count();
        $this->totalAppointmentsStatus["ALL"] = $query->clone()->count();

        // Count by appointment type with proper capitalization
        $this->totalAppointments["Kick Off Meeting Session"] = $query->clone()
            ->where('type', 'Kick Off Meeting Session')
            ->where('status', '!=', 'Cancelled')->count();

        $this->totalAppointments["Implementation Review Session"] = $query->clone()
            ->where('type', 'Implementation Review Session')
            ->where('status', '!=', 'Cancelled')->count();

        $this->totalAppointments["Data Migration Session"] = $query->clone()
            ->where('type', 'Data Migration Session')
            ->where('status', '!=', 'Cancelled')->count();

        $this->totalAppointments["System Setting Session"] = $query->clone()
            ->where('type', 'System Setting Session')
            ->where('status', '!=', 'Cancelled')->count();

        $this->totalAppointments["Weekly Follow Up Session"] = $query->clone()
            ->where('type', 'Weekly Follow Up Session')
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
                                if ($appointment->request_status === 'PENDING APPROVAL') {
                                    // Yellow for pending implementer requests (including Weekly Follow Up)
                                    $data[$daySessionSlots][$sessionName]['status'] = 'implementer_request';
                                } else if ($appointment->type === 'WEEKLY FOLLOW UP SESSION' && !$appointment->lead_id) {
                                    // Special coloring for approved weekly follow up sessions without lead_id
                                    $data[$daySessionSlots][$sessionName]['status'] = 'weekly_followup';
                                } else {
                                    // Red for regular implementation sessions
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
            'IMPLEMENTATION REVIEW SESSION' => 0,
            'DATA MIGRATION SESSION' => 0,
            'SYSTEM SETTING SESSION' => 0,
            'WEEKLY FOLLOW UP SESSION' => 0,
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
        $this->requestSessionType = '';
        $this->implementationDemoType = 'IMPLEMENTATION REVIEW SESSION';

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

        // Show the session type selection modal
        $this->showBookingModal = true;

        // Filter companies to only show open/delay projects
        $this->updateOpenDelayCompanies();
    }

    private function updateOpenDelayCompanies()
    {
        // Get companies with 'Open' or 'Delay' status from software handover
        $this->filteredOpenDelayCompanies = \App\Models\CompanyDetail::join('leads', 'company_details.lead_id', '=', 'leads.id')
            ->join('software_handovers', 'leads.id', '=', 'software_handovers.lead_id')
            ->whereIn('software_handovers.status_handover', ['Open', 'Delay'])
            ->orderBy('company_details.company_name')
            ->pluck('company_details.company_name', 'company_details.id')
            ->toArray();
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
            $this->showImplementerRequestModal = true;
        } else {
            $this->showImplementationSessionModal = true;
        }
    }

    // Add method to handle session type change in implementer request
    public function onRequestSessionTypeChange()
    {
        if ($this->requestSessionType === 'WEEKLY FOLLOW UP SESSION') {
            $this->updateAvailableWeeks();
        } else {
            $this->updateOpenDelayCompanies();
        }
    }

    // Add method to load attendees from software handover
    // public function loadAttendees()
    // {
    //     if (!$this->selectedCompany) {
    //         Notification::make()
    //             ->title('Please select a company first')
    //             ->warning()
    //             ->send();
    //         return;
    //     }

    //     try {
    //         // Get implementation PICs from software handover form
    //         $companyDetail = \App\Models\CompanyDetail::find($this->selectedCompany);
    //         if (!$companyDetail || !$companyDetail->lead_id) {
    //             return;
    //         }

    //         $lead = \App\Models\Lead::find($companyDetail->lead_id);
    //         if (!$lead) {
    //             return;
    //         }

    //         // Here you would fetch the emails from the software handover form
    //         // This is a placeholder - adjust based on your actual data structure
    //         $implementationPics = \App\Models\ImplementationPic::where('lead_id', $lead->id)->get();

    //         $emails = [];
    //         foreach ($implementationPics as $pic) {
    //             if (!empty($pic->email)) {
    //                 $emails[] = $pic->email;
    //             }
    //         }

    //         $this->requiredAttendees = implode(';', $emails);

    //         if (empty($emails)) {
    //             Notification::make()
    //                 ->title('No implementation PICs found')
    //                 ->warning()
    //                 ->body('Please enter attendees manually')
    //                 ->send();
    //         } else {
    //             Notification::make()
    //                 ->title('Attendees loaded successfully')
    //                 ->success()
    //                 ->send();
    //         }
    //     } catch (\Exception $e) {
    //         Notification::make()
    //             ->title('Error loading attendees')
    //             ->danger()
    //             ->body($e->getMessage())
    //             ->send();
    //     }
    // }

    public function submitImplementerRequest()
    {
        if ($this->requestSessionType === 'WEEKLY FOLLOW UP SESSION') {
            $this->validate([
                'requestSessionType' => 'required|string',
                'selectedYear' => 'required|integer',
                'selectedWeek' => 'required|integer|min:1|max:53',
            ]);
        } else {
            $this->validate([
                'requestSessionType' => 'required|string',
                'selectedCompany' => 'required|exists:company_details,id',
            ]);
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
            // Prepare data
            $leadId = null;
            $companyName = '';
            $softwareHandoverId = '';
            $title = '';
            $selectedYear = null;
            $selectedWeek = null;

            if ($this->requestSessionType !== 'WEEKLY FOLLOW UP SESSION') {
                $companyDetail = \App\Models\CompanyDetail::find($this->selectedCompany);
                if (!$companyDetail) {
                    Notification::make()
                        ->title('Company not found')
                        ->danger()
                        ->send();
                    return;
                }
                $companyName = $companyDetail->company_name;
                $softwareHandoverId = 'SW_' . str_pad($companyDetail->id, 6, '0', STR_PAD_LEFT);
                $leadId = $companyDetail->lead_id;
                $title = $this->requestSessionType . ' | IMPLEMENTER REQUEST | ' . $companyName;
            } else {
                $title = $this->requestSessionType . ' | IMPLEMENTER REQUEST | WEEK ' . $this->selectedWeek . ', ' . $this->selectedYear;
                $selectedYear = $this->selectedYear;
                $selectedWeek = $this->selectedWeek;
            }

            // Create appointment record with request_status
            $appointment = new \App\Models\ImplementerAppointment();
            $appointment->fill([
                'lead_id' => $leadId,
                'type' => $this->requestSessionType,
                'appointment_type' => 'ONLINE', // Default to ONLINE for requests
                'date' => $this->bookingDate,
                'start_time' => $this->bookingStartTime,
                'end_time' => $this->bookingEndTime,
                'implementer' => $implementer->name,
                'causer_id' => auth()->user()->id,
                'implementer_assigned_date' => now(),
                'title' => $title,
                'status' => 'New',
                'request_status' => 'PENDING APPROVAL',
                'selected_year' => $selectedYear,
                'selected_week' => $selectedWeek,
                'session' => $this->bookingSession,
                'remarks' => $this->requestSessionType !== 'WEEKLY FOLLOW UP SESSION' ?
                            "Request for {$this->requestSessionType} for {$companyName}" :
                            "Request for {$this->requestSessionType} for Week {$this->selectedWeek}, {$this->selectedYear}",
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
                'status' => 'PENDING APPROVAL',
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
                // $recipients = ['fazuliana.mohdarsad@timeteccloud.com']; // Main recipient
                $recipients = ['zilih.ng@timeteccloud.com']; // Main recipient
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
            $this->reset(['requestSessionType', 'selectedCompany', 'selectedYear', 'selectedWeek']);

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
        $this->validate([
            'selectedCompany' => 'required|exists:company_details,id',
            'implementationDemoType' => 'required|string',
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

        try {
            // Count existing appointments for this company to determine implementation count
            $existingAppointmentsCount = \App\Models\ImplementerAppointment::where('lead_id', $leadId)
                ->where('status', '!=', 'Cancelled')
                ->count();

            // Create the appointment
            $appointment = new \App\Models\ImplementerAppointment();
            $appointment->fill([
                'lead_id' => $leadId,
                'type' => $this->implementationDemoType,
                'appointment_type' => $this->appointmentType,
                'date' => $this->bookingDate,
                'start_time' => $this->bookingStartTime,
                'end_time' => $this->bookingEndTime,
                'implementer' => $implementer->name,
                'causer_id' => auth()->user()->id,
                'implementer_assigned_date' => now(),
                'title' => $this->implementationDemoType . ' | ' . $this->appointmentType . ' | TIMETEC IMPLEMENTER | ' . $companyDetail->company_name,
                'status' => 'New',
                'session' => $this->bookingSession,
                'required_attendees' => $this->requiredAttendees,
                'remarks' => $this->remarks,
            ]);

            $appointment->save();

            // Get lead owner details
            $lead = \App\Models\Lead::find($leadId);
            if (!$lead) {
                Notification::make()
                    ->title('Error preparing email notification')
                    ->danger()
                    ->body('Lead record not found')
                    ->send();
                return;
            }

            $leadOwner = User::where('name', $lead->lead_owner)->first();

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
                $implementationCount = 1; // Always count 1 for kick-off meeting
            }

            // Prepare email content
            $emailContent = [
                'implementerName' => $implementer->name,
                'implementerEmail' => $implementer->email,
                'companyName' => $companyDetail->company_name,
                'implementationCount' => $implementationCount,
                'appointmentType' => $this->appointmentType,
                'date' => Carbon::parse($this->bookingDate)->format('d F Y / l'),
                'sessionTime' => "{$this->bookingSession}: " .
                                Carbon::parse($this->bookingStartTime)->format('h:iA') . ' – ' .
                                Carbon::parse($this->bookingEndTime)->format('h:iA'),
                'meetingLink' => '', // You'll need to provide the meeting link if available
                'meetingId' => '',   // You'll need to provide the meeting ID if available
                'meetingPassword' => '', // You'll need to provide the meeting password if available
                'leadOwnerMobileNumber' => $leadOwner ? $leadOwner->mobile_number : 'N/A',
                'remarks' => $this->remarks,
            ];

            // Send email
            try {
                if (!empty($recipients)) {
                    $authUser = auth()->user();
                    $senderEmail = $authUser->email;
                    $senderName = $authUser->name;

                    \Illuminate\Support\Facades\Mail::send('emails.implementation_session',
                        ['content' => $emailContent],
                        function ($message) use ($recipients, $senderEmail, $senderName, $companyDetail) {
                            $message->from($senderEmail, $senderName)
                                ->to($recipients)
                                ->subject("TIMETEC HR | {$this->implementationDemoType} | {$companyDetail->company_name}");
                        }
                    );

                    Notification::make()
                        ->title('Implementation session booked')
                        ->success()
                        ->body('Email notification sent to attendees')
                        ->send();
                }
            } catch (\Exception $e) {
                Log::error("Email sending failed for implementation session: Error: {$e->getMessage()}");

                Notification::make()
                    ->title('Session booked but email failed')
                    ->warning()
                    ->body('Error sending email: ' . $e->getMessage())
                    ->send();
            }

            // Log activity
            ActivityLog::create([
                'user_id' => auth()->id(),
                'causer_id' => auth()->id(),
                'action' => 'Booked Implementation Session',
                'description' => "Booked {$this->implementationDemoType} for {$companyDetail->company_name} with {$implementer->name}",
                'subject_type' => get_class($appointment),
                'subject_id' => $appointment->id,
            ]);

            // Close modals and reset form
            $this->showImplementationSessionModal = false;
            $this->reset(['selectedCompany', 'appointmentType', 'requiredAttendees', 'remarks', 'implementationDemoType']);

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
            $implementationType = 'IMPLEMENTATION REVIEW SESSION';
        } else if ($existingAppointmentsCount == 2) {
            $implementationType = 'DATA MIGRATION SESSION';
        } else if ($existingAppointmentsCount == 3) {
            $implementationType = 'SYSTEM SETTING SESSION';
        } else if ($existingAppointmentsCount >= 4) {
            $implementationType = 'WEEKLY FOLLOW UP SESSION';
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
        $this->showImplementerRequestModal = false;
        $this->showImplementationSessionModal = false;
        $this->reset(['selectedCompany', 'appointmentType', 'requiredAttendees', 'remarks', 'implementationDemoType', 'requestSessionType', 'selectedYear', 'selectedWeek']);
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
                            // Get the session start time as Carbon object
                            $sessionStartDateTime = Carbon::parse($formattedDate . ' ' . $session['start_time']);

                            // If the current day is past the session day, mark as past
                            if (Carbon::now()->format('Y-m-d') > $formattedDate) {
                                $standardSessions[$key]['status'] = 'past';
                            }
                            // If the session is in the past on the same day, mark as past
                            elseif (Carbon::now()->format('Y-m-d') === $formattedDate && Carbon::now() > $sessionStartDateTime) {
                                $standardSessions[$key]['status'] = 'past';
                            }
                            // If the session is still in the future, make it available
                            else {
                                $standardSessions[$key]['status'] = 'available';
                                // Important: Remove any association with the cancelled appointment
                                $standardSessions[$key]['booked'] = false;
                                $standardSessions[$key]['appointment'] = null;
                                // Add explicit wasCancelled flag here
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
}
