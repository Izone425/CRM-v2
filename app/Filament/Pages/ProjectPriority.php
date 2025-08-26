<?php
namespace App\Filament\Pages;

use App\Models\SoftwareHandover;
use App\Models\User;
use Filament\Pages\Page;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;

class ProjectPriority extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string $view = 'filament.pages.project-priority';
    protected static ?string $navigationLabel = 'Project Priority';
    protected static ?string $navigationGroup = 'Implementation';
    protected static ?string $title = '';
    protected static ?int $navigationSort = 5;

    public $selectedImplementer;
    public $lastRefreshTime;
    public $showSlideOver = false;
    public $slideOverTitle = '';
    public $implementerProjects = [];
    public $expandedImplementers = [];
    public $currentPriority = '';
    public $implementerCompanies = [];

    public $totals = [
        'high' => 0,
        'medium' => 0,
        'low' => 0,
    ];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.pages.project-priority');
    }

    public function mount()
    {
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
        $this->selectedImplementer = session('selectedImplementer', null);

        // Store in session
        session([
            'selectedImplementer' => $this->selectedImplementer,
        ]);

        $this->calculateProjectTotals();
    }

    /**
     * Handle change in selected implementer
     */
    public function updatedSelectedImplementer($implementer)
    {
        $this->selectedImplementer = $implementer;
        session(['selectedImplementer' => $implementer]);
        $this->calculateProjectTotals();
        $this->dispatch('updateProjectTable', $this->selectedImplementer);
    }

    /**
     * Calculate project totals for each priority level
     */
    public function calculateProjectTotals()
    {
        $query = SoftwareHandover::query()
            ->where('status_handover', '!=', 'Closed')
            ->where('status_handover', '!=', 'InActive');

        if ($this->selectedImplementer) {
            $query->where('implementer', $this->selectedImplementer);
        }

        $totals = $query->selectRaw("
            COUNT(CASE WHEN project_priority = 'High' THEN 1 END) as high_count,
            COUNT(CASE WHEN project_priority = 'Medium' THEN 1 END) as medium_count,
            COUNT(CASE WHEN project_priority = 'Low' THEN 1 END) as low_count
        ")->first();

        $this->totals = [
            'high' => $totals->high_count ?? 0,
            'medium' => $totals->medium_count ?? 0,
            'low' => $totals->low_count ?? 0,
        ];
    }

    /**
     * Open slide-over with implementer project counts by priority
     */
    public function openPriorityBreakdownSlideOver($priority)
    {
        $this->currentPriority = $priority;
        $this->expandedImplementers = [];
        $this->implementerCompanies = [];

        $query = SoftwareHandover::query()
            ->where('status_handover', '!=', 'Closed')
            ->where('status_handover', '!=', 'InActive')
            ->where('project_priority', $priority);

        // Get implementer breakdown for this priority
        $implementerBreakdown = $query->select('implementer', DB::raw('COUNT(*) as total'))
            ->groupBy('implementer')
            ->orderBy('total', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'implementer' => $item->implementer,
                    'count' => $item->total,
                ];
            })
            ->toArray();

        $this->implementerProjects = $implementerBreakdown;
        $this->slideOverTitle = "{$priority} Priority Projects";
        $this->showSlideOver = true;
    }

    /**
     * Toggle expanded state for an implementer and load company names
     */
    public function toggleImplementer($implementer)
    {
        // If already expanded, collapse it
        if (in_array($implementer, $this->expandedImplementers)) {
            $this->expandedImplementers = array_diff($this->expandedImplementers, [$implementer]);
            return;
        }

        // Otherwise expand it and load the companies
        $this->expandedImplementers[] = $implementer;

        // Fetch companies for this implementer with the current priority
        $companies = SoftwareHandover::where('implementer', $implementer)
            ->where('project_priority', $this->currentPriority)
            ->where('status_handover', '!=', 'Closed')
            ->where('status_handover', '!=', 'InActive')
            ->orderBy('company_name')
            ->pluck('company_name')
            ->toArray();

        $this->implementerCompanies[$implementer] = $companies;
    }

    #[On('refresh-project-counts')]
    public function refreshCounts()
    {
        $this->calculateProjectTotals();
    }
}
