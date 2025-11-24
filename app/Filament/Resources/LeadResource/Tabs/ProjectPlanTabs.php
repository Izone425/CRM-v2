<?php
namespace App\Filament\Resources\LeadResource\Tabs;

use App\Models\ProjectTask;
use App\Models\ProjectPlan;
use App\Models\Lead;
use App\Models\SoftwareHandover;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
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
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectPlanTabs
{
    public static function getSchema(): array
    {
        return [
            Section::make('Project Plan')
                ->headerActions([
                    \Filament\Forms\Components\Actions\Action::make('downloadExcel')
                        ->label('Generate Project Plan Excel')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->size(ActionSize::Small)
                        ->action(function (Get $get, $livewire) {
                            $leadId = $livewire->record?->id ?? $get('id') ?? 0;

                            if ($leadId === 0) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Please save the lead first')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $lead = Lead::find($leadId);
                            $softwareHandover = SoftwareHandover::where('lead_id', $leadId)
                                ->latest()
                                ->first();

                            if (!$softwareHandover) {
                                Notification::make()
                                    ->title('No Software Handover Found')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $filePath = self::generateProjectPlanExcel($lead, $softwareHandover);

                            if ($filePath) {
                                $softwareHandover->update([
                                    'project_plan_generated_at' => now(),
                                ]);

                                // ✅ Get file details for the notification actions
                                $companyName = $lead->companyDetail?->company_name ?? 'Unknown';
                                $companySlug = \Illuminate\Support\Str::slug($companyName);

                                // Find the latest file
                                $files = \Illuminate\Support\Facades\Storage::disk('public')->files('project-plans');
                                $matchingFiles = [];

                                foreach ($files as $file) {
                                    if (str_contains($file, $companySlug)) {
                                        $fullPath = storage_path('app/public/' . $file);
                                        $matchingFiles[] = [
                                            'path' => $file,
                                            'modified' => file_exists($fullPath) ? filemtime($fullPath) : 0
                                        ];
                                    }
                                }

                                if (!empty($matchingFiles)) {
                                    usort($matchingFiles, function($a, $b) {
                                        return $b['modified'] - $a['modified'];
                                    });

                                    $latestFile = $matchingFiles[0];
                                    $fileName = basename($latestFile['path']);
                                    $fileFullPath = storage_path('app/public/' . $latestFile['path']);

                                    // ✅ Notification with View and Download actions
                                    Notification::make()
                                        ->title('Excel File Generated Successfully')
                                        ->body('Project plan Excel file has been generated. Click below to view or download.')
                                        ->success()
                                        ->duration(10000) // 10 seconds to give time to click
                                        ->actions([
                                            \Filament\Notifications\Actions\Action::make('view')
                                                ->label('View in Office Online')
                                                ->icon('heroicon-o-eye')
                                                ->color('info')
                                                ->url(function () use ($latestFile) {
                                                    // ✅ Generate public URL for the file
                                                    $publicUrl = url('storage/' . $latestFile['path']);

                                                    // ✅ Use Office Web Viewer
                                                    return 'https://view.officeapps.live.com/op/view.aspx?src=' . urlencode($publicUrl);
                                                })
                                                ->openUrlInNewTab(),

                                            \Filament\Notifications\Actions\Action::make('download')
                                                ->label('Download')
                                                ->icon('heroicon-o-arrow-down-tray')
                                                ->color('success')
                                                ->url(function () use ($latestFile) {
                                                    return route('download.project-plan', [
                                                        'file' => basename($latestFile['path'])
                                                    ]);
                                                })
                                                ->openUrlInNewTab(),
                                        ])
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('Excel File Generated')
                                        ->body('Project plan Excel file has been generated and saved.')
                                        ->success()
                                        ->send();
                                }
                            }
                        }),

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

                                    $tableRows = [];

                                    $tableRows[] = [
                                        \Filament\Forms\Components\Placeholder::make("header_{$moduleName}_task")
                                            ->label('')
                                            ->content(new \Illuminate\Support\HtmlString(
                                                '<div>
                                                    <strong style="font-size: 14px;">Task Name</strong>
                                                </div>'
                                            ))
                                            ->columnSpan(4),

                                        \Filament\Forms\Components\Placeholder::make("header_{$moduleName}_plan_date")
                                            ->label('')
                                            ->content(new \Illuminate\Support\HtmlString(
                                                '<div>
                                                    <strong style="font-size: 14px;">Planned Date</strong>
                                                </div>'
                                            ))
                                            ->columnSpan(4),

                                        \Filament\Forms\Components\Placeholder::make("header_{$moduleName}_plan_duration")
                                            ->label('')
                                            ->content(new \Illuminate\Support\HtmlString(
                                                '<div>
                                                    <strong style="font-size: 14px;">Duration</strong>
                                                </div>'
                                            ))
                                            ->columnSpan(2),

                                        \Filament\Forms\Components\Placeholder::make("header_{$moduleName}_actual_date")
                                            ->label('')
                                            ->content(new \Illuminate\Support\HtmlString(
                                                '<div>
                                                    <strong style="font-size: 14px;">Actual Date</strong>
                                                </div>'
                                            ))
                                            ->columnSpan(6),

                                        \Filament\Forms\Components\Placeholder::make("header_{$moduleName}_actual_duration")
                                            ->label('')
                                            ->content(new \Illuminate\Support\HtmlString(
                                                '<div>
                                                    <strong style="font-size: 14px;">Duration</strong>
                                                </div>'
                                            ))
                                            ->columnSpan(2),
                                    ];

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
                                                ->hiddenLabel()
                                                ->default($task->task_name)
                                                ->disabled()
                                                ->columnSpan(4),

                                            TextInput::make("plan_{$plan->id}_plan_date_range")
                                                ->hiddenLabel()
                                                ->default($planDateRangeValue)
                                                ->live(onBlur: true)
                                                ->suffixAction(
                                                    \Filament\Forms\Components\Actions\Action::make('selectPlanDateRange')
                                                        ->icon('heroicon-m-calendar')
                                                        ->tooltip('Select date range')
                                                        ->modalHeading('Select Planned Date Range')
                                                        ->modalWidth('md')
                                                        ->form([
                                                            DateRangePicker::make('date_range')
                                                                ->label('Planned Date Range')
                                                                ->format('d/m/Y')
                                                                ->displayFormat('DD/MM/YYYY')
                                                                ->required()
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->action(function (array $data, Set $set) use ($plan) {
                                                            $dateRange = $data['date_range'];

                                                            if ($dateRange) {
                                                                $set("plan_{$plan->id}_plan_date_range", $dateRange);

                                                                [$start, $end] = explode(' - ', $dateRange);
                                                                $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($start));
                                                                $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($end));

                                                                $weekdays = self::calculateWeekdays($startDate, $endDate);
                                                                $set("plan_{$plan->id}_plan_duration", $weekdays);

                                                                if ($plan->status === 'pending') {
                                                                    $set("plan_{$plan->id}_status", 'in_progress');
                                                                }

                                                                Notification::make()
                                                                    ->title('Planned Dates Set')
                                                                    ->body("Duration: {$weekdays} days")
                                                                    ->success()
                                                                    ->send();
                                                            }
                                                        })
                                                )
                                                ->afterStateUpdated(function ($state, Set $set) use ($plan) {
                                                    if ($state && str_contains($state, ' - ')) {
                                                        try {
                                                            [$start, $end] = explode(' - ', $state);
                                                            $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($start));
                                                            $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($end));

                                                            $weekdays = self::calculateWeekdays($startDate, $endDate);
                                                            $set("plan_{$plan->id}_plan_duration", $weekdays);

                                                            if ($plan->status === 'pending') {
                                                                $set("plan_{$plan->id}_status", 'in_progress');
                                                            }
                                                        } catch (\Exception $e) {
                                                            // Invalid format - just ignore
                                                        }
                                                    }
                                                })
                                                ->columnSpan(4),

                                            TextInput::make("plan_{$plan->id}_plan_duration")
                                                ->hiddenLabel()
                                                ->numeric()
                                                ->default($plan->plan_duration)
                                                ->readOnly()
                                                ->columnSpan(2),

                                            TextInput::make("plan_{$plan->id}_actual_start_date")
                                                ->hiddenLabel()
                                                ->default($plan->actual_start_date ? \Carbon\Carbon::parse($plan->actual_start_date)->format('d/m/Y') : '')
                                                ->live(onBlur: true)
                                                ->suffixAction(
                                                    \Filament\Forms\Components\Actions\Action::make('selectActualStartDate')
                                                        ->icon('heroicon-m-calendar')
                                                        ->tooltip('Select start date')
                                                        ->modalHeading('Select Actual Start Date')
                                                        ->modalWidth('md')
                                                        ->form([
                                                            DatePicker::make('start_date')
                                                                ->label('Actual Start Date')
                                                                ->format('Y-m-d')
                                                                ->native(false)
                                                                ->displayFormat('d/m/Y')
                                                                ->required()
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->action(function (array $data, Set $set, Get $get) use ($plan) {
                                                            $startDate = $data['start_date'];

                                                            if ($startDate) {
                                                                $start = \Carbon\Carbon::parse($startDate);
                                                                $set("plan_{$plan->id}_actual_start_date", $start->format('d/m/Y'));

                                                                // Check if end date exists and is before start date
                                                                $endDateDisplay = $get("plan_{$plan->id}_actual_end_date");
                                                                if ($endDateDisplay) {
                                                                    try {
                                                                        $end = \Carbon\Carbon::createFromFormat('d/m/Y', $endDateDisplay);

                                                                        // ✅ Changed: Only clear if end is BEFORE start
                                                                        if ($end->lt($start)) {
                                                                            $set("plan_{$plan->id}_actual_end_date", null);
                                                                            $set("plan_{$plan->id}_actual_duration", null);

                                                                            Notification::make()
                                                                                ->title('End Date Cleared')
                                                                                ->body('End date was before start date and has been cleared')
                                                                                ->warning()
                                                                                ->send();
                                                                        } else {
                                                                            // Recalculate duration
                                                                            $weekdays = self::calculateWeekdays($start, $end);
                                                                            $set("plan_{$plan->id}_actual_duration", $weekdays);
                                                                        }
                                                                    } catch (\Exception $e) {
                                                                        // Invalid end date format
                                                                    }
                                                                }

                                                                // Update status to in_progress when start date is set
                                                                if ($plan->status === 'pending') {
                                                                    $set("plan_{$plan->id}_status", 'in_progress');
                                                                }

                                                                Notification::make()
                                                                    ->title('Start Date Set')
                                                                    ->body($start->format('d/m/Y'))
                                                                    ->success()
                                                                    ->send();
                                                            }
                                                        })
                                                )
                                                ->afterStateUpdated(function ($state, Set $set, Get $get) use ($plan) {
                                                    if ($state) {
                                                        try {
                                                            $start = \Carbon\Carbon::createFromFormat('d/m/Y', trim($state));

                                                            // Check if end date exists
                                                            $endDateDisplay = $get("plan_{$plan->id}_actual_end_date");
                                                            if ($endDateDisplay) {
                                                                $end = \Carbon\Carbon::createFromFormat('d/m/Y', trim($endDateDisplay));

                                                                // ✅ Changed: Only clear if end is BEFORE start (not same day)
                                                                if ($end->lt($start)) {
                                                                    $set("plan_{$plan->id}_actual_end_date", null);
                                                                    $set("plan_{$plan->id}_actual_duration", null);
                                                                } else {
                                                                    $weekdays = self::calculateWeekdays($start, $end);
                                                                    $set("plan_{$plan->id}_actual_duration", $weekdays);
                                                                }
                                                            }

                                                            if ($plan->status === 'pending') {
                                                                $set("plan_{$plan->id}_status", 'in_progress');
                                                            }
                                                        } catch (\Exception $e) {
                                                            // Invalid format
                                                        }
                                                    }
                                                })
                                                ->columnSpan(3),

                                            TextInput::make("plan_{$plan->id}_actual_end_date")
                                                ->hiddenLabel()
                                                ->default($plan->actual_end_date ? \Carbon\Carbon::parse($plan->actual_end_date)->format('d/m/Y') : '')
                                                ->live(onBlur: true)
                                                ->suffixAction(
                                                    \Filament\Forms\Components\Actions\Action::make('selectActualEndDate')
                                                        ->icon('heroicon-m-calendar')
                                                        ->tooltip('Select end date')
                                                        ->modalHeading('Select Actual End Date')
                                                        ->modalWidth('md')
                                                        ->form(function (Get $get) use ($plan) {
                                                            $startDateDisplay = $get("plan_{$plan->id}_actual_start_date");
                                                            $minDate = null;

                                                            if ($startDateDisplay) {
                                                                try {
                                                                    $minDate = \Carbon\Carbon::createFromFormat('d/m/Y', $startDateDisplay);
                                                                } catch (\Exception $e) {
                                                                    // Invalid start date format
                                                                }
                                                            }

                                                            return [
                                                                DatePicker::make('end_date')
                                                                    ->label('Actual End Date')
                                                                    ->format('Y-m-d')
                                                                    ->displayFormat('d/m/Y')
                                                                    ->native(false)
                                                                    ->required()
                                                                    ->minDate($minDate?->subDay())
                                                                    ->columnSpanFull(),
                                                            ];
                                                        })
                                                        ->action(function (array $data, Set $set, Get $get) use ($plan) {
                                                            $endDate = $data['end_date'];

                                                            if ($endDate) {
                                                                $startDateDisplay = $get("plan_{$plan->id}_actual_start_date");

                                                                if (!$startDateDisplay) {
                                                                    Notification::make()
                                                                        ->title('Start Date Required')
                                                                        ->body('Please select actual start date first')
                                                                        ->warning()
                                                                        ->send();
                                                                    return;
                                                                }

                                                                try {
                                                                    // ✅ FIX: Parse both dates consistently
                                                                    $start = \Carbon\Carbon::createFromFormat('d/m/Y', $startDateDisplay)->startOfDay();
                                                                    $end = \Carbon\Carbon::parse($endDate)->startOfDay(); // From calendar comes in Y-m-d format

                                                                    $set("plan_{$plan->id}_actual_end_date", $end->format('d/m/Y'));

                                                                    // Calculate duration
                                                                    $weekdays = self::calculateWeekdays($start, $end);
                                                                    $set("plan_{$plan->id}_actual_duration", $weekdays);

                                                                    // Set status to completed when end date is entered
                                                                    $set("plan_{$plan->id}_status", 'completed');

                                                                    Notification::make()
                                                                        ->title('Task Completed')
                                                                        ->body("Start: {$start->format('d/m/Y')} | End: {$end->format('d/m/Y')} | Duration: {$weekdays} days")
                                                                        ->success()
                                                                        ->send();
                                                                } catch (\Exception $e) {
                                                                    Notification::make()
                                                                        ->title('Invalid Date Format')
                                                                        ->body('Error: ' . $e->getMessage())
                                                                        ->danger()
                                                                        ->send();
                                                                }
                                                            }
                                                        })
                                                )
                                                ->afterStateUpdated(function ($state, Set $set, Get $get) use ($plan) {
                                                    if ($state) {
                                                        try {
                                                            $startDateDisplay = $get("plan_{$plan->id}_actual_start_date");

                                                            if (!$startDateDisplay) {
                                                                return;
                                                            }

                                                            // ✅ FIX: Parse consistently - state could be in Y-m-d or d/m/Y format
                                                            $start = \Carbon\Carbon::createFromFormat('d/m/Y', trim($startDateDisplay))->startOfDay();

                                                            // Try d/m/Y format first, then Y-m-d format
                                                            try {
                                                                $end = \Carbon\Carbon::createFromFormat('d/m/Y', trim($state))->startOfDay();
                                                            } catch (\Exception $e) {
                                                                $end = \Carbon\Carbon::parse(trim($state))->startOfDay();
                                                            }

                                                            if ($end->lt($start)) {
                                                                $set("plan_{$plan->id}_actual_end_date", null);
                                                                $set("plan_{$plan->id}_actual_duration", null);
                                                                return;
                                                            }

                                                            $weekdays = self::calculateWeekdays($start, $end);
                                                            $set("plan_{$plan->id}_actual_duration", $weekdays);
                                                            $set("plan_{$plan->id}_status", 'completed');
                                                        } catch (\Exception $e) {
                                                            Log::error('Error calculating duration: ' . $e->getMessage());
                                                        }
                                                    }
                                                })
                                                ->columnSpan(3),

                                            TextInput::make("plan_{$plan->id}_actual_duration")
                                                ->hiddenLabel()
                                                ->numeric()
                                                ->default($plan->actual_duration)
                                                ->readOnly()
                                                ->columnSpan(2),

                                            Textarea::make("plan_{$plan->id}_remarks")
                                                ->hiddenLabel()
                                                ->default($plan->remarks)
                                                ->placeholder('Add remarks...')
                                                ->live(onBlur: true)
                                                ->rows(2)
                                                ->autosize()
                                                ->columnSpan(16),

                                            Placeholder::make("plan_{$plan->id}_status")
                                                ->hiddenLabel()
                                                ->content(function (Get $get) use ($plan) {
                                                    // Map status codes to display labels
                                                    $statusLabels = [
                                                        'pending' => 'Pending',
                                                        'in_progress' => 'In Progress',
                                                        'completed' => 'Completed',
                                                        'on_hold' => 'On Hold',
                                                    ];

                                                    // ✅ Try to get the current state from the form first, fallback to database value
                                                    $currentStatus = $get("plan_{$plan->id}_status") ?? $plan->status;
                                                    $statusLabel = $statusLabels[$currentStatus] ?? ucfirst(str_replace('_', ' ', $currentStatus));

                                                    return new \Illuminate\Support\HtmlString(
                                                        '<div style="text-align: center;">
                                                            ' . $statusLabel . '
                                                        </div>'
                                                    );
                                                })
                                                ->columnSpan(2),
                                        ];
                                    }

                                    $moduleSchema = [];

                                    // Task rows
                                    foreach ($tableRows as $row) {
                                        $moduleSchema[] = \Filament\Forms\Components\Grid::make(18)
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
                                            try {
                                                [$start, $end] = explode(' - ', $value);
                                                $plan->plan_start_date = \Carbon\Carbon::createFromFormat('d/m/Y', trim($start))->format('Y-m-d');
                                                $plan->plan_end_date = \Carbon\Carbon::createFromFormat('d/m/Y', trim($end))->format('Y-m-d');

                                                if ($plan->status === 'pending') {
                                                    $plan->status = 'in_progress';
                                                }

                                                $plan->save();
                                                $plan->calculatePlanDuration();
                                                $updatedCount++;
                                            } catch (\Exception $e) {
                                                // Invalid date format
                                            }
                                        }
                                    }
                                    elseif (preg_match('/plan_(\d+)_actual_start_date/', $key, $matches)) {
                                        $planId = $matches[1];
                                        $plan = ProjectPlan::find($planId);

                                        if ($plan && $plan->lead_id == $leadId && $value) {
                                            try {
                                                $plan->actual_start_date = \Carbon\Carbon::createFromFormat('d/m/Y', trim($value))->format('Y-m-d');

                                                if ($plan->status === 'pending') {
                                                    $plan->status = 'in_progress';
                                                }

                                                $plan->save();
                                                $updatedCount++;
                                            } catch (\Exception $e) {
                                                // Invalid date format
                                            }
                                        }
                                    }
                                    elseif (preg_match('/plan_(\d+)_actual_end_date/', $key, $matches)) {
                                        $planId = $matches[1];
                                        $plan = ProjectPlan::find($planId);

                                        if ($plan && $plan->lead_id == $leadId && $value) {
                                            try {
                                                $plan->actual_end_date = \Carbon\Carbon::createFromFormat('d/m/Y', trim($value))->format('Y-m-d');
                                                $plan->status = 'completed';
                                                $plan->save();
                                                $plan->calculateActualDuration();
                                                $updatedCount++;
                                            } catch (\Exception $e) {
                                                // Invalid date format
                                            }
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
                                    elseif (preg_match('/plan_(\d+)_remarks/', $key, $matches)) {
                                        $planId = $matches[1];
                                        $plan = ProjectPlan::find($planId);

                                        if ($plan && $plan->lead_id == $leadId) {
                                            $plan->remarks = $value;
                                            $plan->save();
                                            $updatedCount++;
                                        }
                                    }
                                }
                            });

                            $set('refresh_trigger', time());

                            Notification::make()
                                ->title('Tasks Updated Successfully')
                                ->body("Updated {$updatedCount} field(s). The progress view will refresh automatically.")
                                ->success()
                                ->send();

                            $livewire->dispatch('refresh-project-progress');
                        })
                        ->modalWidth('7xl')
                        ->slideOver()
                        ->after(function ($livewire) {
                            // ✅ Force a full component refresh after modal closes
                            $livewire->dispatch('$refresh');
                        }),
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

    protected static function generateProjectPlanExcel(Lead $lead, SoftwareHandover $softwareHandover): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document properties
        $companyName = $lead->companyDetail?->company_name ?? 'Unknown Company';
        $implementerName = $softwareHandover->implementer ?? 'Not Assigned';

        $spreadsheet->getProperties()
            ->setCreator('TimeTec CRM')
            ->setTitle("Project Plan - {$companyName}")
            ->setSubject('Project Implementation Plan');

        $currentRow = 1;

        // Row 1: Company Name
        $sheet->setCellValue("A{$currentRow}", 'Company Name');
        $sheet->mergeCells("A{$currentRow}:B{$currentRow}");
        $sheet->setCellValue("C{$currentRow}", $companyName);
        $sheet->mergeCells("C{$currentRow}:K{$currentRow}");
        $sheet->getStyle("A{$currentRow}:K{$currentRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F5E9']],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
        ]);
        $currentRow++;

        // Row 2: Implementer Name
        $sheet->setCellValue("A{$currentRow}", 'Implementer Name');
        $sheet->mergeCells("A{$currentRow}:B{$currentRow}");
        $sheet->setCellValue("C{$currentRow}", $implementerName);
        $sheet->mergeCells("C{$currentRow}:K{$currentRow}");
        $sheet->getStyle("A{$currentRow}:K{$currentRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
        ]);
        $currentRow++;

        // Row 3: Project Progress Overview
        $sheet->setCellValue("A{$currentRow}", 'Project Progress Overview');
        $sheet->mergeCells("A{$currentRow}:K{$currentRow}");
        $sheet->getStyle("A{$currentRow}:K{$currentRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1976D2']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
        ]);
        $sheet->getRowDimension($currentRow)->setRowHeight(30);
        $currentRow++;

        // Add empty row for spacing
        $currentRow++;

        $selectedModules = $softwareHandover->getSelectedModules();
        $allModules = array_unique(array_merge(['phase 1', 'phase 2'], $selectedModules));

        $moduleNames = ProjectTask::whereIn('module', $allModules)
            ->where('is_active', true)
            ->select('module_name', 'module_order', 'module_percentage', 'module')
            ->distinct()
            ->orderBy('module_order')
            ->orderBy('module_name')
            ->get();

        foreach ($moduleNames as $moduleData) {
            $moduleName = $moduleData->module_name;
            $modulePercentage = $moduleData->module_percentage;
            $module = $moduleData->module;

            $modulePlans = ProjectPlan::where('lead_id', $lead->id)
                ->where('sw_id', $softwareHandover->id)
                ->whereHas('projectTask', function ($query) use ($moduleName) {
                    $query->where('module_name', $moduleName)
                        ->where('is_active', true);
                })
                ->with('projectTask')
                ->orderBy('id')
                ->get();

            if ($modulePlans->isEmpty()) {
                continue;
            }

            // ✅ First row: Plan and Actual headers only (E-J), K is empty
            $sheet->setCellValue("E{$currentRow}", 'Plan');
            $sheet->mergeCells("E{$currentRow}:G{$currentRow}");

            $sheet->setCellValue("H{$currentRow}", 'Actual');
            $sheet->mergeCells("H{$currentRow}:J{$currentRow}");

            $sheet->getStyle("E{$currentRow}:G{$currentRow}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
                'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                ],
            ]);

            $sheet->getStyle("H{$currentRow}:J{$currentRow}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00FF00']],
                'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                ],
            ]);

            $currentRow++;

            // ✅ Second row: Module code + Module name + Sub-headers + Remarks
            $sheet->setCellValue("A{$currentRow}", ucfirst(strtolower($module)));
            $sheet->setCellValue("B{$currentRow}", $moduleName);
            $sheet->setCellValue("C{$currentRow}", 'Status');
            $sheet->setCellValue("D{$currentRow}", $modulePercentage . '%');

            $sheet->getStyle("A{$currentRow}:D{$currentRow}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00B0F0']],
                'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                ],
            ]);

            // ✅ Sub-headers WITH REMARKS
            $headers = ['Start Date', 'End Date', 'Duration', 'Start Date', 'End Date', 'Duration', 'Remarks'];
            $col = 'E';
            foreach ($headers as $header) {
                $sheet->setCellValue("{$col}{$currentRow}", $header);

                if (in_array($col, ['E', 'F', 'G'])) {
                    $sheet->getStyle("{$col}{$currentRow}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
                        'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                        ],
                    ]);
                } elseif (in_array($col, ['H', 'I', 'J'])) {
                    $sheet->getStyle("{$col}{$currentRow}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00FF00']],
                        'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                        ],
                    ]);
                } elseif ($col === 'K') {
                    // ✅ Remarks column styling
                    $sheet->getStyle("{$col}{$currentRow}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFE699']],
                        'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                        ],
                    ]);
                }

                $col++;
            }

            $currentRow++;

            // Task rows
            $taskNumber = 1;
            foreach ($modulePlans as $plan) {
                $task = $plan->projectTask;

                $sheet->setCellValue("A{$currentRow}", $taskNumber);
                $sheet->setCellValue("B{$currentRow}", $task->task_name);
                $sheet->setCellValue("C{$currentRow}", ucfirst($plan->status));
                $sheet->setCellValue("D{$currentRow}", ($task->task_percentage ?? 0) . '%');

                $sheet->getStyle("D{$currentRow}")->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Plan dates
                $sheet->setCellValue("E{$currentRow}", $plan->plan_start_date ? \Carbon\Carbon::parse($plan->plan_start_date)->format('d/m/Y') : '');
                $sheet->setCellValue("F{$currentRow}", $plan->plan_end_date ? \Carbon\Carbon::parse($plan->plan_end_date)->format('d/m/Y') : '');
                $sheet->setCellValue("G{$currentRow}", $plan->plan_duration ?? '');

                // Actual dates
                $sheet->setCellValue("H{$currentRow}", $plan->actual_start_date ? \Carbon\Carbon::parse($plan->actual_start_date)->format('d/m/Y') : '');
                $sheet->setCellValue("I{$currentRow}", $plan->actual_end_date ? \Carbon\Carbon::parse($plan->actual_end_date)->format('d/m/Y') : '');
                $sheet->setCellValue("J{$currentRow}", $plan->actual_duration ?? '');

                // ✅ Remarks column
                $sheet->setCellValue("K{$currentRow}", $plan->remarks ?? '');
                $sheet->getStyle("K{$currentRow}")->getAlignment()->setWrapText(true);

                // ✅ Add borders to all columns including Remarks
                $sheet->getStyle("A{$currentRow}:K{$currentRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                    ],
                ]);

                $currentRow++;
                $taskNumber++;
            }

            $currentRow++;
        }

        // Auto-size columns A-J
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ✅ Set fixed width for Remarks column
        $sheet->getColumnDimension('K')->setWidth(40);

        // Save to public storage
        $companySlug = \Illuminate\Support\Str::slug($companyName);
        $timestamp = now()->format('Y-m-d_His');
        $filename = "Project_Plan_{$companySlug}_{$timestamp}.xlsx";
        $directory = 'project-plans';
        $filePath = "{$directory}/{$filename}";

        \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory($directory);

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);

        \Illuminate\Support\Facades\Storage::disk('public')->put(
            $filePath,
            file_get_contents($tempFile)
        );

        unlink($tempFile);

        \Illuminate\Support\Facades\Log::info("Project plan Excel generated", [
            'lead_id' => $lead->id,
            'company_name' => $companyName,
            'file_path' => $filePath,
            'filename' => $filename,
        ]);

        return storage_path('app/public/' . $filePath);
    }

    protected static function calculateWeekdays(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): int
    {
        $weekdays = 0;
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            // Check if current day is not Saturday (6) or Sunday (0)
            if (!$current->isWeekend()) {
                $weekdays++;
            }
            $current->addDay();
        }

        return $weekdays;
    }

    public static function createProjectPlansForModules(int $leadId, int $swId, array $modules): int
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
