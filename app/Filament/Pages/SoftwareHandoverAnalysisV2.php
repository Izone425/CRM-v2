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
                $closedCount = SoftwareHandover::whereYear('created_at', $selectedYear)
                    ->whereMonth('created_at', $month)
                    ->where('status_handover', 'Closed')
                    ->count();

                $openCount = SoftwareHandover::whereYear('created_at', $selectedYear)
                    ->whereMonth('created_at', $month)
                    ->where('status_handover', 'Open')
                    ->count();

                $delayCount = SoftwareHandover::whereYear('created_at', $selectedYear)
                    ->whereMonth('created_at', $month)
                    ->where('status_handover', 'Delay')
                    ->count();

                $inactiveCount = SoftwareHandover::whereYear('created_at', $selectedYear)
                    ->whereMonth('created_at', $month)
                    ->where('status_handover', 'InActive')
                    ->count();

                $ongoingCount = $openCount + $delayCount + $inactiveCount;
                $totalCount = $closedCount + $ongoingCount;

                $monthlyData[] = [
                    'month' => Carbon::create()->month($month)->format('M'),
                    'closed' => $closedCount,
                    'ongoing' => $ongoingCount,
                    'total' => $totalCount
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
}
