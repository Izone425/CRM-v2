<?php

namespace App\Filament\Pages;

use App\Models\OvertimeSchedule;
use App\Models\User;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class OvertimeCalendar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Overtime Schedule';
    protected static ?string $navigationGroup = 'Support Information';
    protected static ?string $title = 'Overtime Calendar';
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.overtime-calendar';

    public $selectedYear = '';
    public $months = [];
    public $users = [];
    public $editMode = false;

    public function mount()
    {
        $this->selectedYear = request()->query('year', Carbon::now()->year);
        $this->loadCalendarData();
        $this->users = User::whereIn('role_id', [3, 4, 5, 6])
            ->orderBy('name')
            ->get();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('year_2025')
                ->label('2025')
                ->url(fn() => route('filament.admin.pages.overtime-calendar', ['year' => 2025]))
                ->color('success'),
            Action::make('year_2026')
                ->label('2026')
                ->url(fn() => route('filament.admin.pages.overtime-calendar', ['year' => 2026]))
                ->color('info'),
            Action::make('year_2027')
                ->label('2027')
                ->url(fn() => route('filament.admin.pages.overtime-calendar', ['year' => 2027]))
                ->color('warning'),
            Action::make('toggle_edit')
                ->label(fn() => $this->editMode ? 'Exit Edit Mode' : 'Edit')
                ->action(fn() => $this->toggleEditMode())
                ->visible(fn() => auth()->check() && auth()->user()->role_id === 3)
                ->color(fn() => $this->editMode ? 'danger' : 'primary'),
        ];
    }

    public function toggleEditMode()
    {
        $this->editMode = !$this->editMode;
    }

    public function loadCalendarData()
    {
        $year = $this->selectedYear;
        $startDate = Carbon::createFromDate($year, 1, 1);
        $endDate = Carbon::createFromDate($year, 12, 31);

        // Initialize months structure
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthName = Carbon::create($year, $m, 1)->format('F');
            $months[$monthName] = [
                'name' => $monthName,
                'weeks' => [],
            ];
        }

        // Get all weekends in the selected year
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            // If it's a Saturday, add it to weekends
            if ($currentDate->dayOfWeek === Carbon::SATURDAY) {
                $saturday = $currentDate->format('Y-m-d');
                $sunday = $currentDate->copy()->addDay()->format('Y-m-d');
                $month = $currentDate->format('F');
                $weekNumber = ceil($currentDate->format('j') / 7);

                if (!isset($months[$month]['weeks']["W{$weekNumber}"])) {
                    $months[$month]['weeks']["W{$weekNumber}"] = [
                        'week_number' => "W{$weekNumber}",
                        'dates' => $currentDate->format('j') . ' & ' . $currentDate->copy()->addDay()->format('j'),
                        'date_start' => $saturday,
                        'date_end' => $sunday,
                        'user_id' => null,
                        'user_name' => 'Unassigned',
                        'status' => null,
                        'record_id' => null,
                        'css_class' => 'unassigned',
                    ];
                }
            }

            $currentDate->addDay();
        }

        // Load assigned staff for each weekend
        $overtimeSchedules = OvertimeSchedule::whereYear('weekend_date', $year)->get();

        // Debug the fetched records
        Log::info("Loaded overtime schedules for {$year}", [
            'count' => $overtimeSchedules->count(),
            'records' => $overtimeSchedules->map(function($item) {
                return [
                    'id' => $item->id,
                    'weekend_date' => $item->weekend_date->format('Y-m-d'),
                    'user_id' => $item->user_id
                ];
            })->toArray()
        ]);

        foreach ($months as $monthName => &$month) {
            foreach ($month['weeks'] as $weekNumber => &$week) {
                // Convert date format to ensure proper comparison
                $dateToFind = $week['date_start'];

                // Debug this specific date
                Log::info("Looking for schedule with weekend_date = {$dateToFind}");

                // Find the schedule for this weekend
                $schedule = null;
                foreach ($overtimeSchedules as $s) {
                    if ($s->weekend_date->format('Y-m-d') === $dateToFind) {
                        $schedule = $s;
                        Log::info("Match found for {$dateToFind} with schedule ID: {$s->id}");
                        break;
                    }
                }

                if ($schedule) {
                    Log::info("Found schedule for {$week['date_start']}", [
                        'schedule_id' => $schedule->id,
                        'user_id' => $schedule->user_id,
                        'status' => $schedule->status
                    ]);

                    $week['user_id'] = $schedule->user_id;

                    // Get user name from database
                    $user = User::find($schedule->user_id);
                    $week['user_name'] = $user ? $user->name : 'Unknown User';

                    $week['status'] = $schedule->status;
                    $week['record_id'] = $schedule->id;

                    // Define colors using CSS class names
                    if ($schedule->status === 'completed') {
                        $week['css_class'] = 'staff-color-6'; // Green for completed
                    } else {
                        // Assign consistent color based on user ID
                        $colorIndex = $schedule->user_id % 7;
                        $week['css_class'] = "staff-color-{$colorIndex}";
                    }
                }
            }
        }

        $this->months = $months;
    }

    public function assignStaff($weekendDate, $userId)
    {
        if (!$this->editMode) {
            Notification::make()
                ->title('Edit mode is disabled')
                ->warning()
                ->send();
            return;
        }

        try {
            // Log the incoming data
            Log::info('Assigning staff', [
                'weekendDate' => $weekendDate,
                'userId' => $userId,
                'editMode' => $this->editMode
            ]);

            // If userId is empty, treat it as unassignment
            if (empty($userId)) {
                // Check if there's an existing schedule to delete
                $existingSchedule = OvertimeSchedule::where('weekend_date', $weekendDate)->first();
                if ($existingSchedule) {
                    Log::info("Deleting schedule", ['id' => $existingSchedule->id]);
                    $existingSchedule->delete();

                    Notification::make()
                        ->title('Staff unassigned successfully')
                        ->success()
                        ->send();
                }
            } else {
                // Check if a record already exists for this date
                $schedule = OvertimeSchedule::where('weekend_date', $weekendDate)->first();

                $isNew = !$schedule;
                if (!$schedule) {
                    // Create new record
                    $schedule = new OvertimeSchedule();
                    $schedule->weekend_date = $weekendDate;
                    Log::info("Creating new schedule for date", ['date' => $weekendDate]);
                } else {
                    Log::info("Updating existing schedule", ['id' => $schedule->id]);
                }

                $schedule->user_id = $userId;
                $schedule->status = $schedule->status ?? 'scheduled';

                // Save and log result
                $saved = $schedule->save();
                Log::info('Save result', [
                    'success' => $saved,
                    'record_id' => $schedule->id,
                    'is_new' => $isNew,
                    'data' => $schedule->toArray()
                ]);

                if ($saved) {
                    Notification::make()
                        ->title('Staff assigned successfully')
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Failed to save assignment')
                        ->danger()
                        ->send();
                }
            }
        } catch (\Exception $e) {
            Log::error("Error assigning staff: " . $e->getMessage());
            Log::error($e->getTraceAsString());

            Notification::make()
                ->title('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }

        // Reload calendar data
        $this->loadCalendarData();
    }
}
