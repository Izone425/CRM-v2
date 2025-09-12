<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\Invoice;
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

class RevenueAnalysis extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Revenue Analysis';
    protected static ?string $title = '';
    protected static ?int $navigationSort = 16;
    protected static string $view = 'filament.pages.revenue-analysis';

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
        // Define month names
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

        // Monthly target for demos instead of weekly
        $monthlyDemoTarget = 280; // You can adjust this as needed

        // Get appointment data for New Demo and Webinar Demo
        $appointmentData = $this->getAppointmentData();

        // Get sales data from RevenueTarget table instead of leads
        $salesData = $this->getRevenueActualSales();

        $monthlyStats = [];

        foreach ($months as $monthNumber => $monthInfo) {
            $rawNewDemoCount = $appointmentData['new_demo'][$monthNumber] ?? 0;
            $rawWebinarDemoCount = $appointmentData['webinar_demo'][$monthNumber] ?? 0;

            // Calculate percentage achieved (actual/target * 100)
            $newDemoPercentage = round(($rawNewDemoCount / $monthlyDemoTarget) * 100);
            $webinarDemoPercentage = round(($rawWebinarDemoCount / $monthlyDemoTarget) * 100);

            // Get actual sales from the salesData
            $actualSales = $salesData[$monthNumber] ?? 0;
            $salesTarget = $this->salesTargets[$monthNumber] ?? 0;

            // Calculate difference between actual sales and target
            $difference = $actualSales - $salesTarget;

            $monthlyStats[$monthNumber] = [
                'month_name' => $monthInfo['name'],
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

    // protected function getRevenueActualSales(): array
    // {
    //     $startOfYear = Carbon::createFromDate($this->selectedYear, 1, 1)->startOfYear();
    //     $endOfYear = Carbon::createFromDate($this->selectedYear, 12, 31)->endOfYear();

    //     // Initialize result array with zeros for each month
    //     $salesData = array_fill(1, 12, 0);

    //     // Define excluded salespeople
    //     $excludedSalespeople = ['TTCP', 'WIRSON'];

    //     // Get all invoices for the selected year (excluding credit notes)
    //     $invoices = Invoice::whereBetween('invoice_date', [$startOfYear, $endOfYear])
    //         ->where(function ($query) use ($excludedSalespeople) {
    //             // Include records where salesperson is NULL OR not in the excluded list
    //             $query->whereNull('salesperson')
    //                 ->orWhereNotIn('salesperson', $excludedSalespeople);
    //         })
    //         // Exclude credit notes (assuming invoice_no starts with 'ECN' for credit notes)
    //         ->where('invoice_no', 'NOT LIKE', 'ECN%')
    //         ->select(
    //             DB::raw('MONTH(invoice_date) as month'),
    //             DB::raw('SUM(invoice_amount) as total_amount')
    //         )
    //         ->groupBy('month')
    //         ->get();

    //     // Process invoice data
    //     foreach ($invoices as $invoice) {
    //         $month = (int) $invoice->month;
    //         $salesData[$month] = (float) $invoice->total_amount;
    //     }

    //     // Subtract credit note amounts
    //     $creditNotes = DB::table('credit_notes')
    //         ->whereBetween('credit_note_date', [$startOfYear, $endOfYear])
    //         ->where(function ($query) use ($excludedSalespeople) {
    //             // Filter out excluded salespeople, matching the invoice filter
    //             $query->whereNull('salesperson')
    //                 ->orWhereNotIn('salesperson', $excludedSalespeople);
    //         })
    //         ->select(
    //             DB::raw('MONTH(credit_note_date) as month'),
    //             DB::raw('SUM(amount) as total_amount')
    //         )
    //         ->groupBy('month')
    //         ->get();

    //     // Subtract credit note amounts from the corresponding months
    //     foreach ($creditNotes as $creditNote) {
    //         $month = (int) $creditNote->month;
    //         $salesData[$month] -= (float) $creditNote->total_amount;
    //     }

    //     return $salesData;
    // }

    protected function getRevenueActualSales(): array
    {
        $startOfYear = Carbon::createFromDate($this->selectedYear, 1, 1)->startOfYear();
        $endOfYear = Carbon::createFromDate($this->selectedYear, 12, 31)->endOfYear();

        // Initialize result array with zeros for each month
        $salesData = array_fill(1, 12, 0);

        // Get excluded salespeople from the RevenueTable
        $excludedSalespeople = ['TTCP', 'WIRSON'];

        // Define main salespeople (uppercase for comparison) as in RevenueTable
        $mainSalespeople = ['MUIM', 'YASMIN', 'FARHANAH', 'JOSHUA', 'AZIZ', 'BARI', 'VINCE'];

        // Get all invoices for the selected year - match the RevenueTable query exactly
        $invoiceRevenue = Invoice::whereBetween('invoice_date', [$startOfYear, $endOfYear])
            ->select(
                DB::raw('UPPER(salesperson) as salesperson'),
                DB::raw('MONTH(invoice_date) as month'),
                DB::raw('SUM(invoice_amount) as total_amount')
            )
            ->groupBy(DB::raw('UPPER(salesperson)'), 'month')
            ->get();

        // Get all credit notes for the selected year - match the RevenueTable query exactly
        $creditNoteRevenue = DB::table('credit_notes')
            ->whereBetween('credit_note_date', [$startOfYear, $endOfYear])
            ->select(
                DB::raw('UPPER(salesperson) as salesperson'),
                DB::raw('MONTH(credit_note_date) as month'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->groupBy(DB::raw('UPPER(salesperson)'), 'month')
            ->get();

        // Initialize data structure like in RevenueTable
        $data = [];
        for ($month = 1; $month <= 12; $month++) {
            $data[$month] = [];
            foreach ($mainSalespeople as $person) {
                $data[$month][ucfirst(strtolower($person))] = 0;
            }
            $data[$month]['Others'] = 0;
        }

        // Process invoices exactly as in RevenueTable
        foreach ($invoiceRevenue as $invoice) {
            $month = (int) $invoice->month;
            $salesperson = $invoice->salesperson;
            $amount = (float) $invoice->total_amount;

            // Skip excluded salespeople completely
            if (in_array($salesperson, $excludedSalespeople)) {
                continue;
            }

            // Handle main salespeople for all months
            if (in_array($salesperson, $mainSalespeople)) {
                $originalCase = ucfirst(strtolower($salesperson));
                $data[$month][$originalCase] += $amount;
            }
            // For "Others", only process September onwards from DB
            elseif ($month >= 9) {
                $data[$month]['Others'] += $amount;
            }
        }

        // Process credit notes exactly as in RevenueTable
        foreach ($creditNoteRevenue as $creditNote) {
            $month = (int) $creditNote->month;
            $salesperson = $creditNote->salesperson;
            $amount = (float) $creditNote->total_amount;

            // Skip excluded salespeople completely
            if (in_array($salesperson, $excludedSalespeople)) {
                continue;
            }

            // Handle main salespeople for all months
            if (in_array($salesperson, $mainSalespeople)) {
                $originalCase = ucfirst(strtolower($salesperson));
                $data[$month][$originalCase] -= $amount;
            }
            // For "Others", only process September onwards from DB
            elseif ($month >= 9) {
                $data[$month]['Others'] -= $amount;
            }
        }

        // Hard-coded values for "Others" column (January-August of current year)
        // Use EXACTLY the same values as in RevenueTable
        if ($this->selectedYear == date('Y')) {
            // Set the fixed values for "Others" column for Jan-Aug
            $othersValues = [
                1 => 581675.55,  // January
                2 => 369221.61,  // February
                3 => 432626.93,  // March
                4 => 262396.86,  // April
                5 => 469012.35,  // May
                6 => 412398.51,  // June
                7 => 347908.97,  // July
                8 => 493526.84,  // August
                // September to December will use DB values
            ];

            // Apply the fixed values to the "Others" column for Jan-Aug
            foreach ($othersValues as $month => $value) {
                $data[$month]['Others'] = $value;
            }
        }

        // Now calculate the monthly totals, matching RevenueTable's structure
        for ($month = 1; $month <= 12; $month++) {
            $salesData[$month] = array_sum($data[$month]);
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
