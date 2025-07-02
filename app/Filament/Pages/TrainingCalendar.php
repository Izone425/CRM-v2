<?php

namespace App\Filament\Pages;

use App\Models\PublicHoliday;
use App\Models\TrainingCalendarSetting;
use App\Models\TrainingBooking;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class TrainingCalendar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Training Calendar';
    protected static ?string $title = 'Training Calendar';
    protected static ?int $navigationSort = 13;

    protected static string $view = 'filament.pages.training-calendar';

    public $currentMonth;
    public $currentYear;
    public $calendarDays = [];
    public $months = [];
    public $years = [];
    public $selectedDate = null;
    public $bookingMode = false;
    public $managementMode = false;

    // Booking form properties
    public $bookingDate;
    public $paxCount = 1;
    public $attendeeName;
    public $attendeeEmail;
    public $attendeePhone;
    public $additionalNotes;
    public $selectedCompany;
    public $companies = [];

    // Calendar management properties
    public $managementDate;
    public $dateStatus = 'closed';
    public $capacity = 20;

    public $bulkStartDate;
    public $bulkEndDate;
    public $bulkStatus = 'open';
    public $bulkCapacity = 20;
    public $bulkSelectedDays = ['1', '2', '3', '4', '5'];
    public $showBulkManagementModal = false;

    public function mount()
    {
        // Initialize with current month/year
        $today = Carbon::today();
        $this->currentMonth = $today->month;
        $this->currentYear = $today->year;

        $query = \App\Models\CompanyDetail::query()
            ->whereHas('lead', function ($q) {
                $q->where('lead_status', 'Closed');

                if (auth()->user()->role_id == 1) {
                    $q->where('salesperson', auth()->id());
                }
            })
            ->orderBy('company_name');

        $this->companies = $query->pluck('company_name', 'id')->toArray();

        // Generate months and years for dropdowns
        $this->months = collect([
            1 => 'January', 2 => 'February', 3 => 'March',
            4 => 'April', 5 => 'May', 6 => 'June',
            7 => 'July', 8 => 'August', 9 => 'September',
            10 => 'October', 11 => 'November', 12 => 'December'
        ]);

        $this->years = collect(range($today->year, $today->year + 2));
        $this->bulkStartDate = now()->format('Y-m-d');
        $this->bulkEndDate = now()->addMonths(1)->format('Y-m-d');
        // Build calendar days
        $this->buildCalendar();
    }

    public function saveBulkSettings()
    {
        $this->validate([
            'bulkStartDate' => 'required|date',
            'bulkEndDate' => 'required|date|after_or_equal:bulkStartDate',
            'bulkStatus' => 'required|in:open,closed',
            'bulkCapacity' => 'required|integer|min:1|max:100',
            'bulkSelectedDays' => 'required|array|min:1',
        ]);

        $start = Carbon::parse($this->bulkStartDate);
        $end = Carbon::parse($this->bulkEndDate);

        // Create period of dates
        $period = CarbonPeriod::create($start, $end);

        $count = 0;
        foreach ($period as $date) {
            // Only process if the day is in our selected days
            if (in_array((string)$date->dayOfWeek, $this->bulkSelectedDays)) {
                // Skip dates in the past
                if ($date->isPast()) {
                    continue;
                }

                TrainingCalendarSetting::updateOrCreate(
                    ['date' => $date->format('Y-m-d')],
                    [
                        'status' => $this->bulkStatus,
                        'capacity' => $this->bulkCapacity,
                        'updated_by' => auth()->id(),
                    ]
                );

                $count++;
            }
        }

        Notification::make()
            ->title("{$count} training dates updated successfully")
            ->success()
            ->send();

        $this->showBulkManagementModal = false;
        $this->buildCalendar(); // Refresh calendar
    }

    public function buildCalendar()
    {
        // Get start and end of month
        $startOfMonth = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->startOfMonth();
        $endOfMonth = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->endOfMonth();

        // Get days from previous month to fill first week
        $startDay = $startOfMonth->copy()->startOfWeek();

        // Get days from next month to fill last week
        $endDay = $endOfMonth->copy()->endOfWeek();

        // Create period and get all days
        $period = CarbonPeriod::create($startDay, $endDay);

        // Get holidays for this period
        $holidays = PublicHoliday::whereBetween('date', [$startDay->format('Y-m-d'), $endDay->format('Y-m-d')])
            ->get()
            ->keyBy('date');

        // Get training settings for this period
        $trainingSettings = TrainingCalendarSetting::whereBetween('date', [$startDay->format('Y-m-d'), $endDay->format('Y-m-d')])
            ->get()
            ->keyBy('date');

        // Get bookings for this period
        $bookings = TrainingBooking::whereBetween('training_date', [$startDay->format('Y-m-d'), $endDay->format('Y-m-d')])
            ->where('status', 'confirmed')
            ->get()
            ->groupBy('training_date');

        // Build calendar array
        $this->calendarDays = [];

        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');
            $isCurrentMonth = $date->month === (int)$this->currentMonth;
            $isPast = $date->isPast();
            $isHoliday = $holidays->has($dateString);
            $holidayName = $isHoliday ? $holidays[$dateString]->name : null;

            $settings = $trainingSettings->get($dateString);
            $isOpenForTraining = $settings && $settings->status === 'open';

            $dateBookings = $bookings->get($dateString, collect([]));
            $bookedPax = $dateBookings->sum('pax_count');
            $capacity = $settings ? $settings->capacity : 20;
            $availableSlots = max(0, $capacity - $bookedPax);

            $this->calendarDays[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('j'),
                'isCurrentMonth' => $isCurrentMonth,
                'isToday' => $date->isToday(),
                'isPast' => $isPast,
                'isHoliday' => $isHoliday,
                'holidayName' => $holidayName,
                'isOpenForTraining' => $isOpenForTraining,
                'capacity' => $capacity,
                'bookedPax' => $bookedPax,
                'availableSlots' => $availableSlots,
                'dayOfWeek' => $date->format('D'),
            ];
        }
    }

    public function changeMonth($month)
    {
        $this->currentMonth = $month;
        $this->buildCalendar();
    }

    public function changeYear($year)
    {
        $this->currentYear = $year;
        $this->buildCalendar();
    }

    public function selectDate($date)
    {
        $this->selectedDate = $date;
        $calendarDay = collect($this->calendarDays)->firstWhere('date', $date);

        if ($calendarDay['isPast']) {
            Notification::make()
                ->title('Cannot book past dates')
                ->danger()
                ->send();
            return;
        }

        if ($calendarDay['isHoliday']) {
            Notification::make()
                ->title('Cannot book on holidays')
                ->danger()
                ->send();
            return;
        }

        if (!$calendarDay['isOpenForTraining']) {
            if (auth()->user()->role_id === 3 || (auth()->user()->role_id === 1 && auth()->user()->additional_role === 1)) {
                // Manager can manage closed dates
                $this->managementMode = true;
                $this->bookingMode = false;
                $this->managementDate = $date;

                $settings = TrainingCalendarSetting::where('date', $date)->first();
                $this->dateStatus = $settings ? $settings->status : 'closed';
                $this->capacity = $settings ? $settings->capacity : 20;
            } else {
                Notification::make()
                    ->title('This date is not open for training')
                    ->danger()
                    ->send();
            }
            return;
        }

        if ($calendarDay['availableSlots'] <= 0) {
            Notification::make()
                ->title('No available slots for this date')
                ->danger()
                ->send();
            return;
        }

        // Set booking mode
        $this->bookingMode = true;
        $this->managementMode = false;
        $this->bookingDate = $date;
        $this->paxCount = 1;
        $this->attendeeName = '';
        $this->attendeeEmail = '';
        $this->attendeePhone = '';
        $this->additionalNotes = '';
    }

    public function cancelBooking()
    {
        $this->bookingMode = false;
        $this->managementMode = false;
        $this->selectedDate = null;
    }

    public function submitBooking()
    {
        $this->validate([
            'selectedCompany' => 'required|exists:company_details,id',
            'paxCount' => 'required|integer|min:1|max:20',
            'attendeeName' => 'required|string|max:255',
            'attendeeEmail' => 'required|email|max:255',
            'attendeePhone' => 'required|string|max:50',
        ]);

        // Check if date is still available
        $settings = TrainingCalendarSetting::where('date', $this->bookingDate)->first();
        if (!$settings || $settings->status !== 'open') {
            Notification::make()
                ->title('This date is no longer available for booking')
                ->danger()
                ->send();
            return;
        }

        // Check available slots
        $bookedPax = TrainingBooking::where('training_date', $this->bookingDate)
            ->where('status', 'confirmed')
            ->sum('pax_count');

        $availableSlots = max(0, $settings->capacity - $bookedPax);

        if ($this->paxCount > $availableSlots) {
            Notification::make()
                ->title("Only {$availableSlots} slots available")
                ->danger()
                ->send();
            return;
        }

        // Create booking
        $booking = TrainingBooking::create([
            'training_date' => $this->bookingDate,
            'pax_count' => $this->paxCount,
            'company_id' => $this->selectedCompany,
            'additional_notes' => $this->additionalNotes,
            'created_by' => auth()->id(),
        ]);

        // Add main attendee
        $booking->attendees()->create([
            'company_id' => $this->selectedCompany,
            'name' => $this->attendeeName,
            'email' => $this->attendeeEmail,
            'phone' => $this->attendeePhone,
        ]);

        Notification::make()
            ->title('Training booked successfully')
            ->success()
            ->send();

        $this->bookingMode = false;
        $this->selectedDate = null;
        $this->buildCalendar();
    }

    public function updateDateSettings()
    {
        $this->validate([
            'dateStatus' => 'required|in:open,closed',
            'capacity' => 'required|integer|min:1|max:100',
        ]);

        TrainingCalendarSetting::updateOrCreate(
            ['date' => $this->managementDate],
            [
                'status' => $this->dateStatus,
                'capacity' => $this->capacity,
                'updated_by' => auth()->id(),
            ]
        );

        Notification::make()
            ->title('Training date settings updated')
            ->success()
            ->send();

        $this->managementMode = false;
        $this->selectedDate = null;
        $this->buildCalendar();
    }

    public function canManageCalendar(): bool
    {
        $user = auth()->user();
        // Only allow users with role_id 3
        return $user && $user->role_id === 3;
    }
}
