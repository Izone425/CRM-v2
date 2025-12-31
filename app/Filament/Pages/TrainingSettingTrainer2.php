<?php

namespace App\Filament\Pages;

use App\Models\TrainingSession;
use App\Models\PublicHoliday;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class TrainingSettingTrainer2 extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Training Setting Trainer 2';
    protected static ?string $title = '';
    protected static string $view = 'filament.pages.training-setting-trainer2';
    protected static ?int $navigationSort = 61;

    // Properties
    public int $selectedYear;
    public bool $showCalendar = false;
    public string $selectedCategory = '';
    public string $selectedModule = '';
    public bool $showCategoryModal = false;
    public ?int $sessionToUpdate = null;
    public array $collapsedQuarters = [];
    public bool $showDateModal = false;
    public array $weekDates = [];
    public array $selectedDates = [];
    public bool $isCreatingNewSession = false;
    public array $weekInfo = [];

    // Training Categories
    public array $trainingCategories = [
        'CATEGORY_1' => 'ONLINE HRDF TRAINING + ONLINE WEBINAR TRAINING',
        'CATEGORY_2' => 'ONLINE HRDF TRAINING ONLY',
        'CATEGORY_3' => 'ONLINE WEBINAR TRAINING ONLY'
    ];

    // Training Modules
    public array $trainingModules = [
        'OPERATIONAL' => 'OPERATIONAL MODULE',
        'STRATEGIC' => 'STRATEGIC MODULE'
    ];

    public function mount()
    {
        $this->selectedYear = 2025;
        $this->showCalendar = true; // Show calendar by default
        $this->initializeCollapsedQuarters();
    }

    private function initializeCollapsedQuarters()
    {
        $now = Carbon::now();
        $currentQuarter = ceil($now->month / 3);

        // Auto-collapse past quarters
        for ($q = 1; $q <= 4; $q++) {
            if ($now->year == $this->selectedYear && $q < $currentQuarter) {
                $this->collapsedQuarters["Q{$q}"] = true;
            } else {
                $this->collapsedQuarters["Q{$q}"] = false;
            }
        }
    }

    public function toggleQuarter($quarter)
    {
        $this->collapsedQuarters[$quarter] = !($this->collapsedQuarters[$quarter] ?? false);
    }

    // Generate simple schedule for Trainer 2
    public function generateSchedule()
    {
        // Clear existing sessions for Trainer 2 in the year
        TrainingSession::where('year', $this->selectedYear)
            ->where('trainer_profile', 'TRAINER_2')
            ->delete();

        // Get all public holidays (including previous/next year for cross-year weeks)
        $holidays = PublicHoliday::whereBetween('date', [
                Carbon::create($this->selectedYear - 1, 12, 1)->format('Y-m-d'),
                Carbon::create($this->selectedYear + 1, 1, 31)->format('Y-m-d')
            ])
            ->pluck('date')
            ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
            ->toArray();

        $sessionCounter = 1;
        $createdSessions = 0;
        $heldSessions = 0;

        // Use same logic as quarterlyCalendar to avoid cross-year issues
        $month = 1;

        while ($month <= 12) {
            $firstDayOfMonth = Carbon::create($this->selectedYear, $month, 1);
            $lastDayOfMonth = $firstDayOfMonth->copy()->endOfMonth();

            // Start from the first Monday of the month or first day if it starts on Monday
            $currentDate = $firstDayOfMonth->copy();
            if ($currentDate->dayOfWeek !== Carbon::MONDAY) {
                $currentDate = $currentDate->next(Carbon::MONDAY);
            }

            while ($currentDate->lte($lastDayOfMonth)) {
                $tuesday = $currentDate->copy()->next(Carbon::TUESDAY);
                $wednesday = $tuesday->copy()->addDay();
                $thursday = $wednesday->copy()->addDay();

                // Only process if Tuesday is still in the current month and year
                if ($tuesday->month === $month && $tuesday->year === $this->selectedYear) {
                    // Check if any of these days are public holidays
                    $tuesdayIsHoliday = in_array($tuesday->format('Y-m-d'), $holidays);
                    $wednesdayIsHoliday = in_array($wednesday->format('Y-m-d'), $holidays);
                    $thursdayIsHoliday = in_array($thursday->format('Y-m-d'), $holidays);

                    if (!$tuesdayIsHoliday && !$wednesdayIsHoliday && !$thursdayIsHoliday) {
                        // No holidays, create session for Trainer 2
                        TrainingSession::create([
                            'trainer_profile' => 'TRAINER_2',
                            'year' => $this->selectedYear,
                            'training_category' => 'HRDF',
                            'training_module' => 'OPERATIONAL',
                            'session_number' => "SESSION {$sessionCounter}",
                            'day1_date' => $tuesday->format('Y-m-d'),
                            'day2_date' => $wednesday->format('Y-m-d'),
                            'day3_date' => $thursday->format('Y-m-d'),
                            'max_participants' => 50,
                            'status' => 'DRAFT',
                            'is_manual_schedule' => false
                        ]);

                        $createdSessions++;
                    } else {
                        // Has holiday, hold this week
                        $heldSessions++;

                        // Debug: Log which holiday was detected
                        $holidayDetails = [];
                        if ($tuesdayIsHoliday) $holidayDetails[] = "Tuesday ({$tuesday->format('Y-m-d')})";
                        if ($wednesdayIsHoliday) $holidayDetails[] = "Wednesday ({$wednesday->format('Y-m-d')})";
                        if ($thursdayIsHoliday) $holidayDetails[] = "Thursday ({$thursday->format('Y-m-d')})";
                    }

                    $sessionCounter++;
                }

                $currentDate->addWeek();
            }

            $month++;
        }

        // Renumber sessions chronologically
        $this->renumberSessions();

        $this->showCalendar = true;

        Notification::make()
            ->title('Schedule Generated')
            ->body("Created {$createdSessions} sessions for Trainer 2. Held {$heldSessions} sessions due to holidays.")
            ->success()
            ->send();
    }

    // Get quarterly calendar data for Trainer 2
    #[Computed]
    public function quarterlyCalendar()
    {
        if (!$this->showCalendar) {
            return [];
        }

        $sessions = TrainingSession::where('year', $this->selectedYear)
            ->where('trainer_profile', 'TRAINER_2')
            ->orderBy('day1_date')
            ->get();

        $quarters = [
            'Q1' => [
                'name' => 'Q1 ' . $this->selectedYear,
                'months' => [
                    'Jan' => ['name' => 'January', 'number' => 1, 'sessions' => []],
                    'Feb' => ['name' => 'February', 'number' => 2, 'sessions' => []],
                    'Mar' => ['name' => 'March', 'number' => 3, 'sessions' => []]
                ]
            ],
            'Q2' => [
                'name' => 'Q2 ' . $this->selectedYear,
                'months' => [
                    'Apr' => ['name' => 'April', 'number' => 4, 'sessions' => []],
                    'May' => ['name' => 'May', 'number' => 5, 'sessions' => []],
                    'Jun' => ['name' => 'June', 'number' => 6, 'sessions' => []]
                ]
            ],
            'Q3' => [
                'name' => 'Q3 ' . $this->selectedYear,
                'months' => [
                    'Jul' => ['name' => 'July', 'number' => 7, 'sessions' => []],
                    'Aug' => ['name' => 'August', 'number' => 8, 'sessions' => []],
                    'Sep' => ['name' => 'September', 'number' => 9, 'sessions' => []]
                ]
            ],
            'Q4' => [
                'name' => 'Q4 ' . $this->selectedYear,
                'months' => [
                    'Oct' => ['name' => 'October', 'number' => 10, 'sessions' => []],
                    'Nov' => ['name' => 'November', 'number' => 11, 'sessions' => []],
                    'Dec' => ['name' => 'December', 'number' => 12, 'sessions' => []]
                ]
            ]
        ];

        // Get all weeks and sessions for the year
        $weekCounter = 1;
        $month = 1;

        while ($month <= 12) {
            $firstDayOfMonth = Carbon::create($this->selectedYear, $month, 1);
            $lastDayOfMonth = $firstDayOfMonth->copy()->endOfMonth();

            // Start from the first Monday of the month or first day if it starts on Monday
            $currentDate = $firstDayOfMonth->copy();
            if ($currentDate->dayOfWeek !== Carbon::MONDAY) {
                $currentDate = $currentDate->next(Carbon::MONDAY);
            }

            while ($currentDate->lte($lastDayOfMonth)) {
                $tuesday = $currentDate->copy()->next(Carbon::TUESDAY);
                $wednesday = $tuesday->copy()->addDay();
                $thursday = $wednesday->copy()->addDay();

                // Only process if Tuesday is still in the current month and year
                if ($tuesday->month === $month && $tuesday->year === $this->selectedYear) {
                    // Find session for this week - check if any of the session dates fall in this week
                    $weekSession = $sessions->first(function($session) use ($currentDate, $tuesday, $wednesday, $thursday) {
                        $day1 = Carbon::parse($session->day1_date)->format('Y-m-d');
                        $day2 = Carbon::parse($session->day2_date)->format('Y-m-d');
                        $day3 = Carbon::parse($session->day3_date)->format('Y-m-d');

                        // Check if any session date matches this week's dates
                        $weekDates = [
                            $currentDate->format('Y-m-d'), // Monday
                            $tuesday->format('Y-m-d'),     // Tuesday
                            $wednesday->format('Y-m-d'),   // Wednesday
                            $thursday->format('Y-m-d'),    // Thursday
                            $thursday->copy()->addDay()->format('Y-m-d') // Friday
                        ];

                        return in_array($day1, $weekDates) || in_array($day2, $weekDates) || in_array($day3, $weekDates);
                    });

                    $weekData = [
                        'week_number' => $weekCounter,
                        'dates' => [
                            'tuesday' => $tuesday->format('Y-m-d'),
                            'wednesday' => $wednesday->format('Y-m-d'),
                            'thursday' => $thursday->format('Y-m-d')
                        ],
                        'session' => $weekSession,
                        'status' => $this->getWeekStatus($weekSession, $tuesday),
                        'can_create_meeting' => $weekSession && !$this->hasCompleteMeetingLinks($weekSession)
                    ];

                    // Assign to appropriate quarter and month based on Tuesday's month
                    $quarter = 'Q' . ceil($month / 3);
                    $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    $monthKey = $monthNames[$month - 1];

                    $quarters[$quarter]['months'][$monthKey]['sessions'][] = $weekData;

                    $weekCounter++;
                }

                $currentDate->addWeek();
            }

            $month++;
        }

        return $quarters;
    }

    private function getWeekStatus($session, $tuesday)
    {
        $now = Carbon::now();

        if (!$session) {
            return 'missing'; // Red - needs manual creation
        }

        // Check for past dates first (regardless of meeting links)
        if ($tuesday->lt($now->startOfDay())) {
            return 'past'; // Grey - past sessions
        }

        // Check for missing meetings (only for future sessions)
        if (!$this->hasCompleteMeetingLinks($session)) {
            return 'needs_meeting'; // Yellow - needs Teams meeting
        }

        return 'ready'; // Green - ready with meetings
    }

    private function hasCompleteMeetingLinks($session)
    {
        return !empty($session->day1_meeting_link) &&
               !empty($session->day2_meeting_link) &&
               !empty($session->day3_meeting_link);
    }

    // Create manual session for missing week for Trainer 2
    public function createManualSession($weekNumber, $dates)
    {
        $sessionCounter = TrainingSession::where('year', $this->selectedYear)
            ->where('trainer_profile', 'TRAINER_2')
            ->count() + 1;

        TrainingSession::create([
            'trainer_profile' => 'TRAINER_2',
            'year' => $this->selectedYear,
            'training_category' => 'HRDF',
            'training_module' => 'OPERATIONAL',
            'session_number' => "SESSION {$sessionCounter}",
            'day1_date' => $dates['tuesday'],
            'day2_date' => $dates['wednesday'],
            'day3_date' => $dates['thursday'],
            'max_participants' => 50,
            'status' => 'DRAFT',
            'is_manual_schedule' => true
        ]);

        // Renumber all sessions chronologically
        $this->renumberSessions();

        Notification::make()
            ->title('Session Created')
            ->body("Manual session created for Trainer 2 week {$weekNumber}.")
            ->success()
            ->send();
    }

    private function renumberSessions()
    {
        $sessions = TrainingSession::where('year', $this->selectedYear)
            ->where('trainer_profile', 'TRAINER_2')
            ->orderBy('day1_date')
            ->get();

        foreach ($sessions as $index => $session) {
            $session->update([
                'session_number' => 'SESSION ' . ($index + 1)
            ]);
        }
    }

    // Show category modal for Teams meeting creation
    public function showCategorySelection($sessionId)
    {
        $this->sessionToUpdate = $sessionId;
        $this->selectedCategory = '';
        $this->selectedModule = '';
        $this->showCategoryModal = true;
    }

    // Show date selection modal
    public function showDateSelection($sessionId)
    {
        $session = TrainingSession::find($sessionId);
        if (!$session) {
            return;
        }

        $this->sessionToUpdate = $sessionId;
        $this->selectedDates = [];
        $this->isCreatingNewSession = false;

        // Generate Monday to Friday dates for the week
        $tuesday = Carbon::parse($session->day1_date);
        $monday = $tuesday->copy()->previous(Carbon::MONDAY);

        // Get public holidays for this week
        $weekStart = $monday->format('Y-m-d');
        $weekEnd = $monday->copy()->addDays(4)->format('Y-m-d');
        $holidays = PublicHoliday::whereBetween('date', [$weekStart, $weekEnd])
            ->get()
            ->keyBy('date');

        $this->weekDates = [];
        for ($i = 0; $i < 5; $i++) {
            $date = $monday->copy()->addDays($i);
            $dateString = $date->format('Y-m-d');
            $holiday = $holidays->get($dateString);

            $this->weekDates[] = [
                'date' => $dateString,
                'day' => $date->format('l'),
                'formatted' => $date->format('M j, Y'),
                'is_holiday' => !is_null($holiday),
                'holiday_name' => $holiday ? $holiday->name : null
            ];
        }

        $this->showDateModal = true;
    }

    // Show date selection for creating new session for Trainer 2
    public function showDateSelectionForNewSession($weekNumber, $dates)
    {
        $this->selectedDates = [];
        $this->isCreatingNewSession = true;
        $this->weekInfo = [
            'week_number' => $weekNumber,
            'dates' => $dates
        ];

        // Generate Monday to Friday dates for the week based on Tuesday date
        $tuesday = Carbon::parse($dates['tuesday']);
        $monday = $tuesday->copy()->previous(Carbon::MONDAY);

        // Get public holidays for this week
        $weekStart = $monday->format('Y-m-d');
        $weekEnd = $monday->copy()->addDays(4)->format('Y-m-d');
        $holidays = PublicHoliday::whereBetween('date', [$weekStart, $weekEnd])
            ->get()
            ->keyBy('date');

        $this->weekDates = [];
        for ($i = 0; $i < 5; $i++) {
            $date = $monday->copy()->addDays($i);
            $dateString = $date->format('Y-m-d');
            $holiday = $holidays->get($dateString);

            $this->weekDates[] = [
                'date' => $dateString,
                'day' => $date->format('l'),
                'formatted' => $date->format('M j, Y'),
                'is_holiday' => !is_null($holiday),
                'holiday_name' => $holiday ? $holiday->name : null
            ];
        }

        $this->showDateModal = true;
    }

    // Generate Microsoft Teams meetings for Trainer 2
    public function generateTeamsMeetings()
    {
        if (!$this->selectedCategory || !$this->selectedModule || !$this->sessionToUpdate) {
            Notification::make()
                ->title('Error')
                ->body('Please select both category and module.')
                ->danger()
                ->send();
            return;
        }

        $session = TrainingSession::find($this->sessionToUpdate);
        if (!$session) {
            return;
        }

        // Update session with selected category and module
        $session->update([
            'training_category' => $this->mapCategoryToDatabase($this->selectedCategory),
            'training_module' => $this->selectedModule
        ]);

        // Generate Teams meetings for all 3 days
        $this->createMeetingForDay($session, 1, $session->day1_date);
        $this->createMeetingForDay($session, 2, $session->day2_date);
        $this->createMeetingForDay($session, 3, $session->day3_date);

        $this->showCategoryModal = false;
        $this->sessionToUpdate = null;

        Notification::make()
            ->title('Meetings Created')
            ->body('Microsoft Teams meetings generated for all 3 days for Trainer 2.')
            ->success()
            ->send();
    }

    public function toggleDate($date)
    {
        // Find the date info to check if it's a holiday
        $dateInfo = collect($this->weekDates)->firstWhere('date', $date);

        // Prevent selection of holidays
        if ($dateInfo && $dateInfo['is_holiday']) {
            return;
        }

        if (in_array($date, $this->selectedDates)) {
            $this->selectedDates = array_values(array_filter($this->selectedDates, fn($d) => $d !== $date));
        } else {
            $this->selectedDates[] = $date;
        }
    }

    public function createMeetingsWithSelectedDates()
    {
        if (empty($this->selectedDates)) {
            Notification::make()
                ->title('Error')
                ->body('Please select at least one date.')
                ->danger()
                ->send();
            return;
        }

        if (count($this->selectedDates) > 3) {
            Notification::make()
                ->title('Error')
                ->body('Maximum 3 dates can be selected.')
                ->danger()
                ->send();
            return;
        }

        // For new session creation, require exactly 3 dates
        if ($this->isCreatingNewSession && count($this->selectedDates) !== 3) {
            Notification::make()
                ->title('Error')
                ->body('Please select exactly 3 dates for training session.')
                ->danger()
                ->send();
            return;
        }

        if ($this->isCreatingNewSession) {
            // Create new session with selected dates
            $this->createNewSessionWithDates();
        } else {
            // Update existing session dates
            $session = TrainingSession::find($this->sessionToUpdate);
            if (!$session) {
                return;
            }

            // Update session dates
            sort($this->selectedDates);
            $session->update([
                'day1_date' => $this->selectedDates[0] ?? null,
                'day2_date' => $this->selectedDates[1] ?? null,
                'day3_date' => $this->selectedDates[2] ?? null,
            ]);

            $this->showDateModal = false;
            $this->showCategorySelection($this->sessionToUpdate);
        }
    }

    private function createNewSessionWithDates()
    {
        $sessionCounter = TrainingSession::where('year', $this->selectedYear)
            ->where('trainer_profile', 'TRAINER_2')
            ->count() + 1;

        sort($this->selectedDates);

        TrainingSession::create([
            'trainer_profile' => 'TRAINER_2',
            'year' => $this->selectedYear,
            'training_category' => 'HRDF',
            'training_module' => 'OPERATIONAL',
            'session_number' => "SESSION {$sessionCounter}",
            'day1_date' => $this->selectedDates[0],
            'day2_date' => $this->selectedDates[1],
            'day3_date' => $this->selectedDates[2],
            'max_participants' => 50,
            'status' => 'DRAFT',
            'is_manual_schedule' => true
        ]);

        // Renumber all sessions chronologically
        $this->renumberSessions();

        // Store week number before clearing weekInfo
        $weekNumber = $this->weekInfo['week_number'] ?? 'Unknown';

        $this->showDateModal = false;
        $this->isCreatingNewSession = false;
        $this->weekInfo = [];

        Notification::make()
            ->title('Session Created')
            ->body("Training session created for Trainer 2 week {$weekNumber} with selected dates.")
            ->success()
            ->send();
    }

    public function closeDateModal()
    {
        $this->showDateModal = false;
        $this->sessionToUpdate = null;
        $this->selectedDates = [];
        $this->weekDates = [];
        $this->isCreatingNewSession = false;
        $this->weekInfo = [];
    }

    private function mapCategoryToDatabase($category)
    {
        return match($category) {
            'CATEGORY_1' => 'HRDF_WEBINAR',
            'CATEGORY_2' => 'HRDF',
            'CATEGORY_3' => 'WEBINAR',
            default => 'HRDF'
        };
    }

    private function createMeetingForDay($session, $dayNumber, $date)
    {
        try {
            // Following CustomerCalendar pattern for Teams meeting creation
            $accessToken = \App\Services\MicrosoftGraphService::getAccessToken();
            $graph = new \Microsoft\Graph\Graph();
            $graph->setAccessToken($accessToken);

            $startDateTime = Carbon::parse($date)->setTime(9, 0, 0);
            $endDateTime = Carbon::parse($date)->setTime(17, 0, 0);

            $meetingTitle = "Trainer 2 - {$session->session_number} - Day {$dayNumber} Training";
            $meetingBody = "Training session scheduled for Trainer 2 - {$session->training_category} - {$session->training_module}";

            // Get organizer email (default to authenticated user or a system email)
            $organizerEmail = auth()->user()->email ?? config('mail.from.address');

            // Get organizer user for azure_user_id (following CustomerCalendar pattern)
            $organizer = auth()->user();

            // Format meeting request - following CustomerCalendar pattern with attendees
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
                    'displayName' => 'Microsoft Teams Meeting - Trainer 2'
                ],
                'attendees' => [], // Could add training attendees here in future
                'isOnlineMeeting' => true,
                'onlineMeetingProvider' => 'teamsForBusiness'
            ];

            // STEP 1: Create the event using EMAIL (following CustomerCalendar pattern)
            $response = $graph->createRequest("POST", "/users/$organizerEmail/events")
                ->attachBody($meetingRequest)
                ->setReturnType(\Microsoft\Graph\Model\Event::class)
                ->execute();

            // Extract meeting details
            $teamsEventId = $response->getId();
            $meetingLink = null;
            $onlineMeetingId = null;
            $meetingId = null; // Initialize meeting ID variable
            $meetingPassword = null; // Initialize meeting password variable

            // Add null check before accessing getOnlineMeeting() (CustomerCalendar pattern)
            if ($response->getOnlineMeeting() !== null) {
                $meetingLink = $response->getOnlineMeeting()->getJoinUrl();
            }

            \Illuminate\Support\Facades\Log::info('✅ Step 1: Event created successfully (Training Setting Trainer 2)', [
                'event_id' => $teamsEventId,
                'join_url' => $meetingLink,
                'organizer_email' => $organizerEmail,
                'session' => $session->session_number,
                'day' => $dayNumber,
                'trainer' => 'TRAINER_2'
            ]);

            // STEP 2: Query onlineMeetings using AZURE_USER_ID or EMAIL (CustomerCalendar pattern)
            if ($meetingLink && $meetingLink !== 'N/A') {
                try {
                    // Use azure_user_id if available, otherwise email (CustomerCalendar pattern)
                    $queryIdentifier = $organizer->azure_user_id ?? $organizerEmail;
                    $filterQuery = "joinWebUrl eq '$meetingLink'";

                    // Query to get the online meeting ID
                    $onlineMeetingResponse = $graph->createRequest("GET", "/users/$queryIdentifier/onlineMeetings?\$filter=$filterQuery")
                        ->execute();

                    $responseBody = $onlineMeetingResponse->getBody();

                    \Illuminate\Support\Facades\Log::info('✅ Step 2: Online meeting query response (Training Setting Trainer 2)', [
                        'response' => $responseBody,
                        'join_url' => $meetingLink,
                        'query_identifier' => $queryIdentifier,
                        'session' => $session->session_number,
                        'day' => $dayNumber,
                        'trainer' => 'TRAINER_2'
                    ]);

                    // Extract the online meeting ID, meeting ID and passcode from response
                    if (isset($responseBody['value']) && count($responseBody['value']) > 0) {
                        $meetingData = $responseBody['value'][0];
                        $onlineMeetingId = $meetingData['id'] ?? null;

                        // Extract meeting ID and passcode from joinMeetingIdSettings
                        $meetingId = $meetingData['joinMeetingIdSettings']['joinMeetingId'] ?? null;
                        $meetingPassword = $meetingData['joinMeetingIdSettings']['passcode'] ?? null;

                        \Illuminate\Support\Facades\Log::info('✅ Step 2: Online meeting details retrieved (Training Setting Trainer 2)', [
                            'online_meeting_id' => $onlineMeetingId,
                            'meeting_id' => $meetingId,
                            'meeting_password' => $meetingPassword,
                            'event_id' => $teamsEventId,
                            'session' => $session->session_number,
                            'day' => $dayNumber,
                            'trainer' => 'TRAINER_2'
                        ]);

                        // STEP 3: Enable automatic recording (CustomerCalendar pattern)
                        if ($onlineMeetingId) {
                            try {
                                $recordingPayload = [
                                    'recordAutomatically' => true
                                ];

                                $recordingResponse = $graph->createRequest("PATCH", "/users/$queryIdentifier/onlineMeetings/$onlineMeetingId")
                                    ->attachBody($recordingPayload)
                                    ->execute();

                                \Illuminate\Support\Facades\Log::info('✅ Step 3: Automatic recording enabled (Training Setting Trainer 2)', [
                                    'online_meeting_id' => $onlineMeetingId,
                                    'session' => $session->session_number,
                                    'day' => $dayNumber,
                                    'trainer' => 'TRAINER_2'
                                ]);

                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error('❌ Step 3: Failed to enable automatic recording (Training Setting Trainer 2)', [
                                    'error' => $e->getMessage(),
                                    'online_meeting_id' => $onlineMeetingId,
                                    'session' => $session->session_number,
                                    'day' => $dayNumber,
                                    'trainer' => 'TRAINER_2',
                                    'trace' => $e->getTraceAsString()
                                ]);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('❌ Step 2: Failed to retrieve online meeting ID (Training Setting Trainer 2)', [
                        'error' => $e->getMessage(),
                        'join_url' => $meetingLink,
                        'session' => $session->session_number,
                        'day' => $dayNumber,
                        'trainer' => 'TRAINER_2',
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            // Update session with meeting details
            $session->update([
                "day{$dayNumber}_meeting_link" => $meetingLink,
                "day{$dayNumber}_meeting_id" => $meetingId ?? $teamsEventId, // Use actual meeting ID from API, fallback to event ID
                "day{$dayNumber}_meeting_password" => $meetingPassword ?? $this->generateMeetingPassword(), // Use actual passcode from API, fallback to generated
                "day{$dayNumber}_start_time" => $startDateTime,
                "day{$dayNumber}_end_time" => $endDateTime,
            ]);

            \Illuminate\Support\Facades\Log::info('✅ Step 4: Training session saved with meeting details (Trainer 2)', [
                'session_id' => $session->id,
                'session_number' => $session->session_number,
                'day' => $dayNumber,
                'event_id' => $teamsEventId,
                'online_meeting_id' => $onlineMeetingId,
                'meeting_link' => $meetingLink,
                'meeting_id' => $meetingId ?? $teamsEventId,
                'meeting_password' => $meetingPassword ?? 'generated',
                'recording_enabled' => !empty($onlineMeetingId),
                'trainer' => 'TRAINER_2'
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to create Teams meeting for training session Trainer 2: ' . $e->getMessage(), [
                'session' => $session->session_number,
                'day' => $dayNumber,
                'trainer' => 'TRAINER_2',
                'trace' => $e->getTraceAsString()
            ]);

            // Continue without Teams meeting if it fails (CustomerCalendar pattern)
            Notification::make()
                ->title('Teams meeting creation failed')
                ->warning()
                ->body('The training session will be created without Teams meeting details.')
                ->send();

            $session->update([
                "day{$dayNumber}_meeting_link" => "https://teams.microsoft.com/l/meetup-join/demo-trainer2-" . uniqid(),
                "day{$dayNumber}_meeting_id" => 'demo-trainer2-' . uniqid(),
                "day{$dayNumber}_meeting_password" => $this->generateMeetingPassword(),
                "day{$dayNumber}_start_time" => Carbon::parse($date)->setTime(9, 0, 0),
                "day{$dayNumber}_end_time" => Carbon::parse($date)->setTime(17, 0, 0),
            ]);
        }
    }

    private function generateMeetingPassword()
    {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    // Clear sessions for Trainer 2
    public function clearSessions()
    {
        TrainingSession::where('year', $this->selectedYear)
            ->where('trainer_profile', 'TRAINER_2')
            ->delete();
        $this->showCalendar = false;

        Notification::make()
            ->title('Reset Complete')
            ->body('All training sessions for Trainer 2 have been cleared.')
            ->success()
            ->send();
    }

    // Change year
    public function updatedSelectedYear()
    {
        // Check if there are existing sessions for the selected year for Trainer 2
        $existingSessions = TrainingSession::where('year', $this->selectedYear)
            ->where('trainer_profile', 'TRAINER_2')
            ->exists();

        // Show calendar if sessions exist, hide if none
        $this->showCalendar = $existingSessions;

        $this->initializeCollapsedQuarters();
    }

    public function closeCategoryModal()
    {
        $this->showCategoryModal = false;
        $this->sessionToUpdate = null;
        $this->selectedCategory = '';
        $this->selectedModule = '';
    }
}
