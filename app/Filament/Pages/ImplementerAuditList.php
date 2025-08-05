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
    protected static ?string $navigationLabel = 'Implementer Audit List';
    protected static ?string $title = '';

    public $implementers = [];
    public $statsData = [];
    public $selectedPeriod = 'week';
    public $periods = [
        'week' => 'Past Week',
        'month' => 'Past Month',
        'quarter' => 'Past Quarter'
    ];

    public $largeImplementers = [
        'Mohd Amirul Ashraf',
        'Nur Alia',
        'Zulhilmie',
        'John Low',
        'Muhamad Izzul Aiman'
    ];
    public $largeStatsData = [];

    public function mount()
    {
        // List of allowed implementers for both small and medium companies
        $this->implementers = [
            'Nurul Shaqinur Ain',
            'Ahmad Syamim',
            'Zulhilmie',
            'John Low',
            'Muhamad Izzul Aiman',
        ];

        $this->calculateStats();
        $this->calculateLargeStats();
    }

    public function calculateStats()
    {
        $this->statsData = [];

        // Calculate stats for each implementer
        foreach ($this->implementers as $implementer) {
            // Get small company assignments (1-24)
            $smallAssignments = SoftwareHandover::query()
                ->whereNotNull('completed_at')
                ->where('software_handovers.id', '>=', 666)
                ->where('implementer', $implementer)
                ->join('leads', 'software_handovers.lead_id', '=', 'leads.id')
                ->where('leads.company_size', '1-24')
                ->count();

            // Get medium company assignments (25-99)
            $mediumAssignments = SoftwareHandover::query()
                ->whereNotNull('completed_at')
                ->where('software_handovers.id', '>=', 666)
                ->where('implementer', $implementer)
                ->join('leads', 'software_handovers.lead_id', '=', 'leads.id')
                ->where('leads.company_size', '25-99')
                ->count();

            // Get latest assignment
            $latestAssignment = SoftwareHandover::query()
                ->whereNotNull('completed_at')
                ->where('software_handovers.id', '>=', 666)
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
        $this->calculateOverallStats();
    }

    public function calculateLargeStats()
    {
        $this->largeStatsData = [];

        foreach ($this->largeImplementers as $implementer) {
            // Large companies (100-500)
            $largeAssignments = SoftwareHandover::query()
                ->whereNotNull('completed_at')
                ->where('software_handovers.id', '>=', 599)
                ->where('implementer', $implementer)
                ->join('leads', 'software_handovers.lead_id', '=', 'leads.id')
                ->where('leads.company_size', '100-500')
                ->count();

            // Enterprise companies (501 and Above)
            $enterpriseAssignments = SoftwareHandover::query()
                ->whereNotNull('completed_at')
                ->where('software_handovers.id', '>=', 599)
                ->where('implementer', $implementer)
                ->join('leads', 'software_handovers.lead_id', '=', 'leads.id')
                ->where('leads.company_size', '501 and Above')
                ->count();

            $totalAssignments = $largeAssignments + $enterpriseAssignments;
            $percentLarge = $totalAssignments > 0 ? round(($largeAssignments / $totalAssignments) * 100) : 0;
            $percentEnterprise = $totalAssignments > 0 ? round(($enterpriseAssignments / $totalAssignments) * 100) : 0;

            $latestAssignment = SoftwareHandover::query()
                ->whereNotNull('completed_at')
                ->where('software_handovers.id', '>=', 599)
                ->where('implementer', $implementer)
                ->join('leads', 'software_handovers.lead_id', '=', 'leads.id')
                ->whereIn('leads.company_size', ['100-500', '501 and Above'])
                ->orderBy('completed_at', 'desc')
                ->first();

            $latestDate = $latestAssignment ? Carbon::parse($latestAssignment->completed_at)->format('M d, Y') : 'No assignments';

            $this->largeStatsData[$implementer] = [
                'large' => $largeAssignments,
                'enterprise' => $enterpriseAssignments,
                'total' => $totalAssignments,
                'percentLarge' => $percentLarge,
                'percentEnterprise' => $percentEnterprise,
                'latestAssignment' => $latestDate,
                'color' => $this->getImplementerColor($implementer),
            ];
        }

        // Overall stats for large/enterprise
        $totalLarge = SoftwareHandover::query()
            ->whereNotNull('completed_at')
            ->where('software_handovers.id', '>=', 599)
            ->whereIn('implementer', $this->largeImplementers)
            ->join('leads', 'software_handovers.lead_id', '=', 'leads.id')
            ->where('leads.company_size', '100-500')
            ->count();

        $totalEnterprise = SoftwareHandover::query()
            ->whereNotNull('completed_at')
            ->where('software_handovers.id', '>=', 599)
            ->whereIn('implementer', $this->largeImplementers)
            ->join('leads', 'software_handovers.lead_id', '=', 'leads.id')
            ->where('leads.company_size', '501 and Above')
            ->count();

        $totalAssignments = $totalLarge + $totalEnterprise;

        $latestHandover = SoftwareHandover::query()
            ->whereNotNull('completed_at')
            ->where('software_handovers.id', '>=', 599)
            ->whereIn('implementer', $this->largeImplementers)
            ->join('leads', 'software_handovers.lead_id', '=', 'leads.id')
            ->whereIn('leads.company_size', ['100-500', '501 and Above'])
            ->orderBy('software_handovers.completed_at', 'desc')
            ->select('software_handovers.*', 'leads.company_size', 'leads.id as lead_id')
            ->first();

        $latestImplementer = $latestHandover ? $latestHandover->implementer : 'None';
        $latestHandoverId = $latestHandover ? 'SW_250' . $latestHandover->id : '-';

        $latestCompanyName = '-';
        if ($latestHandover && $latestHandover->lead_id) {
            $companyDetail = \App\Models\CompanyDetail::where('lead_id', $latestHandover->lead_id)->first();
            $latestCompanyName = $companyDetail ? $companyDetail->company_name : '-';
        }

        $this->largeStatsData['overall'] = [
            'totalLarge' => $totalLarge,
            'totalEnterprise' => $totalEnterprise,
            'totalAssignments' => $totalAssignments,
            'latestImplementer' => $latestImplementer,
            'latestHandoverId' => $latestHandoverId,
            'latestCompanyName' => $latestCompanyName,
            'periodLabel' => 'All Time'
        ];
    }

    private function calculateOverallStats()
    {
        // Total assignments (all time, small & medium only)
        $totalAssignments = SoftwareHandover::query()
            ->whereNotNull('completed_at')
            ->where('software_handovers.id', '>=', 666)
            ->whereIn('implementer', $this->implementers)
            ->join('leads', 'software_handovers.lead_id', '=', 'leads.id')
            ->whereIn('leads.company_size', ['1-24', '25-99'])
            ->count();

        // Latest software handover assigned (small & medium only)
        $latestHandover = SoftwareHandover::query()
            ->whereNotNull('completed_at')
            ->where('software_handovers.id', '>=', 666)
            ->whereIn('implementer', $this->implementers)
            ->join('leads', 'software_handovers.lead_id', '=', 'leads.id')
            ->whereIn('leads.company_size', ['1-24', '25-99'])
            ->orderBy('software_handovers.completed_at', 'desc')
            ->select('software_handovers.*', 'leads.company_size', 'leads.id as lead_id')
            ->first();

        $latestImplementer = $latestHandover ? $latestHandover->implementer : 'None';
        $latestHandoverId = $latestHandover ? 'SW_' . $latestHandover->id : '-';

        // Get company name from company_details using lead_id
        $latestCompanyName = '-';
        if ($latestHandover && $latestHandover->lead_id) {
            $companyDetail = \App\Models\CompanyDetail::where('lead_id', $latestHandover->lead_id)->first();
            $latestCompanyName = $companyDetail ? $companyDetail->company_name : '-';
        }

        $this->statsData['overall'] = [
            'totalAssignments' => $totalAssignments,
            'latestImplementer' => $latestImplementer,
            'latestHandoverId' => $latestHandoverId,
            'latestCompanyName' => $latestCompanyName,
            'periodLabel' => 'All Time'
        ];
    }

    private function getImplementerColor($implementer)
    {
        return match($implementer) {
            'Ahmad Syamim' => [59, 130, 246],
            'John Low' => [16, 185, 129],
            'Zulhilmie' => [245, 158, 11],
            'Muhamad Izzul Aiman' => [236, 72, 153],
            'Nurul Shaqinur Ain' => [139, 92, 246],
            'Mohd Amirul Ashraf' => [239, 68, 68],
            'Nur Alia' => [34, 197, 94],
            default => [107, 114, 128],
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
            'largeImplementers' => $this->largeImplementers,
            'largeStatsData' => $this->largeStatsData,
            'selectedPeriod' => $this->selectedPeriod,
            'periods' => $this->periods,
        ];
    }
}
