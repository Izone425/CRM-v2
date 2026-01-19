<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class HrProductDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.pages.hr-product-dashboard';
    protected static ?string $navigationLabel = 'Admin Portal Dashboard';
    protected static ?string $title = '';
    protected static ?int $navigationSort = 1;

    // Hide from navigation (accessed via sidebar only)
    protected static bool $shouldRegisterNavigation = false;

    // Tab management
    public $currentDashboard = 'Dashboard';
    public $lastRefreshTime;

    // State Properties
    public $selectedMonth;
    public $selectedYear;
    public $compareWithPrevious = false;

    // Metrics
    public $trialToPaidConversion = 0;
    public $totalActiveResellers = 0;
    public $totalActiveDistributors = 0;
    public $newSignUpsThisMonth = 0;

    // Product Metrics
    public $topProductsData = [
        'labels' => [],
        'values' => [],
        'colors' => []
    ];

    public $customersByProduct = [
        'ta' => ['count' => 0, 'percentage' => 0],
        'leave' => ['count' => 0, 'percentage' => 0],
        'patrol' => ['count' => 0, 'percentage' => 0],
        'fcc' => ['count' => 0, 'percentage' => 0],
    ];

    // Comparison Data
    public $previousMetrics = [];
    public $trends = [];

    public function mount()
    {
        $this->selectedMonth = now()->month;
        $this->selectedYear = now()->year;
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');

        // Set default to Dashboard tab
        $this->currentDashboard = session('hr_current_dashboard', 'Dashboard');

        $this->loadDashboardData();
    }

    /**
     * Toggle between Dashboard and Raw Data views
     */
    public function toggleDashboard($dashboard)
    {
        $validDashboards = ['Dashboard', 'RawData'];

        if (in_array($dashboard, $validDashboards)) {
            $this->currentDashboard = $dashboard;
            session(['hr_current_dashboard' => $dashboard]);

            // Dispatch event to update UI
            $this->dispatch('dashboard-changed', ['dashboard' => $dashboard]);
        }
    }

    /**
     * Refresh all dashboard data and clear cache
     */
    public function refreshTable()
    {
        // Clear all dashboard-related caches
        Cache::forget('hr_dashboard_data_' . auth()->id());
        Cache::forget('hr_raw_data_counts_' . auth()->id());

        // Update timestamp
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');

        // Reload data
        $this->loadDashboardData();

        // Dispatch events to refresh child components
        $this->dispatch('refresh-hr-dashboard');

        // Show notification
        Notification::make()
            ->title('Dashboard refreshed')
            ->success()
            ->send();
    }

    #[On('filterChanged')]
    public function updatedSelectedMonth()
    {
        $this->loadDashboardData();
    }

    #[On('filterChanged')]
    public function updatedSelectedYear()
    {
        $this->loadDashboardData();
    }

    public function loadDashboardData()
    {
        $startDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Calculate all metrics
        $this->calculateTrialToPaidConversion($startDate, $endDate);
        $this->calculateActiveResellers();
        $this->calculateActiveDistributors();
        $this->calculateNewSignUps($startDate, $endDate);
        $this->calculateTopProducts($startDate, $endDate);
        $this->calculateCustomersByProduct();

        // Calculate trends if comparison enabled
        if ($this->compareWithPrevious) {
            $this->calculateTrends($startDate);
        }
    }

    /**
     * Get cached counts for badge display
     */
    public function getCachedCounts()
    {
        return Cache::remember('hr_dashboard_counts_' . auth()->id(), 300, function () {
            return [
                'pending_conversions' => $this->trialToPaidConversion,
                'new_signups' => $this->newSignUpsThisMonth,
                'raw_data_total' => $this->getRawDataCount(),
            ];
        });
    }

    /**
     * Calculate raw data count (example - customize based on your needs)
     */
    private function getRawDataCount()
    {
        try {
            // Example: Count all software handovers pending review or similar
            return DB::table('software_handovers')
                ->whereIn('status', ['new', 'pending'])
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calculate trial to paid conversion count
     * Counts leads converted to completed software handovers in the selected month
     */
    private function calculateTrialToPaidConversion($start, $end)
    {
        $this->trialToPaidConversion = DB::table('leads as l')
            ->join('software_handovers as sh', 'l.id', '=', 'sh.lead_id')
            ->where('sh.status', 'completed')
            ->whereBetween('sh.created_at', [$start, $end])
            ->whereRaw("l.products LIKE '%\"hr\"%'")
            ->count();
    }

    /**
     * Calculate total active resellers
     * Uses reseller_v2 table which has status column
     */
    private function calculateActiveResellers()
    {
        $this->totalActiveResellers = DB::table('reseller_v2')
            ->where('status', 'active')
            ->count();
    }

    /**
     * Calculate total active distributors
     * Currently same as resellers (no separate distributor table)
     */
    private function calculateActiveDistributors()
    {
        $this->totalActiveDistributors = $this->totalActiveResellers;
    }

    /**
     * Calculate new sign-ups this month
     * Counts new license activations based on paid_license_start date
     */
    private function calculateNewSignUps($start, $end)
    {
        $this->newSignUpsThisMonth = DB::table('license_certificates')
            ->whereNotNull('paid_license_start')
            ->whereBetween('paid_license_start', [$start, $end])
            ->count();
    }

    /**
     * Calculate top products by license count
     * Aggregates boolean product flags from software_handovers
     */
    private function calculateTopProducts($start, $end)
    {
        $products = DB::table('software_handovers')
            ->select([
                DB::raw('SUM(CASE WHEN ta = 1 THEN 1 ELSE 0 END) as ta_count'),
                DB::raw('SUM(CASE WHEN tl = 1 THEN 1 ELSE 0 END) as leave_count'),
                DB::raw('SUM(CASE WHEN tc = 1 THEN 1 ELSE 0 END) as claim_count'),
                DB::raw('SUM(CASE WHEN tp = 1 THEN 1 ELSE 0 END) as payroll_count'),
            ])
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->first();

        $this->topProductsData = [
            'labels' => ['TimeTec TA', 'TimeTec Leave', 'TimeTec Claim', 'TimeTec Payroll'],
            'values' => [
                $products->ta_count ?? 0,
                $products->leave_count ?? 0,
                $products->claim_count ?? 0,
                $products->payroll_count ?? 0,
            ],
            'colors' => ['#06B6D4', '#F59E0B', '#3B82F6', '#EF4444']
        ];
    }

    /**
     * Calculate active customers by product
     * Joins leads with software_handovers and aggregates by product modules
     */
    private function calculateCustomersByProduct()
    {
        $data = DB::table('leads as l')
            ->join('software_handovers as sh', 'l.id', '=', 'sh.lead_id')
            ->select([
                DB::raw('SUM(CASE WHEN sh.ta = 1 THEN 1 ELSE 0 END) as ta_count'),
                DB::raw('SUM(CASE WHEN sh.tl = 1 THEN 1 ELSE 0 END) as leave_count'),
                DB::raw('SUM(CASE WHEN sh.tc = 1 THEN 1 ELSE 0 END) as claim_count'),
                DB::raw('SUM(CASE WHEN sh.tp = 1 THEN 1 ELSE 0 END) as payroll_count'),
            ])
            ->where('l.categories', 'Active')
            ->where('sh.status', 'completed')
            ->first();

        $total = ($data->ta_count + $data->leave_count + $data->claim_count + $data->payroll_count) ?: 1;

        $this->customersByProduct = [
            'ta' => [
                'count' => $data->ta_count ?? 0,
                'percentage' => round(($data->ta_count / $total) * 100)
            ],
            'leave' => [
                'count' => $data->leave_count ?? 0,
                'percentage' => round(($data->leave_count / $total) * 100)
            ],
            'patrol' => [
                'count' => $data->claim_count ?? 0,
                'percentage' => round(($data->claim_count / $total) * 100)
            ],
            'fcc' => [
                'count' => $data->payroll_count ?? 0,
                'percentage' => round(($data->payroll_count / $total) * 100)
            ],
        ];
    }

    /**
     * Calculate trends compared to previous month
     */
    private function calculateTrends($currentStart)
    {
        $prevStart = $currentStart->copy()->subMonth()->startOfMonth();
        $prevEnd = $prevStart->copy()->endOfMonth();

        // Calculate previous month conversion
        $prevConversion = DB::table('leads as l')
            ->join('software_handovers as sh', 'l.id', '=', 'sh.lead_id')
            ->where('sh.status', 'completed')
            ->whereBetween('sh.created_at', [$prevStart, $prevEnd])
            ->whereRaw("l.products LIKE '%\"hr\"%'")
            ->count();

        // Calculate previous month sign-ups
        $prevSignups = DB::table('license_certificates')
            ->whereNotNull('paid_license_start')
            ->whereBetween('paid_license_start', [$prevStart, $prevEnd])
            ->count();

        // Store previous metrics
        $this->previousMetrics['conversion'] = $prevConversion;
        $this->previousMetrics['signups'] = $prevSignups;

        // Calculate trends
        $this->trends['conversion'] = $this->calculateTrendPercentage(
            $prevConversion,
            $this->trialToPaidConversion
        );

        $this->trends['signups'] = $this->calculateTrendPercentage(
            $prevSignups,
            $this->newSignUpsThisMonth
        );
    }

    /**
     * Calculate percentage change between two values
     */
    private function calculateTrendPercentage($previous, $current)
    {
        if ($previous == 0) return $current > 0 ? 100 : 0;
        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Export dashboard data to CSV
     */
    public function exportData()
    {
        $filename = "hr-product-dashboard-{$this->selectedYear}-{$this->selectedMonth}.csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['Metric', 'Value']);
            fputcsv($file, ['Trial to Paid Conversion', $this->trialToPaidConversion]);
            fputcsv($file, ['Total Active Resellers', $this->totalActiveResellers]);
            fputcsv($file, ['Total Active Distributors', $this->totalActiveDistributors]);
            fputcsv($file, ['New Sign Ups This Month', $this->newSignUpsThisMonth]);

            fputcsv($file, ['']);
            fputcsv($file, ['Product', 'Active Customers']);
            fputcsv($file, ['TimeTec TA', $this->customersByProduct['ta']['count']]);
            fputcsv($file, ['TimeTec Leave', $this->customersByProduct['leave']['count']]);
            fputcsv($file, ['TimeTec Patrol', $this->customersByProduct['patrol']['count']]);
            fputcsv($file, ['FCC', $this->customersByProduct['fcc']['count']]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
