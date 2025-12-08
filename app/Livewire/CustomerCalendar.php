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
    public $canScheduleMeeting = false;
    public $showExistingBookings = true;

    public $showMeetingDetailsModal = false;
    public $selectedMeetingDetails = null;

    public $showTutorial = false;
    public $currentTutorialStep = 1;
    public $totalTutorialSteps = 4;

    public function mount()
    {
        $this->currentDate = Carbon::now();
        $customer = auth()->guard('customer')->user();
        $this->customerLeadId = $customer->lead_id;
        $this->swId = $customer->sw_id;
        $this->assignedImplementer = $this->getAssignedImplementer();
        $this->checkExistingBookings();

        // NEW LOGIC: Check if customer can schedule meetings
        $this->canScheduleMeeting = $this->determineSchedulingPermission($customer);

        // Initialize bookings visibility to true
        $this->showExistingBookings = true;

        // Check if tutorial should be shown
        if (!$customer->tutorial_completed) {
            $this->showTutorial = true;
            $this->currentTutorialStep = $customer->tutorial_step ?? 1;
        }
    }

    public function showTutorialModal()
    {
        $customer = auth()->guard('customer')->user();
        $this->currentTutorialStep = 1; // Always start from step 1
        $this->showTutorial = true;
    }

    public function nextTutorialStep()
    {
        if ($this->currentTutorialStep < $this->totalTutorialSteps) {
            $this->currentTutorialStep++;
            $this->updateTutorialProgress();
        } else {
            $this->completeTutorial();
        }
    }

    public function previousTutorialStep()
    {
        if ($this->currentTutorialStep > 1) {
            $this->currentTutorialStep--;
            $this->updateTutorialProgress();
        }
    }

    public function skipTutorial()
    {
        $this->completeTutorial();
    }

    public function closeTutorial()
    {
        $this->showTutorial = false;
    }

    private function updateTutorialProgress()
    {
        $customer = auth()->guard('customer')->user();
        DB::table('customers')
            ->where('id', $customer->id)
            ->update(['tutorial_step' => $this->currentTutorialStep]);
    }

    public function completeTutorial()
    {
        $customer = auth()->guard('customer')->user();
        DB::table('customers')
            ->where('id', $customer->id)
            ->update([
                'tutorial_completed' => true,
                'tutorial_step' => 1  // Reset to step 1
            ]);

        $this->showTutorial = false;

        // Show a success notification
        Notification::make()
            ->title('Tutorial completed! 🎉')
            ->success()
            ->body('You can access this tutorial anytime using the help button.')
            ->send();
    }

    public function openMeetingDetailsModal($bookingId)
    {
        // Add debugging
        Log::info('openMeetingDetailsModal called', ['booking_id' => $bookingId]);

        $booking = collect($this->existingBookings)->firstWhere('id', $bookingId);

        if (!$booking) {
            Log::error('Booking not found', ['booking_id' => $bookingId, 'existing_bookings' => $this->existingBookings]);
            Notification::make()
                ->title('Meeting not found')
                ->danger()
                ->send();
            return;
        }

        // Add more debugging
        Log::info('Booking found', ['booking' => $booking]);

        // Get the full appointment details from database
        $appointment = ImplementerAppointment::find($bookingId);

        if (!$appointment) {
            Log::error('Appointment not found in database', ['booking_id' => $bookingId]);
            Notification::make()
                ->title('Meeting details not found')
                ->danger()
                ->send();
            return;
        }

        // Get implementer email - ADD NULL CHECK HERE
        $implementerEmail = $this->getImplementerEmail($appointment->implementer ?? '');

        $this->selectedMeetingDetails = [
            'id' => $appointment->id,
            'date' => Carbon::parse($appointment->date)->format('j M Y, l'),
            'time' => Carbon::parse($appointment->start_time)->format('H:i') . ' – ' .
                    Carbon::parse($appointment->end_time)->format('H:i'),
            'type' => $appointment->type ?? '',
            'implementer_name' => $appointment->implementer ?? 'Unknown',
            'implementer_email' => $implementerEmail,
            'meeting_link' => $appointment->meeting_link ?? '',
            'meeting_id' => $appointment->meeting_id ?? '',
            'meeting_password' => $appointment->meeting_password ?? '',
            'status' => $appointment->status ?? 'Unknown',
            'required_attendees' => $appointment->required_attendees ?? '',
            'remarks' => $appointment->remarks ?? '',
            'appointment_type' => $appointment->appointment_type ?? '',
        ];

        Log::info('Modal should show now', ['selectedMeetingDetails' => $this->selectedMeetingDetails]);
        $this->showMeetingDetailsModal = true;
    }

    public function closeMeetingDetailsModal()
    {
        $this->showMeetingDetailsModal = false;
        $this->selectedMeetingDetails = null;
    }

    public function toggleExistingBookings()
    {
        $this->showExistingBookings = !$this->showExistingBookings;
    }

    private function determineSchedulingPermission($customer)
    {
        // ✅ NEW LOGIC: Check if customer has explicit permission first
        if ((bool) $customer->able_set_meeting) {
            return true;
        }

        // ✅ Check if the latest appointment is "Done" (allows next booking)
        $latestAppointment = ImplementerAppointment::where('lead_id', $customer->lead_id)
            ->whereIn('type', ['KICK OFF MEETING SESSION', 'REVIEW SESSION'])
            ->orderBy('created_at', 'desc')
            ->first();

        // If no appointments exist, customer can book (first time booking)
        if (!$latestAppointment) {
            return true;
        }

        // If latest appointment is "Done", customer can book the next session
        if ($latestAppointment->status === 'Done') {
            return true;
        }

        // If latest appointment is "New" (pending), cannot book another
        if ($latestAppointment->status === 'New') {
            return false;
        }

        // For any other status (Cancelled, etc.), allow booking
        return true;
    }

    public function checkExistingBookings()
    {
        $customer = auth()->guard('customer')->user();

        // Get all existing bookings (for display purposes) - include both types
        $this->existingBookings = ImplementerAppointment::where('lead_id', $customer->lead_id)
            ->whereIn('status', ['New', 'Done'])
            ->whereIn('type', ['KICK OFF MEETING SESSION', 'REVIEW SESSION'])
            ->orderBy('date', 'desc') // Show most recent first
            ->get()
            ->map(function ($booking) {
                // ✅ Format the type to show session number for review sessions (display only)
                $displayType = $booking->type;

                if ($booking->type === 'REVIEW SESSION') {
                    // Count how many review sessions were completed before this one
                    $previousReviewCount = ImplementerAppointment::where('lead_id', $booking->lead_id)
                        ->where('type', 'REVIEW SESSION')
                        ->where('status', 'Done')
                        ->where('created_at', '<', $booking->created_at)
                        ->count();

                    // For "New" status, it's the next session
                    if ($booking->status === 'New') {
                        $displayType = "REVIEW SESSION " . ($previousReviewCount + 1);
                    } else {
                        // For "Done" status, calculate the actual session number
                        $displayType = "REVIEW SESSION " . ($previousReviewCount + 1);
                    }
                }

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
                    'type' => $displayType, // ✅ Use the formatted type with count for display
                    'raw_type' => $booking->type, // ✅ Keep the raw type from database
                    'raw_date' => $booking->date,
                    'start_time' => $booking->start_time,
                    'end_time' => $booking->end_time,
                ];
            })
            ->toArray();

        // Keep this for display purposes only
        $this->hasExistingBooking = count($this->existingBookings) > 0;

        // Update scheduling permission after checking bookings
        $this->canScheduleMeeting = $this->determineSchedulingPermission($customer);
    }

    private function getNextSessionType()
    {
        $customer = auth()->guard('customer')->user();

        // Check if there's any completed kick-off meeting
        $hasCompletedKickOff = ImplementerAppointment::where('lead_id', $customer->lead_id)
            ->where('type', 'KICK OFF MEETING SESSION')
            ->where('status', 'Done')
            ->exists();

        // If no completed kick-off meeting, next should be kick-off
        if (!$hasCompletedKickOff) {
            return 'KICK OFF MEETING SESSION';
        }

        // ✅ For review sessions, just return the base type (no number)
        return 'REVIEW SESSION';
    }

    public function getSessionTitle()
    {
        $customer = auth()->guard('customer')->user();

        // Check if kick-off is completed
        $hasCompletedKickOff = ImplementerAppointment::where('lead_id', $customer->lead_id)
            ->where('type', 'KICK OFF MEETING SESSION')
            ->where('status', 'Done')
            ->exists();

        if (!$hasCompletedKickOff) {
            return 'Schedule Your Kick-Off Meeting';
        }

        // ✅ Count completed review sessions for display title
        $completedReviewCount = ImplementerAppointment::where('lead_id', $customer->lead_id)
            ->where('type', 'REVIEW SESSION')
            ->where('status', 'Done')
            ->count();

        $nextReviewNumber = $completedReviewCount + 1;
        return "Schedule Your Review Session {$nextReviewNumber}";
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

            // NEW: Update customer's able_set_meeting to 1 after successful cancellation
            $customer = auth()->guard('customer')->user();

            DB::table('customers')
            ->where('id', $customer->id)
            ->update(['able_set_meeting' => true]);

            Log::info('Customer able_set_meeting enabled after cancellation', [
                'customer_id' => $customer->id,
                'customer_email' => $customer->email,
                'appointment_id' => $appointment->id,
                'company_name' => $customer->company_name
            ]);

            // Send cancellation notification email using existing template
            $this->sendCancellationNotification($appointment);

            // Close modal and refresh bookings
            $this->closeCancelModal();
            $this->checkExistingBookings(); // This will update canScheduleMeeting status

            $this->canScheduleMeeting = true;

            Notification::make()
                ->title('Appointment cancelled successfully')
                ->success()
                ->body('Your appointment and Teams meeting have been cancelled. You can now schedule a new meeting if needed.')
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
        $customer = auth()->guard('customer')->user();

        // Check the updated permission logic
        if (!$this->canScheduleMeeting) {
            // ✅ More specific error messages based on the reason
            $latestAppointment = ImplementerAppointment::where('lead_id', $customer->lead_id)
                ->whereIn('type', ['KICK OFF MEETING SESSION', 'REVIEW SESSION'])
                ->orderBy('created_at', 'desc')
                ->first();

            if ($latestAppointment && $latestAppointment->status === 'New') {
                // Format the appointment type for display
                $appointmentType = $latestAppointment->type;
                if ($latestAppointment->type === 'REVIEW SESSION') {
                    $previousReviewCount = ImplementerAppointment::where('lead_id', $customer->lead_id)
                        ->where('type', 'REVIEW SESSION')
                        ->where('status', 'Done')
                        ->where('created_at', '<', $latestAppointment->created_at)
                        ->count();
                    $appointmentType = "REVIEW SESSION " . ($previousReviewCount + 1);
                }

                Notification::make()
                    ->title('Existing appointment pending')
                    ->warning()
                    ->body("You have a pending {$appointmentType}. Please wait for it to be completed before scheduling another one.")
                    ->send();
            } else {
                Notification::make()
                    ->title('Meeting scheduling disabled')
                    ->warning()
                    ->body('You are not authorized to schedule meetings. Please contact support for assistance.')
                    ->send();
            }
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

            // Get lead with company details for checking additional PICs
            $lead = \App\Models\Lead::with('companyDetail')->find($this->customerLeadId);

            // Decode additional_pic from company details
            $additionalPics = [];
            if ($lead && $lead->companyDetail && $lead->companyDetail->additional_pic) {
                if (is_string($lead->companyDetail->additional_pic)) {
                    $additionalPics = json_decode($lead->companyDetail->additional_pic, true) ?? [];
                } elseif (is_array($lead->companyDetail->additional_pic)) {
                    $additionalPics = $lead->companyDetail->additional_pic;
                }
            }

            // Extract valid email addresses
            $emails = [];
            foreach ($implementationPics as $pic) {
                // Check if PIC has resigned
                if (isset($pic['status']) && strtolower($pic['status']) === 'resign') {
                    // Try to find replacement in additional_pic
                    $picName = $pic['pic_name_impl'] ?? '';

                    if ($picName && !empty($additionalPics)) {
                        foreach ($additionalPics as $additionalPic) {
                            // Match by name and check if status is Available
                            if (isset($additionalPic['name']) &&
                                strtolower(trim($additionalPic['name'])) === strtolower(trim($picName)) &&
                                isset($additionalPic['status']) &&
                                strtolower($additionalPic['status']) === 'available' &&
                                isset($additionalPic['email']) &&
                                !empty($additionalPic['email'])) {

                                $email = trim($additionalPic['email']);
                                if (filter_var($email, FILTER_VALIDATE_EMAIL) && !in_array($email, $emails)) {
                                    $emails[] = $email;

                                    Log::info('Replaced resigned PIC with updated email from additional_pic', [
                                        'pic_name' => $picName,
                                        'old_status' => 'Resign',
                                        'new_status' => 'Available',
                                        'new_email' => $email,
                                        'lead_id' => $this->customerLeadId
                                    ]);
                                }
                                break; // Found replacement, stop searching
                            }
                        }
                    }
                } else {
                    // PIC is active, use their email from implementation_pics
                    if (isset($pic['pic_email_impl']) && !empty($pic['pic_email_impl'])) {
                        $email = trim($pic['pic_email_impl']);
                        if (filter_var($email, FILTER_VALIDATE_EMAIL) && !in_array($email, $emails)) {
                            $emails[] = $email;
                        }
                    }
                }
            }

            // Return emails separated by semicolons
            return implode(';', $emails);

        } catch (\Exception $e) {
            Log::error('Error retrieving required attendees from handover: ' . $e->getMessage(), [
                'lead_id' => $this->customerLeadId,
                'sw_id' => $this->swId,
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
        $customer = auth()->guard('customer')->user();

        if (!$this->canScheduleMeeting) {
            Notification::make()
                ->title('Meeting scheduling disabled')
                ->danger()
                ->body('You are not authorized to schedule meetings.')
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

            // Determine the correct session type
            $sessionType = $this->getNextSessionType();

            $displaySessionType = $sessionType;
            if ($sessionType === 'REVIEW SESSION') {
                $completedReviewCount = ImplementerAppointment::where('lead_id', $customer->lead_id)
                    ->where('type', 'REVIEW SESSION')
                    ->where('status', 'Done')
                    ->count();

                $displaySessionType = "REVIEW SESSION " . ($completedReviewCount + 1);
            }

            // Create Teams meeting if appointment type is ONLINE
            $teamsEventId = null;
            $meetingLink = null;
            $meetingId = null;
            $meetingPassword = null;
            $onlineMeetingId = null; // ✅ Add this

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

                    $meetingTitle = "{$sessionType} | {$customer->company_name}";
                    $meetingBody = "Customer-requested {$sessionType} scheduled by {$customer->name}";

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

                    Log::info('✅ Step 1: Event created successfully (Customer Booking)', [
                        'event_id' => $teamsEventId,
                        'join_url' => $meetingLink,
                        'organizer_email' => $organizerEmail,
                        'customer' => $customer->company_name,
                        'session_type' => $sessionType
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

                            Log::info('✅ Step 2: Online meeting query response (Customer Booking)', [
                                'response' => $responseBody,
                                'join_url' => $meetingLink,
                                'query_identifier' => $queryIdentifier,
                                'customer' => $customer->company_name
                            ]);

                            // Extract the online meeting ID from response
                            if (isset($responseBody['value']) && count($responseBody['value']) > 0) {
                                $onlineMeetingId = $responseBody['value'][0]['id'] ?? null;

                                Log::info('✅ Step 2: Online meeting ID retrieved (Customer Booking)', [
                                    'online_meeting_id' => $onlineMeetingId,
                                    'event_id' => $teamsEventId,
                                    'customer' => $customer->company_name
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

                                        Log::info('✅ Step 3: Automatic recording enabled (Customer Booking)', [
                                            'online_meeting_id' => $onlineMeetingId,
                                            'customer' => $customer->company_name,
                                            'session_type' => $sessionType
                                        ]);

                                    } catch (\Exception $e) {
                                        Log::error('❌ Step 3: Failed to enable automatic recording (Customer Booking)', [
                                            'error' => $e->getMessage(),
                                            'online_meeting_id' => $onlineMeetingId,
                                            'customer' => $customer->company_name,
                                            'trace' => $e->getTraceAsString()
                                        ]);
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            Log::error('❌ Step 2: Failed to retrieve online meeting ID (Customer Booking)', [
                                'error' => $e->getMessage(),
                                'join_url' => $meetingLink,
                                'customer' => $customer->company_name,
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    }

                } catch (\Exception $e) {
                    Log::error('Failed to create Teams meeting for customer booking: ' . $e->getMessage(), [
                        'customer' => $customer->company_name,
                        'implementer' => $this->selectedSession['implementer_name'],
                        'session_type' => $sessionType,
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
                'lead_id' => $customer->lead_id,
                'type' => $sessionType,
                'appointment_type' => $this->appointmentType,
                'date' => $this->selectedDate,
                'start_time' => $this->selectedSession['start_time'],
                'end_time' => $this->selectedSession['end_time'],
                'implementer' => $this->selectedSession['implementer_name'],
                'title' => "CUSTOMER REQUEST - {$sessionType} | " . $customer->company_name,
                'status' => 'New',
                'request_status' => 'New',
                'required_attendees' => $this->requiredAttendees,
                'remarks' => $this->remarks,
                'session' => $this->selectedSession['session_name'],
                'event_id' => $teamsEventId,
                'meeting_link' => $meetingLink,
                'meeting_id' => $meetingId,
                'meeting_password' => $meetingPassword,
                'online_meeting_id' => $onlineMeetingId, // ✅ Add this
                'software_handover_id' => $this->swId,
            ]);

            $appointment->save();

            // ✅ Log the final saved appointment details
            Log::info('✅ Step 4: Customer appointment saved with meeting details', [
                'appointment_id' => $appointment->id,
                'event_id' => $teamsEventId,
                'online_meeting_id' => $onlineMeetingId,
                'meeting_link' => $meetingLink,
                'recording_enabled' => !empty($onlineMeetingId),
                'customer' => $customer->company_name,
                'session_type' => $sessionType,
                'implementer' => $this->selectedSession['implementer_name']
            ]);

            DB::table('customers')
            ->where('id', $customer->id)
            ->update(['able_set_meeting' => false]);

            info('Customer able_set_meeting disabled after booking', [
                'customer_id' => $customer->id,
                'customer_email' => $customer->email,
                'appointment_id' => $appointment->id,
                'company_name' => $customer->company_name,
                'session_type' => $sessionType
            ]);

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
                'session_type' => $sessionType,
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

            // Determine email template and subject based on appointment type
            $emailTemplate = ($appointment->type === 'KICK OFF MEETING SESSION')
                ? 'emails.implementer_appointment_notification'
                : 'emails.implementation_session';

            $subjectPrefix = ($appointment->type === 'KICK OFF MEETING SESSION')
                ? 'KICK-OFF MEETING SESSION'
                : 'REVIEW SESSION';

            // Format data to match the email template's expected structure
            $emailData = [
                'content' => [
                    'lead' => [
                        'appointment_type' => $appointment->appointment_type,
                        'demo_type' => $appointment->type,
                        'type' => $appointment->type,
                        'company' => $customer->company_name,
                        'date' => $appointment->date,
                        'startTime' => Carbon::parse($appointment->start_time)->format('H:i'),
                        'endTime' => Carbon::parse($appointment->end_time)->format('H:i'),
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
                    ],
                    // Add additional fields for implementation_session template
                    'appointmentType' => $appointment->appointment_type,
                    'type' => $appointment->type,
                    'date' => $appointment->date,
                    'startTime' => Carbon::parse($appointment->start_time)->format('H:i'),
                    'endTime' => Carbon::parse($appointment->end_time)->format('H:i'),
                    'meetingLink' => $appointment->meeting_link,
                    'companyName' => $customer->company_name,
                    'implementerName' => $appointment->implementer,
                    'implementerEmail' => $this->getImplementerEmail($appointment->implementer),
                    'remarks' => $appointment->remarks,
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

            $ccRecipients = [];

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
                $emailTemplate,
                $emailData,
                function ($message) use ($recipients, $ccRecipients, $customer, $implementerEmail, $implementerName, $subjectPrefix) {
                    $message->from($implementerEmail ?: 'noreply@timeteccloud.com', $implementerName ?: 'TimeTec Implementation Team')
                            ->to($recipients) // Primary recipients (customer + attendees)
                            ->cc($ccRecipients) // CC implementer team + assigned implementer + salesperson
                            ->subject("{$subjectPrefix} | {$customer->company_name}");
                }
            );

            Log::info('Booking notification email sent successfully', [
                'template' => $emailTemplate,
                'subject_prefix' => $subjectPrefix,
                'sender' => $implementerEmail ?: 'noreply@timeteccloud.com',
                'sender_name' => $implementerName ?: 'TimeTec Implementation Team',
                'to_recipients' => $recipients,
                'cc_recipients' => $ccRecipients,
                'customer' => $customer->company_name,
                'appointment_id' => $appointment->id,
                'appointment_type' => $appointment->type,
                'total_to_recipients' => count($recipients),
                'total_cc_recipients' => count($ccRecipients),
                'salesperson_included' => $lead && $lead->getSalespersonEmail() ? 'yes' : 'no'
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send booking notification email: " . $e->getMessage(), [
                'customer' => $customer->company_name,
                'appointment_id' => $appointment->id ?? 'unknown',
                'appointment_type' => $appointment->type ?? 'unknown',
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
        $maxBookingDate = $today->copy()->addWeeks(3)->endOfWeek();

        $customer = auth()->guard('customer')->user();
        $canSchedule = $this->canScheduleMeeting; // Use the updated permission logic

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
            $hasCustomerMeeting = collect($this->existingBookings)->contains(function ($booking) use ($dateString) {
                return Carbon::parse($booking['date'])->format('Y-m-d') === $dateString;
            });

            // Count available sessions for this date (REMOVE $isCurrentMonth condition)
            $availableCount = 0;
            if ($canSchedule && !$isPast && !$isWeekend && !$isPublicHoliday && !$isBeyondBookingWindow) {
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
                'canBook' => $canSchedule && !$isPast && !$isWeekend && !$isPublicHoliday && !$isBeyondBookingWindow && $availableCount > 0
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
