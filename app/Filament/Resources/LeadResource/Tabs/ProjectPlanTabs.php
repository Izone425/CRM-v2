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
use Filament\Forms\Components\Textarea;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Support\Enums\ActionSize;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use Filament\Tables\Columns\TextColumn;

class ProjectPlanTabs
{
    public static function getSchema(): array
    {
        return [
            Section::make('Project Plan')
                ->headerActions([
                    \Filament\Forms\Components\Actions\Action::make('refreshModules')
                        ->label('Sync Tasks from Template')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->size(ActionSize::Small)
                        ->requiresConfirmation()
                        ->modalHeading('Sync Project Tasks')
                        ->modalDescription('This will create project tasks based on the latest software handover modules and admin-defined task templates. Phase 1 and Phase 2 will always be included.')
                        ->action(function (Set $set, Get $get, $livewire) {
                            $leadId = $livewire->record?->id ?? $get('id') ?? 0;

                            if ($leadId === 0) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Please save the lead first')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $softwareHandover = SoftwareHandover::where('lead_id', $leadId)
                                ->latest()
                                ->first();

                            if (!$softwareHandover) {
                                Notification::make()
                                    ->title('No Software Handover Found')
                                    ->body('Please create a software handover first to define project modules')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $selectedModules = $softwareHandover->getSelectedModules();
                            $modulesToSync = array_unique(array_merge(['phase 1', 'phase 2'], $selectedModules));
                            $createdCount = self::createProjectPlansForModules($leadId, $softwareHandover->id, $modulesToSync);

                            $set('refresh_trigger', time());

                            $modulesList = implode(', ', $modulesToSync);
                            Notification::make()
                                ->title('Tasks Synced Successfully')
                                ->body("Created/updated {$createdCount} tasks from templates for modules: {$modulesList}")
                                ->success()
                                ->send();
                        }),

                    \Filament\Forms\Components\Actions\Action::make('setTaskDates')
                        ->label('Update Task Dates & Status')
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

                            $softwareHandover = SoftwareHandover::where('lead_id', $leadId)
                                ->latest()
                                ->first();

                            if (!$softwareHandover) {
                                return [
                                    \Filament\Forms\Components\Placeholder::make('no_sw')
                                        ->content('No software handover found. Please create a software handover first.')
                                ];
                            }

                            $selectedModules = $softwareHandover->getSelectedModules();
                            $allModules = array_unique(array_merge(['phase 1', 'phase 2'], $selectedModules));

                            // ✅ Get all unique module_names from selected modules
                            $moduleNames = ProjectTask::whereIn('module', $allModules)
                                ->where('is_active', true)
                                ->select('module_name', 'module_order')
                                ->distinct()
                                ->orderBy('module_order')
                                ->orderBy('module_name')
                                ->get()
                                ->pluck('module_name')
                                ->toArray();

                            // Get project plans (only non-completed tasks)
                            $projectPlans = ProjectPlan::where('lead_id', $leadId)
                                ->where('sw_id', $softwareHandover->id)
                                ->where('status', '!=', 'completed')
                                ->whereHas('projectTask', function ($query) use ($moduleNames) {
                                    $query->whereIn('module_name', $moduleNames)
                                        ->where('is_active', true);
                                })
                                ->with('projectTask')
                                ->orderBy('id')
                                ->get();

                            if ($projectPlans->isEmpty()) {
                                return [
                                    \Filament\Forms\Components\Placeholder::make('no_plans')
                                        ->content('All tasks are completed! 🎉 No pending tasks found.')
                                ];
                            }

                            $schema = [];

                            // ✅ Group by module_name (not module)
                            foreach ($moduleNames as $moduleName) {
                                $modulePlans = $projectPlans->filter(function ($plan) use ($moduleName) {
                                    return $plan->projectTask && $plan->projectTask->module_name === $moduleName;
                                });

                                // Only show module if it has incomplete tasks
                                if ($modulePlans->isNotEmpty()) {
                                    $firstTask = $modulePlans->first()->projectTask;
                                    $modulePercentage = $firstTask->module_percentage;
                                    $moduleOrder = $firstTask->module_order ?? 999;

                                    // Calculate module progress
                                    $allModulePlans = ProjectPlan::where('lead_id', $leadId)
                                        ->where('sw_id', $softwareHandover->id)
                                        ->whereHas('projectTask', function ($query) use ($moduleName) {
                                            $query->where('module_name', $moduleName)
                                                ->where('is_active', true);
                                        })
                                        ->get();

                                    $totalModuleTasks = $allModulePlans->count();
                                    $completedModuleTasks = $allModulePlans->where('status', 'completed')->count();
                                    $pendingTasks = $totalModuleTasks - $completedModuleTasks;
                                    $progressPercentage = $totalModuleTasks > 0 ? round(($completedModuleTasks / $totalModuleTasks) * 100) : 0;

                                    // Auto-expand logic
                                    $isExpanded = false;
                                    if ($progressPercentage > 0 && $progressPercentage < 100) {
                                        $isExpanded = true;
                                    } elseif ($progressPercentage == 0) {
                                        static $firstPendingExpanded = false;
                                        if (!$firstPendingExpanded) {
                                            $isExpanded = true;
                                            $firstPendingExpanded = true;
                                        }
                                    }

                                    // ✅ Create table rows for this module
                                    $tableRows = [];
                                    foreach ($modulePlans as $plan) {
                                        $task = $plan->projectTask;

                                        $planDateRangeValue = null;
                                        if ($plan->plan_start_date && $plan->plan_end_date) {
                                            $planDateRangeValue = \Carbon\Carbon::parse($plan->plan_start_date)->format('d/m/Y') . ' - ' .
                                                                  \Carbon\Carbon::parse($plan->plan_end_date)->format('d/m/Y');
                                        }

                                        $actualDateRangeValue = null;
                                        if ($plan->actual_start_date && $plan->actual_end_date) {
                                            $actualDateRangeValue = \Carbon\Carbon::parse($plan->actual_start_date)->format('d/m/Y') . ' - ' .
                                                                    \Carbon\Carbon::parse($plan->actual_end_date)->format('d/m/Y');
                                        }

                                        $tableRows[] = [
                                            TextInput::make("plan_{$plan->id}_task")
                                                ->label('')
                                                ->default($task->task_name)
                                                ->disabled()
                                                ->columnSpan(3),

                                            DateRangePicker::make("plan_{$plan->id}_plan_date_range")
                                                ->label('')
                                                ->default($planDateRangeValue)
                                                ->format('d/m/Y')
                                                ->displayFormat('DD/MM/YYYY')
                                                ->live()
                                                ->afterStateUpdated(function ($state, Set $set) use ($plan) {
                                                    if ($state) {
                                                        [$start, $end] = explode(' - ', $state);
                                                        $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', $start);
                                                        $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', $end);
                                                        $set("plan_{$plan->id}_plan_duration", $startDate->diffInDays($endDate) + 1);

                                                        if ($plan->status === 'pending') {
                                                            $set("plan_{$plan->id}_status", 'in_progress');
                                                        }
                                                    }
                                                })
                                                ->columnSpan(2),

                                            TextInput::make("plan_{$plan->id}_plan_duration")
                                                ->label('')
                                                ->numeric()
                                                ->default($plan->plan_duration)
                                                ->readOnly()
                                                ->suffix('days')
                                                ->columnSpan(1),

                                            DateRangePicker::make("plan_{$plan->id}_actual_date_range")
                                                ->label('')
                                                ->default($actualDateRangeValue)
                                                ->format('d/m/Y')
                                                ->displayFormat('DD/MM/YYYY')
                                                ->live()
                                                ->afterStateUpdated(function ($state, Set $set) use ($plan) {
                                                    if ($state) {
                                                        [$start, $end] = explode(' - ', $state);
                                                        $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', $start);
                                                        $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', $end);
                                                        $set("plan_{$plan->id}_actual_duration", $startDate->diffInDays($endDate) + 1);
                                                        $set("plan_{$plan->id}_status", 'completed');
                                                    }
                                                })
                                                ->columnSpan(2),

                                            TextInput::make("plan_{$plan->id}_actual_duration")
                                                ->label(false)
                                                ->numeric()
                                                ->default($plan->actual_duration)
                                                ->readOnly()
                                                ->suffix('days')
                                                ->columnSpan(1),

                                            Select::make("plan_{$plan->id}_status")
                                                ->label('')
                                                ->options([
                                                    'pending' => 'Pending',
                                                    'in_progress' => 'In Progress',
                                                    'completed' => 'Completed',
                                                    'on_hold' => 'On Hold',
                                                ])
                                                ->disabled()
                                                ->dehydrated(true)
                                                ->default($plan->status)
                                                ->required()
                                                ->columnSpan(3),
                                        ];
                                    }

                                    $moduleSchema = [];

                                    // Task rows
                                    foreach ($tableRows as $row) {
                                        $moduleSchema[] = \Filament\Forms\Components\Grid::make(12)
                                            ->schema($row);
                                    }

                                    // Add module section
                                    $schema[] = Section::make($moduleName)
                                        ->description("Module Weight: {$modulePercentage}% | Order: {$moduleOrder} | Progress: {$progressPercentage}% ({$completedModuleTasks}/{$totalModuleTasks}) | Pending: {$pendingTasks} | SW ID: {$softwareHandover->id}")
                                        ->collapsible()
                                        ->collapsed(!$isExpanded)
                                        ->schema($moduleSchema);
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

                            $updatedCount = 0;

                            DB::transaction(function () use ($data, $leadId, &$updatedCount) {
                                foreach ($data as $key => $value) {
                                    if (preg_match('/plan_(\d+)_plan_date_range/', $key, $matches)) {
                                        $planId = $matches[1];
                                        $plan = ProjectPlan::find($planId);

                                        if ($plan && $plan->lead_id == $leadId && $value) {
                                            [$start, $end] = explode(' - ', $value);
                                            $plan->plan_start_date = \Carbon\Carbon::createFromFormat('d/m/Y', $start)->format('Y-m-d');
                                            $plan->plan_end_date = \Carbon\Carbon::createFromFormat('d/m/Y', $end)->format('Y-m-d');

                                            if ($plan->status === 'pending') {
                                                $plan->status = 'in_progress';
                                            }

                                            $plan->save();
                                            $plan->calculatePlanDuration();
                                            $updatedCount += 2;
                                        }
                                    }
                                    elseif (preg_match('/plan_(\d+)_actual_date_range/', $key, $matches)) {
                                        $planId = $matches[1];
                                        $plan = ProjectPlan::find($planId);

                                        if ($plan && $plan->lead_id == $leadId && $value) {
                                            [$start, $end] = explode(' - ', $state);
                                            $plan->actual_start_date = \Carbon\Carbon::createFromFormat('d/m/Y', $start)->format('Y-m-d');
                                            $plan->actual_end_date = \Carbon\Carbon::createFromFormat('d/m/Y', $end)->format('Y-m-d');
                                            $plan->status = 'completed';

                                            $plan->save();
                                            $plan->calculateActualDuration();
                                            $updatedCount += 2;
                                        }
                                    }
                                    elseif (preg_match('/plan_(\d+)_status/', $key, $matches)) {
                                        $planId = $matches[1];
                                        $plan = ProjectPlan::find($planId);

                                        if ($plan && $plan->lead_id == $leadId) {
                                            $plan->status = $value;
                                            $plan->save();
                                            $updatedCount++;
                                        }
                                    }
                                }
                            });

                            $set('refresh_trigger', time());

                            Notification::make()
                                ->title('Tasks Updated Successfully')
                                ->success()
                                ->send();
                        })
                        ->modalWidth('7xl')
                        ->slideOver(),
                ])
                ->schema([
                    \Filament\Forms\Components\Hidden::make('refresh_trigger')
                        ->default(0)
                        ->live(),

                    ViewField::make('project_progress_view')
                        ->view('filament.resources.lead-resource.tabs.project-progress-view')
                        ->live()
                        ->dehydrated(false),
                ])
        ];
    }

    protected static function createProjectPlansForModules(int $leadId, int $swId, array $modules): int
    {
        $createdCount = 0;

        foreach ($modules as $module) {
            $moduleNames = ProjectTask::where('module', $module)
                ->where('is_active', true)
                ->select('module_name')
                ->distinct()
                ->get()
                ->pluck('module_name');

            foreach ($moduleNames as $moduleName) {
                $tasks = ProjectTask::where('module_name', $moduleName)
                    ->where('is_active', true)
                    ->orderBy('order')
                    ->get();

                foreach ($tasks as $task) {
                    $plan = ProjectPlan::firstOrCreate(
                        [
                            'lead_id' => $leadId,
                            'sw_id' => $swId,
                            'project_task_id' => $task->id,
                        ],
                        [
                            'status' => 'pending',
                        ]
                    );

                    if ($plan->wasRecentlyCreated) {
                        $createdCount++;
                    }
                }
            }
        }

        return $createdCount;
    }
}
