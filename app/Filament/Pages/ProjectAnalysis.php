<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\SoftwareHandover;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

class ProjectAnalysis extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static ?string $navigationGroup = 'Analysis';
    protected static string $view = 'filament.pages.project-analysis';
    protected static ?string $navigationLabel = 'Project Analysis';
    protected static ?int $navigationSort = 2;
    protected static ?string $title = '';

    public $selectedMonth = null;
    public $availableMonths = [];
    public $showSlideOver = false;
    public $slideOverTitle = '';
    public $handoverList = [];

    public $selectedYear;
    public $selectedTargetYear;

    public $showSlideOverV2 = false;
    public $slideOverTitleV2 = 'Software Handovers';
    public $handoversList = [];
    protected static ?string $slug = 'software/project-analysis';

    // public static function canAccess(): bool
    // {
    //     $user = auth()->user();

    //     if (!$user || !($user instanceof \App\Models\User)) {
    //         return false;
    //     }

    //     return $user->hasRouteAccess('filament.admin.pages.software-handover-analysis');
    // }

    #[On('yearUpdated')]
    public function refreshCompanySizeData()
    {
        // This will refresh the data for the company size tab
        return [
            'monthlyData' => $this->getMonthlyProjectsByCompanySize()
        ];
    }

    public function mount()
    {
        $this->selectedYear = now()->year; // Default to current year
        $this->selectedTargetYear = now()->year;

        $this->availableMonths = SoftwareHandover::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month')
            ->distinct()
            ->orderBy('month', 'desc')
            ->pluck('month')
            ->toArray();
    }

    public function updatedSelectedMonth()
    {
        $this->dispatch('refresh');
    }

    private function getBaseQuery()
    {
        $query = SoftwareHandover::query()
            ->where('status', '=', 'Completed')
            ->where('implementer', '!=', null);
        return $query;
    }

    // Module counting methods
    public function getModuleCount($module)
    {
        return $this->getBaseQuery()->where($module, 1)->count();
    }

    // Implementer data methods
    public function getImplementerTotal($implementer)
    {
        return $this->getBaseQuery()
            ->where('implementer', $implementer)
            ->count();
    }

    public function getImplementerClosedCount($implementer)
    {
        return $this->getBaseQuery()
            ->where('implementer', $implementer)
            ->where('status_handover', 'CLOSED')
            ->count();
    }

    public function getImplementerOngoingCount($implementer)
    {
        // New Formula: OnGoing = Open + Delay (excluding INACTIVE)
        return $this->getBaseQuery()
            ->where('implementer', $implementer)
            ->whereIn('status_handover', ['OPEN', 'DELAY']) // Removed 'INACTIVE'
            ->count();
    }
    public function getImplementerStatusCount($implementer, $status)
    {
        return $this->getBaseQuery()
            ->where('implementer', $implementer)
            ->where('status_handover', $status)
            ->count();
    }

    public function getImplementerModuleCount($implementer, $module)
    {
        return $this->getBaseQuery()
            ->where('implementer', $implementer)
            ->where($module, 1)
            ->count();
    }

    public function getAllImplementers()
    {
        return SoftwareHandover::select('implementer')
            ->distinct()
            ->pluck('implementer')
            ->toArray();
    }

    public function getActiveImplementers()
    {
        // Updated list of active implementers
        $activeImplementers = [
            'Mohd Amirul Ashraf',
            'John Low',
            'Zulhilmie',
            'Muhamad Izzul Aiman',
            'Ahmad Syamim',
            'Siti Shahilah',
            'Nur Alia',
            'Ameerul Asyraf',
        ];

        $implementers = [];

        foreach ($activeImplementers as $implementer) {
            $total = $this->getImplementerTotal($implementer);
            $closed = $this->getImplementerClosedCount($implementer);
            $ongoing = $this->getImplementerOngoingCount($implementer);
            $open = $this->getImplementerStatusCount($implementer, 'OPEN');
            $delay = $this->getImplementerStatusCount($implementer, 'DELAY');
            $inactive = $this->getImplementerStatusCount($implementer, 'INACTIVE');

            $implementers[] = [
                'name' => $implementer,
                'isActive' => true,
                'total' => $total,
                'closed' => $closed,
                'ongoing' => $ongoing,
                'open' => $open,
                'delay' => $delay,
                'inactive' => $inactive,
                'completionRate' => $total > 0 ? round(($closed / $total) * 100, 1) : 0
            ];
        }

        return $implementers;
    }

    public function getImplementerDisplayNames()
    {
        return [
            'BARI' => 'Muhammad Khoirul Bariah',
            'ADZZIM' => 'Adzzim Bin Kassim',
            'AZRUL' => 'Azrul Nizam',
            'HANIF' => 'Hanif',
            'Ummu Najwa Fajrina' => 'Ummu Najwa Fajrina',
            'Noor Syazana' => 'Noor Syazana',
        ];
    }

    public function getInactiveImplementers()
    {
        // Updated list of inactive implementers with display names
        $inactiveImplementers = [
            'HANIF' => 'Hanif',
            'Nur Fazuliana' => 'Nur Fazuliana',
            'BARI' => 'Muhammad Khoirul Bariah',
            'Ummu Najwa Fajrina' => 'Ummu Najwa Fajrina',
            'Noor Syazana' => 'Noor Syazana',
            'Ahmad Syazwan' => 'Ahmad Syazwan',
            'ADZZIM' => 'Adzzim Bin Kassim',
            'AZRUL' => 'Azrul Nizam',
            'Nurul Shaqinur Ain' => 'Nurul Shaqinur Ain',

        ];

        $implementers = [];

        foreach ($inactiveImplementers as $dbName => $displayName) {
            $total = $this->getImplementerTotal($dbName);
            $closed = $this->getImplementerClosedCount($dbName);
            $ongoing = $this->getImplementerOngoingCount($dbName);
            $open = $this->getImplementerStatusCount($dbName, 'OPEN');
            $delay = $this->getImplementerStatusCount($dbName, 'DELAY');
            $inactive = $this->getImplementerStatusCount($dbName, 'INACTIVE');

            $implementers[] = [
                'name' => $displayName, // Use display name in the UI
                'dbName' => $dbName, // Keep the original name for database queries
                'isActive' => false,
                'total' => $total,
                'closed' => $closed,
                'ongoing' => $ongoing,
                'open' => $open,
                'delay' => $delay,
                'inactive' => $inactive,
                'completionRate' => $total > 0 ? round(($closed / $total) * 100, 1) : 0
            ];
        }

        return $implementers;
    }

    public function getStatusCounts()
    {
        // Create fresh queries for each count to avoid chain issues
        $total = $this->getBaseQuery()->count();

        // Use DB::raw for case-insensitive comparison
        $closed = $this->getBaseQuery()
            ->whereRaw("TRIM(LOWER(status_handover)) = ?", ['closed'])
            ->count();

        $open = $this->getBaseQuery()
            ->whereRaw("TRIM(LOWER(status_handover)) = ?", ['open'])
            ->count();

        $delay = $this->getBaseQuery()
            ->whereRaw("TRIM(LOWER(status_handover)) = ?", ['delay'])
            ->count();

        $inactive = $this->getBaseQuery()
            ->whereRaw("TRIM(LOWER(status_handover)) = ?", ['inactive'])
            ->count();

        // New Formula: OnGoing = Open + Delay
        $ongoing = $open + $delay;

        // Verify: Total should equal Closed + InActive + OnGoing
        $calculatedTotal = $closed + $inactive + $ongoing;

        // Log if there's a discrepancy (optional for debugging)
        if ($total !== $calculatedTotal) {
            Log::info("Status count discrepancy - DB Total: {$total}, Calculated Total: {$calculatedTotal}");
        }

        return [
            'total' => $total,
            'closed' => $closed,
            'ongoing' => $ongoing,  // Now calculated as Open + Delay only
            'open' => $open,
            'delay' => $delay,
            'inactive' => $inactive
        ];
    }

    // public function getTier1Implementers()
    // {
    //     return ['Nurul Shaqinur Ain', 'Ahmad Syamim', 'Ahmad Syazwan', 'Siti Shahilah'];
    // }

    // public function getTier2Implementers()
    // {
    //     return ['Muhamad Izzul Aiman', 'Zulhilmie'];
    // }

    // public function getTier3Implementers()
    // {
    //     return ['Mohd Amirul Ashraf', 'John Low', 'Nur Alia', 'Nur Fazuliana'];
    // }

    // public function getInactiveImplementersList()
    // {
    //     return ['BARI', 'ADZZIM', 'AZRUL', 'Ummu Najwa Fajrina', 'Noor Syazana', 'HANIF'];
    // }

    public function getAllActiveImplementers()
    {
        return [
            'Mohd Amirul Ashraf',
            'John Low',
            'Zulhilmie',
            'Muhamad Izzul Aiman',
            'Ahmad Syamim',
            'Siti Shahilah',
            'Nur Alia',
            'Ameerul Asyraf',
        ];
    }

    public function getAllInactiveImplementers()
    {
        return [
            'HANIF' => 'Hanif',
            'Nur Fazuliana' => 'Nur Fazuliana',
            'BARI' => 'Muhammad Khoirul Bariah',
            'Ummu Najwa Fajrina' => 'Ummu Najwa Fajrina',
            'Noor Syazana' => 'Noor Syazana',
            'Ahmad Syazwan' => 'Ahmad Syazwan',
            'ADZZIM' => 'Adzzim Bin Kassim',
            'AZRUL' => 'Azrul Nizam',
            'Nurul Shaqinur Ain' => 'Nurul Shaqinur Ain',
        ];
    }

    private function groupHandoversByCompanySize($handovers)
    {
        // Group handovers by headcount ranges
        $groupedHandovers = $handovers->groupBy(function ($handover) {
            $headcount = is_numeric($handover->headcount) ? (int)$handover->headcount : null;

            if ($headcount === null) {
                return 'Unknown';
            } elseif ($headcount >= 1 && $headcount <= 24) {
                return 'Small';
            } elseif ($headcount >= 25 && $headcount <= 99) {
                return 'Medium';
            } elseif ($headcount >= 100 && $headcount <= 500) {
                return 'Large';
            } elseif ($headcount > 500) {
                return 'Enterprise';
            } else {
                return 'Unknown';
            }
        });

        // Sort groups in a logical order
        $sortOrder = ['Small', 'Medium', 'Large', 'Enterprise', 'Unknown'];
        $sortedGroups = collect();

        foreach ($sortOrder as $size) {
            if ($groupedHandovers->has($size)) {
                $sortedGroups[$size] = $groupedHandovers[$size];
            }
        }

        return $sortedGroups;
    }

    public function openAllHandoversSlideOver()
    {
        // Get handovers with company size
        $handovers = $this->getBaseQuery()
            ->select('id', 'lead_id', 'company_name', 'status_handover', 'headcount')
            ->get();

        // Group handovers by company size
        $this->handoverList = $this->groupHandoversByCompanySize($handovers);

        // Updated title format
        $this->slideOverTitle = "Project: All Status";
        $this->showSlideOver = true;
    }

    // For "CLOSED" status handovers
    public function openClosedHandoversSlideOver($implementer = null)
    {
        $query = $this->getBaseQuery()->where('status_handover', 'CLOSED');

        if ($implementer) {
            $query->where('implementer', $implementer);
        }

        // Get handovers with company size
        $handovers = $query->select('id', 'lead_id', 'company_name', 'status_handover', 'headcount')->get();

        // Group handovers by company size
        $this->handoverList = $this->groupHandoversByCompanySize($handovers);

        // Updated title format
        $title = $implementer ?
            "{$implementer} Project Status: Closed" :
            "Project Status: Closed";

        $this->slideOverTitle = $title;
        $this->showSlideOver = true;
    }

    // For "OPEN" status handovers
    public function openOpenHandoversSlideOver($implementer = null)
    {
        $query = SoftwareHandover::query()->where('status_handover', 'OPEN');

        if ($implementer) {
            $query->where('implementer', $implementer);
        }

        // Get handovers with company size
        $handovers = $query->select('id', 'lead_id', 'company_name', 'status_handover', 'headcount')->get();

        // Group handovers by company size
        $this->handoverList = $this->groupHandoversByCompanySize($handovers);

        // Updated title format
        $title = $implementer ?
            "{$implementer} Project Status: Open" :
            "Project Status: Open";

        $this->slideOverTitle = $title;
        $this->showSlideOver = true;
    }

    // For "DELAY" status handovers
    public function openDelayHandoversSlideOver($implementer = null)
    {
        $query = SoftwareHandover::query()->where('status_handover', 'DELAY');

        if ($implementer) {
            $query->where('implementer', $implementer);
        }

        // Get handovers with company size
        $handovers = $query->select('id', 'lead_id', 'company_name', 'status_handover', 'headcount')->get();

        // Group handovers by company size
        $this->handoverList = $this->groupHandoversByCompanySize($handovers);

        // Updated title format
        $title = $implementer ?
            "{$implementer} Project Status: Delay" :
            "Project Status: Delay";

        $this->slideOverTitle = $title;
        $this->showSlideOver = true;
    }

    // For "INACTIVE" status handovers
    public function openInactiveHandoversSlideOver($implementer = null)
    {
        $query = SoftwareHandover::query()->where('status_handover', 'INACTIVE');

        if ($implementer) {
            $query->where('implementer', $implementer);
        }

        // Get handovers with company size
        $handovers = $query->select('id', 'lead_id', 'company_name', 'status_handover', 'headcount')->get();

        // Group handovers by company size
        $this->handoverList = $this->groupHandoversByCompanySize($handovers);

        // Updated title format
        $title = $implementer ?
            "{$implementer} Project Status: InActive" :
            "Project Status: InActive";

        $this->slideOverTitle = $title;
        $this->showSlideOver = true;
    }

    // For "ONGOING" status handovers (combines OPEN, DELAY, INACTIVE)
    public function openOngoingHandoversSlideOver($implementer = null)
    {
        // New Formula: OnGoing = Open + Delay (excluding INACTIVE)
        $query = SoftwareHandover::query()->whereIn('status_handover', ['OPEN', 'DELAY']);

        if ($implementer) {
            $query->where('implementer', $implementer);
        }

        // Get handovers with company size
        $handovers = $query->select('id', 'lead_id', 'company_name', 'status_handover', 'headcount')->get();

        // Group handovers by company size
        $this->handoverList = $this->groupHandoversByCompanySize($handovers);

        // Updated title format
        $title = $implementer ?
            "{$implementer} Project Status: Ongoing (Open + Delay)" :
            "Project Status: Ongoing (Open + Delay)";

        $this->slideOverTitle = $title;
        $this->showSlideOver = true;
    }

    // General function to use as a router to specific status functions
    public function openStatusHandoversSlideOver($status, $implementer = null)
    {
        switch(strtoupper($status)) {
            case 'CLOSED':
                return $this->openClosedHandoversSlideOver($implementer);
            case 'OPEN':
                return $this->openOpenHandoversSlideOver($implementer);
            case 'DELAY':
                return $this->openDelayHandoversSlideOver($implementer);
            case 'INACTIVE':
                return $this->openInactiveHandoversSlideOver($implementer);
            default:
                // If status doesn't match any specific case, use the general implementation
                $query = SoftwareHandover::query();

                if ($status) {
                    $query->where('status_handover', $status);
                }

                if ($implementer) {
                    $query->where('implementer', $implementer);
                }

                // Get handovers with company size
                $handovers = $query->select('id', 'lead_id', 'company_name', 'status_handover', 'headcount')->get();

                // Group handovers by company size
                $this->handoverList = $this->groupHandoversByCompanySize($handovers);

                // Updated title format
                $statusText = $status ? " - Project Status: {$status}" : " - All Status";
                $title = $implementer ? "{$implementer} Projects{$statusText}" : "Projects{$statusText}";

                $this->slideOverTitle = $title;
                $this->showSlideOver = true;
        }
    }

    // Modified implementer function to use the status-specific functions
    public function openImplementerHandoversSlideOver($implementer, $status = null)
    {
        if ($status) {
            // Use the status-specific functions when available
            return $this->openStatusHandoversSlideOver($status, $implementer);
        }

        // For all statuses of a specific implementer
        $query = SoftwareHandover::query()->where('implementer', $implementer);

        // Get handovers with company size
        $handovers = $query->select('id', 'lead_id', 'company_name', 'status_handover', 'headcount')->get();

        // Group handovers by company size
        $this->handoverList = $this->groupHandoversByCompanySize($handovers);

        // Updated title format
        $this->slideOverTitle = "{$implementer} Projects - All Status";
        $this->showSlideOver = true;
    }

    // V2 Analysis: Analysis by Statistic
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
                // Get total handovers created in this month (with completed status)
                $totalCount = $this->getBaseQuery()
                    ->whereYear('completed_at', $selectedYear)
                    ->whereMonth('completed_at', $month)
                    ->count();

                // Get only closed projects with go_live_date in this month
                $closedProject = $this->getBaseQuery()
                    ->whereYear('go_live_date', $selectedYear)
                    ->whereMonth('go_live_date', $month)
                    ->where('status_handover', 'Closed')
                    ->count();

                // Get company size data for all handovers created in this month
                $small = $this->getBaseQuery()
                    ->whereYear('completed_at', $selectedYear)
                    ->whereMonth('completed_at', $month)
                    ->where('headcount', '>=', 1)
                    ->where('headcount', '<=', 24)
                    ->count();

                // Update all other queries in this method similarly...
                $medium = $this->getBaseQuery()
                    ->whereYear('completed_at', $selectedYear)
                    ->whereMonth('completed_at', $month)
                    ->where('headcount', '>=', 25)
                    ->where('headcount', '<=', 99)
                    ->count();

                $large = $this->getBaseQuery()
                    ->whereYear('completed_at', $selectedYear)
                    ->whereMonth('completed_at', $month)
                    ->where('headcount', '>=', 100)
                    ->where('headcount', '<=', 500)
                    ->count();

                $enterprise = $this->getBaseQuery()
                    ->whereYear('completed_at', $selectedYear)
                    ->whereMonth('completed_at', $month)
                    ->where('headcount', '>=', 501)
                    ->count();

                // Get company size data for closed handovers with go_live_date in this month
                $closedSmall = $this->getBaseQuery()
                    ->whereYear('go_live_date', $selectedYear)
                    ->whereMonth('go_live_date', $month)
                    ->where('status_handover', 'Closed')
                    ->where('headcount', '>=', 1)
                    ->where('headcount', '<=', 24)
                    ->count();

                $closedMedium = $this->getBaseQuery()
                    ->whereYear('go_live_date', $selectedYear)
                    ->whereMonth('go_live_date', $month)
                    ->where('status_handover', 'Closed')
                    ->where('headcount', '>=', 25)
                    ->where('headcount', '<=', 99)
                    ->count();

                $closedLarge = $this->getBaseQuery()
                    ->whereYear('go_live_date', $selectedYear)
                    ->whereMonth('go_live_date', $month)
                    ->where('status_handover', 'Closed')
                    ->where('headcount', '>=', 100)
                    ->where('headcount', '<=', 500)
                    ->count();

                $closedEnterprise = $this->getBaseQuery()
                    ->whereYear('go_live_date', $selectedYear)
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
        return $this->getBaseQuery()
            ->select('salesperson', DB::raw('count(*) as total'))
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
        $rank1Data = $this->getBaseQuery()
            ->select('salesperson', DB::raw('count(*) as total'))
            ->whereIn('salesperson', $rank1Salespeople)
            ->groupBy('salesperson')
            ->orderByDesc('total')
            ->get();

        // Get the count for all other salespeople excluding both Rank 1 and Rank 2
        $othersCount = $this->getBaseQuery()
            ->where(function($query) use ($excludeSalespeople) {
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
            'open' => $this->getBaseQuery()->where('status_handover', 'OPEN')->count(),
            'delay' => $this->getBaseQuery()->where('status_handover', 'DELAY')->count(),
            'inactive' => $this->getBaseQuery()->where('status_handover', 'INACTIVE')->count(),
            'closed' => $this->getBaseQuery()->where('status_handover', 'CLOSED')->count(),
        ];
    }

    public function getHandoversByCompanySize()
    {
        $sizes = [
            'Small' => $this->getBaseQuery()
                ->where('headcount', '>=', 1)
                ->where('headcount', '<=', 24)
                ->count(),

            'Medium' => $this->getBaseQuery()
                ->where('headcount', '>=', 25)
                ->where('headcount', '<=', 99)
                ->count(),

            'Large' => $this->getBaseQuery()
                ->where('headcount', '>=', 100)
                ->where('headcount', '<=', 500)
                ->count(),

            'Enterprise' => $this->getBaseQuery()
                ->where('headcount', '>=', 501)
                ->count(),
        ];

        return $sizes;
    }


    public function getHandoversByModule()
    {
        // Count each module where its value is 1
        return [
            'ta' => $this->getBaseQuery()->where('ta', 1)->count(),
            'tl' => $this->getBaseQuery()->where('tl', 1)->count(),
            'tc' => $this->getBaseQuery()->where('tc', 1)->count(),
            'tp' => $this->getBaseQuery()->where('tp', 1)->count(),
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
            ->whereYear('completed_at', $year)
            ->whereIn(DB::raw('MONTH(completed_at)'), $months)
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
            $query = $this->getBaseQuery()
                ->whereYear('go_live_date', $year)
                ->whereMonth('go_live_date', $monthNumber)
                ->where('status_handover', 'Closed');

            $this->slideOverTitle = "Closed Projects - {$month} {$year}";
        } else {
            // For new projects, show all projects from this month regardless of status
            $query = $this->getBaseQuery()
                ->whereYear('completed_at', $year)
                ->whereMonth('completed_at', $monthNumber);

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

            $query = $this->getBaseQuery()->where(function($q) use ($excludeSalespeople) {
                $q->whereNotIn('salesperson', $excludeSalespeople)
                ->orWhereNull('salesperson')
                ->orWhere('salesperson', '');
            });

            $this->slideOverTitle = "Projects by Other Salespersons";
        } else {
            // For named salespersons, get their specific handovers
            $query = $this->getBaseQuery()->where('salesperson', $salesperson);
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
        $data = $this->getBaseQuery()
            ->where('completed_at', 'like', "{$yesterday}%")
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
        $data = $this->getBaseQuery()
            ->where('completed_at', 'like', "{$today}%")
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
        return $this->getBaseQuery()->count();
    }

    //V3 Analysis: Analysis by Company Size
    public function getMonthlyProjectsByCompanySize()
    {
        $year = $this->selectedYear ?? Carbon::now()->year;
        $monthlyData = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthName = Carbon::create($year, $month, 1)->format('F');

            // Total new projects for this month
            $totalCount = $this->getBaseQuery()
                ->whereYear('completed_at', $year)
                ->whereMonth('completed_at', $month)
                ->count();

            // Small companies (1-24 employees)
            $smallCount = $this->getBaseQuery()
                ->whereYear('completed_at', $year)
                ->whereMonth('completed_at', $month)
                ->where('headcount', '>=', 1)
                ->where('headcount', '<=', 24)
                ->count();

            // Medium companies (25-99 employees)
            $mediumCount = $this->getBaseQuery()
                ->whereYear('completed_at', $year)
                ->whereMonth('completed_at', $month)
                ->where('headcount', '>=', 25)
                ->where('headcount', '<=', 99)
                ->count();

            // Large companies (100-500 employees)
            $largeCount = $this->getBaseQuery()
                ->whereYear('completed_at', $year)
                ->whereMonth('completed_at', $month)
                ->where('headcount', '>=', 100)
                ->where('headcount', '<=', 500)
                ->count();

            // Enterprise companies (above 500 employees)
            $enterpriseCount = $this->getBaseQuery()
                ->whereYear('completed_at', $year)
                ->whereMonth('completed_at', $month)
                ->where('headcount', '>', 500)
                ->count();

            $monthlyData[] = [
                'month' => $monthName,
                'total' => $totalCount,
                'small' => $smallCount,
                'medium' => $mediumCount,
                'large' => $largeCount,
                'enterprise' => $enterpriseCount
            ];
        }

        return $monthlyData;
    }

    public function getYearlyTotalsByCompanySize()
    {
        $monthlyData = $this->getMonthlyProjectsByCompanySize();

        // Initialize totals
        $totals = [
            'total' => 0,
            'small' => 0,
            'medium' => 0,
            'large' => 0,
            'enterprise' => 0
        ];

        // Sum up monthly values
        foreach ($monthlyData as $month) {
            $totals['total'] += $month['total'];
            $totals['small'] += $month['small'];
            $totals['medium'] += $month['medium'];
            $totals['large'] += $month['large'];
            $totals['enterprise'] += $month['enterprise'];
        }

        return $totals;
    }

    public function openCompanySizeMonthlyHandovers($month, $size)
    {
        $year = $this->selectedYear ?? Carbon::now()->year;

        // Convert month name to number
        $monthNumber = Carbon::parse("{$month} 1")->month;

        $query = $this->getBaseQuery()
            ->whereYear('completed_at', $year)
            ->whereMonth('completed_at', $monthNumber);

        // Apply size filter if not 'all'
        if ($size !== 'all') {
            switch ($size) {
                case 'small':
                    $query->where('headcount', '>=', 1)->where('headcount', '<=', 24);
                    $sizeLabel = 'Small (Below 25)';
                    break;
                case 'medium':
                    $query->where('headcount', '>=', 25)->where('headcount', '<=', 99);
                    $sizeLabel = 'Medium (25-99)';
                    break;
                case 'large':
                    $query->where('headcount', '>=', 100)->where('headcount', '<=', 500);
                    $sizeLabel = 'Large (100-500)';
                    break;
                case 'enterprise':
                    $query->where('headcount', '>', 500);
                    $sizeLabel = 'Enterprise (Above 500)';
                    break;
            }
        }

        $handovers = $query->select('id', 'lead_id', 'company_name', 'status_handover', 'headcount')->get();

        // Group handovers by company size
        if ($size === 'all') {
            $this->handoverList = $this->groupHandoversByCompanySize($handovers);
            $this->slideOverTitle = "{$month} {$year} - All Company Sizes";
        } else {
            $this->handoverList = $handovers;
            $this->slideOverTitle = "{$month} {$year} - {$sizeLabel}";
        }

        $this->showSlideOver = true;
    }

    public function openCompanySizeYearlyHandovers($size)
    {
        $year = $this->selectedYear ?? Carbon::now()->year;

        $query = $this->getBaseQuery()
            ->whereYear('completed_at', $year);

        // Apply size filter if not 'all'
        if ($size !== 'all') {
            switch ($size) {
                case 'small':
                    $query->where('headcount', '>=', 1)->where('headcount', '<=', 24);
                    $sizeLabel = 'Small (Below 25)';
                    break;
                case 'medium':
                    $query->where('headcount', '>=', 25)->where('headcount', '<=', 99);
                    $sizeLabel = 'Medium (25-99)';
                    break;
                case 'large':
                    $query->where('headcount', '>=', 100)->where('headcount', '<=', 500);
                    $sizeLabel = 'Large (100-500)';
                    break;
                case 'enterprise':
                    $query->where('headcount', '>', 500);
                    $sizeLabel = 'Enterprise (Above 500)';
                    break;
            }
        }

        $handovers = $query->select('id', 'lead_id', 'company_name', 'status_handover', 'headcount')->get();

        // Group handovers by company size
        if ($size === 'all') {
            $this->handoverList = $this->groupHandoversByCompanySize($handovers);
            $this->slideOverTitle = "Year {$year} - All Company Sizes";
        } else {
            $this->handoverList = $handovers;
            $this->slideOverTitle = "Year {$year} - {$sizeLabel}";
        }

        $this->showSlideOver = true;
    }
}
