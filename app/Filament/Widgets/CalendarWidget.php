<?php

namespace App\Filament\Widgets;

use App\Classes\Encryptor;
use App\Models\Appointment;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarWidget extends FullCalendarWidget
{
    public ?array $salespersons = []; // Array to handle multiple salespersons
    public ?string $demoAppointmentType = null;
    public ?string $demoType = null;

    public function filterBySalespersons(?array $salespersons): void
    {
        $this->salespersons = $salespersons;
    }

    public function mount(\Illuminate\Database\Eloquent\Model|string|int|null $record = null): void
    {
        $this->record = $record;
    }

    public function filterByDemoAppointmentType(?string $demoAppointmentType): void
    {
        $this->demoAppointmentType = $demoAppointmentType;
    }

    public function filterByDemoType(?string $demoType): void
    {
        $this->demoType = $demoType;
    }

    protected $listeners = [
        'salespersonsUpdated' => 'filterBySalespersons', // Updated listener for multiple salespersons
        'demoTypeUpdated' => 'filterByDemoType',
        'demoAppointmentTypeUpdated' => 'filterByDemoAppointmentType',
    ];

    public function fetchEvents(array $fetchInfo): array
    {
        return Appointment::where('date', '>=', $fetchInfo['start'])
            ->where('date', '<=', $fetchInfo['end'])
            ->where('status', '!=', 'Cancelled') // Exclude Cancelled appointments
            ->when(!empty($this->salespersons) && is_array($this->salespersons), function ($query) {
                $query->whereIn('salesperson', $this->salespersons); // Apply multiple salesperson filter
            })
            ->when($this->demoAppointmentType, function ($query) {
                $query->where('appointment_type', $this->demoAppointmentType); // Apply demo appointment type filter
            })
            ->when($this->demoType, function ($query) {
                $query->where('type', $this->demoType); // Apply demo type filter
            })
            ->get()
            ->map(function (Appointment $appointment) {
                $startDateTime = "{$appointment->date} {$appointment->start_time}";
                $endDateTime = $appointment->end_time ? "{$appointment->date} {$appointment->end_time}" : null;
                $presenter = User::find($appointment->salesperson)?->name ?? 'Unknown'; // Safely retrieve the salesperson name

                // Determine the color dynamically based on the appointment type
                $color = match ($appointment->appointment_type) {
                    'Online Demo' => '#B91C1C',
                    'Webinar Demo' => '#62FB5A',
                    'Onsite Demo' => '#D8C603',
                    default => '#808080',
                };

                return [
                    'id'        => $appointment->id,
                    'title'     => "{$appointment->lead->companyDetail->company_name} - {$appointment->type} ({$appointment->status}): {$presenter}",
                    'start'     => \Carbon\Carbon::parse($startDateTime)->toIso8601String(),
                    'end'       => $endDateTime ? \Carbon\Carbon::parse($endDateTime)->toIso8601String() : null,
                    'color'     => $color,
                    'url'       => route('filament.admin.resources.leads.view', ['record' => Encryptor::encrypt($appointment->lead_id)]),
                ];
            })
            ->toArray();
    }

    public function config(): array
    {
        return [
            'firstDay' => 1,
            'scrollTime' => '08:00:00',
            'height' => 'auto',
            'headerToolbar' => [
                'right' => '',
                'center' => 'title',
                'left' => 'prev,next today',
            ],
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
