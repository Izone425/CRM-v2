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

        // Set default selected implementer to the current user if they're not an admin
        $currentUser = auth()->user();
        $hasAdminAccess = $currentUser->id === 26 || $currentUser->role_id === 3;

        if (!$hasAdminAccess && in_array($currentUser->role_id, [4, 5])) {
            $this->selectedImplementer = $currentUser->name;
        }
    }

    protected function getViewData(): array
    {
        return [
            'years' => $this->getAvailableYears(),
            'implementers' => $this->getImplementers(),
            'weeklyStats' => $this->getWeeklyImplementerStats(),
            'currentWeekNumber' => $this->getCurrentWeekNumber(), // Add this line
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
        // Check if current user has admin access (user_id 26 or role_id 3)
        $currentUser = auth()->user();
        $hasAdminAccess = $currentUser->id === 26 || $currentUser->role_id === 3;

        // If user has admin access, show all implementers
        if ($hasAdminAccess) {
            $implementers = User::whereIn('role_id', [4, 5])
                ->orderBy('name')
                ->get()
                ->pluck('name', 'name')
                ->toArray();

            return ['all' => 'All Implementers'] + $implementers;
        }
        // Otherwise, only show the current user
        else {
            // For implementers, only return their own name
            if (in_array($currentUser->role_id, [4, 5])) {
                return [$currentUser->name => $currentUser->name];
            }
            // For other users, don't show any implementers
            return [];
        }
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

        // Check user permissions
        $currentUser = auth()->user();
        $hasAdminAccess = $currentUser->id === 26 || $currentUser->role_id === 3;

        // If not admin and is an implementer, restrict to own data
        if (!$hasAdminAccess && in_array($currentUser->role_id, [4, 5])) {
            // Force filter by the current user's name
            $query->where('implementer', $currentUser->name);
            // Override selected implementer to ensure it's the current user
            $this->selectedImplementer = $currentUser->name;
        }
        // Otherwise, apply normal filter
        else if ($this->selectedImplementer !== 'all') {
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

    protected function getCurrentWeekNumber(): ?int
    {
        // Only calculate current week if we're viewing the current year
        if ($this->selectedYear !== (int) date('Y')) {
            return null;
        }

        $currentDate = Carbon::now();

        // Find which week number in our array corresponds to the current date
        foreach ($this->getWeeklyImplementerStats() as $weekNumber => $weekData) {
            $weekStart = Carbon::parse(explode(' - ', $weekData['date_range'])[0] . ' ' . $this->selectedYear);
            $weekEnd = Carbon::parse(explode(' - ', $weekData['date_range'])[1] . ' ' . $this->selectedYear);

            if ($currentDate->between($weekStart, $weekEnd)) {
                return $weekNumber;
            }
        }

        return null;
    }
}
