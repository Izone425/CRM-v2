{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/resources/lead-resource/tabs/project-progress-view.blade.php --}}
@php
    $moduleLabels = [
        'phase_1' => 'Phase 1: Implementation',
        'phase_2' => 'Phase 2: Configuration',
        'phase_3' => 'Phase 3: Training',
        'phase_4' => 'Phase 4: Go-Live',
        'phase_5' => 'Phase 5: Support',
        'attendance' => 'Attendance (TA)',
        'leave' => 'Leave (TL)',
        'claim' => 'Claim (TC)',
        'payroll' => 'Payroll (TP)',
    ];

    // Get the data directly from the Livewire component
    $leadId = null;
    $selectedModules = [];
    $swId = null;
    $projectPlans = [];
    $progressOverview = [];
    $overallSummary = [
        'totalTasks' => 0,
        'completedTasks' => 0,
        'overallProgress' => 0,
        'modules' => []
    ];

    // Try to get the livewire component and lead record
    try {
        if (isset($this) && method_exists($this, 'getRecord')) {
            $record = $this->getRecord();
            if ($record) {
                $leadId = $record->id;

                // Get the latest software handover for this lead (not relationship)
                $softwareHandover = \App\Models\SoftwareHandover::where('lead_id', $leadId)
                    ->latest()
                    ->first();

                if ($softwareHandover) {
                    // Get modules from latest SoftwareHandover
                    $selectedModules = $softwareHandover->getSelectedModules();
                    $swId = $softwareHandover->id;

                    // Sort modules by module_order
                    usort($selectedModules, function($a, $b) {
                        return \App\Models\ProjectTask::getModuleOrder($a) - \App\Models\ProjectTask::getModuleOrder($b);
                    });

                    // Get project plans for this specific sw_id
                    $projectPlans = \App\Models\ProjectPlan::where('lead_id', $leadId)
                        ->where('sw_id', $swId)
                        ->whereHas('projectTask', function ($query) use ($selectedModules) {
                            $query->whereIn('module', $selectedModules);
                        })
                        ->with('projectTask')
                        ->get()
                        ->sortBy(function ($plan) {
                            return $plan->projectTask->module_order * 1000 + $plan->projectTask->order;
                        })
                        ->groupBy('projectTask.module')
                        ->map(function ($plans, $module) {
                            return $plans->map(function ($plan) {
                                return array_merge($plan->toArray(), [
                                    'phase_name' => $plan->projectTask->phase_name,
                                    'task_name' => $plan->projectTask->task_name,
                                    'order' => $plan->projectTask->order,
                                    'module' => $plan->projectTask->module,
                                    'module_order' => $plan->projectTask->module_order,
                                    'percentage' => $plan->projectTask->percentage,
                                ]);
                            })->sortBy('order')->values();
                        })
                        ->toArray();

                    // Generate progress overview BY MODULE
                    $totalTasksAll = 0;
                    $completedTasksAll = 0;

                    foreach ($selectedModules as $module) {
                        $modulePlans = \App\Models\ProjectPlan::where('lead_id', $leadId)
                            ->where('sw_id', $swId)
                            ->whereHas('projectTask', function ($query) use ($module) {
                                $query->where('module', $module);
                            })
                            ->with('projectTask')
                            ->get();

                        if ($modulePlans->isNotEmpty()) {
                            $totalTasks = $modulePlans->count();
                            $completedTasks = $modulePlans->where('status', 'completed')->count();
                            $overallProgress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

                            $totalTasksAll += $totalTasks;
                            $completedTasksAll += $completedTasks;

                            $tasksArray = $modulePlans->map(function ($plan) {
                                return array_merge($plan->toArray(), [
                                    'phase_name' => $plan->projectTask->phase_name,
                                    'task_name' => $plan->projectTask->task_name,
                                    'order' => $plan->projectTask->order,
                                    'module' => $plan->projectTask->module,
                                    'module_order' => $plan->projectTask->module_order,
                                    'percentage' => $plan->projectTask->percentage,
                                ]);
                            })->sortBy('order')->values()->toArray();

                            $progressOverview[$module] = [
                                'tasks' => $tasksArray,
                                'totalTasks' => $totalTasks,
                                'completedTasks' => $completedTasks,
                                'overallProgress' => $overallProgress,
                                'module_order' => $modulePlans->first()->projectTask->module_order
                            ];

                            // Add to overall summary
                            $overallSummary['modules'][] = [
                                'module' => $module,
                                'module_order' => $modulePlans->first()->projectTask->module_order,
                                'progress' => $overallProgress,
                                'completed' => $completedTasks,
                                'total' => $totalTasks
                            ];
                        }
                    }

                    // Sort overall summary modules by module_order
                    usort($overallSummary['modules'], function($a, $b) {
                        return $a['module_order'] - $b['module_order'];
                    });

                    // Calculate overall progress
                    $overallSummary['totalTasks'] = $totalTasksAll;
                    $overallSummary['completedTasks'] = $completedTasksAll;
                    $overallSummary['overallProgress'] = $totalTasksAll > 0 ? round(($completedTasksAll / $totalTasksAll) * 100) : 0;
                }
            }
        }
    } catch (Exception $e) {
        // Fallback to empty data if there's any error
        \Log::error('Project Progress View Error: ' . $e->getMessage());
    }
