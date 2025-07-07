<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\SoftwareHandover;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SoftwareHandoverAnalysis extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static ?string $navigationGroup = 'Analysis';
    protected static string $view = 'filament.pages.software-handover-analysis';
    protected static ?string $navigationLabel = 'Software Handover Analysis';
    protected static ?int $navigationSort = 2;
    protected static ?string $title = 'Software Handover Analysis';

    public $selectedMonth = null;
    public $availableMonths = [];
    public $showSlideOver = false;
    public $slideOverTitle = '';
    public $handoverList = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.pages.software-handover-analysis');
    }

    public function mount()
    {
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
        return $this->getBaseQuery()
            ->where('implementer', $implementer)
            ->whereIn('status_handover', ['OPEN', 'DELAY', 'INACTIVE'])
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
        // Define your list of active implementers - this could be stored in config or determined dynamically
        $activeImplementers = ['SHAQINUR', 'SYAMIM', 'SYAZWAN', 'AIMAN', 'ZULHILMIE', 'AMIRUL', 'JOHN', 'FAZULIANA'];

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
            'HANIF' => 'Muhammad Hanif',
            'Ummu Najwa Fajrina' => 'Ummu Najwa Fajrina',
            'Noor Syazana' => 'Noor Syazana',
        ];
    }

    public function getInactiveImplementers()
    {
        // Define your list of inactive implementers
        $inactiveImplementers = ['BARI', 'ADZZIM', 'AZRUL', 'Ummu Najwa Fajrina', 'Noor Syazana', 'HANIF'];
        $displayNames = $this->getImplementerDisplayNames();

        $implementers = [];

        foreach ($inactiveImplementers as $implementer) {
            $total = $this->getImplementerTotal($implementer);
            $closed = $this->getImplementerClosedCount($implementer);
            $ongoing = $this->getImplementerOngoingCount($implementer);
            $open = $this->getImplementerStatusCount($implementer, 'OPEN');
            $delay = $this->getImplementerStatusCount($implementer, 'DELAY');
            $inactive = $this->getImplementerStatusCount($implementer, 'INACTIVE');

            // Get display name if available, otherwise use original name
            $displayName = isset($displayNames[$implementer]) ? $displayNames[$implementer] : $implementer;

            $implementers[] = [
                'name' => $displayName, // Use display name in the UI
                'dbName' => $implementer, // Keep the original name for database queries
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

        $ongoing = $open + $delay + $inactive;

        return [
            'total' => $total,
            'closed' => $closed,
            'ongoing' => $ongoing,
            'open' => $open,
            'delay' => $delay,
            'inactive' => $inactive
        ];
    }

    public function getTier1Implementers()
    {
        return ['Nurul Shaqinur Ain', 'Ahmad Syamim', 'Ahmad Syazwan'];
    }

    public function getTier2Implementers()
    {
        return ['Muhamad Izzul Aiman', 'Zulhilmie'];
    }

    public function getTier3Implementers()
    {
        return ['Mohd Amirul Ashraf', 'John Low', 'Nur Fazuliana'];
    }

    public function getInactiveImplementersList()
    {
        return ['BARI', 'ADZZIM', 'AZRUL', 'Ummu Najwa Fajrina', 'Noor Syazana', 'HANIF'];
    }
}
