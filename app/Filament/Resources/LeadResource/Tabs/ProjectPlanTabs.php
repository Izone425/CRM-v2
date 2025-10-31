<?php
namespace App\Filament\Resources\LeadResource\Tabs;

use App\Models\ProjectTask;
use App\Models\ProjectPlan;
use App\Models\Lead;
use App\Models\SoftwareHandover;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Support\Enums\ActionSize;
use Filament\Notifications\Notification;

class ProjectPlanTabs
{
    public static function getSchema(): array
    {
        return [
            Section::make('Project Plan')
                ->headerActions([
                    \Filament\Forms\Components\Actions\Action::make('refreshModules')
                        ->label('Refresh Modules')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->size(ActionSize::Small)
                        ->action(function (Set $set, Get $get, $livewire) {
                            $leadId = $livewire->record?->id ?? $get('id') ?? 0;

                            if ($leadId > 0) {
                                $lead = Lead::find($leadId);

                                // Get the latest software handover for this lead
                                $softwareHandover = SoftwareHandover::where('lead_id', $leadId)
                                    ->latest()
                                    ->first();

                                if ($softwareHandover) {
                                    // Get modules from SoftwareHandover based on sw_id
                                    $selectedModules = $softwareHandover->getSelectedModules();

                                    // Create project plans for selected modules with the latest sw_id
                                    self::createProjectPlansForModules($leadId, $softwareHandover->id, $selectedModules);

                                    // Update the hidden field to trigger view refresh
                                    $set('refresh_trigger', time());

                                    Notification::make()
                                        ->title('Modules Refreshed')
                                        ->body('Project plans updated based on latest software handover (SW ID: ' . $softwareHandover->id . ') modules: ' . implode(', ', $selectedModules))
                                        ->success()
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('No Software Handover Found')
                                        ->body('Please create a software handover first to define project modules')
                                        ->warning()
                                        ->send();
                                }
                            }
                        }),

                    \Filament\Forms\Components\Actions\Action::make('setTaskDates')
                        ->label('Set Task Dates')
                        ->icon('heroicon-o-calendar')
                        ->color('success')
                        ->size(ActionSize::Small)
                        ->form(function (Get $get, $livewire) {
                            $leadId = $livewire->record?->id ?? $get('id') ?? 0;

                            if ($leadId === 0) {
                                return [
                                    \Filament\Forms\Components\Placeholder::make('no_lead')
                                        ->content('No lead selected. Please save the lead first.')
                                ];
                            }

                            // Get the latest software handover for this lead
                            $softwareHandover = SoftwareHandover::where('lead_id', $leadId)
                                ->latest()
                                ->first();

                            if (!$softwareHandover) {
                                return [
                                    \Filament\Forms\Components\Placeholder::make('no_sw')
                                        ->content('No software handover found. Please create a software handover first.')
                                ];
                            }

                            // Get modules from SoftwareHandover by sw_id
                            $selectedModules = $softwareHandover->getSelectedModules();

                            // Get project plans for this lead and software handover
                            $projectPlans = ProjectPlan::where('lead_id', $leadId)
                                ->where('sw_id', $softwareHandover->id)
                                ->whereHas('projectTask', function ($query) use ($selectedModules) {
                                    $query->whereIn('module', $selectedModules);
                                })
                                ->with('projectTask')
                                ->orderBy('id')
                                ->get();

                            if ($projectPlans->isEmpty()) {
                                return [
                                    \Filament\Forms\Components\Placeholder::make('no_plans')
                                        ->content('No project plans found for SW ID: ' . $softwareHandover->id . '. Click "Refresh Modules" to generate plans.')
                                ];
                            }

                            $schema = [];

                            foreach ($selectedModules as $module) {
                                $modulePlans = $projectPlans->filter(function ($plan) use ($module) {
                                    return $plan->projectTask->module === $module;
                                });

                                if ($modulePlans->isNotEmpty()) {
                                    $schema[] = Section::make(ucfirst($module) . ' Module')
                                        ->schema($modulePlans->map(function ($plan) {
                                            return Section::make($plan->projectTask->phase_name . ' - ' . $plan->projectTask->task_name)
                                                ->description('Progress: ' . $plan->projectTask->percentage . '% (set by manager) | SW ID: ' . $plan->sw_id)
                                                ->schema([
                                                    DatePicker::make("plan_{$plan->id}_plan_start_date")
                                                        ->label('Plan Start Date')
                                                        ->default($plan->plan_start_date)
                                                        ->live()
                                                        ->afterStateUpdated(function ($state, Set $set, Get $get) use ($plan) {
                                                            if ($state && $get("plan_{$plan->id}_plan_end_date")) {
                                                                $start = \Carbon\Carbon::parse($state);
                                                                $end = \Carbon\Carbon::parse($get("plan_{$plan->id}_plan_end_date"));
                                                                $set("plan_{$plan->id}_plan_duration", $start->diffInDays($end) + 1);
                                                            }
                                                        }),
                                                    DatePicker::make("plan_{$plan->id}_plan_end_date")
                                                        ->label('Plan End Date')
                                                        ->default($plan->plan_end_date)
                                                        ->live()
                                                        ->afterStateUpdated(function ($state, Set $set, Get $get) use ($plan) {
                                                            if ($state && $get("plan_{$plan->id}_plan_start_date")) {
                                                                $start = \Carbon\Carbon::parse($get("plan_{$plan->id}_plan_start_date"));
                                                                $end = \Carbon\Carbon::parse($state);
                                                                $set("plan_{$plan->id}_plan_duration", $start->diffInDays($end) + 1);
                                                            }
                                                        }),
                                                    TextInput::make("plan_{$plan->id}_plan_duration")
                                                        ->label('Plan Duration (days)')
                                                        ->numeric()
                                                        ->readOnly(),
                                                    DatePicker::make("plan_{$plan->id}_actual_start_date")
                                                        ->label('Actual Start Date')
                                                        ->default($plan->actual_start_date),
                                                    DatePicker::make("plan_{$plan->id}_actual_end_date")
                                                        ->label('Actual End Date')
                                                        ->default($plan->actual_end_date)
                                                        ->helperText('Setting this date will mark the task as completed'),
                                                    TextInput::make("plan_{$plan->id}_actual_duration")
                                                        ->label('Actual Duration (days)')
                                                        ->numeric()
                                                        ->readOnly()
                                                        ->default($plan->actual_duration),
                                                ])->columns(6);
                                        })->toArray());
                                }
                            }

                            return $schema;
                        })
                        ->action(function (array $data, Get $get, Set $set, $livewire) {
                            $leadId = $livewire->record?->id ?? $get('id') ?? 0;

                            if ($leadId === 0) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('No lead ID found')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // Get the latest software handover for this lead
                            $softwareHandover = SoftwareHandover::where('lead_id', $leadId)
                                ->latest()
                                ->first();

                            if (!$softwareHandover) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('No software handover found')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            foreach ($data as $key => $value) {
                                if (preg_match('/plan_(\d+)_(.+)/', $key, $matches)) {
                                    $planId = $matches[1];
                                    $field = $matches[2];

                                    $plan = ProjectPlan::find($planId);
                                    if ($plan && $plan->lead_id == $leadId && !is_null($value)) {
                                        // Update the field
                                        $plan->{$field} = $value;

                                        // Make sure sw_id is stored with the latest software handover
                                        if (!$plan->sw_id) {
                                            $plan->sw_id = $softwareHandover->id;
                                        }

                                        $plan->save();

                                        // Auto-calculate durations
                                        if (in_array($field, ['plan_start_date', 'plan_end_date'])) {
                                            $plan->calculatePlanDuration();
                                        }
                                        if (in_array($field, ['actual_start_date', 'actual_end_date'])) {
                                            $plan->calculateActualDuration();
                                        }

                                        // Auto-update status based on dates
                                        $plan->updateStatusBasedOnDates();
                                    }
                                }
                            }

                            // Trigger view refresh
                            $set('refresh_trigger', time());

                            Notification::make()
                                ->title('Task dates updated successfully')
                                ->body('Updated for SW ID: ' . $softwareHandover->id)
                                ->success()
                                ->send();
                        })
                        ->modalWidth('7xl'),
                ])
                ->schema([
                    // Hidden field to trigger refresh
                    \Filament\Forms\Components\Hidden::make('refresh_trigger')
                        ->default(0)
                        ->live(),

                    ViewField::make('project_progress_view')
                        ->view('filament.resources.lead-resource.tabs.project-progress-view')
                        ->dehydrated(false),
                ])
        ];
    }

