<?php
namespace App\Livewire;

use App\Models\PublicHoliday;
use App\Models\User;
use App\Models\UserLeave;
use App\Models\ImplementerAppointment;
use App\Models\SoftwareHandover;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class CustomerCalendar extends Component
{
    public $currentDate;
    public $monthlyData = [];
    public $assignedImplementer = null;
    public $customerLeadId;
    public $swId;
    public $hasExistingBooking = false;
    public $existingBookings = [];

    // Booking modal properties
    public $showBookingModal = false;
    public $selectedDate;
    public $selectedSession;
    public $availableSessions = [];
    public $appointmentType = 'ONLINE';
    public $requiredAttendees = '';
    public $remarks = '';

    // Success modal properties
    public $showSuccessModal = false;
    public $submittedBooking = null;

    public $showCancelModal = false;
    public $appointmentToCancel = null;

    public function mount()
    {
        $this->currentDate = Carbon::now();
        $this->customerLeadId = auth()->guard('customer')->user()->lead_id;
        $this->swId = auth()->guard('customer')->user()->sw_id;
        $this->assignedImplementer = $this->getAssignedImplementer();
        $this->checkExistingBookings();
    }

    public function checkExistingBookings()
    {
        $customer = auth()->guard('customer')->user();

        // Use lead_id instead of customer_id
        $this->existingBookings = ImplementerAppointment::where('lead_id', $customer->lead_id)
            ->whereIn('status', ['New', 'Done'])
            ->where('type', 'KICK OFF MEETING SESSION')
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'date' => Carbon::parse($booking->date)->format('l, d F Y'),
                    'time' => Carbon::parse($booking->start_time)->format('g:i A') . ' - ' .
                            Carbon::parse($booking->end_time)->format('g:i A'),
                    'implementer' => $booking->implementer,
                    'session' => $booking->session,
                    'status' => $booking->status,
                    'request_status' => $booking->request_status,
                    'appointment_type' => $booking->appointment_type,
                    'raw_date' => $booking->date,
                    'start_time' => $booking->start_time, // Add this line
                    'end_time' => $booking->end_time,     // Add this line too for consistency
                ];
            })
            ->toArray();

        $this->hasExistingBooking = count($this->existingBookings) > 0;
    }

    private function sendCancellationNotification($appointment)
    {
        try {
            $customer = auth()->guard('customer')->user();

            // Email content for cancellation using the existing template structure
            $emailData = [
                'content' => [
                    'appointmentType' => $appointment->appointment_type,
                    'companyName' => $customer->company_name,
                    'date' => Carbon::parse($appointment->date)->format('l, d F Y'),
                    'time' => Carbon::parse($appointment->start_time)->format('g:i A') . ' - ' .
                            Carbon::parse($appointment->end_time)->format('g:i A'),
                    'implementer' => $appointment->implementer,
                    'session' => $appointment->session,
                    'cancelledAt' => now()->format('d F Y, g:i A'),
                    'cancelledBy' => 'Customer',
                    'customerName' => $customer->name,
                    'customerEmail' => $customer->email,
                    'requiredAttendees' => $appointment->required_attendees,
                    'meetingLink' => $appointment->meeting_link,
                    'eventId' => $appointment->event_id,
                ]
            ];

            // Build primary recipients list (TO field) - same as booking notification
            $recipients = [];

            // // Add customer email
            // if ($customer->email && filter_var($customer->email, FILTER_VALIDATE_EMAIL)) {
            //     $recipients[] = $customer->email;
            // }

            // Add all required attendees
            if ($appointment->required_attendees) {
                $attendeeEmails = array_map('trim', explode(';', $appointment->required_attendees));
                foreach ($attendeeEmails as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL) && !in_array($email, $recipients)) {
                        $recipients[] = $email;
                    }
                }
            }

            // Build CC recipients list (same as booking notification)
            $ccRecipients = [];

            // Add the assigned implementer to CC
            $implementer = User::where('name', $appointment->implementer)->first();
            $implementerEmail = $implementer ? $implementer->email : '';
            if ($implementerEmail && !in_array($implementerEmail, $ccRecipients)) {
                $ccRecipients[] = $implementerEmail;
            }

            // Add the salesperson to CC
            $lead = \App\Models\Lead::find($customer->lead_id);
            if ($lead && $lead->salesperson) {
                $salespersonEmail = $lead->getSalespersonEmail();
                if ($salespersonEmail && !in_array($salespersonEmail, $ccRecipients)) {
                    $ccRecipients[] = $salespersonEmail;
                }
            }

            // Remove duplicates and filter valid emails
            $recipients = array_unique(array_filter($recipients, function($email) {
                return filter_var($email, FILTER_VALIDATE_EMAIL);
            }));

            $ccRecipients = array_unique(array_filter($ccRecipients, function($email) {
                return filter_var($email, FILTER_VALIDATE_EMAIL);
            }));

            if (empty($recipients)) {
                throw new \Exception('No valid email recipients found');
            }

            // Get implementer details for sender (same as booking notification)
            $implementerName = $appointment->implementer;

            \Illuminate\Support\Facades\Mail::send(
                'emails.implementer_appointment_cancel',
                $emailData,
                function ($message) use ($recipients, $ccRecipients, $customer, $implementerEmail, $implementerName, $appointment) {
                    $message->from($implementerEmail ?: 'noreply@timeteccloud.com', $implementerName ?: 'TimeTec Implementation Team')
                            ->to($recipients) // Primary recipients (customer + attendees)
                            ->cc($ccRecipients) // CC implementer + salesperson
                            ->subject("CANCELLED BY CUSTOMER: TIMETEC HR | {$appointment->appointment_type} | {$customer->company_name}");
                }
            );

            Log::info('Cancellation notification sent successfully', [
                'sender' => $implementerEmail ?: 'noreply@timeteccloud.com',
                'sender_name' => $implementerName ?: 'TimeTec Implementation Team',
                'to_recipients' => $recipients,
                'cc_recipients' => $ccRecipients,
                'customer' => $customer->company_name,
                'appointment_id' => $appointment->id,
                'template' => 'implementer_appointment_cancel',
                'total_to_recipients' => count($recipients),
                'total_cc_recipients' => count($ccRecipients),
                'salesperson_included' => $lead && $lead->getSalespersonEmail() ? 'yes' : 'no'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send cancellation notification: ' . $e->getMessage(), [
                'appointment_id' => $appointment->id,
                'template' => 'implementer_appointment_cancel',
                'customer' => $customer->company_name ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function getAssignedImplementer()
    {
        if (!$this->customerLeadId) {
            return null;
        }

        // Get the latest software handover for this lead
        $handover = SoftwareHandover::where('lead_id', $this->customerLeadId)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$handover || !$handover->implementer) {
            return null;
        }

        // Find implementer by name
        $implementer = User::where('name', $handover->implementer)
            ->whereIn('role_id', [4, 5]) // Only implementer roles
            ->first();

        if (!$implementer) {
            return null;
        }

        return [
            'id' => $implementer->id,
            'name' => $implementer->name,
            'avatar_path' => $implementer->avatar_path,
        ];
    }

    public function previousMonth()
    {
        $this->currentDate = $this->currentDate->copy()->subMonth();
    }

    public function nextMonth()
    {
        $this->currentDate = $this->currentDate->copy()->addMonth();
    }

    public function openCancelModal($bookingId)
    {
        $booking = collect($this->existingBookings)->firstWhere('id', $bookingId);

        if (!$booking) {
            Notification::make()
                ->title('Booking not found')
                ->danger()
                ->send();
            return;
        }

        $this->appointmentToCancel = $booking;
        $this->showCancelModal = true;
    }

    public function closeCancelModal()
    {
        $this->showCancelModal = false;
        $this->appointmentToCancel = null;
    }

    public function confirmCancelAppointment()
    {
        if (!$this->appointmentToCancel) {
            return;
        }

        try {
            $appointment = ImplementerAppointment::find($this->appointmentToCancel['id']);

            if (!$appointment) {
                Notification::make()
                    ->title('Appointment not found')
                    ->danger()
                    ->send();
                return;
            }

            // Double-check cancellation rules
            $appointmentDate = Carbon::parse($appointment->date)->format('Y-m-d');
            $appointmentDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $appointmentDate . ' ' . $appointment->start_time);
            $now = Carbon::now();

            if (!($appointmentDateTime->isFuture() || ($appointmentDateTime->isToday() && $appointmentDateTime->gt($now)))) {
                Notification::make()
                    ->title('Cannot cancel appointment')
                    ->danger()
                    ->body('This appointment can no longer be cancelled.')
                    ->send();
                return;
            }

            // Cancel the Teams meeting if it exists
            if ($appointment->event_id) {
                try {
                    $this->cancelTeamsMeeting($appointment);
                    Log::info('Teams meeting cancelled successfully', [
                        'appointment_id' => $appointment->id,
                        'event_id' => $appointment->event_id
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to cancel Teams meeting: ' . $e->getMessage(), [
                        'appointment_id' => $appointment->id,
                        'event_id' => $appointment->event_id
                    ]);
                    // Continue with appointment cancellation even if Teams cancellation fails
                }
            }

            // Update appointment status to cancelled
            $appointment->update([
                'status' => 'Cancelled',
                'request_status' => 'CANCELLED',
                'remarks' => ($appointment->remarks ? $appointment->remarks . ' | ' : '') . 'CANCELLED BY CUSTOMER on ' . now()->format('d M Y H:i:s')
            ]);

            // Send cancellation notification email using existing template
            $this->sendCancellationNotification($appointment);

            // Close modal and refresh bookings
            $this->closeCancelModal();
            $this->checkExistingBookings();

            Notification::make()
                ->title('Appointment cancelled successfully')
                ->success()
                ->body('Your appointment and Teams meeting have been cancelled. Cancellation notifications sent to all attendees, implementer, and salesperson.')
                ->send();

        } catch (\Exception $e) {
            Log::error('Failed to cancel appointment: ' . $e->getMessage());

            Notification::make()
                ->title('Cancellation failed')
                ->danger()
                ->body('There was an error cancelling your appointment. Please contact support.')
                ->send();
        }
    }

    private function cancelTeamsMeeting($appointment)
    {
        try {
            // Get implementer details
            $implementer = User::where('name', $appointment->implementer)->first();
            if (!$implementer || !$implementer->email) {
                throw new \Exception('Implementer not found for Teams meeting cancellation');
            }

            // Cancel Teams meeting through Microsoft Graph API
            $accessToken = \App\Services\MicrosoftGraphService::getAccessToken();
            $graph = new \Microsoft\Graph\Graph();
            $graph->setAccessToken($accessToken);

            // Delete the event from the implementer's calendar
            $organizerEmail = $implementer->email;
            $eventId = $appointment->event_id;

            $graph->createRequest("DELETE", "/users/$organizerEmail/events/$eventId")
                ->execute();

            Log::info('Teams meeting cancelled successfully', [
                'appointment_id' => $appointment->id,
                'event_id' => $eventId,
                'implementer' => $implementer->name,
                'organizer_email' => $organizerEmail
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to cancel Teams meeting: ' . $e->getMessage(), [
                'appointment_id' => $appointment->id,
                'event_id' => $appointment->event_id ?? 'none',
                'implementer' => $appointment->implementer ?? 'unknown'
            ]);
            throw $e;
        }
    }

    public function openBookingModal($date)
    {
        // Check if customer already has a booking
        if ($this->hasExistingBooking) {
            Notification::make()
                ->title('Booking already exists')
                ->warning()
                ->body('You already have a scheduled kick-off meeting. Please contact support if you need to reschedule.')
                ->send();
            return;
        }

        // Only allow booking for future dates
        if (Carbon::parse($date)->isPast()) {
            Notification::make()
                ->title('Cannot book past dates')
                ->warning()
                ->body('Please select a future date for your appointment.')
                ->send();
            return;
        }

        if (!$this->assignedImplementer) {
            Notification::make()
                ->title('No implementer assigned')
                ->warning()
                ->body('Please contact support to assign an implementer to your account.')
                ->send();
            return;
        }

        $this->selectedDate = $date;
        $this->availableSessions = $this->getAvailableSessionsForDate($date);

        if (empty($this->availableSessions)) {
            Notification::make()
                ->title('No available sessions')
                ->warning()
                ->body('There are no available sessions for this date.')
                ->send();
            return;
        }

        // Retrieve required attendees from software handover
        $this->requiredAttendees = $this->getRequiredAttendeesFromHandover();

        $this->showBookingModal = true;
    }

    private function getRequiredAttendeesFromHandover()
    {
        try {
            // Get the latest software handover for this customer's lead
            $handover = SoftwareHandover::where('id', $this->swId)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$handover || !$handover->implementation_pics) {
                return '';
            }

            // Decode the implementation_pics JSON
            $implementationPics = [];
            if (is_string($handover->implementation_pics)) {
                $implementationPics = json_decode($handover->implementation_pics, true) ?? [];
            } elseif (is_array($handover->implementation_pics)) {
                $implementationPics = $handover->implementation_pics;
            }

            // Extract valid email addresses
            $emails = [];
            foreach ($implementationPics as $pic) {
                if (isset($pic['pic_email_impl']) && !empty($pic['pic_email_impl'])) {
                    $email = trim($pic['pic_email_impl']);
                    if (filter_var($email, FILTER_VALIDATE_EMAIL) && !in_array($email, $emails)) {
                        $emails[] = $email;
                    }
                }
            }

            // Return emails separated by semicolons
            return implode(';', $emails);

        } catch (\Exception $e) {
            Log::error('Error retrieving required attendees from handover: ' . $e->getMessage(), [
                'lead_id' => $this->customerLeadId,
                'trace' => $e->getTraceAsString()
            ]);
            return '';
        }
    }

    public function getAvailableSessionsForDate($date)
    {
        $availableSessions = [];
        $dayOfWeek = strtolower(Carbon::parse($date)->format('l'));

        // Calculate booking window - only allow next 3 weeks from today
        $today = Carbon::today();
        $maxBookingDate = $today->copy()->addWeeks(3)->endOfWeek(); // End of third week
        $requestedDate = Carbon::parse($date);

        // Block if date is beyond 3 weeks from today
        if ($requestedDate->gt($maxBookingDate)) {
            return [];
        }

        // Skip weekends
        if (in_array($dayOfWeek, ['saturday', 'sunday'])) {
            return [];
        }

        // Check for public holidays
        $isPublicHoliday = PublicHoliday::where('date', $date)->exists();
        if ($isPublicHoliday) {
            return [];
        }

        // Only check assigned implementer
        if (!$this->assignedImplementer) {
            return [];
        }

        $implementer = User::find($this->assignedImplementer['id']);
        if (!$implementer) {
            return [];
        }

        // Check for leave
        $hasLeave = UserLeave::where('user_id', $implementer->id)
            ->where('date', $date)
            ->whereIn('status', ['Approved', 'Pending'])
            ->exists();

        if ($hasLeave) {
            return [];
        }

        // Define session slots
        $sessionSlots = $this->getSessionSlots($dayOfWeek);

        foreach ($sessionSlots as $sessionName => $sessionData) {
            // Check if slot is already booked
            $isBooked = ImplementerAppointment::where('implementer', $implementer->name)
                ->where('date', $date)
                ->where('start_time', $sessionData['start_time'])
                ->where('status', '!=', 'Cancelled')
                ->exists();

            if (!$isBooked) {
                $availableSessions[] = [
                    'implementer_id' => $implementer->id,
                    'implementer_name' => $implementer->name,
                    'session_name' => $sessionName,
                    'start_time' => $sessionData['start_time'],
                    'end_time' => $sessionData['end_time'],
                    'formatted_time' => $sessionData['formatted_start'] . ' - ' . $sessionData['formatted_end']
                ];
            }
        }

        return $availableSessions;
    }

    private function getSessionSlots($dayOfWeek)
    {
        $standardSessions = [
            'SESSION 1' => [
                'start_time' => '09:30:00',
                'end_time' => '10:30:00',
                'formatted_start' => '9:30 AM',
                'formatted_end' => '10:30 AM',
            ],
            'SESSION 2' => [
                'start_time' => '11:00:00',
                'end_time' => '12:00:00',
                'formatted_start' => '11:00 AM',
                'formatted_end' => '12:00 PM',
            ],
            'SESSION 3' => [
                'start_time' => '14:00:00',
                'end_time' => '15:00:00',
                'formatted_start' => '2:00 PM',
                'formatted_end' => '3:00 PM',
            ],
            'SESSION 4' => [
                'start_time' => '15:30:00',
                'end_time' => '16:30:00',
                'formatted_start' => '3:30 PM',
                'formatted_end' => '4:30 PM',
            ],
            'SESSION 5' => [
                'start_time' => '17:00:00',
                'end_time' => '18:00:00',
                'formatted_start' => '5:00 PM',
                'formatted_end' => '6:00 PM',
            ],
        ];

        // Friday has different schedule
        if ($dayOfWeek === 'friday') {
            $standardSessions['SESSION 3'] = [
                'start_time' => '15:00:00',
                'end_time' => '16:00:00',
                'formatted_start' => '3:00 PM',
                'formatted_end' => '4:00 PM',
            ];
            $standardSessions['SESSION 4'] = [
                'start_time' => '16:30:00',
                'end_time' => '17:30:00',
                'formatted_start' => '4:30 PM',
                'formatted_end' => '5:30 PM',
            ];
            unset($standardSessions['SESSION 5']);
        }

        return $standardSessions;
    }

    public function selectSession($sessionIndex)
    {
        if (isset($this->availableSessions[$sessionIndex])) {
            $session = $this->availableSessions[$sessionIndex];
            $this->selectedSession = $session;
        }
    }

    public function submitBooking()
    {
        // Double check if customer already has a booking
        if ($this->hasExistingBooking) {
            Notification::make()
                ->title('Booking already exists')
                ->danger()
                ->body('You already have a scheduled meeting.')
                ->send();
            return;
        }

        $this->validate([
            'appointmentType' => 'required|in:ONLINE,ONSITE',
            'requiredAttendees' => 'required|string',
        ]);

        if (!$this->selectedSession) {
            Notification::make()
                ->title('No session selected')
                ->danger()
                ->body('Please select a session first.')
                ->send();
            return;
        }

        try {
            // Get customer details
            $customer = auth()->guard('customer')->user();

            // Create Teams meeting if appointment type is ONLINE
            $teamsEventId = null;
            $meetingLink = null;
            $meetingId = null;
            $meetingPassword = null;

            if ($this->appointmentType === 'ONLINE') {
                try {
                    // Parse required attendees for Teams meeting
                    $attendeeEmails = [];
                    foreach (array_map('trim', explode(';', $this->requiredAttendees)) as $email) {
                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $attendeeEmails[] = $email;
                        }
                    }

                    // Get implementer details for Teams meeting
                    $implementer = User::where('name', $this->selectedSession['implementer_name'])->first();
                    if (!$implementer) {
                        throw new \Exception('Implementer not found for Teams meeting creation');
                    }

                    // Create Teams meeting
                    $startDateTime = Carbon::parse($this->selectedDate . ' ' . $this->selectedSession['start_time']);
                    $endDateTime = Carbon::parse($this->selectedDate . ' ' . $this->selectedSession['end_time']);

                    $meetingTitle = "KICK OFF MEETING SESSION | {$customer->company_name}";
                    $meetingBody = "Customer-requested kick-off meeting session scheduled by {$customer->name}";

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

                    // Create the event in the implementer's calendar
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
                        $onlineMeeting = $response->getOnlineMeeting();

                        if ($onlineMeeting) {
                            $meetingId = null;

                            // Extract Conference ID if available
                            if (method_exists($onlineMeeting, 'getConferenceId')) {
                                $meetingId = $onlineMeeting->getConferenceId();
                            }

                            // If not found, try to parse from the joinUrl
                            if (!$meetingId && $meetingLink) {
                                $urlParts = parse_url($meetingLink);
                                if (isset($urlParts['query'])) {
                                    parse_str($urlParts['query'], $queryParams);
                                    if (isset($queryParams['confid'])) {
                                        $meetingId = $queryParams['confid'];
                                    }
                                }
                            }

                            // For password, check all possible locations
                            try {
                                if (method_exists($onlineMeeting, 'getPassword')) {
                                    $meetingPassword = $onlineMeeting->getPassword();
                                } else if (property_exists($onlineMeeting, 'password')) {
                                    $meetingPassword = $onlineMeeting->password;
                                } else {
                                    // Get the full JSON response to inspect all properties
                                    $onlineMeetingArray = $response->getProperties();

                                    // Check common password field names
                                    $possiblePasswordFields = [
                                        'password', 'passcode', 'meetingPassword', 'joinPassword',
                                        'onlineMeeting.password', 'onlineMeeting.passcode'
                                    ];

                                    foreach ($possiblePasswordFields as $field) {
                                        $fieldParts = explode('.', $field);
                                        $value = $onlineMeetingArray;

                                        foreach ($fieldParts as $part) {
                                            if (is_array($value) && isset($value[$part])) {
                                                $value = $value[$part];
                                            } else {
                                                $value = null;
                                                break;
                                            }
                                        }

                                        if ($value) {
                                            $meetingPassword = $value;
                                            break;
                                        }
                                    }
                                }
                            } catch (\Exception $e) {
                                Log::warning('Error accessing meeting password: ' . $e->getMessage());
                            }
                        }
                    } else {
                        // Log the issue for debugging
                        Log::warning('Online meeting object is null in Teams meeting response', [
                            'event_id' => $teamsEventId,
                            'response' => json_encode($response->getProperties())
                        ]);

                        // Try to get meeting URL through another method if available
                        try {
                            $properties = $response->getProperties();
                            if (isset($properties['onlineMeetingUrl'])) {
                                $meetingLink = $properties['onlineMeetingUrl'];
                            } elseif (isset($properties['onlineMeeting']['joinUrl'])) {
                                $meetingLink = $properties['onlineMeeting']['joinUrl'];
                            }
                        } catch (\Exception $e) {
                            Log::error('Error retrieving alternative meeting URL: ' . $e->getMessage());
                        }
                    }

                    Log::info('Teams meeting created successfully for customer booking', [
                        'event_id' => $teamsEventId,
                        'meeting_link' => $meetingLink,
                        'customer' => $customer->company_name,
                        'implementer' => $implementer->name
                    ]);

                } catch (\Exception $e) {
                    Log::error('Failed to create Teams meeting for customer booking: ' . $e->getMessage(), [
                        'customer' => $customer->company_name,
                        'implementer' => $this->selectedSession['implementer_name'],
                        'trace' => $e->getTraceAsString()
                    ]);

                    // Continue without Teams meeting if it fails
                    Notification::make()
                        ->title('Teams meeting creation failed')
                        ->warning()
                        ->body('The appointment will be created without Teams meeting details.')
                        ->send();
                }
            }

            // Create appointment request using lead_id
            $appointment = new ImplementerAppointment();
            $appointment->fill([
                'lead_id' => $customer->lead_id, // Use lead_id instead of customer_id
                'type' => 'KICK OFF MEETING SESSION',
                'appointment_type' => $this->appointmentType,
                'date' => $this->selectedDate,
                'start_time' => $this->selectedSession['start_time'],
                'end_time' => $this->selectedSession['end_time'],
                'implementer' => $this->selectedSession['implementer_name'],
                'title' => 'CUSTOMER REQUEST - KICK OFF MEETING SESSION | ' . $customer->company_name,
                'status' => 'New',
                'request_status' => 'PENDING',
                'required_attendees' => $this->requiredAttendees,
                'remarks' => $this->remarks,
                'session' => $this->selectedSession['session_name'],
                'event_id' => $teamsEventId,
                'meeting_link' => $meetingLink,
                'meeting_id' => $meetingId,
                'meeting_password' => $meetingPassword,
            ]);

            $appointment->save();

            // Send notification email to implementer team
            $this->sendBookingNotification($appointment, $customer);

            // Store booking details for success modal
            $this->submittedBooking = [
                'id' => $appointment->id,
                'date' => Carbon::parse($appointment->date)->format('l, d F Y'),
                'time' => Carbon::parse($appointment->start_time)->format('g:i A') . ' - ' .
                         Carbon::parse($appointment->end_time)->format('g:i A'),
                'implementer' => $appointment->implementer,
                'session' => $appointment->session,
                'type' => $appointment->appointment_type,
                'has_teams' => !empty($meetingLink),
                'submitted_at' => now()->format('g:i A'),
            ];

            // Close booking modal and show success modal
            $this->closeBookingModal();
            $this->showSuccessModal = true;

            // Refresh existing bookings
            $this->checkExistingBookings();

        } catch (\Exception $e) {
            Log::error('Customer booking error: ' . $e->getMessage());

            Notification::make()
                ->title('Booking failed')
                ->danger()
                ->body('There was an error submitting your booking. Please try again.')
                ->send();
        }
    }

    public function closeSuccessModal()
    {
        $this->showSuccessModal = false;
        $this->submittedBooking = null;
    }

    private function sendBookingNotification($appointment, $customer)
    {
        try {
            $lead = \App\Models\Lead::find($customer->lead_id);

            // Format data to match the email template's expected $content['lead'] structure
            $emailData = [
                'content' => [
                    'lead' => [
                        'appointment_type' => $appointment->appointment_type,
                        'demo_type' => $appointment->type,
                        'company' => $customer->company_name,
                        'date' => $appointment->date,
                        'startTime' => Carbon::parse($appointment->start_time)->format('g:i A'),
                        'endTime' => Carbon::parse($appointment->end_time)->format('g:i A'),
                        'meetingLink' => $appointment->meeting_link,
                        'implementerName' => $appointment->implementer,
                        'implementerEmail' => $this->getImplementerEmail($appointment->implementer),
                        'customerName' => $customer->name,
                        'customerEmail' => $customer->email,
                        'customerPhone' => $customer->phone,
                        'leadId' => $customer->lead_id,
                        'session' => $appointment->session,
                        'requiredAttendees' => $appointment->required_attendees,
                        'remarks' => $appointment->remarks,
                        'status' => $appointment->status,
                        'requestStatus' => $appointment->request_status,
                        'meetingId' => $appointment->meeting_id,
                        'meetingPassword' => $appointment->meeting_password,
                        'eventId' => $appointment->event_id,
                        'bookingId' => $appointment->id,
                        'submittedAt' => now()->format('d F Y, g:i A'),
                    ]
                ]
            ];

            // Build primary recipients list (TO field)
            $recipients = [];

            // Add all required attendees
            if ($appointment->required_attendees) {
                $attendeeEmails = array_map('trim', explode(';', $appointment->required_attendees));
                foreach ($attendeeEmails as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL) && !in_array($email, $recipients)) {
                        $recipients[] = $email;
                    }
                }
            }

            $ccRecipients = [
                // 'fazuliana.mohdarsad@timeteccloud.com'
            ];

            // Add the assigned implementer to CC
            $implementerEmail = $this->getImplementerEmail($appointment->implementer);
            if ($implementerEmail && !in_array($implementerEmail, $ccRecipients)) {
                $ccRecipients[] = $implementerEmail;
            }

            // Add the salesperson to CC
            $lead = \App\Models\Lead::find($customer->lead_id);
            if ($lead && $lead->salesperson) {
                $salespersonEmail = $lead->getSalespersonEmail();
                if ($salespersonEmail && !in_array($salespersonEmail, $ccRecipients)) {
                    $ccRecipients[] = $salespersonEmail;
                }
            }

            // Remove duplicates and filter valid emails
            $recipients = array_unique(array_filter($recipients, function($email) {
                return filter_var($email, FILTER_VALIDATE_EMAIL);
            }));

            $ccRecipients = array_unique(array_filter($ccRecipients, function($email) {
                return filter_var($email, FILTER_VALIDATE_EMAIL);
            }));

            if (empty($recipients)) {
                throw new \Exception('No valid email recipients found');
            }

            // Get implementer details for sender
            $implementerName = $appointment->implementer;

            \Illuminate\Support\Facades\Mail::send(
                'emails.implementer_appointment_notification',
                $emailData,
                function ($message) use ($recipients, $ccRecipients, $customer, $implementerEmail, $implementerName) {
                    $message->from($implementerEmail ?: 'noreply@timeteccloud.com', $implementerName ?: 'TimeTec Implementation Team')
                            ->to($recipients) // Primary recipients (customer + attendees)
                            ->cc($ccRecipients) // CC implementer team + assigned implementer + salesperson
                            ->subject("KICK-OFF MEETING SESSION | {$customer->company_name}");
                }
            );

            Log::info('Booking notification email sent successfully', [
                'sender' => $implementerEmail ?: 'noreply@timeteccloud.com',
                'sender_name' => $implementerName ?: 'TimeTec Implementation Team',
                'to_recipients' => $recipients,
                'cc_recipients' => $ccRecipients,
                'customer' => $customer->company_name,
                'appointment_id' => $appointment->id,
                'total_to_recipients' => count($recipients),
                'total_cc_recipients' => count($ccRecipients),
                'salesperson_included' => $lead && $lead->getSalespersonEmail() ? 'yes' : 'no'
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send booking notification email: " . $e->getMessage(), [
                'customer' => $customer->company_name,
                'appointment_id' => $appointment->id ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function getImplementerEmail($implementerName)
    {
        $implementer = User::where('name', $implementerName)->first();
        return $implementer ? $implementer->email : '';
    }

    public function closeBookingModal()
    {
        $this->showBookingModal = false;
        $this->selectedDate = null;
        $this->selectedSession = null;
        $this->availableSessions = [];
        $this->appointmentType = 'ONLINE';
        $this->requiredAttendees = '';
        $this->remarks = '';
    }

    public function getMonthlyData()
    {
        $startOfMonth = $this->currentDate->copy()->startOfMonth();
        $endOfMonth = $this->currentDate->copy()->endOfMonth();

        // Get the calendar grid (including days from previous/next month)
        $startOfCalendar = $startOfMonth->copy()->startOfWeek();
        $endOfCalendar = $endOfMonth->copy()->endOfWeek();

        // Calculate booking window - only allow next 3 weeks from today
        $today = Carbon::today();
        $maxBookingDate = $today->copy()->addWeeks(3)->endOfWeek(); // End of third week

        $monthlyData = [];
        $current = $startOfCalendar->copy();

        while ($current <= $endOfCalendar) {
            $dateString = $current->format('Y-m-d');
            $isCurrentMonth = $current->month === $this->currentDate->month;
            $isToday = $current->isToday();
            $isPast = $current->isPast();
            $isWeekend = $current->isWeekend();

            // Check if date is beyond booking window
            $isBeyondBookingWindow = $current->gt($maxBookingDate);

            // Check for public holidays
            $isPublicHoliday = PublicHoliday::where('date', $dateString)->exists();

            // Check if this date has customer's scheduled meeting
            $hasCustomerMeeting = collect($this->existingBookings)->contains('raw_date', $dateString);

            // Count available sessions for this date (only if customer doesn't have existing booking)
            $availableCount = 0;
            if (!$this->hasExistingBooking && !$isPast && !$isWeekend && !$isPublicHoliday && $isCurrentMonth && !$isBeyondBookingWindow) {
                $availableCount = count($this->getAvailableSessionsForDate($dateString));
            }

            $monthlyData[] = [
                'date' => $current->copy(),
                'dateString' => $dateString,
                'day' => $current->day,
                'isCurrentMonth' => $isCurrentMonth,
                'isToday' => $isToday,
                'isPast' => $isPast,
                'isWeekend' => $isWeekend,
                'isPublicHoliday' => $isPublicHoliday,
                'isBeyondBookingWindow' => $isBeyondBookingWindow,
                'availableCount' => $availableCount,
                'hasCustomerMeeting' => $hasCustomerMeeting,
                'canBook' => !$this->hasExistingBooking && !$isPast && !$isWeekend && !$isPublicHoliday && $isCurrentMonth && !$isBeyondBookingWindow && $availableCount > 0
            ];

            $current->addDay();
        }

        return $monthlyData;
    }

    public function render()
    {
        $this->monthlyData = $this->getMonthlyData();

        return view('livewire.customer-calendar');
    }
}
