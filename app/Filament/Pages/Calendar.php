<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class Calendar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static string $view = 'filament.pages.calendar';
    protected static ?int $navigationSort = 6;


    public $totalDemos = 0;
    public $newDemos = 0;
    public $secondDemos = 0;
    public $webinarDemos = 0;
    public array $selectedSalespersons = []; // Support multiple salespersons
    public ?string $selectedDemoAppointmentType = null; // Demo appointment type filter
    public ?string $selectedDemoType = null; // Demo type filter
    public array $salespersonOptions = [];
    public array $demoAppointmentTypeOptions = [];
    public array $demoTypeOptions = [];
    public bool $allSelected = false;
    public bool $allDemoAppointmentTypeSelected = false;


    public function getTitle(): string | Htmlable
    {
        return __("");
    }

    public function mount(): void
    {
        // Populate salesperson options
        $this->salespersonOptions = User::where('role_id', '2')
            ->pluck('name', 'id')
            ->toArray();

        $this->allSelected = true;

        // Populate demo appointment types
        $this->demoAppointmentTypeOptions = ['ONLINE', 'ONSITE'];

        // Populate demo types
        $this->demoTypeOptions = Appointment::distinct('type')
            ->pluck('type', 'type')
            ->toArray();

        // Custom sort order for demo types
        $customOrder = [
            'New Demo',
            'Second Demo',
            'System Discussion',
            'HRDF Discussion',
        ];

        $this->demoTypeOptions = collect($this->demoTypeOptions)
            ->sortBy(function ($value, $key) use ($customOrder) {
                return array_search($key, $customOrder);
            })
            ->toArray();

        // Initialize all salespersons selected by default
        $this->selectedSalespersons = array_keys($this->salespersonOptions);

        // Update initial counts
        $this->updateCounts();
    }

    public function updatedAllSelected($value): void
    {
        $this->selectedSalespersons = $value
            ? User::where('role_id', '2')->pluck('id')->toArray()
            : [];
    }

    public function updatedSelectedSalespersons(): void
    {
        $this->updateCounts();
        $this->dispatch('salespersonsUpdated', $this->selectedSalespersons);
    }

    public function updatedSelectedDemoAppointmentType(): void
    {
        $this->updateCounts();
        $this->dispatch('demoAppointmentTypeUpdated', $this->selectedDemoAppointmentType);
    }

    public function updatedSelectedDemoType(): void
    {
        $this->updateCounts();
        $this->dispatch('demoTypeUpdated', $this->selectedDemoType);
    }

    protected function updateCounts(): void
    {
        // Base query excluding cancelled appointments
        $query = Appointment::where('status', '!=', 'Cancelled');

        // Apply salesperson filter
        if (!empty($this->selectedSalespersons)) {
            $query->whereIn('salesperson', $this->selectedSalespersons);
        }

        // Apply demo appointment type filter
        if ($this->selectedDemoAppointmentType) {
            $query->where('appointment_type', $this->selectedDemoAppointmentType);
        }

        // Apply demo type filter
        if ($this->selectedDemoType) {
            $query->where('type', $this->selectedDemoType);
        }
        // Update counts
        $this->totalDemos = $query->count();
        $this->newDemos = (clone $query)->where('appointment_type', 'Online Demo')->count();
        $this->secondDemos = (clone $query)->where('appointment_type', 'Onsite Demo')->count();
        $this->webinarDemos = (clone $query)->where('appointment_type', 'Webinar Demo')->count();
    }
}
