<?php
namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\SoftwareHandover;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SoftwareHandoverAnalysisV2 extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Sales Admin Analysis V2';
    protected static ?string $title = '';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.software-handover-analysis-v2';

    public function getHandoversByMonthAndStatus()
    {
        $currentYear = Carbon::now()->year;

        $monthlyData = [];

        for ($month = 1; $month <= 12; $month++) {
            $closedCount = SoftwareHandover::whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $month)
                ->where('status_handover', 'CLOSED')
                ->count();

            $openCount = SoftwareHandover::whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $month)
                ->where('status_handover', 'OPEN')
                ->count();

            $delayCount = SoftwareHandover::whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $month)
                ->where('status_handover', 'DELAY')
                ->count();

            $inactiveCount = SoftwareHandover::whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $month)
                ->where('status_handover', 'INACTIVE')
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
}
