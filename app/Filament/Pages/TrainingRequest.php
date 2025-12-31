<?php

namespace App\Filament\Pages;

use App\Models\TrainingSession;
use App\Models\TrainingBooking;
use App\Models\TrainingAttendee;
use App\Models\Lead;
use App\Models\SoftwareHandover;
use App\Mail\WebinarTrainingNotification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TrainingRequest extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Training Request';
    protected static ?string $title = '';
    protected static string $view = 'filament.pages.training-request';
    protected static ?int $navigationSort = 62;

    // Step 1: Choose Trainer
    public string $selectedTrainer = '';

    // Step 2: Choose Year
    public int $selectedYear;
    public bool $showSessions = false;

    // Step 3: Choose Training Session
    public ?int $selectedSessionId = null;
    public array $expandedSessions = [];

    // Step 5: Add Training Request Modal
    public bool $showRequestModal = false;
    public string $selectedTrainingType = '';

    // Form Data for Training Request
    public string $companySearchTerm = '';
    public ?int $selectedLeadId = null;
    public string $trainingCategory = '';
    public array $attendees = []; // Array of attendee details
    public string $hrdfStatus = 'BOOKED'; // For HRDF training

    // Available options
    public array $trainers = [
        'TRAINER_1' => 'Trainer 1',
        'TRAINER_2' => 'Trainer 2'
    ];

    public array $years = [2025, 2026, 2027];

    public array $trainingTypes = [
        'HRDF' => 'Online HRDF Training',
        'WEBINAR' => 'Online Webinar Training'
    ];

    public array $trainingCategories = [
        'NEW_TRAINING' => 'New Training',
        'RE_TRAINING' => 'Re-Training'
    ];

    public array $hrdfStatuses = [
        'BOOKED' => '(01) Booked',
        'CANCEL' => '(02) Cancel',
        'APPLY' => '(03) Apply'
    ];

    public function mount()
    {
        $this->selectedYear = 2025;
        $this->selectedTrainer = 'TRAINER_1';
    }

    // Step 1 & 2: When trainer or year changes, load sessions
    public function updatedSelectedTrainer()
    {
        $this->loadTrainingSessions();
    }

    public function updatedSelectedYear()
    {
        $this->loadTrainingSessions();
    }

    private function loadTrainingSessions()
    {
        if ($this->selectedTrainer && $this->selectedYear) {
            $this->showSessions = true;
        }
    }

    // Get training sessions with color coding
    #[Computed]
    public function trainingSessions()
    {
        if (!$this->showSessions) {
            return collect();
        }

        return TrainingSession::where('year', $this->selectedYear)
            ->where('trainer_profile', $this->selectedTrainer)
            ->orderBy('day1_date')
            ->get()
            ->map(function ($session) {
                $now = Carbon::now();
                $sessionDate = Carbon::parse($session->day1_date);

                // Color coding based on date
                $status = $sessionDate->lt($now) ? 'past' : 'available';

                // Get booking counts
                $hrdfCount = TrainingBooking::where('training_session_id', $session->id)
                    ->where('training_type', 'HRDF')
                    ->where('status', '!=', 'CANCELLED')
                    ->withCount(['activeAttendees'])
                    ->get()
                    ->sum('active_attendees_count');

                $webinarCount = TrainingBooking::where('training_session_id', $session->id)
                    ->where('training_type', 'WEBINAR')
                    ->where('status', '!=', 'CANCELLED')
                    ->withCount(['activeAttendees'])
                    ->get()
                    ->sum('active_attendees_count');

                // Calculate proper slot limits based on training category
                $hrdfLimit = $session->training_category === 'HRDF_WEBINAR' ? 50 : 50;
                $webinarLimit = $session->training_category === 'HRDF_WEBINAR' ? 50 : 100;

                return [
                    'session' => $session,
                    'status' => $status,
                    'hrdf_count' => $hrdfCount,
                    'webinar_count' => $webinarCount,
                    'hrdf_limit' => $hrdfLimit,
                    'webinar_limit' => $webinarLimit,
                    'training_category' => $session->training_category,
                    'is_expanded' => in_array($session->id, $this->expandedSessions)
                ];
            });
    }

    // Step 4: Expand/Collapse session details
    public function toggleSession($sessionId)
    {
        if (in_array($sessionId, $this->expandedSessions)) {
            $this->expandedSessions = array_filter($this->expandedSessions, fn($id) => $id != $sessionId);
        } else {
            $this->expandedSessions[] = $sessionId;
        }
    }

    // Get bookings for expanded session
    public function getSessionBookings($sessionId)
    {
        return TrainingBooking::where('training_session_id', $sessionId)
            ->with(['lead.companyDetail'])
            ->where('status', '!=', 'CANCELLED')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('training_type');
    }

    // Step 5: Show Add Training Request Modal
    public function showAddRequestModal($sessionId)
    {
        $session = TrainingSession::find($sessionId);

        // Check if session is available (not past date)
        if (Carbon::parse($session->day1_date)->lt(Carbon::now())) {
            Notification::make()
                ->title('Session Not Available')
                ->body('Cannot create training request for past sessions.')
                ->warning()
                ->send();
            return;
        }

        // Check if meeting links exist for all 3 days
        if (!$this->hasCompleteMeetingLinks($session)) {
            Notification::make()
                ->title('No Meeting Link Available')
                ->body('Cannot create training request. Meeting links are required for all training days.')
                ->warning()
                ->send();
            return;
        }

        $this->selectedSessionId = $sessionId;
        $this->resetRequestForm();
        $this->showRequestModal = true;
    }

    private function resetRequestForm()
    {
        $this->selectedTrainingType = '';
        $this->companySearchTerm = '';
        $this->selectedLeadId = null;
        $this->trainingCategory = '';
        $this->attendees = [['name' => '', 'email' => '', 'phone' => '']];
        $this->hrdfStatus = 'BOOKED';
    }

    // Step 6: Select Training Type
    public function selectTrainingType($type)
    {
        $this->selectedTrainingType = $type;

        // For webinar training, auto-populate attendees from software handovers if lead is already selected
        if ($type === 'WEBINAR' && $this->selectedLeadId) {
            $this->populateWebinarAttendees($this->selectedLeadId);
        } elseif ($type === 'HRDF') {
            // Reset to default single attendee for HRDF
            $this->attendees = [['name' => '', 'email' => '', 'phone' => '']];
        }
    }

    // Populate webinar attendees from software handovers implementation_pics
    private function populateWebinarAttendees($leadId)
    {
        // Get all software handovers for this lead
        $softwareHandovers = \App\Models\SoftwareHandover::where('lead_id', $leadId)
            ->whereNotNull('implementation_pics')
            ->where('implementation_pics', '!=', '')
            ->get();

        $attendees = [];
        $uniqueEmails = [];

        foreach ($softwareHandovers as $handover) {
            $implementerPics = $handover->implementation_pics;

            // Handle double-encoded JSON string
            if (is_string($implementerPics)) {
                $implementerPics = json_decode($implementerPics, true);
            }

            if (is_array($implementerPics)) {
                foreach ($implementerPics as $pic) {
                    // Check if email is unique
                    $email = $pic['pic_email_impl'] ?? '';

                    if (!empty($email) && !in_array($email, $uniqueEmails)) {
                        $uniqueEmails[] = $email;

                        $attendees[] = [
                            'name' => $pic['pic_name_impl'] ?? '',
                            'email' => $email,
                            'phone' => $pic['pic_phone_impl'] ?? ''
                        ];
                    }
                }
            }
        }

        // If no implementer pics found, keep at least one empty attendee
        if (empty($attendees)) {
            $attendees = [['name' => '', 'email' => '', 'phone' => '']];
        }

        $this->attendees = $attendees;
    }

    // Search for companies/leads
    public function searchCompanies()
    {
        if (empty($this->companySearchTerm)) {
            return collect();
        }

        return Lead::with('companyDetail')
            ->where(function ($query) {
                $query->where('lead_code', 'like', '%' . $this->companySearchTerm . '%')
                      ->orWhereHas('companyDetail', function ($q) {
                          $q->where('company_name', 'like', '%' . $this->companySearchTerm . '%');
                      });
            })
            ->limit(10)
            ->get();
    }

    public function selectLead($leadId)
    {
        $this->selectedLeadId = $leadId;
        $lead = Lead::with('companyDetail')->find($leadId);

        if ($lead) {
            $this->companySearchTerm = $lead->companyDetail->company_name ?? '';

            // For webinar training, auto-populate attendees from software handovers implementation_pics
            if ($this->selectedTrainingType === 'WEBINAR') {
                $this->populateWebinarAttendees($leadId);
            }
        }
    }

    // Step 7-10: Submit Training Request
    public function submitRequest()
    {
        $this->validate([
            'selectedTrainingType' => 'required',
            'selectedLeadId' => 'required',
            'trainingCategory' => 'required',
            'attendees.*.name' => 'required|string|max:255',
            'attendees.*.email' => 'required|email|max:255'
        ]);

        $session = TrainingSession::find($this->selectedSessionId);
        $lead = Lead::find($this->selectedLeadId);

        // Check slot availability
        $currentCount = TrainingBooking::where('training_session_id', $this->selectedSessionId)
            ->where('training_type', $this->selectedTrainingType)
            ->where('status', '!=', 'CANCELLED')
            ->withCount(['activeAttendees'])
            ->get()
            ->sum('active_attendees_count');

        // Calculate proper slot limits based on training category
        if ($session->training_category === 'HRDF_WEBINAR') {
            $slotLimit = $this->selectedTrainingType === 'HRDF' ? 50 : 50;
        } else {
            $slotLimit = $this->selectedTrainingType === 'HRDF' ? 50 : 100;
        }
        $attendeeCount = count(array_filter($this->attendees, fn($attendee) => !empty($attendee['name']) && !empty($attendee['email'])));

        if ($currentCount + $attendeeCount > $slotLimit) {
            Notification::make()
                ->title('Slot Limit Exceeded')
                ->body("Cannot add {$attendeeCount} attendees. Available slots: " . ($slotLimit - $currentCount))
                ->warning()
                ->send();
            return;
        }

        // Generate running number
        $runningNumber = $this->generateRunningNumber($this->selectedTrainingType);

        // Create training booking
        $booking = TrainingBooking::create([
            'handover_id' => $runningNumber,
            'training_session_id' => $this->selectedSessionId,
            'lead_id' => $this->selectedLeadId,
            'training_type' => $this->selectedTrainingType,
            'training_category' => $this->trainingCategory,
            'status' => 'BOOKED', // Always default to BOOKED
            'submitted_by' => auth()->user()->name,
            'submitted_at' => now(),
            'hrdf_application_status' => 'BOOKED' // Always default to BOOKED
        ]);

        // Create attendees
        $createdAttendees = [];
        foreach ($this->attendees as $attendee) {
            if (!empty($attendee['name']) && !empty($attendee['email'])) {
                $trainingAttendee = TrainingAttendee::create([
                    'training_booking_id' => $booking->id,
                    'name' => $attendee['name'],
                    'email' => $attendee['email'],
                    'phone' => $attendee['phone'] ?? '',
                    'attendance_status' => 'REGISTERED',
                    'registered_at' => now()
                ]);

                $createdAttendees[] = $attendee;
            }
        }

        // Send email notification for webinar training
        if ($this->selectedTrainingType === 'WEBINAR' && !empty($createdAttendees)) {
            try {
                // Send email to each attendee
                foreach ($createdAttendees as $attendee) {
                    Mail::to($attendee['email'])
                        ->send(new WebinarTrainingNotification($booking, $createdAttendees));
                }

                // TODO: Add attendees to MS Teams meeting
                // This would require Microsoft Graph API integration to add participants to the meeting

            } catch (\Exception $e) {
                // Log error but don't fail the booking creation
                Log::error('Failed to send webinar training email: ' . $e->getMessage());
            }
        }

        // Send notification
        $emailStatus = '';
        if ($this->selectedTrainingType === 'WEBINAR' && !empty($createdAttendees)) {
            $emailStatus = ' Webinar invitation emails have been sent to all attendees.';
        }

        Notification::make()
            ->title('Training Request Created')
            ->body("Training request {$runningNumber} has been created with " . $attendeeCount . " attendees.{$emailStatus}")
            ->success()
            ->send();

        // TODO: Send email notifications to participants (for HRDF training)

        $this->closeRequestModal();
    }

    // Attendee management methods
    public function addAttendee()
    {
        $this->attendees[] = ['name' => '', 'email' => '', 'phone' => ''];
    }

    public function removeAttendee($index)
    {
        if (count($this->attendees) > 1) {
            unset($this->attendees[$index]);
            $this->attendees = array_values($this->attendees); // Re-index array
        }
    }

    private function generateRunningNumber($type)
    {
        $year = substr($this->selectedYear, -2); // Get last 2 digits of year
        $prefix = $type === 'HRDF' ? 'TH_' : 'TW_';

        // Get the next sequential number for this year and type
        $lastNumber = TrainingBooking::where('handover_id', 'like', $prefix . $year . '%')
            ->orderBy('handover_id', 'desc')
            ->value('handover_id');

        if ($lastNumber) {
            $lastSequence = (int)substr($lastNumber, -4);
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1;
        }

        return $prefix . $year . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
    }

    public function closeRequestModal()
    {
        $this->showRequestModal = false;
        $this->resetRequestForm();
    }

    // Check if all 3 days have meeting links
    private function hasCompleteMeetingLinks($session)
    {
        return !empty($session->day1_meeting_link) &&
               !empty($session->day2_meeting_link) &&
               !empty($session->day3_meeting_link);
    }

    // Cancel training request
    public function cancelRequest($bookingId)
    {
        $booking = TrainingBooking::find($bookingId);

        if ($booking && ($booking->submitted_by === auth()->user()->name || auth()->user()->role_id === 1)) {
            $booking->update(['status' => 'CANCELLED']);

            Notification::make()
                ->title('Training Request Cancelled')
                ->body("Training request {$booking->handover_id} has been cancelled.")
                ->success()
                ->send();
        }
    }
}