@endphp

<style>
    .project-progress-container {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .overall-progress-card {
        padding: 24px;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .overall-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 2px solid #3b82f6;
    }

    .overall-title-section {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .overall-title {
        font-size: 20px;
        font-weight: 700;
        margin: 0;
        color: #1e40af;
    }

    .overall-sw-badge {
        display: inline-block;
        padding: 4px 12px;
        background-color: #dbeafe;
        color: #1e40af;
        font-size: 12px;
        font-weight: 600;
        border-radius: 12px;
    }

    .overall-stats {
        text-align: right;
    }

    .overall-percentage {
        font-size: 32px;
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

    .modules-summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
    }

    .module-summary-card {
        padding: 16px;
        background-color: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
    }

    .module-summary-name {
        font-size: 14px;
        font-weight: 600;
        color: #1e40af;
        margin-bottom: 8px;
    }

    .module-summary-progress {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    .module-summary-percentage {
        font-size: 20px;
        font-weight: 700;
        color: #1e40af;
    }

    .module-summary-tasks {
        font-size: 11px;
        color: #6b7280;
    }

    .module-summary-bar {
        height: 6px;
        background-color: #e5e7eb;
        border-radius: 3px;
        overflow: hidden;
    }

    .module-summary-fill {
        height: 100%;
        background-color: #3b82f6;
        border-radius: 3px;
        transition: width 0.3s ease;
    }

    .progress-overview-card {
        padding: 24px;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .module-header-section {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 2px solid #3b82f6;
    }

    .module-title-wrapper {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .module-title {
        font-size: 20px;
        font-weight: 700;
        color: #1e40af;
        margin: 0;
    }

    .sw-id-badge {
        display: inline-block;
        padding: 4px 12px;
        background-color: #dbeafe;
        color: #1e40af;
        font-size: 12px;
        font-weight: 600;
        border-radius: 12px;
    }

    .module-stats {
        text-align: right;
    }

    .module-percentage {
        font-size: 24px;
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

    .progress-timeline {
        position: relative;
        overflow-x: auto;
        padding-bottom: 16px;
    }

    .timeline-container {
        display: flex;
        align-items: flex-start;
        justify-content: flex-start;
        min-width: max-content;
        gap: 8px;
    }

    .timeline-task {
        position: relative;
        z-index: 10;
        display: flex;
        flex-direction: column;
        align-items: center;
        flex-shrink: 0;
        min-width: 0;
    }

    .timeline-circle {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        border: 2px solid;
        display: flex;
        align-items: center;
        justify-content: center;
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
    }

    .timeline-dot {
        width: 12px;
        height: 12px;
        background-color: #d1d5db;
        border-radius: 50%;
    }

    .timeline-info {
        margin-top: 12px;
        text-align: center;
        max-width: 120px;
    }

    .timeline-percentage {
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 4px;
    }

    .timeline-percentage.completed { color: #059669; }
    .timeline-percentage.in_progress { color: #d97706; }
    .timeline-percentage.pending { color: #6b7280; }

    .timeline-phase {
        font-size: 11px;
        color: #4b5563;
        font-weight: 500;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 2px;
    }

    .timeline-task-name {
        font-size: 11px;
        color: #6b7280;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .timeline-status {
        margin-top: 4px;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 8px;
        font-weight: 600;
        text-transform: uppercase;
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
        margin-top: 24px;
        min-width: 32px;
        max-width: 60px;
    }

    .timeline-line.completed { border-color: #10b981; }
    .timeline-line.pending { border-color: #d1d5db; }

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
</style>

<div class="project-progress-container">
    @if(!empty($selectedModules) && !empty($progressOverview))
        {{-- OVERALL PROJECT PROGRESS OVERVIEW --}}
        <div class="overall-progress-card">
            <div class="overall-header">
                <div class="overall-title-section">
                    <h3 class="overall-title">Project Progress Overview</h3>
                    <span class="overall-sw-badge">SW ID: {{ $swId }}</span>
                </div>
                <div class="overall-stats">
                    <div class="overall-percentage">{{ $overallSummary['overallProgress'] }}%</div>
                    <div class="overall-label">Overall Completion</div>
                    <div class="overall-meta">{{ $overallSummary['completedTasks'] }}/{{ $overallSummary['totalTasks'] }} tasks completed</div>
                </div>
            </div>

            {{-- Modules Timeline (Similar to Task Timeline) --}}
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
                        @endphp

                        <div class="timeline-task">
                            <div class="timeline-circle {{ $moduleStatus }}">
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
                                <div class="timeline-phase">{{ $moduleLabels[$moduleSummary['module']] ?? ucfirst(str_replace('_', ' ', $moduleSummary['module'])) }}</div>
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
                            <div class="timeline-line {{ $lineCompleted ? 'completed' : 'pending' }}"></div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        {{-- MODULE BY MODULE DETAILS --}}
        @foreach($selectedModules as $module)
            @if(isset($progressOverview[$module]) && !empty($progressOverview[$module]['tasks']))
                <div class="progress-overview-card">
                    <div class="module-header-section">
                        <div class="module-title-wrapper">
                            <h3 class="module-title">{{ $moduleLabels[$module] ?? ucfirst(str_replace('_', ' ', $module)) }}</h3>
                            <span class="sw-id-badge">SW ID: {{ $swId }}</span>
                        </div>
                        <div class="module-stats">
                            <div class="module-percentage">{{ $progressOverview[$module]['overallProgress'] }}%</div>
                            <div class="module-label">Module Completion</div>
                            <div class="module-meta">{{ $progressOverview[$module]['completedTasks'] }}/{{ $progressOverview[$module]['totalTasks'] }} tasks completed</div>
                        </div>
                    </div>

                    {{-- Progress Timeline for this module --}}
                    <div class="progress-timeline">
                        <div class="timeline-container">
                            @foreach($progressOverview[$module]['tasks'] as $index => $task)
                                @php
                                    $taskStatus = $task['status'] ?? 'pending';
                                    $isCompleted = $taskStatus === 'completed';
                                    $isInProgress = $taskStatus === 'in_progress';
                                @endphp

                                <div class="timeline-task">
                                    <div class="timeline-circle {{ $taskStatus }}">
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
                                        <div class="timeline-percentage {{ $taskStatus }}">{{ $task['percentage'] ?? 0 }}%</div>
                                        <div class="timeline-phase">{{ $task['phase_name'] ?? 'N/A' }}</div>
                                        <div class="timeline-task-name">{{ $task['task_name'] ?? 'N/A' }}</div>
                                        <div class="timeline-status {{ $taskStatus }}">
                                            {{ str_replace('_', ' ', $taskStatus) }}
                                        </div>
                                    </div>
                                </div>

                                @if($index < count($progressOverview[$module]['tasks']) - 1)
                                    @php
                                        $nextTask = $progressOverview[$module]['tasks'][$index + 1];
                                        $lineCompleted = $isCompleted && ($nextTask['status'] ?? 'pending') === 'completed';
                                    @endphp
                                    <div class="timeline-line {{ $lineCompleted ? 'completed' : 'pending' }}"></div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    @else
        <div class="empty-state">
            <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012-2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
            </svg>
            <p class="empty-title">No project plans found</p>
            <p class="empty-description">Please create a software handover first, then click "Refresh Modules"</p>
        </div>
    @endif
</div>
