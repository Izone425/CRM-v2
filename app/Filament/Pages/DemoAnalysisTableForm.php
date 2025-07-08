<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DemoAnalysisTableForm extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Demo Analysis';
    protected static ?int $navigationSort = 15;
    protected static string $view = 'filament.pages.demo-analysis-table-form';

    public int $selectedYear;
    public string $selectedSalesperson = 'all';

    public function mount(): void
    {
        $this->selectedYear = (int) date('Y');
    }

    protected function getViewData(): array
    {
        return [
            'years' => $this->getAvailableYears(),
            'salespeople' => $this->getSalespeople(),
            'weeklyStats' => $this->getWeeklyDemoStats(),
        ];
    }

    protected function getAvailableYears(): array
    {
        $currentYear = (int) date('Y');
        return [
            $currentYear - 1 => (string) ($currentYear - 1),
            $currentYear => (string) $currentYear,
            $currentYear + 1 => (string) ($currentYear + 1),
        ];
    }

    protected function getSalespeople(): array
    {
        $salespeople = User::where('role_id', 2)
            ->orderBy('name')
            ->get()
            ->pluck('name', 'id')
            ->toArray();

        return ['all' => 'All Salespeople'] + $salespeople;
    }

    protected function getWeeklyDemoStats(): array
    {
        $startOfYear = Carbon::createFromDate($this->selectedYear, 1, 1)->startOfYear();
        $endOfYear = Carbon::createFromDate($this->selectedYear, 12, 31)->endOfYear();

        // Generate all weeks for the selected year
        $weeks = [];
        $currentDate = clone $startOfYear;
        $weekNumber = 1;

        while ($currentDate->year === $this->selectedYear) {
            $weekStart = clone $currentDate->startOfWeek();
            $weekEnd = clone $currentDate->endOfWeek();

            // Adjust to show only Monday to Friday
            if ($weekEnd->dayOfWeek === 0) { // Sunday
                $weekEnd->subDays(2); // Go back to Friday
            }
            if ($weekEnd->dayOfWeek === 6) { // Saturday
                $weekEnd->subDays(1); // Go back to Friday
            }
            if ($weekStart->dayOfWeek === 0) { // Sunday
                $weekStart->addDays(1); // Move to Monday
            }
            if ($weekStart->dayOfWeek === 6) { // Saturday
                $weekStart->addDays(2); // Move to Monday
            }

            $weeks[$weekNumber] = [
                'start' => clone $weekStart,
                'end' => clone $weekEnd,
                'date_range' => $weekStart->format('j M Y') . ' - ' . $weekEnd->format('j M Y'),
            ];

            $currentDate->addWeek();
            $weekNumber++;
        }

        // Get appointment data
        $query = Appointment::whereBetween('date', [$startOfYear, $endOfYear]);

        // Filter by salesperson if needed
        if ($this->selectedSalesperson !== 'all') {
            $query->where('salesperson', $this->selectedSalesperson);
        }

        $appointments = $query->select(
            'type', // Now stores 'NEW DEMO' or other types
            'appointment_type', // Now stores 'ONLINE', 'WEBINAR', etc.
            'status', // Now could be 'Done', 'New', etc.
            DB::raw('WEEK(date) as week_number'),
            DB::raw('YEAR(date) as year'),
            DB::raw('COUNT(*) as count')
        )
        ->groupBy('type', 'appointment_type', 'status', 'week_number', 'year')
        ->get();

        // Process appointment data into weekly stats
        $weeklyStats = [];

        foreach ($weeks as $weekNumber => $weekData) {
            $weekStart = $weekData['start'];
            $weekEnd = $weekData['end'];

            // Calculate the correct week number for Carbon/MySQL compatibility
            $mysqlWeekNumber = $weekStart->format('W');

            // Initialize stats for this week
            $newDemoCount = 0;
            $webinarDemoCount = 0;
            $totalTarget = 70; // Weekly target (adjust as needed)

            // Find appointments for this week
            foreach ($appointments as $appointment) {
                if ((int)$appointment->week_number === (int)$mysqlWeekNumber && (int)$appointment->year === $this->selectedYear) {
                    // Count NEW DEMO type appointments
                    if (strtoupper($appointment->type) === 'NEW DEMO') {
                        $newDemoCount += $appointment->count;
                    }

                    // Count WEBINAR appointment types
                    if (strtoupper($appointment->type) === 'WEBINAR DEMO') {
                        $webinarDemoCount += $appointment->count;
                    }
                }
            }

            // Calculate percentages
            $newDemoPercentage = $totalTarget > 0 ? round(($newDemoCount / $totalTarget) * 100) : 0;
            $webinarDemoPercentage = $totalTarget > 0 ? round(($webinarDemoCount / $totalTarget) * 100) : 0;

            $weeklyStats[$weekNumber] = [
                'week_number' => $weekNumber,
                'date_range' => $weekData['date_range'],
                'new_demo_count' => $newDemoCount,
                'new_demo_target' => $totalTarget,
                'new_demo_percentage' => $newDemoPercentage,
                'webinar_demo_count' => $webinarDemoCount,
                'webinar_demo_target' => $totalTarget,
                'webinar_demo_percentage' => $webinarDemoPercentage,
            ];
        }

        return $weeklyStats;
    }
}
