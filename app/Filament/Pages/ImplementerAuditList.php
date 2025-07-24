<?php

namespace App\Filament\Pages;

use App\Models\SoftwareHandover;
use App\Models\User;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class ImplementerAuditList extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.implementer-audit-list';

    public $implementers = [];
    public $statsData = [];
    public $selectedPeriod = 'week';
    public $periods = [
        'week' => 'Past Week',
        'month' => 'Past Month',
        'quarter' => 'Past Quarter'
    ];

    public function mount()
    {
        // List of allowed implementers for both small and medium companies
        $this->implementers = [
            'Ahmad Syamim',
            'John Low',
            'Zulhilmie',
            'Muhamad Izzul Aiman',
            'Nurul Shaqinur Ain'
        ];

        $this->calculateStats();
    }

    public function calculateStats()
    {
        $periodStart = $this->getPeriodStartDate();
        $this->statsData = [];

        // Calculate stats for each implementer
        foreach ($this->implementers as $implementer) {
            // Get small company assignments (1-24)
            $smallAssignments = SoftwareHandover::query()
                ->whereNotNull('completed_at')
                ->where('implementer', $implementer)
                ->where('completed_at', '>=', $periodStart)
                ->join('leads', 'software_handovers.lead_id', '=', 'leads.id')
                ->where('leads.company_size', '1-24')
                ->count();

            // Get medium company assignments (25-99)
            $mediumAssignments = SoftwareHandover::query()
                ->whereNotNull('completed_at')
                ->where('implementer', $implementer)
                ->where('completed_at', '>=', $periodStart)
                ->join('leads', 'software_handovers.lead_id', '=', 'leads.id')
                ->where('leads.company_size', '25-99')
                ->count();

            // Get latest assignment
            $latestAssignment = SoftwareHandover::query()
                ->whereNotNull('completed_at')
                ->where('implementer', $implementer)
                ->orderBy('completed_at', 'desc')
                ->first();

            $latestDate = $latestAssignment ? Carbon::parse($latestAssignment->completed_at)->format('M d, Y') : 'No assignments';

            // Calculate total assignments and percentages
            $totalAssignments = $smallAssignments + $mediumAssignments;
            $percentSmall = $totalAssignments > 0 ? round(($smallAssignments / $totalAssignments) * 100) : 0;
            $percentMedium = $totalAssignments > 0 ? round(($mediumAssignments / $totalAssignments) * 100) : 0;

            $this->statsData[$implementer] = [
                'small' => $smallAssignments,
                'medium' => $mediumAssignments,
                'total' => $totalAssignments,
                'percentSmall' => $percentSmall,
                'percentMedium' => $percentMedium,
                'latestAssignment' => $latestDate,
                'color' => $this->getImplementerColor($implementer)
            ];
        }

        // Calculate overall stats
        $this->calculateOverallStats($periodStart);
    }

    private function calculateOverallStats($periodStart)
    {
        // Total assignments this period
        $totalAssignments = SoftwareHandover::query()
            ->whereNotNull('completed_at')
            ->whereIn('implementer', $this->implementers)
            ->where('completed_at', '>=', $periodStart)
            ->count();

        // Most active implementer
        $implementerCounts = SoftwareHandover::query()
            ->whereNotNull('completed_at')
            ->whereIn('implementer', $this->implementers)
            ->where('completed_at', '>=', $periodStart)
            ->select('implementer', DB::raw('count(*) as total'))
            ->groupBy('implementer')
            ->orderBy('total', 'desc')
            ->first();

        $mostActive = $implementerCounts ? $implementerCounts->implementer : 'None';
        $mostActiveCount = $implementerCounts ? $implementerCounts->total : 0;

        $this->statsData['overall'] = [
            'totalAssignments' => $totalAssignments,
            'mostActive' => $mostActive,
            'mostActiveCount' => $mostActiveCount,
            'periodLabel' => $this->periods[$this->selectedPeriod]
        ];
    }

    private function getPeriodStartDate()
    {
        return match($this->selectedPeriod) {
            'week' => Carbon::now()->subWeek(),
            'month' => Carbon::now()->subMonth(),
            'quarter' => Carbon::now()->subMonths(3),
            default => Carbon::now()->subWeek(),
        };
    }

    private function getImplementerColor($implementer)
    {
        return match($implementer) {
            'Ahmad Syamim' => 'rgb(59, 130, 246)', // Blue
            'John Low' => 'rgb(16, 185, 129)', // Green
            'Zulhilmie' => 'rgb(245, 158, 11)', // Amber
            'Muhamad Izzul Aiman' => 'rgb(236, 72, 153)', // Pink
            'Nurul Shaqinur Ain' => 'rgb(139, 92, 246)', // Purple
            default => 'rgb(107, 114, 128)', // Gray
        };
    }

    public function updatedSelectedPeriod()
    {
        $this->calculateStats();
    }

    protected function getViewData(): array
    {
        return [
            'implementers' => $this->implementers,
            'statsData' => $this->statsData,
            'selectedPeriod' => $this->selectedPeriod,
            'periods' => $this->periods,
        ];
    }
}
