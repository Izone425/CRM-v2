<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\ImplementerAppointment;
use App\Models\User;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class ImplementerRequestCount extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Implementer Request Analysis';
    protected static ?int $navigationSort = 16;
    protected static string $view = 'filament.pages.implementer-request-count';

    public int $selectedYear;
    public string $selectedImplementer = 'all';

    public function mount(): void
    {
        $this->selectedYear = (int) date('Y');
    }

    protected function getViewData(): array
    {
        return [
            'years' => $this->getAvailableYears(),
            'implementers' => $this->getImplementers(),
            'weeklyStats' => $this->getWeeklyImplementerStats(),
        ];
    }

    protected function getAvailableYears(): array
    {
        $currentYear = (int) date('Y');
        return [
            $currentYear - 1 => (string) ($currentYear - 1),
            $currentYear => (string) $currentYear,
            $currentYear + 1 => (string) ($currentYear + 1),
            $currentYear + 2 => (string) ($currentYear + 2),
        ];
    }

    protected function getImplementers(): array
    {
        $implementers = User::whereIn('role_id', [4, 5])
            ->orderBy('name')
            ->get()
            ->pluck('name', 'name')
            ->toArray();

        return ['all' => 'All Implementers'] + $implementers;
    }

    protected function getWeeklyImplementerStats(): array
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

            // Store both the display week number and MySQL week number
            $weeks[$weekNumber] = [
                'start' => clone $weekStart,
                'end' => clone $weekEnd,
                'date_range' => $weekStart->format('j M Y') . ' - ' . $weekEnd->format('j M Y'),
                'mysql_week' => (int)$weekStart->format('W'), // Store MySQL week number for matching
            ];

            $currentDate->addWeek();
            $weekNumber++;
        }

        // Get appointment data
        $query = ImplementerAppointment::whereBetween('date', [$startOfYear, $endOfYear])
            ->where('status', '!=', 'Cancelled')
            ->whereIn('type', ['DATA MIGRATION SESSION', 'SYSTEM SETTING SESSION', 'WEEKLY FOLLOW UP SESSION']);

        // Filter by implementer if needed
        if ($this->selectedImplementer !== 'all') {
            $query->where('implementer', $this->selectedImplementer);
        }

        $appointments = $query->select(
            'type',
            DB::raw('WEEK(date, 1) as week_number'),
            DB::raw('YEAR(date) as year'),
            DB::raw('COUNT(*) as count')
        )
        ->groupBy('type', 'week_number', 'year')
        ->get();

        // Process appointment data into weekly stats
        $weeklyStats = [];

        foreach ($weeks as $weekNumber => $weekData) {
            // Use the MySQL week number for matching
            $mysqlWeekNumber = $weekData['mysql_week'];

            // Initialize stats for this week
            $dataMigrationCount = 0;
            $systemSettingCount = 0;
            $weeklyFollowUpCount = 0;

            // Find appointments for this week using MySQL week number
            foreach ($appointments as $appointment) {
                if ((int)$appointment->week_number === $mysqlWeekNumber && (int)$appointment->year === $this->selectedYear) {
                    // Count by session type
                    if ($appointment->type === 'DATA MIGRATION SESSION') {
                        $dataMigrationCount += $appointment->count;
                    }
                    elseif ($appointment->type === 'SYSTEM SETTING SESSION') {
                        $systemSettingCount += $appointment->count;
                    }
                    elseif ($appointment->type === 'WEEKLY FOLLOW UP SESSION') {
                        $weeklyFollowUpCount += $appointment->count;
                    }
                }
            }

            // Calculate total
            $totalSessions = $dataMigrationCount + $systemSettingCount + $weeklyFollowUpCount;

            $weeklyStats[$weekNumber] = [
                'week_number' => $weekNumber,
                'date_range' => $weekData['date_range'],
                'data_migration_count' => $dataMigrationCount,
                'system_setting_count' => $systemSettingCount,
                'weekly_follow_up_count' => $weeklyFollowUpCount,
                'total_sessions' => $totalSessions,
            ];
        }

        return $weeklyStats;
    }
}
