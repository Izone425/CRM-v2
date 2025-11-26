<?php

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

    public string $activeView = 'tier1'; // tier1 or tier2
    public ?string $selectedImplementer = null;
    public ?int $selectedSwId = null;
    public array $filters = [
        'status' => 'all',
    ];

    public string $sortBy = 'percentage';
    public string $sortDirection = 'desc';

    public function updatedSelectedSwId()
    {
        $this->dispatch('init-tooltips');
    }

    /**
     * Get Tier 1 Summary Data (Implementer Overview)
     */
    #[Computed]
    public function getTier1Data(): array
    {
        $implementers = User::whereIn('role_id', [4, 5])
            ->orderBy('name')
            ->get();

        $data = [];

        foreach ($implementers as $implementer) {
            // ✅ Get all projects for this implementer with Open or Delay status (with or without project plans)
            $projects = SoftwareHandover::where('implementer', $implementer->name)
                ->whereIn('status_handover', ['Open', 'Delay'])
                ->with(['lead.companyDetail'])
                ->get();

            if ($projects->isEmpty()) {
                continue;
            }

            // Count based on status_handover
            $openCount = $projects->where('status_handover', 'Open')->count();
            $delayCount = $projects->where('status_handover', 'Delay')->count();
            $totalProjects = $projects->count();

            // ✅ Calculate overall progress based on TOTAL TASKS across all projects
            $totalTasksAll = 0;
            $completedTasksAll = 0;

            foreach ($projects as $project) {
                $lead = $project->lead;
                if (!$lead) {
                    continue;
                }

                $selectedModules = $project->getSelectedModules();
                $allModules = array_unique(array_merge(['phase 1', 'phase 2'], $selectedModules));

                foreach ($allModules as $module) {
                    $modulePlans = ProjectPlan::where('lead_id', $lead->id)
                        ->where('sw_id', $project->id)
                        ->whereHas('projectTask', function ($query) use ($module) {
                            $query->where('module', $module)
                                ->where('is_active', true);
                        })
                        ->get();

                    if ($modulePlans->isNotEmpty()) {
                        $totalTasksAll += $modulePlans->count();
                        $completedTasksAll += $modulePlans->where('status', 'completed')->count();
                    }
                }
            }

            // ✅ Calculate percentage: (completed tasks / total tasks) * 100
            $averagePercentage = $totalTasksAll > 0 ? round(($completedTasksAll / $totalTasksAll) * 100, 0) : 0;

            $data[] = [
                'implementer_name' => $implementer->name,
                'open_count' => $openCount,
                'delay_count' => $delayCount,
                'total_projects' => $totalProjects,
                'total_progress' => $completedTasksAll, // ✅ Completed tasks count
                'total_tasks' => $totalTasksAll, // ✅ Total tasks count
                'average_percentage' => $averagePercentage,
                'projects' => $projects,
            ];
        }

        // Sort by average percentage descending
        usort($data, function($a, $b) {
            return $b['average_percentage'] <=> $a['average_percentage'];
        });

        return $data;
    }

    /**
     * Calculate project progress based on completed tasks (like CustomerProjectPlan)
     */
    private function calculateProjectProgress(SoftwareHandover $softwareHandover): int
    {
        $lead = $softwareHandover->lead;
        if (!$lead) {
            return 0;
        }

        $selectedModules = $softwareHandover->getSelectedModules();

        // ✅ Include generic phases
        $allModules = array_unique(array_merge(['phase 1', 'phase 2'], $selectedModules));

        $totalTasks = 0;
        $completedTasks = 0;

        foreach ($allModules as $module) {
            $modulePlans = ProjectPlan::where('lead_id', $lead->id)
                ->where('sw_id', $softwareHandover->id)
                ->whereHas('projectTask', function ($query) use ($module) {
                    $query->where('module', $module)
                        ->where('is_active', true);
                })
                ->get();

            if ($modulePlans->isNotEmpty()) {
                $totalTasks += $modulePlans->count();
                $completedTasks += $modulePlans->where('status', 'completed')->count();
            }
        }

        return $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
    }

    /**
     * Get Tier 2 Detailed Data (Company List)
     */
    #[Computed]
    public function getTier2Data(): array
    {
        if (!$this->selectedImplementer) {
            return [];
        }

        // ✅ Filter by implementer and status_handover (with or without project plans)
        $softwareHandovers = SoftwareHandover::where('implementer', $this->selectedImplementer)
            ->whereIn('status_handover', ['Open', 'Delay'])
            ->with(['lead.companyDetail'])
            ->get();

        $data = [];

        foreach ($softwareHandovers as $sw) {
            $lead = $sw->lead;
            if (!$lead) {
                continue;
            }

            $companyName = $lead->companyDetail->company_name ?? 'Unknown Company';

            // ✅ Calculate project progress based on completed tasks
            $projectProgress = $this->calculateProjectProgress($sw);

            $data[] = [
                'lead_id' => $lead->id,
                'sw_id' => $sw->id,
                'company_name' => $companyName,
                'project_code' => $sw->project_code,
                'status' => $sw->status_handover,
                'project_progress' => $projectProgress,
                'headcount' => $sw->headcount ?? 0, // ✅ Add headcount
            ];
        }

        // ✅ Sort the data based on sortBy and sortDirection
        usort($data, function($a, $b) {
            $direction = $this->sortDirection === 'asc' ? 1 : -1;

            switch ($this->sortBy) {
                case 'percentage':
                    return ($b['project_progress'] <=> $a['project_progress']) * $direction;

                case 'headcount':
                    return ($b['headcount'] <=> $a['headcount']) * $direction;

                case 'status':
                    // Open first, then Delay
                    $statusOrder = ['Open' => 1, 'Delay' => 2];
                    $aStatus = $statusOrder[$a['status']] ?? 3;
                    $bStatus = $statusOrder[$b['status']] ?? 3;
                    return ($aStatus <=> $bStatus) * $direction;

                default:
                    return 0;
            }
        });

        return $data;
    }

    public function sortCompanies(string $sortBy): void
    {
        if ($this->sortBy === $sortBy) {
            // Toggle direction if same column
            $this->sortDirection = $this->sortDirection === 'desc' ? 'asc' : 'desc';
        } else {
            // Set new sort column with default direction
            $this->sortBy = $sortBy;
            $this->sortDirection = 'desc';
        }
    }

    /**
     * Get Project Plan Data for a specific software handover (Tier 3)
     */
    #[Computed]
    public function getProjectPlanData(): ?array
    {
        if (!$this->selectedSwId) {
            return null;
        }

        // Find software handover by ID
        $softwareHandover = SoftwareHandover::find($this->selectedSwId);

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

        $selectedModules = $softwareHandover->getSelectedModules();

        // ✅ Include generic phases along with selected modules
        $allModules = array_unique(array_merge(['phase 1', 'phase 2'], $selectedModules));

        $totalTasksAll = 0;
        $completedTasksAll = 0;

        // ✅ Get all module plans grouped by module_name
        foreach ($allModules as $module) {
            $modulePlans = ProjectPlan::where('lead_id', $lead->id)
                ->where('sw_id', $softwareHandover->id)
                ->whereHas('projectTask', function ($query) use ($module) {
                    $query->where('module', $module)
                        ->where('is_active', true);
                })
                ->with('projectTask')
                ->get();

            if ($modulePlans->isEmpty()) {
                continue;
            }

            // ✅ Group by module_name (e.g., "Phase 1", "Attendance Phase 1", "Attendance Phase 2")
            $groupedByModuleName = $modulePlans->groupBy(function ($plan) {
                return $plan->projectTask->module_name ?? 'Unknown';
            });

            foreach ($groupedByModuleName as $moduleName => $plans) {
                $totalTasks = $plans->count();
                $completedTasks = $plans->where('status', 'completed')->count();
                $overallProgress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

                $totalTasksAll += $totalTasks;
                $completedTasksAll += $completedTasks;

                $sortedPlans = $plans->sortBy(function($plan) {
                    return $plan->projectTask->order ?? 0;
                });

                $tasksArray = $sortedPlans->map(function ($plan) {
                    return [
                        'id' => $plan->id,
                        'phase_name' => $plan->projectTask->phase_name ?? 'N/A',
                        'task_name' => $plan->projectTask->task_name ?? 'N/A',
                        'order' => $plan->projectTask->order ?? 0,
                        'module' => $plan->projectTask->module ?? '',
                        'module_name' => $plan->projectTask->module_name ?? 'N/A',
                        'percentage' => $plan->projectTask->task_percentage ?? 0,
                        'status' => $plan->status ?? 'pending',
                        'plan_start_date' => $plan->plan_start_date,
                        'plan_end_date' => $plan->plan_end_date,
                        'actual_start_date' => $plan->actual_start_date,
                        'actual_end_date' => $plan->actual_end_date,
                    ];
                })->values()->toArray();

                $moduleOrder = $plans->first()->projectTask->module_order ?? 999;

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

                // ✅ Track unique module_names for selectedModules
                if (!in_array($moduleName, $progressData['selectedModules'])) {
                    $progressData['selectedModules'][] = $moduleName;
                }
            }
        }

        // ✅ Sort by module_order
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

    /**
     * Switch between views
     */
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

    /**
     * Select an implementer to view Tier 2 details
     */
    public function selectImplementer(string $implementerName): void
    {
        $this->selectedImplementer = $implementerName;
        $this->selectedSwId = null;
        $this->activeView = 'tier2';
    }

    /**
     * Select a software handover to view project plan (Tier 3)
     */
    public function selectCompany(int $swId): void
    {
        if ($this->selectedSwId === $swId) {
            // Toggle off if clicking the same software handover
            $this->selectedSwId = null;
        } else {
            $this->selectedSwId = $swId;
        }
    }

    /**
     * Get implementer's project statistics for header
     */
    #[Computed]
    public function getImplementerStats(): ?array
    {
        if (!$this->selectedImplementer) {
            return null;
        }

        // ✅ Filter by implementer and status_handover (with or without project plans)
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
