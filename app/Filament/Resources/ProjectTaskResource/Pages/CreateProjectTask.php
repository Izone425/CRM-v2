<?php

namespace App\Filament\Resources\ProjectTaskResource\Pages;

use App\Filament\Resources\ProjectTaskResource;
use App\Models\ProjectTask;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class CreateProjectTask extends CreateRecord
{
    protected static string $resource = ProjectTaskResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Don't mutate here, handle in handleRecordCreation instead
        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $moduleData = [
            'module' => $data['module'],
            'module_name' => $data['module_name'],
            'module_order' => $data['module_order'],
            'module_percentage' => $data['module_percentage'],
            'hr_version' => $data['hr_version'], // ✅ Keep hr_version
        ];

        $tasks = $data['tasks'] ?? [];

        // Validate that at least one task is provided
        if (empty($tasks)) {
            Notification::make()
                ->title('No Tasks Provided')
                ->body('Please add at least one task to the module.')
                ->danger()
                ->send();

            $this->halt();
        }

        $createdTasks = [];

        foreach ($tasks as $index => $taskData) {
            // Merge module data with task data
            $taskRecord = array_merge($moduleData, [
                'task_name' => $taskData['task_name'],
                'task_percentage' => $taskData['task_percentage'],
                'order' => $taskData['order'] ?? $index,
                'is_active' => $taskData['is_active'] ?? true,
            ]);

            $createdTasks[] = ProjectTask::create($taskRecord);
        }

        // Return the first created task as the "main" record
        return $createdTasks[0];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        $taskCount = count($this->data['tasks'] ?? []);
        return "Successfully created {$taskCount} task(s)";
    }
}