    protected static function updateViewData(Get $get, Set $set, ?int $leadId = null): void
    {
        // Trigger refresh by updating the hidden field
        $set('refresh_trigger', time());
    }

    protected static function createProjectPlansForModules(int $leadId, int $swId, array $modules): void
    {
        foreach ($modules as $module) {
            $tasks = ProjectTask::where('module', $module)->orderBy('order')->get();

            foreach ($tasks as $task) {
                ProjectPlan::firstOrCreate([
                    'lead_id' => $leadId,
                    'sw_id' => $swId, // Store the latest software handover ID
                    'project_task_id' => $task->id,
                ], [
                    'status' => 'pending',
                ]);
            }
        }
    }

    protected static function getProjectPlans(int $leadId, int $swId, array $modules): array
    {
        if ($leadId === 0 || $swId === 0) {
            return [];
        }

        return ProjectPlan::where('lead_id', $leadId)
            ->where('sw_id', $swId)
            ->whereHas('projectTask', function ($query) use ($modules) {
                $query->whereIn('module', $modules);
            })
            ->with('projectTask')
            ->get()
            ->groupBy('projectTask.module')
            ->map(function ($plans, $module) {
                return $plans->map(function ($plan) {
                    return array_merge($plan->toArray(), [
                        'phase_name' => $plan->projectTask->phase_name,
                        'task_name' => $plan->projectTask->task_name,
                        'order' => $plan->projectTask->order,
                        'module' => $plan->projectTask->module,
                        'percentage' => $plan->projectTask->percentage,
                    ]);
                })->sortBy('order')->values();
            })
            ->toArray();
    }

