<?php

namespace App\Filament\Pages;

use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketModule;
use App\Exports\TicketAnalysisExport;
use Filament\Pages\Page;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

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
    public $selectedProduct = 'v1';
    public $showAllTickets = false;

    // Trend chart filter
    public $trendStartDate;
    public $trendEndDate;

    // Summary stats
    public $totalTickets = 0;
    public $openTickets = 0;
    public $completedTickets = 0;
    public $avgResolutionDays = 0;

    // Chart data
    public $priorityData = [];
    public $moduleData = [];
    public $priorityModuleData = []; // Priority with module breakdown for bar chart
    public $durationData = [];

    // Frontend tickets data
    public $frontendTotalTickets = 0;
    public $frontendUserData = [];

    // Selected frontend user chart data
    public $selectedFrontendUserId = null;
    public $selectedFrontendUserName = '';
    public $selectedFrontendUserModuleData = [];

    // Slide-over modal
    public $showSlideOver = false;
    public $ticketList = [];
    public $ticketsByPriority = [];
    public $slideOverTitle = 'Tickets';
    public $focusPriorityId = null;

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
        // Default date range: 1st of current month to today
        $this->endDate = Carbon::now()->format('Y-m-d');
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');

        // Default trend chart date range: last 3 months
        $this->trendEndDate = Carbon::now()->format('Y-m-d');
        $this->trendStartDate = Carbon::now()->subMonths(3)->format('Y-m-d');

        $this->loadData();
    }

    public function updatedStartDate($value)
    {
        $this->startDate = $value;
        $this->loadData();
    }

    public function updatedEndDate($value)
    {
        $this->endDate = $value;
        $this->loadData();
    }

    public function updatedSelectedProduct($value)
    {
        $this->selectedProduct = $value;
        $this->loadData();
    }

    // Generic filter method for wire:change
    public function applyFilters()
    {
        $this->loadData();
    }

    private function getBaseQuery()
    {
        $query = Ticket::query();

        // Product filter - V1 or V2 only (no merge)
        if ($this->selectedProduct === 'v2') {
            $query->where('product_id', 2);
        } else {
            $query->where('product_id', 1);
        }

        // Date range filter based on created_at timestamp (skip if showAllTickets is enabled)
        if (!$this->showAllTickets) {
            if ($this->startDate) {
                $query->whereDate('created_at', '>=', $this->startDate);
            }
            if ($this->endDate) {
                $query->whereDate('created_at', '<=', $this->endDate);
            }
        }

        return $query;
    }

    public function loadData()
    {
        $this->fetchSummaryStats();
        $this->fetchPriorityData();
        $this->fetchModuleData();
        $this->fetchPriorityModuleData();
        $this->fetchDurationData();
        $this->fetchFrontendData();
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

        // Average resolution time in minutes - based on Status Log
        // Find when each ticket was first marked as "Closed" from logs
        $closedTicketIds = (clone $query)
            ->where('status', 'Closed')
            ->pluck('id');

        if ($closedTicketIds->isNotEmpty()) {
            // Get first "Closed" log entry for each ticket and calculate duration
            $avgMinutes = DB::connection('ticketingsystem_live')
                ->table('ticket_logs as tl')
                ->join('tickets as t', 't.id', '=', 'tl.ticket_id')
                ->whereIn('tl.ticket_id', $closedTicketIds)
                ->where('tl.field_name', 'status')
                ->where('tl.new_value', 'Closed')
                ->where('tl.old_value', '!=', 'Closed')
                ->whereNotNull('t.created_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, t.created_at, tl.created_at)) as avg_minutes')
                ->value('avg_minutes');

            $this->avgResolutionDays = $this->formatDuration($avgMinutes ?? 0);
        } else {
            $this->avgResolutionDays = '00:00:00';
        }
    }

    /**
     * Format duration from minutes to DD:HH:MM format
     */
    private function formatDuration($totalMinutes): string
    {
        if ($totalMinutes <= 0) {
            return '00:00:00';
        }

        $totalMinutes = round($totalMinutes);
        $days = floor($totalMinutes / (60 * 24));
        $hours = floor(($totalMinutes % (60 * 24)) / 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%02d:%02d:%02d', $days, $hours, $minutes);
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

        // Get top 10 modules by count
        $moduleCounts = (clone $query)
            ->select('module_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('module_id')
            ->groupBy('module_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $moduleIds = $moduleCounts->pluck('module_id')->toArray();

        // Get module names
        $modules = TicketModule::whereIn('id', $moduleIds)->get()->keyBy('id');

        // Get all priorities for color mapping
        $priorities = TicketPriority::all()->keyBy('id');

        // Get breakdown by module and priority
        $moduleBreakdown = (clone $query)
            ->select('module_id', 'priority_id', DB::raw('COUNT(*) as count'))
            ->whereIn('module_id', $moduleIds)
            ->whereNotNull('priority_id')
            ->groupBy('module_id', 'priority_id')
            ->get()
            ->groupBy('module_id');

        $maxCount = $moduleCounts->max('count') ?? 1;

        // Priority colors mapping
        $priorityColors = [
            'Software Bugs' => '#EF4444',
            'Back End Assistance' => '#F59E0B',
            'Critical Enhancement' => '#8B5CF6',
            'Non-Critical Enhancement' => '#10B981',
            'Paid Customization' => '#3B82F6',
        ];

        $this->moduleData = $moduleCounts->map(function ($item) use ($modules, $maxCount, $moduleBreakdown, $priorities, $priorityColors) {
            $module = $modules->get($item->module_id);
            $breakdown = $moduleBreakdown->get($item->module_id, collect());

            // Build priority breakdown for this module
            $priorityBreakdown = $breakdown->map(function ($b) use ($priorities, $priorityColors, $item) {
                $priority = $priorities->get($b->priority_id);
                $name = $priority ? $priority->name : 'Unknown';
                return [
                    'priority_id' => $b->priority_id,
                    'name' => $name,
                    'count' => $b->count,
                    'percentage' => $item->count > 0 ? round(($b->count / $item->count) * 100, 1) : 0,
                    'color' => $priorityColors[$name] ?? '#6B7280',
                ];
            })->sortByDesc('count')->values()->toArray();

            return [
                'id' => $item->module_id,
                'name' => $module ? $module->name : 'Unknown',
                'count' => $item->count,
                'percentage' => round(($item->count / $maxCount) * 100, 1),
                'breakdown' => $priorityBreakdown,
            ];
        })->values()->toArray();
    }

    private function fetchPriorityModuleData()
    {
        $query = $this->getBaseQuery();

        // Get all priorities with counts
        $priorityCounts = (clone $query)
            ->select('priority_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('priority_id')
            ->groupBy('priority_id')
            ->orderByDesc('count')
            ->get();

        $priorityIds = $priorityCounts->pluck('priority_id')->toArray();

        // Get priority names
        $priorities = TicketPriority::whereIn('id', $priorityIds)->get()->keyBy('id');

        // Get all modules for name mapping
        $modules = TicketModule::all()->keyBy('id');

        // Get breakdown by priority and module
        $priorityBreakdown = (clone $query)
            ->select('priority_id', 'module_id', DB::raw('COUNT(*) as count'))
            ->whereIn('priority_id', $priorityIds)
            ->whereNotNull('module_id')
            ->groupBy('priority_id', 'module_id')
            ->get()
            ->groupBy('priority_id');

        $maxCount = $priorityCounts->max('count') ?? 1;

        // Module colors mapping - must match the pie chart colors
        $moduleColors = [
            '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
            '#EC4899', '#6366F1', '#14B8A6', '#F97316', '#06B6D4'
        ];

        // Build module color map from moduleData (pie chart order) for consistency
        $moduleColorMap = [];
        foreach ($this->moduleData as $index => $mod) {
            $moduleColorMap[$mod['id']] = $moduleColors[$index % count($moduleColors)];
        }

        $this->priorityModuleData = $priorityCounts->map(function ($item) use ($priorities, $maxCount, $priorityBreakdown, $modules, $moduleColors, $moduleColorMap) {
            $priority = $priorities->get($item->priority_id);
            $breakdown = $priorityBreakdown->get($item->priority_id, collect());

            // Build module breakdown for this priority
            $moduleBreakdownData = $breakdown->map(function ($b) use ($modules, $moduleColors, $moduleColorMap, $item) {
                $module = $modules->get($b->module_id);
                // Use color from pie chart mapping, fallback to default
                $color = $moduleColorMap[$b->module_id] ?? '#6B7280';
                return [
                    'module_id' => $b->module_id,
                    'name' => $module ? $module->name : 'Unknown',
                    'count' => $b->count,
                    'percentage' => $item->count > 0 ? round(($b->count / $item->count) * 100, 1) : 0,
                    'color' => $color,
                ];
            })->sortByDesc('count')->values()->toArray();

            return [
                'id' => $item->priority_id,
                'name' => $priority ? $priority->name : 'Unknown',
                'count' => $item->count,
                'percentage' => round(($item->count / $maxCount) * 100, 1),
                'breakdown' => $moduleBreakdownData,
            ];
        })->values()->toArray();
    }

    private function fetchDurationData()
    {
        // Build query for closed tickets based on selected product
        $ticketQuery = Ticket::query();

        if ($this->selectedProduct === 'v2') {
            $ticketQuery->where('product_id', 2);
        } else {
            $ticketQuery->where('product_id', 1);
        }

        // Get closed ticket IDs
        $closedTicketIds = (clone $ticketQuery)
            ->where('status', 'Closed')
            ->pluck('id');

        if ($closedTicketIds->isEmpty()) {
            $this->durationData = [];
            return;
        }

        // Build query with trend date range filter
        $logQuery = DB::connection('ticketingsystem_live')
            ->table('ticket_logs as tl')
            ->join('tickets as t', 't.id', '=', 'tl.ticket_id')
            ->whereIn('tl.ticket_id', $closedTicketIds)
            ->where('tl.field_name', 'status')
            ->where('tl.new_value', 'Closed')
            ->where('tl.old_value', '!=', 'Closed')
            ->whereNotNull('t.created_at');

        // Apply trend date range filter
        if ($this->trendStartDate) {
            $logQuery->whereDate('tl.created_at', '>=', $this->trendStartDate);
        }
        if ($this->trendEndDate) {
            $logQuery->whereDate('tl.created_at', '<=', $this->trendEndDate);
        }

        // Get resolution data from Status Log - group by the date when ticket was closed
        $this->durationData = $logQuery
            ->selectRaw('DATE_FORMAT(tl.created_at, "%Y-%m-%d") as close_date')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, t.created_at, tl.created_at)) as avg_hours')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('close_date')
            ->orderBy('close_date')
            ->get()
            ->map(function ($item) {
                $avgDays = round($item->avg_hours / 24, 1);
                return [
                    'month' => Carbon::parse($item->close_date)->format('d M'),
                    'avg_days' => $avgDays,
                    'count' => $item->count,
                ];
            })
            ->toArray();
    }

    private function fetchFrontendData()
    {
        // Base query for frontend tickets (is_internal = false)
        $query = $this->getBaseQuery()->where('is_internal', false);

        // Total frontend tickets
        $this->frontendTotalTickets = (clone $query)->count();

        // By User (Requestor) - query from ticketingsystem_live database
        $userCounts = (clone $query)
            ->select('requestor_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('requestor_id')
            ->groupBy('requestor_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Query users from the ticketingsystem_live connection (same as tickets)
        $users = DB::connection('ticketingsystem_live')
            ->table('users')
            ->whereIn('id', $userCounts->pluck('requestor_id'))
            ->get()
            ->keyBy('id');

        $this->frontendUserData = $userCounts->map(function ($item) use ($users) {
            $user = $users->get($item->requestor_id);
            return [
                'id' => $item->requestor_id,
                'name' => $user ? $user->name : 'Unknown',
                'count' => $item->count,
            ];
        })->values()->toArray();
    }

    // Frontend user chart methods
    public function selectFrontendUser($userId)
    {
        // Toggle off if clicking the same user
        if ($this->selectedFrontendUserId === $userId) {
            $this->clearFrontendUserChart();
            return;
        }

        $query = $this->getBaseQuery()->where('is_internal', false)->where('requestor_id', $userId);

        // Get user from ticketingsystem_live database
        $user = DB::connection('ticketingsystem_live')
            ->table('users')
            ->where('id', $userId)
            ->first();

        $this->selectedFrontendUserId = $userId;
        $this->selectedFrontendUserName = $user ? $user->name : 'Unknown';

        // Get module counts for this user
        $moduleCounts = (clone $query)
            ->select('module_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('module_id')
            ->groupBy('module_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $moduleIds = $moduleCounts->pluck('module_id')->toArray();
        $modules = TicketModule::whereIn('id', $moduleIds)->get()->keyBy('id');

        // Get all priorities for color mapping
        $priorities = TicketPriority::all()->keyBy('id');

        // Get breakdown by module and priority for this user
        $moduleBreakdown = (clone $query)
            ->select('module_id', 'priority_id', DB::raw('COUNT(*) as count'))
            ->whereIn('module_id', $moduleIds)
            ->whereNotNull('priority_id')
            ->groupBy('module_id', 'priority_id')
            ->get()
            ->groupBy('module_id');

        $maxCount = $moduleCounts->max('count') ?? 1;

        // Priority colors mapping
        $priorityColors = [
            'Software Bugs' => '#EF4444',
            'Back End Assistance' => '#F59E0B',
            'Critical Enhancement' => '#8B5CF6',
            'Non-Critical Enhancement' => '#10B981',
            'Paid Customization' => '#3B82F6',
        ];

        $this->selectedFrontendUserModuleData = $moduleCounts->map(function ($item) use ($modules, $maxCount, $moduleBreakdown, $priorities, $priorityColors) {
            $module = $modules->get($item->module_id);
            $breakdown = $moduleBreakdown->get($item->module_id, collect());

            // Build priority breakdown for this module
            $priorityBreakdown = $breakdown->map(function ($b) use ($priorities, $priorityColors, $item) {
                $priority = $priorities->get($b->priority_id);
                $name = $priority ? $priority->name : 'Unknown';
                return [
                    'priority_id' => $b->priority_id,
                    'name' => $name,
                    'count' => $b->count,
                    'percentage' => $item->count > 0 ? round(($b->count / $item->count) * 100, 1) : 0,
                    'color' => $priorityColors[$name] ?? '#6B7280',
                ];
            })->sortByDesc('count')->values()->toArray();

            return [
                'id' => $item->module_id,
                'name' => $module ? $module->name : 'Unknown',
                'count' => $item->count,
                'percentage' => round(($item->count / $maxCount) * 100, 1),
                'breakdown' => $priorityBreakdown,
            ];
        })->values()->toArray();
    }

    public function clearFrontendUserChart()
    {
        $this->selectedFrontendUserId = null;
        $this->selectedFrontendUserName = '';
        $this->selectedFrontendUserModuleData = [];
    }

    public function openFrontendUserModuleSlideOver($moduleId)
    {
        if (!$this->selectedFrontendUserId) {
            return;
        }

        $query = $this->getBaseQuery()
            ->where('is_internal', false)
            ->where('requestor_id', $this->selectedFrontendUserId)
            ->where('module_id', $moduleId);

        $module = TicketModule::find($moduleId);

        // Get tickets with priority relationship
        $tickets = (clone $query)
            ->with('priority:id,name')
            ->select('id', 'ticket_id', 'title', 'company_name', 'status', 'created_date', 'priority_id')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        // Store minimal ticket data
        $this->ticketList = $tickets->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'ticket_id' => $ticket->ticket_id,
                'title' => $ticket->title,
                'company_name' => $ticket->company_name,
                'status' => $ticket->status,
                'created_date' => $ticket->created_date ? $ticket->created_date->format('Y-m-d') : null,
            ];
        })->toArray();

        // Group tickets by priority
        $priorityColors = [
            'Software Bugs' => '#EF4444',
            'Back End Assistance' => '#F59E0B',
            'Critical Enhancement' => '#8B5CF6',
            'Non-Critical Enhancement' => '#10B981',
            'Paid Customization' => '#3B82F6',
        ];

        $grouped = $tickets->groupBy(function ($ticket) {
            return $ticket->priority ? $ticket->priority->id : 0;
        });

        $this->ticketsByPriority = $grouped->map(function ($ticketGroup, $priorityIdKey) use ($priorityColors) {
            $firstTicket = $ticketGroup->first();
            $priorityName = $firstTicket && $firstTicket->priority ? $firstTicket->priority->name : 'Unknown';
            return [
                'id' => $priorityIdKey,
                'name' => $priorityName,
                'color' => $priorityColors[$priorityName] ?? '#6B7280',
                'count' => $ticketGroup->count(),
                'tickets' => $ticketGroup->map(function ($ticket) {
                    return [
                        'id' => $ticket->id,
                        'ticket_id' => $ticket->ticket_id,
                        'title' => $ticket->title,
                        'company_name' => $ticket->company_name,
                        'status' => $ticket->status,
                        'created_date' => $ticket->created_date ? $ticket->created_date->format('Y-m-d') : null,
                    ];
                })->values()->toArray(),
            ];
        })->sortByDesc('count')->values()->toArray();

        $this->focusPriorityId = null;
        $this->slideOverTitle = $this->selectedFrontendUserName . ' - ' . ($module ? $module->name : 'Module');
        $this->showSlideOver = true;
    }

    // Slide-over methods
    public function openPrioritySlideOver($priorityId)
    {
        $query = $this->getBaseQuery();
        $priority = TicketPriority::find($priorityId);

        $tickets = (clone $query)
            ->where('priority_id', $priorityId)
            ->select('id', 'ticket_id', 'title', 'company_name', 'status', 'created_date')
            ->orderByDesc('created_date')
            ->limit(100)
            ->get();

        $this->ticketList = $tickets->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'ticket_id' => $ticket->ticket_id,
                'title' => $ticket->title,
                'company_name' => $ticket->company_name,
                'status' => $ticket->status,
                'created_date' => $ticket->created_date ? $ticket->created_date->format('Y-m-d') : null,
            ];
        })->toArray();

        $this->slideOverTitle = ($priority ? $priority->name : 'Priority') . ' Tickets';
        $this->showSlideOver = true;
    }

    public function openModuleSlideOver($moduleId, $priorityId = null)
    {
        $query = $this->getBaseQuery();
        $module = TicketModule::find($moduleId);

        // Get tickets with priority relationship - only select needed fields
        $tickets = (clone $query)
            ->where('module_id', $moduleId)
            ->with('priority:id,name')
            ->select('id', 'ticket_id', 'title', 'company_name', 'status', 'created_date', 'priority_id')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        // Store minimal ticket data for flat list fallback
        $this->ticketList = $tickets->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'ticket_id' => $ticket->ticket_id,
                'title' => $ticket->title,
                'company_name' => $ticket->company_name,
                'status' => $ticket->status,
                'created_date' => $ticket->created_date ? $ticket->created_date->format('Y-m-d') : null,
            ];
        })->toArray();

        // Group tickets by priority
        $priorityColors = [
            'Software Bugs' => '#EF4444',
            'Back End Assistance' => '#F59E0B',
            'Critical Enhancement' => '#8B5CF6',
            'Non-Critical Enhancement' => '#10B981',
            'Paid Customization' => '#3B82F6',
        ];

        $grouped = $tickets->groupBy(function ($ticket) {
            return $ticket->priority ? $ticket->priority->id : 0;
        });

        $this->ticketsByPriority = $grouped->map(function ($ticketGroup, $priorityIdKey) use ($priorityColors) {
            $firstTicket = $ticketGroup->first();
            $priorityName = $firstTicket && $firstTicket->priority ? $firstTicket->priority->name : 'Unknown';
            return [
                'id' => $priorityIdKey,
                'name' => $priorityName,
                'color' => $priorityColors[$priorityName] ?? '#6B7280',
                'count' => $ticketGroup->count(),
                'tickets' => $ticketGroup->map(function ($ticket) {
                    return [
                        'id' => $ticket->id,
                        'ticket_id' => $ticket->ticket_id,
                        'title' => $ticket->title,
                        'company_name' => $ticket->company_name,
                        'status' => $ticket->status,
                        'created_date' => $ticket->created_date ? $ticket->created_date->format('Y-m-d') : null,
                    ];
                })->values()->toArray(),
            ];
        })->sortByDesc('count')->values()->toArray();

        // Set focus priority for auto-scroll/expand
        $this->focusPriorityId = $priorityId;

        $this->slideOverTitle = ($module ? $module->name : 'Module');
        $this->showSlideOver = true;
    }

    public function openPriorityBarSlideOver($priorityId, $moduleId = null)
    {
        $query = $this->getBaseQuery();
        $priority = TicketPriority::find($priorityId);

        // Build query for this priority
        $ticketQuery = (clone $query)->where('priority_id', $priorityId);

        // If module specified, filter by module too
        if ($moduleId) {
            $ticketQuery->where('module_id', $moduleId);
        }

        // Get tickets with module relationship
        $tickets = $ticketQuery
            ->with('module:id,name')
            ->select('id', 'ticket_id', 'title', 'company_name', 'status', 'created_date', 'module_id')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        // Store minimal ticket data for flat list fallback
        $this->ticketList = $tickets->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'ticket_id' => $ticket->ticket_id,
                'title' => $ticket->title,
                'company_name' => $ticket->company_name,
                'status' => $ticket->status,
                'created_date' => $ticket->created_date ? $ticket->created_date->format('Y-m-d') : null,
            ];
        })->toArray();

        // Module colors mapping - must match pie chart
        $moduleColors = [
            '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
            '#EC4899', '#6366F1', '#14B8A6', '#F97316', '#06B6D4'
        ];

        // Build module color map from moduleData (pie chart order) for consistency
        $moduleColorMap = [];
        foreach ($this->moduleData as $index => $mod) {
            $moduleColorMap[$mod['id']] = $moduleColors[$index % count($moduleColors)];
        }

        // Group tickets by module
        $grouped = $tickets->groupBy(function ($ticket) {
            return $ticket->module ? $ticket->module->id : 0;
        });

        $this->ticketsByPriority = $grouped->map(function ($ticketGroup, $moduleIdKey) use ($moduleColorMap) {
            $firstTicket = $ticketGroup->first();
            $moduleName = $firstTicket && $firstTicket->module ? $firstTicket->module->name : 'Unknown';
            // Use color from pie chart mapping, fallback to gray
            $color = $moduleColorMap[$moduleIdKey] ?? '#6B7280';
            return [
                'id' => $moduleIdKey,
                'name' => $moduleName,
                'color' => $color,
                'count' => $ticketGroup->count(),
                'tickets' => $ticketGroup->map(function ($ticket) {
                    return [
                        'id' => $ticket->id,
                        'ticket_id' => $ticket->ticket_id,
                        'title' => $ticket->title,
                        'company_name' => $ticket->company_name,
                        'status' => $ticket->status,
                        'created_date' => $ticket->created_date ? $ticket->created_date->format('Y-m-d') : null,
                    ];
                })->values()->toArray(),
            ];
        })->sortByDesc('count')->values()->toArray();

        // Set focus for auto-scroll/expand
        $this->focusPriorityId = $moduleId;

        $this->slideOverTitle = ($priority ? $priority->name : 'Priority');
        $this->showSlideOver = true;
    }

    public function openStatusSlideOver($status)
    {
        $query = $this->getBaseQuery();

        if ($status === 'open') {
            $tickets = (clone $query)
                ->whereNotIn('status', ['Closed', 'Resolved'])
                ->select('id', 'ticket_id', 'title', 'company_name', 'status', 'created_date')
                ->orderByDesc('created_date')
                ->limit(100)
                ->get();
            $this->slideOverTitle = 'Open Tickets';
        } else {
            $tickets = (clone $query)
                ->whereIn('status', ['Closed', 'Resolved'])
                ->select('id', 'ticket_id', 'title', 'company_name', 'status', 'created_date')
                ->orderByDesc('created_date')
                ->limit(100)
                ->get();
            $this->slideOverTitle = 'Completed Tickets';
        }

        $this->ticketList = $tickets->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'ticket_id' => $ticket->ticket_id,
                'title' => $ticket->title,
                'company_name' => $ticket->company_name,
                'status' => $ticket->status,
                'created_date' => $ticket->created_date ? $ticket->created_date->format('Y-m-d') : null,
            ];
        })->toArray();

        $this->showSlideOver = true;
    }

    public function closeSlideOver()
    {
        $this->showSlideOver = false;
        $this->ticketList = [];
        $this->ticketsByPriority = [];
        $this->focusPriorityId = null;
    }

    public function exportToExcel()
    {
        if (empty($this->ticketsByPriority)) {
            return;
        }

        $filename = str_replace(' ', '_', $this->slideOverTitle) . '_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(
            new TicketAnalysisExport($this->ticketsByPriority, $this->slideOverTitle),
            $filename
        );
    }
}
