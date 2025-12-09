<?php
// filepath: /var/www/html/timeteccrm/app/Filament/Pages/TrainingSetting.php

namespace App\Filament\Pages;

use App\Models\TrainingSession;
use App\Models\PublicHoliday;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use Carbon\Carbon;
use Filament\Notifications\Notification;

class TrainingSetting extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Training Setting';
    protected static ?string $title = 'Training Setting Management';
    protected static string $view = 'filament.pages.training-setting';
    protected static ?int $navigationSort = 60;

    // Step 1-5: Initial Setup
    public ?string $selectedTrainer = null;
    public ?int $selectedYear = null;
    public ?string $selectedCategory = null;
    public ?string $selectedModule = null;
    public string $viewMode = 'month'; // month | quarter

    // Step 6: Manual Scheduling
    public array $manualSessions = [];
    public array $selectedDatesForManual = [];
    public ?string $currentManualSession = null;

    // Step 7: Session Configuration
    public ?int $selectedSessionForConfig = null;

    // Current Step
    public int $currentStep = 1;
    public bool $showSchedule = false;
    public bool $showConfiguration = false;

    public function mount()
    {
        $this->selectedYear = now()->year;
    }

    // Check if year has existing data
    #[Computed]
    public function getHasExistingData(): bool
    {
        if (!$this->selectedYear) {
            return false;
        }

        return TrainingSession::where('year', $this->selectedYear)
            ->when($this->selectedTrainer, fn($q) => $q->where('trainer_profile', $this->selectedTrainer))
            ->when($this->selectedCategory, fn($q) => $q->where('training_category', $this->selectedCategory))
            ->when($this->selectedModule, fn($q) => $q->where('training_module', $this->selectedModule))
            ->exists();
    }

    // Auto-Generate Training Sessions Button Action
    public function generateYearlySchedule()
    {
        if (!$this->validateAllSteps()) {
            Notification::make()
                ->title('Incomplete Information')
                ->body('Please fill all required fields before generating schedule.')
                ->warning()
                ->send();
            return;
        }

        // Check if data already exists
        if ($this->getHasExistingData()) {
            Notification::make()
                ->title('Data Already Exists')
                ->body('Training schedule for this configuration already exists.')
                ->warning()
                ->send();
            return;
        }

        $maxParticipants = $this->selectedCategory === 'HRDF' ? 50 : 100;

        // Get all public holidays for the year
        $holidays = PublicHoliday::whereYear('date', $this->selectedYear)->pluck('date')->toArray();

        $sessionCounter = 1;
        $createdSessions = 0;
        $manualSessions = [];

        // Start from first week of January
        $currentDate = Carbon::create($this->selectedYear, 1, 1)->startOfWeek();
        $endOfYear = Carbon::create($this->selectedYear, 12, 31);

        while ($currentDate->lte($endOfYear)) {
            $weekDates = $this->getWeekTrainingDates($currentDate, $holidays);

            if ($weekDates['auto']) {
                // Auto-generate session
                TrainingSession::create([
                    'trainer_profile' => $this->selectedTrainer,
                    'year' => $this->selectedYear,
                    'training_category' => $this->selectedCategory,
                    'training_module' => $this->selectedModule,
                    'session_number' => "SESSION {$sessionCounter}",
                    'day1_date' => $weekDates['dates'][0],
                    'day2_date' => $weekDates['dates'][1],
                    'day3_date' => $weekDates['dates'][2],
                    'max_participants' => $maxParticipants,
                    'is_manual_schedule' => false
                ]);

                $createdSessions++;
            } else {
                // Add to manual sessions list
                $manualSessions[] = [
                    'week_start' => $currentDate->copy(),
                    'session_number' => "SESSION {$sessionCounter}",
                    'conflicting_holidays' => $weekDates['conflicts']
                ];
            }

            $sessionCounter++;
            $currentDate->addWeek();
        }

        $this->manualSessions = $manualSessions;
        $this->showSchedule = true;
        $this->currentStep = 6;

        Notification::make()
            ->title('Schedule Generated')
            ->body("Created {$createdSessions} automatic sessions. " . count($manualSessions) . " sessions need manual scheduling.")
            ->success()
            ->send();
    }

    /**
     * Get training dates for a week (Tue, Wed, Thu)
     */
    private function getWeekTrainingDates(Carbon $weekStart, array $holidays): array
    {
        $tuesday = $weekStart->copy()->next(Carbon::TUESDAY);
        $wednesday = $tuesday->copy()->addDay();
        $thursday = $wednesday->copy()->addDay();

        $proposedDates = [$tuesday, $wednesday, $thursday];
        $conflicts = [];

        foreach ($proposedDates as $date) {
            if (in_array($date->format('Y-m-d'), $holidays)) {
                $conflicts[] = $date->format('Y-m-d');
            }
        }

        if (empty($conflicts)) {
            return [
                'auto' => true,
                'dates' => array_map(fn($date) => $date->format('Y-m-d'), $proposedDates),
                'conflicts' => []
            ];
        } else {
            // Try alternative combinations (Mon-Tue-Wed or Wed-Thu-Fri)
            $alternatives = $this->getAlternativeDates($weekStart, $holidays);

            if ($alternatives) {
                return [
                    'auto' => true,
                    'dates' => $alternatives,
                    'conflicts' => []
                ];
            } else {
                return [
                    'auto' => false,
                    'dates' => [],
                    'conflicts' => $conflicts
                ];
            }
        }
    }

    /**
     * Get alternative 3-day combinations if Tue/Wed/Thu has conflicts
     */
    private function getAlternativeDates(Carbon $weekStart, array $holidays): ?array
    {
        $weekDays = [];
        for ($i = 1; $i <= 5; $i++) { // Mon-Fri
            $day = $weekStart->copy()->addDays($i);
            if (!in_array($day->format('Y-m-d'), $holidays)) {
                $weekDays[] = $day->format('Y-m-d');
            }
        }

        // Need exactly 3 consecutive weekdays
        if (count($weekDays) >= 3) {
            return array_slice($weekDays, 0, 3);
        }

        return null;
    }

    private function validateAllSteps(): bool
    {
        return $this->selectedTrainer &&
               $this->selectedYear &&
               $this->selectedCategory &&
               $this->selectedModule;
    }

    // Manual Session Scheduling (Step 6)
    public function startManualScheduling(int $sessionIndex)
    {
        $this->currentManualSession = $sessionIndex;
        $this->selectedDatesForManual = [];
    }

    public function toggleManualDate(string $date)
    {
        if (in_array($date, $this->selectedDatesForManual)) {
            $this->selectedDatesForManual = array_values(array_diff($this->selectedDatesForManual, [$date]));
        } elseif (count($this->selectedDatesForManual) < 3) {
            $this->selectedDatesForManual[] = $date;
        }
    }

    public function saveManualSession()
    {
        if (count($this->selectedDatesForManual) === 3 && is_numeric($this->currentManualSession)) {
            $sessionData = $this->manualSessions[$this->currentManualSession];
            $maxParticipants = $this->selectedCategory === 'HRDF' ? 50 : 100;

            TrainingSession::create([
                'trainer_profile' => $this->selectedTrainer,
                'year' => $this->selectedYear,
                'training_category' => $this->selectedCategory,
                'training_module' => $this->selectedModule,
                'session_number' => $sessionData['session_number'],
                'day1_date' => $this->selectedDatesForManual[0],
                'day2_date' => $this->selectedDatesForManual[1],
                'day3_date' => $this->selectedDatesForManual[2],
                'max_participants' => $maxParticipants,
                'is_manual_schedule' => true
            ]);

            // Remove from manual sessions list
            unset($this->manualSessions[$this->currentManualSession]);
            $this->manualSessions = array_values($this->manualSessions);
            $this->currentManualSession = null;
            $this->selectedDatesForManual = [];

            Notification::make()
                ->title('Manual Session Created')
                ->body('Training session has been scheduled successfully.')
                ->success()
                ->send();
        }
    }

    public function submitPart1()
    {
        $this->showConfiguration = true;
        $this->currentStep = 7;
    }

    // Session Configuration (Step 7)
    public function selectSessionForConfig(int $sessionId)
    {
        $this->selectedSessionForConfig = $sessionId;
    }

    public function updateSessionField(int $sessionId, string $field, $value)
    {
        TrainingSession::find($sessionId)->update([$field => $value]);

        Notification::make()
            ->title('Updated')
            ->body('Training session updated successfully.')
            ->success()
            ->send();
    }

    public function submitPart2()
    {
        if ($this->selectedSessionForConfig) {
            TrainingSession::find($this->selectedSessionForConfig)->update(['status' => 'SCHEDULED']);

            Notification::make()
                ->title('Session Configured')
                ->body('Training session has been fully configured and scheduled.')
                ->success()
                ->send();

            $this->selectedSessionForConfig = null;
        }
    }

    // Computed Properties
    #[Computed]
    public function getScheduledSessions()
    {
        if (!$this->selectedYear) {
            return collect();
        }

        $query = TrainingSession::query()
            ->where('year', $this->selectedYear)
            ->when($this->selectedTrainer, fn($q) => $q->where('trainer_profile', $this->selectedTrainer))
            ->when($this->selectedCategory, fn($q) => $q->where('training_category', $this->selectedCategory))
            ->when($this->selectedModule, fn($q) => $q->where('training_module', $this->selectedModule))
            ->orderBy('day1_date');

        if ($this->viewMode === 'quarter') {
            return $query->get()->groupBy(function($session) {
                $month = Carbon::parse($session->day1_date)->month;
                $quarter = ceil($month / 3);
                return "Q{$quarter}";
            });
        } else {
            return $query->get()->groupBy(function($session) {
                return Carbon::parse($session->day1_date)->format('F Y');
            });
        }
    }

    #[Computed]
    public function getAvailableDatesForManual()
    {
        if (!is_numeric($this->currentManualSession) || !isset($this->manualSessions[$this->currentManualSession])) {
            return [];
        }

        $sessionData = $this->manualSessions[$this->currentManualSession];
        $weekStart = $sessionData['week_start'];

        $dates = [];
        for ($i = 1; $i <= 5; $i++) { // Monday to Friday
            $date = $weekStart->copy()->addDays($i);
            $dates[] = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->format('l'),
                'formatted' => $date->format('D, M j'),
                'is_selected' => in_array($date->format('Y-m-d'), $this->selectedDatesForManual),
                'is_holiday' => in_array($date->format('Y-m-d'), $sessionData['conflicting_holidays'] ?? [])
            ];
        }

        return $dates;
    }

    // Utility Methods
    public function switchViewMode(string $mode)
    {
        $this->viewMode = $mode;
    }

    public function resetWizard()
    {
        $this->selectedTrainer = null;
        $this->selectedYear = now()->year;
        $this->selectedCategory = null;
        $this->selectedModule = null;
        $this->manualSessions = [];
        $this->selectedDatesForManual = [];
        $this->currentManualSession = null;
        $this->selectedSessionForConfig = null;
        $this->currentStep = 1;
        $this->showSchedule = false;
        $this->showConfiguration = false;
    }

    public function goToStep(int $step)
    {
        $this->currentStep = $step;
    }

    // Step navigation helpers
    public function nextStep()
    {
        if ($this->currentStep < 8) {
            $this->currentStep++;
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }
}
