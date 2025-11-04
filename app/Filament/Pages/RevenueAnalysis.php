<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
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
use Illuminate\Support\Facades\Cache;

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

        // Clear cache when year changes
        Cache::forget("revenue_analysis_sales_{$this->selectedYear}");
        Cache::forget("revenue_analysis_appointments_{$this->selectedYear}");

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
        $monthlyDemoTarget = 280;

        // Get appointment data for New Demo and Webinar Demo with caching
        $appointmentData = $this->getAppointmentData();

        // Get sales data with caching
        $salesData = $this->getRevenueActualSales();

        $monthlyStats = [];

        foreach ($months as $monthNumber => $monthInfo) {
            $rawNewDemoCount = $appointmentData['new_demo'][$monthNumber] ?? 0;
            $rawWebinarDemoCount = $appointmentData['webinar_demo'][$monthNumber] ?? 0;

            // Calculate percentage achieved (actual/target * 100)
            $newDemoPercentage = $monthlyDemoTarget > 0 ? round(($rawNewDemoCount / $monthlyDemoTarget) * 100) : 0;
            $webinarDemoPercentage = $monthlyDemoTarget > 0 ? round(($rawWebinarDemoCount / $monthlyDemoTarget) * 100) : 0;

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

    protected function getRevenueActualSales(): array
    {
        $cacheKey = "revenue_analysis_sales_{$this->selectedYear}";

        // Cache for 5 minutes (adjust as needed)
        return Cache::remember($cacheKey, 300, function () {
            $startOfYear = Carbon::createFromDate($this->selectedYear, 1, 1)->startOfYear();
            $endOfYear = Carbon::createFromDate($this->selectedYear, 12, 31)->endOfYear();

            // Initialize result array with zeros for each month
            $salesData = array_fill(1, 12, 0);

            // Get excluded salespeople
            $excludedSalespeople = ['TTCP', 'WIRSON'];

            // Define main salespeople
            $mainSalespeople = ['MUIM', 'YASMIN', 'FARHANAH', 'JOSHUA', 'AZIZ', 'BARI', 'VINCE'];

            // Initialize data structure
            $data = [];
            for ($month = 1; $month <= 12; $month++) {
                $data[$month] = [];
                foreach ($mainSalespeople as $person) {
                    $data[$month][ucfirst(strtolower($person))] = 0;
                }
                $data[$month]['Others'] = 0;
            }

            // OPTIMIZED: Single query to get all invoice amounts using raw SQL for better performance
            $excludedItemCodes = [
                'SHIPPING', 'BANKCHG',
                'DEPOSIT-MYR', 'F.COMMISSION', 'L.COMMISSION',
                'L.ENTITLEMENT', 'MGT FEES', 'PG.COMMISSION'
            ];

            $placeholders = implode(',', array_fill(0, count($excludedItemCodes), '?'));

            $invoiceAmounts = DB::select("
                SELECT
                    i.doc_key,
                    i.salesperson,
                    MONTH(i.invoice_date) as month,
                    COALESCE(SUM(id.local_sub_total), 0) as total_amount
                FROM invoices i
                LEFT JOIN invoice_details id ON i.doc_key = id.doc_key
                    AND id.item_code NOT IN ($placeholders)
                WHERE i.invoice_date BETWEEN ? AND ?
                GROUP BY i.doc_key, i.salesperson, MONTH(i.invoice_date)
                HAVING total_amount > 0
            ", array_merge($excludedItemCodes, [$startOfYear, $endOfYear]));

            // Process invoice amounts
            foreach ($invoiceAmounts as $row) {
                $month = (int) $row->month;
                $salesperson = strtoupper($row->salesperson);
                $amount = (float) $row->total_amount;

                // Skip excluded salespeople
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

            // Process credit notes in a single query
            $creditNotes = DB::table('credit_notes')
                ->whereBetween('credit_note_date', [$startOfYear, $endOfYear])
                ->select('salesperson', DB::raw('MONTH(credit_note_date) as month'), 'amount')
                ->get();

            foreach ($creditNotes as $creditNote) {
                $month = (int) $creditNote->month;
                $salesperson = strtoupper($creditNote->salesperson);
                $amount = (float) $creditNote->amount;

                // Skip excluded salespeople
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
            if ($this->selectedYear == date('Y')) {
                $othersValues = [
                    1 => 581675.55,  // January
                    2 => 369221.61,  // February
                    3 => 432626.93,  // March
                    4 => 262396.86,  // April
                    5 => 469012.35,  // May
                    6 => 412398.51,  // June
                    7 => 347908.97,  // July
                    8 => 493526.84,  // August
                ];

                foreach ($othersValues as $month => $value) {
                    $data[$month]['Others'] = $value;
                }
            }

            // Calculate monthly totals
            for ($month = 1; $month <= 12; $month++) {
                $salesData[$month] = array_sum($data[$month]);
            }

            return $salesData;
        });
    }

    protected function getAppointmentData(): array
    {
        $cacheKey = "revenue_analysis_appointments_{$this->selectedYear}";

        // Cache for 5 minutes
        return Cache::remember($cacheKey, 300, function () {
            $startOfYear = Carbon::createFromDate($this->selectedYear, 1, 1)->startOfYear();
            $endOfYear = Carbon::createFromDate($this->selectedYear, 12, 31)->endOfYear();

            // OPTIMIZED: Single query with grouping
            $appointments = Appointment::whereBetween('date', [$startOfYear, $endOfYear])
                ->where('status', '!=', 'Cancelled')
                ->select('type', DB::raw('MONTH(date) as month'), DB::raw('COUNT(*) as count'))
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
                    $newDemoData[$month] = $appointment->count;
                } elseif ($type === 'WEBINAR DEMO') {
                    $webinarDemoData[$month] = $appointment->count;
                }
            }

            return [
                'new_demo' => $newDemoData,
                'webinar_demo' => $webinarDemoData,
            ];
        });
    }

    protected function getSalesData(): array
    {
        $startOfYear = Carbon::createFromDate($this->selectedYear, 1, 1)->startOfYear();
        $endOfYear = Carbon::createFromDate($this->selectedYear, 12, 31)->endOfYear();

        // OPTIMIZED: Single query with grouping
        $sales = Lead::whereBetween('created_at', [$startOfYear, $endOfYear])
            ->where('lead_status', 'Closed')
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('COUNT(*) as count'))
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
