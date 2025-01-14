<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Models\Lead;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarWidget extends FullCalendarWidget
{
    public \Illuminate\Database\Eloquent\Model|string|int|null $record = null;
    public ?int $salesperson = null;
    public ?string $demoType = null;

    public function filterBySalesperson(?int $salesperson): void
    {
        $this->salesperson = $salesperson;
    }

    public function mount(\Illuminate\Database\Eloquent\Model|string|int|null $record = null): void
    {
        $this->record = $record;
    }

    public function filterByDemoType(?string $demoType): void
    {
        $this->demoType = $demoType;
    }

    protected $listeners = [
        'salespersonUpdated' => 'filterBySalesperson',
        'demoTypeUpdated' => 'filterByDemoType',
    ];

    public function fetchEvents(array $fetchInfo): array
    {
        return Appointment::where('date', '>=', $fetchInfo['start'])
            ->where('date', '<=', $fetchInfo['end'])
            ->where('status', '!=', 'Cancelled') // Exclude Cancelled appointments
            ->when($this->salesperson, function ($query) {
                $query->where('salesperson', $this->salesperson); // Apply salesperson filter
            })
            ->when($this->demoType, function ($query) {
                $query->where('type', $this->demoType); // Apply demo type filter
            })
            ->get()
            ->map(function (Appointment $appointment) {
                $startDateTime = "{$appointment->date} {$appointment->start_time}";
                $endDateTime = $appointment->end_time ? "{$appointment->date} {$appointment->end_time}" : null;
                $presenter = User::findOrFail($appointment->salesperson)->name; // Find the selected user

                return [
                    'id'    => $appointment->id,
                    'title' => $appointment->lead->companyDetail->company_name. ' - '. $appointment->type . ' (' . $appointment->status . ')'. ' : ' . $presenter,
                    'start' => \Carbon\Carbon::parse($startDateTime)->toIso8601String(),
                    'end'   => $endDateTime ? \Carbon\Carbon::parse($endDateTime)->toIso8601String() : null,
                    'color' => $appointment->type === 'New Demo' ? '#B91C1C' : '#D8C603',
                ];
            })
            ->toArray();
    }

    public function config(): array
    {
        return [
            'firstDay' => 1,
            'headerToolbar' => [
                'right' => 'dayGridWeek,dayGridDay,dayGridMonth',
                'center' => 'title',
                'left' => 'prev,next today',
            ],
            // 'locale' => [
            //     'buttonText' => [
            //         'today' => 'Today',
            //         'month' => 'month',
            //         'week'  => 'week',
            //         'day'   => 'day',
            //         'list'  => 'list',
            //     ],
            // ],
        ];
    }

    protected function viewAction(): Action
    {
        return ViewAction::make()
            ->label('View Appointment')
            ->url(route('filament.admin.resources.leads.index'))
            ->disabled(); // Disable if no record is selected
    }
}
