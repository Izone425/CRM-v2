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
                        ->label('Download Excel')
                        ->icon('heroicon-o-arrow-down-tray')
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

                            return self::downloadProjectPlanExcel($lead, $softwareHandover);
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
                                            ->columnSpan(3),

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
                                            ->columnSpan(3),

                                        \Filament\Forms\Components\Placeholder::make("header_{$moduleName}_actual_duration")
                                            ->label('')
                                            ->content(new \Illuminate\Support\HtmlString(
                                                '<div>
                                                    <strong style="font-size: 14px;">Duration</strong>
                                                </div>'
                                            ))
                                            ->columnSpan(2),

                                        \Filament\Forms\Components\Placeholder::make("header_{$moduleName}_status")
                                            ->label('')
                                            ->content(new \Illuminate\Support\HtmlString(
                                                '<div>
                                                    <strong style="font-size: 14px;">Status</strong>
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

                                            DateRangePicker::make("plan_{$plan->id}_plan_date_range")
                                                ->hiddenLabel()
                                                ->default($planDateRangeValue)
                                                ->format('d/m/Y')
                                                ->displayFormat('DD/MM/YYYY')
                                                ->live()
                                                ->afterStateUpdated(function ($state, Set $set) use ($plan) {
                                                    if ($state) {
                                                        [$start, $end] = explode(' - ', $state);
                                                        $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', $start);
                                                        $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', $end);

                                                        // ✅ Calculate weekdays only (excluding weekends)
                                                        $weekdays = self::calculateWeekdays($startDate, $endDate);
                                                        $set("plan_{$plan->id}_plan_duration", $weekdays);

                                                        if ($plan->status === 'pending') {
                                                            $set("plan_{$plan->id}_status", 'in_progress');
                                                        }
                                                    }
                                                })
                                                ->columnSpan(3),

                                            TextInput::make("plan_{$plan->id}_plan_duration")
                                                ->hiddenLabel()
                                                ->numeric()
                                                ->default($plan->plan_duration)
                                                ->readOnly()
                                                ->suffix('days')
                                                ->columnSpan(2),

                                            DateRangePicker::make("plan_{$plan->id}_actual_date_range")
                                                ->hiddenLabel()
                                                ->default($actualDateRangeValue)
                                                ->format('d/m/Y')
                                                ->displayFormat('DD/MM/YYYY')
                                                ->live()
                                                ->afterStateUpdated(function ($state, Set $set) use ($plan) {
                                                    if ($state) {
                                                        [$start, $end] = explode(' - ', $state);
                                                        $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', $start);
                                                        $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', $end);

                                                        // ✅ Calculate weekdays only (excluding weekends)
                                                        $weekdays = self::calculateWeekdays($startDate, $endDate);
                                                        $set("plan_{$plan->id}_actual_duration", $weekdays);
                                                        $set("plan_{$plan->id}_status", 'completed');
                                                    }
                                                })
                                                ->columnSpan(3),

                                            TextInput::make("plan_{$plan->id}_actual_duration")
                                                ->hiddenLabel()
                                                ->numeric()
                                                ->default($plan->actual_duration)
                                                ->readOnly()
                                                ->suffix('days')
                                                ->columnSpan(2),

                                            Select::make("plan_{$plan->id}_status")
                                                ->hiddenLabel()
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
                                                ->columnSpan(2),
                                        ];
                                    }

                                    $moduleSchema = [];

                                    // Task rows
                                    foreach ($tableRows as $row) {
                                        $moduleSchema[] = \Filament\Forms\Components\Grid::make(16)
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

    protected static function downloadProjectPlanExcel(Lead $lead, SoftwareHandover $softwareHandover): StreamedResponse
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

        // ✅ Add header information (Company Name, Implementer, Progress Overview)

        // Row 1: Company Name
        $sheet->setCellValue("A{$currentRow}", 'Company Name');
        $sheet->mergeCells("A{$currentRow}:B{$currentRow}");
        $sheet->setCellValue("C{$currentRow}", $companyName);
        $sheet->mergeCells("C{$currentRow}:J{$currentRow}");
        $sheet->getStyle("A{$currentRow}:J{$currentRow}")->applyFromArray([
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
        $sheet->mergeCells("C{$currentRow}:J{$currentRow}");
        $sheet->getStyle("A{$currentRow}:J{$currentRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
        ]);
        $currentRow++;

        // Row 3: Project Progress Overview
        $sheet->setCellValue("A{$currentRow}", 'Project Progress Overview');
        $sheet->mergeCells("A{$currentRow}:J{$currentRow}");
        $sheet->getStyle("A{$currentRow}:J{$currentRow}")->applyFromArray([
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
            $module = $moduleData->module; // ✅ Get the module field

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

            // ✅ First row: Plan and Actual headers only
            // Plan header (yellow) - merged cells E:G
            $sheet->setCellValue("E{$currentRow}", 'Plan');
            $sheet->mergeCells("E{$currentRow}:G{$currentRow}");

            // Actual header (green) - merged cells H:J
            $sheet->setCellValue("H{$currentRow}", 'Actual');
            $sheet->mergeCells("H{$currentRow}:J{$currentRow}");

            // Style Plan header (yellow background)
            $sheet->getStyle("E{$currentRow}:G{$currentRow}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);

            // Style Actual header (green background)
            $sheet->getStyle("H{$currentRow}:J{$currentRow}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00FF00']],
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);

            $currentRow++;

            // ✅ Second row: Module code + Module name + Sub-headers (same row)
            $sheet->setCellValue("A{$currentRow}", ucfirst(strtolower($module)));
            $sheet->setCellValue("B{$currentRow}", $moduleName);
            $sheet->setCellValue("C{$currentRow}", 'Status');
            $sheet->setCellValue("D{$currentRow}", $modulePercentage . '%');

            // Style module name section (cyan background with BLACK font and borders)
            $sheet->getStyle("A{$currentRow}:D{$currentRow}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00B0F0']],
                'font' => ['bold' => true, 'color' => ['rgb' => '000000']], // ✅ Changed to black
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => [ // ✅ Added borders
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                ],
            ]);

            // Sub-headers: Start Date/End Date/Duration (columns E-J)
            $headers = ['Start Date', 'End Date', 'Duration', 'Start Date', 'End Date', 'Duration'];
            $col = 'E';
            foreach ($headers as $header) {
                $sheet->setCellValue("{$col}{$currentRow}", $header);

                // Apply yellow background to Plan columns (E, F, G)
                if (in_array($col, ['E', 'F', 'G'])) {
                    $sheet->getStyle("{$col}{$currentRow}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
                        'font' => ['bold' => true, 'color' => ['rgb' => '000000']], // ✅ Added black font
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        'borders' => [ // ✅ Added borders
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                        ],
                    ]);
                }
                // Apply green background to Actual columns (H, I, J)
                elseif (in_array($col, ['H', 'I', 'J'])) {
                    $sheet->getStyle("{$col}{$currentRow}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00FF00']],
                        'font' => ['bold' => true, 'color' => ['rgb' => '000000']], // ✅ Added black font
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        'borders' => [ // ✅ Added borders
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

                // ✅ Add center alignment for task percentage (column D)
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

                // Add borders
                $sheet->getStyle("A{$currentRow}:J{$currentRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                    ],
                ]);

                $currentRow++;
                $taskNumber++;
            }

            $currentRow++; // Add spacing between modules
        }

        // Auto-size columns
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Generate filename
        $filename = 'Project_Plan_' . str_replace(' ', '_', $companyName) . '_' . date('Y-m-d') . '.xlsx';

        // Create response
        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
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
