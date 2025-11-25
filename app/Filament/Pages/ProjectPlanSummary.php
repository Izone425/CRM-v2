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
            // Get all projects for this implementer with Open or Delay status
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

            // ✅ Calculate overall progress based on completed tasks (like CustomerProjectPlan)
            $totalProgressSum = 0;
            foreach ($projects as $project) {
                $projectProgress = $this->calculateProjectProgress($project);
                $totalProgressSum += $projectProgress;
            }

            $averagePercentage = $totalProjects > 0 ? round($totalProgressSum / $totalProjects, 0) : 0;

            $data[] = [
                'implementer_name' => $implementer->name,
                'open_count' => $openCount,
                'delay_count' => $delayCount,
                'total_projects' => $totalProjects,
                'total_progress' => $totalProgressSum,
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

        // Filter by implementer and status_handover
        $softwareHandovers = SoftwareHandover::where('implementer', $this->selectedImplementer)
            ->whereIn('status_handover', ['Open', 'Delay'])
            ->with(['lead.companyDetail'])
            ->get();

        $data = [];

        foreach ($softwareHandovers as $sw) {
            $lead = $sw->lead;
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
            ];
        }

        return $data;
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
                'overallProgress' => 0, // ✅ Will be calculated from tasks
                'modules' => []
            ],
        ];

        $selectedModules = $softwareHandover->getSelectedModules();
        $progressData['selectedModules'] = array_unique(array_merge(['phase 1', 'phase 2'], $selectedModules));

        usort($progressData['selectedModules'], function($a, $b) {
            return ProjectTask::getModuleOrder($a) - ProjectTask::getModuleOrder($b);
        });

        $totalTasksAll = 0;
        $completedTasksAll = 0;

        foreach ($progressData['selectedModules'] as $module) {
            $modulePlans = ProjectPlan::where('lead_id', $lead->id)
                ->where('sw_id', $softwareHandover->id)
                ->whereHas('projectTask', function ($query) use ($module) {
                    $query->where('module', $module)
                        ->where('is_active', true);
                })
                ->with('projectTask')
                ->get();

            if ($modulePlans->isNotEmpty()) {
                $totalTasks = $modulePlans->count();
                $completedTasks = $modulePlans->where('status', 'completed')->count();
                $overallProgress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

                $totalTasksAll += $totalTasks;
                $completedTasksAll += $completedTasks;

                $sortedPlans = $modulePlans->sortBy(function($plan) {
                    return $plan->projectTask->order ?? 0;
                });

                $moduleName = $modulePlans->first()->projectTask->module_name ?? ucfirst(str_replace('_', ' ', $module));

                $tasksArray = $sortedPlans->map(function ($plan) {
                    return [
                        'id' => $plan->id,
                        'phase_name' => $plan->projectTask->phase_name ?? 'N/A',
                        'task_name' => $plan->projectTask->task_name ?? 'N/A',
                        'order' => $plan->projectTask->order ?? 0,
                        'module' => $plan->projectTask->module ?? '',
                        'percentage' => $plan->projectTask->task_percentage ?? 0,
                        'status' => $plan->status ?? 'pending',
                        'plan_start_date' => $plan->plan_start_date,
                        'plan_end_date' => $plan->plan_end_date,
                        'actual_start_date' => $plan->actual_start_date,
                        'actual_end_date' => $plan->actual_end_date,
                    ];
                })->values()->toArray();

                $moduleOrder = ProjectTask::getModuleOrder($module);

                $progressData['progressOverview'][$moduleName] = [
                    'tasks' => $tasksArray,
                    'totalTasks' => $totalTasks,
                    'completedTasks' => $completedTasks,
                    'overallProgress' => $overallProgress,
                    'module_order' => $moduleOrder,
                    'module_name' => $moduleName
                ];

                $progressData['overallSummary']['modules'][] = [
                    'module' => $module,
                    'module_name' => $moduleName,
                    'module_order' => $moduleOrder,
                    'progress' => $overallProgress,
                    'completed' => $completedTasks,
                    'total' => $totalTasks
                ];
            }
        }

        usort($progressData['overallSummary']['modules'], function($a, $b) {
            return $a['module_order'] - $b['module_order'];
        });

        $progressData['overallSummary']['totalTasks'] = $totalTasksAll;
        $progressData['overallSummary']['completedTasks'] = $completedTasksAll;
        // ✅ Calculate overall progress from completed tasks (like CustomerProjectPlan)
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

        // Filter by implementer and status_handover
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
