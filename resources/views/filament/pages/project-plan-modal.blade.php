{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/project-plan-modal.blade.php --}}
@php
    // Get data from the software handover record
    $leadId = $softwareHandover->lead_id ?? null;
    $swId = $softwareHandover->id ?? null;
    $projectPlanGeneratedAt = $softwareHandover->project_plan_generated_at ?? null;

    // Use the same logic from project-progress-view.blade.php
    $selectedModules = [];
    $progressOverview = [];
    $overallSummary = [
        'totalTasks' => 0,
        'completedTasks' => 0,
        'overallProgress' => 0,
        'modules' => []
    ];

    if ($softwareHandover) {
        $selectedModules = $softwareHandover->getSelectedModules();
        $selectedModules = array_unique(array_merge(['phase 1', 'phase 2'], $selectedModules));

        usort($selectedModules, function($a, $b) {
            return \App\Models\ProjectTask::getModuleOrder($a) - \App\Models\ProjectTask::getModuleOrder($b);
        });

        $totalTasksAll = 0;
        $completedTasksAll = 0;

        foreach ($selectedModules as $module) {
            $moduleNames = \App\Models\ProjectTask::where('module', $module)
                ->where('is_active', true)
                ->select('module_name')
                ->distinct()
                ->get()
                ->pluck('module_name')
                ->toArray();

            usort($moduleNames, function($a, $b) {
                $orderA = \App\Models\ProjectTask::where('module_name', $a)->value('module_order') ?? 999;
                $orderB = \App\Models\ProjectTask::where('module_name', $b)->value('module_order') ?? 999;
                return $orderA - $orderB;
            });

            foreach ($moduleNames as $moduleName) {
                $modulePlans = \App\Models\ProjectPlan::where('lead_id', $leadId)
                    ->where('sw_id', $swId)
                    ->whereHas('projectTask', function ($query) use ($moduleName) {
                        $query->where('module_name', $moduleName)
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

                    $tasksArray = $sortedPlans->map(function ($plan) {
                        return [
                            'id' => $plan->id,
                            'task_name' => $plan->projectTask->task_name ?? 'N/A',
                            'order' => $plan->projectTask->order ?? 0,
                            'module' => $plan->projectTask->module ?? '',
                            'module_name' => $plan->projectTask->module_name ?? '',
                            'percentage' => $plan->projectTask->task_percentage ?? 0,
                            'status' => $plan->status ?? 'pending',
                            'plan_start_date' => $plan->plan_start_date,
                            'plan_end_date' => $plan->plan_end_date,
                            'actual_start_date' => $plan->actual_start_date,
                            'actual_end_date' => $plan->actual_end_date,
                            'remarks' => $plan->remarks,
                        ];
                    })->values()->toArray();

                    $moduleOrder = \App\Models\ProjectTask::where('module_name', $moduleName)
                        ->value('module_order') ?? 999;

                    $progressOverview[$moduleName] = [
                        'tasks' => $tasksArray,
                        'totalTasks' => $totalTasks,
                        'completedTasks' => $completedTasks,
                        'overallProgress' => $overallProgress,
                        'module_order' => $moduleOrder,
                        'module_name' => $moduleName
                    ];

                    $overallSummary['modules'][] = [
                        'module' => $module,
                        'module_name' => $moduleName,
                        'module_order' => $moduleOrder,
                        'progress' => $overallProgress,
                        'completed' => $completedTasks,
                        'total' => $totalTasks
                    ];
                }
            }
        }

        usort($overallSummary['modules'], function($a, $b) {
            return $a['module_order'] - $b['module_order'];
        });

        $overallSummary['totalTasks'] = $totalTasksAll;
        $overallSummary['completedTasks'] = $completedTasksAll;
        $overallSummary['overallProgress'] = $totalTasksAll > 0 ? round(($completedTasksAll / $totalTasksAll) * 100) : 0;
    }
@endphp

{{-- Copy all styles from project-progress-view.blade.php --}}
<style>
    .project-progress-container {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .overall-progress-card {
        padding: 16px;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .overall-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 8px;
        border-bottom: 2px solid #3b82f6;
    }

    .overall-title-section {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .overall-title {
        font-size: 16px;
        font-weight: 700;
        margin: 0;
        color: #1e40af;
    }

    .overall-stats {
        text-align: right;
    }

    .overall-percentage {
        font-size: 24px;
        font-weight: 700;
        color: #1e40af;
        line-height: 1;
    }

    .overall-label {
        font-size: 13px;
        color: #6b7280;
        margin: 4px 0;
    }

    .overall-meta {
        font-size: 11px;
        color: #9ca3af;
    }

    .progress-timeline {
        position: relative;
        overflow-x: auto;
        overflow-y: visible;
        padding-left: 16px;
        padding-bottom: 16px;
        padding-top: 16px;
    }

    .timeline-container {
        display: flex;
        align-items: flex-start;
        justify-content: flex-start;
        min-width: max-content;
        gap: 8px;
        padding-top: 10px;
    }

    .timeline-task {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        flex-shrink: 0;
        min-width: 0;
    }

    .timeline-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: 2px solid;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
    }

    .timeline-circle:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .timeline-circle.completed {
        background-color: #10b981;
        border-color: #10b981;
    }

    .timeline-circle.pending {
        background-color: white;
        border-color: #d1d5db;
    }

    .timeline-circle.in_progress {
        background-color: #fbbf24;
        border-color: #f59e0b;
    }

    .timeline-icon-completed {
        width: 24px;
        height: 24px;
        color: white;
        pointer-events: none;
    }

    .timeline-dot {
        width: 12px;
        height: 12px;
        background-color: #d1d5db;
        border-radius: 50%;
        pointer-events: none;
    }

    .timeline-info {
        margin-top: 12px;
        text-align: center;
        max-width: 180px;
        min-width: 120px;
    }

    .timeline-percentage {
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 4px;
    }

    .timeline-percentage.completed { color: #059669; }
    .timeline-percentage.in_progress { color: #d97706; }
    .timeline-percentage.pending { color: #6b7280; }

    .timeline-remarks {
        font-size: 10px;
        color: #9ca3af;
        font-style: italic;
        margin-top: 2px;
        margin-bottom: 6px;
        line-height: 1.3;
        max-width: 180px;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .timeline-task-name {
        font-size: 11px;
        color: #6b7280;
        white-space: normal;
        word-wrap: break-word;
        overflow-wrap: break-word;
        line-height: 1.4;
        max-width: 180px;
        margin-bottom: 4px;
    }

    .timeline-status {
        margin-top: 4px;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 8px;
        font-weight: 600;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .timeline-status.completed {
        background-color: #d1fae5;
        color: #065f46;
    }

    .timeline-status.in_progress {
        background-color: #fef3c7;
        color: #92400e;
    }

    .timeline-status.pending {
        background-color: #f3f4f6;
        color: #1f2937;
    }

    .timeline-line {
        flex: 1;
        height: 2px;
        border-top: 2px solid;
        margin-top: 18px;
        min-width: 32px;
        max-width: 30px;
        flex-shrink: 0;
    }

    .timeline-line.completed { border-color: #10b981; }
    .timeline-line.pending { border-color: #d1d5db; }

    .progress-overview-card {
        padding: 16px;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        display: none;
    }

    .progress-overview-card.show {
        display: block;
    }

    .module-header-section {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 8px;
        border-bottom: 2px solid #3b82f6;
    }

    .module-title-wrapper {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .module-title {
        font-size: 16px;
        font-weight: 700;
        color: #1e40af;
        margin: 0;
    }

    .module-stats {
        text-align: right;
    }

    .module-percentage {
        font-size: 16px;
        font-weight: 700;
        color: #1e40af;
        line-height: 1;
    }

    .module-label {
        font-size: 13px;
        color: #6b7280;
        margin: 4px 0;
    }

    .module-meta {
        font-size: 11px;
        color: #9ca3af;
    }

    .empty-state {
        padding: 48px 0;
        text-align: center;
        color: #6b7280;
    }

    .empty-icon {
        width: 48px;
        height: 48px;
        margin: 0 auto 16px;
        color: #d1d5db;
    }

    .empty-title {
        font-size: 18px;
        font-weight: 500;
        color: #111827;
        margin-bottom: 8px;
    }

    .empty-description {
        font-size: 14px;
        color: #6b7280;
    }

    #tooltip-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 9999999;
    }

    #tooltip-container .task-tooltip {
        position: fixed;
        background-color: #1f2937;
        color: white;
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 12px;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.2s ease;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
        min-width: 220px;
        pointer-events: none;
    }

    #tooltip-container .task-tooltip.show {
        opacity: 1 !important;
        visibility: visible !important;
    }

    #tooltip-container .task-tooltip::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border: 8px solid transparent;
        border-top-color: #1f2937;
    }

    .tooltip-task-name {
        font-weight: 700;
        font-size: 13px;
        margin-bottom: 6px;
        color: #93c5fd;
    }

    .tooltip-divider {
        height: 1px;
        background-color: #374151;
        margin: 8px 0;
    }

    .tooltip-status {
        font-size: 11px;
        font-weight: 600;
        margin-bottom: 4px;
    }

    .tooltip-status.in-progress {
        color: #fbbf24;
    }

    .tooltip-status.completed {
        color: #10b981;
    }

    .tooltip-status.pending {
        color: #9ca3af;
    }

    .tooltip-date-label {
        font-size: 10px;
        color: #9ca3af;
        margin-bottom: 2px;
        font-weight: 600;
    }

    .tooltip-date-value {
        font-size: 11px;
        color: #d1d5db;
        margin-bottom: 6px;
        white-space: normal;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .tooltip-progress {
        font-size: 11px;
        font-weight: 600;
        color: #93c5fd;
    }

    .tooltip-day-counter {
        font-size: 11px;
        font-weight: 700;
        padding: 4px 8px;
        border-radius: 4px;
        margin-top: 6px;
        display: inline-block;
    }

    .tooltip-day-counter.overdue {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .tooltip-day-counter.urgent {
        background-color: #fef3c7;
        color: #92400e;
    }

    .tooltip-day-counter.normal {
        background-color: #dbeafe;
        color: #1e40af;
    }

    .tooltip-day-counter.completed {
        background-color: #d1fae5;
        color: #065f46;
    }

    .timeline-circle > .task-tooltip {
        display: none !important;
    }
</style>

<script>
    function toggleModuleDetails(moduleKey) {
        const moduleCard = document.getElementById('module-' + moduleKey);
        if (moduleCard) {
            moduleCard.classList.toggle('show');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('tooltip-container');
        if (!container) return;

        document.querySelectorAll('.timeline-circle').forEach(circle => {
            const tooltipOriginal = circle.querySelector('.task-tooltip');
            if (!tooltipOriginal) return;

            const tooltipHtml = tooltipOriginal.innerHTML;

            circle.addEventListener('mouseenter', function(e) {
                const rect = this.getBoundingClientRect();

                const tooltip = document.createElement('div');
                tooltip.className = 'task-tooltip show';
                tooltip.innerHTML = tooltipHtml;

                tooltip.style.bottom = (window.innerHeight - rect.top + 12) + 'px';
                tooltip.style.left = (rect.left + rect.width / 2) + 'px';
                tooltip.style.transform = 'translateX(-50%)';

                container.appendChild(tooltip);
                this.tooltipElement = tooltip;
            });

            circle.addEventListener('mouseleave', function() {
                if (this.tooltipElement) {
                    this.tooltipElement.remove();
                    this.tooltipElement = null;
                }
            });
        });
    });
</script>

{{-- Rest of the HTML remains the same as project-progress-view.blade.php --}}
@if($projectPlanGeneratedAt)
    <div style="text-align: right; margin-bottom: 16px;">
        <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; background-color: #F3F4F6; border-radius: 6px; border-left: 3px solid #3B82F6;">
            <div style="font-size: 11px; color: #6B7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                📅 Project Plan Generated:
            </div>
            <div style="font-size: 13px; color: #1F2937; font-weight: 700;">
                {{ \Carbon\Carbon::parse($projectPlanGeneratedAt)->format('d M Y, h:i A') }}
            </div>
            <div style="font-size: 10px; color: #9CA3AF; font-weight: 500;">
                ({{ \Carbon\Carbon::parse($projectPlanGeneratedAt)->diffForHumans() }})
            </div>
        </div>
    </div>
@endif

<div class="project-progress-container">
    @if(!empty($selectedModules) && !empty($progressOverview))
        {{-- Overall Progress Card --}}
        <div class="overall-progress-card">
            <div class="overall-header">
                <div class="overall-title-section">
                    <h4 class="overall-title">Project Progress Overview</h4>
                    <div class="overall-meta">{{ $overallSummary['completedTasks'] }}/{{ $overallSummary['totalTasks'] }} tasks completed</div>
                </div>
                <div class="overall-stats">
                    <div class="overall-percentage">{{ $overallSummary['overallProgress'] }}%</div>
                    <div class="overall-label">Overall Completion</div>
                </div>
            </div>

            {{-- Modules Timeline --}}
            <div class="progress-timeline">
                <div class="timeline-container">
                    @foreach($overallSummary['modules'] as $index => $moduleSummary)
                        @php
                            $moduleProgress = $moduleSummary['progress'];
                            $moduleStatus = 'pending';
                            if ($moduleProgress == 100) {
                                $moduleStatus = 'completed';
                            } elseif ($moduleProgress > 0) {
                                $moduleStatus = 'in_progress';
                            }
                            $isCompleted = $moduleStatus === 'completed';
                            $isInProgress = $moduleStatus === 'in_progress';

                            $moduleKey = str_replace([' ', ':'], '-', $moduleSummary['module_name']);
                        @endphp

                        <div class="timeline-task">
                            <div class="timeline-circle {{ $moduleStatus }}" onclick="toggleModuleDetails('{{ $moduleKey }}')" style="width: 48px; height: 48px;">
                                <div class="task-tooltip">
                                    <div class="tooltip-task-name">{{ ucfirst($moduleSummary['module']) }}</div>
                                    <div class="tooltip-task-name">{{ $moduleSummary['module_name'] }}</div>
                                    <div class="tooltip-divider"></div>
                                    <div class="tooltip-progress">{{ $moduleSummary['completed'] }}/{{ $moduleSummary['total'] }} tasks completed</div>
                                </div>

                                @if($isCompleted)
                                    <svg class="timeline-icon-completed" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                @elseif($isInProgress)
                                    <svg class="timeline-icon-completed" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                    </svg>
                                @else
                                    <div class="timeline-dot"></div>
                                @endif
                            </div>

                            <div class="timeline-info">
                                <div class="timeline-percentage {{ $moduleStatus }}">{{ $moduleProgress }}%</div>
                                <div class="timeline-task-name">{{ $moduleSummary['module_name'] }}</div>
                                <div class="timeline-task-name">{{ $moduleSummary['completed'] }}/{{ $moduleSummary['total'] }} tasks</div>
                                <div class="timeline-status {{ $moduleStatus }}">
                                    {{ str_replace('_', ' ', $moduleStatus) }}
                                </div>
                            </div>
                        </div>

                        @if($index < count($overallSummary['modules']) - 1)
                            @php
                                $nextModule = $overallSummary['modules'][$index + 1];
                                $nextModuleStatus = $nextModule['progress'] == 100 ? 'completed' : ($nextModule['progress'] > 0 ? 'in_progress' : 'pending');
                                $lineCompleted = $isCompleted && $nextModuleStatus === 'completed';
                            @endphp
                            <div class="timeline-line {{ $lineCompleted ? 'completed' : 'pending' }}" style="margin-top: 24px; max-width: 60px;"></div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Module Details --}}
        @foreach($progressOverview as $moduleName => $moduleData)
            @php
                $moduleKey = str_replace([' ', ':'], '-', $moduleName);
                $moduleProgress = $moduleData['overallProgress'];

                $showByDefault = false;
                if ($moduleProgress > 0 && $moduleProgress < 100) {
                    $showByDefault = true;
                } elseif ($moduleProgress == 0) {
                    static $firstPendingFound = false;
                    if (!$firstPendingFound) {
                        $showByDefault = true;
                        $firstPendingFound = true;
                    }
                }
            @endphp

            <div class="progress-overview-card {{ $showByDefault ? 'show' : '' }}" id="module-{{ $moduleKey }}">
                <div class="module-header-section">
                    <div class="module-title-wrapper">
                        <h4 class="module-title">{{ $moduleName }}</h4>
                        <div class="module-meta">{{ $moduleData['completedTasks'] }}/{{ $moduleData['totalTasks'] }} tasks completed</div>
                    </div>
                    <div class="module-stats">
                        <div class="module-percentage">{{ $moduleData['overallProgress'] }}%</div>
                        <div class="module-label">Module Completion</div>
                    </div>
                </div>

                <div class="progress-timeline">
                    <div class="timeline-container">
                        @foreach($moduleData['tasks'] as $index => $task)
                            @php
                                $taskStatus = $task['status'] ?? 'pending';
                                $planStartDate = $task['plan_start_date'] ? \Carbon\Carbon::parse($task['plan_start_date']) : null;
                                $planEndDate = $task['plan_end_date'] ? \Carbon\Carbon::parse($task['plan_end_date']) : null;
                                $actualStartDate = $task['actual_start_date'] ? \Carbon\Carbon::parse($task['actual_start_date']) : null;
                                $actualEndDate = $task['actual_end_date'] ? \Carbon\Carbon::parse($task['actual_end_date']) : null;

                                $visualStatus = $taskStatus;
                                $isCompleted = false;
                                $isInProgress = false;
                                $isPending = false;

                                if ($taskStatus === 'completed' || $actualEndDate) {
                                    $visualStatus = 'completed';
                                    $isCompleted = true;
                                } elseif ($taskStatus === 'in_progress' || $actualStartDate || ($planStartDate && $planEndDate)) {
                                    $visualStatus = 'in_progress';
                                    $isInProgress = true;
                                } else {
                                    $visualStatus = 'pending';
                                    $isPending = true;
                                }

                                $tooltipStatusText = ucfirst(str_replace('_', ' ', $visualStatus));
                                $tooltipStatusClass = $visualStatus === 'completed' ? 'completed' : ($visualStatus === 'in_progress' ? 'in-progress' : 'pending');

                                $plannedPeriod = '';
                                if ($planStartDate && $planEndDate) {
                                    $plannedPeriod = $planStartDate->format('d M Y') . ' - ' . $planEndDate->format('d M Y');
                                }

                                $actualPeriod = '';
                                if ($actualStartDate && $actualEndDate) {
                                    $actualPeriod = $actualStartDate->format('d M Y') . ' - ' . $actualEndDate->format('d M Y');
                                } elseif ($actualStartDate) {
                                    $actualPeriod = $actualStartDate->format('d M Y') . ' - Now';
                                }

                                $daysLeft = null;
                                $dayCounterClass = 'normal';
                                $dayCounterText = '';

                                if ($isCompleted) {
                                    $dayCounterText = 'Completed';
                                    $dayCounterClass = 'completed';
                                } elseif ($planEndDate) {
                                    $today = \Carbon\Carbon::now();
                                    $daysLeft = $today->diffInDays($planEndDate, false);

                                    if ($daysLeft < 0) {
                                        $dayCounterText = abs($daysLeft) . ' days overdue';
                                        $dayCounterClass = 'overdue';
                                    } elseif ($daysLeft == 0) {
                                        $dayCounterText = 'Due today';
                                        $dayCounterClass = 'urgent';
                                    } elseif ($daysLeft <= 3) {
                                        $dayCounterText = $daysLeft . ' days left';
                                        $dayCounterClass = 'urgent';
                                    } else {
                                        $dayCounterText = $daysLeft . ' days left';
                                        $dayCounterClass = 'normal';
                                    }
                                }
                            @endphp

                            <div class="timeline-task">
                                <div class="timeline-circle {{ $visualStatus }}">
                                    <div class="task-tooltip">
                                        <div class="tooltip-task-name">{{ $task['task_name'] ?? 'N/A' }}</div>
                                        <div class="tooltip-status {{ $tooltipStatusClass }}">
                                            Status: {{ $tooltipStatusText }}
                                        </div>

                                        @if($plannedPeriod)
                                            <div class="tooltip-divider"></div>
                                            <div class="tooltip-date-label">📅 Planned Period:</div>
                                            <div class="tooltip-date-value">{{ $plannedPeriod }}</div>
                                        @endif

                                        @if($actualPeriod)
                                            <div class="tooltip-date-label">✅ Actual Period:</div>
                                            <div class="tooltip-date-value">{{ $actualPeriod }}</div>
                                        @endif

                                        @if($dayCounterText)
                                            <div class="tooltip-divider"></div>
                                            <div class="tooltip-day-counter {{ $dayCounterClass }}">
                                                ⏰ {{ $dayCounterText }}
                                            </div>
                                        @endif

                                        <div class="tooltip-progress">Percentage: {{ $task['percentage'] ?? 0 }}%</div>

                                        @if(!empty($task['remarks']))
                                            <div class="tooltip-divider"></div>
                                            <div class="tooltip-date-label">💬 Remarks:</div>
                                            <div class="tooltip-date-value" style="white-space: normal; max-width: 200px;">{{ $task['remarks'] }}</div>
                                        @endif
                                    </div>

                                    @if($isCompleted)
                                        <svg class="timeline-icon-completed" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    @elseif($isInProgress)
                                        <svg class="timeline-icon-completed" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                        </svg>
                                    @else
                                        <div class="timeline-dot"></div>
                                    @endif
                                </div>
                            </div>

                            @if($index < count($moduleData['tasks']) - 1)
                                @php
                                    $nextTask = $moduleData['tasks'][$index + 1];
                                    $nextTaskActualEndDate = $nextTask['actual_end_date'] ?? null;
                                    $lineCompleted = $isCompleted && $nextTaskActualEndDate !== null;
                                @endphp
                                <div class="timeline-line {{ $lineCompleted ? 'completed' : 'pending' }}"></div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    @else
        <div class="empty-state">
            <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
            </svg>
            <p class="empty-title">No project plans found</p>
            <p class="empty-description">Please create a software handover first, then click "Sync Tasks from Template"</p>
        </div>
    @endif

    <div id="tooltip-container"></div>
</div>
