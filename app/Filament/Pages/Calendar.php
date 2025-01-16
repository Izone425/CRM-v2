<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\User;
use Filament\Pages\Page;

class Calendar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static string $view = 'filament.pages.calendar';
    protected static ?int $navigationSort = 6;

    public $totalDemos = 0;
    public $newDemos = 0;
    public $secondDemos = 0;
    public ?int $selectedSalesperson = null;
    public ?string $selectedDemoType = null; // New property for demo type filter
    public array $salespersonOptions = [];
    public array $demoTypeOptions = []; // Options for the demo type dropdown

    public function mount(): void
    {
        $this->salespersonOptions = User::where('role_id', '2')
            ->pluck('name', 'id')
            ->toArray();

        $this->demoTypeOptions = Appointment::distinct('type')
            ->pluck('type', 'type') // Fetch unique demo types from the `type` column
            ->toArray();

        $this->updateCounts();
    }

    public function updatedSelectedSalesperson($value): void
    {
        $this->updateCounts();
        $this->dispatch('salespersonUpdated', $value);
    }

    public function updatedSelectedDemoType($value): void
    {
        $this->updateCounts();
        $this->dispatch('demoTypeUpdated', $value);
    }

    protected function updateCounts(): void
    {
        // Base query with Cancelled status excluded
        $query = Appointment::where('status', '!=', 'Cancelled');

        if ($this->selectedSalesperson) {
            $query->where('salesperson', $this->selectedSalesperson);
        }

        if ($this->selectedDemoType) {
            $query->where('type', $this->selectedDemoType);
        }

        $this->totalDemos = $query->count();
        $this->newDemos = (clone $query)->where('appointment_type', 'Online Demo')->count();
        $this->secondDemos = (clone $query)->where('appointment_type', 'Onsite Demo')->count();
    }
}
