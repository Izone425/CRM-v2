<?php
namespace App\Livewire;

use Livewire\Component;
use App\Models\SoftwareHandover;
use App\Models\ProjectTask;
use App\Models\ProjectPlan;
use App\Models\Lead;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class CustomerProjectPlan extends Component
{
    public $projectProgressData = [];
    public $hasProjectPlan = false;

    public function mount()
    {
        $this->loadProjectPlan();

        // Add debugging
        $customer = Auth::guard('customer')->user();
        Log::info('Customer ID: ' . ($customer->id ?? 'null'));
        Log::info('Lead ID: ' . ($customer->lead_id ?? 'null'));
        Log::info('Has Project Plan: ' . ($this->hasProjectPlan ? 'yes' : 'no'));
        Log::info('Progress Data: ', $this->projectProgressData);
    }

    public function loadProjectPlan()
    {
        $customer = Auth::guard('customer')->user();

        if (!$customer || !$customer->lead_id) {
            $this->hasProjectPlan = false;
            return;
        }

        $this->projectProgressData = $this->getProjectProgressData($customer->lead_id);
        $this->hasProjectPlan = !empty($this->projectProgressData['progressOverview']);
    }

    public function downloadProjectPlan()
    {
        $customer = Auth::guard('customer')->user();

        if (!$customer || !$customer->lead_id) {
            session()->flash('error', 'No customer or lead found');
            return;
        }

        $lead = Lead::find($customer->lead_id);
        $softwareHandover = SoftwareHandover::where('lead_id', $customer->lead_id)
            ->latest()
            ->first();

        if (!$softwareHandover) {
            session()->flash('error', 'No software handover found');
            return;
        }

        $filePath = $this->generateProjectPlanExcel($lead, $softwareHandover);

        if ($filePath && file_exists($filePath)) {
            return response()->download($filePath)->deleteFileAfterSend(true);
        }

        session()->flash('error', 'Failed to generate project plan');
    }

    protected function generateProjectPlanExcel(Lead $lead, SoftwareHandover $softwareHandover): ?string
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

            // First row: Plan and Actual headers only
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

            // Second row: Module code + Module name + Sub-headers
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

            // Sub-headers
            $headers = ['Start Date', 'End Date', 'Duration', 'Start Date', 'End Date', 'Duration'];
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

                // Add borders
                $sheet->getStyle("A{$currentRow}:J{$currentRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                    ],
                ]);

                $currentRow++;
                $taskNumber++;
            }

            $currentRow++;
        }

        // Auto-size columns
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Save to temp directory
        $companySlug = \Illuminate\Support\Str::slug($companyName);
        $timestamp = now()->format('Y-m-d_His');
        $filename = "Project_Plan_{$companySlug}_{$timestamp}.xlsx";
        $tempFile = sys_get_temp_dir() . '/' . $filename;

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        Log::info("Project plan Excel generated for customer", [
            'lead_id' => $lead->id,
            'company_name' => $companyName,
            'temp_file' => $tempFile,
        ]);

        return $tempFile;
    }

    private function getProjectProgressData($leadId)
    {
        $progressData = [
            'leadId' => $leadId,
            'selectedModules' => [],
            'swId' => null,
            'progressOverview' => [],
            'overallSummary' => [
                'totalTasks' => 0,
                'completedTasks' => 0,
                'overallProgress' => 0,
                'modules' => []
            ],
            'projectPlanGeneratedAt' => null
        ];

        try {
            $softwareHandover = SoftwareHandover::where('lead_id', $leadId)
                ->latest()
                ->first();

            if (!$softwareHandover) {
                return $progressData;
            }

            $progressData['projectPlanGeneratedAt'] = $softwareHandover->project_plan_generated_at;
            $progressData['selectedModules'] = $softwareHandover->getSelectedModules();
            $progressData['selectedModules'] = array_unique(array_merge(['phase 1', 'phase 2'], $progressData['selectedModules']));
            $progressData['swId'] = $softwareHandover->id;

            usort($progressData['selectedModules'], function($a, $b) {
                return ProjectTask::getModuleOrder($a) - ProjectTask::getModuleOrder($b);
            });

            $totalTasksAll = 0;
            $completedTasksAll = 0;

            foreach ($progressData['selectedModules'] as $module) {
                $moduleNames = ProjectTask::where('module', $module)
                    ->where('is_active', true)
                    ->select('module_name')
                    ->distinct()
                    ->get()
                    ->pluck('module_name')
                    ->toArray();

                usort($moduleNames, function($a, $b) {
                    $orderA = ProjectTask::where('module_name', $a)->value('module_order') ?? 999;
                    $orderB = ProjectTask::where('module_name', $b)->value('module_order') ?? 999;
                    return $orderA - $orderB;
                });

                foreach ($moduleNames as $moduleName) {
                    $modulePlans = ProjectPlan::where('lead_id', $leadId)
                        ->where('sw_id', $softwareHandover->id)
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
                            ];
                        })->values()->toArray();

                        $moduleOrder = ProjectTask::where('module_name', $moduleName)->value('module_order') ?? 999;

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
            }

            usort($progressData['overallSummary']['modules'], function($a, $b) {
                return $a['module_order'] - $b['module_order'];
            });

            $progressData['overallSummary']['totalTasks'] = $totalTasksAll;
            $progressData['overallSummary']['completedTasks'] = $completedTasksAll;
            $progressData['overallSummary']['overallProgress'] = $totalTasksAll > 0 ? round(($completedTasksAll / $totalTasksAll) * 100) : 0;

        } catch (\Exception $e) {
            Log::error('Error getting project progress data: ' . $e->getMessage());
        }

        return $progressData;
    }

    public function render()
    {
        return view('livewire.customer-project-plan');
    }
}
