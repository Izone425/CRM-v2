<?php

namespace App\Filament\Pages;

use App\Models\TrainingCalendarSetting;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class TrainingCalendarBulkManagement extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Training Calendar Management';
    protected static ?string $navigationGroup = 'Training Management';

    protected static string $view = 'filament.pages.training-calendar-bulk-management';

    public $startDate;
    public $endDate;
    public $status = 'open';
    public $capacity = 20;
    public $selectedDays = ['1', '2', '3', '4', '5'];

    public function mount()
    {
        // Can only be accessed by managers
        if (!auth()->user() || !(auth()->user()->role_id === 3 || (auth()->user()->role_id === 1 && auth()->user()->additional_role === 1))) {
            abort(403);
        }

        $this->startDate = now()->format('Y-m-d');
        $this->endDate = now()->addMonths(1)->format('Y-m-d');
    }

    public function saveBulkSettings()
    {
        $this->validate([
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
            'status' => 'required|in:open,closed',
            'capacity' => 'required|integer|min:1|max:100',
            'selectedDays' => 'required|array|min:1',
        ]);

        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);

        // Create period of dates
        $period = CarbonPeriod::create($start, $end);

        $count = 0;
        foreach ($period as $date) {
            // Only process if the day is in our selected days
            // 0 = Sunday, 6 = Saturday
            if (in_array((string)$date->dayOfWeek, $this->selectedDays)) {
                // Skip dates in the past
                if ($date->isPast()) {
                    continue;
                }

                TrainingCalendarSetting::updateOrCreate(
                    ['date' => $date->format('Y-m-d')],
                    [
                        'status' => $this->status,
                        'capacity' => $this->capacity,
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
    }
}
