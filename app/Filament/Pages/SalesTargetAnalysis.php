<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\Lead;
use App\Models\RevenueTarget;
use App\Models\SalesTarget;
use App\Models\User;
use App\Models\YearlyTarget;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class SalesTargetAnalysis extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Sales Target Analysis';
    protected static ?string $title = '';
    protected static ?int $navigationSort = 16;
    protected static string $view = 'filament.pages.sales-target-analysis';

    public int $selectedYear;
    public bool $editMode = false;

    // Sales targets by month (to be entered by user)
    public array $salesTargets = [];

    public function mount(): void
    {
        $this->selectedYear = (int) date('Y');

        // Initialize empty sales targets for all months
        $this->loadSalesTargets();
    }

    protected function loadSalesTargets(): void
    {
        // Initialize with zeros first
        $months = range(1, 12);
        foreach ($months as $month) {
            $this->salesTargets[$month] = 0;
        }

        // Load saved targets from the database if they exist
        $targets = YearlyTarget::where('year', $this->selectedYear)
            ->where('salesperson', 0)
            ->get();

        foreach ($targets as $target) {
            $this->salesTargets[$target->month] = $target->target_amount;
        }
    }

    public function updatedSelectedYear()
    {
        $this->loadSalesTargets();

        // Force refresh of view data by dispatching a browser event
        $this->dispatch('refresh');
    }

    public function toggleEditMode(): void
    {
        $this->editMode = !$this->editMode;
    }

    public function saveTargets(): void
    {
        // Save targets to database
        foreach ($this->salesTargets as $month => $value) {
            YearlyTarget::updateOrCreate(
                [
                    'year' => $this->selectedYear,
                    'month' => $month,
                    'salesperson' => 0,
                ],
                [
                    'target_amount' => $value,
                ]
            );
        }

        $this->editMode = false;

        Notification::make()
            ->title('Sales targets saved successfully')
            ->success()
            ->send();
    }

    public function updateSalesTarget(int $month, $value): void
    {
        $this->salesTargets[$month] = (float) $value;
    }

    protected function getViewData(): array
    {
        return [
            'years' => $this->getAvailableYears(),
            'monthlyStats' => $this->getMonthlyStats(),
        ];
    }

    protected function getAvailableYears(): array
    {
        $currentYear = (int) date('Y');
        return [
            $currentYear => (string) $currentYear,
            $currentYear + 1 => (string) ($currentYear + 1),
            $currentYear + 2 => (string) ($currentYear + 2),
        ];
    }

    protected function getMonthlyStats(): array
    {
        // Define week ranges for different years
        $yearWeekRanges = [
            2025 => [
                1 => ['weeks' => 'W1-W5', 'weekCount' => 5],
                2 => ['weeks' => 'W6-W9', 'weekCount' => 4],
                3 => ['weeks' => 'W10-W13', 'weekCount' => 4],
                4 => ['weeks' => 'W14-W18', 'weekCount' => 5],
                5 => ['weeks' => 'W19-W22', 'weekCount' => 4],
                6 => ['weeks' => 'W23-W26', 'weekCount' => 4],
                7 => ['weeks' => 'W27-W31', 'weekCount' => 5],
                8 => ['weeks' => 'W32-W35', 'weekCount' => 4],
                9 => ['weeks' => 'W36-W39', 'weekCount' => 4],
                10 => ['weeks' => 'W40-W44', 'weekCount' => 5],
                11 => ['weeks' => 'W45-W48', 'weekCount' => 4],
                12 => ['weeks' => 'W49-W52', 'weekCount' => 4],
            ],
            2026 => [
                1 => ['weeks' => 'W1-W4', 'weekCount' => 4],
                2 => ['weeks' => 'W5-W8', 'weekCount' => 4],
                3 => ['weeks' => 'W9-W13', 'weekCount' => 5],
                4 => ['weeks' => 'W14-W17', 'weekCount' => 4],
                5 => ['weeks' => 'W18-W22', 'weekCount' => 5],
                6 => ['weeks' => 'W23-W26', 'weekCount' => 4],
                7 => ['weeks' => 'W27-W30', 'weekCount' => 4],
                8 => ['weeks' => 'W31-W35', 'weekCount' => 5],
                9 => ['weeks' => 'W36-W39', 'weekCount' => 4],
                10 => ['weeks' => 'W40-W44', 'weekCount' => 5],
                11 => ['weeks' => 'W45-W48', 'weekCount' => 4],
                12 => ['weeks' => 'W49-W53', 'weekCount' => 5],
            ],
            2027 => [
                1 => ['weeks' => 'W1-W5', 'weekCount' => 5],
                2 => ['weeks' => 'W6-W9', 'weekCount' => 4],
                3 => ['weeks' => 'W10-W13', 'weekCount' => 4],
                4 => ['weeks' => 'W14-W17', 'weekCount' => 4],
                5 => ['weeks' => 'W18-W22', 'weekCount' => 5],
                6 => ['weeks' => 'W23-W26', 'weekCount' => 4],
                7 => ['weeks' => 'W27-W31', 'weekCount' => 5],
                8 => ['weeks' => 'W32-W35', 'weekCount' => 4],
                9 => ['weeks' => 'W36-W39', 'weekCount' => 4],
                10 => ['weeks' => 'W40-W44', 'weekCount' => 5],
                11 => ['weeks' => 'W45-W48', 'weekCount' => 4],
                12 => ['weeks' => 'W49-W52', 'weekCount' => 4],
            ],
        ];

        // Month names are consistent across years
        $months = [
            1 => ['name' => 'January'],
            2 => ['name' => 'February'],
            3 => ['name' => 'March'],
            4 => ['name' => 'April'],
            5 => ['name' => 'May'],
            6 => ['name' => 'June'],
            7 => ['name' => 'July'],
            8 => ['name' => 'August'],
            9 => ['name' => 'September'],
            10 => ['name' => 'October'],
            11 => ['name' => 'November'],
            12 => ['name' => 'December'],
        ];

        // Use default data for years not explicitly defined
        $defaultWeekRanges = $yearWeekRanges[2025];

        // Determine which week ranges to use based on selected year
        $weekRanges = $yearWeekRanges[$this->selectedYear] ?? $defaultWeekRanges;

        // Weekly target for demos
        $weeklyTarget = 70;

        // Get appointment data for New Demo and Webinar Demo
        $appointmentData = $this->getAppointmentData();

        // Get sales data from RevenueTarget table instead of leads
        $salesData = $this->getRevenueActualSales();

        $monthlyStats = [];

        foreach ($months as $monthNumber => $monthInfo) {
            // Combine month name with week data for the selected year
            $weekInfo = $weekRanges[$monthNumber];
            $monthData = array_merge($monthInfo, $weekInfo);

            $rawNewDemoCount = $appointmentData['new_demo'][$monthNumber] ?? 0;
            $rawWebinarDemoCount = $appointmentData['webinar_demo'][$monthNumber] ?? 0;

            // Calculate the total target for the month (70 per week * number of weeks)
            $monthlyDemoTarget = $weeklyTarget * $monthData['weekCount'];

            // Calculate percentage achieved (actual/target * 100)
            $newDemoPercentage = ($monthlyDemoTarget > 0)
                ? round(($rawNewDemoCount / $monthlyDemoTarget) * 100)
                : 0;

            $webinarDemoPercentage = ($monthlyDemoTarget > 0)
                ? round(($rawWebinarDemoCount / $monthlyDemoTarget) * 100)
                : 0;

            // Get actual sales from the salesData
            $actualSales = $salesData[$monthNumber] ?? 0;
            $salesTarget = $this->salesTargets[$monthNumber] ?? 0;

            // Calculate difference between actual sales and target
            $difference = $actualSales - $salesTarget;

            $monthlyStats[$monthNumber] = [
                'month_name' => $monthData['name'],
                'weeks' => $monthData['weeks'],
                'week_count' => $monthData['weekCount'],
                'new_demo_count' => $rawNewDemoCount,
                'new_demo_percentage' => $newDemoPercentage,
                'webinar_demo_count' => $rawWebinarDemoCount,
                'webinar_demo_percentage' => $webinarDemoPercentage,
                'monthly_demo_target' => $monthlyDemoTarget,
                'actual_sales' => $actualSales,
                'sales_target' => $salesTarget,
                'raw_sales_target' => $salesTarget,
                'raw_difference' => $difference,
            ];
        }

        return $monthlyStats;
    }

    protected function getRevenueActualSales(): array
    {
        // Initialize result array with zeros for each month
        $salesData = array_fill(1, 12, 0);

        // Get all revenue targets for the selected year
        $monthlyRevenues = RevenueTarget::where('year', $this->selectedYear)
            ->select(
                'month',
                DB::raw('SUM(target_amount) as total_amount')
            )
            ->groupBy('month')
            ->get();

        // Process revenue data
        foreach ($monthlyRevenues as $revenue) {
            $month = (int) $revenue->month;
            $salesData[$month] = (float) $revenue->total_amount;
        }

        return $salesData;
    }

    protected function getAppointmentData(): array
    {
        $startOfYear = Carbon::createFromDate($this->selectedYear, 1, 1)->startOfYear();
        $endOfYear = Carbon::createFromDate($this->selectedYear, 12, 31)->endOfYear();

        $query = Appointment::whereBetween('date', [$startOfYear, $endOfYear])
            ->where('status', '!=', 'Cancelled');

        $appointments = $query->select(
            'type',
            DB::raw('MONTH(date) as month'),
            DB::raw('COUNT(*) as count')
        )
        ->groupBy('type', 'month')
        ->get();

        // Initialize result arrays
        $newDemoData = array_fill(1, 12, 0);
        $webinarDemoData = array_fill(1, 12, 0);

        // Process appointment data
        foreach ($appointments as $appointment) {
            $month = (int) $appointment->month;
            $type = strtoupper($appointment->type);

            if ($type === 'NEW DEMO') {
                $newDemoData[$month] += $appointment->count;
            } elseif ($type === 'WEBINAR DEMO') {
                $webinarDemoData[$month] += $appointment->count;
            }
        }

        return [
            'new_demo' => $newDemoData,
            'webinar_demo' => $webinarDemoData,
        ];
    }

    protected function getSalesData(): array
    {
        $startOfYear = Carbon::createFromDate($this->selectedYear, 1, 1)->startOfYear();
        $endOfYear = Carbon::createFromDate($this->selectedYear, 12, 31)->endOfYear();

        $query = Lead::whereBetween('created_at', [$startOfYear, $endOfYear])
            ->where('lead_status', 'Closed');

        $sales = $query->select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as count')
        )
        ->groupBy('month')
        ->get();

        // Initialize result array
        $salesData = array_fill(1, 12, 0);

        // Process sales data
        foreach ($sales as $sale) {
            $month = (int) $sale->month;
            $salesData[$month] = $sale->count;
        }

        return $salesData;
    }
}