    protected static function generateProgressOverview(int $leadId, int $swId, array $modules): array
    {
        if ($leadId === 0 || $swId === 0) {
            return [
                'tasks' => [],
                'totalTasks' => 0,
                'completedTasks' => 0,
                'overallProgress' => 0
            ];
        }

        $plans = ProjectPlan::where('lead_id', $leadId)
            ->where('sw_id', $swId)
            ->whereHas('projectTask', function ($query) use ($modules) {
                $query->whereIn('module', $modules);
            })
            ->with('projectTask')
            ->get();

        if ($plans->isEmpty()) {
            return [
                'tasks' => [],
                'totalTasks' => 0,
                'completedTasks' => 0,
                'overallProgress' => 0
            ];
        }

        $totalTasks = $plans->count();
        $completedTasks = $plans->where('status', 'completed')->count();
        $overallProgress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

        $tasksArray = $plans->map(function ($plan) {
            return array_merge($plan->toArray(), [
                'phase_name' => $plan->projectTask->phase_name,
                'task_name' => $plan->projectTask->task_name,
                'order' => $plan->projectTask->order,
                'module' => $plan->projectTask->module,
                'percentage' => $plan->projectTask->percentage,
            ]);
        })->sortBy('order')->values()->toArray();

        return [
            'tasks' => $tasksArray,
            'totalTasks' => $totalTasks,
            'completedTasks' => $completedTasks,
            'overallProgress' => $overallProgress
        ];
    }
}
