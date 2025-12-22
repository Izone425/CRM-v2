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

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.pages.implementer-audit-list');
    }

    public function mount()
    {
        // List of implementers in the specified order
        $this->implementers = [
            'Rahmah',
            'Mohd Fairos',
            'Siti Nadia',
            'Mohd Amirul Ashraf',
            'John Low',
            'Zulhilmie',
            'Muhamad Izzul Aiman',
            'Ahmad Syamim',
            'Nur Alia',
            'Ameerul Asyraf',
        ];

        $this->calculateStats();
    }

    public function calculateStats()
    {
        $this->statsData = [];

        // Calculate stats for each implementer
        foreach ($this->implementers as $implementer) {
            // Get small company assignments (1-24 headcount)
            $smallAssignments = SoftwareHandover::query()
                ->whereNotNull('completed_at')
                ->where('software_handovers.id', '>=', 666)
                ->where('implementer', $implementer)
                ->where('headcount', '>=', 1)
                ->where('headcount', '<=', 24)
                ->count();

            // Get medium company assignments (25-99 headcount)
            $mediumAssignments = SoftwareHandover::query()
                ->whereNotNull('completed_at')
                ->where('software_handovers.id', '>=', 666)
                ->where('implementer', $implementer)
                ->where('headcount', '>=', 25)
                ->where('headcount', '<=', 99)
                ->count();

            // Get large company assignments (100-500 headcount)
            $largeAssignments = SoftwareHandover::query()
                ->whereNotNull('completed_at')
                ->where('software_handovers.id', '>=', 599)
                ->where('implementer', $implementer)
                ->where('headcount', '>=', 100)
                ->where('headcount', '<=', 500)
                ->count();

            // Get enterprise company assignments (501+ headcount)
            $enterpriseAssignments = SoftwareHandover::query()
                ->whereNotNull('completed_at')
                ->where('software_handovers.id', '>=', 599)
                ->where('implementer', $implementer)
                ->where('headcount', '>=', 501)
                ->count();

            // Get latest assignment overall
            $latestAssignment = SoftwareHandover::query()
                ->whereNotNull('completed_at')
                ->where(function($query) {
                    $query->where('software_handovers.id', '>=', 666)
                          ->orWhere('software_handovers.id', '>=', 599);
                })
                ->where('implementer', $implementer)
                ->where('headcount', '>=', 1)
                ->orderBy('completed_at', 'desc')
                ->first();

            $latestAssignmentDate = $latestAssignment ? Carbon::parse($latestAssignment->completed_at)->format('M d, Y') : 'No assignments';

            // Calculate total and percentages
            $totalAssignments = $smallAssignments + $mediumAssignments + $largeAssignments + $enterpriseAssignments;
            $percentSmall = $totalAssignments > 0 ? round(($smallAssignments / $totalAssignments) * 100) : 0;
            $percentMedium = $totalAssignments > 0 ? round(($mediumAssignments / $totalAssignments) * 100) : 0;
            $percentLarge = $totalAssignments > 0 ? round(($largeAssignments / $totalAssignments) * 100) : 0;
            $percentEnterprise = $totalAssignments > 0 ? round(($enterpriseAssignments / $totalAssignments) * 100) : 0;

            $this->statsData[$implementer] = [
                'small' => $smallAssignments,
                'medium' => $mediumAssignments,
                'large' => $largeAssignments,
                'enterprise' => $enterpriseAssignments,
                'total' => $totalAssignments,
                'percentSmall' => $percentSmall,
                'percentMedium' => $percentMedium,
                'percentLarge' => $percentLarge,
                'percentEnterprise' => $percentEnterprise,
                'latestAssignment' => $latestAssignmentDate,
                'color' => $this->getImplementerColor($implementer)
            ];
        }

        // Calculate overall stats
        $this->calculateOverallStats();
    }

    private function formatHandoverId($handover)
    {
        // Extract the year from created_at (or use completed_at if that's more appropriate)
        $year = Carbon::parse($handover->created_at)->format('y');

        // Format ID to ensure 4 digits (pad with zeros if needed)
        $formattedId = str_pad($handover->id, 4, '0', STR_PAD_LEFT);

        // Combine into final format: SW_25XXXX
        return "SW_{$year}{$formattedId}";
    }

    private function calculateOverallStats()
    {
        // Total assignments for all company sizes
        $totalAssignments = SoftwareHandover::query()
            ->whereNotNull('completed_at')
            ->where(function($query) {
                $query->where('software_handovers.id', '>=', 666)
                      ->orWhere('software_handovers.id', '>=', 599);
            })
            ->whereIn('implementer', $this->implementers)
            ->where('headcount', '>=', 1)
            ->count();

        // Latest software handover overall
        $latestHandover = SoftwareHandover::query()
            ->whereNotNull('completed_at')
            ->where(function($query) {
                $query->where('software_handovers.id', '>=', 666)
                      ->orWhere('software_handovers.id', '>=', 599);
            })
            ->whereIn('implementer', $this->implementers)
            ->where('headcount', '>=', 1)
            ->orderBy('completed_at', 'desc')
            ->first();

        $this->statsData['overall'] = [
            'totalAssignments' => $totalAssignments,
            'latestImplementer' => $latestHandover ? $latestHandover->implementer : 'None',
            'latestHandoverId' => $latestHandover ? $this->formatHandoverId($latestHandover) : '-',
            'periodLabel' => 'All Time'
        ];
    }

    private function getImplementerColor($implementer)
    {
        return match($implementer) {
            'Rahmah' => [239, 68, 68],                    // Red
            'Mohd Fairos' => [16, 185, 129],              // Emerald Green
            'Siti Nadia' => [245, 158, 11],               // Amber Orange
            'Mohd Amirul Ashraf' => [236, 72, 153],       // Pink
            'John Low' => [59, 130, 246],                 // Blue
            'Zulhilmie' => [168, 85, 247],                // Purple
            'Muhamad Izzul Aiman' => [34, 197, 94],       // Green
            'Ahmad Syamim' => [234, 179, 8],              // Yellow
            'Nur Alia' => [20, 184, 166],                 // Teal
            'Ameerul Asyraf' => [251, 146, 60],           // Orange
            default => [107, 114, 128],                   // Gray
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
