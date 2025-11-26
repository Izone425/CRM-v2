<?php
// filepath: /var/www/html/timetec-crm/app/Filament/Pages/ProjectPlanSummary.php

namespace App\Filament\Pages;

use App\Models\SoftwareHandover;
use App\Models\User;
use App\Models\Lead;
use App\Models\ProjectPlan;
use App\Models\ProjectTask;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

class ProjectPlanSummary extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Project Plan Summary';
    protected static ?string $title = '';
    protected static string $view = 'filament.pages.project-plan-summary';
    protected static ?int $navigationSort = 50;

    public string $activeView = 'tier1';
    public ?string $selectedImplementer = null;
    public ?int $selectedSwId = null;
    public array $filters = [
        'status' => 'all',
    ];

    // ✅ Multi-sort configuration
    public array $sortRules = [
        ['field' => 'percentage', 'direction' => 'desc']
    ];

    public function updatedSelectedSwId()
    {
        $this->dispatch('init-tooltips');
    }

    /**
     * ✅ OPTIMIZED: Get Tier 1 Summary Data (Implementer Overview)
     */
    #[Computed]
    public function getTier1Data(): array
    {
        // Get all implementers
        $implementers = User::whereIn('role_id', [4, 5])
            ->orderBy('name')
            ->get();

        // ✅ Get all projects for all implementers in one query
        $allProjects = SoftwareHandover::whereIn('implementer', $implementers->pluck('name'))
            ->whereIn('status_handover', ['Open', 'Delay'])
            ->with(['lead.companyDetail'])
            ->get()
            ->groupBy('implementer');

        // ✅ Get all SW IDs for progress calculation
        $allSwIds = $allProjects->flatten()->pluck('id')->toArray();
        $allLeadIds = $allProjects->flatten()->pluck('lead_id')->filter()->toArray();

        // ✅ Get all progress data in ONE query
        $progressBySwId = [];
        if (!empty($allSwIds) && !empty($allLeadIds)) {
            $progressData = DB::table('project_plans as pp')
                ->join('project_tasks as pt', 'pp.project_task_id', '=', 'pt.id')
                ->whereIn('pp.lead_id', $allLeadIds)
                ->whereIn('pp.sw_id', $allSwIds)
                ->where('pt.is_active', true)
                ->select(
                    'pp.sw_id',
                    DB::raw('COUNT(*) as total_tasks'),
                    DB::raw('SUM(CASE WHEN pp.status = "completed" THEN 1 ELSE 0 END) as completed_tasks')
                )
                ->groupBy('pp.sw_id')
                ->get()
                ->keyBy('sw_id');

            $progressBySwId = $progressData;
        }

        $data = [];

        foreach ($implementers as $implementer) {
            $projects = $allProjects->get($implementer->name) ?? collect();

            if ($projects->isEmpty()) {
                continue;
            }

            $openCount = $projects->where('status_handover', 'Open')->count();
            $delayCount = $projects->where('status_handover', 'Delay')->count();
            $totalProjects = $projects->count();

            // ✅ Calculate totals from cached progress data
            $totalTasksAll = 0;
            $completedTasksAll = 0;

            foreach ($projects as $project) {
                $progress = $progressBySwId->get($project->id);
                if ($progress) {
                    $totalTasksAll += $progress->total_tasks;
                    $completedTasksAll += $progress->completed_tasks;
                }
            }

            $averagePercentage = $totalTasksAll > 0 ? round(($completedTasksAll / $totalTasksAll) * 100, 0) : 0;

            $data[] = [
                'implementer_name' => $implementer->name,
                'open_count' => $openCount,
                'delay_count' => $delayCount,
                'total_projects' => $totalProjects,
                'total_progress' => $completedTasksAll,
                'total_tasks' => $totalTasksAll,
                'average_percentage' => $averagePercentage,
                'projects' => $projects,
            ];
        }

        usort($data, function($a, $b) {
            return $b['average_percentage'] <=> $a['average_percentage'];
        });

        return $data;
    }

    /**
     * ✅ OPTIMIZED: Calculate project progress with single query
     */
    private function calculateProjectProgress(SoftwareHandover $softwareHandover): int
    {
        $lead = $softwareHandover->lead;
        if (!$lead) {
            return 0;
        }

        // ✅ Single aggregation query
        $progressData = DB::table('project_plans as pp')
            ->join('project_tasks as pt', 'pp.project_task_id', '=', 'pt.id')
            ->where('pp.lead_id', $lead->id)
            ->where('pp.sw_id', $softwareHandover->id)
            ->where('pt.is_active', true)
            ->select(
                DB::raw('COUNT(*) as total_tasks'),
                DB::raw('SUM(CASE WHEN pp.status = "completed" THEN 1 ELSE 0 END) as completed_tasks')
            )
            ->first();

        $totalTasks = $progressData->total_tasks ?? 0;
        $completedTasks = $progressData->completed_tasks ?? 0;

        return $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
    }

    /**
     * ✅ OPTIMIZED: Get Tier 2 Detailed Data (Company List) with Multi-Sort
     */
    #[Computed]
    public function getTier2Data(): array
    {
        if (!$this->selectedImplementer) {
            return [];
        }

        // ✅ Get software handovers with eager loading
        $softwareHandovers = SoftwareHandover::where('implementer', $this->selectedImplementer)
            ->whereIn('status_handover', ['Open', 'Delay'])
            ->with(['lead.companyDetail'])
            ->get();

        if ($softwareHandovers->isEmpty()) {
            return [];
        }

        $swIds = $softwareHandovers->pluck('id')->toArray();
        $leadIds = $softwareHandovers->pluck('lead_id')->filter()->toArray();

        // ✅ Get all progress data in ONE query
        $progressData = collect();
        if (!empty($swIds) && !empty($leadIds)) {
            $progressData = DB::table('project_plans as pp')
                ->join('project_tasks as pt', 'pp.project_task_id', '=', 'pt.id')
                ->whereIn('pp.lead_id', $leadIds)
                ->whereIn('pp.sw_id', $swIds)
                ->where('pt.is_active', true)
                ->select(
                    'pp.sw_id',
                    DB::raw('COUNT(*) as total_tasks'),
                    DB::raw('SUM(CASE WHEN pp.status = "completed" THEN 1 ELSE 0 END) as completed_tasks')
                )
                ->groupBy('pp.sw_id')
                ->get()
                ->keyBy('sw_id');
        }

        $data = [];

        foreach ($softwareHandovers as $sw) {
            $lead = $sw->lead;
            if (!$lead) {
                continue;
            }

            $companyName = $lead->companyDetail->company_name ?? 'Unknown Company';

            // ✅ Get progress from cached query result
            $progress = $progressData->get($sw->id);
            $totalTasks = $progress->total_tasks ?? 0;
            $completedTasks = $progress->completed_tasks ?? 0;
            $projectProgress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

            $data[] = [
                'lead_id' => $lead->id,
                'sw_id' => $sw->id,
                'company_name' => $companyName,
                'project_code' => $sw->project_code,
                'status' => $sw->status_handover,
                'project_progress' => $projectProgress,
                'headcount' => $sw->headcount ?? 0,
            ];
        }

        // ✅ Apply multi-level sorting
        usort($data, function($a, $b) {
            foreach ($this->sortRules as $rule) {
                $result = $this->compareValues($a, $b, $rule['field'], $rule['direction']);
                if ($result !== 0) {
                    return $result;
                }
            }
            return 0;
        });

        return $data;
    }

    /**
     * ✅ Compare two values for sorting
     */
    private function compareValues($a, $b, string $field, string $direction): int
    {
        $multiplier = $direction === 'desc' ? 1 : -1;

        switch ($field) {
            case 'percentage':
                return ($b['project_progress'] <=> $a['project_progress']) * $multiplier;

            case 'headcount':
                return ($b['headcount'] <=> $a['headcount']) * $multiplier;

            case 'status':
                $statusOrder = ['Open' => 1, 'Delay' => 2];
                $aStatus = $statusOrder[$a['status']] ?? 3;
                $bStatus = $statusOrder[$b['status']] ?? 3;
                return ($aStatus <=> $bStatus) * $multiplier;

            case 'company_name':
                return strcmp($a['company_name'], $b['company_name']) * ($multiplier * -1);

            default:
                return 0;
        }
    }

    /**
     * ✅ Toggle sort for a field
     */
    public function toggleSort(string $field): void
    {
        $existingIndex = null;
        foreach ($this->sortRules as $index => $rule) {
            if ($rule['field'] === $field) {
                $existingIndex = $index;
                break;
            }
        }

        if ($existingIndex !== null) {
            $currentDirection = $this->sortRules[$existingIndex]['direction'];
            $this->sortRules[$existingIndex]['direction'] = $currentDirection === 'desc' ? 'asc' : 'desc';
        } else {
            $this->sortRules[] = ['field' => $field, 'direction' => 'desc'];
        }

        $this->dispatch('$refresh');
    }

    /**
     * ✅ Remove a sort rule
     */
    public function removeSort(int $index): void
    {
        if (count($this->sortRules) > 1) {
            array_splice($this->sortRules, $index, 1);
            $this->dispatch('$refresh');
        }
    }

    /**
     * ✅ Move sort rule up in priority
     */
    public function moveSortUp(int $index): void
    {
        if ($index > 0) {
            $temp = $this->sortRules[$index];
            $this->sortRules[$index] = $this->sortRules[$index - 1];
            $this->sortRules[$index - 1] = $temp;
            $this->dispatch('$refresh');
        }
    }

    /**
     * ✅ Move sort rule down in priority
     */
    public function moveSortDown(int $index): void
    {
        if ($index < count($this->sortRules) - 1) {
            $temp = $this->sortRules[$index];
            $this->sortRules[$index] = $this->sortRules[$index + 1];
            $this->sortRules[$index + 1] = $temp;
            $this->dispatch('$refresh');
        }
    }

    /**
     * ✅ Clear all sorts
     */
    public function clearAllSorts(): void
    {
        $this->sortRules = [['field' => 'percentage', 'direction' => 'desc']];
        $this->dispatch('$refresh');
    }

    /**
     * ✅ OPTIMIZED: Get Project Plan Data for a specific software handover (Tier 3)
     */
    #[Computed]
    public function getProjectPlanData(): ?array
    {
        if (!$this->selectedSwId) {
            return null;
        }

        $softwareHandover = SoftwareHandover::with('lead.companyDetail')->find($this->selectedSwId);

        if (!$softwareHandover) {
            return null;
        }

        $lead = $softwareHandover->lead;
        if (!$lead) {
            return null;
        }

        $progressData = [
            'leadId' => $lead->id,
            'swId' => $softwareHandover->id,
            'companyName' => $lead->companyDetail->company_name ?? 'Unknown',
            'selectedModules' => [],
            'progressOverview' => [],
            'overallSummary' => [
                'totalTasks' => 0,
                'completedTasks' => 0,
                'overallProgress' => 0,
                'modules' => []
            ],
        ];

        // ✅ Get all project plans with tasks in ONE query
        $plans = ProjectPlan::where('lead_id', $lead->id)
            ->where('sw_id', $softwareHandover->id)
            ->with(['projectTask' => function($query) {
                $query->where('is_active', true);
            }])
            ->whereHas('projectTask', function ($query) {
                $query->where('is_active', true);
            })
            ->get();

        if ($plans->isEmpty()) {
            return $progressData;
        }

        $totalTasksAll = 0;
        $completedTasksAll = 0;

        // Group by module_name
        $groupedByModuleName = $plans->groupBy(function ($plan) {
            return $plan->projectTask->module_name ?? 'Unknown';
        });

        foreach ($groupedByModuleName as $moduleName => $modulePlans) {
            $totalTasks = $modulePlans->count();
            $completedTasks = $modulePlans->where('status', 'completed')->count();
            $overallProgress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

            $totalTasksAll += $totalTasks;
            $completedTasksAll += $completedTasks;

            $sortedPlans = $modulePlans->sortBy(function($plan) {
                return $plan->projectTask->order ?? 0;
            });

            $tasksArray = $sortedPlans->map(function ($plan) {
                $task = $plan->projectTask;
                return [
                    'id' => $plan->id,
                    'phase_name' => $task->phase_name ?? 'N/A',
                    'task_name' => $task->task_name ?? 'N/A',
                    'order' => $task->order ?? 0,
                    'module' => $task->module ?? '',
                    'module_name' => $task->module_name ?? 'N/A',
                    'percentage' => $task->task_percentage ?? 0,
                    'status' => $plan->status ?? 'pending',
                    'plan_start_date' => $plan->plan_start_date,
                    'plan_end_date' => $plan->plan_end_date,
                    'actual_start_date' => $plan->actual_start_date,
                    'actual_end_date' => $plan->actual_end_date,
                ];
            })->values()->toArray();

            $firstTask = $sortedPlans->first()->projectTask;
            $moduleOrder = $firstTask->module_order ?? 999;
            $module = $firstTask->module ?? '';

            $progressData['progressOverview'][$moduleName] = [
                'tasks' => $tasksArray,
                'totalTasks' => $totalTasks,
                'completedTasks' => $completedTasks,
                'overallProgress' => $overallProgress,
                'module_order' => $moduleOrder,
                'module_name' => $moduleName,
                'module' => $module
            ];

            $progressData['overallSummary']['modules'][] = [
                'module' => $module,
                'module_name' => $moduleName,
                'module_order' => $moduleOrder,
                'progress' => $overallProgress,
                'completed' => $completedTasks,
                'total' => $totalTasks
            ];

            if (!in_array($moduleName, $progressData['selectedModules'])) {
                $progressData['selectedModules'][] = $moduleName;
            }
        }

        usort($progressData['selectedModules'], function($a, $b) use ($progressData) {
            $orderA = $progressData['progressOverview'][$a]['module_order'] ?? 999;
            $orderB = $progressData['progressOverview'][$b]['module_order'] ?? 999;
            return $orderA - $orderB;
        });

        usort($progressData['overallSummary']['modules'], function($a, $b) {
            return $a['module_order'] - $b['module_order'];
        });

        $progressData['overallSummary']['totalTasks'] = $totalTasksAll;
        $progressData['overallSummary']['completedTasks'] = $completedTasksAll;
        $progressData['overallSummary']['overallProgress'] = $totalTasksAll > 0
            ? round(($completedTasksAll / $totalTasksAll) * 100)
            : 0;

        return $progressData;
    }

    public function switchView(string $view): void
    {
        $this->activeView = $view;

        if ($view === 'tier1') {
            $this->selectedImplementer = null;
            $this->selectedSwId = null;
        } elseif ($view === 'tier2') {
            $this->selectedSwId = null;
        }
    }

    public function selectImplementer(string $implementerName): void
    {
        $this->selectedImplementer = $implementerName;
        $this->selectedSwId = null;
        $this->activeView = 'tier2';
    }

    public function selectCompany(int $swId): void
    {
        if ($this->selectedSwId === $swId) {
            $this->selectedSwId = null;
        } else {
            $this->selectedSwId = $swId;
        }
    }

    #[Computed]
    public function getImplementerStats(): ?array
    {
        if (!$this->selectedImplementer) {
            return null;
        }

        $projects = SoftwareHandover::where('implementer', $this->selectedImplementer)
            ->whereIn('status_handover', ['Open', 'Delay'])
            ->get();

        return [
            'name' => $this->selectedImplementer,
            'open_count' => $projects->where('status_handover', 'Open')->count(),
            'delay_count' => $projects->where('status_handover', 'Delay')->count(),
            'total_projects' => $projects->count(),
        ];
    }
}
