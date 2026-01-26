<?php

namespace App\Filament\Pages;

use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketModule;
use Filament\Pages\Page;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TicketAnalysis extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static string $view = 'filament.pages.ticket-analysis';
    protected static ?string $navigationLabel = 'Ticket Analysis';
    protected static ?string $title = '';
    protected static ?string $slug = 'ticket-analysis';
    protected static ?int $navigationSort = 4;
    protected static bool $shouldRegisterNavigation = false;

    // Filter properties
    public $startDate;
    public $endDate;
    public $selectedProduct = 'all';

    // Summary stats
    public $totalTickets = 0;
    public $openTickets = 0;
    public $completedTickets = 0;
    public $avgResolutionDays = 0;

    // Chart data
    public $priorityData = [];
    public $moduleData = [];
    public $durationData = [];

    // Slide-over modal
    public $showSlideOver = false;
    public $ticketList = [];
    public $slideOverTitle = 'Tickets';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.pages.ticket-analysis');
    }

    public function mount()
    {
        // Default date range: last 6 months
        $this->endDate = Carbon::now()->format('Y-m-d');
        $this->startDate = Carbon::now()->subMonths(6)->format('Y-m-d');

        $this->loadData();
    }

    public function updatedStartDate()
    {
        $this->loadData();
    }

    public function updatedEndDate()
    {
        $this->loadData();
    }

    public function updatedSelectedProduct()
    {
        $this->loadData();
    }

    private function getBaseQuery()
    {
        $query = Ticket::query();

        // Product filter
        if ($this->selectedProduct === 'v1') {
            $query->where('product_id', 1);
        } elseif ($this->selectedProduct === 'v2') {
            $query->where('product_id', 2);
        } else {
            $query->whereIn('product_id', [1, 2]);
        }

        // Date range filter
        if ($this->startDate) {
            $query->where('created_date', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->where('created_date', '<=', $this->endDate);
        }

        return $query;
    }

    public function loadData()
    {
        $this->fetchSummaryStats();
        $this->fetchPriorityData();
        $this->fetchModuleData();
        $this->fetchDurationData();
    }

    private function fetchSummaryStats()
    {
        $query = $this->getBaseQuery();

        $this->totalTickets = (clone $query)->count();

        $this->openTickets = (clone $query)
            ->whereNotIn('status', ['Closed', 'Resolved'])
            ->count();

        $this->completedTickets = (clone $query)
            ->whereIn('status', ['Closed', 'Resolved'])
            ->count();

        // Average resolution time (only for completed tickets with completion_date)
        $avgDays = (clone $query)
            ->whereNotNull('completion_date')
            ->whereNotNull('created_date')
            ->selectRaw('AVG(DATEDIFF(completion_date, created_date)) as avg_days')
            ->value('avg_days');

        $this->avgResolutionDays = round($avgDays ?? 0, 1);
    }

    private function fetchPriorityData()
    {
        $query = $this->getBaseQuery();

        $priorityCounts = (clone $query)
            ->select('priority_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('priority_id')
            ->groupBy('priority_id')
            ->get();

        // Get priority names and colors
        $priorities = TicketPriority::whereIn('id', $priorityCounts->pluck('priority_id'))->get()->keyBy('id');

        $this->priorityData = $priorityCounts->map(function ($item) use ($priorities) {
            $priority = $priorities->get($item->priority_id);
            return [
                'id' => $item->priority_id,
                'name' => $priority ? $priority->name : 'Unknown',
                'count' => $item->count,
                'percentage' => $this->totalTickets > 0 ? round(($item->count / $this->totalTickets) * 100, 1) : 0,
            ];
        })->sortByDesc('count')->values()->toArray();
    }

    private function fetchModuleData()
    {
        $query = $this->getBaseQuery();

        $moduleCounts = (clone $query)
            ->select('module_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('module_id')
            ->groupBy('module_id')
            ->orderByDesc('count')
            ->limit(10) // Top 10 modules
            ->get();

        // Get module names
        $modules = TicketModule::whereIn('id', $moduleCounts->pluck('module_id'))->get()->keyBy('id');

        $maxCount = $moduleCounts->max('count') ?? 1;

        $this->moduleData = $moduleCounts->map(function ($item) use ($modules, $maxCount) {
            $module = $modules->get($item->module_id);
            return [
                'id' => $item->module_id,
                'name' => $module ? $module->name : 'Unknown',
                'count' => $item->count,
                'percentage' => round(($item->count / $maxCount) * 100, 1),
            ];
        })->values()->toArray();
    }

    private function fetchDurationData()
    {
        $query = $this->getBaseQuery();

        $this->durationData = (clone $query)
            ->whereNotNull('completion_date')
            ->whereNotNull('created_date')
            ->selectRaw('DATE_FORMAT(completion_date, "%Y-%m") as month')
            ->selectRaw('AVG(DATEDIFF(completion_date, created_date)) as avg_days')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->limit(12) // Last 12 months with data
            ->get()
            ->map(function ($item) {
                return [
                    'month' => Carbon::parse($item->month . '-01')->format('M Y'),
                    'avg_days' => round($item->avg_days, 1),
                    'count' => $item->count,
                ];
            })
            ->toArray();
    }

    // Slide-over methods
    public function openPrioritySlideOver($priorityId)
    {
        $query = $this->getBaseQuery();
        $priority = TicketPriority::find($priorityId);

        $this->ticketList = (clone $query)
            ->where('priority_id', $priorityId)
            ->orderByDesc('created_date')
            ->limit(100)
            ->get();

        $this->slideOverTitle = ($priority ? $priority->name : 'Priority') . ' Tickets';
        $this->showSlideOver = true;
    }

    public function openModuleSlideOver($moduleId)
    {
        $query = $this->getBaseQuery();
        $module = TicketModule::find($moduleId);

        $this->ticketList = (clone $query)
            ->where('module_id', $moduleId)
            ->orderByDesc('created_date')
            ->limit(100)
            ->get();

        $this->slideOverTitle = ($module ? $module->name : 'Module') . ' Tickets';
        $this->showSlideOver = true;
    }

    public function openStatusSlideOver($status)
    {
        $query = $this->getBaseQuery();

        if ($status === 'open') {
            $this->ticketList = (clone $query)
                ->whereNotIn('status', ['Closed', 'Resolved'])
                ->orderByDesc('created_date')
                ->limit(100)
                ->get();
            $this->slideOverTitle = 'Open Tickets';
        } else {
            $this->ticketList = (clone $query)
                ->whereIn('status', ['Closed', 'Resolved'])
                ->orderByDesc('created_date')
                ->limit(100)
                ->get();
            $this->slideOverTitle = 'Completed Tickets';
        }

        $this->showSlideOver = true;
    }

    public function closeSlideOver()
    {
        $this->showSlideOver = false;
        $this->ticketList = [];
    }
}
