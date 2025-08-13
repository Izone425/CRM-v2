<?php
namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\SoftwareHandover;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

class SoftwareHandoverAnalysisV2 extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Sales Admin Analysis V2';
    protected static ?string $title = '';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.software-handover-analysis-v2';

    public $selectedYear;
    public $selectedTargetYear;

    public $showSlideOver = false;
    public $slideOverTitle = 'Software Handovers';
    public $handoversList = [];

    public function mount()
    {
        $this->selectedYear = now()->year; // Default to current year
        $this->selectedTargetYear = now()->year;
    }

    #[On('getDataForYear')]
    public function updateSelectedYear($year)
    {
        $this->selectedYear = $year;
    }

    public function updateTargetYear()
    {
        // This will automatically refresh the chart with the new year
    }

    public function getHandoversByMonthAndStatus($year = null)
{
    // Use the selected year or the passed year parameter
    $selectedYear = $year ?? $this->selectedYear ?? Carbon::now()->year;

    $monthlyData = [];

    try {
        for ($month = 1; $month <= 12; $month++) {
            // Get total handovers created in this month (regardless of status)
            $totalCount = SoftwareHandover::whereYear('created_at', $selectedYear)
                ->whereMonth('created_at', $month)
                ->count();

            // Get only closed projects with go_live_date in this month
            $closedProject = SoftwareHandover::whereYear('go_live_date', $selectedYear)
                ->whereMonth('go_live_date', $month)
                ->where('status_handover', 'Closed')
                ->count();

            // Get company size data for all handovers created in this month
            $small = SoftwareHandover::whereYear('created_at', $selectedYear)
                ->whereMonth('created_at', $month)
                ->where('headcount', '>=', 1)
                ->where('headcount', '<=', 24)
                ->count();

            $medium = SoftwareHandover::whereYear('created_at', $selectedYear)
                ->whereMonth('created_at', $month)
                ->where('headcount', '>=', 25)
                ->where('headcount', '<=', 99)
                ->count();

            $large = SoftwareHandover::whereYear('created_at', $selectedYear)
                ->whereMonth('created_at', $month)
                ->where('headcount', '>=', 100)
                ->where('headcount', '<=', 500)
                ->count();

            $enterprise = SoftwareHandover::whereYear('created_at', $selectedYear)
                ->whereMonth('created_at', $month)
                ->where('headcount', '>=', 501)
                ->count();

            // Get company size data for closed handovers with go_live_date in this month
            $closedSmall = SoftwareHandover::whereYear('go_live_date', $selectedYear)
                ->whereMonth('go_live_date', $month)
                ->where('status_handover', 'Closed')
                ->where('headcount', '>=', 1)
                ->where('headcount', '<=', 24)
                ->count();

            $closedMedium = SoftwareHandover::whereYear('go_live_date', $selectedYear)
                ->whereMonth('go_live_date', $month)
                ->where('status_handover', 'Closed')
                ->where('headcount', '>=', 25)
                ->where('headcount', '<=', 99)
                ->count();

            $closedLarge = SoftwareHandover::whereYear('go_live_date', $selectedYear)
                ->whereMonth('go_live_date', $month)
                ->where('status_handover', 'Closed')
                ->where('headcount', '>=', 100)
                ->where('headcount', '<=', 500)
                ->count();

            $closedEnterprise = SoftwareHandover::whereYear('go_live_date', $selectedYear)
                ->whereMonth('go_live_date', $month)
                ->where('status_handover', 'Closed')
                ->where('headcount', '>=', 501)
                ->count();

            $monthlyData[] = [
                'month' => Carbon::create()->month($month)->format('M'),
                'closed' => $closedProject,
                'total' => $totalCount,
                // Add size breakdowns
                'small' => $small,
                'medium' => $medium,
                'large' => $large,
                'enterprise' => $enterprise,
                // Add closed size breakdowns
                'closed_small' => $closedSmall,
                'closed_medium' => $closedMedium,
                'closed_large' => $closedLarge,
                'closed_enterprise' => $closedEnterprise,
            ];
        }
    } catch (Exception $e) {
        // Log any errors
        Log::error('Error fetching monthly handovers: ' . $e->getMessage());
    }

    return $monthlyData;
}

    public function getHandoversBySalesPerson()
    {
        return SoftwareHandover::select('salesperson', DB::raw('count(*) as total'))
            ->whereNotNull('salesperson')
            ->where('salesperson', '!=', '')
            ->groupBy('salesperson')
            ->orderByDesc('total')
            ->limit(4)
            ->get();
    }

    public function getHandoversBySalesPersonRank1()
    {
        // Rank 1 salespeople
        $rank1Salespeople = ['Joshua Ho', 'Vince Leong', 'Wan Amirul Muim'];

        // Rank 2 salespeople (to be excluded from Others count)
        $rank2Salespeople = ['Yasmin', 'Muhammad Khoirul Bariah', 'Abdul Aziz', 'Farhanah Jamil'];

        // All salespeople to exclude from "Others" count
        $excludeSalespeople = array_merge($rank1Salespeople, $rank2Salespeople);

        // Get the count for the specified Rank 1 salespeople
        $rank1Data = SoftwareHandover::select('salesperson', DB::raw('count(*) as total'))
            ->whereIn('salesperson', $rank1Salespeople)
            ->groupBy('salesperson')
            ->orderByDesc('total')
            ->get();

        // Get the count for all other salespeople excluding both Rank 1 and Rank 2
        $othersCount = SoftwareHandover::where(function($query) use ($excludeSalespeople) {
            // Include records where salesperson is not in excluded list
            $query->whereNotIn('salesperson', $excludeSalespeople)
                // Or include records where salesperson is null or empty
                ->orWhereNull('salesperson')
                ->orWhere('salesperson', '');
        })->count();

        // Add "Others" as a new entry in the collection with a sequence field
        $rank1Data->push((object)[
            'salesperson' => 'Others',
            'total' => $othersCount,
            'is_others' => true
        ]);

        // Sort the entire collection including "Others" by total in descending order
        $sortedData = $rank1Data->sortByDesc('total');

        // Convert back to a collection to maintain the same return type
        return collect($sortedData->values()->all());
    }

    public function getHandoversBySalesPersonRank2()
    {
        $salespeople = ['Yasmin', 'Muhammad Khoirul Bariah', 'Abdul Aziz', 'Farhanah Jamil'];

        return SoftwareHandover::select('salesperson', DB::raw('count(*) as total'))
            ->whereIn('salesperson', $salespeople)
            ->groupBy('salesperson')
            ->orderByDesc('total')
            ->get();
    }

    public function getHandoversByStatus()
    {
        return [
            'open' => SoftwareHandover::where('status_handover', 'OPEN')->count(),
            'delay' => SoftwareHandover::where('status_handover', 'DELAY')->count(),
            'inactive' => SoftwareHandover::where('status_handover', 'INACTIVE')->count(),
            'closed' => SoftwareHandover::where('status_handover', 'CLOSED')->count(),
        ];
    }

    public function getHandoversByCompanySize()
    {
        $sizes = [
            'Small' => SoftwareHandover::where('headcount', '>=', 1)
                ->where('headcount', '<=', 24)
                ->count(),

            'Medium' => SoftwareHandover::where('headcount', '>=', 25)
                ->where('headcount', '<=', 99)
                ->count(),

            'Large' => SoftwareHandover::where('headcount', '>=', 100)
                ->where('headcount', '<=', 500)
                ->count(),

            'Enterprise' => SoftwareHandover::where('headcount', '>=', 501)
                ->count(),
        ];

        return $sizes;
    }

    public function getHandoversByModule()
    {
        // Count each module where its value is 1
        return [
            'ta' => SoftwareHandover::where('ta', 1)->count(),
            'tl' => SoftwareHandover::where('tl', 1)->count(),
            'tc' => SoftwareHandover::where('tc', 1)->count(),
            'tp' => SoftwareHandover::where('tp', 1)->count(),
        ];
    }

    public function getModulesByQuarter()
    {
        // Starting from Q3 2024 and generating 12 quarters
        $quarters = [];
        $startYear = 2024;
        $startQuarter = 3;

        for ($i = 0; $i < 6; $i++) {
            $year = $startYear + floor(($startQuarter + $i - 1) / 4);
            $quarter = (($startQuarter + $i - 1) % 4) + 1;

            // Generate quarterly data for each module
            // These should be fetched from your database in a real implementation
            $taCount = $this->getModuleCountForQuarter('ta', $year, $quarter);
            $tlCount = $this->getModuleCountForQuarter('tl', $year, $quarter);
            $tcCount = $this->getModuleCountForQuarter('tc', $year, $quarter);
            $tpCount = $this->getModuleCountForQuarter('tp', $year, $quarter);

            $quarters[] = [
                'quarter' => "Q$quarter $year",
                'ta' => $taCount,
                'tl' => $tlCount,
                'tc' => $tcCount,
                'tp' => $tpCount
            ];
        }

        return $quarters;
    }

    private function getModuleCountForQuarter($moduleCode, $year, $quarter)
    {
        // Define which months are in each quarter
        $quarterMonths = [
            1 => [1, 2, 3],
            2 => [4, 5, 6],
            3 => [7, 8, 9],
            4 => [10, 11, 12]
        ];

        $months = $quarterMonths[$quarter];

        // Query the database to count handovers where the specified module is true/1
        // in the specified quarter
        return SoftwareHandover::where($moduleCode, 1)  // Using 1 instead of true for database compatibility
            ->whereYear('created_at', $year)
            ->whereIn(DB::raw('MONTH(created_at)'), $months)
            ->count();

        // If no data available for testing, uncomment this line:
        // return rand(5, 20); // Random data for visualization testing
    }

    public function openMonthlyHandoverDetailsSlideOver($month, $type = 'new')
    {
        // Convert short month name to month number
        $monthMap = [
            'Jan' => 1, 'Feb' => 2, 'Mar' => 3, 'Apr' => 4, 'May' => 5, 'Jun' => 6,
            'Jul' => 7, 'Aug' => 8, 'Sep' => 9, 'Oct' => 10, 'Nov' => 11, 'Dec' => 12
        ];

        $monthNumber = $monthMap[$month] ?? null;

        if (!$monthNumber) {
            $this->handoversList = collect();
            $this->slideOverTitle = 'Invalid Month Selected';
            $this->showSlideOver = true;
            return;
        }

        $year = $this->selectedTargetYear ?? now()->year;

        // Query for handovers based on month, year and type (new or closed)
        if ($type === 'closed') {
            // For closed projects, use go_live_date and status_handover = Closed
            $query = \App\Models\SoftwareHandover::whereYear('go_live_date', $year)
                ->whereMonth('go_live_date', $monthNumber)
                ->where('status_handover', 'Closed');

            $this->slideOverTitle = "Closed Projects - {$month} {$year}";
        } else {
            // For new projects, show all projects from this month regardless of status
            $query = \App\Models\SoftwareHandover::whereYear('created_at', $year)
                ->whereMonth('created_at', $monthNumber);

            $this->slideOverTitle = "New Projects - {$month} {$year}";
        }

        $handovers = $query->get();

        // Group handovers by company size
        $groupedHandovers = $handovers->groupBy(function ($handover) {
            if ($handover->headcount >= 1 && $handover->headcount <= 24) {
                return 'Small (1-24)';
            } elseif ($handover->headcount >= 25 && $handover->headcount <= 99) {
                return 'Medium (25-99)';
            } elseif ($handover->headcount >= 100 && $handover->headcount <= 500) {
                return 'Large (100-500)';
            } elseif ($handover->headcount >= 501) {
                return 'Enterprise (501+)';
            } else {
                return 'Unknown';
            }
        });

        // Sort the groups in a logical order
        $sortOrder = ['Small (1-24)', 'Medium (25-99)', 'Large (100-500)', 'Enterprise (501+)', 'Unknown'];
        $sortedGroups = collect();

        foreach ($sortOrder as $size) {
            if ($groupedHandovers->has($size)) {
                $sortedGroups[$size] = $groupedHandovers[$size];
            }
        }

        $this->handoversList = $sortedGroups;
        $this->showSlideOver = true;
    }

    public function openSalespersonHandoversSlideOver($salesperson)
    {
        // Query for handovers with the selected salesperson
        if ($salesperson === 'Others') {
            // For "Others", exclude both Rank 1 and Rank 2 salespeople
            $rank1Salespeople = ['Joshua Ho', 'Vince Leong', 'Wan Amirul Muim'];
            $rank2Salespeople = ['Yasmin', 'Muhammad Khoirul Bariah', 'Abdul Aziz', 'Farhanah Jamil'];
            $excludeSalespeople = array_merge($rank1Salespeople, $rank2Salespeople);

            $query = \App\Models\SoftwareHandover::where(function($q) use ($excludeSalespeople) {
                $q->whereNotIn('salesperson', $excludeSalespeople)
                ->orWhereNull('salesperson')
                ->orWhere('salesperson', '');
            });

            $this->slideOverTitle = "Projects by Other Salespersons";
        } else {
            // For named salespersons, get their specific handovers
            $query = \App\Models\SoftwareHandover::where('salesperson', $salesperson);
            $this->slideOverTitle = "Projects by {$salesperson}";
        }

        $handovers = $query->get();

        // Group handovers by company size, similar to the monthly slide-over
        $groupedHandovers = $handovers->groupBy(function ($handover) {
            if ($handover->headcount >= 1 && $handover->headcount <= 24) {
                return 'Small (1-24)';
            } elseif ($handover->headcount >= 25 && $handover->headcount <= 99) {
                return 'Medium (25-99)';
            } elseif ($handover->headcount >= 100 && $handover->headcount <= 500) {
                return 'Large (100-500)';
            } elseif ($handover->headcount >= 501) {
                return 'Enterprise (501 and Above)';
            } else {
                return 'Unknown';
            }
        });

        // Sort the groups in a logical order
        $sortOrder = ['Small (1-24)', 'Medium (25-99)', 'Large (100-500)', 'Enterprise (501 and Above)', 'Unknown'];
        $sortedGroups = collect();

        foreach ($sortOrder as $size) {
            if ($groupedHandovers->has($size)) {
                $sortedGroups[$size] = $groupedHandovers[$size];
            }
        }

        $this->handoversList = $sortedGroups;
        $this->showSlideOver = true;
    }

    public function getYesterdayHandoversByModule(): array
    {
        $yesterday = now()->subDay()->format('Y-m-d');

        // Query for yesterday's module data
        $data = SoftwareHandover::where('created_at', 'like', "{$yesterday}%")
            ->get();

        // Initialize counts
        $counts = [
            'ta' => 0,
            'tl' => 0,
            'tc' => 0,
            'tp' => 0
        ];

        // Count each module
        foreach ($data as $handover) {
            if ($handover->ta) $counts['ta']++;
            if ($handover->tl) $counts['tl']++;
            if ($handover->tc) $counts['tc']++;
            if ($handover->tp) $counts['tp']++;
        }

        return $counts;
    }

    public function getTodayHandoversByModule(): array
    {
        $today = now()->format('Y-m-d');

        // Query for today's module data
        $data = SoftwareHandover::where('created_at', 'like', "{$today}%")
            ->get();

        // Initialize counts
        $counts = [
            'ta' => 0,
            'tl' => 0,
            'tc' => 0,
            'tp' => 0
        ];

        // Count each module
        foreach ($data as $handover) {
            if ($handover->ta) $counts['ta']++;
            if ($handover->tl) $counts['tl']++;
            if ($handover->tc) $counts['tc']++;
            if ($handover->tp) $counts['tp']++;
        }

        return $counts;
    }

    public function getAllSalespersonHandovers(): int
    {
        $baseQuery = SoftwareHandover::query();

        // Get the total count of all handovers
        return $baseQuery->count();
    }
}
